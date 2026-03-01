<?php

namespace App\Mail;

use App\Models\UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserModel $user;

    public function __construct(UserModel $user)
    {
        $this->user = $user;
    }

    public function build(): self
    {
        return $this
            ->subject('مرحباً بك في مكتبة الفقراء!')
            ->view('emails.welcome');
    }
}
