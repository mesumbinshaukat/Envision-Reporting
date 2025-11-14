<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Attendance Statistics</h2>
            <a href="{{ route('admin.attendance.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Back to Attendance
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Date Range Filter -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4">Select Date Range</h3>
            
            <form method="GET" action="{{ route('admin.attendance.statistics') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="employee_user_id" class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                    <select name="employee_user_id" id="employee_user_id" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">All Employees</option>
                        @foreach($allEmployees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_user_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        id="start_date" 
                        value="{{ $startDate->format('Y-m-d') }}" 
                        class="w-full border border-gray-300 rounded px-3 py-2"
                    >
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input 
                        type="date" 
                        name="end_date" 
                        id="end_date" 
                        value="{{ $endDate->format('Y-m-d') }}" 
                        class="w-full border border-gray-300 rounded px-3 py-2"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-2 rounded hover:bg-navy-800 flex-1">
                        Generate Report
                    </button>
                    <a href="{{ route('admin.attendance.statistics') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">
                Summary for {{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Total Employees</h4>
                    <p class="text-3xl font-bold text-blue-600">{{ $statistics->count() }}</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Total Days Worked</h4>
                    <p class="text-3xl font-bold text-green-600">{{ $statistics->sum('completed_days') }}</p>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Incomplete Days</h4>
                    <p class="text-3xl font-bold text-yellow-600">{{ $statistics->sum('incomplete_days') }}</p>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Days on Leave</h4>
                    <p class="text-3xl font-bold text-red-600">{{ $statistics->sum('days_on_leave') }}</p>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Total Hours</h4>
                    <p class="text-3xl font-bold text-purple-600">{{ number_format($statistics->sum('total_hours'), 1) }}</p>
                </div>
            </div>
        </div>

        <!-- Employee Statistics -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Employee-wise Statistics</h3>
            
            @if($statistics->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-navy-900">
                                <th class="text-left py-2 px-4 text-navy-900">Employee</th>
                                <th class="text-left py-2 px-4 text-navy-900">Total Days</th>
                                <th class="text-left py-2 px-4 text-navy-900">Completed Days</th>
                                <th class="text-left py-2 px-4 text-navy-900">Incomplete Days</th>
                                <th class="text-left py-2 px-4 text-navy-900">Days on Leave</th>
                                <th class="text-left py-2 px-4 text-navy-900">Total Hours</th>
                                <th class="text-left py-2 px-4 text-navy-900">Avg Hours/Day</th>
                                <th class="text-left py-2 px-4 text-navy-900">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statistics as $stat)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4">
                                        <p class="font-semibold text-navy-900">{{ $stat['employee']->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $stat['employee']->email }}</p>
                                    </td>
                                    <td class="py-2 px-4">{{ $stat['total_days'] }}</td>
                                    <td class="py-2 px-4">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                                            {{ $stat['completed_days'] }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($stat['incomplete_days'] > 0)
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">
                                                {{ $stat['incomplete_days'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($stat['days_on_leave'] > 0)
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">
                                                {{ $stat['days_on_leave'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 font-semibold">{{ number_format($stat['total_hours'], 2) }}h</td>
                                    <td class="py-2 px-4">{{ number_format($stat['average_hours'], 2) }}h</td>
                                    <td class="py-2 px-4">
                                        @php
                                            $completionRate = $stat['total_days'] > 0 ? ($stat['completed_days'] / $stat['total_days']) * 100 : 0;
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                <div 
                                                    class="h-2 rounded-full {{ $completionRate >= 80 ? 'bg-green-600' : ($completionRate >= 50 ? 'bg-yellow-600' : 'bg-red-600') }}"
                                                    style="width: {{ $completionRate }}%"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-semibold">{{ number_format($completionRate, 1) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600 text-center py-8">No attendance data found for the selected date range.</p>
            @endif
        </div>
    </div>
</x-app-layout>
