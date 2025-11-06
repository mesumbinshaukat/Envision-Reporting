<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Attendance Logs</h2>
    </x-slot>

    <div class="space-y-6">
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
                                <th class="text-left py-3 px-4 text-navy-900 font-semibold">IP Address</th>
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
                                    <td class="py-3 px-4 text-sm font-mono">
                                        {{ $log->ip_address ?? 'N/A' }}
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
