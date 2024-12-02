<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Traits\DivineAPITrait;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductService
{
    use DivineAPITrait;

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $order = $request->input('order', 'DESC');

            $products = Product::orderBy('id', $order);

            if ($request->filled('search_term')) {
                $products->where('title', 'LIKE', "%{$request->search_term}%");
            }

            if($request->filled('is_active')) {
                $products->where('is_active', $request->is_active);
            }

            $products = $products->paginate($perPage);

            return $products;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function show()
    {
        try {
            $mainReport = Product::where('type', 'Main')
                ->where('is_active', true)
                ->first();

            $bundleReports = Product::where('type', 'Bundle')
                ->where('is_active', true)
                ->get();
            $upsellReports = Product::where('type', 'Upsell')
                ->where('is_active', true)
                ->get();

            $data = [
                'main' => $mainReport,
                'upsell' => $upsellReports,
                'bundle' => [
                    'sum' => $bundleReports->count() ? $bundleReports->sum('amount') + $mainReport->amount : null,
                    'count' => $bundleReports->count() + 1,
                    'reports' => $bundleReports
                ],
            ];

            return [ 'status' => true, 'data' => $data ];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $request['is_active'] = $request['is_active'] == 'false' ? false : true;

            $rules = [
                'title' => ['required', 'string', 'max:255'],
                'image' => ['nullable', 'file', 'image', 'max:1024'],
                'images' => ['nullable', 'array'],
                'amount' => ['required', 'numeric'],
                'is_active' => ['nullable', 'boolean'],
                'report' => ['required', 'string', 'max:255'],
                'type' => ['required', 'string', 'in:Main,Bundle,Upsell'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors(), 400);

            if(!isset($request->images) && !isset($request->image)){
                throw new Exception("Imagem é obrigatória", 400);
            }

            if($request->type == 'Main' && Product::where('type', 'Main')->where('is_active', true)->count()){
                throw new Exception("Já existe um relatório setado como principal", 400);
            }

            $validatedData = $validator->validated();

            if(isset($request->images)){
                $image = $request->images[0];
                $imagePath = $image->store('images');
                $validatedData['image'] = str_replace('images/', '', $imagePath);
            }else{
                $imagePath = $request->file('image')->store('images');
                $validatedData['image'] = str_replace('images/', '', $imagePath);
            }

            $product = Product::create($validatedData);

            return ['status' => true, 'data' => $product];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $id)
    {
        try {
            $request['is_active'] = $request['is_active'] == 'false' ? false : true;

            $rules = [
                'title' => ['required', 'string', 'max:255'],
                'image' => ['nullable', 'file', 'image', 'max:1024'],
                'images' => ['nullable', 'array'],
                'amount' => ['required', 'numeric'],
                'is_active' => ['nullable', 'boolean'],
                'report' => ['required', 'string', 'max:255'],
                'type' => ['required', 'string', 'in:Main,Bundle,Upsell'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $productToUpdate = Product::find($id);

            $mainReports = Product::where('type', 'Main')->where('is_active', true)
                ->where('id', '!=', $productToUpdate->id)
                ->count();

            if($request->type == 'Main' && $mainReports){
                throw new Exception("Já existe um relatório setado como principal", 400);
            }

            if (!$productToUpdate) throw new Exception('Produto não encontrado');

            $validatedData = $validator->validated();

            if(isset($request->images)){
                $image = $request->images[0];                
                if ($productToUpdate->image) {
                    $storagePath = explode('/storage', $productToUpdate->image);
                    if(Storage::exists($storagePath[1])){
                        Storage::delete($productToUpdate->image);
                    }
                }
                $imagePath = $image->store('images');
                $validatedData['image'] = str_replace('images/', '', $imagePath);
            }else if($request->hasFile('image')){
                $imagePath = $request->file('image')->store('images');
                $validatedData['image'] = str_replace('images/', '', $imagePath);
            }

            $productToUpdate->update($validatedData);

            return ['status' => true, 'data' => $productToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) throw new Exception('Produto não encontrado');

            $productName = $product->name;
            $product->delete();

            return ['status' => true, 'data' => $productName];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

}

