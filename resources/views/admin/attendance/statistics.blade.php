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

        <!-- Office Schedule Overview -->
        <div class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-navy-900">Reporting Window</h3>
                    <p class="text-sm text-gray-600">{{ $startDate->format('M d, Y') }} &ndash; {{ $endDate->format('M d, Y') }}</p>
                </div>
                <div class="bg-navy-50 border border-navy-200 rounded-lg px-4 py-3 grid grid-cols-2 gap-4 md:gap-8 text-sm">
                    <div>
                        <p class="font-semibold text-navy-900">Office Timings</p>
                        <p class="text-gray-600">
                            {{ $schedule->start_time }} &ndash; {{ $schedule->end_time }}
                            @if($schedule->start_time && $schedule->end_time && 
                                \Carbon\Carbon::parse($schedule->end_time)->lessThanOrEqualTo(\Carbon\Carbon::parse($schedule->start_time)))
                                <span class="block text-xs text-orange-600">Crosses midnight</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-navy-900">Timezone</p>
                        <p class="text-gray-600">{{ $schedule->timezone }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
                <p class="font-semibold text-blue-900 mb-1">Configured Working Days</p>
                <p class="capitalize">{{ $workingDaysCollection->map(fn($date) => $date->translatedFormat('D'))->unique()->implode(', ') ?: 'None' }}</p>
                @if($closures->isNotEmpty())
                    <p class="mt-2 text-blue-700">
                        {{ $closures->count() }} closure{{ $closures->count() === 1 ? '' : 's' }} applied in this range.
                    </p>
                @endif
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">
                Summary Metrics
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
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Non-Working Days</h4>
                    <p class="text-3xl font-bold text-red-600">{{ $totalWorkingDays }}</p>
                    <p class="text-xs text-red-500 mt-1">Working days after removing configured closures.</p>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Total Hours</h4>
                    <p class="text-3xl font-bold text-purple-600">{{ number_format($statistics->sum('total_hours'), 1) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Late Hours</h4>
                    <p class="text-3xl font-bold text-orange-600">{{ number_format($statistics->sum('late_hours'), 1) }}</p>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Overtime Hours</h4>
                    <p class="text-3xl font-bold text-emerald-600">{{ number_format($statistics->sum('overtime_hours'), 1) }}</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Days on Leave</h4>
                    <p class="text-3xl font-bold text-slate-600">{{ $statistics->sum('days_on_leave') }}</p>
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
                                <th class="text-left py-2 px-4 text-navy-900">Late (hrs)</th>
                                <th class="text-left py-2 px-4 text-navy-900">Overtime (hrs)</th>
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
                                            <span class="text-gray-500 text-sm">0</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($stat['days_on_leave'] > 0)
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                                {{ $stat['days_on_leave'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 text-sm">0</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4">{{ number_format($stat['total_hours'], 1) }}</td>
                                    <td class="py-2 px-4">{{ number_format($stat['average_hours'], 1) }}</td>
                                    <td class="py-2 px-4">{{ number_format($stat['late_hours'], 1) }}</td>
                                    <td class="py-2 px-4">{{ number_format($stat['overtime_hours'], 1) }}</td>
                                    <td class="py-2 px-4">
                                        @php
                                            $completionRate = $stat['total_days'] > 0
                                                ? round(($stat['completed_days'] / $stat['total_days']) * 100, 1)
                                                : 0;
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
                <p class="text-gray-600 text-center py-6">No statistics available for the selected range.</p>
            @endif
        </div>

        @if ($closures->isNotEmpty())
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Closures Applied</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($closures as $closure)
                        @php
                            $duration = $closure->end_date
                                ? $closure->start_date->diffInDays($closure->end_date) + 1
                                : 1;
                        @endphp
                        <div class="border border-navy-200 rounded-lg p-4 bg-navy-50">
                            <p class="font-semibold text-navy-900">
                                {{ $closure->start_date->format('M d, Y') }}
                                @if ($closure->end_date)
                                    &ndash; {{ $closure->end_date->format('M d, Y') }}
                                @endif
                            </p>
                            <p class="text-sm text-gray-600">{{ $closure->reason ?? 'General Closure' }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $duration }} {{ Str::plural('day', $duration) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
