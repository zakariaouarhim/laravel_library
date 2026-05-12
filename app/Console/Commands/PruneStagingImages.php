<?php

namespace App\Console\Commands;

use App\Models\PendingBook;
use Illuminate\Console\Command;

class PruneStagingImages extends Command
{
    protected $signature = 'staging:prune-images {--days=7 : Delete files older than N days that aren\'t referenced by any non-discarded pending row}';

    protected $description = 'Clean up orphaned cover images in public/images/books/staging that no live pending_books row points to';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days)->getTimestamp();

        $stagingDir = public_path('images/books/staging');
        if (!is_dir($stagingDir)) {
            $this->info('No staging dir, nothing to do.');
            return self::SUCCESS;
        }

        // Gather every staging path actively referenced by a pending row that
        // could still be reviewed (anything except 'approved' / 'discarded').
        // staging_images is now {source => path}, so flatten across sources.
        $referenced = [];
        $rows = PendingBook::whereIn('status', [
                PendingBook::STATUS_ENRICHED,
                PendingBook::STATUS_FAILED,
                PendingBook::STATUS_DUPLICATE,
            ])
            ->whereNotNull('staging_images')
            ->pluck('staging_images');

        foreach ($rows as $imageMap) {
            foreach ((array) $imageMap as $path) {
                if ($path) {
                    $referenced[] = basename($path);
                }
            }
        }
        $referenced = array_unique($referenced);

        $deleted = 0;
        foreach (glob($stagingDir . '/*.webp') ?: [] as $file) {
            if (!is_file($file)) continue;
            if (filemtime($file) >= $cutoff) continue;
            if (in_array(basename($file), $referenced, true)) continue;

            @unlink($file);
            // Matching thumbnail — also delete.
            $thumb = $stagingDir . '/thumbs/' . basename($file);
            if (is_file($thumb)) @unlink($thumb);
            $deleted++;
        }

        $this->info("Pruned {$deleted} orphan staging image(s).");
        return self::SUCCESS;
    }
}
