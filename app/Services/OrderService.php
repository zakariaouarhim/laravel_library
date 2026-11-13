<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Mail\OrderStatusUpdateMail;
use App\Models\Book;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    /**
     * Statuses whose books are back in (or never left) the store, so their
     * quantities return to stock. Refunded is a money event only: it never
     * moves stock in either direction.
     */
    private const RESTOCK_STATUSES = [OrderStatus::Cancelled, OrderStatus::Failed, OrderStatus::Returned];

    /**
     * Update an order's status, move stock accordingly, log the change, and
     * send the customer email.
     *
     * Stock rules (idempotent via orders.stock_restored_at):
     *  - entering cancelled/failed/returned → books go back to stock (once)
     *  - leaving them to an active status   → books are deducted again,
     *    blocked with a clear message if stock ran out in the meantime
     *
     * @throws \InvalidArgumentException if the transition is not allowed or
     *                                   stock is insufficient to reactivate
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

        $order = DB::transaction(function () use ($order, $newStatus, $note) {
            // Re-read under lock: a concurrent request (double tap) must not
            // restock or deduct the same order twice.
            $fresh = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status === $newStatus) {
                return $fresh;
            }

            $updateData = ['status' => $newStatus];

            if ($newStatus === OrderStatus::Shipped) {
                $updateData['estimated_delivery_date'] = Carbon::now()->addWeekdays(3)->toDateString();
            } elseif ($newStatus === OrderStatus::Delivered) {
                $updateData['estimated_delivery_date'] = null;
            }

            $enteringRestock = in_array($newStatus, self::RESTOCK_STATUSES, true)
                && $fresh->stock_restored_at === null;
            $leavingRestock = !in_array($newStatus, self::RESTOCK_STATUSES, true)
                && $newStatus !== OrderStatus::Refunded
                && $fresh->stock_restored_at !== null;

            // Log types come from the inventory_logs ENUM:
            // 'return' = books came back from this order, 'sale' = deducted again.
            if ($enteringRestock) {
                $this->adjustStock($fresh, +1, 'return');
                $updateData['stock_restored_at'] = Carbon::now();
            } elseif ($leavingRestock) {
                $this->adjustStock($fresh, -1, 'sale');
                $updateData['stock_restored_at'] = null;
            }

            $fresh->update($updateData);

            OrderStatusHistory::create([
                'order_id' => $fresh->id,
                'status'   => $newStatus->value,
                'note'     => $note,
            ]);

            return $fresh;
        });

        // Outside the transaction: SMTP is slow and must not hold row locks.
        $this->sendStatusEmail($order, $oldStatus, $newStatus, $note);

        return $order;
    }

    /**
     * Atomically return (+1) or re-deduct (-1) the order's book quantities,
     * under row locks, writing an inventory_logs row per book.
     *
     * @throws \InvalidArgumentException when deducting and stock is short
     */
    private function adjustStock(Order $order, int $direction, string $logType): void
    {
        $order->loadMissing('orderDetails');

        // Merge per book: offers can put the same book on two order lines.
        $needs = [];
        foreach ($order->orderDetails as $detail) {
            $needs[$detail->book_id] = ($needs[$detail->book_id] ?? 0) + (int) $detail->quantity;
        }
        if (!$needs) {
            return;
        }

        $books = Book::whereIn('id', array_keys($needs))->lockForUpdate()->get()->keyBy('id');

        foreach ($needs as $bookId => $qty) {
            $book = $books->get($bookId);
            if (!$book) {
                continue; // book no longer exists; nothing to adjust
            }

            if ($direction < 0 && $book->quantity < $qty) {
                throw new \InvalidArgumentException(
                    "لا يمكن إعادة تفعيل الطلب: الكمية غير كافية للكتاب \"{$book->title}\" (المتوفر: {$book->quantity})"
                );
            }

            $before = (int) $book->quantity;
            $after  = $before + ($direction * $qty);

            Book::whereKey($bookId)->update(['quantity' => $after]);

            InventoryLog::create([
                'book_id'         => $bookId,
                'type'            => $logType,
                'quantity_change' => $direction * $qty,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'reference_type'  => 'order',
                'reference_id'    => $order->id,
            ]);
        }
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
