<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Fix Request Details</h2>
            <a href="{{ route('admin.attendance.fix-requests.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Back to Requests
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
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

        <!-- Request Status -->
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
                    <p class="text-sm text-gray-600">Employee</p>
                    <p class="font-semibold text-navy-900">{{ $fixRequest->employeeUser->name }}</p>
                    <p class="text-sm text-gray-500">{{ $fixRequest->employeeUser->email }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Submitted On</p>
                    <p class="font-semibold text-navy-900">{{ $fixRequest->created_at->format('M d, Y h:i A') }}</p>
                </div>
                
                @if($fixRequest->processed_at)
                    <div>
                        <p class="text-sm text-gray-600">Processed By</p>
                        <p class="font-semibold text-navy-900">{{ $fixRequest->processedBy->name }}</p>
                    </div>
                    
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
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
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

            <a href="{{ route('admin.attendance.show', $fixRequest->attendance) }}" class="text-blue-600 hover:underline text-sm">
                View Full Attendance Record
            </a>
        </div>

        <!-- Request Details -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Employee's Request</h3>
            
            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Reason:</p>
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $fixRequest->reason }}</p>
                </div>
            </div>
        </div>

        <!-- Process Request Form -->
        @if($fixRequest->isPending())
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Process Request</h3>
                
                <form method="POST" action="{{ route('admin.attendance.fix-requests.process', $fixRequest) }}">
                    @csrf

                    <div class="mb-6">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Decision <span class="text-red-600">*</span>
                        </label>
                        <select 
                            name="status" 
                            id="status" 
                            class="w-full border border-gray-300 rounded px-3 py-2 @error('status') border-red-500 @enderror"
                            required
                        >
                            <option value="">Select Decision</option>
                            <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approve</option>
                            <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Reject</option>
                        </select>
                        @error('status')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Admin Notes (Optional)
                        </label>
                        <textarea 
                            id="admin_notes" 
                            name="admin_notes" 
                            rows="4" 
                            class="w-full border border-gray-300 rounded px-4 py-2 @error('admin_notes') border-red-500 @enderror"
                            placeholder="Add any notes or comments for the employee..."
                        >{{ old('admin_notes') }}</textarea>
                        @error('admin_notes')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="bg-blue-50 border border-blue-300 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> After processing this request, you can edit the attendance record if needed.
                        </p>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="bg-navy-900 text-white px-6 py-2 rounded hover:bg-navy-800">
                            Submit Decision
                        </button>
                        <a href="{{ route('admin.attendance.edit', $fixRequest->attendance) }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                            Edit Attendance Now
                        </a>
                    </div>
                </form>
            </div>
        @else
            <!-- Admin Response (Already Processed) -->
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Admin Response</h3>
                
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
