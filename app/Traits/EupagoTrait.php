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

        if(!isset($result['sucesso']) || !$result['sucesso']){
            throw new Exception($result['resposta'], 400);
        }

        return $result;
    }

    public function createMbWayPayment($id, $amount, $countryCode = '+351', $phoneNumber)
    {
        $this->prepareEupagoApiCredencials();

        $response = $this->client->post('/clientes/rest_api/mbway/create', [
            'json' => [
                'valor' => $amount,
                'id' => $id,
                'alias' => $phoneNumber,
                'chave' => $this->apiKey,
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (!isset($result['sucesso']) || !$result['sucesso']) {
            throw new Exception($result['resposta'], 400);
        }

        return $result;
        return  [
            'success' => true,
            'estado' => 0,
            'response' => '234234',
            'referencia' => '1234234',
            'valor' => 12,
            'alias' => '123',
        ];
    }

    public function checkPaymentStatus($reference, $entity = null)
    {        
        $this->prepareEupagoApiCredencials();
        $payload = [
            'chave'      => $this->apiKey,
            'referencia' => $reference,            
        ];

        if($entity) $payload['entidade'] = $entity;

        $response = $this->client->post('/clientes/rest_api/multibanco/info', [
            'form_params' => $payload
        ]);

        $result = json_decode($response->getBody(), true);

        if(!isset($result['sucesso']) || !$result['sucesso']){
            throw new Exception($result['resposta'], 400);
        }

        return $result;
    }
}
