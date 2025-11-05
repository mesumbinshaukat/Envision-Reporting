<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Release Salary</h2>
    </x-slot>

    <div class="max-w-4xl">
        <form method="POST" action="{{ route('salary-releases.store') }}" id="salaryReleaseForm" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf

            <div>
                <label for="employee_id" class="block text-sm font-semibold text-navy-900 mb-1">Employee *</label>
                <select name="employee_id" id="employee_id" required class="w-full px-4 py-2 border border-navy-900 rounded" onchange="updatePreview()">
                    <option value="">Select Employee...</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }} - Base: Rs.{{ number_format($employee->salary, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="month" class="block text-sm font-semibold text-navy-900 mb-1">Month *</label>
                <input type="month" name="month" id="month" value="{{ old('month', date('Y-m')) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
                <p class="text-sm text-gray-600 mt-1">Select the month this salary is for</p>
                <div id="month_warning" class="text-sm text-red-600 font-semibold mt-1" style="display: none;"></div>
                @error('month')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="release_date" class="block text-sm font-semibold text-navy-900 mb-1">Release Date *</label>
                <input type="date" name="release_date" id="release_date" value="{{ old('release_date', date('Y-m-d')) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
                <p class="text-sm text-gray-600 mt-1">Must be in or after the salary month</p>
                <div id="release_date_warning" class="text-sm text-red-600 font-semibold mt-1" style="display: none;"></div>
            </div>

            <div>
                <label for="deductions" class="block text-sm font-semibold text-navy-900 mb-1">Deductions</label>
                <input type="number" name="deductions" id="deductions" value="{{ old('deductions', 0) }}" step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded" oninput="updatePreview()">
            </div>

            <div>
                <label for="notes" class="block text-sm font-semibold text-navy-900 mb-1">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2 border border-navy-900 rounded">{{ old('notes') }}</textarea>
            </div>

            <!-- Preview Section -->
            <div id="preview_section" class="bg-white border-2 border-navy-900 rounded-lg p-6 mt-6" style="display: none;">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Salary Calculation Preview</h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between border-b border-gray-300 pb-2">
                        <span class="font-semibold text-navy-900">Base Salary:</span>
                        <span id="preview_base" class="text-navy-900">Rs.0.00</span>
                    </div>

                    <div>
                        <div class="flex justify-between border-b border-gray-300 pb-2">
                            <span class="font-semibold text-navy-900">Commission from paid invoices:</span>
                            <span id="preview_commission" class="text-navy-900">Rs.0.00</span>
                        </div>
                        <div id="invoice_list" class="ml-4 mt-2 text-sm text-gray-700"></div>
                    </div>

                    <div>
                        <div class="flex justify-between border-b border-gray-300 pb-2">
                            <span class="font-semibold text-navy-900">Bonuses (with salary):</span>
                            <span id="preview_bonus" class="text-navy-900">Rs.0.00</span>
                        </div>
                        <div id="bonus_list" class="ml-4 mt-2 text-sm text-gray-700"></div>
                    </div>

                    <div class="flex justify-between border-b border-gray-300 pb-2">
                        <span class="font-semibold text-navy-900">Deductions:</span>
                        <span id="preview_deductions" class="text-navy-900">Rs.0.00</span>
                    </div>

                    <div class="flex justify-between border-t-2 border-navy-900 pt-4 mt-4">
                        <span class="font-bold text-lg text-navy-900">Total Calculated:</span>
                        <span id="preview_total" class="font-bold text-lg text-navy-900">Rs.0.00</span>
                    </div>

                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded p-4">
                <h4 class="font-semibold text-navy-900 mb-2">Auto-Calculation Info:</h4>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Base salary from employee record</li>
                    <li>• Commission from paid invoices only (status = 'Payment Done')</li>
                    <li>• Bonuses marked "with salary" that are not yet released</li>
                    <li>• Minus any deductions entered above</li>
                    <li>• <strong>Full calculated amount will be released</strong></li>
                </ul>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Release Salary</button>
                <a href="{{ route('salary-releases.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function updatePreview() {
            const employeeId = document.getElementById('employee_id').value;
            const deductions = document.getElementById('deductions').value || 0;
            const month = document.getElementById('month').value;
            
            if (!employeeId) {
                document.getElementById('preview_section').style.display = 'none';
                document.getElementById('month_warning').style.display = 'none';
                return;
            }

            fetch('{{ route('salary-releases.preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    deductions: deductions,
                    month: month,
                    release_date: document.getElementById('release_date').value
                })
            })
            .then(response => response.json())
            .then(data => {
                // Check if salary already released
                const monthWarning = document.getElementById('month_warning');
                if (data.already_released) {
                    const monthName = new Date(month + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                    monthWarning.textContent = '⚠️ Salary has already been released for ' + monthName;
                    monthWarning.style.display = 'block';
                } else {
                    monthWarning.style.display = 'none';
                }
                
                document.getElementById('preview_section').style.display = 'block';
                const currSymbol = data.currency_symbol || 'Rs.';
                document.getElementById('preview_base').textContent = currSymbol + data.base_salary;
                document.getElementById('preview_commission').textContent = currSymbol + data.commission_amount;
                document.getElementById('preview_bonus').textContent = currSymbol + data.bonus_amount;
                document.getElementById('preview_deductions').textContent = currSymbol + data.deductions;
                document.getElementById('preview_total').textContent = currSymbol + data.total_calculated;
                
                // Update invoice list with detailed payment breakdown
                let invoiceHtml = '';
                if (data.paid_invoices.length > 0) {
                    invoiceHtml = '<div class="space-y-2">';
                    data.paid_invoices.forEach(invoice => {
                        invoiceHtml += `
                            <div class="border-l-4 border-green-500 pl-3 py-1">
                                <div class="font-semibold">${invoice.client}</div>
                                <div class="text-sm text-gray-600">
                                    Payments: ${invoice.paid_amount_formatted} | 
                                    Rate: ${invoice.commission_rate}% | 
                                    Commission: <span class="text-green-600 font-semibold">${currSymbol}${invoice.commission}</span>
                                </div>
                            </div>
                        `;
                    });
                    invoiceHtml += '</div>';
                } else {
                    invoiceHtml = '<p class="text-gray-500">No payments with unpaid commissions up to the release date</p>';
                }
                document.getElementById('invoice_list').innerHTML = invoiceHtml;
                
                // Update bonus list
                let bonusHtml = '';
                if (data.bonuses.length > 0) {
                    bonusHtml = '<ul class="list-disc list-inside">';
                    data.bonuses.forEach(bonus => {
                        bonusHtml += `<li>${bonus.description}: ${currSymbol}${bonus.amount}</li>`;
                    });
                    bonusHtml += '</ul>';
                } else {
                    bonusHtml = '<p class="text-gray-500">No unreleased bonuses</p>';
                }
                document.getElementById('bonus_list').innerHTML = bonusHtml;
            })
            .catch(error => console.error('Error:', error));
        }

        // Validate release date
        function validateReleaseDate() {
            const monthInput = document.getElementById('month').value;
            const releaseDateInput = document.getElementById('release_date').value;
            const warningDiv = document.getElementById('release_date_warning');
            const submitBtn = document.querySelector('button[type="submit"]');
            
            if (!monthInput || !releaseDateInput) {
                warningDiv.style.display = 'none';
                return;
            }
            
            // Get first day of salary month
            const salaryMonthStart = new Date(monthInput + '-01');
            const releaseDate = new Date(releaseDateInput);
            
            // Release date must be >= first day of salary month
            if (releaseDate < salaryMonthStart) {
                const monthName = salaryMonthStart.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                warningDiv.textContent = '⚠️ Release date cannot be before ' + monthName;
                warningDiv.style.display = 'block';
                submitBtn.disabled = true;
                return false;
            } else {
                warningDiv.style.display = 'none';
                submitBtn.disabled = false;
            }
            
            // Set min date for release_date picker
            document.getElementById('release_date').min = monthInput + '-01';
            
            return true;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('employee_id').value) {
                updatePreview();
            }
            
            // Add event listeners
            document.getElementById('month').addEventListener('change', function() {
                validateReleaseDate();
                updatePreview();
            });
            document.getElementById('release_date').addEventListener('change', function() {
                validateReleaseDate();
                updatePreview();
            });
            
            // Initial validation
            validateReleaseDate();
        });
    </script>
</x-app-layout>
