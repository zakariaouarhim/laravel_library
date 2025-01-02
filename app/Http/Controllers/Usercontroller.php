<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


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

            // Test database connection
            try {
                \DB::connection()->getPdo();
                \Log::info('Database connected successfully');
            } catch (\Exception $e) {
                \Log::error('Database connection failed:', ['error' => $e->getMessage()]);
                throw $e;
            }

            $userData = [
                'name' => $validateData['name'],
                'email' => $validateData['email'],
                'password' => bcrypt($validateData['password']),
                'role' => 'user'
            ];
            
            \Log::info('Attempting to create user with data:', $userData);
            
            // Try manual insert to bypass model
            $inserted = \DB::table('user')->insert($userData);
            \Log::info('Direct DB insert result:', ['success' => $inserted]);

            if ($inserted) {
                return redirect()->back()->with('success', 'User registered successfully');
            } else {
                throw new \Exception('Failed to insert user data');
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
            'email' => 'required|email|exists:user,email', // Check if email exists in the 'user' table
            'password' => 'required|min:8',
        ]);

        // Fetch the user from the database
        $user = DB::table('user')
            ->where('email', $validatedData['email'])
            ->first();

        // Check if the user exists and the password is correct
        if ($user && Hash::check($validatedData['password'], $user->password)) {
            // Log the user in (you can use Laravel's Auth system here)
            // For example: Auth::loginUsingId($user->id);

            // Redirect to the home page or dashboard
            return redirect()->route('index.page')->with('success', 'Login successful');
        } else {
            // Invalid credentials
            return back()->with('fail', 'Invalid email or password');
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
   
}