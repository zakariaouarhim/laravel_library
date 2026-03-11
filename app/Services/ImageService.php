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
    public function downloadFromUrl(string $url, string $destinationDir, string $filenamePrefix): ?string
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

            // Detect extension from MIME type
            $extension = 'jpg';
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            if (strpos($mimeType, 'png') !== false) {
                $extension = 'png';
            } elseif (strpos($mimeType, 'webp') !== false) {
                $extension = 'webp';
            }

            $filename = $filenamePrefix . '_' . time() . '.' . $extension;
            $fullDestination = public_path($destinationDir);

            if (!file_exists($fullDestination)) {
                mkdir($fullDestination, 0755, true);
            }

            $fullPath = $fullDestination . '/' . $filename;
            if (file_put_contents($fullPath, $imageContent) === false) {
                throw new \Exception('Failed to save image to disk');
            }

            return $destinationDir . '/' . $filename;

        } catch (\Exception $e) {
            \Log::error('Error downloading image: ' . $e->getMessage());
            return null;
        }
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
