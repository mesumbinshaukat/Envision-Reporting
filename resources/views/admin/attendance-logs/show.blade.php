<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Attendance Log Details</h2>
            <a href="{{ route('admin.attendance-logs.index') }}" class="bg-navy-900 text-white px-4 py-2 rounded hover:bg-opacity-90">
                Back to Logs
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Log Information -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Log Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Employee</h4>
                    <p class="text-lg text-navy-900">{{ $log->employeeUser->employee->name ?? 'N/A' }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Date & Time</h4>
                    <p class="text-lg text-navy-900">{{ $log->attempted_at->format('F d, Y \a\t h:i:s A') }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Action</h4>
                    <p class="text-lg text-navy-900">{{ str_replace('_', ' ', ucwords($log->action, '_')) }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Status</h4>
                    @if(str_contains($log->action, 'success'))
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded font-semibold">Success</span>
                    @else
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded font-semibold">Failed</span>
                    @endif
                </div>

                @if($log->failure_reason)
                    <div class="col-span-2">
                        <h4 class="text-sm font-semibold text-gray-600 mb-1">Failure Reason</h4>
                        <p class="text-lg text-red-600 font-semibold">{{ str_replace('_', ' ', ucwords($log->failure_reason, '_')) }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Location Information -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Location Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Coordinates</h4>
                    @if($log->latitude && $log->longitude)
                        <p class="text-lg text-navy-900 font-mono">
                            {{ number_format($log->latitude, 6) }}, {{ number_format($log->longitude, 6) }}
                        </p>
                        <a 
                            href="https://www.google.com/maps?q={{ $log->latitude }},{{ $log->longitude }}" 
                            target="_blank"
                            class="text-blue-600 hover:underline text-sm mt-1 inline-block"
                        >
                            View on Google Maps →
                        </a>
                    @else
                        <p class="text-gray-400">Not available</p>
                    @endif
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Distance from Office</h4>
                    @if($log->distance_from_office !== null)
                        <p class="text-lg {{ $log->distance_from_office > 15 ? 'text-red-600' : 'text-green-600' }} font-semibold">
                            {{ number_format($log->distance_from_office, 2) }} meters
                        </p>
                        @if($log->distance_from_office > 15)
                            <p class="text-sm text-red-600 mt-1">⚠️ Outside allowed radius</p>
                        @else
                            <p class="text-sm text-green-600 mt-1">✓ Within allowed radius</p>
                        @endif
                    @else
                        <p class="text-gray-400">Not calculated</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Device & Network Information -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Device & Network Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @php
                    $primaryIp = $log->ip_address;
                    $ipv4 = $log->ip_address_v4 ?? (filter_var($primaryIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $primaryIp : null);
                    $ipv6 = $log->ip_address_v6 ?? (filter_var($primaryIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $primaryIp : null);
                @endphp
                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">IPv4</h4>
                    <p class="text-lg text-navy-900 font-mono break-all">{{ $ipv4 ?? 'N/A' }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">IPv6</h4>
                    <p class="text-lg text-navy-900 font-mono break-all">{{ $ipv6 ?? 'N/A' }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Device Type</h4>
                    <p class="text-lg text-navy-900">{{ $log->device_type ?? 'Unknown' }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Browser</h4>
                    <p class="text-lg text-navy-900">{{ $log->browser ?? 'Unknown' }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">Operating System</h4>
                    <p class="text-lg text-navy-900">{{ $log->os ?? 'Unknown' }}</p>
                </div>

                <div class="col-span-2">
                    <h4 class="text-sm font-semibold text-gray-600 mb-1">User Agent</h4>
                    <p class="text-sm text-gray-700 font-mono break-all bg-gray-50 p-2 rounded">
                        {{ $log->user_agent ?? 'Not available' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Related Attendance Record -->
        @if($log->attendance)
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Related Attendance Record</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-600 mb-1">Date</h4>
                        <p class="text-lg text-navy-900">{{ $log->attendance->attendance_date->format('M d, Y') }}</p>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-600 mb-1">Check In</h4>
                        <p class="text-lg text-navy-900">
                            {{ $log->attendance->check_in ? $log->attendance->check_in->format('h:i A') : 'Not checked in' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-600 mb-1">Check Out</h4>
                        <p class="text-lg text-navy-900">
                            {{ $log->attendance->check_out ? $log->attendance->check_out->format('h:i A') : 'Not checked out' }}
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('admin.attendance.show', $log->attendance) }}" class="text-blue-600 hover:underline">
                        View Full Attendance Record →
                    </a>
                </div>
            </div>
        @endif

        <!-- Additional Information -->
        @if($log->additional_info)
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Additional Information</h3>
                <pre class="bg-gray-50 p-4 rounded text-sm overflow-x-auto">{{ json_encode(json_decode($log->additional_info), JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    </div>
</x-app-layout>
