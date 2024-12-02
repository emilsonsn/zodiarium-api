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

            $products = Product::orderBy('id', 'desc');

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

    public function create($request)
    {
        try {
            $request['is_active'] = $request['is_active'] == 'null' ? true : $request['is_active'];

            $rules = [
                'title' => ['required', 'string', 'max:255'],
                'image' => ['required', 'file', 'image', 'max:1024'],
                'amount' => ['required', 'numeric'],
                'is_active' => ['nullable', 'boolean'],
                'type' => ['required', 'string', 'in:Main,Bundle,Upsell'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors(), 400);

            $validatedData = $validator->validated();

            if ($request->hasFile('image')) {                
                $logoPath = $request->file('logo')->store('public/images');
                $validatedData['logo'] = str_replace('public/image/', '', $logoPath);
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
            $request['is_active'] = $request['is_active'] == 'null' ? true : $request['is_active'];
            
            $rules = [
                'title' => ['required', 'string', 'max:255'],
                'image' => ['required', 'file', 'image', 'max:1024'],
                'amount' => ['required', 'numeric'],
                'is_active' => ['nullable', 'boolean'],
                'type' => ['required', 'string', 'in:Main,Bundle,Upsell'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $productToUpdate = Product::find($id);

            if (!$productToUpdate) throw new Exception('Produto não encontrado');

            $validatedData = $validator->validated();

            if ($request->hasFile('logo')) {
                if ($productToUpdate->image && Storage::exists($productToUpdate->image)) {
                    Storage::delete($productToUpdate->logo);
                }

                $logoPath = $request->file('logo')->store('public/images');
                $validatedData['logo'] = str_replace('public/images/', '', $logoPath);
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
