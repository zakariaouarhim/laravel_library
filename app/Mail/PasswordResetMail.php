<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetLink;
    public string $userName;

    public function __construct(string $resetLink, string $userName)
    {
        $this->resetLink = $resetLink;
        $this->userName = $userName;
    }

    public function build(): self
    {
        return $this
            ->subject('رابط إعادة تعيين كلمة المرور — مكتبة الفقراء')
            ->view('emails.password-reset');
    }
}
