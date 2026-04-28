<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Mail\OrderStatusUpdateMail;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    /**
     * Update an order's status, log the change, and send email notification.
     *
     * @throws \InvalidArgumentException if the transition is not allowed
     */
    public function updateStatus(Order $order, OrderStatus|string $newStatus, ?string $note = null): Order
    {
        $newStatus = $newStatus instanceof OrderStatus
            ? $newStatus
            : OrderStatus::from($newStatus);

        $oldStatus = $order->status;

        if (!$oldStatus->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "لا يمكن تغيير الحالة من \"{$oldStatus->label()}\" إلى \"{$newStatus->label()}\""
            );
        }

        if ($oldStatus === $newStatus) {
            return $order;
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === OrderStatus::Shipped) {
            $updateData['estimated_delivery_date'] = Carbon::now()->addWeekdays(3)->toDateString();
        } elseif ($newStatus === OrderStatus::Delivered) {
            $updateData['estimated_delivery_date'] = null;
        }

        $order->update($updateData);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status'   => $newStatus->value,
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

    private function sendStatusEmail(Order $order, OrderStatus $oldStatus, OrderStatus $newStatus, ?string $note): void
    {
        $order->loadMissing('checkoutDetail');

        $customerEmail = $order->checkoutDetail->email ?? ($order->user ? $order->user->email : null);

        if (!$customerEmail) {
            return;
        }

        $manageUrl = $order->management_token
            ? route('order.manage', ['token' => $order->management_token])
            : null;

        try {
            $customerName = $order->checkoutDetail->full_name ?? ($order->user ? $order->user->name : 'العميل');

            Mail::to($customerEmail)->send(new OrderStatusUpdateMail(
                $order,
                $customerName,
                $oldStatus->label(),
                $newStatus->label(),
                $note,
                $manageUrl
            ));
        } catch (\Exception $e) {
            \Log::error('Failed to send order status email: ' . $e->getMessage());
        }
    }
}
