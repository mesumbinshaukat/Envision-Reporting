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
        
        $employees = $query->paginate(10);
        return view('employees.index', compact('employees'));
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
        ]);
        
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
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
}
