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
        $query = auth()->user()->clients();
        
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email,NULL,id,user_id,' . auth()->id(),
            'primary_contact' => 'nullable|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'picture' => 'nullable|image|max:2048',
            'website' => 'nullable|url|max:255',
        ]);
        
        $validated['user_id'] = auth()->id();
        
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
        $this->authorize('view', $client);
        return view('clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        $this->authorize('update', $client);
        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email,' . $client->id . ',id,user_id,' . auth()->id(),
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
        $this->authorize('delete', $client);
        
        if ($client->picture) {
            Storage::disk('public')->delete($client->picture);
        }
        
        $client->delete();
        
        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }
}
