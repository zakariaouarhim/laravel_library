<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_reference'           => 'required|unique:shipments',
            'supplier_name'                => 'nullable|string|max:255',
            'arrival_date'                 => 'required|date',
            'notes'                        => 'nullable|string',
            'items'                        => 'required|array|min:1',
            'items.*.book_id'              => 'nullable|exists:books,id',
            'items.*.isbn'                 => 'required|string',
            'items.*.title'                => 'required|string',
            'items.*.author_id'            => 'nullable|exists:authors,id',
            'items.*.publishing_house_id'  => 'nullable|exists:publishing_houses,id',
            'items.*.language'             => 'nullable|string|in:arabic,english,french,spanish,german',
            'items.*.quantity_received'    => 'required|integer|min:1',
            'items.*.cost_price'           => 'nullable|numeric|min:0',
            'items.*.selling_price'        => 'required|numeric|min:0',
        ];
    }
}
