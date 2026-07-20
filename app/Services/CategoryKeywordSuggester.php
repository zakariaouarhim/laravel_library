<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Best-effort category suggestion for catalogue items, which carry no real
 * category (the source only has "Produits"). Matches keywords in the title +
 * publisher against curated category names, falling back to a per-language
 * default. Categories are resolved by NAME at runtime (cached), so it stays
 * correct across environments regardless of category ids. The admin always
 * confirms/edits in the modal — this only pre-fills a sensible default.
 */
class CategoryKeywordSuggester
{
    /**
     * Ordered keyword => category-name rules. First match wins. Keywords are
     * matched case-insensitively against a normalized "title publisher" string.
     */
    private const RULES = [
        // Arabic
        ['kw' => ['رواية', 'روايات'],                        'cat' => 'روايات'],
        ['kw' => ['ديوان', 'شعر', 'قصائد'],                  'cat' => 'شعر عربي'],
        ['kw' => ['قصص', 'قصة', 'حكايات'],                    'cat' => 'قصص قصيرة'],
        ['kw' => ['اطفال', 'أطفال', 'الطفل', 'ناشئة'],        'cat' => 'قصص الأطفال'],
        ['kw' => ['قران', 'قرآن', 'تفسير', 'حديث', 'فقه', 'عقيده', 'عقيدة', 'اسلام', 'إسلام', 'السيره النبويه', 'السيرة النبوية', 'دعاء', 'اذكار', 'أذكار'], 'cat' => 'كتب دينية'],
        ['kw' => ['نحو', 'اعراب', 'إعراب', 'قواعد', 'صرف', 'بلاغه', 'بلاغة'], 'cat' => 'لغويات'],
        ['kw' => ['تاريخ'],                                   'cat' => 'تاريخ عربي'],
        ['kw' => ['فلسفه', 'فلسفة'],                          'cat' => 'فلسفة'],
        ['kw' => ['طبخ', 'مطبخ', 'وصفات'],                    'cat' => 'طبخ'],
        ['kw' => ['تنميه', 'تنمية', 'تطوير الذات', 'النجاح'], 'cat' => 'تطوير الذات'],
        ['kw' => ['اقتصاد', 'استثمار', 'مال', 'تسويق'],       'cat' => 'علم المال'],
        ['kw' => ['سياس'],                                    'cat' => 'الكتب السياسية'],
        ['kw' => ['طب', 'صحه', 'صحة'],                        'cat' => 'طب'],

        // French
        ['kw' => ['roman'],                                   'cat' => 'Littérature française'],
        ['kw' => ['poésie', 'poesie', 'poèmes'],              'cat' => 'Littérature française'],
        ['kw' => ['histoire'],                                'cat' => 'Histoire moderne'],
        ['kw' => ['cuisine', 'recettes'],                     'cat' => 'Cuisine'],
        ['kw' => ['philosophie'],                             'cat' => 'Essais'],
        ['kw' => ['enfant', 'jeunesse'],                      'cat' => 'Contes de fées'],
        ['kw' => ['science-fiction'],                         'cat' => 'Science-fiction'],

        // English
        ['kw' => ['novel'],                                   'cat' => 'Novels'],
        ['kw' => ['poetry', 'poems'],                         'cat' => 'Poetry'],
        ['kw' => ['history'],                                 'cat' => 'History'],
        ['kw' => ['cooking', 'recipes'],                      'cat' => 'Cooking'],
        ['kw' => ['philosophy'],                              'cat' => 'Philosophy'],
        ['kw' => ['children', 'kids'],                        'cat' => 'Children'],

        // API subject words (BNF dc:subject / Google Books categories / OL subjects).
        // Kept AFTER the language-specific rules so an explicit title keyword wins;
        // these catch books whose subjects come only from the enrichment sources.
        ['kw' => ['juvenile', 'juvenile fiction', 'picture book'], 'cat' => 'Children'],
        ['kw' => ['religion', 'islam', 'islamic', 'religieux'],    'cat' => 'كتب دينية'],
        ['kw' => ['fiction'],                                      'cat' => 'Novels'],
        ['kw' => ['biography', 'biographie', 'autobiography'],     'cat' => 'History'],
        ['kw' => ['self-help', 'personal development'],            'cat' => 'تطوير الذات'],
        ['kw' => ['business', 'economics', 'économie', 'finance'], 'cat' => 'علم المال'],
        ['kw' => ['science'],                                      'cat' => 'Science'],
    ];

    /** Fallback category name per language when no keyword matches. */
    private const LANG_DEFAULT = [
        'arabic'  => 'أدب عربي',
        'french'  => 'Littérature française',
        'english' => 'Novels',
    ];

    private const OTHER_DEFAULT = 'أدب عالمي';

    /**
     * @param string[] $subjects  Subject/genre strings from the enrichment sources
     *                            (BNF/Google Books/Open Library), matched alongside
     *                            the title/publisher/description.
     * @return array{category_ids: int[], primary_category_id: ?int}
     */
    public function suggest(?string $title, ?string $publisher, ?string $language, ?string $description = null, array $subjects = []): array
    {
        $map = $this->nameToId();
        $parts = array_filter([
            $title,
            $publisher,
            $description,
            $subjects ? implode(' ', $subjects) : null,
        ]);
        $hay = ' ' . mb_strtolower(trim(implode(' ', $parts))) . ' ';

        foreach (self::RULES as $rule) {
            foreach ($rule['kw'] as $kw) {
                if (mb_strpos($hay, mb_strtolower($kw)) !== false && isset($map[$rule['cat']])) {
                    return $this->one($map[$rule['cat']]);
                }
            }
        }

        // Language fallback.
        $default = self::LANG_DEFAULT[$language] ?? self::OTHER_DEFAULT;
        if (isset($map[$default])) {
            return $this->one($map[$default]);
        }
        if (isset($map[self::OTHER_DEFAULT])) {
            return $this->one($map[self::OTHER_DEFAULT]);
        }

        return ['category_ids' => [], 'primary_category_id' => null];
    }

    private function one(int $id): array
    {
        return ['category_ids' => [$id], 'primary_category_id' => $id];
    }

    /**
     * Candidate category NAMES (that exist locally) for the AI-fallback shortlist:
     * every keyword rule that matches the haystack, plus any subject string that is
     * itself an exact local category name, plus the language default — deduped,
     * preserving order. Bounded so the AI prompt stays small/cheap.
     *
     * @param string[] $subjects
     * @return string[]
     */
    public function candidateNames(?string $title, ?string $language, ?string $description = null, array $subjects = [], int $limit = 12): array
    {
        $map = $this->nameToId();
        $hay = ' ' . mb_strtolower(trim(($title ?? '') . ' ' . ($description ?? '') . ' ' . ($subjects ? implode(' ', $subjects) : ''))) . ' ';

        $names = [];
        foreach (self::RULES as $rule) {
            if (!isset($map[$rule['cat']]) || in_array($rule['cat'], $names, true)) {
                continue;
            }
            foreach ($rule['kw'] as $kw) {
                if (mb_strpos($hay, mb_strtolower($kw)) !== false) {
                    $names[] = $rule['cat'];
                    break;
                }
            }
        }

        // Subjects that happen to be exact local category names.
        foreach ($subjects as $s) {
            $s = trim((string) $s);
            if ($s !== '' && isset($map[$s]) && !in_array($s, $names, true)) {
                $names[] = $s;
            }
        }

        $default = self::LANG_DEFAULT[$language] ?? self::OTHER_DEFAULT;
        if (isset($map[$default]) && !in_array($default, $names, true)) {
            $names[] = $default;
        }

        return array_slice($names, 0, $limit);
    }

    /** Resolve a category name to its local id (or null), using the cached map. */
    public function idForName(string $name): ?int
    {
        return $this->nameToId()[trim($name)] ?? null;
    }

    /** name => id, cached briefly (category taxonomy is stable). */
    private function nameToId(): array
    {
        return Cache::remember('category_name_to_id', 600, function () {
            return Category::pluck('id', 'name')
                ->mapWithKeys(fn($id, $name) => [trim($name) => (int) $id])
                ->all();
        });
    }
}
