<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $table = 'return_requests';

    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'reason',
        'admin_notes',
        'payment_method',
        'refund_amount',
        'guest_email',
        'resolved_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
