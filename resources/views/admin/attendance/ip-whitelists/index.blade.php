<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Employee IP Whitelists</h2>
            <a href="{{ route('admin.attendance.index') }}" class="text-sm text-navy-900 hover:underline">&larr; Back to Attendance</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
                <p class="font-semibold">Please fix the following issues:</p>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white border border-navy-200 rounded-lg shadow-sm p-6 space-y-6">
            <h3 class="text-xl font-bold text-navy-900">Add Whitelisted IP</h3>
            <form method="POST" action="{{ route('admin.attendance.ip-whitelists.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label for="employee_id" class="block text-sm font-semibold text-navy-900 mb-1">Employee</label>
                    <select name="employee_id" id="employee_id" class="w-full border border-navy-200 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-navy-300" required>
                        <option value="">Select employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id', request('employee_id')) == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                                @if ($employee->employeeUser)
                                    &mdash; {{ $employee->employeeUser->email }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="ip_address" class="block text-sm font-semibold text-navy-900 mb-1">IP Address</label>
                    <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address') }}" class="w-full border border-navy-200 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-navy-300" placeholder="e.g. 203.0.113.5" required>
                    <p class="text-xs text-gray-500 mt-1">Supports IPv4 and IPv6.</p>
                </div>

                <div>
                    <label for="label" class="block text-sm font-semibold text-navy-900 mb-1">Label (optional)</label>
                    <input type="text" name="label" id="label" value="{{ old('label') }}" class="w-full border border-navy-200 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-navy-300" placeholder="Home WiFi">
                </div>

                <div class="md:col-span-4 flex justify-end">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-2 rounded shadow hover:bg-navy-800 transition">
                        Add IP
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-navy-200 rounded-lg shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-navy-900">Whitelisted IPs</h3>
                <form method="GET" action="{{ route('admin.attendance.ip-whitelists.index') }}" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label for="filter_employee_id" class="block text-xs font-semibold text-navy-900 mb-1">Employee</label>
                        <select name="employee_id" id="filter_employee_id" class="border border-navy-200 rounded px-3 py-2 text-sm">
                            <option value="">All employees</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="search_ip" class="block text-xs font-semibold text-navy-900 mb-1">IP Address</label>
                        <input type="text" name="search_ip" id="search_ip" value="{{ request('search_ip') }}" class="border border-navy-200 rounded px-3 py-2 text-sm" placeholder="Search">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-navy-900 text-white px-4 py-2 rounded text-sm">Filter</button>
                        <a href="{{ route('admin.attendance.ip-whitelists.index') }}" class="bg-gray-200 text-navy-900 px-4 py-2 rounded text-sm">Reset</a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100 text-left">
                        <tr>
                            <th class="px-4 py-2 text-sm font-semibold text-navy-900">Employee</th>
                            <th class="px-4 py-2 text-sm font-semibold text-navy-900">IP Address</th>
                            <th class="px-4 py-2 text-sm font-semibold text-navy-900">Label</th>
                            <th class="px-4 py-2 text-sm font-semibold text-navy-900">Created</th>
                            <th class="px-4 py-2 text-sm font-semibold text-navy-900">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($whitelists as $whitelist)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="font-semibold">{{ $whitelist->employee->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $whitelist->employee->employeeUser->email ?? 'No login' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-mono">
                                    <span class="inline-flex items-center gap-2">
                                        <span>{{ $whitelist->ip_address }}</span>
                                        <span class="text-xs uppercase bg-navy-100 text-navy-900 px-2 py-0.5 rounded">{{ strtoupper($whitelist->ip_version) }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $whitelist->label ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div>{{ $whitelist->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $whitelist->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <form method="POST" action="{{ route('admin.attendance.ip-whitelists.destroy', $whitelist) }}" onsubmit="return confirm('Remove this IP from the whitelist?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500 text-sm">No whitelisted IPs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $whitelists->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
