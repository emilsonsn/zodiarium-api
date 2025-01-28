<?php

namespace App\Services\Report;

use App\Models\Client;
use App\Models\Genereted;
use App\Models\Setting;
use Exception;
use App\Traits\DivineAPITrait;
use Illuminate\Support\Facades\Log;

class ReportService
{
    use DivineAPITrait;

    public function getGeneratedReports(){
        try{
            $genereteds = Genereted::with('client')
                ->orderBy('id', 'desc')
                ->get();

            return [
                'status' => true, 
                'data' => $genereteds
            ];

        }catch(Exception $error ){
            return [
                'status' => true,
                'data'   => $error->getMessage()
            ];
        }
    }

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

            Log::info("Relatórios: " . json_encode($reports));

            foreach($reports as $report){
                $data['report_code'] = $report;
                Log::info("Iniciando geração de relatório $report");
                Log::info('Data: ' . json_encode($data));
                $response = $this->getFinancialReport($data);                

                Log::info('Report response: ' . json_encode($response));

                if ($response['success'] !== 1) {
                    $error = json_encode($response);
                    throw new Exception("Erro na API ao gerar relatório. error: $error");
                }
            
                $reportUrl = $response['data']['report_url'];            
    
                $htmlPath = $this->callPythonScript($reportUrl);
                // $htmlPath = "/home/emilsonsn/desktop/Emilson/Projetos/2024/10 - Outubro/zodiarium/zodiarium-api/storage/app/public/pages/page_1738030391.html";                
    
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
            throw new Exception('Erro ao executar o script Python.');
        }

        return trim($output);
    }

    private function translateHtmlTextAndCreateFileTranslated(string $filePath): string
    {
        $html = file_get_contents($filePath);
        $translatedHtml = '';
        
        if (!$html) {
            throw new Exception('Erro ao ler o arquivo HTML.');
        }
    
        $html = mb_convert_encoding($html, 'UTF-8', 'auto');
    
        $preservedValues = [];
        $counter = 1;
    
        $protectedHtml = preg_replace_callback(
            '/(class|id|src|href|alt|title|style)="([^"]*)"/i',
            function ($matches) use (&$preservedValues, &$counter) {
                $placeholder = "{{preservado_$counter}}";
                $preservedValues[$placeholder] = $matches[2];
                $counter++;
                return $matches[1] . '="' . $placeholder . '"';
            },
            $html
        );
    
        $translator = new \Stichoza\GoogleTranslate\GoogleTranslate();
        $translator->setSource('en');
        $translator->setTarget('pt-PT');
        
        if (mb_strlen($protectedHtml) > 5000) {
            $chunks = str_split($protectedHtml, 5000);
            $translatedChunks = [];
            foreach ($chunks as $chunk) {
                $translatedChunks[] = $translator->translate($chunk);
            }
            $translatedHtml = implode('', $translatedChunks);
        } else {
            $translatedHtml = $translator->translate($protectedHtml);
            $translatedHtml = mb_convert_encoding($translatedHtml, 'UTF-8', 'auto');
        }

        foreach ($preservedValues as $placeholder => $originalValue) {
            $translatedHtml = str_replace($placeholder, $originalValue, $translatedHtml);
        }
    
        $finalHtml = str_replace('terceiro', 'o', $translatedHtml);
        $finalHtml = str_replace('fade', '', $finalHtml);
        $finalHtml = str_replace('folha de estilo', 'stylesheet', $finalHtml);
        $finalHtml = str_replace('<cabeça>', '<head>', $finalHtml);
        $finalHtml = str_replace('Stylesheet', 'stylesheet', $finalHtml);
        $finalHtml = str_replace('Apresentação', 'presentation', $finalHtml);
        $finalHtml = str_replace('apresentação', 'presentation', $finalHtml);
        $finalHtml = str_replace('aia-selected', 'aria-selected', $finalHtml);
        $finalHtml = str_replace('<ult', '<ul', $finalHtml);
        
        $newFilePath = str_replace('.html', '_translated.html', $filePath);
        file_put_contents($newFilePath, $finalHtml, LOCK_EX);
    
        return $newFilePath;
    }
}