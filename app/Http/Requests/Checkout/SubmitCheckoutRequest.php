<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SubmitCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'      => 'required|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'required|string|regex:/^[0-9]{10}$/',
            'address'        => 'required|string',
            'city'           => 'required|string',
            'notes'          => 'nullable|string|max:500',
            'payment_method' => 'required|in:cod,bank_transfer',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'      => 'الاسم الكامل مطلوب',
            'email.email'             => 'يرجى إدخال بريد إلكتروني صحيح',
            'phone.required'          => 'رقم الهاتف مطلوب',
            'phone.regex'             => 'يرجى إدخال رقم هاتف صحيح (10 أرقام)',
            'address.required'        => 'العنوان مطلوب',
            'city.required'           => 'المدينة مطلوبة',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated();

        if (empty($data['email']) && Auth::check()) {
            $data['email'] = Auth::user()->email;
        }

        if ($key !== null) {
            return $data[$key] ?? $default;
        }

        return $data;
    }
}
