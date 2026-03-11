<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminUserController extends Controller
{
    public function usersIndex(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'role'   => 'nullable|in:user,admin',
        ]);

        $search     = $request->input('search', '');
        $roleFilter = $request->input('role', '');

        $query = UserModel::whereIn('role', ['user', 'admin']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        $users       = $query->latest()->paginate(15);
        $totalUsers  = UserModel::where('role', 'user')->count();
        $totalAdmins = UserModel::where('role', 'admin')->count();
        $newThisMonth = UserModel::whereIn('role', ['user', 'admin'])
                            ->where('created_at', '>=', Carbon::now()->startOfMonth())
                            ->count();

        return view('Dashbord_Admin.users', compact('users', 'totalUsers', 'totalAdmins', 'newThisMonth'));
    }

    public function promoteUser($id)
    {
        $target = UserModel::findOrFail($id);

        if ($target->role !== 'user') {
            return response()->json(['success' => false, 'message' => 'يمكن ترقية الزبائن فقط'], 422);
        }

        $target->role = 'admin';
        $target->save();

        Log::info('Role promotion', ['by' => auth()->id(), 'target' => $id, 'from' => 'user', 'to' => 'admin']);

        return response()->json(['success' => true, 'message' => 'تم ترقية المستخدم إلى مشرف بنجاح']);
    }

    public function demoteUser($id)
    {
        $target = UserModel::findOrFail($id);

        if ($target->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك تخفيض صلاحياتك الخاصة'], 422);
        }

        if ($target->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'يمكن تخفيض المشرفين فقط'], 422);
        }

        $target->role = 'user';
        $target->save();

        Log::info('Role demotion', ['by' => auth()->id(), 'target' => $id, 'from' => 'admin', 'to' => 'user']);

        return response()->json(['success' => true, 'message' => 'تم تخفيض المشرف إلى زبون بنجاح']);
    }

}
