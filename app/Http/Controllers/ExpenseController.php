<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class ExpenseController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $userId = auth()->id();
        $query = Expense::where('user_id', $userId);
        
        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        
        $expenses = $query->latest('date')->paginate(10);
        $totalAmount = $query->sum('amount');
        
        return view('expenses.index', compact('expenses', 'totalAmount'));
    }

    public function create()
    {
        return view('expenses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);
        
        $validated['user_id'] = auth()->id();
        
        Expense::create($validated);
        
        return redirect()->route('expenses.index')->with('success', 'Expense created successfully.');
    }

    public function show(Expense $expense)
    {
        $this->authorize('view', $expense);
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $this->authorize('update', $expense);
        return view('expenses.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);
        
        $validated = $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);
        
        $expense->update($validated);
        
        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);
        $expense->delete();
        
        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
    }
}
