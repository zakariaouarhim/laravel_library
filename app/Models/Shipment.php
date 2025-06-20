<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_reference',
        'supplier_name',
        'arrival_date',
        'status',
        'total_books',
        'processed_books',
        'notes'
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'total_books' => 'integer',
        'processed_books' => 'integer'
    ];

    // Relationship with ShipmentItems
    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    // Calculate progress percentage
    public function getProgressPercentageAttribute()
    {
        return $this->total_books > 0 ? round(($this->processed_books / $this->total_books) * 100) : 0;
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Get status in Arabic
    public function getStatusInArabicAttribute()
    {
        $statuses = [
            'pending' => 'في الانتظار',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغية'
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}