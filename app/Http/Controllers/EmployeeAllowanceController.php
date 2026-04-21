<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAllowance;
use App\Models\Employee;
use App\Models\AllowanceType;
use App\Models\Currency;
use App\Traits\HandlesCurrency;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeAllowanceController extends Controller
{
    use AuthorizesRequests, HandlesCurrency;

    public function index()
    {
        $userId = auth()->id();
        $employeeAllowances = EmployeeAllowance::where('user_id', $userId)
            ->with(['employee', 'allowanceType', 'currency'])
            ->latest()
            ->paginate(10);
        $baseCurrency = $this->getBaseCurrency();
        return view('employee-allowances.index', compact('employeeAllowances', 'baseCurrency'));
    }

    public function create()
    {
        $userId = auth()->id();
        $employees = Employee::where('user_id', $userId)->get();
        $allowanceTypes = AllowanceType::active()->orderBy('label')->get();
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        return view('employee-allowances.create', compact('employees', 'allowanceTypes', 'currencies', 'baseCurrency'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'allowance_type_id' => 'required|exists:allowance_types,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active', true);

        // Capture exchange rate at time of creation for historical accuracy
        if (isset($validated['currency_id'])) {
            $currency = Currency::find($validated['currency_id']);
            if ($currency) {
                $validated['exchange_rate_at_time'] = $currency->conversion_rate;
            }
        }

        // Check if this employee already has this allowance type
        $existing = EmployeeAllowance::where('employee_id', $validated['employee_id'])
            ->where('allowance_type_id', $validated['allowance_type_id'])
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return redirect()->back()->withErrors([
                'allowance_type_id' => 'This employee already has an active ' . $existing->allowanceType->label . ' allowance.'
            ])->withInput();
        }

        EmployeeAllowance::create($validated);

        return redirect()->route('employee-allowances.index')->with('success', 'Employee allowance created successfully.');
    }

    public function edit(EmployeeAllowance $employeeAllowance)
    {
        $this->authorize('update', $employeeAllowance);
        $userId = auth()->id();
        $employees = Employee::where('user_id', $userId)->get();
        $allowanceTypes = AllowanceType::active()->orderBy('label')->get();
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        return view('employee-allowances.edit', compact('employeeAllowance', 'employees', 'allowanceTypes', 'currencies', 'baseCurrency'));
    }

    public function update(Request $request, EmployeeAllowance $employeeAllowance)
    {
        $this->authorize('update', $employeeAllowance);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'allowance_type_id' => 'required|exists:allowance_types,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', $employeeAllowance->is_active);

        // Check if another active allowance exists for this employee and type (excluding current record)
        $existing = EmployeeAllowance::where('employee_id', $validated['employee_id'])
            ->where('allowance_type_id', $validated['allowance_type_id'])
            ->where('is_active', true)
            ->where('id', '!=', $employeeAllowance->id)
            ->first();

        if ($existing) {
            return redirect()->back()->withErrors([
                'allowance_type_id' => 'This employee already has an active ' . $existing->allowanceType->label . ' allowance.'
            ])->withInput();
        }

        $employeeAllowance->update($validated);

        return redirect()->route('employee-allowances.index')->with('success', 'Employee allowance updated successfully.');
    }

    public function destroy(EmployeeAllowance $employeeAllowance)
    {
        $this->authorize('delete', $employeeAllowance);
        $employeeAllowance->delete();

        return redirect()->route('employee-allowances.index')->with('success', 'Employee allowance deleted successfully.');
    }
}
