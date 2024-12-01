<?php

namespace App\Http\Controllers;

use App\Services\Sale\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    private $saleService;

    public function __construct(SaleService $saleService) {
        $this->saleService = $saleService;
    }

    public function search(Request $request){
        $result = $this->saleService->search($request);

        return $result;
    }

    public function getById(int $id){
        $result = $this->saleService->getById($id);

        return $result;
    }

    public function create(Request $request){
        $result = $this->saleService->create($request);

        if($result['status']) $result['message'] = "Compra criada com sucesso";
        return $this->response($result);
    }

    public function update(Request $request, $id){
        $result = $this->saleService->update($request, $id);

        if($result['status']) $result['message'] = "Compra atualizada com sucesso";
        return $this->response($result);
    }

    public function delete($id){
        $result = $this->saleService->delete($id);

        if($result['status']) $result['message'] = "Compra deletada com sucesso";
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
