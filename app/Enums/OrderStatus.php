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
        return match ($this) {
            self::Pending    => [self::Processing, self::Cancelled, self::Failed],
            self::Processing => [self::Shipped, self::Cancelled, self::Failed],
            self::Shipped    => [self::Delivered, self::Returned],
            self::Delivered  => [self::Returned, self::Refunded],
            self::Failed     => [self::Pending],
            self::Returned   => [self::Refunded],
            self::Cancelled,
            self::Refunded   => [],
        };
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
