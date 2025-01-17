<?php

namespace App\Http\Controllers;

use App\Services\Report\ReportService;

class ReportController extends Controller
{
    private $reportService;

    public function __construct(ReportService $reportService) {
        $this->reportService = $reportService;
    }

    public function getGeneratedReports(){
        $response = $this->reportService->getGeneratedReports();

        return $this->response($response);
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
