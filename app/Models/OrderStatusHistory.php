<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'status',
        'note',
        'created_at',
    ];

    protected $dates = ['created_at'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
