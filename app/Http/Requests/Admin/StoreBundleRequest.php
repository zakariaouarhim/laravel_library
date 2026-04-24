<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'series_id'        => 'required|exists:series,id',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:5000',
            'price'            => 'required|numeric|min:0',
            'quantity'         => 'required|integer|min:0',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'items'            => 'required|array|min:1',
            'items.*.book_id'  => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'series_id.required' => 'يجب اختيار السلسلة',
            'title.required'     => 'اسم الباقة مطلوب',
            'price.required'     => 'السعر مطلوب',
            'quantity.required'  => 'الكمية مطلوبة',
            'items.required'     => 'يجب اختيار جزء واحد على الأقل',
            'items.min'          => 'يجب اختيار جزء واحد على الأقل',
        ];
    }
}
