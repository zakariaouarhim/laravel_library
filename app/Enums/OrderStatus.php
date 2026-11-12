<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Shipped    = 'shipped';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';
    case Failed     = 'failed';
    case Refunded   = 'refunded';
    case Returned   = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'قيد الانتظار',
            self::Processing => 'قيد المعالجة',
            self::Shipped    => 'مشحون',
            self::Delivered  => 'تم التسليم',
            self::Cancelled  => 'ملغي',
            self::Failed     => 'فشل',
            self::Refunded   => 'مسترجع',
            self::Returned   => 'مرتجع',
        };
    }

    public function allowedTransitions(): array
    {
        // Admin may move an order from any status straight to any other
        // (e.g. pending → delivered for a same-day COD handoff). Customer-side
        // cancellation stays restricted via isCancellable().
        return array_values(array_filter(self::cases(), fn (self $s) => $s !== $this));
    }

    public function canTransitionTo(self $target): bool
    {
        return $this === $target || in_array($target, $this->allowedTransitions(), true);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
