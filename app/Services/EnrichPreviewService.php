<?php

namespace App\Services;

/**
 * Builds the "enrich preview" payload used by the import review modals: queries
 * the local reference catalogue AND the language-aware web pipeline (BNF/Google/
 * Open Library/Wikipedia) and returns EVERY source's value per field so the admin
 * picks which to apply. Shared by reader-import and catalogue-import. No writes.
 */
class EnrichPreviewService
{
    private const PRIORITY = ['catalogue', 'bnf', 'google_books', 'open_library', 'wikipedia'];
    private const LABELS   = [
        'catalogue'    => 'الكتالوج',
        'bnf'          => 'BNF',
        'google_books' => 'Google Books',
        'open_library' => 'Open Library',
        'wikipedia'    => 'Wikipedia',
    ];

    public function __construct(
        private CatalogueLookupService $catalogue,
        private BookIngestionService $ingestion,
    ) {
    }

    /**
     * @param array $current  Echoed-back current values: description, page_num,
     *                        publisher, language, isbn.
     * @return array{status:int, body:array}
     */
    public function preview(string $name, string $author, ?string $isbn, string $language, array $current = []): array
    {
        $name     = trim($name);
        $author   = trim($author);
        $language = $language ?: 'arabic';
        $cleanIsbn = $this->cleanIsbn($isbn);

        if ($name === '' && !$cleanIsbn) {
            return ['status' => 422, 'body' => ['success' => false, 'message' => 'لا يوجد عنوان أو ISBN للبحث.']];
        }

        try {
            $results = [];
            $method  = null;

            // 1) Local reference catalogue — always (does its own isbn/title match).
            $cat = $this->catalogue->lookup($isbn, $name, $author);
            if ($cat) {
                $results['catalogue'] = $cat;
                $method = 'catalogue';
            }

            // 2) Web pipeline — prefer ISBN, fall back to title+author.
            $web = [];
            if ($cleanIsbn) {
                $web = $this->ingestion->resolveSourcesByIsbn($cleanIsbn, $language);
                if ($web) $method = $method ?: 'ISBN';
            }
            if (empty($web) && $name !== '') {
                $web = $this->ingestion->resolveSources($name, $author, $language);
                if ($web) $method = $method ?: 'title+author';
            }
            $results += $web; // keep catalogue key first

            if (empty($results)) {
                return ['status' => 200, 'body' => ['success' => true, 'found' => false, 'message' => 'لم يتم العثور على بيانات في أي مصدر.']];
            }

            $options = function (string $field, ?callable $transform = null) use ($results) {
                $out = [];
                foreach (self::PRIORITY as $src) {
                    if (empty($results[$src]) || empty($results[$src][$field])) continue;
                    $value = $transform ? $transform($results[$src][$field]) : $results[$src][$field];
                    if ($value === null || $value === '' || $value === 0) continue;
                    $out[] = ['source' => $src, 'label' => self::LABELS[$src], 'value' => $value];
                }
                return $out;
            };

            $fields = array_filter([
                'description' => $options('description', fn($v) => trim(strip_tags($v))),
                'image'       => $options('image_url'),
                'page_num'    => $options('page_num', fn($v) => (int) $v > 0 ? (int) $v : null),
                'publisher'   => $options('publisher', fn($v) => trim($v)),
                'language'    => $options('language'),
                'isbn'        => $options('isbn'),
            ], fn($o) => !empty($o));

            return ['status' => 200, 'body' => [
                'success'       => true,
                'found'         => !empty($fields),
                'search_method' => $method,
                'sources'       => array_values(array_map(fn($s) => self::LABELS[$s] ?? $s, array_keys($results))),
                'current'       => $current,
                'fields'        => $fields,
            ]];
        } catch (\Throwable $e) {
            return ['status' => 500, 'body' => ['success' => false, 'message' => 'تعذّر الاتصال بالمصادر: ' . $e->getMessage()]];
        }
    }

    /**
     * Flat, deduped, lower-cased list of subject/genre strings merged from every
     * source (catalogue + BNF/Google/Open Library/Wikipedia). Same lookup path as
     * preview() — used by the category-suggestion endpoint to feed the keyword
     * matcher. Returns [] on any failure (never throws to the caller).
     *
     * @return string[]
     */
    public function subjects(string $name, string $author, ?string $isbn, string $language): array
    {
        $name      = trim($name);
        $author    = trim($author);
        $language  = $language ?: 'arabic';
        $cleanIsbn = $this->cleanIsbn($isbn);

        if ($name === '' && !$cleanIsbn) {
            return [];
        }

        try {
            $results = [];

            $cat = $this->catalogue->lookup($isbn, $name, $author);
            if ($cat) {
                $results['catalogue'] = $cat;
            }

            $web = [];
            if ($cleanIsbn) {
                $web = $this->ingestion->resolveSourcesByIsbn($cleanIsbn, $language);
            }
            if (empty($web) && $name !== '') {
                $web = $this->ingestion->resolveSources($name, $author, $language);
            }
            $results += $web;

            $subjects = [];
            foreach ($results as $src) {
                foreach ((array) ($src['categories'] ?? []) as $s) {
                    $s = mb_strtolower(trim((string) $s));
                    if ($s !== '') {
                        $subjects[$s] = true; // dedupe
                    }
                }
            }

            return array_keys($subjects);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Normalize an ISBN to 10/13 chars, or null. */
    private function cleanIsbn(?string $isbn): ?string
    {
        if (empty($isbn)) {
            return null;
        }
        $clean = preg_replace('/[^0-9X]/', '', strtoupper($isbn));

        return in_array(strlen($clean), [10, 13], true) ? $clean : null;
    }
}
