<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => 'required|string|max:191',
            'description'      => 'nullable|string|max:2000',
            'type'             => 'required|in:pick_n_for_price',
            'quantity'         => 'required|integer|min:1',
            'fixed_price'      => 'required|numeric|min:0',
            'min_price'        => 'nullable|numeric|min:0',
            'max_price'        => 'nullable|numeric|min:0|gte:min_price',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date|after_or_equal:starts_at',
            'is_active'        => 'boolean',
            'banner_image'     => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'meta_title'       => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'book_ids'         => 'nullable|array',
            'book_ids.*'       => 'integer|exists:books,id',
            'excluded_book_ids'   => 'nullable|array',
            'excluded_book_ids.*' => 'integer|exists:books,id',
            'series_ids'          => 'nullable|array',
            'series_ids.*'        => 'integer|exists:series,id',
            'bundle_ids'          => 'nullable|array',
            'bundle_ids.*'        => 'integer|exists:books,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ends_at.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البداية',
            'quantity.min'           => 'عدد الكتب يجب أن يكون 1 على الأقل',
            'max_price.gte'          => 'السعر الأعلى يجب أن يكون أكبر من أو يساوي السعر الأدنى',
        ];
    }
}
