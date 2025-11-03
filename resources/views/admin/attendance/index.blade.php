<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Attendance Management</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.attendance.statistics') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    View Statistics
                </a>
                <a href="{{ route('admin.attendance.fix-requests.index') }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
                    Fix Requests
                </a>
                <a href="{{ route('admin.attendance.create') }}" class="bg-navy-900 text-white px-4 py-2 rounded hover:bg-navy-800">
                    Add Attendance
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4">Filters</h3>
            
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="employee_user_id" class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                    <select name="employee_user_id" id="employee_user_id" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_user_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">All Status</option>
                        <option value="checked_in" {{ request('status') == 'checked_in' ? 'selected' : '' }}>Checked In Only</option>
                        <option value="checked_out" {{ request('status') == 'checked_out' ? 'selected' : '' }}>Completed</option>
                        <option value="missing" {{ request('status') == 'missing' ? 'selected' : '' }}>Missing Check-in</option>
                    </select>
                </div>

                <div class="md:col-span-4 flex gap-2">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-2 rounded hover:bg-navy-800">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.attendance.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Attendance Records -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Attendance Records</h3>
            
            @if($attendances->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-navy-900">
                                <th class="text-left py-2 px-4 text-navy-900">Employee</th>
                                <th class="text-left py-2 px-4 text-navy-900">Date</th>
                                <th class="text-left py-2 px-4 text-navy-900">Check In</th>
                                <th class="text-left py-2 px-4 text-navy-900">Check Out</th>
                                <th class="text-left py-2 px-4 text-navy-900">Duration</th>
                                <th class="text-left py-2 px-4 text-navy-900">Status</th>
                                <th class="text-left py-2 px-4 text-navy-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendances as $attendance)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4">{{ $attendance->employeeUser->name }}</td>
                                    <td class="py-2 px-4">{{ $attendance->attendance_date->format('M d, Y') }}</td>
                                    <td class="py-2 px-4">
                                        {{ $attendance->check_in ? $attendance->check_in->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-2 px-4">
                                        {{ $attendance->check_out ? $attendance->check_out->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-2 px-4">
                                        {{ $attendance->formatted_work_duration ?? '-' }}
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($attendance->hasCheckedOut())
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Complete</span>
                                        @elseif($attendance->hasCheckedIn())
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Checked In</span>
                                        @else
                                            <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">Incomplete</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4">
                                        <div class="flex gap-2">
                                            <a href="{{ route('admin.attendance.show', $attendance) }}" class="text-blue-600 hover:underline text-sm">View</a>
                                            <a href="{{ route('admin.attendance.edit', $attendance) }}" class="text-navy-900 hover:underline text-sm">Edit</a>
                                            <form method="POST" action="{{ route('admin.attendance.destroy', $attendance) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this attendance record?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $attendances->links() }}
                </div>
            @else
                <p class="text-gray-600 text-center py-8">No attendance records found.</p>
            @endif
        </div>
    </div>
</x-app-layout>
