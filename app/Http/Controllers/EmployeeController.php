<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class EmployeeController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $userId = auth()->id();
        $query = Employee::where('user_id', $userId);
        
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
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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
            'create_user_account' => 'nullable|boolean',
            'user_password' => 'required_if:create_user_account,1|nullable|min:8',
        ]);
        
        $validated['user_id'] = auth()->id();
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
        
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
        $employee->load(['invoices', 'bonuses', 'salaryReleases']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->authorize('update', $employee);
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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
        ]);
        
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
        
        $employee->update($validated);
        
        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);
        $employee->delete();
        
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
