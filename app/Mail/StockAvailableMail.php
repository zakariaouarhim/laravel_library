<?php

namespace App\Mail;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StockAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Book $book) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'الكتاب متوفر الآن — ' . $this->book->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.stock-available',
        );
    }
}
