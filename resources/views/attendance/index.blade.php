<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">My Attendance</h2>
            <a href="{{ route('attendance.fix-requests.index') }}" class="bg-navy-900 text-white px-4 py-2 rounded hover:bg-navy-800">
                View Fix Requests
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Check In/Out Card -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Today's Attendance</h3>
            
            @if($todayAttendance)
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Check-in Time</p>
                            <p class="text-lg font-semibold text-navy-900">
                                {{ $todayAttendance->check_in ? $todayAttendance->check_in->format('h:i A') : 'Not checked in' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Check-out Time</p>
                            <p class="text-lg font-semibold text-navy-900">
                                {{ $todayAttendance->check_out ? $todayAttendance->check_out->format('h:i A') : 'Not checked out' }}
                            </p>
                        </div>
                        @if($todayAttendance->formatted_work_duration)
                            <div>
                                <p class="text-sm text-gray-600">Work Duration</p>
                                <p class="text-lg font-semibold text-green-600">
                                    {{ $todayAttendance->formatted_work_duration }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-4">
                        @if(!$todayAttendance->hasCheckedOut())
                            <form method="POST" action="{{ route('attendance.check-out') }}">
                                @csrf
                                <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 font-semibold text-lg shadow-lg transition-all duration-200 hover:shadow-xl" style="background-color: #dc2626; color: white;">
                                    Check Out
                                </button>
                            </form>
                        @else
                            <p class="text-green-600 font-semibold">You have completed your attendance for today.</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-600 mb-4">You haven't checked in today.</p>
                    <form method="POST" action="{{ route('attendance.check-in') }}">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-semibold text-lg shadow-lg transition-all duration-200 hover:shadow-xl" style="background-color: #16a34a; color: white;">
                            Check In
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Attendance History -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">This Month's Attendance</h3>
            
            @if($attendances->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-navy-900">
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
                                <tr class="border-b">
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

                                        @if($attendance->pendingFixRequests->count() > 0)
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs ml-1">Fix Pending</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4">
                                        <a href="{{ route('attendance.show', $attendance) }}" class="text-navy-900 hover:underline mr-2">View</a>
                                        <a href="{{ route('attendance.fix-requests.create', $attendance) }}" class="text-blue-600 hover:underline">Request Fix</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600 text-center py-8">No attendance records for this month.</p>
            @endif
        </div>
    </div>
</x-app-layout>
