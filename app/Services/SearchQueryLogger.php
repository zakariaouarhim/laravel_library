<?php

namespace App\Services;

use App\Models\SearchQuery;
use Illuminate\Http\Request;

/**
 * Records user search queries for SEO insights.
 *
 * Skipped: empty queries, common bot user-agents (no signal, just noise).
 * Failures are swallowed — search latency must not depend on logging.
 */
class SearchQueryLogger
{
    /**
     * Pattern matches search-engine, monitoring, and preview-tool user agents.
     * Conservative on purpose — better to log a few bots than to drop real users.
     */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot|preview|monitor|uptime|pingdom|gtmetrix|lighthouse/i';

    public function log(
        ?string $query,
        int $resultCount,
        string $source,
        ?Request $request = null,
    ): void {
        $query = trim((string) $query);
        if ($query === '') return;
        if (mb_strlen($query) > 200) return; // request validation should catch this, defensive

        $request ??= request();
        if ($request && $this->looksLikeBot((string) $request->userAgent())) return;

        try {
            SearchQuery::create([
                'query'            => $query,
                'normalized_query' => $this->normalize($query),
                'result_count'     => max(0, $resultCount),
                'source'           => $source === 'autocomplete' ? 'autocomplete' : 'page',
                'user_id'          => $request?->user()?->id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('SearchQueryLogger::log failed: ' . $e->getMessage());
        }
    }

    /**
     * Tashkeel-stripped, lowercased, whitespace-collapsed form of the query.
     * Groups "نَجِيب مَحفوظ" with "نجيب محفوظ" for the top-queries report.
     */
    public function normalize(string $query): string
    {
        // Strip Arabic diacritics (tashkeel: U+064B..U+0652, U+0670, U+0640 tatweel).
        $cleaned = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $query);
        // Unify common alif and ya variants.
        $cleaned = strtr($cleaned, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه']);
        // Collapse whitespace, lowercase ASCII letters.
        $cleaned = preg_replace('/\s+/u', ' ', mb_strtolower(trim($cleaned)));
        return mb_substr($cleaned, 0, 200);
    }

    private function looksLikeBot(string $userAgent): bool
    {
        if ($userAgent === '') return false; // empty UA could be a curl test — let it through
        return (bool) preg_match(self::BOT_PATTERN, $userAgent);
    }
}
