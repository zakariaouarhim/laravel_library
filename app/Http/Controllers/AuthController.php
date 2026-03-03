<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;

class AuthController extends Controller
{
    public function adduser(Request $request)
    {
        try {
            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:user,email',
                'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/',
            ], [
                'password.regex' => 'كلمة المرور يجب أن تحتوي على أحرف وأرقام على الأقل',
            ]);

            $user = UserModel::create([
                'name' => $validateData['name'],
                'email' => $validateData['email'],
                'password' => Hash::make($validateData['password']),
                'role' => 'user'
            ]);

            if ($user) {
                Auth::login($user);

                session([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'is_logged_in' => true
                ]);

                try {
                    Mail::to($user->email)->send(new WelcomeMail($user));
                } catch (\Exception $e) {
                    Log::error('Failed to send welcome email:', ['error' => $e->getMessage()]);
                }

                return redirect()->route('index.page')->with('success', 'Account created successfully! Welcome ' . $user->name);
            } else {
                throw new \Exception('Failed to create user');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in user creation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء التسجيل، يرجى المحاولة لاحقاً.')
                ->withInput();
        }
    }

    public function userlogin(Request $requestlogin)
    {
        try {
            $validatedData = $requestlogin->validate([
                'email' => 'required|email',
                'password' => 'required|min:8',
            ]);

            $credentials = [
                'email' => $validatedData['email'],
                'password' => $validatedData['password']
            ];

            if (Auth::attempt($credentials, $requestlogin->has('remember'))) {
                $user = Auth::user();

                session([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'user_avatar' => $user->avatar,
                    'is_logged_in' => true,
                    'user_updated_at' => $user->created_at->locale('ar')->translatedFormat('F Y')
                ]);

                if (in_array($user->role, ['admin', 'super_admin'])) {
                    return redirect()->route('admin.Dashbord_Admin.dashboard')->with('success', 'Login successful');
                } else {
                    return redirect()->route('index.page')->with('success', 'Login successful! Welcome back ' . $user->name);
                }

            } else {
                return back()->with('fail', 'Invalid email or password')->withInput();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Login error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('fail', 'An error occurred during login. Please try again.');
        }
    }

    public function logout()
    {
        Auth::logout();
        session()->flush();
        return redirect()->route('index.page')->with('success', 'Logged out successfully');
    }

    public function showLogin2()
    {
        return view('login2');
    }

    public function logoutRedirect()
    {
        return redirect()->route('index.page');
    }
}
