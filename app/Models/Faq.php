<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Frequently asked question. Admin-editable via /admin/faqs.
 * Rendered as a Bootstrap accordion on /about plus emitted as JSON-LD
 * FAQPage schema (read by Google for FAQ rich results in SERPs).
 */
class Faq extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'answer', 'display_order', 'is_active'];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
