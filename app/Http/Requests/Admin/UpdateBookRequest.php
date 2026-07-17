<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookId = $this->route('id');

        return [
            'title'               => 'required|string|max:255',
            'description'         => 'required|string',
            'price'               => 'required|numeric|min:0',
            'author'              => 'required|string|max:255',
            'author_id'           => 'nullable|integer|exists:authors,id',
            'page_num'            => 'nullable|integer|min:1',
            'language'            => 'nullable|string|max:100',
            'publishing_house'    => 'nullable|string|max:255',
            'publishing_house_id' => 'nullable|integer|exists:publishing_houses,id',
            'isbn'                => 'nullable|string|max:50|unique:books,isbn,' . $bookId,
            'quantity'            => 'required|integer|min:0',
            'categories'          => 'nullable|array|min:1',
            'categories.*'        => 'exists:categories,id',
            'primary_category_id' => 'nullable|in_array:categories.*',
            'category_id'         => 'nullable|integer|exists:categories,id',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:2048',
            // Crop sliders (center crop per axis); apply to the existing cover too.
            'image_zoom_w'        => 'nullable|numeric|min:1|max:3',
            'image_zoom_h'        => 'nullable|numeric|min:1|max:3',
            // AI rewrite bookkeeping (nightly cron skips 'rewritten' books).
            'description_rewritten' => 'nullable|boolean',
            'original_description'  => 'nullable|string',
            // Cover picked in the enrich panel (downloaded server-side on save).
            'image_url'             => 'nullable|url|max:2048',
            'series_id'           => 'nullable|exists:series,id',
            'volume_number'       => 'nullable|integer|min:1',
            // SEO overrides: leave blank to fall back to MetaBuilder auto-generation.
            'meta_title'          => 'nullable|string|max:70',
            'meta_description'    => 'nullable|string|max:160',
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.unique' => 'هذا الرقم الدولي (ISBN) مستخدم بالفعل لكتاب آخر',
        ];
    }
}
