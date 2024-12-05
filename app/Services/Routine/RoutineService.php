<?php

namespace App\Services\Routine;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Payment;
use App\Models\Sale;
use App\Traits\EupagoTrait;
use App\Traits\StripeTrait;
use Exception;
use Illuminate\Support\Facades\Log;

class RoutineService
{
    use EupagoTrait, StripeTrait;

    public function checkPayments(){
        $payments = Payment::where('status', PaymentStatus::Pending->value)
            ->get();

        foreach($payments as $payment){
            try{
                switch($payment->origin_api){
                    case 'Eupago':
                        $response = $this->checkPaymentStatus($payment->reference, $payment->entity);
                        if (($response['estado_referencia'] ?? null) === 'pago') {
                            $this->successfulPaymentProcess($payment);
                        }
                        break;
                    case 'Stripe':
                        $response = $this->getCheckoutSession($payment->reference);
                        // payment_status: Status do pagamento (ex.: paid, unpaid, no_payment_required).
                        if (($response->payment_status ?? null) !== 'paid'){
                            $this->successfulPaymentProcess($payment);
                        }
                        break;
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
        $payment->update([
            'status' => PaymentStatus::Successful->value
        ]);

        Sale::find($payment->sale_id)->update([
            'status' => SaleStatus::Finished->value
        ]);

        $customer = $payment->sale->client;
        // Jogar na lista de compradores da brevo
    }
}