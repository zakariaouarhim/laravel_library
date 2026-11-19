<?php

namespace App\Console\Commands;

use App\Services\BookIngestionService;
use App\Services\CatalogueDraftService;
use Illuminate\Console\Command;

/**
 * Bulk version of the catalogue-import "find this book" panel: look each CSV
 * row up across the web sources and queue it as an `api:` catalogue row, which
 * then appears in the normal review grid under the "بحث API" source filter.
 *
 * It used to write pending_books rows for a separate review screen; that queue
 * and screen are retired.
 */
class BooksIngest extends Command
{
    protected $signature = 'books:ingest
        {file : Path to a CSV file with two columns (title, author). Optional header.}
        {--language=french : Language to look the books up in}
        {--dry-run : Look each row up and report what would be queued, write nothing}';

    protected $description = 'Bulk-queue books from a CSV of (title, author) pairs for review as api: catalogue rows';

    public function handle(BookIngestionService $sources, CatalogueDraftService $drafts): int
    {
        $path = $this->argument('file');
        if (!is_file($path)) {
            $this->error("CSV not found: {$path}");
            return self::FAILURE;
        }

        $rows = $this->readCsv($path);
        if (empty($rows)) {
            $this->error('CSV is empty or unreadable.');
            return self::FAILURE;
        }

        $language = $this->option('language');
        $dryRun   = (bool) $this->option('dry-run');

        if (!isset(CatalogueDraftService::LANGUE_TOKEN[$language])) {
            $this->error("Unsupported --language={$language}. Use one of: "
                . implode(', ', array_keys(CatalogueDraftService::LANGUE_TOKEN)));

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%s %d row(s) | language=%s | dry-run=%s',
            $dryRun ? 'Would queue' : 'Queueing',
            count($rows),
            $language,
            $dryRun ? 'yes' : 'no'
        ));

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $counts = ['queued' => 0, 'not_found' => 0, 'errors' => 0];

        foreach ($rows as [$title, $author]) {
            try {
                $resolved = $sources->resolveSources($title, $author, $language);
                if ($resolved === []) {
                    $counts['not_found']++;
                    $this->newLine();
                    $this->warn("  - no source has: {$title} | {$author}");
                    $bar->advance();
                    continue;
                }

                if ($dryRun) {
                    $draft = $drafts->mergeSources($resolved, $title, $author);
                    $this->newLine();
                    $this->line(sprintf('  would queue: %s | %s (%d source(s))',
                        mb_substr($draft['title'], 0, 50), $draft['author'] ?: 'بدون مؤلف', count($resolved)));
                } else {
                    // Idempotent: the dedup_key is derived from the book's
                    // identity, so re-running a CSV refreshes rather than duplicates.
                    $drafts->adoptSources($resolved, $title, $author, $language);
                }
                $counts['queued']++;
            } catch (\Throwable $e) {
                $counts['errors']++;
                $this->newLine();
                $this->error("  x {$title} | {$author} -> {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['result', 'count'], [
            [$dryRun ? 'would queue' : 'queued', $counts['queued']],
            ['not found in any source',          $counts['not_found']],
            ['errors',                           $counts['errors']],
        ]);

        if ($dryRun) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $this->info('Review them under the "بحث API" source filter: ' . url('/admin/catalogue-import'));

        return self::SUCCESS;
    }

    /**
     * Read a 2-column CSV. Auto-detects a header row by sniffing the first cell.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        $isFirst = true;
        while (($cols = fgetcsv($handle)) !== false) {
            if (count($cols) < 2) continue;

            $title  = trim((string) $cols[0]);
            $author = trim((string) $cols[1]);

            // Skip header row if its first cell is literally "title".
            if ($isFirst) {
                $isFirst = false;
                if (strcasecmp($title, 'title') === 0) {
                    continue;
                }
            }

            if ($title === '' || $author === '') continue;
            $rows[] = [$title, $author];
        }

        fclose($handle);
        return $rows;
    }
}
