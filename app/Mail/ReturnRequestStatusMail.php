<?php

namespace App\Mail;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReturnRequestStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public ReturnRequest $returnRequest;
    public string $customerName;
    public string $statusText;
    public ?string $manageUrl;

    public function __construct(ReturnRequest $returnRequest, string $customerName, string $statusText, ?string $manageUrl)
    {
        $this->returnRequest = $returnRequest;
        $this->customerName = $customerName;
        $this->statusText = $statusText;
        $this->manageUrl = $manageUrl;
    }

    public function build(): self
    {
        return $this
            ->subject('تحديث طلب الإسترجاع — مكتبة الفقراء')
            ->view('emails.return-request-status');
    }
}
