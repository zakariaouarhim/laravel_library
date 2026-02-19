<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query();

        if ($search = $request->input('search')) {
            $query->where('code', 'like', '%' . $search . '%');
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $coupons = $query->latest()->paginate(20)->withQueryString();

        return view('Dashbord_Admin.coupons', compact('coupons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code',
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date|after:now',
            'is_active'        => 'boolean',
        ], [
            'code.unique'  => 'هذا الكود مستخدم بالفعل',
            'value.min'    => 'يجب أن تكون قيمة الخصم أكبر من صفر',
            'expires_at.after' => 'تاريخ الانتهاء يجب أن يكون في المستقبل',
        ]);

        $validated['code']      = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0;

        // Percentage coupons must be 1–100
        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'نسبة الخصم يجب أن تكون بين 1 و 100'])->withInput();
        }

        Coupon::create($validated);

        return back()->with('success', 'تم إنشاء الكوبون بنجاح.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date',
            'is_active'        => 'boolean',
        ], [
            'code.unique' => 'هذا الكود مستخدم بالفعل',
        ]);

        $validated['code']      = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0;

        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return response()->json(['success' => false, 'message' => 'نسبة الخصم يجب أن تكون بين 1 و 100'], 422);
        }

        $coupon->update($validated);

        return response()->json(['success' => true, 'message' => 'تم تحديث الكوبون بنجاح.']);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الكوبون.']);
    }

    public function toggleActive(Coupon $coupon)
    {
        $coupon->update(['is_active' => !$coupon->is_active]);
        $state = $coupon->is_active ? 'مفعّل' : 'معطّل';
        return response()->json(['success' => true, 'message' => "الكوبون الآن {$state}.", 'is_active' => $coupon->is_active]);
    }
}
