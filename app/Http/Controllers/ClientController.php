<?php

namespace App\Http\Controllers;

use App\Services\Client\ClientService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private $clientService;

    public function __construct(ClientService $clientService) {
        $this->clientService = $clientService;
    }

    public function search(Request $request){
        $result = $this->clientService->search($request);

        return $result;
    }

    public function export(Request $request){
        return $this->clientService->export($request);
    }

    public function getClientZodiacSing(Request $request){
        $result = $this->clientService->getClientZodiacSing($request);

        if($result['status']) $result['message'] = "Signo consultado com sucesso";
        return $this->response($result);
    }

    public function getCitys(Request $request){
        $result = $this->clientService->getCitys($request);

        if($result['status']) $result['message'] = "Cidades consultadas com sucesso";
        return $this->response($result);
    }

    public function create(Request $request){
        $result = $this->clientService->create($request);

        if($result['status']) $result['message'] = "Cliente criado com sucesso";
        return $this->response($result);
    }

    public function generate(Request $request){
        $result = $this->clientService->generateReportImmediately($request);

        if($result['status']) $result['message'] = "Relatório gerado com sucesso";
        return $this->response($result);
    }

    public function update(Request $request, $id){
        $result = $this->clientService->update($request, $id);

        if($result['status']) $result['message'] = "Cliente atualizado com sucesso";
        return $this->response($result);
    }

    public function delete($id){
        $result = $this->clientService->delete($id);

        if($result['status']) $result['message'] = "Cliente Deletado com sucesso";
        return $this->response($result);
    }

    private function response($result){
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null
        ], $result['statusCode'] ?? 200);
    }
}
