<?php

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class BonusController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $bonuses = auth()->user()->bonuses()->with('employee')->latest()->paginate(10);
        return view('bonuses.index', compact('bonuses'));
    }

    public function create()
    {
        $employees = auth()->user()->employees;
        return view('bonuses.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'release_type' => 'required|in:with_salary,separate',
        ]);
        
        $validated['user_id'] = auth()->id();
        $validated['released'] = $request->release_type === 'separate';
        
        Bonus::create($validated);
        
        return redirect()->route('bonuses.index')->with('success', 'Bonus created successfully.');
    }

    public function show(Bonus $bonus)
    {
        $this->authorize('view', $bonus);
        $bonus->load('employee');
        return view('bonuses.show', compact('bonus'));
    }

    public function edit(Bonus $bonus)
    {
        $this->authorize('update', $bonus);
        $employees = auth()->user()->employees;
        return view('bonuses.edit', compact('bonus', 'employees'));
    }

    public function update(Request $request, Bonus $bonus)
    {
        $this->authorize('update', $bonus);
        
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'release_type' => 'required|in:with_salary,separate',
        ]);
        
        $validated['released'] = $request->release_type === 'separate';
        
        $bonus->update($validated);
        
        return redirect()->route('bonuses.index')->with('success', 'Bonus updated successfully.');
    }

    public function destroy(Bonus $bonus)
    {
        $this->authorize('delete', $bonus);
        $bonus->delete();
        
        return redirect()->route('bonuses.index')->with('success', 'Bonus deleted successfully.');
    }
}
