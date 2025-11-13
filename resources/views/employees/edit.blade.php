<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Edit Employee</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('employees.update', $employee) }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-semibold text-navy-900 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $employee->name) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-navy-900 mb-1">Email *</label>
                <input type="email" name="email" id="email" value="{{ old('email', $employee->email) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="marital_status" class="block text-sm font-semibold text-navy-900 mb-1">Marital Status</label>
                <select name="marital_status" id="marital_status" class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select...</option>
                    <option value="Single" {{ old('marital_status', $employee->marital_status) == 'Single' ? 'selected' : '' }}>Single</option>
                    <option value="Married" {{ old('marital_status', $employee->marital_status) == 'Married' ? 'selected' : '' }}>Married</option>
                    <option value="Other" {{ old('marital_status', $employee->marital_status) == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div>
                <label for="primary_contact" class="block text-sm font-semibold text-navy-900 mb-1">Primary Contact *</label>
                <input type="text" name="primary_contact" id="primary_contact" value="{{ old('primary_contact', $employee->primary_contact) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="secondary_contact" class="block text-sm font-semibold text-navy-900 mb-1">Secondary Contact</label>
                <input type="text" name="secondary_contact" id="secondary_contact" value="{{ old('secondary_contact', $employee->secondary_contact) }}" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="role" class="block text-sm font-semibold text-navy-900 mb-1">Role *</label>
                <input type="text" name="role" id="role" value="{{ old('role', $employee->role) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="employment_type" class="block text-sm font-semibold text-navy-900 mb-1">Employment Type *</label>
                <select name="employment_type" id="employment_type" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select...</option>
                    <option value="Onsite" {{ old('employment_type', $employee->employment_type) == 'Onsite' ? 'selected' : '' }}>Onsite</option>
                    <option value="Project-Based (Freelancer) - Remote" {{ old('employment_type', $employee->employment_type) == 'Project-Based (Freelancer) - Remote' ? 'selected' : '' }}>Project-Based (Freelancer) - Remote</option>
                    <option value="Contract Arbitrage" {{ old('employment_type', $employee->employment_type) == 'Contract Arbitrage' ? 'selected' : '' }}>Contract Arbitrage</option>
                    <option value="Monthly/Annual Contract" {{ old('employment_type', $employee->employment_type) == 'Monthly/Annual Contract' ? 'selected' : '' }}>Monthly/Annual Contract</option>
                    <option value="Hybrid" {{ old('employment_type', $employee->employment_type) == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="joining_date" class="block text-sm font-semibold text-navy-900 mb-1">Joining Date</label>
                    <input type="date" name="joining_date" id="joining_date" value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}" class="w-full px-4 py-2 border border-navy-900 rounded">
                </div>

                <div>
                    <label for="last_date" class="block text-sm font-semibold text-navy-900 mb-1">Last Date</label>
                    <input type="date" name="last_date" id="last_date" value="{{ old('last_date', $employee->last_date?->format('Y-m-d')) }}" class="w-full px-4 py-2 border border-navy-900 rounded">
                </div>
            </div>

            <div>
                <label for="currency_id" class="block text-sm font-semibold text-navy-900 mb-1">Salary Currency *</label>
                <select name="currency_id" id="currency_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ (old('currency_id', $employee->currency_id ?? $baseCurrency->id) == $currency->id) ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name }} ({{ $currency->symbol }})
                            @if($currency->is_base) - BASE @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="salary" class="block text-sm font-semibold text-navy-900 mb-1">Monthly Salary *</label>
                <input type="number" name="salary" id="salary" value="{{ old('salary', $employee->salary) }}" required step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="commission_rate" class="block text-sm font-semibold text-navy-900 mb-1">Commission Rate (%)</label>
                <input type="number" name="commission_rate" id="commission_rate" value="{{ old('commission_rate', $employee->commission_rate) }}" step="0.01" min="0" max="100" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <!-- Geolocation Required Checkbox -->
            <div class="border-t border-gray-300 pt-4">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="geolocation_required" id="geolocation_required" value="1" {{ old('geolocation_required', $employee->geolocation_required) ? 'checked' : '' }} class="mr-2 w-4 h-4">
                    <span class="text-sm font-semibold text-navy-900">📍 Require geolocation for attendance</span>
                </label>
                <p class="text-xs text-gray-600 mt-1 ml-6">
                    When enabled, employee must be within office radius to check in/out. 
                    Disable for remote employees who work from anywhere.
                </p>
            </div>

            <div class="flex gap-4 mt-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Update Employee</button>
                @if(!$employee->employeeUser)
                    <button type="button" onclick="openEmployeeUserModal()" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">Create User Account</button>
                @else
                    <span class="px-6 py-2 bg-gray-200 text-gray-600 rounded">User Account Active</span>
                @endif
                <a href="{{ route('employees.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>

        <!-- Employee User Creation Modal -->
        <div id="employeeUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Create Employee User Account</h3>
                
                <form method="POST" action="{{ route('employee-users.store', $employee) }}" id="employeeUserForm">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="user_email" class="block text-sm font-semibold text-navy-900 mb-1">Email *</label>
                        <input type="email" name="email" id="user_email" value="{{ $employee->email }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
                        <p class="text-xs text-gray-600 mt-1">Default is employee's email. You can change it.</p>
                    </div>

                    <div class="mb-4">
                        <label for="user_password" class="block text-sm font-semibold text-navy-900 mb-1">Password *</label>
                        <input type="password" name="password" id="user_password" required minlength="8" class="w-full px-4 py-2 border border-navy-900 rounded">
                        <p class="text-xs text-gray-600 mt-1">Minimum 8 characters</p>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Account</button>
                        <button type="button" onclick="closeEmployeeUserModal()" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEmployeeUserModal() {
            document.getElementById('employeeUserModal').classList.remove('hidden');
            document.getElementById('employeeUserModal').classList.add('flex');
        }

        function closeEmployeeUserModal() {
            document.getElementById('employeeUserModal').classList.add('hidden');
            document.getElementById('employeeUserModal').classList.remove('flex');
        }

        // Close modal when clicking outside
        document.getElementById('employeeUserModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEmployeeUserModal();
            }
        });
    </script>
</x-app-layout>
