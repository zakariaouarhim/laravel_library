<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => 'required|exists:books,id',
            'text'    => 'required|string|min:10|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required'    => 'معرف الكتاب مطلوب',
            'book_id.exists'      => 'الكتاب غير موجود',
            'text.required'       => 'نص الاقتباس مطلوب',
            'text.min'            => 'الاقتباس يجب أن يكون على الأقل 10 أحرف',
            'text.max'            => 'الاقتباس يجب أن لا يزيد عن 1000 حرف',
            'page_number.integer' => 'رقم الصفحة يجب أن يكون رقماً صحيحاً',
            'page_number.min'     => 'رقم الصفحة يجب أن يكون أكبر من 0',
            'page_number.max'     => 'رقم الصفحة يجب أن لا يزيد عن 9999',
        ];
    }
}
