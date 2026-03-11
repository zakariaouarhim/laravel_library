<?php

namespace App\Services;

use App\Mail\StockAvailableMail;
use App\Models\Author;
use App\Models\Book;
use App\Models\PublishingHouse;
use App\Models\StockNotification;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Laravel\Facades\Image;

class BookAdminService
{
    /**
     * Process and store a book cover image as WebP with thumbnail.
     * Optionally deletes the previous image.
     */
    public function processBookImage($file, ?string $oldImagePath = null): string
    {
        $imageName = time() . '_' . uniqid() . '.webp';
        $destinationPath = public_path('images/books');
        $thumbPath = public_path('images/books/thumbs');

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        if (!file_exists($thumbPath)) {
            mkdir($thumbPath, 0755, true);
        }

        if ($oldImagePath && file_exists(public_path($oldImagePath))) {
            unlink(public_path($oldImagePath));
        }

        $image = Image::read($file instanceof \Illuminate\Http\UploadedFile ? $file->getRealPath() : $file);
        $image->scale(width: 400);
        $image->toWebp(80)->save($destinationPath . '/' . $imageName);

        $thumb = Image::read($destinationPath . '/' . $imageName);
        $thumb->scale(width: 150);
        $thumb->toWebp(75)->save($thumbPath . '/' . $imageName);

        return 'images/books/' . $imageName;
    }

    /**
     * Find an author by name or create a new one.
     */
    public function findOrCreateAuthor(string $name): Author
    {
        return Author::firstOrCreate(
            ['name' => trim($name)],
            ['status' => 'active']
        );
    }

    /**
     * Find a publishing house by name or create a new one.
     * Returns the ID or null if name is empty.
     */
    public function findOrCreatePublishingHouse(?string $name): ?int
    {
        $name = trim($name ?? '');
        if (empty($name)) {
            return null;
        }

        return PublishingHouse::firstOrCreate(
            ['name' => $name],
            ['status' => 'active']
        )->id;
    }

    /**
     * Notify users who subscribed to stock alerts for a book.
     * Sends email + creates in-app notification.
     */
    public function notifyStockSubscribers(Book $product): void
    {
        $notifications = StockNotification::where('book_id', $product->id)
            ->whereNull('notified_at')
            ->get();

        foreach ($notifications as $notification) {
            try {
                Mail::to($notification->email)->send(new StockAvailableMail($product));
                $notification->update(['notified_at' => now()]);
            } catch (\Exception $mailError) {
                \Log::error('Stock notification email failed for book ' . $product->id . ': ' . $mailError->getMessage());
            }

            if ($notification->user_id) {
                UserNotification::create([
                    'user_id' => $notification->user_id,
                    'type'    => 'stock_available',
                    'title'   => 'الكتاب متوفر الآن',
                    'body'    => '«' . $product->title . '» أصبح متوفراً. أضفه للسلة قبل نفاد الكمية!',
                    'url'     => route('moredetail2.page', $product->id),
                ]);
            }
        }
    }
}
