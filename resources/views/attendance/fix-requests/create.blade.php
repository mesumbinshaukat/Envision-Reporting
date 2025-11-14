<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Request Attendance Fix</h2>
            <a href="{{ route('attendance.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Attendance Details -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Attendance Record</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-600">Date</p>
                    <p class="font-semibold text-navy-900">{{ $attendance->attendance_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Check In</p>
                    <p class="font-semibold text-navy-900">
                        {{ $attendance->check_in ? $attendance->check_in->format('h:i A') : 'Not checked in' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Check Out</p>
                    <p class="font-semibold text-navy-900">
                        {{ $attendance->check_out ? $attendance->check_out->format('h:i A') : 'Not checked out' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Fix Request Form -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Submit Fix Request</h3>
            
            <form method="POST" action="{{ route('attendance.fix-requests.store') }}">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

                <div class="mb-6">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Fix Request <span class="text-red-600">*</span>
                    </label>
                    <textarea 
                        id="reason" 
                        name="reason" 
                        rows="6" 
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-navy-900 @error('reason') border-red-500 @enderror"
                        placeholder="Please explain in detail what needs to be fixed and why..."
                        required
                    >{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">Minimum 10 characters required. Be specific about what needs to be corrected.</p>
                </div>

                <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 mb-6">
                    <p class="text-sm text-yellow-800">
                        <strong>Note:</strong> Your fix request will be reviewed by an administrator. 
                        You cannot edit or delete attendance records directly. Please provide clear and accurate information.
                    </p>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-2 rounded hover:bg-navy-800">
                        Submit Request
                    </button>
                    <a href="{{ route('attendance.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
