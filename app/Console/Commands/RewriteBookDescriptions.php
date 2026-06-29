<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\DescriptionRewriteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

    public function handle(DescriptionRewriteService $rewriter): int
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
        $query->chunkById(50, function ($books) use ($dryRun, $bar, &$stats, $rewriter, $limit, &$processed) {
            foreach ($books as $book) {
                if ($limit !== null && $processed >= $limit) {
                    return false; // halts chunkById
                }

                $result = $this->rewriteOne($book, $rewriter);

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
    private function rewriteOne(Book $book, DescriptionRewriteService $rewriter): array
    {
        $authorName = $book->primaryAuthor?->name ?? $book->author_name ?? null;

        return $rewriter->rewrite($book->title, $authorName, $book->description, $book->language);
    }
}
