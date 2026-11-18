<?php

namespace App\Console\Commands;

use App\Services\CatalogueLookupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Scrape livremoi.ma's IN-STOCK store (enstock.livremoi.ma) into the existing
 * `catalogue_reference` table as a third reference source next to almouggar and
 * booksondemand. Rows are keyed `dedup_key = "lm:{product_id}"` — that prefix is
 * how the review dashboard tells the sources apart (there is deliberately NO
 * `source` column: `catalogue:import` drop/recreates the table from the almouggar
 * dump, so any added column would vanish).
 *
 * Why the in-stock subdomain and not the main site: livremoi.ma's main catalogue
 * is a ~3.4M-URL Magento aggregator, almost all "Sur commande" (on-order) foreign
 * titles with no price/stock — infeasible and irrelevant. enstock.livremoi.ma is
 * the curated store of books actually in stock, with price + stock + ISBN + cover
 * + author, organised into ~60 rayons (shelves). Its listing pages show 30 books
 * per HTTP request, so no per-book page fetch is needed.
 *
 * CAVEAT: re-running `catalogue:import` WIPES all lm rows (and orphans their
 * catalogue_reviews). That's fine — this command is a fully idempotent upsert;
 * just re-run it afterwards.
 *
 * Data notes (verified against the live store):
 *  - Each `.book-card` carries: product id (in the livremoi.ma product href),
 *    cover (images.epagine.fr/…/{isbn}_1_75.jpg), title (h5.book-title), author
 *    ("par …"), explicit ISBN ("ISBN: …"), price (span.price, e.g. "110.0 Dhs").
 *  - Descriptions are NOT on the card and are deliberately left null — the admin
 *    fills them at review time via the enrich + AI-rewrite buttons.
 *  - price is stored "110,00 DH" (comma decimal) because the dashboard's
 *    parsePrice() strips dots before converting commas.
 *  - a book appears in several rayons; the FIRST rayon that yields it defines its
 *    category/langue hint (keep-first dedup by product id).
 */
class ScrapeLivremoi extends Command
{
    private const BASE = 'https://enstock.livremoi.ma';

    /**
     * Rayon slug => readable French category hint (shown in the review modal;
     * the local category is resolved by the suggester at review time, so this is
     * only a hint — null for generic/promo shelves). `papeterie` is intentionally
     * absent: stationery, not books. Order matters a little: specific genres are
     * listed before broad buckets so keep-first dedup lands a useful hint.
     */
    private const RAYONS = [
        'religion'                        => 'Religion',
        'histoire'                        => 'Histoire',
        'cuisine'                         => 'Cuisine',
        'poesie'                          => 'Poésie',
        'theatre'                         => 'Théâtre',
        'polar'                           => 'Polar',
        'thriller'                        => 'Thriller',
        'sf-fantasy'                      => 'Science-fiction & Fantasy',
        'love-is-in-the-air'              => 'Romance',
        'dark-romance'                    => 'Dark romance',
        'self-help'                       => 'Développement personnel',
        'devpersonnel'                    => 'Développement personnel',
        'sante-bien-etre'                 => 'Santé & Bien-être',
        'maternite'                       => 'Maternité',
        'sciences-humaines'               => 'Sciences humaines',
        'arts-culture'                    => 'Arts & Culture',
        'cinema-photographie'             => 'Cinéma & Photographie',
        'musique'                         => 'Musique',
        'peinture'                        => 'Peinture',
        'architecture'                    => 'Architecture',
        'beaux-livres'                    => 'Beaux livres',
        'nature-animaux'                  => 'Nature & Animaux',
        'loisirs-sports'                  => 'Loisirs & Sports',
        'methode-langue'                  => 'Méthodes de langue',
        'scolaire'                        => 'Scolaire',
        'parascolaire'                    => 'Parascolaire',
        'pedagogie'                       => 'Pédagogie',
        'mangas'                          => 'Mangas',
        'mangas-anglais'                  => 'Mangas (English)',
        'bande-dessinees'                 => 'Bandes dessinées',
        'bande-dessinees-enfant'          => 'Bandes dessinées jeunesse',
        'marocain'                        => 'Littérature marocaine',
        'livre-arabe-adulte'             => 'كتب عربية',
        'livres-arabe-enfant'            => 'كتب أطفال عربية',
        'anglais-adult'                   => 'English fiction',
        'litterature-english'             => 'English literature',
        'humanities-in-english'           => 'Humanities (English)',
        'sci-fi-in-english'               => 'Science-fiction (English)',
        'litterature-etrangere'           => 'Littérature étrangère',
        'litterature-francophone'         => 'Littérature francophone',
        // Jeunesse
        'eveil'                           => 'Éveil (petite enfance)',
        'activites-enfant'                => 'Activités enfant',
        'doc-jeunesse'                    => 'Documentaire jeunesse',
        '3-6ans'                          => 'Jeunesse 3-6 ans',
        '6-9ans'                          => 'Jeunesse 6-9 ans',
        '912ans'                          => 'Jeunesse 9-12 ans',
        'young-adults'                    => 'Young adult',
        'marocain-enfant'                 => 'Jeunesse marocaine',
        'anglais-enfant'                  => 'Jeunesse (English)',
        'enfant-espagnol'                 => 'Jeunesse (Espagnol)',
        'zero-three-years-old-in-english' => 'Jeunesse 0-3 ans (English)',
        'three-six-years-old-in-english'  => 'Jeunesse 3-6 ans (English)',
        'six-nine-years-old-in-english'   => 'Jeunesse 6-9 ans (English)',
        'nine-twelve-years-old-in-english' => 'Jeunesse 9-12 ans (English)',
        'coffrets'                        => 'Coffrets',
        // Generic / promotional shelves (books, but no genre hint).
        'coup-de-coeur'                   => null,
        'petits-prix-adultes'             => null,
        'petits-prix-jeunesse'            => null,
        'livres-promotion'                => null,
    ];

    protected $signature = 'lm:scrape
        {--limit= : Stop after N unique books (smoke test)}
        {--dry-run : Fetch and report, but write nothing}
        {--delay=750 : Milliseconds to sleep between HTTP requests}
        {--rayon= : Scrape a single rayon slug only}';

    protected $description = 'Scrape enstock.livremoi.ma (in-stock) into catalogue_reference (dedup_key prefix "lm:")';

    public function handle(): int
    {
        $limit  = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $dryRun = (bool) $this->option('dry-run');

        $rayons = $this->option('rayon')
            ? [$this->option('rayon') => self::RAYONS[$this->option('rayon')] ?? null]
            : self::RAYONS;

        // Keep-first dedup across rayons: product id => row.
        $books   = [];
        $samples = [];
        $reached = false;

        foreach ($rayons as $slug => $categoryLabel) {
            if ($reached) {
                break;
            }
            $langue = $this->langueForSlug($slug);
            $pageCount = 0;

            for ($page = 1; ; $page++) {
                $html = $this->fetchHtml(self::BASE . "/rayon/{$slug}?page={$page}");
                if ($html === null) {
                    break; // 404 past the last page = end of this rayon
                }
                $cards = $this->parseCards($html);
                if ($cards === []) {
                    break;
                }
                $pageCount += count($cards);

                foreach ($cards as $card) {
                    $id = $card['id'];
                    if (!$id || isset($books[$id])) {
                        continue; // no/broken product id (e.g. /id/0), or keep-first
                    }
                    if ($limit !== null && count($books) >= $limit) {
                        $reached = true;
                        break;
                    }
                    $books[$id] = $row = $this->toRow($card, $categoryLabel, $langue);
                    if (count($samples) < 3) {
                        $samples[] = $row;
                    }
                }
                if ($reached) {
                    break;
                }
            }
            $this->line("  rayon {$slug}: {$pageCount} cards seen, " . count($books) . ' unique so far');
        }

        if (!$dryRun && $books !== []) {
            $rows = array_values($books);
            foreach (array_chunk($rows, 500) as $chunk) {
                // Query builder on purpose: CatalogueReference is read-only by
                // convention ($timestamps = false).
                DB::table('catalogue_reference')->upsert(
                    $chunk,
                    ['dedup_key'],
                    array_values(array_diff(array_keys($chunk[0]), ['dedup_key', 'created_at']))
                );
            }
        }

        foreach ($samples as $sample) {
            $this->newLine();
            foreach (['dedup_key', 'title', 'author', 'isbn', 'price', 'category', 'langue', 'completeness', 'cover_url'] as $key) {
                $this->line("  {$key}: " . mb_substr((string) $sample[$key], 0, 110));
            }
        }
        $this->newLine();

        $total = $dryRun ? '(dry run, nothing written)'
            : DB::table('catalogue_reference')->where('dedup_key', 'like', 'lm:%')->count() . ' lm rows now in catalogue_reference';
        $this->info('Done. ' . count($books) . " unique books scraped. {$total}");

        return 0;
    }

    /**
     * GET an HTML page politely (UA + retry + delay). Returns null on 404 —
     * enstock returns 404 for a page past the last one, which is how we detect
     * the end of a rayon; any other non-2xx is a real error and throws.
     */
    private function fetchHtml(string $url): ?string
    {
        // Manual retry (connection errors only): Http::retry() would call
        // Response::throw() internally on the end-of-pagination 404 to drive a
        // retry, so the 404 could never reach the status check below.
        $resp = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $resp = Http::withHeaders([
                    'User-Agent' => 'LibraryFokara/1.0 (catalogue enrichment; contact: zakariaouarhim2002@gmail.com)',
                ])->timeout(25)->get($url);
                break;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($attempt === 3) {
                    throw $e;
                }
                usleep(1500 * 1000);
            }
        }

        usleep(max(0, (int) $this->option('delay')) * 1000);

        if ($resp->status() === 404) {
            return null; // past the last page = end of this rayon
        }

        return $resp->throw()->body();
    }

    /**
     * Parse every `.book-card` on a rayon listing page into a raw field array.
     *
     * @return array<int, array{id: ?int, isbn: ?string, title: ?string, author: ?string, price: ?string, cover: ?string}>
     */
    private function parseCards(string $html): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        // The <?xml encoding> prolog forces UTF-8 so Arabic titles decode right.
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);

        $cards = [];
        foreach ($xp->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' book-card ')]") as $node) {
            $href  = $this->xpText($xp, ".//a[contains(@href,'catalog/product/view/id/')]/@href", $node);
            $cover = $this->xpText($xp, ".//div[contains(@class,'book-image')]//img/@src", $node);
            $title = $this->xpText($xp, ".//h5[contains(@class,'book-title')]", $node);
            $price = $this->xpText($xp, ".//span[contains(@class,'price')]", $node);

            // Author ("par …") and ISBN ("ISBN: …") are plain <p> siblings.
            $author = null;
            $isbnText = null;
            foreach ($xp->query(".//div[contains(@class,'book-info')]//p", $node) as $p) {
                $t = trim(preg_replace('/\s+/u', ' ', $p->textContent));
                if (stripos($t, 'par ') === 0) {
                    $author = trim(mb_substr($t, 4));
                } elseif (stripos($t, 'ISBN') === 0) {
                    $isbnText = $t;
                }
            }

            $id = null;
            if ($href && preg_match('#/id/(\d+)#', $href, $m)) {
                $id = (int) $m[1];
            }

            $cards[] = [
                'id'     => $id,
                'isbn'   => $this->cleanIsbn($isbnText) ?? $this->isbnFromCoverUrl($cover),
                'title'  => $title !== null ? trim(preg_replace('/\s+/u', ' ', $title)) : null,
                'author' => $author !== '' ? $author : null,
                'price'  => $this->parsePrice($price),
                'cover'  => $cover ?: null,
            ];
        }

        return $cards;
    }

    /** First matching node's trimmed text (or attribute value), or null. */
    private function xpText(\DOMXPath $xp, string $expr, \DOMNode $ctx): ?string
    {
        $nodes = $xp->query($expr, $ctx);
        if (!$nodes || $nodes->length === 0) {
            return null;
        }
        $v = trim($nodes->item(0)->textContent);

        return $v === '' ? null : $v;
    }

    /** One parsed card → one catalogue_reference row (bod's schema). */
    private function toRow(array $card, ?string $categoryLabel, ?string $langue): array
    {
        $title  = mb_substr((string) $card['title'], 0, 191);
        $author = $card['author'] ? mb_substr($card['author'], 0, 191) : null;
        $isbn   = $card['isbn'];
        $now    = now();

        $row = [
            'dedup_key'         => 'lm:' . $card['id'],
            'scraped_book_id'   => $card['id'],
            'isbn'              => $isbn,
            'title'             => $title,
            'title_normalized'  => mb_substr(CatalogueLookupService::normalize($title), 0, 191),
            'author'            => $author,
            'author_normalized' => $author ? mb_substr(CatalogueLookupService::normalize($author), 0, 191) : null,
            'description'       => null, // filled at review time (enrich + AI rewrite)
            'price'             => $card['price'],
            'cover_url'         => $card['cover'],
            'category'          => $categoryLabel ? mb_substr($categoryLabel, 0, 191) : null,
            'edition'           => null,
            'langue'            => $langue ?? $this->langueFromIsbnOrTitle($isbn, $title),
            'pages'             => null,
            'dimensions'        => null,
            'source_url'        => 'https://livremoi.ma/catalog/product/view/id/' . $card['id'],
            'completeness'      => 0,
            'source_last_seen'  => $now,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
        $row['completeness'] = $this->completeness($row);

        return $row;
    }

    /** Almouggar-style langue token from the rayon slug, or null. */
    private function langueForSlug(string $slug): ?string
    {
        if (preg_match('/english|anglais/i', $slug)) {
            return 'Anglais';
        }
        if (preg_match('/arabe/i', $slug)) {
            return 'Arabe';
        }

        return null;
    }

    /** Fallback langue: ISBN registration group, then Arabic script in the title. */
    private function langueFromIsbnOrTitle(?string $isbn, string $title): ?string
    {
        if ($isbn !== null && strlen($isbn) === 13) {
            if ($isbn[3] === '0' || $isbn[3] === '1') {
                return 'Anglais';
            }
            if ($isbn[3] === '2') {
                return 'Français';
            }
        }
        if (preg_match('/\p{Arabic}/u', $title)) {
            return 'Arabe';
        }

        return null;
    }

    /** "110.0 Dhs" → "110,00 DH" (comma decimal, per the dashboard's parsePrice). */
    private function parsePrice(?string $raw): ?string
    {
        if (!$raw || !preg_match('/(\d+(?:[.,]\d+)?)/', $raw, $m)) {
            return null;
        }
        $value = (float) str_replace(',', '.', $m[1]);
        if ($value <= 0) {
            return null;
        }

        return number_format($value, 2, ',', '') . ' DH';
    }

    /** "ISBN: 9782017351924" → validated 13/10-digit ISBN, or null. */
    private function cleanIsbn(?string $text): ?string
    {
        if (!$text || !preg_match('/(97[89]\d{10}|\d{9}[\dX])/i', str_replace(['-', ' '], '', $text), $m)) {
            return null;
        }
        $isbn = strtoupper($m[1]);

        return (strlen($isbn) === 13 && $this->isbn13Valid($isbn)) || strlen($isbn) === 10 ? $isbn : null;
    }

    /**
     * Cover files are named after the ISBN-13 ("…/924/9782017351924_1_75.jpg").
     * Checksum-validated to avoid mistaking other digit runs for ISBNs.
     */
    private function isbnFromCoverUrl(?string $url): ?string
    {
        $file = basename(parse_url((string) $url, PHP_URL_PATH) ?: '');
        if (!preg_match('/(?<!\d)(97[89]\d{10})(?!\d)/', $file, $m)) {
            return null;
        }

        return $this->isbn13Valid($m[1]) ? $m[1] : null;
    }

    private function isbn13Valid(string $isbn): bool
    {
        $check = 0;
        foreach (str_split($isbn) as $i => $d) {
            $check += (int) $d * ($i % 2 === 0 ? 1 : 3);
        }

        return $check % 10 === 0;
    }

    /** Honest 0-8 score on the same fields almouggar/bod rows are scored on. */
    private function completeness(array $row): int
    {
        $fields = ['title', 'author', 'isbn', 'description', 'price', 'cover_url', 'category', 'langue'];

        return count(array_filter($fields, fn ($f) => !empty($row[$f])));
    }
}
