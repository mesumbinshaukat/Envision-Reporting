<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">My Fix Requests</h2>
            <a href="{{ route('attendance.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
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

        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Fix Requests</h3>
            
            @if($fixRequests->count() > 0)
                <div class="space-y-4">
                    @foreach($fixRequests as $fixRequest)
                        <div class="border border-gray-300 rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <p class="font-semibold text-navy-900">
                                            {{ $fixRequest->attendance->attendance_date->format('M d, Y') }}
                                        </p>
                                        @if($fixRequest->isPending())
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>
                                        @elseif($fixRequest->isApproved())
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Approved</span>
                                        @else
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Rejected</span>
                                        @endif
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 mb-2">
                                        Submitted: {{ $fixRequest->created_at->format('M d, Y h:i A') }}
                                    </p>
                                    
                                    <div class="mb-2">
                                        <p class="text-sm font-semibold text-gray-700">Reason:</p>
                                        <p class="text-gray-600">{{ Str::limit($fixRequest->reason, 150) }}</p>
                                    </div>
                                    
                                    @if($fixRequest->admin_notes)
                                        <div class="bg-gray-100 p-2 rounded mt-2">
                                            <p class="text-sm font-semibold text-gray-700">Admin Response:</p>
                                            <p class="text-sm text-gray-600">{{ Str::limit($fixRequest->admin_notes, 100) }}</p>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="ml-4">
                                    <a href="{{ route('attendance.fix-requests.show', $fixRequest) }}" class="text-navy-900 hover:underline text-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $fixRequests->links() }}
                </div>
            @else
                <p class="text-gray-600 text-center py-8">You haven't submitted any fix requests yet.</p>
            @endif
        </div>
    </div>
</x-app-layout>
