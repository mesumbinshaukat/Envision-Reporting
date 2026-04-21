<?php

namespace App\Http\Controllers;

use App\Models\AllowanceType;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AllowanceTypeController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $allowanceTypes = AllowanceType::orderBy('label')->paginate(10);
        return view('allowance-types.index', compact('allowanceTypes'));
    }

    public function create()
    {
        return view('allowance-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:allowance_types,name',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        AllowanceType::create($validated);

        return redirect()->route('allowance-types.index')->with('success', 'Allowance type created successfully.');
    }

    public function edit(AllowanceType $allowanceType)
    {
        $this->authorize('update', $allowanceType);
        return view('allowance-types.edit', compact('allowanceType'));
    }

    public function update(Request $request, AllowanceType $allowanceType)
    {
        $this->authorize('update', $allowanceType);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:allowance_types,name,' . $allowanceType->id,
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', $allowanceType->is_active);

        $allowanceType->update($validated);

        return redirect()->route('allowance-types.index')->with('success', 'Allowance type updated successfully.');
    }

    public function destroy(AllowanceType $allowanceType)
    {
        $this->authorize('delete', $allowanceType);

        // Check if any employees are using this allowance type
        if ($allowanceType->employeeAllowances()->count() > 0) {
            return redirect()->route('allowance-types.index')->with('error', 'Cannot delete allowance type that is assigned to employees.');
        }

        $allowanceType->delete();

        return redirect()->route('allowance-types.index')->with('success', 'Allowance type deleted successfully.');
    }
}
