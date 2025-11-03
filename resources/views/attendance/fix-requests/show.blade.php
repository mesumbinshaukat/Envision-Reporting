<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Fix Request Details</h2>
            <a href="{{ route('attendance.fix-requests.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Back to Requests
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Fix Request Status -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-bold text-navy-900">Request Status</h3>
                <div>
                    @if($fixRequest->isPending())
                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded text-sm font-semibold">Pending Review</span>
                    @elseif($fixRequest->isApproved())
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded text-sm font-semibold">Approved</span>
                    @else
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded text-sm font-semibold">Rejected</span>
                    @endif
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Submitted On</p>
                    <p class="font-semibold text-navy-900">{{ $fixRequest->created_at->format('M d, Y h:i A') }}</p>
                </div>
                
                @if($fixRequest->processed_at)
                    <div>
                        <p class="text-sm text-gray-600">Processed On</p>
                        <p class="font-semibold text-navy-900">{{ $fixRequest->processed_at->format('M d, Y h:i A') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Attendance Information -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Related Attendance Record</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Date</p>
                    <p class="font-semibold text-navy-900">{{ $fixRequest->attendance->attendance_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Check In</p>
                    <p class="font-semibold text-navy-900">
                        {{ $fixRequest->attendance->check_in ? $fixRequest->attendance->check_in->format('h:i A') : 'Not checked in' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Check Out</p>
                    <p class="font-semibold text-navy-900">
                        {{ $fixRequest->attendance->check_out ? $fixRequest->attendance->check_out->format('h:i A') : 'Not checked out' }}
                    </p>
                </div>
                @if($fixRequest->attendance->formatted_work_duration)
                    <div>
                        <p class="text-sm text-gray-600">Duration</p>
                        <p class="font-semibold text-green-600">{{ $fixRequest->attendance->formatted_work_duration }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Request Details -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Your Request</h3>
            
            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Reason:</p>
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $fixRequest->reason }}</p>
                </div>
            </div>
        </div>

        <!-- Admin Response -->
        @if($fixRequest->admin_notes || !$fixRequest->isPending())
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Admin Response</h3>
                
                @if($fixRequest->processedBy)
                    <p class="text-sm text-gray-600 mb-4">
                        Processed by <strong>{{ $fixRequest->processedBy->name }}</strong> on {{ $fixRequest->processed_at->format('M d, Y h:i A') }}
                    </p>
                @endif
                
                @if($fixRequest->admin_notes)
                    <div class="bg-gray-50 p-4 rounded border border-gray-200">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $fixRequest->admin_notes }}</p>
                    </div>
                @else
                    <p class="text-gray-500 italic">No admin notes provided.</p>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
