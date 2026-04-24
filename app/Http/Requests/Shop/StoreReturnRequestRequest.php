<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'reason'   => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'يرجى اختيار الطلب',
            'order_id.exists'   => 'الطلب غير موجود',
            'reason.required'   => 'يرجى كتابة سبب الإرجاع',
            'reason.max'        => 'سبب الإرجاع يجب ألا يتجاوز 1000 حرف',
        ];
    }
}
