<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Usercontroller extends Controller
{
    function adduser(Request $request){
        try {
            // Log the incoming request
            \Log::info('Form submitted with data:', $request->all());

            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:user,email',
                'password' => 'required|min:8|confirmed',
            ]);
            
            \Log::info('Data passed validation');

            // Create user using the UserModel
            $user = UserModel::create([
                'name' => $validateData['name'],
                'email' => $validateData['email'],
                'password' => Hash::make($validateData['password']),
                'role' => 'user'
            ]);
            
            \Log::info('User created successfully:', ['user_id' => $user->id]);

            if ($user) {
                // Login the user using Laravel's Auth system
                Auth::login($user);
                
                // Also store in session for backward compatibility (if needed)
                session([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'is_logged_in' => true
                ]);
                
                return redirect()->route('index.page')->with('success', 'Account created successfully! Welcome ' . $user->name);
            } else {
                throw new \Exception('Failed to create user');
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', ['errors' => $e->errors()]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error in user creation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Registration failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    function userlogin(Request $requestlogin){
        try {
            // Validate the request data
            $validatedData = $requestlogin->validate([
                'email' => 'required|email',
                'password' => 'required|min:8',
            ]);

            // Attempt to authenticate using Laravel's Auth system
            $credentials = [
                'email' => $validatedData['email'],
                'password' => $validatedData['password']
            ];

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                
                // Store user info in session for backward compatibility
                session([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'is_logged_in' => true
                ]);

                // Redirect based on role
                if ($user->role == "admin") {
                    return redirect()->route('Dashbord_Admin.dashboard')->with('success', 'Login successful');
                } else {
                    return redirect()->route('index.page')->with('success', 'Login successful! Welcome back ' . $user->name);
                }
                
            } else {
                // Invalid credentials
                return back()->with('fail', 'Invalid email or password')->withInput();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // Log the error and return a generic error message
            Log::error('Login error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('fail', 'An error occurred during login. Please try again.');
        }
    }

    // Add logout function
    function logout(){
        Auth::logout(); // Logout from Laravel's Auth system
        session()->flush(); // Clear all session data
        return redirect()->route('index.page')->with('success', 'Logged out successfully');
    }

    public function index()
    {
        return view('Dashbord_Admin.client');
    }
}