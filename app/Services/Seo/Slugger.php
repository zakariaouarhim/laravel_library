<?php

namespace App\Services\Seo;

class Slugger
{
    /**
     * Maximum slug length. URLs work up to ~2048 chars, but anything past ~80
     * gets ugly in SERPs. 100 is a reasonable cap that doesn't cut common book
     * titles short.
     */
    public const MAX_LENGTH = 100;

    /**
     * Generate a URL-safe slug that preserves Arabic characters. Latin chars
     * lowercased; tashkeel removed; alif/ya forms normalized; whitespace and
     * non-alphanumeric collapsed to single dashes.
     *
     * Examples:
     *   "الأسود يَليق بك"       → "الاسود-يليق-بك"
     *   "L'Étranger"            → "l-étranger"     (note: keeps é, that's fine for slugs)
     *   "Mille et une nuits!!"  → "mille-et-une-nuits"
     */
    public function make(string $input): string
    {
        $value = mb_strtolower(trim($input), 'UTF-8');

        // Remove Arabic tashkeel (diacritics): fathatan, dammatan, kasratan,
        // fatha, damma, kasra, shadda, sukun, hamza-above, hamza-below, madda.
        $value = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}]/u', '', $value);

        // Unify alif variants and ya forms — keeps slug stable across spelling
        // differences ("احمد" vs "أحمد").
        $value = strtr($value, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ى' => 'ي',
            'ة' => 'ه',
        ]);

        // Replace any run of non-(letter|digit) chars with a single dash.
        // \p{L} = letters from any script (Arabic, Latin, etc.); \p{N} = digits.
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value);

        // Collapse repeating dashes + trim leading/trailing.
        $value = preg_replace('/-+/', '-', $value);
        $value = trim($value, '-');

        if ($value === '') {
            return 'item-' . substr(md5(uniqid('', true)), 0, 8);
        }

        // Length cap — cut at the last dash before MAX_LENGTH to avoid
        // truncating mid-word.
        if (mb_strlen($value) > self::MAX_LENGTH) {
            $cut = mb_substr($value, 0, self::MAX_LENGTH);
            $lastDash = mb_strrpos($cut, '-');
            $value = $lastDash !== false && $lastDash > 0 ? mb_substr($cut, 0, $lastDash) : $cut;
        }

        return $value;
    }
}
