<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Attendance Fix Requests</h2>
            <a href="{{ route('admin.attendance.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Back to Attendance
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

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Pending Requests</h3>
                <p class="text-3xl font-bold text-yellow-600">{{ $fixRequests->where('status', 'pending')->count() }}</p>
            </div>
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Approved</h3>
                <p class="text-3xl font-bold text-green-600">{{ $fixRequests->where('status', 'approved')->count() }}</p>
            </div>
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Rejected</h3>
                <p class="text-3xl font-bold text-red-600">{{ $fixRequests->where('status', 'rejected')->count() }}</p>
            </div>
        </div>

        <!-- Fix Requests List -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">All Fix Requests</h3>
            
            @if($fixRequests->count() > 0)
                <div class="space-y-4">
                    @foreach($fixRequests as $fixRequest)
                        <div class="border border-gray-300 rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <p class="font-semibold text-navy-900">
                                            {{ $fixRequest->employeeUser->name }}
                                        </p>
                                        <span class="text-gray-400">•</span>
                                        <p class="text-sm text-gray-600">
                                            {{ $fixRequest->attendance->attendance_date->format('M d, Y') }}
                                        </p>
                                        @if($fixRequest->isPending())
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-semibold">Pending</span>
                                        @elseif($fixRequest->isApproved())
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">Approved</span>
                                        @else
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">Rejected</span>
                                        @endif
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 mb-2">
                                        Submitted: {{ $fixRequest->created_at->format('M d, Y h:i A') }}
                                    </p>
                                    
                                    <div class="mb-2">
                                        <p class="text-sm font-semibold text-gray-700">Reason:</p>
                                        <p class="text-gray-600">{{ Str::limit($fixRequest->reason, 150) }}</p>
                                    </div>
                                    
                                    <div class="text-sm text-gray-600">
                                        <p><strong>Check-in:</strong> {{ $fixRequest->attendance->check_in ? $fixRequest->attendance->check_in->format('h:i A') : 'Not checked in' }}</p>
                                        <p><strong>Check-out:</strong> {{ $fixRequest->attendance->check_out ? $fixRequest->attendance->check_out->format('h:i A') : 'Not checked out' }}</p>
                                    </div>
                                    
                                    @if($fixRequest->processedBy)
                                        <p class="text-xs text-gray-500 mt-2">
                                            Processed by {{ $fixRequest->processedBy->name }} on {{ $fixRequest->processed_at->format('M d, Y h:i A') }}
                                        </p>
                                    @endif
                                </div>
                                
                                <div class="ml-4 flex flex-col gap-2">
                                    <a href="{{ route('admin.attendance.fix-requests.show', $fixRequest) }}" class="text-blue-600 hover:underline text-sm">
                                        View Details
                                    </a>
                                    @if($fixRequest->isPending())
                                        <a href="{{ route('admin.attendance.edit', $fixRequest->attendance) }}" class="text-navy-900 hover:underline text-sm">
                                            Edit Attendance
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $fixRequests->links() }}
                </div>
            @else
                <p class="text-gray-600 text-center py-8">No fix requests found.</p>
            @endif
        </div>
    </div>
</x-app-layout>
