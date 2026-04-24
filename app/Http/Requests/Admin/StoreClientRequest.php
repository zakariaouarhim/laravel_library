<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'اسم الزبون مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique'   => 'البريد الإلكتروني مستخدم بالفعل',
        ];
    }
}
