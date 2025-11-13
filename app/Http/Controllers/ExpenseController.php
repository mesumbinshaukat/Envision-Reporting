<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Currency;
use App\Traits\HandlesCurrency;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class ExpenseController extends Controller
{
    use AuthorizesRequests, HandlesCurrency;
    public function index(Request $request)
    {
        $userId = auth()->id();
        $query = Expense::where('user_id', $userId)->with('currency');
        
        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        
        $expenses = $query->latest('date')->paginate(10);
        
        // Convert all to base currency for total
        $baseCurrency = $this->getBaseCurrency();
        $allExpenses = Expense::where('user_id', $userId)->with('currency')->get();
        $totalAmount = 0;
        foreach ($allExpenses as $expense) {
            if ($expense->currency_id && $expense->currency) {
                $totalAmount += $expense->currency->toBase($expense->amount);
            } else {
                $totalAmount += $expense->amount;
            }
        }
        
        return view('expenses.index', compact('expenses', 'totalAmount', 'baseCurrency'));
    }

    public function create()
    {
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        return view('expenses.create', compact('currencies', 'baseCurrency'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);
        
        $validated['user_id'] = auth()->id();
        
        // Capture exchange rate at time of creation for historical accuracy
        if (isset($validated['currency_id'])) {
            $currency = \App\Models\Currency::find($validated['currency_id']);
            if ($currency) {
                $validated['exchange_rate_at_time'] = $currency->conversion_rate;
            }
        }
        
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
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        return view('expenses.edit', compact('expense', 'currencies', 'baseCurrency'));
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);
        
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
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
