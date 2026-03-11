<?php

namespace App\Services;

use App\Mail\OrderStatusUpdateMail;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    /**
     * Valid status transitions to prevent invalid state jumps.
     */
    private const ALLOWED_TRANSITIONS = [
        'pending'    => ['processing', 'cancelled', 'failed'],
        'processing' => ['shipped', 'cancelled', 'failed'],
        'shipped'    => ['delivered', 'returned'],
        'delivered'  => ['returned', 'refunded'],
        'cancelled'  => [],
        'failed'     => ['pending'],
        'refunded'   => [],
        'returned'   => ['refunded'],
    ];

    /**
     * Validate whether a status transition is allowed.
     *
     * @throws \InvalidArgumentException if transition is invalid
     */
    public function validateTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$from] ?? [];

        if (!in_array($to, $allowed)) {
            throw new \InvalidArgumentException(
                "لا يمكن تغيير الحالة من \"{$from}\" إلى \"{$to}\""
            );
        }
    }

    /**
     * Update an order's status, log the change, and send email notification.
     */
    public function updateStatus(Order $order, string $newStatus, ?string $note = null): Order
    {
        $oldStatus = $order->status;
        $this->validateTransition($oldStatus, $newStatus);

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'shipped') {
            $updateData['estimated_delivery_date'] = Carbon::now()->addWeekdays(3)->toDateString();
        } elseif ($newStatus === 'delivered') {
            $updateData['estimated_delivery_date'] = null;
        }

        $order->update($updateData);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status'   => $newStatus,
            'note'     => $note,
        ]);

        $this->sendStatusEmail($order, $oldStatus, $newStatus, $note);

        return $order;
    }

    /**
     * Get order counts grouped by status (single query).
     * Returns a Collection keyed by status name.
     */
    public function getStatusCounts(?int $userId = null): \Illuminate\Support\Collection
    {
        $query = Order::selectRaw('status, COUNT(*) as total')
            ->groupBy('status');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->pluck('total', 'status');
    }

    /**
     * Send a status-update email to the customer.
     */
    private function sendStatusEmail(Order $order, string $oldStatus, string $newStatus, ?string $note): void
    {
        $order->loadMissing('checkoutDetail');

        $customerEmail = $order->checkoutDetail->email ?? ($order->user ? $order->user->email : null);

        if (!$customerEmail) {
            return;
        }

        $statusLabels = Order::STATUS_LABELS;
        $manageUrl = $order->management_token
            ? route('order.manage', ['token' => $order->management_token])
            : null;

        try {
            $customerName = $order->checkoutDetail->full_name ?? ($order->user ? $order->user->name : 'العميل');

            Mail::to($customerEmail)->send(new OrderStatusUpdateMail(
                $order,
                $customerName,
                $statusLabels[$oldStatus] ?? $oldStatus,
                $statusLabels[$newStatus] ?? $newStatus,
                $note,
                $manageUrl
            ));
        } catch (\Exception $e) {
            \Log::error('Failed to send order status email: ' . $e->getMessage());
        }
    }
}
