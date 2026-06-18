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
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an expert e-commerce SEO copywriter for an Arabic digital bookstore (مكتبة الفقراء).

Your task: rewrite the provided book description to make it 100% unique, avoiding duplicate-content SEO penalties.

CRITICAL RULES:
1. The input is Arabic. Your output MUST be in clear formal Arabic (فصحى) suitable for retail.
   Do NOT translate to English. Do NOT mix languages.
2. NEVER invent or change plot points, character names, or factual details.
   If the original is sparse, write a SHORTER rewrite rather than fabricate.
3. Completely restructure sentences and word choices — do not just swap synonyms.
4. Adopt an engaging, professional retail tone.
5. End with a natural soft call-to-action in Arabic (e.g.,
   «أضف هذا الكتاب إلى مكتبتك اليوم», «لا تفوّت فرصة قراءة هذا العمل المميز»).
6. Length: 80-200 Arabic words. Aim for similar length to the input.
7. Return ONLY the rewritten Arabic text. No filler like "إليك النص:" or English commentary.
PROMPT;

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

        $query->when($limit, fn ($q) => $q->limit($limit))
            ->chunkById(50, function ($books) use ($dryRun, $bar, &$stats, $apiKey) {
                foreach ($books as $book) {
                    $result = $this->rewriteOne($book, $apiKey);

                    if ($dryRun) {
                        $this->newLine();
                        $this->line("--- Book #{$book->id}: {$book->title} ---");
                        $this->line("ORIGINAL: " . mb_substr($book->description, 0, 200) . '...');
                        $this->line("REWRITE:  " . ($result['text'] ?? '[FAILED: ' . $result['error'] . ']'));
                        $this->newLine();
                        $bar->advance();
                        continue;
                    }

                    if ($result['ok']) {
                        DB::table('books')->where('id', $book->id)->update([
                            'original_description' => $book->original_description ?: $book->description,
                            'description'          => $result['text'],
                            'rewrite_status'       => 'rewritten',
                            'rewrite_error'        => null,
                            'rewritten_at'         => now(),
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
        $authorName = $book->primaryAuthor?->name ?? $book->author_name ?? 'غير محدد';

        $userMessage = "العنوان: {$book->title}\n"
                     . "المؤلف: {$authorName}\n\n"
                     . "الوصف الأصلي:\n{$book->description}";

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
                    'system'     => self::SYSTEM_PROMPT,
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
