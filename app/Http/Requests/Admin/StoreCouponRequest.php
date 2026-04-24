<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPercentage = $this->input('type') === 'percentage';

        return [
            'code'             => 'required|string|max:50|unique:coupons,code',
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0.01' . ($isPercentage ? '|max:100' : ''),
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date|after:now',
            'is_active'        => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'      => 'هذا الكود مستخدم بالفعل',
            'value.min'        => 'يجب أن تكون قيمة الخصم أكبر من صفر',
            'value.max'        => 'نسبة الخصم يجب أن تكون بين 1 و 100',
            'expires_at.after' => 'تاريخ الانتهاء يجب أن يكون في المستقبل',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated();

        $data['is_active']        = $this->boolean('is_active', true);
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;

        if ($key !== null) {
            return $data[$key] ?? $default;
        }

        return $data;
    }
}
