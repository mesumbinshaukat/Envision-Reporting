<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ClientController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            $userId = $employeeUser->admin_id;
            $employeeId = $employeeUser->employee_id;
            
            // Employee only sees clients assigned to them in invoices
            $query = Client::where('user_id', $userId)
                ->whereHas('invoices', function($q) use ($employeeId) {
                    $q->where('employee_id', $employeeId);
                });
        } else {
            $userId = auth()->id();
            $query = Client::where('user_id', $userId);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $clients = $query->paginate(10);
        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            $userId = $employeeUser->admin_id;
        } else {
            $userId = auth()->id();
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email,NULL,id,user_id,' . $userId,
            'primary_contact' => 'nullable|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'picture' => 'nullable|image|max:2048',
            'website' => 'nullable|url|max:255',
        ]);
        
        if ($isEmployee) {
            $validated['user_id'] = $userId;
            $validated['created_by_employee_id'] = $employeeUser->id;
        } else {
            $validated['user_id'] = $userId;
        }
        
        if ($request->hasFile('picture')) {
            $validated['picture'] = $request->file('picture')->store('clients', 'public');
        }
        
        Client::create($validated);
        
        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        // Manual authorization check for both guards
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            if ($employeeUser->admin_id !== $client->user_id) {
                abort(403, 'This action is unauthorized.');
            }
        } else {
            $this->authorize('view', $client);
        }
        
        $client->load(['invoices', 'invoices.currency']);
        return view('clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        // Manual authorization check for both guards
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            if ($employeeUser->admin_id !== $client->user_id) {
                abort(403, 'This action is unauthorized.');
            }
        } else {
            $this->authorize('update', $client);
        }
        
        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        // Manual authorization check for both guards
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            if ($employeeUser->admin_id !== $client->user_id) {
                abort(403, 'This action is unauthorized.');
            }
        } else {
            $this->authorize('update', $client);
        }
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            $userId = $employeeUser->admin_id;
        } else {
            $userId = auth()->id();
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email,' . $client->id . ',id,user_id,' . $userId,
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
        
        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        // Manual authorization check for both guards
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            if ($employeeUser->admin_id !== $client->user_id) {
                abort(403, 'This action is unauthorized.');
            }
        } else {
            $this->authorize('delete', $client);
        }
        
        if ($isEmployee) {
            // Employee soft delete - mark who deleted it
            $employeeUser = auth()->guard('employee')->user();
            $client->deleted_by_employee_id = $employeeUser->id;
            $client->save();
        }
        
        $client->delete(); // Soft delete
        
        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    public function trash()
    {
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            abort(403); // Employees can't see trash
        }
        
        $userId = auth()->id();
        $clients = Client::where('user_id', $userId)->onlyTrashed()->with(['createdByEmployee', 'deletedByEmployee'])->latest('deleted_at')->paginate(10);
        
        return view('clients.trash', compact('clients'));
    }

    public function restore($id)
    {
        $client = Client::withTrashed()->findOrFail($id);
        $client->restore();
        $client->update(['deleted_by_employee_id' => null]);
        
        return redirect()->back()->with('success', 'Client restored successfully.');
    }

    public function forceDelete($id)
    {
        $client = Client::withTrashed()->findOrFail($id);
        
        if ($client->picture) {
            Storage::disk('public')->delete($client->picture);
        }
        
        $client->forceDelete();
        
        return redirect()->back()->with('success', 'Client permanently deleted.');
    }
}
