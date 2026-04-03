<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Services\BookCoverImportService;
use App\Services\ClaudeVisionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportBooksFromCovers extends Command
{
    protected $signature = 'books:import-from-covers
        {--path= : Base path to book cover folders}
        {--folder= : Process only a specific folder (arabic|english|religion)}
        {--limit=0 : Max images to process (0 = all)}
        {--delay=1000 : Delay between Claude API calls in milliseconds}
        {--dry-run : Preview what would happen without creating records}
        {--resume : Resume from last progress checkpoint}
        {--reset : Clear progress file and start fresh}
        {--report= : Path for CSV report output}
        {--stage : Extract and enrich only, save to staging JSON for n8n review}
        {--model= : Override Claude model (e.g. claude-sonnet-4-5-20241022)}';

    protected $description = 'Import books from cover images using Claude Vision AI and Google Books API';

    /**
     * Folder configuration: maps folder keys to their settings.
     */
    protected array $folders = [
        'arabic' => [
            'dir' => 'arabic books',
            'category_name' => 'كتب عربية',
            'language' => 'arabic',
            'language_hint' => 'arabic',
            'price' => 80.00,
        ],
        'english' => [
            'dir' => 'english books images',
            'category_name' => 'كتب إنجليزية',
            'language' => 'english',
            'language_hint' => 'english',
            'price' => 120.00,
        ],
        'religion' => [
            'dir' => 'relegion books',
            'category_name' => 'كتب دينية',
            'language' => 'arabic',
            'language_hint' => 'arabic',
            'price' => 70.00,
        ],
    ];

    public function handle(BookCoverImportService $importService): int
    {
        $basePath = $this->option('path') ?: env('BOOK_COVERS_PATH');
        $folderFilter = $this->option('folder');
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $dryRun = (bool) $this->option('dry-run');
        $resume = (bool) $this->option('resume');
        $reportPath = $this->option('report');
        $stageMode = (bool) $this->option('stage');
        $modelOverride = $this->option('model');

        // Apply model override if specified
        if ($modelOverride) {
            app()->singleton(ClaudeVisionService::class, fn() => new ClaudeVisionService($modelOverride));
            $importService = app(BookCoverImportService::class);
            $this->info("Using model: {$modelOverride}");
        }

        // Handle --reset
        if ($this->option('reset')) {
            $importService->clearProgress();
            $this->info('Progress file cleared.');
        }

        // Validate base path
        if (!$basePath || !is_dir($basePath)) {
            $this->error("Base path not found: {$basePath}");
            $this->error('Set BOOK_COVERS_PATH in .env or use --path option.');
            return 1;
        }

        // Validate API key
        if (!config('services.anthropic.api_key')) {
            $this->error('ANTHROPIC_API_KEY not configured in .env');
            return 1;
        }

        if ($dryRun) {
            $this->warn('=== DRY RUN MODE — no records will be created ===');
        }

        if ($stageMode) {
            $this->warn('=== STAGE MODE — extracting data for n8n review, no DB writes ===');
        }

        // Determine which folders to process
        $foldersToProcess = $folderFilter
            ? [$folderFilter => $this->folders[$folderFilter] ?? null]
            : $this->folders;

        if ($folderFilter && !isset($this->folders[$folderFilter])) {
            $this->error("Unknown folder key: {$folderFilter}. Available: arabic, english, religion");
            return 1;
        }

        // Collect all image files
        $allFiles = [];
        foreach ($foldersToProcess as $key => $config) {
            if (!$config) continue;

            $folderPath = $basePath . DIRECTORY_SEPARATOR . $config['dir'];
            if (!is_dir($folderPath)) {
                $this->warn("Folder not found, skipping: {$folderPath}");
                continue;
            }

            $files = $this->scanImages($folderPath);
            foreach ($files as $file) {
                $allFiles[] = [
                    'path' => $file,
                    'folder_key' => $key,
                    'config' => $config,
                    'relative' => $config['dir'] . '/' . basename($file),
                ];
            }

            $this->info("Found " . count($files) . " images in '{$config['dir']}'");
        }

        if (empty($allFiles)) {
            $this->error('No image files found.');
            return 1;
        }

        // Apply limit
        $totalFound = count($allFiles);
        if ($limit > 0) {
            $allFiles = array_slice($allFiles, 0, $limit);
        }

        $this->info("Processing " . count($allFiles) . " of {$totalFound} total images");
        $this->newLine();

        // Load progress for resume
        $progress = $resume ? $importService->loadProgress() : ['files' => [], 'started_at' => now()->toIso8601String()];

        // Create/find categories
        $categories = [];
        foreach ($foldersToProcess as $key => $config) {
            if (!$config) continue;
            if ($dryRun) {
                $categories[$key] = Category::firstOrNew(['name' => $config['category_name']]);
                if (!$categories[$key]->exists) {
                    $this->info("Would create category: {$config['category_name']}");
                }
            } else {
                $categories[$key] = Category::firstOrCreate(['name' => $config['category_name']]);
            }
        }

        // Process images
        $bar = $this->output->createProgressBar(count($allFiles));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        $stats = ['success' => 0, 'skipped_duplicate' => 0, 'failed' => 0, 'dry_run' => 0, 'staged' => 0];
        $reportRows = [];

        foreach ($allFiles as $fileInfo) {
            $relativePath = $fileInfo['relative'];

            // Skip if already processed (resume mode)
            if ($resume && isset($progress['files'][$relativePath])) {
                $prevStatus = $progress['files'][$relativePath]['status'];
                if (in_array($prevStatus, ['success', 'skipped_duplicate'])) {
                    $bar->setMessage("Skipped (already processed): " . basename($fileInfo['path']));
                    $bar->advance();
                    $stats[$prevStatus] = ($stats[$prevStatus] ?? 0) + 1;
                    continue;
                }
            }

            $bar->setMessage(mb_substr(basename($fileInfo['path']), 0, 40));

            $category = $categories[$fileInfo['folder_key']] ?? null;
            if (!$category || (!$dryRun && !$category->exists)) {
                $bar->advance();
                continue;
            }

            // Stage mode: extract + enrich only, save to staging JSON
            if ($stageMode) {
                $result = $importService->stageImage(
                    $fileInfo['path'],
                    $fileInfo['folder_key'],
                    $fileInfo['config']
                );
            } else {
                // Full import mode
                $result = $importService->processImage(
                    $fileInfo['path'],
                    $fileInfo['folder_key'],
                    $category,
                    $fileInfo['config'],
                    $dryRun
                );
            }

            $status = $result['status'];
            $stats[$status] = ($stats[$status] ?? 0) + 1;

            // Save to progress
            $progress['files'][$relativePath] = [
                'status' => $status,
                'book_id' => $result['book_id'] ?? null,
                'title' => $result['title'],
                'processed_at' => now()->toIso8601String(),
            ];

            $importService->saveProgress($progress);

            // Collect for report / staging
            $row = [
                'file' => $relativePath,
                'folder' => $fileInfo['folder_key'],
                'status' => $status,
                'title' => $result['title'] ?? '',
                'author' => $result['author'] ?? '',
                'source' => $result['source'] ?? '',
                'book_id' => $result['book_id'] ?? '',
                'errors' => implode('; ', $result['errors'] ?? []),
            ];

            // Add staging fields when in stage mode
            if ($stageMode) {
                $row['file_path'] = $result['file_path'] ?? $fileInfo['path'];
                $row['description'] = $result['description'] ?? '';
                $row['publisher'] = $result['publisher'] ?? '';
                $row['isbn'] = $result['isbn'] ?? '';
                $row['page_num'] = $result['page_num'] ?? 0;
                $row['language'] = $result['language'] ?? $fileInfo['config']['language'];
                $row['price'] = $result['price'] ?? $fileInfo['config']['price'];
                $row['category_name'] = $result['category_name'] ?? $fileInfo['config']['category_name'];
                $row['confidence'] = $result['confidence'] ?? 0;
            }

            $reportRows[] = $row;

            $bar->advance();

            // Delay between API calls
            if ($delay > 0) {
                usleep($delay * 1000);
            }
        }

        $bar->setMessage('Done!');
        $bar->finish();
        $this->newLine(2);

        // Summary table
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total processed', array_sum($stats)],
                ['Successful imports', $stats['success'] ?? 0],
                ['Skipped (duplicate)', $stats['skipped_duplicate'] ?? 0],
                ['Failed', $stats['failed'] ?? 0],
                ['Dry run previews', $stats['dry_run'] ?? 0],
                ['Staged for review', $stats['staged'] ?? 0],
            ]
        );

        // Write staging JSON for n8n
        if ($stageMode) {
            $stagingPath = storage_path('app/staged-books.json');
            $stagingData = array_filter($reportRows, fn($r) => $r['status'] === 'staged');
            file_put_contents($stagingPath, json_encode(
                array_values($stagingData),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ));
            $this->info("Staging JSON saved to: {$stagingPath}");
            $this->info("Staged " . count($stagingData) . " books for n8n review.");
        }

        // Write CSV report
        if ($reportPath) {
            $this->writeReport($reportPath, $reportRows);
            $this->info("Report saved to: {$reportPath}");
        }

        // Bust cache after import
        if (!$dryRun && !$stageMode && ($stats['success'] ?? 0) > 0) {
            Cache::forget('latest_books');
            Cache::forget('popular_books');
            $this->info('Cache cleared.');
        }

        return 0;
    }

    /**
     * Scan a folder for image files.
     */
    protected function scanImages(string $folderPath): array
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $files = [];

        foreach ($extensions as $ext) {
            $found = glob($folderPath . DIRECTORY_SEPARATOR . '*.{' . $ext . ',' . strtoupper($ext) . '}', GLOB_BRACE);
            if ($found) {
                $files = array_merge($files, $found);
            }
        }

        // Remove duplicates and sort
        $files = array_unique($files);
        sort($files);

        return $files;
    }

    /**
     * Write results to a CSV file.
     */
    protected function writeReport(string $path, array $rows): void
    {
        $fp = fopen($path, 'w');
        // BOM for Excel Arabic support
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['File', 'Folder', 'Status', 'Title', 'Author', 'Source', 'Book ID', 'Errors']);

        foreach ($rows as $row) {
            fputcsv($fp, [
                $row['file'],
                $row['folder'],
                $row['status'],
                $row['title'],
                $row['author'],
                $row['source'],
                $row['book_id'],
                $row['errors'],
            ]);
        }

        fclose($fp);
    }
}
