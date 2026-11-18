<?php

namespace App\Console\Commands;

use App\Services\CatalogueLookupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Scrape aseeralkotb.com (أسير الكتب, Arabic bookstore) into the existing
 * `catalogue_reference` table as a fourth reference source next to almouggar,
 * booksondemand and livremoi. Rows are keyed `dedup_key = "ak:{book_id}"` — the
 * prefix is how the review dashboard tells sources apart (there is deliberately
 * NO `source` column: `catalogue:import` drop/recreates the table from the
 * almouggar dump, so any added column would vanish).
 *
 * Why the JSON API and not the HTML pages: the site's HTML is behind a
 * Cloudflare JS challenge, but its first-party JSON:API (the one the mobile
 * apps use) is served unchallenged, and robots.txt allows all crawling.
 * `GET /api/v1/books/{id}` returns everything in one response: title,
 * description, ISBN, cover, page count, authors, publisher, categories.
 * Listing endpoints ignore pagination params (always "latest 10"), so this
 * command walks book ids instead — they are dense and sequential from ~5 up to
 * the newest id (read off the latest-books listing); gaps return 404.
 *
 * CAVEAT (same as lm/bod): re-running `catalogue:import` WIPES all ak rows
 * (and orphans their catalogue_reviews). This command is an idempotent upsert
 * and resumes from the highest already-scraped ak id — just re-run it with
 * --from=1 afterwards.
 *
 * Data notes (verified against the live API, 2026-11-16):
 *  - description/publisher/categories ARE present (unlike lm) — Arabic books
 *    get a real description without the enrich step.
 *  - prices are in USD (their international storefront), not our retail DH,
 *    so price is stored null and set by the admin at review time.
 *  - `isbn` is sometimes junk (barcodes like 8675…); checksum-validated here,
 *    invalid ones stored null.
 *  - `edition` holds the publisher name (that's what the dashboard shows as
 *    "دار النشر" for catalogue rows).
 */
class ScrapeAseerAlKotob extends Command
{
    private const BASE = 'https://www.aseeralkotb.com';
    private const USER_AGENT = 'LibraryFokara/1.0 (catalogue enrichment)';
    private const UPSERT_CHUNK = 200;

    protected $signature = 'ak:scrape
        {--from= : First book id (default: highest already-scraped ak id + 1)}
        {--to= : Last book id (default: newest id on the site)}
        {--limit= : Stop after N found books (smoke test)}
        {--dry-run : Fetch and report, but write nothing}
        {--delay=350 : Milliseconds to sleep between request batches}
        {--concurrency=3 : Parallel requests per batch (keep small, be polite)}';

    protected $description = 'Scrape aseeralkotb.com JSON API into catalogue_reference (dedup_key prefix "ak:")';

    public function handle(): int
    {
        $limit  = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $dryRun = (bool) $this->option('dry-run');
        $conc   = max(1, min(6, (int) $this->option('concurrency')));

        $from = $this->option('from') !== null ? max(1, (int) $this->option('from')) : $this->defaultFrom();
        $to   = $this->option('to') !== null ? (int) $this->option('to') : $this->newestId();
        if ($to === null) {
            $this->error('Could not determine the newest book id from ' . self::BASE . '/api/v1/books');
            return 1;
        }
        if ($from > $to) {
            $this->info("Nothing to do: from={$from} > to={$to} (already up to date?)");
            return 0;
        }

        // Ignition (dev) keeps the last 200 queries WITH bindings in memory; each
        // 200-row upsert is ~220 KB of bindings, so that alone pins ~45 MB.
        DB::connection()->unsetEventDispatcher();

        $this->info("Scraping ids {$from}..{$to}" . ($limit ? " (limit {$limit})" : '') . ($dryRun ? ' [dry run]' : ''));

        $buffer      = [];
        $samples     = [];
        $found       = 0;
        $missing     = 0;
        $written     = 0;
        $errorStreak = 0;

        for ($id = $from; $id <= $to; $id += $conc) {
            $ids = range($id, min($id + $conc - 1, $to));

            $promises = [];
            foreach ($ids as $i) {
                $promises[$i] = $this->client()->getAsync("/api/v1/books/{$i}");
            }
            $results = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

            foreach ($ids as $i) {
                $resp = ($results[$i]['state'] ?? null) === 'fulfilled' ? $results[$i]['value'] : null;

                // Transport error (timeout, reset): retry that id sequentially;
                // a second failure aborts (flushing the buffer first) so a
                // long run can be resumed.
                if ($resp === null) {
                    $resp = $this->retrySingle($i);
                    if ($resp === null) {
                        $this->flush($buffer, $dryRun, $written);
                        $this->error("Transport failure at id {$i} after retries. Resume with: php artisan ak:scrape --from={$i}");
                        return 1;
                    }
                }

                $status = $resp->getStatusCode();
                if ($status === 404) {
                    $missing++;
                    $errorStreak = 0;
                    continue;
                }
                if ($status < 200 || $status >= 300) {
                    // Some ids are permanently broken server-side (e.g. 4827
                    // always answers 500): skip those. Only a long streak of
                    // failures means the server itself is struggling (or rate
                    // limiting us) — back off between tries, then abort.
                    if (++$errorStreak >= 10) {
                        $this->flush($buffer, $dryRun, $written);
                        $first = $i - $errorStreak + 1;
                        $this->error("HTTP {$status} at id {$i} — {$errorStreak} consecutive failures. Resume with: php artisan ak:scrape --from={$first}");
                        return 1;
                    }
                    $missing++;
                    usleep(2000 * 1000);
                    continue;
                }
                $errorStreak = 0;

                $json = json_decode((string) $resp->getBody(), true);
                $row  = $this->toRow(is_array($json['data'] ?? null) ? $json['data'] : []);
                if ($row === null) {
                    $missing++;
                    continue;
                }

                $buffer[] = $row;
                $found++;
                if (count($samples) < 3) {
                    $samples[] = $row;
                }
                if (count($buffer) >= self::UPSERT_CHUNK) {
                    $this->flush($buffer, $dryRun, $written);
                }
                if ($limit !== null && $found >= $limit) {
                    break 2;
                }
            }

            if ($id + $conc <= $to) {
                usleep(max(0, (int) $this->option('delay')) * 1000);
            }
            if (($found + $missing) % 1000 < $conc && ($found + $missing) >= 1000) {
                $this->line('  …at id ' . end($ids) . ": {$found} found, {$missing} missing, "
                    . round(memory_get_usage(true) / 1048576) . ' MB');
            }
        }

        $this->flush($buffer, $dryRun, $written);

        foreach ($samples as $sample) {
            $this->newLine();
            foreach (['dedup_key', 'title', 'author', 'isbn', 'edition', 'category', 'langue', 'pages', 'completeness', 'cover_url'] as $key) {
                $this->line("  {$key}: " . mb_substr((string) $sample[$key], 0, 110));
            }
        }
        $this->newLine();

        $total = $dryRun ? '(dry run, nothing written)'
            : DB::table('catalogue_reference')->where('dedup_key', 'like', 'ak:%')->count() . ' ak rows now in catalogue_reference';
        $this->info("Done. {$found} books found, {$missing} ids missing/unusable. {$total}");

        return 0;
    }

    /** Highest already-scraped ak book id + 1 (resume), or 1. */
    private function defaultFrom(): int
    {
        $max = DB::table('catalogue_reference')
            ->where('dedup_key', 'like', 'ak:%')
            ->max('scraped_book_id');

        return $max ? (int) $max + 1 : 1;
    }

    /** Newest book id on the site, read off the latest-books listing. */
    private function newestId(): ?int
    {
        try {
            $data = Http::withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'])
                ->timeout(25)->retry(2, 1500)
                ->get(self::BASE . '/api/v1/books')
                ->json('data');

            $id = (int) ($data[0]['id'] ?? 0);

            return $id > 0 ? $id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * One Guzzle client for the whole run. Laravel 8's Http::pool() built a new
     * client + curl multi handle per batch; over tens of thousands of batches
     * that leaked memory and eventually wedged in a CPU-spinning curl loop
     * with a stalled transfer. A single client reuses keep-alive connections,
     * and the low-speed abort kills a stalled transfer even if `timeout` doesn't fire.
     */
    private ?\GuzzleHttp\Client $client = null;

    private function client(): \GuzzleHttp\Client
    {
        return $this->client ??= new \GuzzleHttp\Client([
            'base_uri'        => self::BASE,
            'headers'         => ['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'],
            'connect_timeout' => 10,
            'timeout'         => 25,
            'http_errors'     => false,
            'curl'            => [CURLOPT_LOW_SPEED_LIMIT => 1, CURLOPT_LOW_SPEED_TIME => 20],
        ]);
    }

    /** Sequential retries for an id whose concurrent request failed in transport. */
    private function retrySingle(int $id): ?\Psr\Http\Message\ResponseInterface
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            usleep(1500 * 1000);
            try {
                return $this->client()->get("/api/v1/books/{$id}");
            } catch (\GuzzleHttp\Exception\TransferException $e) {
                // fall through to next attempt
            }
        }

        return null;
    }

    /** Upsert the buffered rows and clear the buffer. */
    private function flush(array &$buffer, bool $dryRun, int &$written): void
    {
        if ($buffer === []) {
            return;
        }
        if (!$dryRun) {
            // Query builder on purpose: CatalogueReference is read-only by
            // convention ($timestamps = false).
            DB::table('catalogue_reference')->upsert(
                $buffer,
                ['dedup_key'],
                array_values(array_diff(array_keys($buffer[0]), ['dedup_key', 'created_at']))
            );
        }
        $written += count($buffer);
        $buffer = [];
    }

    /** One API book payload → one catalogue_reference row, or null if unusable. */
    private function toRow(array $data): ?array
    {
        $attr = $data['attributes'] ?? [];
        $rel  = $data['relationships'] ?? [];
        $id   = (int) ($data['id'] ?? 0);

        $title = trim(preg_replace('/\s+/u', ' ', (string) ($attr['title'] ?? '')));
        if ($id <= 0 || $title === '') {
            return null;
        }

        $authors = array_values(array_filter(array_map(
            fn ($a) => trim((string) ($a['attributes']['name'] ?? '')),
            (array) ($rel['authors'] ?? [])
        )));
        $author = $authors !== [] ? mb_substr(implode('، ', $authors), 0, 191) : null;

        $publisher = trim((string) ($rel['publisher']['attributes']['name'] ?? ''));

        $categories = array_values(array_filter(array_map(
            fn ($c) => trim((string) ($c['attributes']['title'] ?? '')),
            (array) ($rel['categories'] ?? [])
        )));

        $description = trim(strip_tags((string) ($attr['description'] ?? '')));
        $isbn        = $this->cleanIsbn((string) ($attr['isbn'] ?? ''));
        $pages       = (int) ($attr['page_count'] ?? 0);
        $title191    = mb_substr($title, 0, 191);
        $now         = now();

        $row = [
            'dedup_key'         => 'ak:' . $id,
            'scraped_book_id'   => $id,
            'isbn'              => $isbn,
            'title'             => $title191,
            'title_normalized'  => mb_substr(CatalogueLookupService::normalize($title191), 0, 191),
            'author'            => $author,
            'author_normalized' => $author ? mb_substr(CatalogueLookupService::normalize($author), 0, 191) : null,
            'description'       => $description !== '' ? $description : null,
            'price'             => null, // site prices are USD, not our retail — admin sets it at review
            'cover_url'         => ($attr['poster'] ?? null) ?: null,
            'category'          => $categories !== [] ? mb_substr(implode(' / ', $categories), 0, 191) : null,
            'edition'           => $publisher !== '' ? mb_substr($publisher, 0, 191) : null,
            'langue'            => $this->langueFor($title, $isbn),
            'pages'             => $pages > 0 ? (string) $pages : null,
            'dimensions'        => null,
            'source_url'        => self::BASE . (($attr['path'] ?? null) ?: "/books/{$id}"),
            'completeness'      => 0,
            'source_last_seen'  => $now,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
        $row['completeness'] = $this->completeness($row);

        return $row;
    }

    /** Arabic-first store: Arabic script in the title wins, then ISBN group. */
    private function langueFor(string $title, ?string $isbn): ?string
    {
        if (preg_match('/\p{Arabic}/u', $title)) {
            return 'Arabe';
        }
        if ($isbn !== null && strlen($isbn) === 13) {
            if ($isbn[3] === '0' || $isbn[3] === '1') {
                return 'Anglais';
            }
            if ($isbn[3] === '2') {
                return 'Français';
            }
        }

        return null;
    }

    /** Checksum-validated 13/10-char ISBN, or null (the API field is often a junk barcode). */
    private function cleanIsbn(string $text): ?string
    {
        if (!preg_match('/(97[89]\d{10}|\d{9}[\dX])/i', str_replace(['-', ' '], '', $text), $m)) {
            return null;
        }
        $isbn = strtoupper($m[1]);

        return (strlen($isbn) === 13 && $this->isbn13Valid($isbn)) || strlen($isbn) === 10 ? $isbn : null;
    }

    private function isbn13Valid(string $isbn): bool
    {
        $check = 0;
        foreach (str_split($isbn) as $i => $d) {
            $check += (int) $d * ($i % 2 === 0 ? 1 : 3);
        }

        return $check % 10 === 0;
    }

    /** Honest 0-8 score on the same fields almouggar/bod/lm rows are scored on. */
    private function completeness(array $row): int
    {
        $fields = ['title', 'author', 'isbn', 'description', 'price', 'cover_url', 'category', 'langue'];

        return count(array_filter($fields, fn ($f) => !empty($row[$f])));
    }
}
