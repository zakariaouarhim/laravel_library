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
            'productDescription'     => 'required|string',
            'productPrice'           => 'required|numeric|min:0',
            'productNumPages'        => 'nullable|integer|min:1',
            'productLanguage'        => 'nullable|string|max:100',
            'ProductPublishingHouse' => 'nullable|string|max:255',
            'productIsbn'            => 'nullable|string|max:50|unique:books,isbn',
            'categories'             => 'required|array|min:1',
            'categories.*'           => 'exists:categories,id',
            'primary_category_id'    => 'required|in_array:categories.*',
            'productQuantity'        => 'required|integer|min:0',
            'productImage'           => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:2048',
            'auto_enrich'            => 'nullable|boolean',
            'series_id'              => 'nullable|exists:series,id',
            'volume_number'          => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'productIsbn.unique' => 'هذا الرقم الدولي (ISBN) مستخدم بالفعل لكتاب آخر',
        ];
    }
}
