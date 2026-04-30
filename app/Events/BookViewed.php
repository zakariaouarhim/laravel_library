<?php

namespace App\Events;

use App\Models\Book;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookViewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Book $book,
        public int $userId,
    ) {}
}
