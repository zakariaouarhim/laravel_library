<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $customerName;
    public string $manageUrl;

    public function __construct(Order $order, string $customerName, string $manageUrl)
    {
        $this->order = $order;
        $this->customerName = $customerName;
        $this->manageUrl = $manageUrl;
    }

    public function build(): self
    {
        return $this
            ->subject('تأكيد الطلب #' . $this->order->id . ' — مكتبة الفقراء')
            ->view('emails.order-confirmation');
    }
}
