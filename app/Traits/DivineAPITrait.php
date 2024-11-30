<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;

trait DivineAPITrait
{
    protected $apiKey;
    protected $bearerToken;
    protected $baseUrl;

    public function prepareDivineAPICredencials(){
        $this->apiKey = '5c1634ff878b14073aeefddca74e2746';
        $this->bearerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FzdHJvYXBpLTEuZGl2aW5lYXBpLmNvbS9hcGkvYXV0aC1hcGktdXNlciIsImlhdCI6MTczMjk4MDg1MCwibmJmIjoxNzMyOTgwODUwLCJqdGkiOiJ2dnNoYVNCQnRDbDVCMVc3Iiwic3ViIjoiMjk5MyIsInBydiI6ImU2ZTY0YmIwYjYxMjZkNzNjNmI5N2FmYzNiNDY0ZDk4NWY0NmM5ZDcifQ.kkg3hVXHyIOdB88Tw6IBHWsQyULL1C45W5nLjetLQrA';
        $this->baseUrl = 'https://pdf.divineapi.com/astrology/v1/report';
    }

    public function getFinancialReport($data)
    {
        try {
            $this->prepareDivineAPICredencials();
            $client = new Client();

            $response = $client->post($this->baseUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => array_merge($data, [
                    'api_key' => $this->apiKey,
                ]),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
