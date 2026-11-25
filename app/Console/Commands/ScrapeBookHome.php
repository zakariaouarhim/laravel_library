<?php

namespace App\Console\Commands;

use App\Services\CatalogueLookupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Scrape thebookhome.com (بيت الكتب, Alexandria) into the existing
 * `catalogue_reference` table as a fifth reference source next to almouggar,
 * booksondemand, livremoi and aseeralkotb. Rows are keyed
 * `dedup_key = "bh:{id}"` — the prefix is how the review dashboard tells
 * sources apart (there is deliberately NO `source` column: `catalogue:import`
 * drop/recreates the table from the almouggar dump, so any added column would
 * vanish).
 *
 * The site is ASP.NET/IIS with no JSON API, so this parses the product HTML.
 * robots.txt is empty (no restrictions). Product ids are dense and sequential
 * from 1 to ~83,383, so this walks ids the way ak:scrape does.
 *
 * CAVEAT (same as ak/bod/lm): re-running `catalogue:import` WIPES all bh rows
 * (and orphans their catalogue_reviews). Re-run this with --from=1 afterwards,
 * which is a ~3 hour walk.
 *
 * Verified against the live site, 2026-11-24 — four things that are NOT obvious:
 *
 *  1. A slug redirect does NOT prove the product exists. `/product/index/{id}/x`
 *     301s to a canonical slug for ids whose page then renders `<title>خطأ</title>`
 *     (id 1000 is one). Existence is therefore judged from the fetched page
 *     (og:title present), never from the redirect — the difference is a 95%
 *     "hit rate" on redirects versus a real 87% yield.
 *  2. Cover URLs are published UNENCODED and 403 as-is: og:image carries raw
 *     Arabic in the path. The path segments must be percent-encoded (verified:
 *     raw = 403, encoded = 200).
 *  3. Descriptions carry mangled entities — the site emits "Andlaquo;" where
 *     "&laquo;" belongs (a double-encoding bug on their side). Repaired here, or
 *     every imported description inherits it.
 *  4. The ISBN field is sometimes a plain barcode (id 100 carries
 *     6223009864860, which has no 978/979 prefix); checksum-validated here,
 *     invalid ones stored null.
 *
 * Data notes:
 *  - prices are USD (their storefront), not our retail DH, so price is stored
 *    null and set by the admin at review time — same as ak. That caps
 *    completeness at 7, so review these with the quality filter lowered.
 *  - `edition` holds the publisher name (what the dashboard shows as "دار النشر").
 *  - this source does carry book dimensions, unlike ak.
 */
class ScrapeBookHome extends Command
{
    private const BASE = 'https://www.thebookhome.com';
    /** The site's own "no cover" artwork, served as og:image. See encodeUrl(). */
    private const PLACEHOLDER_COVER = 'siteDefult.jpg';
    private const USER_AGENT = 'LibraryFokara/1.0 (catalogue enrichment)';
    private const INSERT_CHUNK = 200;

    /**
     * The site's own double-encoding bug: "Andlaquo;" instead of "&laquo;".
     *
     * This matches the SHAPE, not a fixed list of names: an earlier allow-list
     * of 15 entities silently let "Andbull;" through, and each new slice of the
     * id range turns up another. repairEntities() converts a match only when
     * "&name;" is a real HTML entity, so "Andrew;" is left alone.
     *
     * Names must be 3+ characters, because the HTML5 table also defines very
     * short, word-like entities - "&or;" is a real entity (logical OR), so a
     * 2-character rule would rewrite "Andor;" to "∨". The only 2-character
     * names this source actually needs are lt and gt, allowed explicitly.
     *
     * Numeric references are mangled the same way ("And#8201;" for a thin
     * space, "And#39;" for an apostrophe) and need their own branches: a name
     * pattern cannot match "#8201". They turned up in 23 rows of the full 68k
     * backfill, all of them after the named-entity fix had already gone in.
     */
    private const MANGLED_ENTITIES = '/And(#x[0-9A-Fa-f]{1,5}|#[0-9]{1,6}|lt|gt|[A-Za-z][A-Za-z0-9]{2,9});/';

    protected $signature = 'bh:scrape
        {--from= : First product id (default: highest already-scraped bh id + 1)}
        {--to= : Last product id (default: walk until --stop-after consecutive misses)}
        {--limit= : Stop after N found books (smoke test)}
        {--stop-after=60 : Consecutive misses that mean end-of-catalogue}
        {--dry-run : Fetch and report, but write nothing}
        {--delay=350 : Milliseconds to sleep between request batches}
        {--concurrency=2 : Parallel requests per batch (keep small, be polite)}
        {--retries=4 : Sequential retries before an id is recorded as failed}
        {--refresh-existing : Also overwrite rows already held (default: new books only)}';

    protected $description = 'Scrape thebookhome.com into catalogue_reference (dedup_key prefix "bh:")';

    public function handle(): int
    {
        $limit     = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $dryRun    = (bool) $this->option('dry-run');
        $conc      = max(1, min(6, (int) $this->option('concurrency')));
        $retries   = max(1, min(8, (int) $this->option('retries')));
        $stopAfter = max(5, (int) $this->option('stop-after'));
        $refresh   = (bool) $this->option('refresh-existing');

        $from = $this->option('from') !== null ? max(1, (int) $this->option('from')) : $this->defaultFrom();
        // No listing endpoint exists, and bisecting the id range is unreliable
        // (gaps near the top make it converge on an id that does not exist).
        // The range ends in a clean cliff, so walk until enough consecutive
        // misses prove we are past the end.
        $to = $this->option('to') !== null ? (int) $this->option('to') : null;

        if ($to !== null && $from > $to) {
            $this->info("Nothing to do: from={$from} > to={$to} (already up to date?)");
            return 0;
        }

        // Ignition (dev) keeps the last 200 queries WITH bindings in memory;
        // each 200-row insert is a few hundred KB of bindings.
        DB::connection()->unsetEventDispatcher();

        $this->info("Scraping ids {$from}.." . ($to ?? 'end')
            . ($limit ? " (limit {$limit})" : '')
            . ($dryRun ? ' [dry run]' : '')
            . ($refresh ? ' [refreshing existing]' : ' [new rows only]'));

        $buffer = [];
        $samples = [];
        $found = 0;
        $missing = 0;
        $added = 0;
        $missStreak = 0;
        $errorStreak = 0;
        $failStreak = 0;
        $failed = [];
        $lastId = $from;

        for ($id = $from; $to === null || $id <= $to; $id += $conc) {
            $ids = range($id, $to === null ? $id + $conc - 1 : min($id + $conc - 1, $to));

            // PHASE 1 - resolve each id with a HEAD that does NOT follow redirects.
            //
            // A live id answers 301 -> /product/index/{id}/{slug}. A dead id
            // answers 301 -> /product/index/{id}, which redirects to ITSELF
            // forever. Following redirects automatically therefore turns every
            // dead id into a TooManyRedirects exception indistinguishable from a
            // real network failure - which is how an earlier version mistook 8
            // ordinary gaps in the id range for the site being down.
            //
            // Resolving by hand also makes a dead id cost one tiny HEAD instead
            // of a 170 KB page fetch, and costs a live id nothing extra: the
            // redirect hop happens either way.
            $heads = [];
            foreach ($ids as $i) {
                $heads[$i] = $this->client()->headAsync($this->path($i));
            }
            $resolved = \GuzzleHttp\Promise\Utils::settle($heads)->wait();

            $targets = [];
            foreach ($ids as $i) {
                $lastId = $i;
                $resp = ($resolved[$i]['state'] ?? null) === 'fulfilled' ? $resolved[$i]['value'] : null;

                if ($resp === null) {
                    $resp = $this->retrySingle($this->path($i), $retries, 'HEAD');
                    if ($resp === null) {
                        // Record and continue rather than abort: resume works off
                        // the HIGHEST scraped id, so stopping mid-range and
                        // resuming later would skip this id forever. The failed
                        // list printed at the end is what makes it recoverable.
                        $failed[] = $i;
                        if (++$failStreak >= 8) {
                            $this->flush($buffer, $dryRun, $refresh, $added);
                            $first = $i - $failStreak + 1;
                            $this->error("{$failStreak} consecutive transport failures ending at id {$i} - the site looks unreachable.");
                            $this->error("Resume with: php artisan bh:scrape --from={$first}");
                            return 1;
                        }
                        $missing++;
                        $missStreak++;
                        continue;
                    }
                }
                $failStreak = 0;

                $status = $resp->getStatusCode();
                if ($status >= 400) {
                    if (++$errorStreak >= 10) {
                        $this->flush($buffer, $dryRun, $refresh, $added);
                        $first = $i - $errorStreak + 1;
                        $this->error("HTTP {$status} at id {$i} - {$errorStreak} consecutive failures. Resume with: php artisan bh:scrape --from={$first}");
                        return 1;
                    }
                    $missing++;
                    $missStreak++;
                    usleep(2000 * 1000);
                    continue;
                }
                $errorStreak = 0;

                $target = $this->canonicalPath($i, $resp);
                if ($target === null) {
                    // No slug in the redirect: this id holds no product.
                    $missing++;
                    $missStreak++;
                    continue;
                }
                $targets[$i] = $target;
            }

            // PHASE 2 - fetch only the pages that actually exist.
            $pages = [];
            foreach ($targets as $i => $target) {
                $pages[$i] = $this->client()->getAsync($target);
            }
            $fetched = $pages === [] ? [] : \GuzzleHttp\Promise\Utils::settle($pages)->wait();

            foreach ($targets as $i => $target) {
                $resp = ($fetched[$i]['state'] ?? null) === 'fulfilled' ? $fetched[$i]['value'] : null;

                if ($resp === null) {
                    $resp = $this->retrySingle($target, $retries, 'GET');
                    if ($resp === null) {
                        $failed[] = $i;
                        $missing++;
                        $missStreak++;
                        continue;
                    }
                }

                if ($resp->getStatusCode() >= 400) {
                    $missing++;
                    $missStreak++;
                    continue;
                }

                // A slug redirect still does not prove the product is real: some
                // ids resolve to a slug whose page renders an error title.
                // toRow() returns null for those (no og:title).
                $row = $this->toRow($i, (string) $resp->getBody());
                if ($row === null) {
                    $missing++;
                    $missStreak++;
                    continue;
                }

                $missStreak = 0;
                $buffer[] = $row;
                $found++;
                if (count($samples) < 3) {
                    $samples[] = $row;
                }
                if (count($buffer) >= self::INSERT_CHUNK) {
                    $this->flush($buffer, $dryRun, $refresh, $added);
                }
                if ($limit !== null && $found >= $limit) {
                    break 2;
                }
            }

            // End of catalogue: only meaningful when we were not given a --to.
            if ($to === null && $missStreak >= $stopAfter) {
                $this->line("  Stopping: {$missStreak} consecutive misses ending at id {$lastId} — past the end of the catalogue.");
                break;
            }

            usleep(max(0, (int) $this->option('delay')) * 1000);

            if (($found + $missing) % 1000 < $conc && ($found + $missing) >= 1000) {
                $this->line('  …at id ' . $lastId . ": {$found} found, {$missing} missing, "
                    . round(memory_get_usage(true) / 1048576) . ' MB');
            }
        }

        $this->flush($buffer, $dryRun, $refresh, $added);

        foreach ($samples as $sample) {
            $this->newLine();
            foreach (['dedup_key', 'title', 'author', 'isbn', 'edition', 'category', 'langue', 'pages', 'dimensions', 'completeness', 'cover_url'] as $key) {
                $this->line("  {$key}: " . mb_substr((string) $sample[$key], 0, 110));
            }
        }
        $this->newLine();

        $held = $found - $added;
        $total = $dryRun ? '(dry run, nothing written)'
            : DB::table('catalogue_reference')->where('dedup_key', 'like', 'bh:%')->count() . ' bh rows now in catalogue_reference';
        $this->info("Done. {$found} books found, {$added} new, {$held} already held, {$missing} ids missing/unusable. {$total}");

        if ($failed !== []) {
            // These were never read, so they are NOT known to be absent. Resume
            // starts from the highest scraped id and would never come back for
            // them, so they have to be re-run explicitly.
            $this->newLine();
            $this->warn(count($failed) . ' id(s) could not be fetched after ' . $retries . ' retries each:');
            $this->line('  ' . implode(', ', array_slice($failed, 0, 40)) . (count($failed) > 40 ? ' …' : ''));
            $this->line('  Re-run just those ranges, e.g.: php artisan bh:scrape --from=' . $failed[0] . ' --to=' . $failed[0]);
        }

        return 0;
    }

    /** Any slug works: the site 301s to the canonical one. */
    private function path(int $id): string
    {
        return "/product/index/{$id}/x";
    }

    /**
     * The canonical product path from a 301, or null when the id holds no
     * product. A live id redirects to "/product/index/{id}/{slug}"; a dead one
     * to "/product/index/{id}" with no slug, which then loops to itself.
     */
    private function canonicalPath(int $id, \Psr\Http\Message\ResponseInterface $resp): ?string
    {
        $location = $resp->getHeaderLine('Location');
        if ($location === '') {
            // Served directly without a redirect: use the path we asked for.
            return $resp->getStatusCode() === 200 ? $this->path($id) : null;
        }

        $path = parse_url($location, PHP_URL_PATH) ?: $location;
        $marker = "/product/index/{$id}/";
        $pos = stripos($path, $marker);
        if ($pos === false) {
            return null;
        }

        return trim(substr($path, $pos + strlen($marker))) === '' ? null : $path;
    }

    /** Highest already-scraped bh id + 1 (resume), or 1. */
    private function defaultFrom(): int
    {
        $max = DB::table('catalogue_reference')
            ->where('dedup_key', 'like', 'bh:%')
            ->max('scraped_book_id');

        return $max ? (int) $max + 1 : 1;
    }

    /**
     * One Guzzle client for the whole run — same reasoning as ak:scrape: a
     * per-batch client leaked memory and could wedge in a CPU-spinning curl
     * loop. Redirects must be followed: every product id 301s to its slug.
     */
    private ?\GuzzleHttp\Client $client = null;

    private function client(): \GuzzleHttp\Client
    {
        return $this->client ??= new \GuzzleHttp\Client([
            'base_uri'        => self::BASE,
            'headers'         => [
                'User-Agent'      => self::USER_AGENT,
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'ar,en;q=0.8',
                // Measured: a product page is 159,591 bytes raw and 19,161
                // gzipped - 8.3x. Guzzle only advertises gzip if we ask for it
                // explicitly (its decode_content default sets CURLOPT_ENCODING
                // but then strips the header curl adds), so without this line a
                // full 68k-id backfill transfers ~9 GB instead of ~1.1 GB.
                // Guzzle inflates it transparently: the parser sees the same
                // 159,591 bytes either way.
                'Accept-Encoding' => 'gzip',
            ],
            // This site is slow and uneven — a cold product page has been
            // measured at ~9s — so the ceilings are well above ak:scrape's.
            'connect_timeout' => 15,
            'timeout'         => 45,
            'http_errors'     => false,
            // Resolved by hand - see PHASE 1. Following them automatically
            // makes a dead id (which redirects to itself forever) throw.
            'allow_redirects' => false,
            'curl'            => [CURLOPT_LOW_SPEED_LIMIT => 1, CURLOPT_LOW_SPEED_TIME => 45],
        ]);
    }

    /**
     * Sequential retries for an id whose concurrent request failed in
     * transport, backing off further each time (2s, 4s, 8s, …). The site
     * stalls intermittently, so a single slow moment must not end a run.
     */
    private function retrySingle(string $path, int $attempts, string $method = 'GET'): ?\Psr\Http\Message\ResponseInterface
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            usleep(min(16, 2 ** $attempt) * 1000 * 1000);
            try {
                return $this->client()->request($method, $path);
            } catch (\GuzzleHttp\Exception\TransferException $e) {
                // fall through to the next, longer-spaced attempt
            }
        }

        return null;
    }

    /** Write the buffered rows and clear the buffer. */
    private function flush(array &$buffer, bool $dryRun, bool $refresh, int &$added): void
    {
        if ($buffer === []) {
            return;
        }
        if (!$dryRun) {
            // Query builder on purpose: CatalogueReference is read-only by
            // convention ($timestamps = false).
            if ($refresh) {
                DB::table('catalogue_reference')->upsert(
                    $buffer,
                    ['dedup_key'],
                    array_values(array_diff(array_keys($buffer[0]), ['dedup_key', 'created_at']))
                );
                $added += count($buffer);
            } else {
                // INSERT IGNORE on the unique dedup_key: a row we already hold
                // is left exactly as first scraped — the admin may have
                // reviewed or imported it already. The return value is the
                // count of genuinely new books.
                $added += DB::table('catalogue_reference')->insertOrIgnore($buffer);
            }
        } else {
            $added += count($buffer);
        }
        $buffer = [];
    }

    /** One product page → one catalogue_reference row, or null if unusable. */
    private function toRow(int $id, string $html): ?array
    {
        if ($html === '') {
            return null;
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);

        $og = [];
        foreach ($xp->query('//meta[starts-with(@property,"og:")]') as $meta) {
            $og[substr($meta->getAttribute('property'), 3)] = $meta->getAttribute('content');
        }

        // A missing og:title is how a dead id announces itself: the page still
        // returns 200 and renders <title>خطأ</title>. ~13% of ids are like this.
        $title = $this->clean((string) ($og['title'] ?? ''));
        if ($title === '') {
            return null;
        }

        $detail     = $this->detailFields($xp);
        $author     = $this->joinField($detail, 'المؤلف', '، ');
        $publisher  = $this->joinField($detail, 'دار النشر', ' / ');
        $categories = $this->joinField($detail, 'التصنيفات', ' / ');
        $dimensions = $this->joinField($detail, 'حجم الكتاب', ' ');
        $pagesRaw   = $this->joinField($detail, 'عدد الصفحات', ' ');
        $pages      = (int) preg_replace('/\D+/', '', (string) $pagesRaw);

        $isbn        = $this->cleanIsbn($this->joinField($detail, 'ISBN', ' ') ?? '');
        $description = $this->clean(strip_tags($this->repairEntities((string) ($og['description'] ?? ''))));
        $title191    = mb_substr($title, 0, 191);
        $now         = now();

        $row = [
            'dedup_key'         => 'bh:' . $id,
            'scraped_book_id'   => $id,
            'isbn'              => $isbn,
            'title'             => $title191,
            'title_normalized'  => mb_substr(CatalogueLookupService::normalize($title191), 0, 191),
            'author'            => $author ? mb_substr($author, 0, 191) : null,
            'author_normalized' => $author ? mb_substr(CatalogueLookupService::normalize($author), 0, 191) : null,
            'description'       => $description !== '' ? $description : null,
            'price'             => null, // site prices are USD, not our retail — admin sets it at review
            'cover_url'         => $this->encodeUrl((string) ($og['image'] ?? '')),
            'category'          => $categories ? mb_substr($categories, 0, 191) : null,
            'edition'           => $publisher ? mb_substr($publisher, 0, 191) : null,
            'langue'            => $this->langueFor($title, $isbn, $categories),
            'pages'             => $pages > 0 ? (string) $pages : null,
            'dimensions'        => $dimensions ? mb_substr($dimensions, 0, 191) : null,
            'source_url'        => ($og['url'] ?? null) ?: self::BASE . "/product/index/{$id}",
            'completeness'      => 0,
            'source_last_seen'  => $now,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
        $row['completeness'] = $this->completeness($row);

        return $row;
    }

    /**
     * The product detail block is a flat list of
     *   <div class="bookDetail|bookwriter"><label>KEY</label> VALUE </div>
     * where VALUE is either a second <label> (pages, dimensions, ISBN) or a
     * <div> of <a> links (author, publisher, categories — which can hold
     * several). Matching on "the text after the label" instead would pick up
     * unrelated links: on ids with no categories it grabs the FAQ link.
     *
     * @return array<string, string[]>
     */
    private function detailFields(\DOMXPath $xp): array
    {
        $fields = [];

        foreach ($xp->query('//div[contains(@class,"bookDetail") or contains(@class,"bookwriter")]') as $block) {
            $labels = $xp->query('./label', $block);
            if ($labels->length === 0) {
                continue;
            }
            // Labels are inconsistently padded in the markup (" التصنيفات").
            $key = $this->clean($labels->item(0)->textContent);
            if ($key === '' || isset($fields[$key])) {
                continue;
            }

            $values = [];
            $links = $xp->query('./div//a', $block);
            if ($links->length > 0) {
                foreach ($links as $a) {
                    $text = $this->clean($a->textContent);
                    if ($text !== '') {
                        $values[] = $text;
                    }
                }
            } elseif ($labels->length > 1) {
                $text = $this->clean($labels->item(1)->textContent);
                if ($text !== '') {
                    $values[] = $text;
                }
            }

            if ($values !== []) {
                $fields[$key] = $values;
            }
        }

        return $fields;
    }

    /** @param array<string, string[]> $fields */
    private function joinField(array $fields, string $key, string $glue): ?string
    {
        $values = $fields[$key] ?? [];

        return $values === [] ? null : implode($glue, $values);
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** "Andlaquo;" -> "&laquo;" -> "«"; "And#39;" -> "&#39;" -> ''. See MANGLED_ENTITIES. */
    private function repairEntities(string $text): string
    {
        $text = preg_replace_callback(self::MANGLED_ENTITIES, function (array $m): string {
            $entity = '&' . $m[1] . ';';
            // html_entity_decode returns the input unchanged when the entity is
            // not real, which is exactly the test we want: repair "Andbull;",
            // leave a name like "Andrew;" ("&rew;" is nothing) as it stands.
            $decoded = html_entity_decode($entity, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return $decoded === $entity ? $m[0] : $decoded;
        }, $text);

        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Percent-encode the path of a cover URL. The site publishes og:image with
     * raw Arabic in the path, which the image host answers with 403; the same
     * URL encoded answers 200. Decoding first keeps this idempotent, so an
     * already-encoded URL is not double-encoded.
     */
    private function encodeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // A book with no artwork gets the site's placeholder as its og:image -
        // 10,591 of the 68,016 rows in the full backfill. Keeping it is worse
        // than keeping nothing: it is published as a site-RELATIVE path, so our
        // review grid resolves it against alfokara.com and shows a broken
        // image, the importer would fetch that 404 as a cover, and it counts
        // toward completeness as though a real cover existed.
        if (stripos($url, self::PLACEHOLDER_COVER) !== false) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['path'])) {
            return null;
        }

        // Without a host the value is only usable if it is a rooted path.
        // parse_url('not a url') yields a path too, and resolving THAT against
        // the site produced "https://www.thebookhome.comnot%20a%20url".
        if (empty($parts['host']) && $parts['path'][0] !== '/') {
            return null;
        }

        $path = implode('/', array_map(
            fn ($segment) => rawurlencode(rawurldecode($segment)),
            explode('/', $parts['path'])
        ));

        // Any other relative og:image belongs to THIS site, so resolve it here
        // rather than storing a path that would resolve against ours.
        $scheme = $parts['scheme'] ?? (string) parse_url(self::BASE, PHP_URL_SCHEME);
        $host   = $parts['host'] ?? (string) parse_url(self::BASE, PHP_URL_HOST);

        return $scheme . '://' . $host . $path
            . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }

    /**
     * Arabic-first store: Arabic script in the title wins, then this site's own
     * shelf label (it files foreign titles under "English Books" / "French
     * Books"), then the ISBN registration group. The shelf label matters here
     * because foreign titles frequently carry no ISBN at all.
     */
    private function langueFor(string $title, ?string $isbn, ?string $category = null): ?string
    {
        if (preg_match('/\p{Arabic}/u', $title)) {
            return 'Arabe';
        }
        if ($category !== null) {
            if (stripos($category, 'english') !== false) {
                return 'Anglais';
            }
            if (stripos($category, 'french') !== false || stripos($category, 'fran') !== false) {
                return 'Français';
            }
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

    /**
     * Checksum-validated ISBN, or null. This field very often holds a plain
     * barcode instead (id 100 carries 6223009864860).
     *
     * Deliberately matches the WHOLE digit run rather than searching for an
     * ISBN-shaped substring inside it: a 13-digit barcode's first 10 digits
     * will happily look like an ISBN-10, so substring matching turns junk into
     * a confident-looking wrong ISBN. Both lengths are checksum-verified.
     */
    private function cleanIsbn(string $text): ?string
    {
        $raw = strtoupper(preg_replace('/[^0-9X]/i', '', $text));

        if (strlen($raw) === 13) {
            return (str_starts_with($raw, '978') || str_starts_with($raw, '979')) && $this->isbn13Valid($raw)
                ? $raw
                : null;
        }
        if (strlen($raw) === 10) {
            return $this->isbn10Valid($raw) ? $raw : null;
        }

        return null;
    }

    private function isbn10Valid(string $isbn): bool
    {
        $sum = 0;
        foreach (str_split($isbn) as $i => $c) {
            if ($c === 'X') {
                if ($i !== 9) {
                    return false;
                }
                $sum += 10 * (10 - $i);
                continue;
            }
            if (!ctype_digit($c)) {
                return false;
            }
            $sum += (int) $c * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    private function isbn13Valid(string $isbn): bool
    {
        $check = 0;
        foreach (str_split($isbn) as $i => $d) {
            $check += (int) $d * ($i % 2 === 0 ? 1 : 3);
        }

        return $check % 10 === 0;
    }

    /**
     * Honest 0-8 score on the same fields the other sources are scored on.
     * price is always null here (USD), so bh rows top out at 7 — review them
     * with the dashboard quality filter lowered.
     */
    private function completeness(array $row): int
    {
        $fields = ['title', 'author', 'isbn', 'description', 'price', 'cover_url', 'category', 'langue'];

        return count(array_filter($fields, fn ($f) => !empty($row[$f])));
    }
}
