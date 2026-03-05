<?php

namespace App\Mail;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StockAvailableMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Book $book;

    public function __construct(Book $book)
    {
        $this->book = $book;
    }

    public function build(): self
    {
        return $this
            ->subject('الكتاب متوفر الآن — ' . $this->book->title)
            ->view('emails.stock-available');
    }
}
