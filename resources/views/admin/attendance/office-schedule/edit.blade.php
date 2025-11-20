<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Office Timings & Working Days</h2>
            <a href="{{ route('admin.attendance.statistics') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                View Statistics
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
                <h3 class="text-xl font-bold text-navy-900 mb-2">Configure Office Timings</h3>
                <p class="text-gray-600 text-sm">
                    Define the working window for your organization. Employees can still check out after this window if shifts cross midnight—timings are used for analytics such as overtime, late check-ins, and attendance reporting.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.attendance.office-schedule.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="start_time" class="block text-sm font-semibold text-navy-900 mb-1">Shift Start Time</label>
                        <input
                            type="time"
                            name="start_time"
                            id="start_time"
                            value="{{ old('start_time', $schedule->start_time ?? App\Services\OfficeScheduleService::DEFAULT_START_TIME) }}"
                            class="w-full border border-gray-300 rounded px-3 py-2"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Employees checking in after this time are considered late.</p>
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-semibold text-navy-900 mb-1">Shift End Time</label>
                        <input
                            type="time"
                            name="end_time"
                            id="end_time"
                            value="{{ old('end_time', $schedule->end_time ?? App\Services\OfficeScheduleService::DEFAULT_END_TIME) }}"
                            class="w-full border border-gray-300 rounded px-3 py-2"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">When the shift spans midnight, set this to the following day's time (e.g. 18:00 → 02:00).</p>
                    </div>

                    <div>
                        <label for="timezone" class="block text-sm font-semibold text-navy-900 mb-1">Timezone</label>
                        <select name="timezone" id="timezone" class="w-full border border-gray-300 rounded px-3 py-2">
                            @foreach ($timezones as $timezoneOption)
                                <option value="{{ $timezoneOption }}" {{ old('timezone', $schedule->timezone) === $timezoneOption ? 'selected' : '' }}>
                                    {{ $timezoneOption }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Statistics and late/overtime calculations follow this timezone.</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-lg font-semibold text-navy-900 mb-3">Working Days</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        Select the days your office operates. Attendance on unselected days will not be counted as absent. Use this to configure weekend policies such as alternate Saturdays.
                    </p>

                    @php
                        $checkedDays = collect(old('working_days', $schedule->working_days))->map(fn ($d) => strtolower($d));
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3">
                        @foreach ($dayOptions as $day)
                            <label class="flex items-center gap-2 bg-navy-50 border border-navy-200 rounded px-3 py-2 cursor-pointer hover:bg-navy-100 transition">
                                <input
                                    type="checkbox"
                                    name="working_days[]"
                                    value="{{ $day }}"
                                    {{ $checkedDays->contains($day) ? 'checked' : '' }}
                                >
                                <span class="capitalize text-sm font-semibold text-navy-900">{{ $day }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">Tip</h4>
                    <p class="text-sm text-blue-800">
                        Use the <a href="{{ route('admin.attendance.closures.index') }}" class="underline font-semibold">closure calendar</a> to mark specific holidays, weather shutdowns, or other exceptions so employee attendance isn't penalized.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-3 rounded-lg hover:bg-navy-800 font-semibold shadow">
                        Save Office Timings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
