<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Asynchronously fetch a book cover from a remote URL, run it through
 * ImageService (which now produces 400px + 800px + 150px thumb WebP),
 * and persist the resulting image path on the Book row.
 *
 * Dispatch this from any caller that doesn't need the image URL back in the
 * response (typically bulk API ingestion). For admin one-book uploads where
 * UX requires the URL immediately, keep calling ImageService synchronously.
 *
 * With QUEUE_CONNECTION=sync (current default) this runs inline. After the
 * VPS gains a queue worker (see deployment/queue-setup.md) it offloads to
 * the worker without code changes.
 */
class ProcessBookCoverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];
    public int $timeout = 120;

    public function __construct(
        public int $bookId,
        public string $sourceUrl,
        public string $destinationDir = 'images/books',
        public string $filenamePrefix = 'cover',
    ) {}

    public function handle(ImageService $images): void
    {
        $book = Book::find($this->bookId);
        if (!$book) {
            return; // book deleted between dispatch and run — nothing to do
        }

        $path = $images->downloadFromUrl(
            $this->sourceUrl,
            $this->destinationDir,
            $this->filenamePrefix . '_' . $this->bookId,
        );

        if ($path) {
            $book->image          = $path;
            $book->original_image = $this->sourceUrl;
            $book->saveQuietly(); // skip BookObserver — image change isn't catalog-meaningful
        }
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('ProcessBookCoverJob failed', [
            'book_id'    => $this->bookId,
            'source_url' => $this->sourceUrl,
            'error'      => $e->getMessage(),
        ]);
    }
}
