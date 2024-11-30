<?php

namespace App\Services\Setting;

use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingService
{
    public function search()
    {
        try {
            $settings = Setting::first();            

            return $settings;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

   public function update($request)
    {
        try {
            $rules = [
                'company_name' => ['required', 'string', 'max:255'],
                'company_url' => ['nullable', 'string', 'max:255'],
                'company_email' => ['nullable', 'string', 'max:255'],
                'company_phone' => ['nullable', 'string', 'max:255'],
                'company_bio' => ['nullable', 'string'],
                'theme' => ['nullable', 'string', 'max:255'],
                'logo' => ['nullable', 'file', 'image', 'max:1024'], 
                'footer_text' => ['required', 'string', 'max:255'],
                'api_key' => ['required', 'string'],
                'bearer_token' => ['required', 'string'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                throw new Exception($validator->errors());
            }

            $settingToUpdate = Setting::first();

            if (!$settingToUpdate) {
                throw new Exception('Configuração não encontrada');
            }

            $validatedData = $validator->validated();

            if ($request->hasFile('logo')) {
                // Apaga a imagem anterior
                if ($settingToUpdate->logo && Storage::exists('public/settings/' . $settingToUpdate->logo)) {
                    Storage::delete('public/settings/' . $settingToUpdate->logo);
                }

                $logoPath = $request->file('logo')->store('public/settings');
                $validatedData['logo'] = str_replace('public/settings/', '', $logoPath);
            }

            $settingToUpdate->update($validatedData);

            return ['status' => true, 'data' => $settingToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }


}
