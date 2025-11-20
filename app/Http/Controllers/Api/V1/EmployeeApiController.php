<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\V1\EmployeeResource;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiFiltering;
use App\Http\Traits\ApiSorting;
use App\Models\Employee;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeApiController extends BaseApiController
{
    use ApiPagination, ApiFiltering, ApiSorting;

    /**
     * Display a listing of employees
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Only admin users can access this
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can access employee list');
        }

        $userId = $request->user()->id;
        
        $query = Employee::where('user_id', $userId)
            ->with(['currency', 'employeeUser', 'ipWhitelists']);

        // Apply search
        $this->applySearch($query, ['name', 'email', 'role', 'primary_contact'], $request->input('search'));

        // Apply filters
        $this->applyFilters($query, [
            'role' => 'like',
            'employment_type' => '=',
            'marital_status' => '=',
            'geolocation_mode' => '=',
            'joining_date' => 'date_from',
            'last_date' => 'date_to',
        ]);

        // Apply sorting
        $this->applySorting($query, [
            'id', 'name', 'email', 'role', 'employment_type', 
            'joining_date', 'last_date', 'salary', 'created_at'
        ], 'created_at', 'desc');

        // Paginate
        $employees = $this->applyPagination($query);

        return $this->paginated($employees, EmployeeResource::class);
    }

    /**
     * Store a newly created employee
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can create employees');
        }

        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'primary_contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:employees,email,NULL,id,user_id,' . $userId,
            'role' => 'nullable|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'joining_date' => 'required|date',
            'last_date' => 'nullable|date|after:joining_date',
            'salary' => 'required|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'currency_id' => 'required|exists:currencies,id',
            'geolocation_mode' => ['required', Rule::in(Employee::GEOLOCATION_MODE_OPTIONS)],
            'ip_whitelists' => 'nullable|array',
            'ip_whitelists.*.ip_address' => 'required_with:ip_whitelists|ip',
            'ip_whitelists.*.label' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = $userId;

        DB::beginTransaction();
        try {
            $employee = Employee::create($validated);

            // Create IP whitelists if provided
            if (!empty($validated['ip_whitelists'])) {
                foreach ($validated['ip_whitelists'] as $whitelist) {
                    $employee->ipWhitelists()->create([
                        'ip_address' => $whitelist['ip_address'],
                        'ip_version' => filter_var($whitelist['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'ipv6' : 'ipv4',
                        'label' => $whitelist['label'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $employee->load(['currency', 'ipWhitelists']);

            return $this->created(new EmployeeResource($employee), 'Employee created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified employee
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view employee details');
        }

        $employee = Employee::where('user_id', $request->user()->id)
            ->with(['currency', 'employeeUser', 'ipWhitelists', 'invoices', 'bonuses', 'salaryReleases'])
            ->find($id);

        if (!$employee) {
            return $this->notFound('Employee not found');
        }

        return $this->resource(new EmployeeResource($employee));
    }

    /**
     * Update the specified employee
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can update employees');
        }

        $employee = Employee::where('user_id', $request->user()->id)->find($id);

        if (!$employee) {
            return $this->notFound('Employee not found');
        }

        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'primary_contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:employees,email,' . $id . ',id,user_id,' . $userId,
            'role' => 'nullable|string|max:255',
            'secondary_contact' => 'nullable|string|max:255',
            'employment_type' => 'sometimes|required|in:full_time,part_time,contract,intern',
            'joining_date' => 'sometimes|required|date',
            'last_date' => 'nullable|date|after:joining_date',
            'salary' => 'sometimes|required|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'currency_id' => 'sometimes|required|exists:currencies,id',
            'geolocation_mode' => ['sometimes', 'required', Rule::in(Employee::GEOLOCATION_MODE_OPTIONS)],
            'ip_whitelists' => 'nullable|array',
            'ip_whitelists.*.ip_address' => 'required_with:ip_whitelists|ip',
            'ip_whitelists.*.label' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $employee->update($validated);

            // Update IP whitelists if provided
            if (isset($validated['ip_whitelists'])) {
                // Delete existing whitelists
                $employee->ipWhitelists()->delete();
                
                // Create new whitelists
                foreach ($validated['ip_whitelists'] as $whitelist) {
                    $employee->ipWhitelists()->create([
                        'ip_address' => $whitelist['ip_address'],
                        'ip_version' => filter_var($whitelist['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'ipv6' : 'ipv4',
                        'label' => $whitelist['label'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $employee->load(['currency', 'employeeUser', 'ipWhitelists']);

            return $this->resource(new EmployeeResource($employee), 'Employee updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified employee (soft delete)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can delete employees');
        }

        $employee = Employee::where('user_id', $request->user()->id)->find($id);

        if (!$employee) {
            return $this->notFound('Employee not found');
        }

        $employee->delete();

        return $this->success(null, 'Employee deleted successfully');
    }

    /**
     * Toggle geolocation requirement for an employee
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleGeolocation(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can toggle geolocation');
        }

        $employee = Employee::where('user_id', $request->user()->id)->find($id);

        if (!$employee) {
            return $this->notFound('Employee not found');
        }

        $validated = $request->validate([
            'geolocation_mode' => ['required', Rule::in(Employee::GEOLOCATION_MODE_OPTIONS)],
        ]);

        $employee->update($validated);

        return $this->resource(new EmployeeResource($employee), 'Geolocation mode updated successfully');
    }

    /**
     * Bulk action on employees
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can perform bulk actions');
        }

        $validated = $request->validate([
            'action' => 'required|in:delete,restore,force_delete',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|integer|exists:employees,id',
        ]);

        $userId = $request->user()->id;
        $employees = Employee::where('user_id', $userId)
            ->whereIn('id', $validated['employee_ids']);

        if ($validated['action'] === 'delete') {
            $employees->delete();
            $message = 'Employees deleted successfully';
        } elseif ($validated['action'] === 'restore') {
            $employees->onlyTrashed()->restore();
            $message = 'Employees restored successfully';
        } elseif ($validated['action'] === 'force_delete') {
            $employees->onlyTrashed()->forceDelete();
            $message = 'Employees permanently deleted';
        }

        return $this->success(null, $message);
    }
}
