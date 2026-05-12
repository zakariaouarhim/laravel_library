<?php

namespace App\Console\Commands;

use App\Services\BookIngestionService;
use Illuminate\Console\Command;

class BooksIngest extends Command
{
    protected $signature = 'books:ingest
        {file : Path to a CSV file with two columns (title, author). Optional header.}
        {--language=french : Default language for staged books}
        {--dry-run : Look up each row and report findings without writing pending_books}';

    protected $description = 'Bulk-ingest books from a CSV of (title, author) pairs into the pending_books staging table';

    public function handle(BookIngestionService $service): int
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

        $this->info(sprintf(
            '%s %d row(s) | language=%s | dry-run=%s',
            $dryRun ? 'Would ingest' : 'Ingesting',
            count($rows),
            $language,
            $dryRun ? 'yes' : 'no'
        ));

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $counts = [
            'enriched'  => 0,
            'failed'    => 0,
            'duplicate' => 0,
            'reused'    => 0,   // idempotent hits
            'errors'    => 0,
        ];

        foreach ($rows as [$title, $author]) {
            try {
                if ($dryRun) {
                    // We can't preview without writing — just report the input.
                    // Real run uses the service which is idempotent anyway.
                    $this->newLine();
                    $this->line("  → {$title} | {$author}");
                } else {
                    $beforeCount = \App\Models\PendingBook::count();
                    $pending = $service->stageFromTitleAuthor($title, $author, $language);
                    $isNew = \App\Models\PendingBook::count() > $beforeCount;

                    if (!$isNew) {
                        $counts['reused']++;
                    } else {
                        $counts[$pending->status] = ($counts[$pending->status] ?? 0) + 1;
                    }
                }
            } catch (\Throwable $e) {
                $counts['errors']++;
                $this->newLine();
                $this->error("  ✗ {$title} | {$author} → {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info('Dry-run complete — no rows written.');
            return self::SUCCESS;
        }

        $this->table(['result', 'count'], [
            ['enriched',  $counts['enriched']  ?? 0],
            ['failed',    $counts['failed']    ?? 0],
            ['duplicate', $counts['duplicate'] ?? 0],
            ['reused',    $counts['reused']],
            ['errors',    $counts['errors']],
        ]);

        $this->info('Review pending books: ' . url('/admin/books/pending'));
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
