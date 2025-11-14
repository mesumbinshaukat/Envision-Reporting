<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeIpWhitelist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EmployeeIpWhitelistController extends Controller
{
    /**
     * Display a listing of the IP whitelists with creation form.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::with('employeeUser')
            ->orderBy('name')
            ->get();

        $query = EmployeeIpWhitelist::with(['employee.employeeUser', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('search_ip')) {
            $query->where('ip_address', 'like', '%' . trim($request->input('search_ip')) . '%');
        }

        $whitelists = $query->paginate(20)->appends($request->only('employee_id', 'search_ip'));

        return view('admin.attendance.ip-whitelists.index', [
            'employees' => $employees,
            'whitelists' => $whitelists,
        ]);
    }

    /**
     * Store a newly created whitelist entry.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeIpWhitelist::class);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'ip_address' => ['required', 'string', 'max:45'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizedIp = trim($validated['ip_address']);
        $ipVersion = null;

        if (filter_var($normalizedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipVersion = 'ipv4';
        } elseif (filter_var($normalizedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipVersion = 'ipv6';
            $normalizedIp = strtolower($normalizedIp);
        }

        if (!$ipVersion) {
            return back()
                ->withInput()
                ->with('error', 'Please provide a valid IPv4 or IPv6 address.');
        }

        $exists = EmployeeIpWhitelist::where('employee_id', $validated['employee_id'])
            ->whereRaw('LOWER(ip_address) = ?', [strtolower($normalizedIp)])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->with('error', 'This IP address is already whitelisted for the selected employee.');
        }

        $whitelist = EmployeeIpWhitelist::create([
            'employee_id' => $validated['employee_id'],
            'ip_address' => $normalizedIp,
            'ip_version' => $ipVersion,
            'label' => $validated['label'] ?? null,
            'created_by' => Auth::id(),
        ]);

        Log::info('Employee IP whitelisted', [
            'whitelist_id' => $whitelist->id,
            'employee_id' => $whitelist->employee_id,
            'ip_address' => $whitelist->ip_address,
            'ip_version' => $whitelist->ip_version,
        ]);

        return redirect()
            ->route('admin.attendance.ip-whitelists.index', $request->only('employee_id'))
            ->with('success', 'IP address whitelisted successfully.');
    }

    /**
     * Remove the specified whitelist entry.
     */
    public function destroy(EmployeeIpWhitelist $ipWhitelist, Request $request): RedirectResponse
    {
        $this->authorize('delete', $ipWhitelist);

        $ipWhitelist->delete();

        return redirect()
            ->route('admin.attendance.ip-whitelists.index', $request->only('employee_id'))
            ->with('success', 'Whitelist entry removed successfully.');
    }
}
