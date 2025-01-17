<?php

namespace App\Services\Routine;

use App\Enums\BrevoListEnum;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Mail\ClientReportMail;
use App\Models\Genereted;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Report\ReportService;
use App\Traits\BrevoTrait;
use App\Traits\EupagoTrait;
use App\Traits\StripeTrait;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RoutineService
{
    use EupagoTrait, StripeTrait, BrevoTrait;

    private $reportService; 

    public function __construct(ReportService $reportService){
        $this->reportService = $reportService;
    }

    public function checkPayments(){
        $payments = Payment::where('status', PaymentStatus::Pending->value)
            ->get();

        foreach($payments as $payment){
            try{
                switch($payment->origin_api){
                    case 'Eupago':
                        $response = $this->checkPaymentStatus($payment->reference, $payment->entity);
                        if (($response['estado_referencia'] ?? null) === 'paga') {
                            $this->successfulPaymentProcess($payment);
                        }
                        break;
                    case 'Stripe':
                        $response = $this->getCheckoutSession($payment->reference);
                        if (($response->payment_status ?? null) === 'paid'){
                            $this->successfulPaymentProcess($payment);
                        }
                        break;
                    case 'Zodiarium':
                        $this->successfulPaymentProcess($payment);
                    default:
                        break;
                }
            }catch(Exception $error){
                Log::error($error->getMessage());
                continue;
            }            
        }
    }

    public function successfulPaymentProcess(Payment $payment){
        $customer = $payment->sale->client;

        $sale = Sale::find($payment->sale_id)->update([
            'status' => SaleStatus::Finished->value
        ]);

        $payment->update([
            'status' => PaymentStatus::Successful->value
        ]);

        $report_ids = collect($payment->sale->products)
            ->map(fn($saleProduct) => $saleProduct->product->report)
            ->toArray();

        $this->addContactInList(BrevoListEnum::Client->value, $customer);

        $reports = $this->reportService->generateReport($customer['id'], $report_ids);

        if(!isset($reports['status']) || !$reports['status']){
            $error = json_encode($reports);
            Log::error("Erro ao gerar relatórios para o cliente {$customer->name}");
            Log::error($error);
            return;
        }

        $reports = array_map(function($report) {
            return str_replace('/app/public', '', $report);
        }, $reports['data']);

        $reportsData = [];        
        foreach($payment->sale->products as $indice => $saleProduct){
            $product = Product::find($saleProduct->product_id)->first();
            $reportsData[] = [
                'image' => $product->image,
                'title' => $product->title,
                'url'   => $reports[$indice]
            ];
        }

        foreach($reportsData as $report){
            Genereted::create([
                'client_id' => $customer->id,
                'path' => $report['url']
            ]);
        }

        Mail::to($customer->email)->send(new ClientReportMail($customer->name, $reportsData));
    }
}