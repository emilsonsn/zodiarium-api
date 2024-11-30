<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Dompdf\Dompdf;

class Teste2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:teste2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PDF from HTML file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Caminho do arquivo HTML
        $htmlFilePath = base_path('output/teste.html');

        // Verifica se o arquivo existe
        if (!file_exists($htmlFilePath)) {
            $this->error("Arquivo HTML não encontrado em: $htmlFilePath");
            return;
        }

        // Lê o conteúdo do arquivo HTML
        $htmlContent = file_get_contents($htmlFilePath);

        // Instancia o Dompdf
        $dompdf = new Dompdf();
        $dompdf->loadHtml($htmlContent);

        // Renderiza o PDF
        $dompdf->setPaper('A4', 'portrait'); // Define o tamanho do papel e orientação
        $dompdf->render();

        // Caminho para salvar o PDF
        $pdfFilePath = base_path('Python/teste.pdf');

        // Salva o PDF gerado
        file_put_contents($pdfFilePath, $dompdf->output());

        $this->info("PDF gerado com sucesso em: $pdfFilePath");
    }
}
