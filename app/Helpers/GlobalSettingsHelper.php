<?php
namespace App\Helpers;

use App\Models\GlobalSetting;
use Illuminate\Support\Facades\DB;

class GlobalSettingsHelper
{
    public static function get($key, $default = null)
    {
        $setting = GlobalSetting::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }
}
