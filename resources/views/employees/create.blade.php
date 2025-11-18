<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Add New Employee</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('employees.store') }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf

            @php
                $geoModeDefault = \App\Models\Employee::GEO_MODE_DISABLED;
                $oldGeolocationMode = old('geolocation_mode', $geoModeDefault);
                $initialIpWhitelists = collect(old('ip_whitelists', []))
                    ->filter(fn ($entry) => is_array($entry) && !empty($entry['ip_address']))
                    ->values()
                    ->map(fn ($entry) => [
                        'ip_address' => $entry['ip_address'],
                        'label' => $entry['label'] ?? '',
                    ]);

                $ipWhitelistError = $errors->first('ip_whitelists')
                    ?? collect($errors->get('ip_whitelists.*.ip_address'))->first();
            @endphp

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
                <label for="currency_id" class="block text-sm font-semibold text-navy-900 mb-1">Salary Currency *</label>
                <select name="currency_id" id="currency_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ (old('currency_id', $baseCurrency->id ?? null) == $currency->id) ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name }} ({{ $currency->symbol }})
                            @if($currency->is_base) - BASE @endif
                        </option>
                    @endforeach
                </select>
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
                    <input type="checkbox" name="create_user_account" id="create_user_account" value="1" class="mr-2 w-4 h-4" onchange="toggleUserAccountFields()" {{ old('create_user_account') ? 'checked' : '' }}>
                    <span class="text-sm font-semibold text-navy-900">Create employee user account (allows employee to login)</span>
                </label>
            </div>

            <!-- Password Field (Hidden by default) -->
            <div id="password_field" class="space-y-1" style="display: none;">
                <label for="user_password" class="block text-sm font-semibold text-navy-900 mb-1">User Password *</label>
                <input type="password" name="user_password" id="user_password" minlength="8" class="w-full px-4 py-2 border border-navy-900 rounded" disabled>
                <p class="text-sm text-gray-600 mt-1">Minimum 8 characters. Employee will use their email to login.</p>
            </div>

            <!-- Geolocation Mode + IP Whitelist (visible only when account is created) -->
            <div id="geolocation_section" class="border-t border-gray-300 pt-4 space-y-4" style="display: none;">
                <div>
                    <label for="geolocation_mode" class="block text-sm font-semibold text-navy-900 mb-1">Attendance Geolocation Mode *</label>
                    <select name="geolocation_mode" id="geolocation_mode" class="w-full px-4 py-2 border border-navy-900 rounded" disabled>
                        @foreach($geolocationModeOptions as $value => $label)
                            <option value="{{ $value }}" {{ $oldGeolocationMode === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-600 mt-1" id="geolocation_mode_hint"></p>
                </div>

                <div id="ip_whitelist_section" class="space-y-3" style="display: none;">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-navy-900">Whitelisted IP Addresses</h4>
                            <p class="text-xs text-gray-600">These IPs bypass the office radius requirement but still record GPS & IP data.</p>
                        </div>
                        <button type="button" id="add_ip_btn" class="text-sm text-navy-900 underline">+ Add IP</button>
                    </div>

                    @if($ipWhitelistError)
                        <p class="text-xs text-red-600">{{ $ipWhitelistError }}</p>
                    @endif

                    <div id="ip_whitelist_list" class="space-y-3"></div>

                    <p class="text-xs text-gray-600">
                        Need to manage existing IPs later? Use the
                        <a href="{{ route('admin.attendance.ip-whitelists.index') }}" class="text-navy-900 underline" target="_blank">IP whitelist admin page</a>.
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <button type="submit" id="submit_btn" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Employee</button>
                <a href="{{ route('employees.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        const geoModeCopy = {
            '{{ \App\Models\Employee::GEO_MODE_DISABLED }}': 'Employee can check in from anywhere. Geolocation capture is skipped.',
            '{{ \App\Models\Employee::GEO_MODE_REQUIRED }}': 'Employee must be within office radius. GPS and IP are logged.',
            '{{ \App\Models\Employee::GEO_MODE_REQUIRED_WITH_WHITELIST }}': 'GPS + IP are logged, but whitelisted IPs can check in even if outside radius.',
        };

        const geoModeWhitelistValue = '{{ \App\Models\Employee::GEO_MODE_REQUIRED_WITH_WHITELIST }}';

        function toggleUserAccountFields() {
            const checkbox = document.getElementById('create_user_account');
            const passwordField = document.getElementById('password_field');
            const passwordInput = document.getElementById('user_password');
            const submitBtn = document.getElementById('submit_btn');
            const geoSection = document.getElementById('geolocation_section');
            const geoModeSelect = document.getElementById('geolocation_mode');

            const enabled = checkbox.checked;

            passwordField.style.display = enabled ? 'block' : 'none';
            passwordInput.disabled = !enabled;
            passwordInput.required = enabled;
            if (!enabled) {
                passwordInput.value = '';
            }

            geoSection.style.display = enabled ? 'block' : 'none';
            geoModeSelect.disabled = !enabled;

            if (!enabled) {
                geoModeSelect.value = '{{ $geoModeDefault }}';
                hideIpWhitelistSection();
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = passwordInput.value.length < 8;
            }
        }

        function hideIpWhitelistSection() {
            document.getElementById('ip_whitelist_section').style.display = 'none';
        }

        function onGeoModeChange() {
            const geoModeSelect = document.getElementById('geolocation_mode');
            const hint = document.getElementById('geolocation_mode_hint');
            const selected = geoModeSelect.value;
            hint.textContent = geoModeCopy[selected] ?? '';

            const ipSection = document.getElementById('ip_whitelist_section');
            if (selected === geoModeWhitelistValue) {
                ipSection.style.display = 'block';
                if (!document.querySelector('#ip_whitelist_list .ip-whitelist-row')) {
                    addIpWhitelistRow();
                }
            } else {
                ipSection.style.display = 'none';
            }
        }

        function addIpWhitelistRow(ip = '', label = '') {
            const list = document.getElementById('ip_whitelist_list');
            const index = list.children.length;
            const wrapper = document.createElement('div');
            wrapper.className = 'ip-whitelist-row grid grid-cols-1 md:grid-cols-5 gap-3 items-start';

            wrapper.innerHTML = `
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-navy-900 mb-1 block">IP Address</label>
                    <input type="text" name="ip_whitelists[${index}][ip_address]" value="${ip}" class="w-full border border-navy-900 rounded px-3 py-2" placeholder="203.0.113.5">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-navy-900 mb-1 block">Label</label>
                    <input type="text" name="ip_whitelists[${index}][label]" value="${label}" class="w-full border border-navy-900 rounded px-3 py-2" placeholder="Home WiFi">
                </div>
                <div class="md:col-span-1 flex items-end">
                    <button type="button" class="text-red-600 text-sm underline" onclick="removeIpWhitelistRow(this)">Remove</button>
                </div>
            `;

            list.appendChild(wrapper);
        }

        function removeIpWhitelistRow(button) {
            const row = button.closest('.ip-whitelist-row');
            if (row) {
                row.remove();
            }
        }

        function hydrateFromOldData() {
            const checkbox = document.getElementById('create_user_account');
            const passwordInput = document.getElementById('user_password');
            const submitBtn = document.getElementById('submit_btn');
            const geoModeSelect = document.getElementById('geolocation_mode');

            const initialIps = @json($initialIpWhitelists);

            document.getElementById('add_ip_btn').addEventListener('click', () => addIpWhitelistRow());
            geoModeSelect.addEventListener('change', onGeoModeChange);

            const enabled = checkbox.checked;
            toggleUserAccountFields();

            if (enabled && initialIps.length > 0) {
                initialIps.forEach(entry => addIpWhitelistRow(entry.ip_address, entry.label));
            }

            onGeoModeChange();

            passwordInput.addEventListener('input', function() {
                submitBtn.disabled = checkbox.checked && this.value.length < 8;
            });
        }

        document.addEventListener('DOMContentLoaded', hydrateFromOldData);
    </script>
</x-app-layout>
