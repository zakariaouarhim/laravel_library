<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index'); // Make sure this view exists or adjust accordingly
    }

    public function update(Request $request)
    {
        // Handle settings update logic here
        return back()->with('success', 'Settings updated successfully.');
    }
}