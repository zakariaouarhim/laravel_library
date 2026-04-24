<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name'   => 'nullable|string|max:255',
            'arrival_date'    => 'required|date',
            'status'          => 'required|in:pending,processing,completed,cancelled',
            'notes'           => 'nullable|string',
            'total_books'     => 'nullable|integer|min:0',
            'processed_books' => 'nullable|integer|min:0',
        ];
    }
}
