<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;

class PasswordResetController extends Controller
{
    public function showForgotPasswordForm()
    {
        return view('forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ], [
                'email.required' => 'البريد الإلكتروني مطلوب',
                'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            ]);

            $genericMessage = 'إذا كان هذا البريد مسجلاً، سيتم إرسال رابط إعادة تعيين كلمة المرور';

            $user = UserModel::where('email', $request->email)->first();

            if (!$user) {
                return back()->with('success', $genericMessage);
            }

            $token = Str::random(60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            $resetLink = route('password.reset', ['token' => $token, 'email' => $request->email]);
            Mail::to($user->email)->send(new PasswordResetMail($resetLink, $user->name));

            Log::info('Password reset link sent to: ' . $request->email);

            return back()->with('success', $genericMessage);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error sending reset link:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'حدث خطأ أثناء إرسال الرابط. يرجى المحاولة لاحقاً.');
        }
    }

    public function showResetPasswordForm($token, $email)
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            return redirect()->route('login2.page')->with('error', 'رابط إعادة التعيين غير صحيح أو انتهت صلاحيته');
        }

        if (now()->diffInSeconds($resetRecord->created_at) > 3600) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('login2.page')->with('error', 'انتهت صلاحية رابط إعادة التعيين. يرجى طلب رابط جديد');
        }

        return view('reset-password', ['token' => $token, 'email' => $email]);
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:user,email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'
            ], [
                'email.required' => 'البريد الإلكتروني مطلوب',
                'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
                'email.exists' => 'هذا البريد الإلكتروني غير موجود',
                'password.required' => 'كلمة المرور مطلوبة',
                'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
                'password.confirmed' => 'كلمات المرور غير متطابقة',
                'password.regex' => 'كلمة المرور يجب أن تحتوي على أحرف وأرقام على الأقل',
            ]);

            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
                return back()->with('error', 'رابط إعادة التعيين غير صحيح');
            }

            if (now()->diffInSeconds($resetRecord->created_at) > 3600) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();
                return back()->with('error', 'انتهت صلاحية رابط إعادة التعيين');
            }

            $user = UserModel::where('email', $request->email)->first();
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            Log::info('Password reset successfully for: ' . $request->email);

            return redirect()->route('login2.page')->with('success', 'تم إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error resetting password:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'حدث خطأ أثناء إعادة تعيين كلمة المرور');
        }
    }
}
