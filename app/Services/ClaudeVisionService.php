<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;

class ClaudeVisionService
{
    protected string $apiKey;
    protected string $model;
    protected string $endpoint = 'https://api.anthropic.com/v1/messages';

    public function __construct(?string $modelOverride = null)
    {
        $this->apiKey = config('services.anthropic.api_key', '');
        $this->model = $modelOverride ?? config('services.anthropic.model', 'claude-haiku-4-5-20251001');
    }

    /**
     * Extract book metadata from a cover image file.
     *
     * @param string $filePath     Absolute path to the image file
     * @param string $languageHint 'arabic'|'english'|'mixed' — from folder context
     * @return array{title: ?string, author: ?string, language: ?string, confidence: float}
     */
    public function extractBookInfo(string $filePath, string $languageHint = 'unknown'): array
    {
        $default = ['title' => null, 'author' => null, 'language' => null, 'confidence' => 0.0];

        try {
            $imageData = $this->encodeImage($filePath);

            $prompt = <<<PROMPT
You are analyzing a book cover image. Extract the following information:
1. Book title (in original language exactly as printed on the cover)
2. Author name (in original language exactly as printed on the cover)
3. Detected language of the text on the cover (arabic/english/french/other)

The book is expected to be in {$languageHint} based on its source folder.

Respond in JSON format ONLY, no markdown fences, no explanation:
{"title": "...", "author": "...", "language": "...", "confidence": 0.0}

- confidence: 0.0 to 1.0, how confident you are in the extraction
- If you cannot determine a field, use null for that field
- For Arabic text, preserve the original Arabic characters
- If there are multiple authors, use only the primary/first author
PROMPT;

            $messages = [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $imageData['media_type'],
                                'data' => $imageData['base64'],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                    ],
                ],
            ];

            $response = $this->callApi($messages, 300);

            if (!$response) {
                return $default;
            }

            $text = $response['content'][0]['text'] ?? '';
            $parsed = $this->parseJsonResponse($text);

            return array_merge($default, array_filter($parsed, fn($v) => $v !== null));

        } catch (\Exception $e) {
            Log::error('ClaudeVision extractBookInfo error: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Generate a description for a book when APIs fail (Tier 3 fallback).
     */
    public function generateDescription(string $title, ?string $author, string $language): ?string
    {
        try {
            $authorPart = $author ? " by {$author}" : '';
            $langInstruction = $language === 'arabic'
                ? 'Write the description in Arabic.'
                : 'Write the description in English.';

            $prompt = <<<PROMPT
Write a brief 2-3 sentence book description for the book titled "{$title}"{$authorPart}.
{$langInstruction}
The description should be suitable for an online bookstore listing.
Respond with ONLY the description text, no labels or formatting.
PROMPT;

            $messages = [
                ['role' => 'user', 'content' => $prompt],
            ];

            $response = $this->callApi($messages, 300);

            if (!$response) {
                return null;
            }

            return trim($response['content'][0]['text'] ?? '');

        } catch (\Exception $e) {
            Log::error('ClaudeVision generateDescription error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Encode a local image file to base64 with MIME type detection.
     * Resizes images >5MB before encoding to stay within API limits.
     *
     * @return array{base64: string, media_type: string}
     */
    protected function encodeImage(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Image file not found: {$filePath}");
        }

        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mediaType = $mimeMap[$ext] ?? (mime_content_type($filePath) ?: 'image/jpeg');

        // Resize large images (>5MB) to stay within API limits
        if (filesize($filePath) > 5 * 1024 * 1024) {
            $image = Image::read($filePath);
            $image->scale(width: 1024);
            $encoded = (string) $image->toJpeg(85);
            return [
                'base64' => base64_encode($encoded),
                'media_type' => 'image/jpeg',
            ];
        }

        return [
            'base64' => base64_encode(file_get_contents($filePath)),
            'media_type' => $mediaType,
        ];
    }

    /**
     * Make an API call to Claude with retry logic.
     */
    protected function callApi(array $messages, int $maxTokens = 500, int $maxRetries = 3): ?array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withOptions([
                    'verify' => false,
                    'timeout' => 60,
                    'connect_timeout' => 15,
                ])->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])->post($this->endpoint, [
                    'model' => $this->model,
                    'max_tokens' => $maxTokens,
                    'messages' => $messages,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                $status = $response->status();
                $body = $response->body();

                // Rate limited — wait and retry
                if ($status === 429) {
                    $retryAfter = (int) ($response->header('retry-after') ?: pow(2, $attempt));
                    Log::warning("Claude API rate limited (attempt {$attempt}), waiting {$retryAfter}s");
                    sleep($retryAfter);
                    continue;
                }

                // Overloaded — wait and retry
                if ($status === 529) {
                    Log::warning("Claude API overloaded (attempt {$attempt}), waiting...");
                    sleep(pow(2, $attempt));
                    continue;
                }

                $lastException = new \RuntimeException("Claude API error {$status}: {$body}");
                Log::error("Claude API error (attempt {$attempt}/{$maxRetries}): {$status} — {$body}");

            } catch (\Exception $e) {
                $lastException = $e;
                Log::error("Claude API exception (attempt {$attempt}/{$maxRetries}): " . $e->getMessage());
            }

            if ($attempt < $maxRetries) {
                sleep(pow(2, $attempt - 1));
            }
        }

        Log::error('Claude API failed after all retries: ' . ($lastException?->getMessage() ?? 'Unknown'));
        return null;
    }

    /**
     * Parse JSON from Claude's text response, handling markdown fences.
     */
    protected function parseJsonResponse(string $text): array
    {
        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```\s*$/m', '', $text);
        $text = trim($text);

        // Try direct JSON parse
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try extracting JSON object from the text
        if (preg_match('/\{[^}]+\}/s', $text, $match)) {
            $decoded = json_decode($match[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('Failed to parse Claude JSON response: ' . substr($text, 0, 200));
        return [];
    }
}
