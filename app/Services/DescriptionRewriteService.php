<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Rewrites a book description via Claude Haiku to remove duplicate-content SEO
 * penalties, in the book's own language. Shared by the nightly
 * `books:rewrite-descriptions` command and the reader-import review screen.
 */
class DescriptionRewriteService
{
    private const MAX_OUTPUT_TOKENS = 600;

    private const LANGUAGE_LABELS = [
        'arabic'  => 'Arabic (clear formal فصحى)',
        'english' => 'English',
        'french'  => 'French (français)',
        'spanish' => 'Spanish (español)',
        'german'  => 'German (Deutsch)',
    ];

    /**
     * @return array{ok: bool, text: ?string, error: ?string}
     */
    public function rewrite(string $title, ?string $author, string $description, ?string $language): array
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            return ['ok' => false, 'text' => null, 'error' => 'ANTHROPIC_API_KEY is not set'];
        }

        $userMessage = "Title: {$title}\n"
                     . 'Author: ' . ($author ?: '—') . "\n\n"
                     . "Original description:\n{$description}";

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
                ->timeout(60)
                ->retry(2, 2000)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => config('services.anthropic.model'),
                    'max_tokens' => self::MAX_OUTPUT_TOKENS,
                    'system'     => $this->systemPromptFor($language),
                    'messages'   => [
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'text' => null, 'error' => 'HTTP exception: ' . $e->getMessage()];
        }

        if (!$response->successful()) {
            return [
                'ok'    => false,
                'text'  => null,
                'error' => "HTTP {$response->status()}: " . mb_substr($response->body(), 0, 300),
            ];
        }

        $text = trim($response->json('content.0.text') ?? '');
        if ($text === '') {
            return ['ok' => false, 'text' => null, 'error' => 'Empty response body'];
        }

        return ['ok' => true, 'text' => $text, 'error' => null];
    }

    public function systemPromptFor(?string $language): string
    {
        $label = self::LANGUAGE_LABELS[strtolower((string) $language)]
            ?? 'the same language as the input description (detect automatically)';

        return <<<PROMPT
You are an expert e-commerce SEO copywriter for a multilingual digital bookstore (مكتبة الفقراء) that carries Arabic, English, French, Spanish, and German titles.

Your task: rewrite the provided book description to make it 100% unique, avoiding duplicate-content SEO penalties.

CRITICAL RULES:
1. Target language for your output: {$label}.
   Output MUST be entirely in this language. Do NOT translate to another language. Do NOT mix languages.
2. NEVER invent or change plot points, character names, or factual details.
   If the original is sparse, write a SHORTER rewrite rather than fabricate.
3. Completely restructure sentences and word choices — do not just swap synonyms.
4. Adopt an engaging, professional retail tone appropriate to the target language.
5. End with a natural soft call-to-action in the target language (e.g.,
   "Add this gripping read to your collection today" / «أضف هذا الكتاب إلى مكتبتك اليوم» /
   "Ajoutez ce livre captivant à votre collection").
6. Length: roughly 80-200 words. Aim for similar length to the input.
7. Return ONLY the rewritten text in the target language. No filler like "Here is..." or "إليك النص:" or commentary in any other language.
PROMPT;
    }
}
