<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * AI fallback for category suggestion: given a book and a SHORTLIST of candidate
 * category names (built from the keyword matcher), asks Claude Haiku to pick the
 * single best-fitting one. Opt-in per book (the admin clicks the AI button), so a
 * call is only made on demand. Shortlisting keeps each call tiny/cheap (~$0.002).
 *
 * Same client wiring/config keys as DescriptionRewriteService.
 */
class CategoryClassifierService
{
    private const MAX_OUTPUT_TOKENS = 40;

    /**
     * @param string[] $candidateNames  Local category names the model must choose from.
     * @return array{ok: bool, name: ?string, error: ?string}
     *         name is one of $candidateNames, or null when the model returns NONE.
     */
    public function classify(string $title, ?string $description, ?string $language, array $candidateNames): array
    {
        $candidateNames = array_values(array_filter(array_map('trim', $candidateNames)));
        if (empty($candidateNames)) {
            return ['ok' => false, 'name' => null, 'error' => 'No candidate categories to choose from'];
        }

        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            return ['ok' => false, 'name' => null, 'error' => 'ANTHROPIC_API_KEY is not set'];
        }

        $numbered = [];
        foreach ($candidateNames as $i => $name) {
            $numbered[] = ($i + 1) . '. ' . $name;
        }
        $list = implode("\n", $numbered);

        $desc = trim((string) $description);
        if (mb_strlen($desc) > 800) {
            $desc = mb_substr($desc, 0, 800) . '…';
        }

        $userMessage = "Book title: " . (trim($title) ?: '—') . "\n"
                     . 'Language: ' . ($language ?: 'unknown') . "\n"
                     . "Description: " . ($desc ?: '—') . "\n\n"
                     . "Candidate categories:\n{$list}";

        $system = "You are a librarian classifying a book into ONE category for a Moroccan "
                . "bookstore (Arabic/French/English catalogue). Choose the single best-fitting "
                . "category for the book from the numbered candidate list. "
                . "Reply with ONLY the exact category name, copied verbatim from the list — no "
                . "number, no punctuation, no extra words. If none fit, reply exactly: NONE.";

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
                ->timeout(30)
                ->retry(1, 1500)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'       => config('services.anthropic.model'),
                    'max_tokens'  => self::MAX_OUTPUT_TOKENS,
                    'temperature' => 0,
                    'system'      => $system,
                    'messages'    => [
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'name' => null, 'error' => 'HTTP exception: ' . $e->getMessage()];
        }

        if (!$response->successful()) {
            return [
                'ok'    => false,
                'name'  => null,
                'error' => "HTTP {$response->status()}: " . mb_substr($response->body(), 0, 300),
            ];
        }

        $text = trim($response->json('content.0.text') ?? '');
        if ($text === '' || strtoupper($text) === 'NONE') {
            return ['ok' => true, 'name' => null, 'error' => null];
        }

        // Match the reply back to a candidate (exact, then case-insensitive), so we
        // never trust a hallucinated name that isn't in the local list.
        $match = $this->matchCandidate($text, $candidateNames);

        return ['ok' => true, 'name' => $match, 'error' => null];
    }

    /** @param string[] $candidates */
    private function matchCandidate(string $text, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if ($c === $text) {
                return $c;
            }
        }
        $lower = mb_strtolower($text);
        foreach ($candidates as $c) {
            if (mb_strtolower($c) === $lower) {
                return $c;
            }
        }

        return null;
    }
}
