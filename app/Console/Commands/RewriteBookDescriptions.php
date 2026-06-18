<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RewriteBookDescriptions extends Command
{
    protected $signature = 'books:rewrite-descriptions
        {--dry-run : Call the API and print outputs, never write to DB}
        {--limit= : Maximum number of books to process this run}
        {--retry-failed : Also pick up books with rewrite_status = failed}';

    protected $description = 'Rewrite book descriptions via Claude Haiku to remove duplicate-content SEO penalties (Arabic output).';

    private const MIN_LENGTH = 80;
    private const MAX_LENGTH = 3000;
    private const MAX_OUTPUT_TOKENS = 600;

    private const LANGUAGE_LABELS = [
        'arabic'  => 'Arabic (clear formal فصحى)',
        'english' => 'English',
        'french'  => 'French (français)',
        'spanish' => 'Spanish (español)',
        'german'  => 'German (Deutsch)',
    ];

    private function systemPromptFor(?string $language): string
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

    public function handle(): int
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            $this->error('ANTHROPIC_API_KEY is not set in .env');
            return self::FAILURE;
        }

        $dryRun     = (bool) $this->option('dry-run');
        $limit      = $this->option('limit') ? (int) $this->option('limit') : null;
        $retryFails = (bool) $this->option('retry-failed');

        $statuses = $retryFails ? ['pending', 'failed'] : ['pending'];

        $query = Book::query()
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->whereRaw('CHAR_LENGTH(description) BETWEEN ? AND ?', [self::MIN_LENGTH, self::MAX_LENGTH])
            ->where(function ($q) {
                $q->whereNull('meta_description')->orWhere('meta_description', '');
            })
            ->whereIn('rewrite_status', $statuses)
            ->orderByDesc('id');

        $total = (clone $query)->count();
        if ($limit) {
            $total = min($total, $limit);
        }

        if ($total === 0) {
            $this->info('Nothing to rewrite — all eligible books are already processed.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Processing {$total} books with Claude Haiku.");
        $this->line('Model: ' . config('services.anthropic.model'));
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $stats = ['rewritten' => 0, 'failed' => 0, 'skipped' => 0];

        $processed = 0;
        $query->chunkById(50, function ($books) use ($dryRun, $bar, &$stats, $apiKey, $limit, &$processed) {
            foreach ($books as $book) {
                if ($limit !== null && $processed >= $limit) {
                    return false; // halts chunkById
                }

                $result = $this->rewriteOne($book, $apiKey);

                if ($dryRun) {
                    $this->newLine();
                    $this->line("--- Book #{$book->id} [{$book->language}]: {$book->title} ---");
                    $this->line("ORIGINAL: " . mb_substr($book->description, 0, 200) . '...');
                    $this->line("REWRITE:  " . ($result['text'] ?? '[FAILED: ' . $result['error'] . ']'));
                    $this->newLine();
                    $bar->advance();
                    $processed++;
                    continue;
                }

                if ($result['ok']) {
                    DB::table('books')->where('id', $book->id)->update([
                        'original_description' => $book->original_description ?: $book->description,
                        'description'          => $result['text'],
                        'rewrite_status'       => 'rewritten',
                        'rewrite_error'        => null,
                        'rewritten_at'         => now(),
                        'updated_at'           => now(), // bumps sitemap <lastmod> so Google recrawls
                    ]);
                    $stats['rewritten']++;
                } else {
                    DB::table('books')->where('id', $book->id)->update([
                        'rewrite_status' => 'failed',
                        'rewrite_error'  => mb_substr($result['error'], 0, 500),
                    ]);
                    $stats['failed']++;
                    Log::warning("Book rewrite failed", ['book_id' => $book->id, 'error' => $result['error']]);
                }

                $bar->advance();
                $processed++;
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Rewritten: {$stats['rewritten']} | Failed: {$stats['failed']}");

        return self::SUCCESS;
    }

    /**
     * Returns ['ok' => bool, 'text' => ?string, 'error' => ?string].
     */
    private function rewriteOne(Book $book, string $apiKey): array
    {
        $authorName = $book->primaryAuthor?->name ?? $book->author_name ?? '—';

        $userMessage = "Title: {$book->title}\n"
                     . "Author: {$authorName}\n\n"
                     . "Original description:\n{$book->description}";

        try {
            $response = Http::withHeaders([
                'x-api-key'          => $apiKey,
                'anthropic-version'  => '2023-06-01',
                'content-type'       => 'application/json',
            ])
                ->timeout(60)
                ->retry(2, 2000)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => config('services.anthropic.model'),
                    'max_tokens' => self::MAX_OUTPUT_TOKENS,
                    'system'     => $this->systemPromptFor($book->language),
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
}
