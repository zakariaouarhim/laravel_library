<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:100',
            'parent_id'        => 'nullable|exists:categories,id',
            'language'         => 'nullable|in:arabic,english,french,spanish,german',
            'categorie_icon'   => 'nullable|string|max:100',
            'categorie_image'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            // SEO overrides: leave blank to fall back to MetaBuilder auto-generation.
            'meta_title'        => 'nullable|string|max:70',
            'meta_description'  => 'nullable|string|max:160',
            'editorial_content' => 'nullable|string|max:3000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'اسم الفئة مطلوب',
            'parent_id.exists' => 'الفئة الأم المختارة غير موجودة',
        ];
    }
}
