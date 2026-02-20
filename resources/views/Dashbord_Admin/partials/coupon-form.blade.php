<div class="mb-3">
    <label class="form-label">الكود *</label>
    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
           value="{{ old('code', $coupon->code ?? '') }}"
           placeholder="مثال: SAVE20" required style="text-transform:uppercase;">
    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">نوع الخصم *</label>
    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
        <option value="percentage" {{ old('type', $coupon->type ?? '') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
        <option value="fixed"      {{ old('type', $coupon->type ?? '') === 'fixed'      ? 'selected' : '' }}>مبلغ ثابت (د.م)</option>
    </select>
    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">القيمة *</label>
    <input type="number" name="value" class="form-control @error('value') is-invalid @enderror"
           step="0.01" min="0.01"
           value="{{ old('value', $coupon->value ?? '') }}" required>
    @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">الحد الأدنى للطلب (د.م)</label>
    <input type="number" name="min_order_amount" class="form-control"
           step="0.01" min="0"
           value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}">
</div>

<div class="mb-3">
    <label class="form-label">الحد الأقصى للاستخدام</label>
    <input type="number" name="max_uses" class="form-control"
           min="1"
           value="{{ old('max_uses', $coupon->max_uses ?? '') }}">
    <div class="form-text">اتركه فارغاً لاستخدام غير محدود</div>
</div>

<div class="mb-3">
    <label class="form-label">تاريخ الانتهاء</label>
    <input type="date" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror"
           value="{{ old('expires_at', isset($coupon->expires_at) ? $coupon->expires_at?->format('Y-m-d') : '') }}">
    @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
           {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">مفعّل</label>
</div>
