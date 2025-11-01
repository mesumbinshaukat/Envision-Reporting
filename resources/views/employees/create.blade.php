<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Add New Employee</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('employees.store') }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-navy-900 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-navy-900 mb-1">Email *</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="marital_status" class="block text-sm font-semibold text-navy-900 mb-1">Marital Status</label>
                <select name="marital_status" id="marital_status" class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select...</option>
                    <option value="Single" {{ old('marital_status') == 'Single' ? 'selected' : '' }}>Single</option>
                    <option value="Married" {{ old('marital_status') == 'Married' ? 'selected' : '' }}>Married</option>
                    <option value="Other" {{ old('marital_status') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div>
                <label for="primary_contact" class="block text-sm font-semibold text-navy-900 mb-1">Primary Contact *</label>
                <input type="text" name="primary_contact" id="primary_contact" value="{{ old('primary_contact') }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="secondary_contact" class="block text-sm font-semibold text-navy-900 mb-1">Secondary Contact</label>
                <input type="text" name="secondary_contact" id="secondary_contact" value="{{ old('secondary_contact') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="role" class="block text-sm font-semibold text-navy-900 mb-1">Role *</label>
                <input type="text" name="role" id="role" value="{{ old('role') }}" required placeholder="e.g., Sales, Developer, Manager" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="employment_type" class="block text-sm font-semibold text-navy-900 mb-1">Employment Type *</label>
                <select name="employment_type" id="employment_type" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select...</option>
                    <option value="Onsite" {{ old('employment_type') == 'Onsite' ? 'selected' : '' }}>Onsite</option>
                    <option value="Project-Based (Freelancer) - Remote" {{ old('employment_type') == 'Project-Based (Freelancer) - Remote' ? 'selected' : '' }}>Project-Based (Freelancer) - Remote</option>
                    <option value="Contract Arbitrage" {{ old('employment_type') == 'Contract Arbitrage' ? 'selected' : '' }}>Contract Arbitrage</option>
                    <option value="Monthly/Annual Contract" {{ old('employment_type') == 'Monthly/Annual Contract' ? 'selected' : '' }}>Monthly/Annual Contract</option>
                    <option value="Hybrid" {{ old('employment_type') == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="joining_date" class="block text-sm font-semibold text-navy-900 mb-1">Joining Date</label>
                    <input type="date" name="joining_date" id="joining_date" value="{{ old('joining_date') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
                </div>

                <div>
                    <label for="last_date" class="block text-sm font-semibold text-navy-900 mb-1">Last Date</label>
                    <input type="date" name="last_date" id="last_date" value="{{ old('last_date') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
                </div>
            </div>

            <div>
                <label for="salary" class="block text-sm font-semibold text-navy-900 mb-1">Monthly Salary *</label>
                <input type="number" name="salary" id="salary" value="{{ old('salary') }}" required step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="commission_rate" class="block text-sm font-semibold text-navy-900 mb-1">Commission Rate (%)</label>
                <input type="number" name="commission_rate" id="commission_rate" value="{{ old('commission_rate', 0) }}" step="0.01" min="0" max="100" class="w-full px-4 py-2 border border-navy-900 rounded">
                <p class="text-sm text-gray-600 mt-1">Enter commission percentage (e.g., 5 for 5%)</p>
            </div>

            <!-- Create User Account Checkbox -->
            <div class="border-t border-gray-300 pt-4">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="create_user_account" id="create_user_account" value="1" class="mr-2 w-4 h-4" onchange="toggleUserPasswordField()">
                    <span class="text-sm font-semibold text-navy-900">Create employee user account (allows employee to login)</span>
                </label>
            </div>

            <!-- Password Field (Hidden by default) -->
            <div id="password_field" style="display: none;">
                <label for="user_password" class="block text-sm font-semibold text-navy-900 mb-1">User Password *</label>
                <input type="password" name="user_password" id="user_password" minlength="8" class="w-full px-4 py-2 border border-navy-900 rounded" disabled>
                <p class="text-sm text-gray-600 mt-1">Minimum 8 characters. Employee will use their email to login.</p>
            </div>

            <div class="flex gap-4">
                <button type="submit" id="submit_btn" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Employee</button>
                <a href="{{ route('employees.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function toggleUserPasswordField() {
            const checkbox = document.getElementById('create_user_account');
            const passwordField = document.getElementById('password_field');
            const passwordInput = document.getElementById('user_password');
            const submitBtn = document.getElementById('submit_btn');

            if (checkbox.checked) {
                passwordField.style.display = 'block';
                passwordInput.disabled = false;
                passwordInput.required = true;
                submitBtn.disabled = true; // Disable until password is entered
                
                // Enable submit when password is typed
                passwordInput.addEventListener('input', function() {
                    submitBtn.disabled = this.value.length < 8;
                });
            } else {
                passwordField.style.display = 'none';
                passwordInput.disabled = true;
                passwordInput.required = false;
                passwordInput.value = '';
                submitBtn.disabled = false;
            }
        }
    </script>
</x-app-layout>
