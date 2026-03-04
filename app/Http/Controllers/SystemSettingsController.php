<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;

class SystemSettingsController extends Controller
{
    private const DEFAULTS = [
        'store_name'              => 'مكتبة الفقراء',
        'store_phone'             => '',
        'store_email'             => '',
        'store_address'           => '',
        'shipping_cost'           => '25.00',
        'free_shipping_threshold' => '0',
        'facebook_url'            => '',
        'instagram_url'           => '',
        'twitter_url'             => '',
        'min_order_amount'        => '0',
        'max_quantity_per_item'   => '10',
    ];

    public function index()
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = SystemSetting::getSetting($key, $default);
        }

        return view('Dashbord_Admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name'              => 'required|string|max:255',
            'store_phone'             => 'nullable|string|max:20',
            'store_email'             => 'nullable|email|max:255',
            'store_address'           => 'nullable|string|max:500',
            'shipping_cost'           => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'facebook_url'            => 'nullable|url|max:500',
            'instagram_url'           => 'nullable|url|max:500',
            'twitter_url'             => 'nullable|url|max:500',
            'min_order_amount'        => 'nullable|numeric|min:0',
            'max_quantity_per_item'   => 'nullable|integer|min:1|max:100',
        ]);

        foreach (self::DEFAULTS as $key => $default) {
            SystemSetting::setSetting($key, $request->input($key, $default));
        }

        return back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }
}
