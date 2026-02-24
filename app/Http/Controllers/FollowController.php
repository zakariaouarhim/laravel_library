<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    /**
     * Toggle follow/unfollow for an author or publisher.
     * Route: POST /follow/{type}/{id}  (type: 'author' or 'publisher')
     */
    public function toggle(string $type, int $id)
    {
        if (!Auth::check()) {
            return response()->json([
                'success'  => false,
                'redirect' => route('login2.page'),
            ], 401);
        }

        if (!in_array($type, ['author', 'publisher'])) {
            return response()->json(['success' => false, 'message' => 'نوع غير صالح'], 422);
        }

        $existing = Follow::where('user_id', Auth::id())
            ->where('followable_type', $type)
            ->where('followable_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success'   => true,
                'following' => false,
                'message'   => 'تم إلغاء المتابعة',
            ]);
        }

        Follow::create([
            'user_id'        => Auth::id(),
            'followable_type' => $type,
            'followable_id'  => $id,
        ]);

        return response()->json([
            'success'   => true,
            'following' => true,
            'message'   => 'أنت الآن تتابع هذا ' . ($type === 'author' ? 'المؤلف' : 'الناشر'),
        ]);
    }
}
