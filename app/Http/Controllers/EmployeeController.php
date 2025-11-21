<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeIpWhitelist;
use App\Traits\HandlesCurrency;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


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
        
        $employees = $query->paginate(10)->withQueryString();
        $geolocationModeOptions = $this->geolocationModeOptions();

        return view('employees.index', compact('employees', 'geolocationModeOptions'));
    }

    public function create()
    {
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        $geolocationModeOptions = $this->geolocationModeOptions();

        return view('employees.create', compact('currencies', 'baseCurrency', 'geolocationModeOptions'));
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
            'geolocation_mode' => ['nullable', Rule::in(Employee::GEOLOCATION_MODE_OPTIONS)],
            'create_user_account' => 'nullable|boolean',
            'user_password' => 'required_if:create_user_account,1|nullable|min:8',
            'is_sales_person' => 'nullable|boolean',
        ]);
        
        $shouldCreateAccount = $request->boolean('create_user_account');
        $geolocationMode = $shouldCreateAccount
            ? ($validated['geolocation_mode'] ?? Employee::GEO_MODE_DISABLED)
            : Employee::GEO_MODE_DISABLED;

        if (!in_array($geolocationMode, Employee::GEOLOCATION_MODE_OPTIONS, true)) {
            $geolocationMode = Employee::GEO_MODE_REQUIRED;
        }

        $ipWhitelistEntries = [];
        if ($geolocationMode === Employee::GEO_MODE_REQUIRED_WITH_WHITELIST) {
            $ipWhitelistEntries = $this->prepareIpWhitelistEntries($request->input('ip_whitelists', []));

            if (empty($ipWhitelistEntries)) {
                throw ValidationException::withMessages([
                    'ip_whitelists' => 'Please add at least one IP address when using IP whitelisting.',
                ]);
            }
        }

        $validated['user_id'] = auth()->id();
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
        $validated['is_sales_person'] = $request->boolean('is_sales_person');
        $validated['geolocation_mode'] = $geolocationMode;
        $validated['geolocation_required'] = $geolocationMode !== Employee::GEO_MODE_DISABLED;

        DB::transaction(function () use ($validated, $shouldCreateAccount, $request, $ipWhitelistEntries) {
            $employee = Employee::create($validated);

            if ($shouldCreateAccount && $request->user_password) {
                \App\Models\EmployeeUser::create([
                    'employee_id' => $employee->id,
                    'admin_id' => auth()->id(),
                    'email' => $validated['email'],
                    'password' => \Hash::make($request->user_password),
                    'name' => $employee->name,
                ]);
            }

            if (!empty($ipWhitelistEntries)) {
                $this->createEmployeeIpWhitelists($employee, $ipWhitelistEntries);
            }
        });
        
        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);
        $employee->load(['invoices', 'bonuses', 'salaryReleases', 'currency', 'employeeUser', 'ipWhitelists']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->authorize('update', $employee);
        $employee->load('ipWhitelists');
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        $geolocationModeOptions = $this->geolocationModeOptions();

        return view('employees.edit', compact('employee', 'currencies', 'baseCurrency', 'geolocationModeOptions'));
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
            'geolocation_mode' => ['nullable', Rule::in(Employee::GEOLOCATION_MODE_OPTIONS)],
            'is_sales_person' => 'nullable|boolean',
        ]);

        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
        $validated['is_sales_person'] = $request->boolean('is_sales_person');
        $geolocationMode = $employee->employeeUser
            ? ($validated['geolocation_mode'] ?? $employee->geolocation_mode)
            : Employee::GEO_MODE_DISABLED;

        $ipWhitelistEntries = [];

        if (!in_array($geolocationMode, Employee::GEOLOCATION_MODE_OPTIONS, true)) {
            $geolocationMode = Employee::GEO_MODE_REQUIRED;
        }

        if ($geolocationMode === Employee::GEO_MODE_REQUIRED_WITH_WHITELIST) {
            $ipWhitelistEntries = $this->prepareIpWhitelistEntries($request->input('ip_whitelists', []));

            if (empty($ipWhitelistEntries)) {
                throw ValidationException::withMessages([
                    'ip_whitelists' => 'Please add at least one IP address when using IP whitelisting.',
                ]);
            }
        }

        $validated['geolocation_mode'] = $geolocationMode;
        $validated['geolocation_required'] = $geolocationMode !== Employee::GEO_MODE_DISABLED;
        
        $employee->update($validated);
        $this->syncEmployeeIpWhitelists($employee, $ipWhitelistEntries, $geolocationMode);
        
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
        
        if (!$employee->employeeUser) {
            return response()->json([
                'success' => false,
                'message' => 'Create an employee login before toggling geolocation.',
            ], 422);
        }

        $employee->geolocation_mode = $employee->geolocation_mode === Employee::GEO_MODE_DISABLED
            ? Employee::GEO_MODE_REQUIRED
            : Employee::GEO_MODE_DISABLED;
        $employee->geolocation_required = $employee->geolocation_mode !== Employee::GEO_MODE_DISABLED;
        $employee->save();
        
        $status = $employee->geolocation_required ? 'enabled' : 'disabled';
        $message = "Geolocation tracking {$status} for {$employee->name}";
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'geolocation_required' => $employee->geolocation_required,
            'geolocation_mode' => $employee->geolocation_mode,
        ]);
    }

    /**
     * Return selectable geolocation modes with friendly labels.
     */
    protected function geolocationModeOptions(): array
    {
        return [
            Employee::GEO_MODE_DISABLED => 'Default (Geolocation Not Required)',
            Employee::GEO_MODE_REQUIRED => 'Require geolocation for attendance',
            Employee::GEO_MODE_REQUIRED_WITH_WHITELIST => 'Geolocation Required With IP Whitelisting',
        ];
    }

    /**
     * Normalize and validate whitelist inputs from the request payload.
     *
     * @param array<int, array<string, mixed>> $input
     * @return array<int, array<string, mixed>>
     * @throws ValidationException
     */
    protected function prepareIpWhitelistEntries(array $input): array
    {
        $entries = [];
        $errors = [];

        foreach ($input as $index => $payload) {
            $ip = isset($payload['ip_address']) ? trim($payload['ip_address']) : '';
            $label = isset($payload['label']) ? trim($payload['label']) : null;

            if ($ip === '') {
                continue;
            }

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $entries[] = [
                    'ip_address' => $ip,
                    'ip_version' => 'ipv4',
                    'label' => $label,
                ];
                continue;
            }

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $entries[] = [
                    'ip_address' => strtolower($ip),
                    'ip_version' => 'ipv6',
                    'label' => $label,
                ];
                continue;
            }

            $errors["ip_whitelists.{$index}.ip_address"] = 'Please provide a valid IPv4 or IPv6 address.';
        }

        $duplicates = collect($entries)
            ->groupBy(fn ($entry) => strtolower($entry['ip_address']))
            ->filter(fn ($group) => $group->count() > 1)
            ->keys();

        if ($duplicates->isNotEmpty()) {
            $errors['ip_whitelists'] = 'Duplicate IP addresses are not allowed.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return collect($entries)
            ->unique(fn ($entry) => strtolower($entry['ip_address']))
            ->values()
            ->all();
    }

    protected function createEmployeeIpWhitelists(Employee $employee, array $entries): void
    {
        foreach ($entries as $entry) {
            EmployeeIpWhitelist::create([
                'employee_id' => $employee->id,
                'ip_address' => $entry['ip_address'],
                'ip_version' => $entry['ip_version'],
                'label' => $entry['label'] ?? null,
                'created_by' => auth()->id(),
            ]);
        }
    }

    protected function syncEmployeeIpWhitelists(Employee $employee, array $entries, string $geolocationMode): void
    {
        if ($geolocationMode !== Employee::GEO_MODE_REQUIRED_WITH_WHITELIST) {
            if ($employee->hasIpWhitelist()) {
                $employee->ipWhitelists()->delete();
            }

            return;
        }

        $existing = $employee->ipWhitelists()
            ->get()
            ->keyBy(fn ($whitelist) => strtolower($whitelist->ip_address));

        foreach ($entries as $entry) {
            $key = strtolower($entry['ip_address']);

            if (isset($existing[$key])) {
                $whitelist = $existing->pull($key);
                $newLabel = $entry['label'] ?? null;

                if ($whitelist->label !== $newLabel) {
                    $whitelist->update(['label' => $newLabel]);
                }

                continue;
            }

            EmployeeIpWhitelist::create([
                'employee_id' => $employee->id,
                'ip_address' => $entry['ip_address'],
                'ip_version' => $entry['ip_version'],
                'label' => $entry['label'] ?? null,
                'created_by' => auth()->id(),
            ]);
        }

        foreach ($existing as $stale) {
            $stale->delete();
        }
    }

    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['enable_location', 'disable_location', 'delete'])],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer'],
        ]);

        $ids = collect($data['employee_ids'])->unique()->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one employee.',
            ], 422);
        }

        $employees = Employee::where('user_id', auth()->id())
            ->whereIn('id', $ids)
            ->with('employeeUser')
            ->get();

        if ($employees->count() !== $ids->count()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected employees could not be found.',
            ], 422);
        }

        $summary = [
            'processed' => [],
            'skipped' => [],
        ];

        DB::transaction(function () use ($data, $employees, &$summary) {
            foreach ($employees as $employee) {
                $this->authorize('update', $employee);

                switch ($data['action']) {
                    case 'enable_location':
                        if (!$employee->employeeUser) {
                            $summary['skipped'][] = [
                                'id' => $employee->id,
                                'reason' => 'No employee login. Create one before enabling location.',
                            ];
                            continue 2;
                        }

                        $employee->geolocation_mode = Employee::GEO_MODE_REQUIRED;
                        $employee->save();
                        $summary['processed'][] = $employee->id;
                        break;

                    case 'disable_location':
                        $employee->geolocation_mode = Employee::GEO_MODE_DISABLED;
                        $employee->save();
                        $summary['processed'][] = $employee->id;
                        break;

                    case 'delete':
                        $this->authorize('delete', $employee);
                        $employee->delete();
                        $summary['processed'][] = $employee->id;
                        break;
                }
            }
        });

        $actionMessages = [
            'enable_location' => 'Location requirement enabled for selected employees.',
            'disable_location' => 'Location requirement disabled for selected employees.',
            'delete' => 'Selected employees deleted successfully.',
        ];

        return response()->json([
            'success' => true,
            'message' => $actionMessages[$data['action']] ?? 'Bulk action completed.',
            'summary' => $summary,
        ]);
    }

    public function bulkFetch(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:25'],
            'ids.*' => ['integer'],
        ]);

        $ids = collect($data['ids'])->unique()->values();

        $employees = Employee::where('user_id', auth()->id())
            ->whereIn('id', $ids)
            ->with(['currency', 'employeeUser'])
            ->get();

        if ($employees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No employees found for the provided selection.',
            ], 404);
        }

        $collection = $employees->map(function (Employee $employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role,
                'employment_type' => $employee->employment_type,
                'salary' => $employee->salary,
                'commission_rate' => $employee->commission_rate,
                'currency_symbol' => optional($employee->currency)->symbol ?? 'Rs.',
                'geolocation_mode' => $employee->geolocation_mode,
                'geolocation_mode_label' => $employee->geolocationModeLabel(),
                'has_user_account' => (bool) $employee->employeeUser,
                'geolocation_required' => $employee->geolocation_required,
                'joining_date' => optional($employee->joining_date)->format('M d, Y'),
                'phone' => $employee->primary_contact,
                'marital_status' => $employee->marital_status,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'employees' => $collection,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'updates' => ['required', 'array', 'min:1'],
            'updates.*.id' => ['required', 'integer'],
            'updates.*.role' => ['required', 'string', 'max:255'],
            'updates.*.employment_type' => ['required', 'string', 'max:255'],
            'updates.*.salary' => ['required', 'numeric', 'min:0'],
            'updates.*.commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'updates.*.geolocation_mode' => ['nullable', Rule::in(Employee::GEOLOCATION_MODE_OPTIONS)],
        ]);

        $ids = collect($data['updates'])->pluck('id')->unique();

        $employees = Employee::where('user_id', auth()->id())
            ->whereIn('id', $ids)
            ->with('employeeUser')
            ->get()
            ->keyBy('id');

        if ($employees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No employees matched your selection.',
            ], 404);
        }

        DB::transaction(function () use ($data, $employees) {
            foreach ($data['updates'] as $payload) {
                $employee = $employees->get($payload['id']);

                if (!$employee) {
                    continue;
                }

                $this->authorize('update', $employee);

                $employee->role = $payload['role'];
                $employee->employment_type = $payload['employment_type'];
                $employee->salary = $payload['salary'];
                $employee->commission_rate = $payload['commission_rate'];

                if ($employee->employeeUser && array_key_exists('geolocation_mode', $payload) && $payload['geolocation_mode']) {
                    $employee->geolocation_mode = $payload['geolocation_mode'];
                } elseif (!$employee->employeeUser) {
                    $employee->geolocation_mode = Employee::GEO_MODE_DISABLED;
                }

                $employee->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Employees updated successfully.',
        ]);
    }
}
