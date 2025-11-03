<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Edit Attendance Record</h2>
            <a href="{{ route('admin.attendance.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <form method="POST" action="{{ route('admin.attendance.update', $attendance) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                        <input 
                            type="text" 
                            value="{{ $attendance->employeeUser->name }}" 
                            class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100"
                            disabled
                        >
                        <p class="text-sm text-gray-500 mt-1">Employee cannot be changed</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="attendance_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Attendance Date <span class="text-red-600">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="attendance_date" 
                            id="attendance_date" 
                            value="{{ old('attendance_date', $attendance->attendance_date->format('Y-m-d')) }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 @error('attendance_date') border-red-500 @enderror"
                            required
                        >
                        @error('attendance_date')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="check_in" class="block text-sm font-medium text-gray-700 mb-2">
                            Check-in Time
                        </label>
                        <input 
                            type="datetime-local" 
                            name="check_in" 
                            id="check_in" 
                            value="{{ old('check_in', $attendance->check_in ? $attendance->check_in->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 @error('check_in') border-red-500 @enderror"
                        >
                        @error('check_in')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="check_out" class="block text-sm font-medium text-gray-700 mb-2">
                            Check-out Time
                        </label>
                        <input 
                            type="datetime-local" 
                            name="check_out" 
                            id="check_out" 
                            value="{{ old('check_out', $attendance->check_out ? $attendance->check_out->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 @error('check_out') border-red-500 @enderror"
                        >
                        @error('check_out')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 mt-1">Must be after check-in time</p>
                    </div>
                </div>

                <div class="mt-6 flex gap-4">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-2 rounded hover:bg-navy-800">
                        Update Attendance
                    </button>
                    <a href="{{ route('admin.attendance.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Convert datetime-local to proper format before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const checkIn = document.getElementById('check_in');
            const checkOut = document.getElementById('check_out');
            
            if (checkIn.value) {
                const checkInDate = new Date(checkIn.value);
                checkIn.value = checkInDate.toISOString().slice(0, 19).replace('T', ' ');
            }
            
            if (checkOut.value) {
                const checkOutDate = new Date(checkOut.value);
                checkOut.value = checkOutDate.toISOString().slice(0, 19).replace('T', ' ');
            }
        });
    </script>
</x-app-layout>
