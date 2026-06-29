<?php

namespace App\Console\Commands;

use App\Models\ReaderStagingBook;
use App\Services\ReaderImportMapper;
use Illuminate\Console\Command;

class StageReaderBooks extends Command
{
    protected $signature = 'reader:stage
        {--file= : Path to the scraped JSON (default: reader_DB/exports/*.json newest)}
        {--reset : Truncate the staging table first}';

    protected $description = 'Load scraped reader books into the review staging table with category/author/language suggestions';

    public function handle(ReaderImportMapper $mapper): int
    {
        $file = $this->option('file') ?: $this->latestExport();

        if (!$file || !is_file($file)) {
            $this->error("JSON file not found: " . ($file ?: '(none in reader_DB/exports)'));
            return 1;
        }

        $rows = json_decode(file_get_contents($file), true);
        if (!is_array($rows)) {
            $this->error('Could not parse JSON.');
            return 1;
        }

        if ($this->option('reset')) {
            ReaderStagingBook::truncate();
            $this->warn('Staging table truncated.');
        }

        $readerBase = base_path('reader_DB');
        $created = 0; $updated = 0; $skippedRejected = 0; $missingImages = 0;

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            if (($row['status'] ?? null) === 'rejected') {
                $skippedRejected++;
                continue;
            }

            $externalId = $row['external_id'] ?? null;
            $name       = trim((string) ($row['name'] ?? ''));
            if (!$externalId || $name === '') {
                continue;
            }

            $sourceCats = is_array($row['categories'] ?? null) ? $row['categories'] : [];
            $language   = $mapper->normalizeLanguage($row['language'] ?? null);

            $localImage = $row['local_image'] ?? null;
            $imageExists = $localImage && is_file($readerBase . DIRECTORY_SEPARATOR . $localImage);
            if (!$imageExists) {
                $missingImages++;
            }

            // Don't clobber an already-reviewed row on re-run.
            $existing = ReaderStagingBook::where('external_id', $externalId)->first();
            if ($existing && $existing->status !== 'pending') {
                continue;
            }

            $suggestedCategoryId = $mapper->suggestCategoryId($sourceCats, $language);

            $attrs = [
                'name'                  => $name,
                'author'                => $mapper->suggestAuthor($row['author'] ?? null, $sourceCats, $row['description'] ?? null),
                'language'              => $language,
                'price'                 => 40,
                'description'           => $row['description'] ?? null,
                'local_image'           => $localImage,
                'image_exists'          => $imageExists,
                'source_categories'     => $sourceCats,
                'suggested_category_id' => $suggestedCategoryId,
                'category_ids'          => $suggestedCategoryId ? [$suggestedCategoryId] : [],
                'primary_category_id'   => $suggestedCategoryId,
                'stock'                 => (int) ($row['stock'] ?? 0),
                'status'                => 'pending',
            ];

            if ($existing) {
                $existing->update($attrs);
                $updated++;
            } else {
                ReaderStagingBook::create(['external_id' => $externalId] + $attrs);
                $created++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Metric', 'Count'], [
            ['Created', $created],
            ['Updated (pending)', $updated],
            ['Skipped (rejected)', $skippedRejected],
            ['Missing images flagged', $missingImages],
            ['Total pending in table', ReaderStagingBook::where('status', 'pending')->count()],
        ]);

        return 0;
    }

    private function latestExport(): ?string
    {
        $files = glob(base_path('reader_DB/exports/*.json'));
        if (!$files) {
            return null;
        }
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        return $files[0];
    }
}
