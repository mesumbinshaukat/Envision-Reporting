<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Currency;
use App\Traits\HandlesCurrency;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class EmployeeController extends Controller
{
    use AuthorizesRequests, HandlesCurrency;
    public function index(Request $request)
    {
        $userId = auth()->id();
        $query = Employee::where('user_id', $userId)->with(['currency', 'employeeUser']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->has('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }
        
        $employees = $query->paginate(10);
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        return view('employees.create', compact('currencies', 'baseCurrency'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
            'marital_status' => 'nullable|string',
            'primary_contact' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,NULL,id,user_id,' . auth()->id(),
            'role' => 'required|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'employment_type' => 'required|string',
            'joining_date' => 'nullable|date',
            'last_date' => 'nullable|date',
            'salary' => 'required|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'geolocation_required' => 'sometimes|boolean',
            'create_user_account' => 'nullable|boolean',
            'user_password' => 'required_if:create_user_account,1|nullable|min:8',
        ]);
        
        $validated['user_id'] = auth()->id();
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
        $validated['geolocation_required'] = $request->boolean('geolocation_required', true);
        
        $employee = Employee::create($validated);
        
        // Create employee user account if requested
        if ($request->create_user_account && $request->user_password) {
            \App\Models\EmployeeUser::create([
                'employee_id' => $employee->id,
                'admin_id' => auth()->id(),
                'email' => $validated['email'],
                'password' => \Hash::make($request->user_password),
                'name' => $employee->name,
            ]);
        }
        
        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);
        $employee->load(['invoices', 'bonuses', 'salaryReleases', 'currency', 'employeeUser']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->authorize('update', $employee);
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        return view('employees.edit', compact('employee', 'currencies', 'baseCurrency'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
            'marital_status' => 'nullable|string',
            'primary_contact' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id . ',id,user_id,' . auth()->id(),
            'role' => 'required|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'employment_type' => 'required|string',
            'joining_date' => 'nullable|date',
            'last_date' => 'nullable|date',
            'salary' => 'required|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'geolocation_required' => 'sometimes|boolean',
        ]);
        
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
        $validated['geolocation_required'] = $request->boolean('geolocation_required');
        
        $employee->update($validated);
        
        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);
        $employee->delete();
        
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    /**
     * Toggle geolocation requirement for an employee
     */
    public function toggleGeolocation(Employee $employee)
    {
        $this->authorize('update', $employee);
        
        $employee->geolocation_required = !$employee->geolocation_required;
        $employee->save();
        
        $status = $employee->geolocation_required ? 'enabled' : 'disabled';
        $message = "Geolocation tracking {$status} for {$employee->name}";
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'geolocation_required' => $employee->geolocation_required
        ]);
    }
}
