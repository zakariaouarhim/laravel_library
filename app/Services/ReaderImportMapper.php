<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Turns the messy source-site labels of scraped "reader" books into clean
 * suggestions (category id, author name, language) for the review screen.
 *
 * The source `categories` array mixes four different things:
 *  - real genres            -> map to an existing DB category (by NAME)
 *  - author names           -> fill the author field instead
 *  - merch (mugs/add-ons)   -> Accessories category
 *  - carousel/promo/series  -> ignored for categorization
 *
 * Categories are resolved by NAME at runtime (category ids differ between the
 * dev DB and the VPS), so a suggestion never points at a non-existent id.
 * All suggestions are only defaults; the admin confirms/overrides per book.
 */
class ReaderImportMapper
{
    /** Source genre label => existing category NAME. */
    public const CATEGORY_MAP = [
        'كتب انجليزية'   => 'English Books',
        'تنمية ذاتية'    => 'تطوير الذات',
        'كتب دينية'      => 'كتب دينية',
        'كتب فرنسية'     => 'French',
        'رعب و فانتازيا' => 'فانتازيا',
        'mugs'           => 'Accessories',
        'ADD ONES'       => 'Accessories',
    ];

    /** Source labels that are really author names => normalized author name. */
    public const AUTHOR_LABELS = [
        'أغاثا كريستي'        => 'أغاثا كريستي',
        'أسامة مسلم'          => 'أسامة مسلم',
        'دوستويفسكي'          => 'دوستويفسكي',
        'خولة حميدي'          => 'خولة حميدي',
        'مصطفى محمود'         => 'مصطفى محمود',
        'حسن أوريد'           => 'حسن أوريد',
        'مؤلفات إيمان نضيفي'  => 'إيمان نضيفي',
        'تحقيقات نوح الألفي'  => 'نوح الألفي',
    ];

    /** Labels that carry no categorization signal (carousels, promos, series buckets). */
    public const IGNORE_LABELS = [
        'وصل حديثا', 'وصل حديثا 2', 'الأكثر مبيعا', 'FOR-SALE', 'عشوائيات',
        'سلاسل', 'منتجاتنا', 'مملكة البلاغة', 'قواعد جارتين', 'ثلاثية ردني إليك',
    ];

    /** Fallback primary category NAME by language when nothing else maps. */
    public const LANGUAGE_FALLBACK = [
        'arabic'  => 'كتب عربية',
        'english' => 'English Books',
        'french'  => 'French',
    ];

    /** Normalize the source language string to the catalogue's lowercase value. */
    public function normalizeLanguage(?string $language): string
    {
        return match (strtolower(trim((string) $language))) {
            'english' => 'english',
            'french'  => 'french',
            default   => 'arabic',
        };
    }

    /**
     * Best-guess primary category id from the source labels (first real genre wins),
     * else a language fallback. Names are resolved to this environment's ids, so
     * the result is always an existing category (or null if even the fallback is
     * absent).
     */
    public function suggestCategoryId(array $sourceCategories, string $language): ?int
    {
        foreach ($sourceCategories as $label) {
            $label = trim((string) $label);
            if (isset(self::CATEGORY_MAP[$label]) && ($id = $this->idForName(self::CATEGORY_MAP[$label]))) {
                return $id;
            }
        }

        $fallbackName = self::LANGUAGE_FALLBACK[$language] ?? self::LANGUAGE_FALLBACK['arabic'];

        return $this->idForName($fallbackName) ?? $this->idForName(self::LANGUAGE_FALLBACK['arabic']);
    }

    /** Resolve a category name to this environment's id (cached), or null. */
    private function idForName(string $name): ?int
    {
        $map = Cache::remember('category_name_to_id', 600, fn() => Category::pluck('id', 'name')
            ->mapWithKeys(fn($id, $n) => [trim($n) => (int) $id])
            ->all());

        return $map[trim($name)] ?? null;
    }

    /**
     * Best-guess author name: an existing value, else an author-label among the
     * source categories, else a "للكاتب X" hint pulled from the description.
     */
    public function suggestAuthor(?string $author, array $sourceCategories, ?string $description): ?string
    {
        if (!empty(trim((string) $author))) {
            return trim($author);
        }

        foreach ($sourceCategories as $label) {
            $label = trim((string) $label);
            if (isset(self::AUTHOR_LABELS[$label])) {
                return self::AUTHOR_LABELS[$label];
            }
        }

        if ($description) {
            // e.g. "… للكاتب حسن الجندي" / "… للكاتبة Mélissa Da Costa"
            if (preg_match('/للكاتب(?:ة)?\s+([^\r\n]+?)(?:\r|\n|$)/u', $description, $m)) {
                $name = trim($m[1]);
                // Trim trailing connective words that sometimes follow the name.
                $name = preg_split('/\s+(?:رواية|كتاب|قصة|مجموعة)\b/u', $name)[0];
                return mb_substr(trim($name), 0, 200) ?: null;
            }
        }

        return null;
    }
}
