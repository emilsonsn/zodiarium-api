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
        $data = [
            "full_name" => "Emilson de Souza Nascimento",
            "day" => 18,
            "month" => 12,
            "year" => 2001,
            "hour" => 14,
            "min" => 30,
            "sec" => 15,
            "gender" => "male",
            "place" => "São Paulo, Brasil",
            "lat" => -23.5505,
            "lon" => -46.6333,
            "tzone" => -3.0,
            "company_name" => "Minha Empresa",
            "company_url" => "https://minhaempresa.com.br",
            "company_email" => "contato@minhaempresa.com.br",
            "company_mobile" => "5511912345678",
            "company_bio" => "Empresa dedicada ao desenvolvimento de soluções personalizadas para clientes em diversas áreas.",
            "logo_url" => "https://www.imagenspng.com.br/wp-content/uploads/2019/03/baby-shark-png-02-600x600.png",
            "footer_text" => "Copyright © 2024 Minha Empresa",
            "lan" => "en",
            "report_code" => "FINANCIAL-REPORT",
            "theme" => "010"
        ];

        $response = $this->getFinancialReport($data);
        if ($response['success'] !== 1) {
            $this->error('Erro ao gerar o relatório');
            return;
        }
    
        try {
            $reportUrl = $response['data']['report_url'];

            $htmlPath = $this->callPythonScript($reportUrl);

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
    
        $crawler = new Crawler($html);
    
        $translator = new \Stichoza\GoogleTranslate\GoogleTranslate('pt'); // Tradução para português
    
        // Iterar pelos nós do HTML
        $crawler->filter('*')->each(function (Crawler $node) use ($translator) {
            // Traduz apenas nós com texto e ignora tags vazias ou sem conteúdo
            if ($node->getNode(0) && trim($node->text()) !== '') {
                $text = $node->getNode(0)->nodeValue;
    
                // Escapa caracteres especiais como "&"
                $text = str_replace('&', 'E', $text);
                // $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5);
    
                if (mb_strlen($text) > 5000) { // Limite aproximado para a API do Google
                    $chunks = str_split($text, 5000); // Divide o texto em partes menores
                    $translatedText = implode(' ', array_map(fn($chunk) => $translator->translate($chunk), $chunks));
                } else {
                    $translatedText = $translator->translate($text);
                }
    
                // Decodifica novamente o texto traduzido e mantém o conteúdo no HTML
                $node->getNode(0)->nodeValue = htmlspecialchars_decode($translatedText, ENT_QUOTES | ENT_HTML5);
            }
        });
    
        // Mantém a estrutura original e apenas substitui os textos traduzidos
        $translatedHtml = $crawler->outerHtml();
    
        $newFilePath = str_replace('.html', '_translated.html', $filePath);
        file_put_contents($newFilePath, $translatedHtml);
    
        return $newFilePath;
    }
    
}
