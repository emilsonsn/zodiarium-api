<?php

namespace App\Services\Sale;

use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\Validator;

class SaleService
{

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);

            $sales = Sale::orderBy('id', 'desc');

            if ($request->filled('external_id')) {
                $sales->where('external_id', 'LIKE', "%{$request->external_id}%");
            }

            if($request->filled('client_id')){
                $sales->whereIn('client_id', $request->client_id);
            }

            if($request->filled('date_from') && $request->filled('date_to')){
                if($request->date_from === $request->date_to){
                    $sales->whereDate('date_from', $request->date_from);
                }else{
                    $sales->whereBetween('date_from', [$request->date_from, $request->date_to]);
                }
            }elseif($request->filled('date_from')){
                $sales->whereDate('date_from', '>' ,$request->date_from);
            }elseif($request->filled('date_to')){
                $sales->whereDate('date_from', '<' ,$request->date_from);
            }

            $sales = $sales->paginate($perPage);

            return $sales;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function getById($id)
    {
        try {
            $sale = Sale::wiht('client', 'product')
                ->find($id);

            return $sale;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $rules = [
                'external_id' => ['required' ,'string', 'max:255'],
                'client_id' => ['required', 'integer'],
                'status' => ['nullable', 'string', 'in:Pending,Rejected,Finished'],
                'product_id' => ['nullable', 'integer'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors(), 400);

            $data = $validator->validated();

            $sale = Sale::create($data);

            return ['status' => true, 'data' => $sale];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $user_id)
    {
        try {
            $rules = [
                'external_id' => ['required' ,'string', 'max:255'],
                'client_id' => ['required', 'integer'],
                'status' => ['nullable', 'string', 'in:Pending,Rejected,Finished'],
                'product_id' => ['nullable', 'integer'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $saleToUpdate = Sale::find($user_id);

            if (!$saleToUpdate) throw new Exception('Compra não encontrada');

            $saleToUpdate->update($validator->validated());

            return ['status' => true, 'data' => $saleToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id)
    {
        try {
            $sale = Sale::find($id);

            if (!$sale) throw new Exception('Compra não encontrada');

            $saleId = $sale->id;
            $sale->delete();

            return ['status' => true, 'data' => $saleId];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
}
