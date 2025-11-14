<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Attendance Details</h2>
            <a href="{{ route('attendance.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Back to Attendance
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Attendance Information -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Attendance Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600">Date</p>
                    <p class="text-lg font-semibold text-navy-900">{{ $attendance->attendance_date->format('F d, Y') }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Day</p>
                    <p class="text-lg font-semibold text-navy-900">{{ $attendance->attendance_date->format('l') }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Check-in Time</p>
                    <p class="text-lg font-semibold text-navy-900">
                        {{ $attendance->check_in ? $attendance->check_in->format('h:i A') : 'Not checked in' }}
                    </p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Check-out Time</p>
                    <p class="text-lg font-semibold text-navy-900">
                        {{ $attendance->check_out ? $attendance->check_out->format('h:i A') : 'Not checked out' }}
                    </p>
                </div>
                
                @if($attendance->formatted_work_duration)
                    <div>
                        <p class="text-sm text-gray-600">Work Duration</p>
                        <p class="text-lg font-semibold text-green-600">{{ $attendance->formatted_work_duration }}</p>
                    </div>
                @endif
                
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
            </div>

            <div class="mt-6">
                <a href="{{ route('attendance.fix-requests.create', $attendance) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Request Fix
                </a>
            </div>
        </div>

        <!-- Fix Requests -->
        @if($attendance->fixRequests->count() > 0)
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Fix Requests</h3>
                
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
                            </div>
                            
                            <div class="mb-2">
                                <p class="text-sm font-semibold text-gray-700">Reason:</p>
                                <p class="text-gray-600">{{ $fixRequest->reason }}</p>
                            </div>
                            
                            @if($fixRequest->admin_notes)
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-sm font-semibold text-gray-700">Admin Notes:</p>
                                    <p class="text-gray-600">{{ $fixRequest->admin_notes }}</p>
                                    @if($fixRequest->processedBy)
                                        <p class="text-xs text-gray-500 mt-1">
                                            Processed by {{ $fixRequest->processedBy->name }} on {{ $fixRequest->processed_at->format('M d, Y h:i A') }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
