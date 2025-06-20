<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    protected $fillable = [
        'shipment_id',
        'book_id',
        'isbn',
        'title',
        'author',
        'quantity_received',
        'cost_price',
        'selling_price',
        'processing_status',
        'error_message'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}