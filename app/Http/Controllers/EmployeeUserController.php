<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeUserController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:employee_users,email',
            'password' => 'required|min:8',
        ]);

        // Check if employee already has a user account
        if ($employee->employeeUser) {
            return redirect()->back()->withErrors(['email' => 'This employee already has a user account.']);
        }

        EmployeeUser::create([
            'employee_id' => $employee->id,
            'admin_id' => auth()->id(),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'name' => $employee->name,
        ]);

        return redirect()->route('employees.edit', $employee)->with('success', 'Employee user account created successfully.');
    }

    public function destroy(EmployeeUser $employeeUser)
    {
        $employeeUser->delete();
        return redirect()->back()->with('success', 'Employee user access revoked successfully.');
    }

    public function destroyFull(Employee $employee)
    {
        // Delete employee user first if exists
        if ($employee->employeeUser) {
            $employee->employeeUser->delete();
        }
        
        // Then delete employee
        $employee->delete();
        
        return redirect()->route('employees.index')->with('success', 'Employee and user account deleted successfully.');
    }
}
