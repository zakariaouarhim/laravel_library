<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description'
    ];

    private const CACHE_KEY = 'system_settings_all';
    private const CACHE_TTL = 3600; // 1 hour

    public static function getSetting($key, $default = null)
    {
        $settings = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::pluck('setting_value', 'setting_key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    public static function setSetting($key, $value, $description = null)
    {
        $result = self::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'description' => $description
            ]
        );

        // Bust cache so next read picks up the new value
        Cache::forget(self::CACHE_KEY);

        return $result;
    }

    /**
     * Calculate shipping cost based on subtotal and store settings.
     *
     * @return array{shipping: float, freeThreshold: float}
     */
    public static function calculateShipping(float $subtotal): array
    {
        $shipping = (float) self::getSetting('shipping_cost', 25.00);
        $freeThreshold = (float) self::getSetting('free_shipping_threshold', 0);

        if ($freeThreshold > 0 && $subtotal >= $freeThreshold) {
            $shipping = 0;
        }

        return compact('shipping', 'freeThreshold');
    }
}
