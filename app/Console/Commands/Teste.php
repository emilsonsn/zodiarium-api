<?php

namespace App\Console\Commands;

use App\Traits\DivineAPITrait;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Dompdf\Dompdf;

class Teste extends Command
{
    use DivineAPITrait;
    
    protected $signature = 'app:teste';
    protected $description = 'Teste de criação de PDF com conteúdo HTML';

    public function __construct()
    {
        parent::__construct(); // Chamando o construtor da classe pai
    }

    public function handle()
    {
        // $data = [
        //     "full_name" => "Emilson de Souza Nascimento",
        //     "day" => 18,
        //     "month" => 12,
        //     "year" => 2001,
        //     "hour" => 14,
        //     "min" => 30,
        //     "sec" => 15,
        //     "gender" => "male",
        //     "place" => "São Paulo, Brasil",
        //     "lat" => -23.5505,
        //     "lon" => -46.6333,
        //     "tzone" => -3.0,
        //     "company_name" => "Minha Empresa",
        //     "company_url" => "https://minhaempresa.com.br",
        //     "company_email" => "contato@minhaempresa.com.br",
        //     "company_mobile" => "5511912345678",
        //     "company_bio" => "Empresa dedicada ao desenvolvimento de soluções personalizadas para clientes em diversas áreas.",
        //     "logo_url" => "https://www.imagenspng.com.br/wp-content/uploads/2019/03/baby-shark-png-02-600x600.png",
        //     "footer_text" => "Copyright © 2024 Minha Empresa",
        //     "lan" => "en",
        //     "report_code" => "FINANCIAL-REPORT",
        //     "theme" => "010"
        // ];

        // $response = $this->getFinancialReport($data);
        // if ($response['success'] !== 1) {
        //     $this->error('Erro ao gerar o relatório');
        //     return;
        // }
    
        try {
            // $reportUrl = $response['data']['report_url'];            

            // $htmlPath = $this->callPythonScript($reportUrl);
            $htmlPath = "/home/emilsonsn/desktop/Emilson/Projetos/10 - Outubro/zodiarium/zodiarium-api/storage/app/public/pages/page_1732991362.html";

            $translatedHtmlPath = $this->translateHtmlTextAndCreateFileTranslated($htmlPath);
    
            $this->info("Arquivo traduzido gerado em: {$translatedHtmlPath}");
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->error($e->getMessage());
        }
    }

    private function callPythonScript(string $url): string
    {
        $pythonScript = base_path('Python/pageScraping.py'); // Caminho para o script Python
        $command = escapeshellcmd("python3 '{$pythonScript}' '{$url}'");
        $output = shell_exec($command);

        if (!$output) {
            throw new \Exception('Erro ao executar o script Python.');
        }

        return trim($output); // Retorna o caminho do arquivo gerado
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
                        // Protege outros valores
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
        
        // Restaurar os valores protegidos dos atributos
        $finalHtml = preg_replace_callback(
            '/(class|id|src|href|alt|title|styles)="([^"]*)"/i',
            function ($attributeMatches) use (&$preservedValues) {
                $decodedValue = base64_decode($attributeMatches[2], true);
                if ($decodedValue !== false) {
                    return $attributeMatches[1] . '="' . $decodedValue . '"';
                }
                // Restaurar valores preservados
                if (array_key_exists($attributeMatches[2], $preservedValues)) {
                    return $attributeMatches[1] . '="' . $preservedValues[$attributeMatches[2]] . '"';
                }
                return $attributeMatches[0];
            },
            $translatedHtml
        );

        $finalHtml = str_replace('terceiro', 'o', $finalHtml);
        $finalHtml = str_replace('fade', '', $finalHtml);
            
        $newFilePath = str_replace('.html', '_translated.html', $filePath);
        file_put_contents($newFilePath, $finalHtml);
        
        return $newFilePath;
    }
}
