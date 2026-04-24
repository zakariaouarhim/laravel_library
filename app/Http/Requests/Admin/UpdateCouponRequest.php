<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPercentage = $this->input('type') === 'percentage';
        $couponId = $this->route('coupon')?->id;

        return [
            'code'             => 'required|string|max:50|unique:coupons,code,' . $couponId,
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0.01' . ($isPercentage ? '|max:100' : ''),
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date',
            'is_active'        => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'هذا الكود مستخدم بالفعل',
            'value.max'   => 'نسبة الخصم يجب أن تكون بين 1 و 100',
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

        $data['is_active']        = $this->boolean('is_active', false);
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;

        if ($key !== null) {
            return $data[$key] ?? $default;
        }

        return $data;
    }
}
