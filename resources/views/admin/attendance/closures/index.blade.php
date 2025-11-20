<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Office Closure Calendar</h2>
                <p class="text-sm text-gray-600 mt-1">Mark public holidays, bad weather days, or other exceptional closures.</p>
            </div>
            <a href="{{ route('admin.attendance.office-schedule.edit') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Manage Office Timings
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <strong class="block font-semibold mb-2">Please fix the following issues:</strong>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white border border-navy-900 rounded-lg p-6 space-y-6">
            <div>
                <h3 class="text-xl font-bold text-navy-900 mb-2">Add Closure</h3>
                <p class="text-sm text-gray-600">
                    Employees will not be marked absent for closures in this list. If a closure spans multiple days, set the start and end dates accordingly.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.attendance.closures.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                @csrf

                <div>
                    <label for="start_date" class="block text-sm font-semibold text-navy-900 mb-1">Start Date</label>
                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        value="{{ old('start_date') }}"
                        class="w-full border border-gray-300 rounded px-3 py-2"
                        required
                    >
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-semibold text-navy-900 mb-1">End Date</label>
                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        value="{{ old('end_date') }}"
                        class="w-full border border-gray-300 rounded px-3 py-2"
                    >
                    <p class="text-xs text-gray-500 mt-1">Optional. Leave empty for single-day closure.</p>
                </div>

                <div class="md:col-span-2">
                    <label for="reason" class="block text-sm font-semibold text-navy-900 mb-1">Reason / Notes</label>
                    <input
                        type="text"
                        name="reason"
                        id="reason"
                        value="{{ old('reason') }}"
                        placeholder="e.g. Eid Holiday, Heavy Rain, Power Outage"
                        class="w-full border border-gray-300 rounded px-3 py-2"
                    >
                </div>

                <div class="md:col-span-4 flex justify-end">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-3 rounded-lg hover:bg-navy-800 font-semibold shadow">
                        Add Closure
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-navy-900">Upcoming & Past Closures</h3>
                <span class="text-sm text-gray-500">Timezone: {{ $schedule->timezone }}</span>
            </div>

            @if ($closures->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-navy-900">
                                <th class="text-left py-2 px-4 text-navy-900">Dates</th>
                                <th class="text-left py-2 px-4 text-navy-900">Reason</th>
                                <th class="text-left py-2 px-4 text-navy-900">Duration</th>
                                <th class="text-left py-2 px-4 text-navy-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($closures as $closure)
                                @php
                                    $start = $closure->start_date->format('M d, Y');
                                    $end = $closure->end_date ? $closure->end_date->format('M d, Y') : null;
                                    $duration = $closure->end_date
                                        ? $closure->start_date->diffInDays($closure->end_date) + 1
                                        : 1;
                                @endphp
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-navy-900">
                                            {{ $start }}
                                            @if ($end)
                                                &ndash; {{ $end }}
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $closure->start_date->format('l') }}
                                            @if ($end)
                                                to {{ $closure->end_date->format('l') }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ $closure->reason ?? '—' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ $duration }} {{ Str::plural('day', $duration) }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <form method="POST" action="{{ route('admin.attendance.closures.destroy', $closure) }}" onsubmit="return confirm('Remove this closure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $closures->links() }}
                </div>
            @else
                <p class="text-gray-600 text-center py-8">No closures yet. Add your first closure above.</p>
            @endif
        </div>
    </div>
</x-app-layout>
