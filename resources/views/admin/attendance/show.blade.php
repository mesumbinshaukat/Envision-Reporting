<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Attendance Details</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.attendance.edit', $attendance) }}" class="bg-navy-900 text-white px-4 py-2 rounded hover:bg-navy-800">
                    Edit
                </a>
                <a href="{{ route('admin.attendance.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Attendance Information -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Attendance Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm text-gray-600">Employee</p>
                    <p class="text-lg font-semibold text-navy-900">{{ $attendance->employeeUser->name }}</p>
                    <p class="text-sm text-gray-500">{{ $attendance->employeeUser->email }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Date</p>
                    <p class="text-lg font-semibold text-navy-900">{{ $attendance->attendance_date->format('F d, Y') }}</p>
                    <p class="text-sm text-gray-500">{{ $attendance->attendance_date->format('l') }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Status</p>
                    <p class="text-lg font-semibold">
                        @if($attendance->hasCheckedOut())
                            <span class="text-green-600">Complete</span>
                        @elseif($attendance->hasCheckedIn())
                            <span class="text-yellow-600">Checked In Only</span>
                        @else
                            <span class="text-gray-600">Incomplete</span>
                        @endif
                    </p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Check-in Time</p>
                    <p class="text-lg font-semibold text-navy-900">
                        {{ $attendance->check_in ? $attendance->check_in->format('h:i A') : 'Not checked in' }}
                    </p>
                    @if($attendance->check_in)
                        <p class="text-sm text-gray-500">{{ $attendance->check_in->format('F d, Y') }}</p>
                    @endif
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Check-out Time</p>
                    <p class="text-lg font-semibold text-navy-900">
                        {{ $attendance->check_out ? $attendance->check_out->format('h:i A') : 'Not checked out' }}
                    </p>
                    @if($attendance->check_out)
                        <p class="text-sm text-gray-500">{{ $attendance->check_out->format('F d, Y') }}</p>
                    @endif
                </div>
                
                @if($attendance->formatted_work_duration)
                    <div>
                        <p class="text-sm text-gray-600">Work Duration</p>
                        <p class="text-lg font-semibold text-green-600">{{ $attendance->formatted_work_duration }}</p>
                        <p class="text-sm text-gray-500">{{ number_format($attendance->work_duration, 2) }} hours</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Fix Requests -->
        @if($attendance->fixRequests->count() > 0)
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Related Fix Requests</h3>
                
                <div class="space-y-4">
                    @foreach($attendance->fixRequests as $fixRequest)
                        <div class="border border-gray-300 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-semibold text-navy-900">
                                        Status: 
                                        @if($fixRequest->isPending())
                                            <span class="text-yellow-600">Pending</span>
                                        @elseif($fixRequest->isApproved())
                                            <span class="text-green-600">Approved</span>
                                        @else
                                            <span class="text-red-600">Rejected</span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-600">Submitted: {{ $fixRequest->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <a href="{{ route('admin.attendance.fix-requests.show', $fixRequest) }}" class="text-blue-600 hover:underline text-sm">
                                    View Details
                                </a>
                            </div>
                            
                            <div class="mb-2">
                                <p class="text-sm font-semibold text-gray-700">Reason:</p>
                                <p class="text-gray-600">{{ Str::limit($fixRequest->reason, 200) }}</p>
                            </div>
                            
                            @if($fixRequest->admin_notes)
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-sm font-semibold text-gray-700">Admin Notes:</p>
                                    <p class="text-gray-600">{{ $fixRequest->admin_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Actions</h3>
            
            <div class="flex gap-4">
                <a href="{{ route('admin.attendance.edit', $attendance) }}" class="bg-navy-900 text-white px-6 py-2 rounded hover:bg-navy-800">
                    Edit Attendance
                </a>
                
                <form method="POST" action="{{ route('admin.attendance.destroy', $attendance) }}" onsubmit="return confirm('Are you sure you want to delete this attendance record? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700">
                        Delete Attendance
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
