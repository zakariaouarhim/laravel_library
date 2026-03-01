<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $customerName;
    public string $oldStatus;
    public string $newStatus;
    public ?string $note;
    public ?string $manageUrl;

    public function __construct(Order $order, string $customerName, string $oldStatus, string $newStatus, ?string $note, ?string $manageUrl)
    {
        $this->order = $order;
        $this->customerName = $customerName;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->note = $note;
        $this->manageUrl = $manageUrl;
    }

    public function build(): self
    {
        return $this
            ->subject("تحديث حالة الطلب #{$this->order->id} — مكتبة الفقراء")
            ->view('emails.order-status-update');
    }
}
