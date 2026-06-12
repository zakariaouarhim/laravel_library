<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomeCarouselRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => 'required|string|max:191',
            'source_type'   => 'required|in:categories,author,manual',
            'book_limit'    => 'required|integer|min:1|max:50',
            'sort_order'    => 'nullable|integer',
            'is_active'     => 'boolean',

            'author_id'     => 'required_if:source_type,author|nullable|integer|exists:authors,id',

            'category_ids'  => 'required_if:source_type,categories|array',
            'category_ids.*'=> 'integer|exists:categories,id',

            'book_ids'      => 'required_if:source_type,manual|array',
            'book_ids.*'    => 'integer|exists:books,id',
        ];
    }

    public function messages(): array
    {
        return [
            'author_id.required_if'    => 'يجب اختيار مؤلف لهذا النوع من الكاروسيل',
            'category_ids.required_if' => 'يجب اختيار فئة واحدة على الأقل',
            'book_ids.required_if'     => 'يجب اختيار كتاب واحد على الأقل',
            'book_limit.max'           => 'الحد الأقصى لعدد الكتب هو 50',
        ];
    }
}
