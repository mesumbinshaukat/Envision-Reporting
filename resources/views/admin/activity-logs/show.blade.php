<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Activity Log Details</h2>
                <p class="text-sm text-gray-500">Log ID: {{ $log->id }}</p>
            </div>
            <a href="{{ route('admin.activity-logs.index') }}" class="bg-navy-900 text-white px-4 py-2 rounded hover:bg-opacity-90">Back to Logs</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Summary -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Employee</h3>
                    <p class="text-lg text-navy-900">{{ $log->employee_display_name }}</p>
                    <p class="text-xs text-gray-500 mt-1">Employee User ID: {{ $log->employee_user_id ?? 'N/A' }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Admin</h3>
                    <p class="text-lg text-navy-900">{{ $log->admin?->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 mt-1">Admin ID: {{ $log->admin_id }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Timestamp</h3>
                    <p class="text-lg text-navy-900">{{ $log->occurred_at?->timezone(config('app.timezone'))->format('F d, Y h:i:s A') ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $log->occurred_at?->diffForHumans() }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Category & Action</h3>
                    <p class="text-lg text-navy-900">{{ $log->category ? Str::headline($log->category) : 'Uncategorized' }}</p>
                    <p class="text-xs text-gray-500 mt-1 font-mono bg-gray-100 px-2 py-1 rounded inline-block">{{ $log->action }}</p>
                </div>
            </div>

            @if ($log->summary)
                <div class="mt-4">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Summary</h3>
                    <p class="text-sm text-gray-800">{{ $log->summary }}</p>
                </div>
            @endif

            @if ($log->description)
                <div class="mt-4">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Description</h3>
                    <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $log->description }}</p>
                </div>
            @endif
        </div>

        <!-- Request Information -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4">Request Context</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">HTTP Method</h4>
                    <p class="text-base text-navy-900 font-semibold">{{ $log->request_method ?? 'N/A' }}</p>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Response Status</h4>
                    <p class="text-base text-navy-900 font-semibold">{{ $log->response_status ?? 'N/A' }}</p>
                </div>
                <div class="md:col-span-2">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Request Path</h4>
                    <p class="text-base text-navy-900 font-mono break-words">{{ $log->request_path ?? 'N/A' }}</p>
                </div>
                <div class="md:col-span-2">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Named Route</h4>
                    <p class="text-base text-navy-900 font-mono break-words">{{ $log->route_name ?? 'N/A' }}</p>
                </div>
                <div class="md:col-span-2">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Referer</h4>
                    <p class="text-base text-navy-900 font-mono break-words">{{ $log->referer ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Network & Device Information -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4">Network & Device Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">IPv4 Address</h4>
                    <p class="text-base text-navy-900 font-mono break-words">{{ $log->ip_address_v4 ?? $log->ip_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">IPv6 Address</h4>
                    <p class="text-base text-navy-900 font-mono break-words">{{ $log->ip_address_v6 ?? 'N/A' }}</p>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Device Type</h4>
                    <p class="text-base text-navy-900">{{ $log->device_type ?? 'Unknown' }}</p>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Browser</h4>
                    <p class="text-base text-navy-900">{{ $log->browser ?? 'Unknown' }}</p>
                </div>
                <div class="md:col-span-2">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Operating System</h4>
                    <p class="text-base text-navy-900">{{ $log->os ?? 'Unknown' }}</p>
                </div>
                <div class="md:col-span-2">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">User Agent</h4>
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded">
                        <p class="text-xs font-mono break-all text-gray-700">{{ $log->user_agent ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payloads -->
        @if ($log->request_payload || $log->metadata)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @if ($log->request_payload)
                    <div class="bg-white border border-navy-900 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-navy-900 mb-4">Request Payload</h3>
                        <pre class="text-xs bg-gray-50 border border-gray-200 rounded p-4 overflow-x-auto">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @endif

                @if ($log->metadata)
                    <div class="bg-white border border-navy-900 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-navy-900 mb-4">Metadata</h3>
                        <pre class="text-xs bg-gray-50 border border-gray-200 rounded p-4 overflow-x-auto">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
