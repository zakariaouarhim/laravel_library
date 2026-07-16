<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Download an image from a URL and save it locally.
     * Detects MIME type and uses appropriate extension.
     *
     * @return string|null Relative path to the saved image, or null on failure
     */
    /**
     * Process raw image bytes (already fetched) into WebP + thumbnail. Used by
     * BookIngestionService after Http::pool downloads covers concurrently.
     */
    public function processFromBytes(string $bytes, string $destinationDir, string $filenamePrefix, float $zoomW = 1.0, float $zoomH = 1.0): ?string
    {
        try {
            $filename = $filenamePrefix . '_' . time() . mt_rand(100, 999) . '.webp';
            $fullDestination = public_path($destinationDir);
            $thumbDir = $fullDestination . '/thumbs';

            if (!file_exists($fullDestination)) mkdir($fullDestination, 0755, true);
            if (!file_exists($thumbDir)) mkdir($thumbDir, 0755, true);

            $this->saveLargeVariant($bytes, $destinationDir, $filename, $zoomW, $zoomH);

            $image = Image::read($bytes);
            $this->applyZoom($image, $zoomW, $zoomH);
            $image->scale(width: 400);
            $image->toWebp(80)->save($fullDestination . '/' . $filename);

            $thumb = Image::read($fullDestination . '/' . $filename);
            $thumb->scale(width: 150);
            $thumb->toWebp(75)->save($thumbDir . '/' . $filename);

            return $destinationDir . '/' . $filename;
        } catch (\Exception $e) {
            \Log::error('ImageService::processFromBytes failed: ' . $e->getMessage());
            return null;
        }
    }

    public function downloadFromUrl(string $url, string $destinationDir, string $filenamePrefix, float $zoomW = 1.0, float $zoomH = 1.0): ?string
    {
        try {
            // Try higher quality version first (Google Books specific)
            $highQualityUrl = str_replace('zoom=1', 'zoom=2', $url);
            $highQualityUrl = str_replace('&edge=curl', '', $highQualityUrl);

            $imageContent = @file_get_contents($highQualityUrl);
            if ($imageContent === false) {
                $imageContent = @file_get_contents($url);
            }

            if ($imageContent === false) {
                throw new \Exception('Failed to download image from URL');
            }

            $filename = $filenamePrefix . '_' . time() . '.webp';
            $fullDestination = public_path($destinationDir);
            $thumbDir = $fullDestination . '/thumbs';

            if (!file_exists($fullDestination)) {
                mkdir($fullDestination, 0755, true);
            }
            if (!file_exists($thumbDir)) {
                mkdir($thumbDir, 0755, true);
            }

            $this->saveLargeVariant($imageContent, $destinationDir, $filename, $zoomW, $zoomH);

            // Resize to 400px wide and convert to WebP
            $image = Image::read($imageContent);
            $this->applyZoom($image, $zoomW, $zoomH);
            $image->scale(width: 400);
            $image->toWebp(80)->save($fullDestination . '/' . $filename);

            // Generate 150px thumbnail
            $thumb = Image::read($fullDestination . '/' . $filename);
            $thumb->scale(width: 150);
            $thumb->toWebp(75)->save($thumbDir . '/' . $filename);

            return $destinationDir . '/' . $filename;

        } catch (\Exception $e) {
            \Log::error('Error downloading image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process a local image file: resize, convert to WebP, create thumbnail.
     *
     * @param string $filePath       Absolute path to the source image
     * @param string $destinationDir Relative dir under public/ (e.g., 'images/books')
     * @param string $filenamePrefix Prefix for the generated filename
     * @return string|null           Relative path to the saved image, or null on failure
     */
    public function processLocalFile(string $filePath, string $destinationDir, string $filenamePrefix, float $zoomW = 1.0, float $zoomH = 1.0): ?string
    {
        try {
            $filename = $filenamePrefix . '_' . time() . '_' . mt_rand(100, 999) . '.webp';
            $fullDestination = public_path($destinationDir);
            $thumbDir = $fullDestination . '/thumbs';

            if (!file_exists($fullDestination)) {
                mkdir($fullDestination, 0755, true);
            }
            if (!file_exists($thumbDir)) {
                mkdir($thumbDir, 0755, true);
            }

            $this->saveLargeVariant($filePath, $destinationDir, $filename, $zoomW, $zoomH);

            // Resize to 400px wide and convert to WebP
            $image = Image::read($filePath);
            $this->applyZoom($image, $zoomW, $zoomH);
            $image->scale(width: 400);
            $image->toWebp(80)->save($fullDestination . '/' . $filename);

            // Generate 150px thumbnail
            $thumb = Image::read($fullDestination . '/' . $filename);
            $thumb->scale(width: 150);
            $thumb->toWebp(75)->save($thumbDir . '/' . $filename);

            return $destinationDir . '/' . $filename;

        } catch (\Exception $e) {
            \Log::error('Error processing local image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save an 800px-wide WebP variant under <destinationDir>/large/<filename>
     * for retina @2x LCP. Sources <800px are saved at their native width
     * (no upscaling — that adds no visual information). Failures are logged
     * but not re-thrown: large variant is optional, srcset falls back to 400px.
     *
     * @param  string|UploadedFile $source  Raw bytes, local file path, or an UploadedFile
     */
    private function saveLargeVariant($source, string $destinationDir, string $filename, float $zoomW = 1.0, float $zoomH = 1.0): void
    {
        try {
            $largeDir = public_path($destinationDir . '/large');
            if (!file_exists($largeDir)) mkdir($largeDir, 0755, true);

            $img = Image::read($source);
            $this->applyZoom($img, $zoomW, $zoomH);
            // Don't upscale — saves no information and bloats the file.
            if ($img->width() > 800) {
                $img->scale(width: 800);
            }
            $img->toWebp(82)->save($largeDir . '/' . $filename);
        } catch (\Throwable $e) {
            \Log::warning('ImageService::saveLargeVariant failed: ' . $e->getMessage());
        }
    }

    /**
     * "Zoom" = keep the central 1/zoomW of the width and 1/zoomH of the height
     * (the admin's live preview in the modals shows exactly this center crop).
     * Independent axes so white side bars can be cut harder than the
     * top/bottom, reshaping a squarish source into a rectangular cover.
     * Public static so other image pipelines (BookAdminService) share the math.
     */
    public static function centerCrop($image, float $zoomW, float $zoomH): void
    {
        if ($zoomW > 1.01 || $zoomH > 1.01) {
            $image->crop(
                max(1, (int) round($image->width() / max(1.0, $zoomW))),
                max(1, (int) round($image->height() / max(1.0, $zoomH))),
                position: 'center'
            );
        }
    }

    private function applyZoom($image, float $zoomW, float $zoomH): void
    {
        self::centerCrop($image, $zoomW, $zoomH);
    }

    /**
     * Process and store a category image as WebP, resized to 300px wide.
     * Deletes the old image if provided.
     *
     * @return string Relative storage path to the saved image
     */
    public function processCategoryImage(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $image = Image::read($file);
        $image->scale(width: 300);
        $encoded = $image->toWebp(80);

        $filename = 'categories/' . uniqid('cat_') . '.webp';
        Storage::disk('public')->put($filename, (string) $encoded);

        return $filename;
    }

    /**
     * Process and store a series cover image as WebP, resized to 400px wide.
     * Deletes the old image if provided.
     *
     * @return string Relative storage path to the saved image
     */
    public function processSeriesImage(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $image = Image::read($file);
        $image->scale(width: 400);
        $encoded = $image->toWebp(80);

        $filename = 'series/' . uniqid('series_') . '.webp';
        Storage::disk('public')->put($filename, (string) $encoded);

        return $filename;
    }

    /**
     * Process and store a publisher logo as WebP, resized to 300px wide.
     * Deletes the old logo if provided.
     *
     * @return string Relative storage path to the saved image
     */
    public function processPublisherLogo(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $image = Image::read($file);
        $image->scale(width: 300);
        $encoded = $image->toWebp(80);

        $filename = 'publishers/' . uniqid('pub_') . '.webp';
        Storage::disk('public')->put($filename, (string) $encoded);

        return $filename;
    }

    /**
     * Process an uploaded avatar image: resize to 300px wide WebP.
     * Deletes the old avatar if provided.
     *
     * @return string Relative path to the stored avatar
     */
    public function processAvatar(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $image = Image::read($file);
        $image->scale(width: 300);
        $encoded = $image->toWebp(80);

        $filename = 'avatars/' . uniqid('avatar_') . '.webp';
        Storage::disk('public')->put($filename, (string) $encoded);

        return $filename;
    }
}
