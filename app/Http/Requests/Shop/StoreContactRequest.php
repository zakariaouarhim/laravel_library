<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // These three columns are varchar(191), not 255 — see
            // Schema::defaultStringLength(191) in AppServiceProvider. `message`
            // is TEXT, so max:5000 is already within bounds.
            'name'    => 'required|string|max:191',
            'email'   => 'required|email|max:191',
            'subject' => 'required|string|max:191',
            'message' => 'required|string|max:5000',
        ];
    }
}
