<?php

namespace App\Services\Report;

use App\Models\Client;
use App\Models\Setting;
use Exception;
use App\Traits\DivineAPITrait;

class ReportService
{
    use DivineAPITrait;

    public function generateReport($id, $reports){
        try{
            $setting = Setting::first();
            $client = Client::find($id);
    
            $data = [
                "full_name" => $client->name,
                "day" => $client->day_birth,
                "month" => $client->month_birth,
                "year" => $client->year_birth,
                "hour" => $client->hour_birth,
                "min" => $client->minute_birth,
                "sec" => 0,
                "gender" => $client->gender,
                "place" => $client->address,
                "lat" => -23.5505,
                "lon" => -46.6333,
                "tzone" => 0,
                "company_name" => $setting->company_name ?? '',
                "company_url" => $setting->company_url ?? '',
                "company_email" => $setting->company_email ?? '',
                "company_mobile" => $setting->company_phone ?? '',
                "company_bio" => $setting->company_bio ?? '',
                "logo_url" => $setting->logo,
                "footer_text" => $setting->footer_text,
                "lan" => "en",
                "theme" => '010',
            ];
            $generatedReports = [];

            foreach($reports as $report){
                $data['report_code'] = $report;
                // Retirar
                // $data['report_code'] = 'FINANCIAL-REPORT';
                // $data['logo_url'] = 'https://zodiarium.com:3000/storage/settings/i3C0gY6q1R1kXPGILhDPhDSFHxrf3ZwVGcRs24Sw.png';
                //
                $response = $this->getFinancialReport($data);

                if ($response['success'] !== 1) throw new Exception('Erro na API ao gerar relatório');                
            
                $reportUrl = $response['data']['report_url'];            
    
                $htmlPath = $this->callPythonScript($reportUrl);
                // $htmlPath = "/home/emilsonsn/desktop/Emilson/Projetos/10 - Outubro/zodiarium/zodiarium-api/storage/app/public/pages/page_1732991362.html";
    
                $generatedReports[] = $this->translateHtmlTextAndCreateFileTranslated($htmlPath);
            }

            return ['status' => true, 'data' => $generatedReports];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function callPythonScript(string $url): string
    {
        $pythonScript = base_path('Python/pageScraping.py');
        $command = escapeshellcmd("python3 '{$pythonScript}' '{$url}'");
        $output = shell_exec($command);

        if (!$output) {
            throw new \Exception('Erro ao executar o script Python.');
        }

        return trim($output);
    }

    private function translateHtmlTextAndCreateFileTranslated(string $filePath): string
    {
        $html = file_get_contents($filePath);
        
        if (!$html) {
            throw new \Exception('Erro ao ler o arquivo HTML.');
        }

        $preservedValues = [];
        $protectedHtml = preg_replace_callback(
            '/<([^>]+)>/',
            function ($matches) use (&$preservedValues) {
                return preg_replace_callback(
                    '/(class|id|src|href|alt|title|styles)="([^"]*)"/i',
                    function ($attributeMatches) use (&$preservedValues) {
                        if (strpos($attributeMatches[2], 'data:image/svg+xml;base64,') === 0) {
                            $key = 'PRESERVED_' . count($preservedValues);
                            $preservedValues[$key] = $attributeMatches[2];
                            return $attributeMatches[1] . '="' . $key . '"';
                        }

                        return $attributeMatches[1] . '="' . base64_encode($attributeMatches[2]) . '"';
                    },
                    $matches[0]
                );
            },
            $html
        );
        
        $translator = new \Stichoza\GoogleTranslate\GoogleTranslate('pt');
        
        if (mb_strlen($protectedHtml) > 5000) {
            $chunks = str_split($protectedHtml, 5000);
            $translatedChunks = [];
            foreach ($chunks as $chunk) {
                $translatedChunks[] = $translator->translate($chunk);
            }
            $translatedHtml = implode('', $translatedChunks);
        } else {
            $translatedHtml = $translator->translate($protectedHtml);
        }
        
        $finalHtml = preg_replace_callback(
            '/(class|id|src|href|alt|title|styles)="([^"]*)"/i',
            function ($attributeMatches) use (&$preservedValues) {
                $decodedValue = base64_decode($attributeMatches[2], true);
                if ($decodedValue !== false) {
                    return $attributeMatches[1] . '="' . $decodedValue . '"';
                }

                if (array_key_exists($attributeMatches[2], $preservedValues)) {
                    return $attributeMatches[1] . '="' . $preservedValues[$attributeMatches[2]] . '"';
                }
                return $attributeMatches[0];
            },
            $translatedHtml
        );

        $finalHtml = str_replace('terceiro', 'o', $finalHtml);
        $finalHtml = str_replace('fade', '', $finalHtml);
        $finalHtml = str_replace('folha de estilo', 'stylesheet', $finalHtml);
        $finalHtml = str_replace('<cabeça>', '<head>', $finalHtml);
        
        $newFilePath = str_replace('.html', '_translated.html', $filePath);
        file_put_contents($newFilePath, $finalHtml);
        
        return $newFilePath;
    }
}