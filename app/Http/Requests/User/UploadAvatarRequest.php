<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:2048|dimensions:max_width=2000,max_height=2000',
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required'   => 'يرجى اختيار صورة',
            'avatar.image'      => 'الملف يجب أن يكون صورة',
            'avatar.mimes'      => 'الصورة يجب أن تكون بصيغة jpeg, jpg, png أو webp',
            'avatar.max'        => 'حجم الصورة يجب ألا يتجاوز 2 ميغابايت',
            'avatar.dimensions' => 'أبعاد الصورة يجب ألا تتجاوز 2000x2000 بكسل',
        ];
    }
}
