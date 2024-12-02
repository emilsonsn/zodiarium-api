<?php

namespace App\Services\Sale;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Traits\EupagoTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SaleService
{
    use EupagoTrait;

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);

            $sales = Sale::orderBy('id', 'desc');

            if($request->filled('client_id')){
                $sales->whereIn('client_id', $request->client_id);
            }

            if($request->filled('date_from') && $request->filled('date_to')){
                if($request->date_from === $request->date_to){
                    $sales->whereDate('created_at', $request->date_from);
                }else{
                    $sales->whereBetween('created_at', [$request->date_from, $request->date_to]);
                }
            }elseif($request->filled('date_from')){
                $sales->whereDate('created_at', '>' ,$request->date_from);
            }elseif($request->filled('date_to')){
                $sales->whereDate('created_at', '<' ,$request->date_from);
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
            $sale = Sale::wiht('client', 'products')
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
                'client_id' => ['required', 'integer'],
                'payment_method' => ['required', 'string', 'in:Multibanco,Mbway'],
                'country_code' => ['nullable', 'string'],
                'phone' => ['nullable', 'string'],
                'status' => ['nullable', 'string', 'in:Pending,Rejected,Finished'],
                'products' => ['required', 'string'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors(), 400);

            $data = $validator->validated();

            DB::beginTransaction();

            $sale = Sale::create($data);

            $products = explode(',' , $request->products);
            $saleProducts = [];
            foreach($products as $product_id){
                $saleProducts[] = SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product_id
                ]);
            }

            DB::commit();

            $payment_method = $request->payment_method;
            $uuid = Str::uuid()->toString();
            $country_code = $request->country_code;
            $phone = $request->phone;
            $totalAmount = SaleProduct::where('sale_id', $sale->id)
                ->with('product')
                ->get()
                ->sum(function ($saleProduct) {
                    return $saleProduct->product->amount;
                });

            $sale['payment'] = $this->createPayment($uuid, $payment_method, $totalAmount, $country_code, $phone);

            return ['status' => true, 'data' => $sale];
        } catch (Exception $error) {
            DB::rollBack();
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function veriryPayment($sale_id)
    {
        try {

            $payment = Payment::where('sale_id', $sale_id)
                ->first();
        
            $result = $this->checkPaymentStatus($payment->reference, $payment->entity);

            if(isset($result['estado']) && $result['estado'] == 'paga'){
                $payment->update([
                    'status' => PaymentStatus::Successful->value
                ]);
            }
            
            return ['status' => true, 'data' => $payment];
        } catch (Exception $error) {
            DB::rollBack();
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function createPayment(
        $uuid,
        $payment_method,
        $totalAmount,
        $countryCode = null,
        $phoneNumber = null
    ){
        switch($payment_method){
            case PaymentMethod::Multibanco->value:            
                return $this->createMultibancoReference($uuid, $totalAmount);
                break;
            case PaymentMethod::Mbway->value:
                return $this->createMbWayPayment($uuid, $totalAmount, $phoneNumber, $countryCode);
                break;
        }
    }


}