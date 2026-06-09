<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Backfill 800px retina variants for book covers under public/images/books/large/.
 *
 * Operates only on books whose original source URL is known (original_image
 * column populated by BookEnrichmentService). Upscaling the existing 400px
 * WebP adds no information, so we refetch the original source and downscale
 * to 800px instead.
 */
class ReprocessCovers extends Command
{
    protected $signature = 'books:reprocess-covers
        {--only-api-sourced : Only books with api_source IS NOT NULL}
        {--limit=0 : Max books to process (0 = all)}
        {--dry-run : Show what would happen without writing files}';

    protected $description = 'Backfill 800px retina variants for book covers (large/) from original source URLs';

    public function handle(): int
    {
        $query = Book::query()
            ->whereNotNull('original_image')
            ->whereNotNull('image');

        if ($this->option('only-api-sourced')) {
            $query->whereNotNull('api_source');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $dryRun = (bool) $this->option('dry-run');
        $total = (clone $query)->count();
        $this->info("Found {$total} candidate books.");

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $query->orderBy('id')->chunk(50, function ($books) use (&$processed, &$skipped, &$failed, $dryRun) {
            foreach ($books as $book) {
                $filename = basename($book->image);
                $largeRelative = 'images/books/large/' . $filename;
                $largeAbs = public_path($largeRelative);

                if (file_exists($largeAbs)) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("[dry-run] would fetch {$book->original_image} -> {$largeRelative}");
                    $processed++;
                    continue;
                }

                try {
                    $bytes = @file_get_contents($book->original_image);
                    if ($bytes === false) {
                        $this->warn("  fetch failed: id={$book->id} url={$book->original_image}");
                        $failed++;
                        continue;
                    }

                    $largeDir = dirname($largeAbs);
                    if (!file_exists($largeDir)) mkdir($largeDir, 0755, true);

                    $img = Image::read($bytes);
                    if ($img->width() > 800) {
                        $img->scale(width: 800);
                    }
                    $img->toWebp(82)->save($largeAbs);

                    $processed++;
                    if ($processed % 25 === 0) {
                        $this->info("  processed {$processed} so far...");
                    }
                } catch (\Throwable $e) {
                    \Log::warning("ReprocessCovers failed for book {$book->id}: " . $e->getMessage());
                    $failed++;
                }
            }
        });

        $this->newLine();
        $this->info("Done. processed={$processed} skipped(already exist)={$skipped} failed={$failed}");

        return self::SUCCESS;
    }
}
