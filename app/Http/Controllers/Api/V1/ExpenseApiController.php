<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\V1\ExpenseResource;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiFiltering;
use App\Http\Traits\ApiSorting;
use App\Models\Expense;
use App\Models\Currency;
use Illuminate\Http\Request;

class ExpenseApiController extends BaseApiController
{
    use ApiPagination, ApiFiltering, ApiSorting;

    public function index(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view expenses');
        }

        $query = Expense::where('user_id', $request->user()->id)->with('currency');

        // Apply filters
        $this->applyFilters($query, [
            'currency_id' => '=',
            'date' => 'date',
        ]);

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $this->applySorting($query, ['id', 'date', 'amount', 'created_at'], 'date', 'desc');

        $expenses = $this->applyPagination($query);

        return $this->paginated($expenses, ExpenseResource::class);
    }

    public function store(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can create expenses');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        $validated['user_id'] = $request->user()->id;

        // Capture exchange rate
        $currency = Currency::find($validated['currency_id']);
        if ($currency) {
            $validated['exchange_rate_at_time'] = $currency->conversion_rate;
        }

        $expense = Expense::create($validated);
        $expense->load('currency');

        return $this->created(new ExpenseResource($expense), 'Expense created successfully');
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view expenses');
        }

        $expense = Expense::where('user_id', $request->user()->id)->with('currency')->find($id);

        if (!$expense) {
            return $this->notFound('Expense not found');
        }

        return $this->resource(new ExpenseResource($expense));
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can update expenses');
        }

        $expense = Expense::where('user_id', $request->user()->id)->find($id);

        if (!$expense) {
            return $this->notFound('Expense not found');
        }

        $validated = $request->validate([
            'description' => 'sometimes|required|string|max:255',
            'currency_id' => 'sometimes|required|exists:currencies,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
        ]);

        $expense->update($validated);
        $expense->load('currency');

        return $this->resource(new ExpenseResource($expense), 'Expense updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can delete expenses');
        }

        $expense = Expense::where('user_id', $request->user()->id)->find($id);

        if (!$expense) {
            return $this->notFound('Expense not found');
        }

        $expense->delete();

        return $this->success(null, 'Expense deleted successfully');
    }
}
