<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string|max:2000',
            'author_id'     => 'nullable|exists:authors,id',
            'total_volumes' => 'nullable|integer|min:1|max:9999',
            'is_complete'   => 'nullable|boolean',
            'cover_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'اسم السلسلة مطلوب',
            'author_id.exists' => 'المؤلف المختار غير موجود',
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated();
        $data['is_complete'] = $this->boolean('is_complete');

        if ($key !== null) {
            return $data[$key] ?? $default;
        }

        return $data;
    }
}
