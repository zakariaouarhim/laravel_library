<?php

namespace App\Listeners;

use App\Events\BookViewed;
use App\Services\UserInterestService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateInterestOnBookView implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(
        private UserInterestService $interests,
    ) {}

    public function handle(BookViewed $event): void
    {
        $this->interests->recordView($event->userId, $event->book);
    }

    public function failed(BookViewed $event, \Throwable $e): void
    {
        \Log::error('UpdateInterestOnBookView failed', [
            'user_id' => $event->userId,
            'book_id' => $event->book->id,
            'error'   => $e->getMessage(),
        ]);
    }
}
