<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactAutoReply extends Mailable
{
    use SerializesModels;

    public string $customerName;
    public string $subject;

    public function __construct(string $customerName, string $subject)
    {
        $this->customerName = $customerName;
        $this->subject = $subject;
    }

    public function build(): self
    {
        return $this
            ->subject('تم استلام رسالتك — مكتبة الفقراء')
            ->view('emails.contact-auto-reply');
    }
}
