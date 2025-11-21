<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Attendance Logs</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
            <div class="text-blue-900">
                <strong>Retention Policy:</strong> Only the last {{ $retentionDays }} days of attendance logs are retained automatically.
            </div>
            <form method="POST" action="{{ route('admin.attendance-logs.cleanup') }}" onsubmit="return confirmAttendanceLogCleanup(event)">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V5a2 2 0 00-2-2h-2a2 2 0 00-2 2v2M4 7h16" />
                    </svg>
                    Clear All Logs
                </button>
            </form>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4">Filters</h3>
            
            <form method="GET" action="{{ route('admin.attendance-logs.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="employee_user_id" class="block text-sm font-semibold text-navy-900 mb-1">Employee</label>
                    <select name="employee_user_id" id="employee_user_id" class="w-full px-4 py-2 border border-navy-900 rounded">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_user_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="action" class="block text-sm font-semibold text-navy-900 mb-1">Action</label>
                    <select name="action" id="action" class="w-full px-4 py-2 border border-navy-900 rounded">
                        <option value="">All Actions</option>
                        <option value="check_in_success" {{ request('action') == 'check_in_success' ? 'selected' : '' }}>Check In Success</option>
                        <option value="check_in_failed" {{ request('action') == 'check_in_failed' ? 'selected' : '' }}>Check In Failed</option>
                        <option value="check_out_success" {{ request('action') == 'check_out_success' ? 'selected' : '' }}>Check Out Success</option>
                        <option value="check_out_failed" {{ request('action') == 'check_out_failed' ? 'selected' : '' }}>Check Out Failed</option>
                    </select>
                </div>

                <div>
                    <label for="failure_reason" class="block text-sm font-semibold text-navy-900 mb-1">Failure Reason</label>
                    <select name="failure_reason" id="failure_reason" class="w-full px-4 py-2 border border-navy-900 rounded">
                        <option value="">All Reasons</option>
                        <option value="out_of_range" {{ request('failure_reason') == 'out_of_range' ? 'selected' : '' }}>Out of Range</option>
                        <option value="already_checked_in" {{ request('failure_reason') == 'already_checked_in' ? 'selected' : '' }}>Already Checked In</option>
                        <option value="already_checked_out" {{ request('failure_reason') == 'already_checked_out' ? 'selected' : '' }}>Already Checked Out</option>
                        <option value="not_checked_in" {{ request('failure_reason') == 'not_checked_in' ? 'selected' : '' }}>Not Checked In</option>
                        <option value="geolocation_denied" {{ request('failure_reason') == 'geolocation_denied' ? 'selected' : '' }}>Geolocation Denied</option>
                        <option value="other" {{ request('failure_reason') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-semibold text-navy-900 mb-1">Date From</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-semibold text-navy-900 mb-1">Date To</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-2 rounded hover:bg-opacity-90 font-semibold">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.attendance-logs.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 font-semibold">
                        Clear
                    </a>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="failed_only" id="failed_only" value="1" {{ request('failed_only') ? 'checked' : '' }} class="mr-2">
                    <label for="failed_only" class="text-sm font-semibold text-navy-900">Show Failed Attempts Only</label>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            <div class="p-4 bg-navy-900 text-white flex justify-between items-center">
                <h3 class="text-xl font-bold">Attendance Logs ({{ $logs->total() }})</h3>
            </div>

            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-100 border-b-2 border-navy-900">
                            <tr>
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">Date/Time</th>
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">Employee</th>
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">Action</th>
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">Status</th>
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">Distance</th>
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">IP Addresses</th>
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">Device</th>
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 text-sm">
                                        {{ $log->attempted_at->format('M d, Y') }}<br>
                                        <span class="text-gray-600">{{ $log->attempted_at->format('h:i:s A') }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ $log->employeeUser->employee->name ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm">
                                            {{ str_replace('_', ' ', ucwords($log->action, '_')) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if(str_contains($log->action, 'success'))
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">Success</span>
                                        @else
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">
                                                Failed: {{ str_replace('_', ' ', ucwords($log->failure_reason ?? 'Unknown', '_')) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-sm">
                                        @if($log->distance_from_office !== null)
                                            <span class="{{ $log->distance_from_office > 15 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                                {{ number_format($log->distance_from_office, 2) }}m
                                            </span>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    @php
                                        $primaryIp = $log->ip_address;
                                        $ipv4 = $log->ip_address_v4 ?? (filter_var($primaryIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $primaryIp : null);
                                        $ipv6 = $log->ip_address_v6 ?? (filter_var($primaryIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $primaryIp : null);
                                    @endphp
                                    <td class="py-3 px-4 text-sm">
                                        <div class="space-y-1 font-mono text-xs sm:text-sm">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                                                <span class="text-gray-500 uppercase tracking-wide text-[10px] sm:text-[11px]">IPv4</span>
                                                <span class="break-all">{{ $ipv4 ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                                                <span class="text-gray-500 uppercase tracking-wide text-[10px] sm:text-[11px]">IPv6</span>
                                                <span class="break-all">{{ $ipv6 ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-sm">
                                        <div class="text-xs">
                                            <div>{{ $log->device_type ?? 'Unknown' }}</div>
                                            <div class="text-gray-600">{{ $log->browser ?? 'Unknown' }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <a href="{{ route('admin.attendance-logs.show', $log) }}" class="text-navy-900 hover:underline text-sm">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="p-8 text-center text-gray-600">
                    No logs found matching your criteria.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

@push('scripts')
    <script>
        function confirmAttendanceLogCleanup(event) {
            const proceed = confirm('This will permanently remove all attendance logs. This action cannot be undone. Continue?');
            if (!proceed) {
                event.preventDefault();
                return false;
            }

            const button = event.currentTarget.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="flex items-center gap-2"><svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path></svg><span>Clearing...</span></span>';
            }

            return true;
        }
    </script>
@endpush
