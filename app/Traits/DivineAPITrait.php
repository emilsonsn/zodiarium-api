<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;

trait DivineAPITrait
{
    protected $apiKey;
    protected $bearerToken;
    protected $baseUrl;

    protected $apiKeyWesternApi;
    protected $bearerTokenWesternApi;
    protected $baseUrlWesternApi;

    public function prepareDivineAPICredencials(){
        $this->apiKey = '5c1634ff878b14073aeefddca74e2746';
        $this->baseUrl = 'https://pdf.divineapi.com/astrology/v1/report';
        $this->bearerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FzdHJvYXBpLTEuZGl2aW5lYXBpLmNvbS9hcGkvYXV0aC1hcGktdXNlciIsImlhdCI6MTczMjk4MDg1MCwibmJmIjoxNzMyOTgwODUwLCJqdGkiOiJ2dnNoYVNCQnRDbDVCMVc3Iiwic3ViIjoiMjk5MyIsInBydiI6ImU2ZTY0YmIwYjYxMjZkNzNjNmI5N2FmYzNiNDY0ZDk4NWY0NmM5ZDcifQ.kkg3hVXHyIOdB88Tw6IBHWsQyULL1C45W5nLjetLQrA';

        $this->apiKeyWesternApi = 'df59876584a2812f45269920d13a5292';
        $this->baseUrlWesternApi = 'https://astroapi-4.divineapi.com/western-api/v1';
        $this->bearerTokenWesternApi = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FzdHJvYXBpLTEuZGl2aW5lYXBpLmNvbS9hcGkvYXV0aC1hcGktdXNlciIsImlhdCI6MTczMjc4ODE0MCwibmJmIjoxNzMyNzg4MTQwLCJqdGkiOiJ4VzNQeE9nck1qdERXN2ZRIiwic3ViIjoiMzAzNCIsInBydiI6ImU2ZTY0YmIwYjYxMjZkNzNjNmI5N2FmYzNiNDY0ZDk4NWY0NmM5ZDcifQ.FPN5fFU1BMDfXKDpKeV35A1_y2Ph446vmck2iR9nq0M';
    }

    public function getNatalChart($data)
    {
        try {
            $this->prepareDivineAPICredencials();
            $client = new Client();

            $response = $client->post("$this->baseUrlWesternApi/natal-wheel-chart", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerTokenWesternApi,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => array_merge($data, [
                    'api_key' => $this->apiKeyWesternApi,
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
