<?php

namespace App\Services\Client;

use App\Models\Client;
use Exception;
use Illuminate\Support\Facades\Validator;

class ClientService
{
    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;

            $clients = Client::orderBy('id', 'desc');

            if ($request->filled('search_term')) {
                $clients->where('name', 'LIKE', "%{$search_term}%")
                    ->orWhere('email', 'LIKE', "%{$search_term}%")
                    ->orWhere('whatsapp', 'LIKE', "%{$search_term}%")
                    ->orWhere('ddi', 'LIKE', "%{$search_term}%");
            }

            if($request->filled('status')){
                $clients->where('status', $request->status);
            }

            $clients = $clients->paginate($perPage);

            return $clients;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'gender' => 'required|string|max:10',
                'address' => 'required|string',
                'day_birth' => 'required|integer|min:1|max:31',
                'month_birth' => 'required|integer|min:1|max:12',
                'year_birth' => 'required|integer',
                'hour_birth' => 'nullable|integer|min:0|max:23',
                'minute_birth' => 'nullable|integer|min:0|max:59',
                'email' => 'nullable|string|email|max:255|unique:clients,email',
                'ddi' => 'nullable|string|max:5',
                'whatsapp' => 'nullable|string|max:20',
                'status' => 'nullable|string|in:Lead,Client,Partner',
                'client_id' => 'nullable|integer'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $client = Client::create($validator->validated());

            return ['status' => true, 'data' => $client];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $user_id)
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'gender' => 'required|string|max:10',
                'address' => 'required|string',
                'day_birth' => 'required|integer|min:1|max:31',
                'month_birth' => 'required|integer|min:1|max:12',
                'year_birth' => 'required|integer',
                'hour_birth' => 'nullable|integer|min:0|max:23',
                'minute_birth' => 'nullable|integer|min:0|max:59',
                'email' => 'nullable|string|email|max:255|unique:clients,email,' . $user_id,
                'ddi' => 'nullable|string|max:5',
                'whatsapp' => 'nullable|string|max:20',
                'status' => 'nullable|string|in:Lead,Client',
                'client_id' => 'nullable|integer'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $clientToUpdate = Client::find($user_id);

            if (!$clientToUpdate) throw new Exception('Cliente não encontrado');

            $clientToUpdate->update($validator->validated());

            return ['status' => true, 'data' => $clientToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id)
    {
        try {
            $client = Client::find($id);

            if (!$client) throw new Exception('Cliente não encontrado');

            $clientName = $client->name;
            $client->delete();

            return ['status' => true, 'data' => $clientName];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
}
