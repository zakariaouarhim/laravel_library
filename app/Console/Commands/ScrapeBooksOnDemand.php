<?php

namespace App\Console\Commands;

use App\Services\CatalogueLookupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Scrape booksondemand.ma (Shopify storefront, ~2.9k books) into the existing
 * `catalogue_reference` table as a second reference source next to almouggar.
 * Rows are keyed `dedup_key = "bod:{shopify_product_id}"` — that prefix is how
 * the review dashboard tells the sources apart (there is deliberately NO
 * `source` column: `catalogue:import` drop/recreates the table from the
 * almouggar dump, so any added column would vanish).
 *
 * CAVEAT: re-running `catalogue:import` therefore WIPES all bod rows (and
 * orphans their catalogue_reviews). That's fine — this command is a fully
 * idempotent upsert; just re-run it afterwards.
 *
 * Data notes (verified against the live store):
 *  - `vendor` holds the AUTHOR (store-name vendors are dropped).
 *  - variants[].barcode is always null, but ~90% of cover FILENAMES are the
 *    ISBN-13 (e.g. .../files/9782824625256.jpg) — extracted with a checksum
 *    check. Rows without one match by title_normalized, so those columns MUST
 *    use the same normalization as the dump (CatalogueLookupService::normalize()).
 *  - price is stored "149,00 DH" (comma decimal) because the dashboard's
 *    parsePrice() strips dots before converting commas.
 *  - category = the most specific (smallest) non-generic collection a product
 *    belongs to; mapping to local categories happens later at review time.
 */
class ScrapeBooksOnDemand extends Command
{
    private const BASE = 'https://booksondemand.ma';
    private const PER_PAGE = 250;

    /** Generic, non-genre collections that would pollute `category`. */
    private const COLLECTION_HANDLE_DENYLIST = [
        'all', 'all-products', 'frontpage',
    ];
    private const COLLECTION_TITLE_DENY_RE =
        '/bestsell|bundle|all products|all books|new .*(arrival|release)|(summer|spring|winter|holiday) read|sale|discount|gift|of the month|bfcm/i';

    protected $signature = 'bod:scrape
        {--limit= : Stop after N products (smoke test)}
        {--dry-run : Fetch and report, but write nothing}
        {--delay=750 : Milliseconds to sleep between HTTP requests}
        {--skip-collections : Skip the category-mapping pass (category left null)}
        {--include-bundles : Also scrape bundle products (skipped by default)}';

    protected $description = 'Scrape booksondemand.ma into catalogue_reference (dedup_key prefix "bod:")';

    public function handle(): int
    {
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $dryRun = (bool) $this->option('dry-run');

        [$categoryMap, $collectionSizes] = $this->option('skip-collections')
            ? [[], []]
            : $this->buildCategoryMap();

        $page = 1;
        $scraped = 0;
        $skippedBundles = 0;
        $upserted = 0;
        $samples = [];

        while (true) {
            $products = $this->fetchJson(self::BASE . '/products.json?limit=' . self::PER_PAGE . "&page={$page}")['products'] ?? [];
            if ($products === []) {
                break;
            }
            $this->info("Products page {$page}: " . count($products) . ' items');

            $rows = [];
            $reachedLimit = false;
            foreach ($products as $product) {
                if ($limit !== null && $scraped >= $limit) {
                    $reachedLimit = true;
                    break;
                }
                if (!$this->option('include-bundles') && $this->isBundle($product)) {
                    $skippedBundles++;
                    continue;
                }
                $rows[] = $row = $this->toRow($product, $categoryMap, $collectionSizes);
                $scraped++;
                if (count($samples) < 3) {
                    $samples[] = $row;
                }
            }

            if (!$dryRun && $rows !== []) {
                // Query builder on purpose: the CatalogueReference model is
                // read-only by convention ($timestamps = false).
                $upserted += DB::table('catalogue_reference')->upsert(
                    $rows,
                    ['dedup_key'],
                    array_values(array_diff(array_keys($rows[0]), ['dedup_key', 'created_at']))
                );
            }

            if ($reachedLimit) {
                break;
            }
            $page++;
        }

        foreach ($samples as $sample) {
            $this->newLine();
            foreach (['dedup_key', 'title', 'author', 'isbn', 'price', 'category', 'langue', 'completeness', 'cover_url'] as $key) {
                $this->line("  {$key}: " . mb_substr((string) $sample[$key], 0, 110));
            }
            $this->line('  description: ' . mb_substr((string) $sample['description'], 0, 160));
        }
        $this->newLine();

        $total = $dryRun ? '(dry run, nothing written)'
            : DB::table('catalogue_reference')->where('dedup_key', 'like', 'bod:%')->count() . ' bod rows now in catalogue_reference';
        $this->info("Done. {$scraped} products scraped, {$skippedBundles} bundles skipped, {$upserted} rows upserted. {$total}");

        return 0;
    }

    /** GET a JSON endpoint politely (UA + retry + delay). */
    private function fetchJson(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'LibraryFokara/1.0 (catalogue enrichment; contact: zakariaouarhim2002@gmail.com)',
        ])->timeout(20)->retry(3, 1500)->get($url)->throw()->json();

        usleep(max(0, (int) $this->option('delay')) * 1000);

        return $response ?? [];
    }

    /**
     * Map product id → member collection titles (genre collections only), plus
     * each collection's member count so toRow() can pick the most specific one.
     *
     * @return array{0: array<int, string[]>, 1: array<string, int>}
     */
    private function buildCategoryMap(): array
    {
        $collections = $this->fetchJson(self::BASE . '/collections.json?limit=' . self::PER_PAGE)['collections'] ?? [];

        $kept = array_filter($collections, fn ($c) => !in_array($c['handle'], self::COLLECTION_HANDLE_DENYLIST, true)
            && !preg_match(self::COLLECTION_TITLE_DENY_RE, $c['title']));
        $this->info('Collections: ' . count($collections) . ' total, ' . count($kept) . ' kept for category mapping');

        $map = [];
        $sizes = [];
        foreach ($kept as $collection) {
            $count = 0;
            for ($page = 1; ; $page++) {
                $products = $this->fetchJson(
                    self::BASE . "/collections/{$collection['handle']}/products.json?limit=" . self::PER_PAGE . "&page={$page}"
                )['products'] ?? [];
                if ($products === []) {
                    break;
                }
                foreach ($products as $product) {
                    $map[$product['id']][] = $collection['title'];
                    $count++;
                }
            }
            $sizes[$collection['title']] = $count;
            $this->line("  {$collection['title']}: {$count} products");
        }

        return [$map, $sizes];
    }

    /** Bundle packs aren't books — skip them (tag or vendor says "bundle"). */
    private function isBundle(array $product): bool
    {
        foreach ((array) ($product['tags'] ?? []) as $tag) {
            if (stripos($tag, 'bundle') !== false) {
                return true;
            }
        }

        return stripos($product['vendor'] ?? '', 'bundle') !== false;
    }

    /** One Shopify product → one catalogue_reference row. */
    private function toRow(array $product, array $categoryMap, array $collectionSizes): array
    {
        $title = mb_substr(trim($product['title'] ?? ''), 0, 191);
        $author = $this->cleanAuthor($product['vendor'] ?? null);
        $price = $product['variants'][0]['price'] ?? null;
        $coverUrl = $product['images'][0]['src'] ?? null;
        $isbn = $this->isbnFromCoverUrl($coverUrl);
        $now = now();

        $row = [
            'dedup_key'        => 'bod:' . $product['id'],
            'scraped_book_id'  => $product['id'],
            'isbn'             => $isbn,
            'title'            => $title,
            'title_normalized' => mb_substr(CatalogueLookupService::normalize($title), 0, 191),
            'author'            => $author,
            'author_normalized' => $author ? mb_substr(CatalogueLookupService::normalize($author), 0, 191) : null,
            'description'      => $this->sanitizeDescription($product['body_html'] ?? null),
            // Comma decimal ("149,00 DH"): the dashboard's parsePrice() strips
            // dots before converting commas, so "149.00 DH" would read as 14900.
            'price'            => $price !== null ? number_format((float) $price, 2, ',', '') . ' DH' : null,
            'cover_url'        => $coverUrl,
            'category'         => $this->bestCategory($product['id'], $categoryMap, $collectionSizes),
            'edition'          => null,
            'langue'           => $this->detectLangue($product, $categoryMap, $isbn),
            'pages'            => null,
            'dimensions'       => null,
            'source_url'       => self::BASE . '/products/' . ($product['handle'] ?? ''),
            'completeness'     => 0,
            'source_last_seen' => $now,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];
        $row['completeness'] = $this->completeness($row);

        return $row;
    }

    /** Most specific category = the smallest collection the product belongs to. */
    private function bestCategory(int $productId, array $categoryMap, array $collectionSizes): ?string
    {
        $titles = $categoryMap[$productId] ?? [];
        if ($titles === []) {
            return null;
        }
        usort($titles, fn ($a, $b) => [$collectionSizes[$a], $a] <=> [$collectionSizes[$b], $b]);

        return mb_substr($titles[0], 0, 191);
    }

    /**
     * The store fills no barcode field, but ~90% of cover files are named after
     * the ISBN-13 ("…/files/9782824625256.jpg"). Checksum-validated to avoid
     * mistaking other 13-digit filenames for ISBNs.
     */
    private function isbnFromCoverUrl(?string $url): ?string
    {
        $file = basename(parse_url((string) $url, PHP_URL_PATH) ?: '');
        if (!preg_match('/(?<!\d)(97[89]\d{10})(?!\d)/', $file, $m)) {
            return null;
        }

        $digits = str_split($m[1]);
        $check = 0;
        foreach ($digits as $i => $d) {
            $check += (int) $d * ($i % 2 === 0 ? 1 : 3);
        }

        return $check % 10 === 0 ? $m[1] : null;
    }

    /** Vendor is the author unless it's the store itself. */
    private function cleanAuthor(?string $vendor): ?string
    {
        $vendor = trim((string) $vendor);
        if ($vendor === ''
            || stripos($vendor, 'booksondemand') !== false
            || stripos($vendor, 'books on demand') !== false) {
            return null;
        }

        return mb_substr($vendor, 0, 191);
    }

    /**
     * body_html → plain text. Some descriptions contain whole pasted web pages
     * (scripts, chat UI markup), so this must cope with arbitrary garbage.
     */
    private function sanitizeDescription(?string $html): ?string
    {
        if (!$html) {
            return null;
        }
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#si', '', $html);
        $text = preg_replace('#<(?:br|/p|/div|/li|/h[1-6])\b[^>]*>#i', "\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = trim(preg_replace('/\s*\n\s*(\s*\n\s*)+/u', "\n\n", $text));

        return mb_strlen($text) >= 20 ? $text : null;
    }

    /** Almouggar-style langue token ("Anglais"/"Français"/"Arabe") or null. */
    private function detectLangue(array $product, array $categoryMap, ?string $isbn): ?string
    {
        $haystack = implode(' ', array_merge(
            (array) ($product['tags'] ?? []),
            $categoryMap[$product['id']] ?? []
        ));

        if (preg_match('/french|français|francais/i', $haystack)) {
            return 'Français';
        }
        if (preg_match('/\beng\b|english/i', $haystack)) {
            return 'Anglais';
        }
        if (preg_match('/arabic|arabe/i', $haystack) || preg_match('/\p{Arabic}/u', $product['title'] ?? '')) {
            return 'Arabe';
        }

        // Fall back to the ISBN registration group: 978-0/978-1 = English-speaking
        // area, 978-2 = French-speaking. Tags are silent for most rows here.
        if ($isbn !== null) {
            if ($isbn[3] === '0' || $isbn[3] === '1') {
                return 'Anglais';
            }
            if ($isbn[3] === '2') {
                return 'Français';
            }
        }

        return null;
    }

    /** Honest 0-8 score on the same fields almouggar rows are scored on. */
    private function completeness(array $row): int
    {
        $fields = ['title', 'author', 'isbn', 'description', 'price', 'cover_url', 'category', 'langue'];

        return count(array_filter($fields, fn ($f) => !empty($row[$f])));
    }
}
