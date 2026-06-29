<?php

namespace App\Services;

/**
 * Turns the messy source-site labels of scraped "reader" books into clean
 * suggestions (category id, author name, language) for the review screen.
 *
 * The source `categories` array mixes four different things:
 *  - real genres            -> map to an existing DB category id
 *  - author names           -> fill the author field instead
 *  - merch (mugs/add-ons)   -> Accessories category
 *  - carousel/promo/series  -> ignored for categorization
 *
 * All suggestions are only defaults; the admin confirms/overrides per book.
 */
class ReaderImportMapper
{
    /** Source genre label => existing categories.id. */
    public const CATEGORY_MAP = [
        'كتب انجليزية'   => 82,  // كتب إنجليزية
        'تنمية ذاتية'    => 3,   // تطوير الذات
        'كتب دينية'      => 2,   // كتب دينية
        'كتب فرنسية'     => 79,  // French
        'رعب و فانتازيا' => 99,  // فانتازيا
        'mugs'           => 80,  // Accessories
        'ADD ONES'       => 80,  // Accessories
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

    /** Fallback primary category by language when nothing else maps. */
    public const LANGUAGE_FALLBACK = [
        'arabic'  => 83, // كتب عربية
        'english' => 82, // كتب إنجليزية
        'french'  => 79, // French
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
     * else merch -> Accessories, else a language fallback.
     */
    public function suggestCategoryId(array $sourceCategories, string $language): ?int
    {
        foreach ($sourceCategories as $label) {
            $label = trim((string) $label);
            if (isset(self::CATEGORY_MAP[$label])) {
                return self::CATEGORY_MAP[$label];
            }
        }

        return self::LANGUAGE_FALLBACK[$language] ?? self::LANGUAGE_FALLBACK['arabic'];
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
