<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublishingHouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'         => 'required|string|max:191|unique:publishing_houses,name,' . $id,
            'address'      => 'nullable|string|max:2000',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:191',
            'website'      => 'nullable|url|max:255',
            'founded_year' => 'nullable|integer|min:1400|max:' . (date('Y') + 1),
            'country'      => 'nullable|string|max:100',
            'description'  => 'nullable|string|max:5000',
            'logo'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status'       => 'required|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'اسم دار النشر مطلوب',
            'name.unique'       => 'اسم دار النشر مستخدم بالفعل',
            'name.max'          => 'اسم دار النشر طويل جداً (الحد 191 حرف)',
            'email.email'       => 'صيغة البريد الإلكتروني غير صحيحة',
            'website.url'       => 'صيغة الموقع الإلكتروني غير صحيحة',
            'founded_year.min'  => 'سنة التأسيس غير صالحة',
            'founded_year.max'  => 'سنة التأسيس يجب ألا تتجاوز السنة الحالية',
            'logo.image'        => 'الملف يجب أن يكون صورة',
            'logo.max'          => 'حجم الشعار يجب ألا يتجاوز 2MB',
            'status.required'   => 'حالة دار النشر مطلوبة',
            'status.in'         => 'حالة دار النشر غير صالحة',
        ];
    }
}
