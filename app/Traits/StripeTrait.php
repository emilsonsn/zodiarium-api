<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;
use Stripe\Stripe;
use Stripe\Checkout\Session;

trait StripeTrait
{
    protected $clientKey;
    protected $privateKey;

    public function prepareStripeApiCredencials()
    {
        $this->privateKey = env('STRIPE_PRIVATE_KEY');
    }

    public function createStripePayment($amount)
    {
        $this->prepareStripeApiCredencials();
        Stripe::setApiKey($this->privateKey);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Zodiarium - Relatórios',
                    ],
                    'unit_amount' => $amount * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url('/stripe/success'),
            'cancel_url' => url('/stripe/cancel'),
        ]);

        $contentBody = response()
            ->json(['id' => $session->id])
            ->content();
        
        $response = json_decode($contentBody);

        if(!isset($response->id)) throw new Exception('Erro na transação');

        return ['id' => $response->id, 'valor' => $amount];
    }

    public function getCheckoutSession($sessionId)
    {
        // payment_status: Status do pagamento (ex.: paid, unpaid, no_payment_required).
        
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $session = Session::retrieve($sessionId);

        return response()->json($session);
    }
}
