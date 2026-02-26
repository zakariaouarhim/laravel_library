<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\Laravel\Facades\Image;

class GenerateThumbnails extends Command
{
    protected $signature = 'images:thumbnails';
    protected $description = 'Generate 150px thumbnails for existing book images';

    public function handle()
    {
        $sourcePath = public_path('images/books');
        $thumbPath = public_path('images/books/thumbs');

        if (!is_dir($thumbPath)) {
            mkdir($thumbPath, 0755, true);
        }

        $files = glob($sourcePath . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        $count = 0;

        foreach ($files as $file) {
            $filename = basename($file);
            $thumbFile = $thumbPath . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp';

            if (file_exists($thumbFile)) {
                continue;
            }

            try {
                $image = Image::read($file);
                $image->scale(width: 150);
                $image->toWebp(75)->save($thumbFile);
                $count++;
                $this->info("Generated: {$filename}");
            } catch (\Exception $e) {
                $this->warn("Failed: {$filename} — {$e->getMessage()}");
            }
        }

        $this->info("Done! Generated {$count} thumbnails.");
    }
}
