<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('money')) {
    function money($amount): string
    {
        $symbol = Setting::get('currency_symbol', '₹');

        return $symbol.number_format((float) $amount, 2);
    }
}

if (! function_exists('app_installed')) {
    function app_installed(): bool
    {
        $lock = storage_path('installed');
        clearstatcache(true, $lock);

        return file_exists($lock);
    }
}
