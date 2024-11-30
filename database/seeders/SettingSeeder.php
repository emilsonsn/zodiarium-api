<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $setting = [
            'company_name' => 'Zodiarium',
            'company_url' => 'https://zodiarium.com.br',
            'company_email' => 'zodiarium@gmail.com',
            'company_phone' => '5583991236636',
            'company_bio' => 'Algum texto',
            'theme' => '010',
            'logo' => null,
            'footer_text' => null,
            'api_key' => '5c1634ff878b14073aeefddca74e2746',
            'bearer_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FzdHJvYXBpLTEuZGl2aW5lYXBpLmNvbS9hcGkvYXV0aC1hcGktdXNlciIsImlhdCI6MTczMjk4MDg1MCwibmJmIjoxNzMyOTgwODUwLCJqdGkiOiJ2dnNoYVNCQnRDbDVCMVc3Iiwic3ViIjoiMjk5MyIsInBydiI6ImU2ZTY0YmIwYjYxMjZkNzNjNmI5N2FmYzNiNDY0ZDk4NWY0NmM5ZDcifQ.kkg3hVXHyIOdB88Tw6IBHWsQyULL1C45W5nLjetLQrA',
        ];
        Setting::firstOrCreate($setting, $setting);
    }
}
