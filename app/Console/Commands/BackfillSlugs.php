<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Services\Seo\Slugger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSlugs extends Command
{
    protected $signature = 'seo:backfill-slugs {--dry-run : Show what would change without writing}';
    protected $description = 'Generate slugs for any Book/Author/Category/PublishingHouse row that has slug=NULL.';

    public function handle(Slugger $slugger): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) $this->warn('DRY RUN — no writes.');

        $entities = [
            ['model' => Book::class,            'source' => 'title', 'label' => 'books'],
            ['model' => Author::class,          'source' => 'name',  'label' => 'authors'],
            ['model' => Category::class,        'source' => 'name',  'label' => 'categories'],
            ['model' => PublishingHouse::class, 'source' => 'name',  'label' => 'publishing_houses'],
        ];

        foreach ($entities as $e) {
            $this->info("=== {$e['label']} ===");
            $this->backfill($e['model'], $e['source'], $slugger, $dry);
        }

        return self::SUCCESS;
    }

    private function backfill(string $modelClass, string $sourceColumn, Slugger $slugger, bool $dry): void
    {
        // Use a raw DB query that ignores soft-delete scope — trashed rows still
        // need slugs so a future restore can't collide on the unique constraint.
        $table = (new $modelClass)->getTable();
        $total = DB::table($table)->whereNull('slug')->count();
        if ($total === 0) {
            $this->line('  Nothing to backfill.');
            return;
        }

        $this->line("  Backfilling {$total} row(s)…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Track in-memory to avoid duplicate slug collisions in the same run
        // (firstOrCreate-style writes might race the DB unique constraint).
        $usedThisRun = [];

        DB::table($table)
            ->whereNull('slug')
            ->select('id', $sourceColumn)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $sourceColumn, $slugger, $dry, &$usedThisRun, $bar) {
                foreach ($rows as $row) {
                    $source = (string) ($row->{$sourceColumn} ?? '');
                    $base = $slugger->make($source);
                    $slug = $base;
                    $i = 2;
                    // Uniqueness check: both DB rows (incl. trashed) AND slugs we've assigned in this same chunk batch.
                    while (
                        isset($usedThisRun[$slug])
                        || DB::table($table)->where('slug', $slug)->where('id', '!=', $row->id)->exists()
                    ) {
                        $slug = $base . '-' . $i++;
                    }
                    $usedThisRun[$slug] = true;

                    if (!$dry) {
                        DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
    }
}
