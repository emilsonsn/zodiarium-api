<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;

trait EupagoTrait
{
    protected $client;
    protected $apiKey;

    public function prepareEupagoApiCredencials()
    {
        $this->client = new Client([
            'base_uri' => env('EUPAGO_BASE_URL'),
            'timeout'  => 10.0,
        ]);

        $this->apiKey = env('EUPAGO_API_KEY');
    }

    public function createMultibancoReference($id, $amount, $additionalParams = [])
    {
        $this->prepareEupagoApiCredencials();
        $params = array_merge([
            'chave' => $this->apiKey,
            'valor' => $amount,
            'id'    => $id,
        ], $additionalParams);

        $response = $this->client->post('/clientes/rest_api/multibanco/create', [
            'form_params' => $params,
        ]);

        $result = json_decode($response->getBody(), true);

        if(!isset($body['sucesso']) || !$body['sucesso']){
            throw new Exception($result['resposta'], 400);
        }

        return $result;
    }

    public function createMbWayPayment($id, $amount, $countryCode = '+351', $phoneNumber)
    {
        $this->prepareEupagoApiCredencials();
        $response = $this->client->post('/api/v1.02/mbway/create', [
            'headers' => [
                'ApiKey' => $this->apiKey,
            ],
            'form_params' => [
                'payment' => [
                    'identifier' => $id,
                    'amount' => [
                        'value' => $amount,
                        'currency' => 'EUR'
                    ],
                    'customerPhone' => $phoneNumber,
                    'countryCode' => $countryCode
                ]                
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if(!isset($body['sucesso']) || !$body['sucesso']){
            throw new Exception($result['resposta'], 400);
        }

        return $result;

    }

    public function checkPaymentStatus($reference, $entity)
    {        
        $response = $this->client->post('/clientes/rest_api/multibanco/info', [
            'form_params' => [
                'chave'      => $this->apiKey,
                'referencia' => $reference,
                'entidade' => $entity,
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if(!isset($body['sucesso']) || !$body['sucesso']){
            throw new Exception($result['resposta'], 400);
        }

        return $result;
    }
}
