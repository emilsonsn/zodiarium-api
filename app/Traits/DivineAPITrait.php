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
        // Api Key Prod:
        $this->apiKey = '49f8c64822e567df1650eb82f3d09f56';

        // Api Key Test:
        // $this->apiKey = '5c1634ff878b14073aeefddca74e2746';
        
        $this->baseUrl = 'https://pdf.divineapi.com/astrology/v1/report';        
        $this->bearerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FzdHJvYXBpLTEuZGl2aW5lYXBpLmNvbS9hcGkvYXV0aC1hcGktdXNlciIsImlhdCI6MTczMjk4MDg1MCwibmJmIjoxNzMyOTgwODUwLCJqdGkiOiJ2dnNoYVNCQnRDbDVCMVc3Iiwic3ViIjoiMjk5MyIsInBydiI6ImU2ZTY0YmIwYjYxMjZkNzNjNmI5N2FmYzNiNDY0ZDk4NWY0NmM5ZDcifQ.kkg3hVXHyIOdB88Tw6IBHWsQyULL1C45W5nLjetLQrA';        

        $this->apiKeyWesternApi = '49d437de9f731901a8172943edd6b74d';
        $this->baseUrlWesternApi = 'https://astroapi-4.divineapi.com/western-api/v1';
        $this->bearerTokenWesternApi = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FzdHJvYXBpLTEuZGl2aW5lYXBpLmNvbS9hcGkvYXV0aC1hcGktdXNlciIsImlhdCI6MTczNDAzNjMwMiwibmJmIjoxNzM0MDM2MzAyLCJqdGkiOiJrajJUTUwySkEzQXk0TGhtIiwic3ViIjoiMzA3NCIsInBydiI6ImU2ZTY0YmIwYjYxMjZkNzNjNmI5N2FmYzNiNDY0ZDk4NWY0NmM5ZDcifQ.jnLkGrZCDYJfRrZvmb0syXu9JIq0HXE8Ve35FyXiyiU';
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

    public function getPlanetText($data, $planet)
    {
        try {
            $this->prepareDivineAPICredencials();
            $client = new Client();

            $response = $client->post("$this->baseUrlWesternApi/general-house-report/$planet", [
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
