<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productName'            => 'required|string|max:255',
            'productauthor'          => 'required|string|max:255',
            'productauthor_id'              => 'nullable|integer|exists:authors,id',
            'productDescription'     => 'required|string',
            'productPrice'           => 'required|numeric|min:0',
            'productNumPages'        => 'nullable|integer|min:1',
            'productLanguage'        => 'nullable|string|max:100',
            'ProductPublishingHouse' => 'nullable|string|max:255',
            'ProductPublishingHouse_id'     => 'nullable|integer|exists:publishing_houses,id',
            'productIsbn'            => 'nullable|string|max:50|unique:books,isbn',
            'categories'             => 'required|array|min:1',
            'categories.*'           => 'exists:categories,id',
            'primary_category_id'    => 'required|in_array:categories.*',
            'productQuantity'        => 'required|integer|min:0',
            'productImage'           => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:2048',
            // Crop sliders (center crop per axis) shown under the image preview.
            'image_zoom_w'           => 'nullable|numeric|min:1|max:3',
            'image_zoom_h'           => 'nullable|numeric|min:1|max:3',
            // AI rewrite bookkeeping (nightly cron skips 'rewritten' books).
            'description_rewritten'  => 'nullable|boolean',
            'original_description'   => 'nullable|string',
            // Cover picked in the enrich panel (downloaded server-side on save).
            'image_url'              => 'nullable|url|max:2048',
            'auto_enrich'            => 'nullable|boolean',
            'series_id'              => 'nullable|exists:series,id',
            'volume_number'          => 'nullable|integer|min:1',
            // SEO overrides: leave blank to fall back to MetaBuilder auto-generation.
            'meta_title'             => 'nullable|string|max:70',
            'meta_description'       => 'nullable|string|max:160',
        ];
    }

    public function messages(): array
    {
        return [
            'productIsbn.unique' => 'هذا الرقم الدولي (ISBN) مستخدم بالفعل لكتاب آخر',
        ];
    }
}
