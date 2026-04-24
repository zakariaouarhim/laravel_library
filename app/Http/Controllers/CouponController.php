<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query();

        if ($search = $request->input('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
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

    public function store(StoreCouponRequest $request)
    {
        Coupon::create($request->validated());

        return back()->with('success', 'تم إنشاء الكوبون بنجاح.');
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $coupon->update($request->validated());

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
