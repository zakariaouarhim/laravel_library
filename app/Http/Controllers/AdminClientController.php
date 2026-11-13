<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use Illuminate\Http\Request;
use App\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminClientController extends Controller
{
    /**
     * The clients screen must not touch privileged accounts: only a
     * super_admin may view or modify an admin / super_admin account.
     * Prevents a plain admin from taking over the super-admin login.
     */
    private function guardPrivilegedTarget(UserModel $user): void
    {
        if (in_array($user->role, ['admin', 'super_admin'], true)
            && auth()->user()->role !== 'super_admin') {
            abort(403, 'غير مصرح');
        }
    }

    public function index()
    {
        $clients = UserModel::has('orders')->get();
        $totalClients = UserModel::has('orders')->count();
        $newClientsThisMonth = UserModel::whereHas('orders', function ($q) {
                $q->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ]);
            })
            ->whereDoesntHave('orders', function ($q) {
                $q->where('created_at', '<', Carbon::now()->startOfMonth());
            })
            ->count();
        $activeClients = UserModel::whereHas('orders', function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            })
            ->count();

        return view('Dashbord_Admin.client', compact('clients', 'totalClients', 'newClientsThisMonth', 'activeClients'));
    }

    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make(Str::random(12));

        $user = UserModel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الزبون بنجاح',
            'user' => $user,
        ]);
    }

    public function showclient($id)
    {
        $user = UserModel::with([
            'orders.orderDetails.book',
            'orders.checkoutDetail'
        ])
        ->findOrFail($id);

        $this->guardPrivilegedTarget($user);

        return response()->json($user);
    }

    public function update(UpdateClientRequest $request, $id)
    {
        $user = UserModel::findOrFail($id);
        $this->guardPrivilegedTarget($user);
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الزبون بنجاح',
            'user' => $user
        ]);
    }

    public function resetPasswordAdmin(Request $request, $id)
    {
        // Outside the try: abort(403) must reach the client as 403, not a generic 500.
        $user = UserModel::findOrFail($id);
        $this->guardPrivilegedTarget($user);

        try {
            $request->validate(['method' => 'required|in:auto,manual']);
            $method = $request->input('method');

            if ($method === 'auto') {
                $newPassword = Str::random(12);
                $user->update(['password' => Hash::make($newPassword)]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم توليد كلمة مرور عشوائية وإرسالها للزبون عبر البريد الإلكتروني'
                ]);
            } else {
                $validated = $request->validate([
                    'password' => 'required|string|min:8|confirmed'
                ], [
                    'password.required' => 'كلمة المرور مطلوبة',
                    'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
                    'password.confirmed' => 'كلمات المرور غير متطابقة'
                ]);

                $user->update(['password' => Hash::make($validated['password'])]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تعيين كلمة المرور الجديدة بنجاح'
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Reset password error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ، يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }
}
