<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\SystemSetting;

class ImageService
{
    public function downloadAndStoreImage($imageUrl)
    {
        if (!$imageUrl || !SystemSetting::getSetting('image_download_enabled', true)) {
            return null;
        }

        $imageName = basename($imageUrl);
        $imagePath = "public/books/{$imageName}";

        // Download and store image
        $imageContent = file_get_contents($imageUrl);
        Storage::put($imagePath, $imageContent);

        return Storage::url($imagePath);
    }
}