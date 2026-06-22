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
        {--id= : Process a single book by id}
        {--from-base : Regenerate the large/ variant from the on-disk base image instead of refetching original_image}
        {--force : Overwrite large/ variants that already exist (default skips them)}
        {--limit=0 : Max books to process (0 = all)}
        {--dry-run : Show what would happen without writing files}';

    protected $description = 'Backfill/repair 800px retina variants for book covers (large/) from original source URLs or the curated base image';

    public function handle(): int
    {
        $fromBase = (bool) $this->option('from-base');

        $query = Book::query()->whereNotNull('image');

        // Refetch mode needs the remote source URL; --from-base reads the base
        // image off disk, so original_image is irrelevant there.
        if (!$fromBase) {
            $query->whereNotNull('original_image');
        }

        if ($this->option('only-api-sourced')) {
            $query->whereNotNull('api_source');
        }

        if ($this->option('id')) {
            $query->whereKey((int) $this->option('id'));
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $total = (clone $query)->count();
        $this->info("Found {$total} candidate books.");

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $query->orderBy('id')->chunk(50, function ($books) use (&$processed, &$skipped, &$failed, $dryRun, $force, $fromBase) {
            foreach ($books as $book) {
                $filename = basename($book->image);
                $largeRelative = 'images/books/large/' . $filename;
                $largeAbs = public_path($largeRelative);

                // Existing variants are left alone unless --force: backfill only
                // fills gaps, while --force repairs stale/diverged variants.
                if (file_exists($largeAbs) && !$force) {
                    $skipped++;
                    continue;
                }

                // --from-base derives the large variant from the curated base
                // image on disk (source of truth shown everywhere else), so it
                // can never diverge from the cover the admin actually set. The
                // default refetch path uses the remote original_image, which may
                // point to a different edition/language than a hand-replaced base.
                $source = $fromBase ? public_path($book->image) : $book->original_image;

                if ($fromBase && !file_exists($source)) {
                    $this->warn("  base image missing on disk: id={$book->id} path={$book->image}");
                    $failed++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("[dry-run] would build {$largeRelative} from {$source}");
                    $processed++;
                    continue;
                }

                try {
                    $bytes = @file_get_contents($source);
                    if ($bytes === false) {
                        $this->warn("  read failed: id={$book->id} source={$source}");
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
