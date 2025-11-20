<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\V1\ClientResource;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiFiltering;
use App\Http\Traits\ApiSorting;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientApiController extends BaseApiController
{
    use ApiPagination, ApiFiltering, ApiSorting;

    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->tokenCan('admin')) {
            $query = Client::where('user_id', $user->id);
        } else {
            // Employee sees clients they have invoices for
            $query = Client::where('user_id', $user->admin_id)
                ->whereHas('invoices', function($q) use ($user) {
                    $q->where('employee_id', $user->employee_id);
                });
        }

        // Apply search
        $this->applySearch($query, ['name', 'email', 'primary_contact'], $request->input('search'));

        // Apply sorting
        $this->applySorting($query, [
            'id', 'name', 'email', 'created_at'
        ], 'created_at', 'desc');

        $clients = $this->applyPagination($query);

        return $this->paginated($clients, ClientResource::class);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $userId = $user->tokenCan('admin') ? $user->id : $user->admin_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email,NULL,id,user_id,' . $userId,
            'primary_contact' => 'nullable|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'picture' => 'nullable|image|max:2048',
            'website' => 'nullable|url|max:255',
        ]);

        $validated['user_id'] = $userId;

        if ($user->tokenCan('employee')) {
            $validated['created_by_employee_id'] = $user->id;
        }

        if ($request->hasFile('picture')) {
            $validated['picture'] = $request->file('picture')->store('clients', 'public');
        }

        $client = Client::create($validated);

        return $this->created(new ClientResource($client), 'Client created successfully');
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $query = Client::with(['invoices']);

        if ($user->tokenCan('admin')) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('user_id', $user->admin_id);
        }

        $client = $query->find($id);

        if (!$client) {
            return $this->notFound('Client not found');
        }

        return $this->resource(new ClientResource($client));
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $userId = $user->tokenCan('admin') ? $user->id : $user->admin_id;
        
        $query = Client::query();

        if ($user->tokenCan('admin')) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('user_id', $user->admin_id);
        }

        $client = $query->find($id);

        if (!$client) {
            return $this->notFound('Client not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:clients,email,' . $id . ',id,user_id,' . $userId,
            'primary_contact' => 'nullable|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'picture' => 'nullable|image|max:2048',
            'website' => 'nullable|url|max:255',
        ]);

        if ($request->hasFile('picture')) {
            if ($client->picture) {
                Storage::disk('public')->delete($client->picture);
            }
            $validated['picture'] = $request->file('picture')->store('clients', 'public');
        }

        $client->update($validated);

        return $this->resource(new ClientResource($client), 'Client updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $query = Client::query();

        if ($user->tokenCan('admin')) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('user_id', $user->admin_id);
        }

        $client = $query->find($id);

        if (!$client) {
            return $this->notFound('Client not found');
        }

        if ($user->tokenCan('employee')) {
            $client->deleted_by_employee_id = $user->id;
            $client->save();
        }

        $client->delete();

        return $this->success(null, 'Client deleted successfully');
    }
}
