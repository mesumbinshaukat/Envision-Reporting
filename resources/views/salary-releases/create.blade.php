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
            </div>

            <div>
                <label for="release_date" class="block text-sm font-semibold text-navy-900 mb-1">Release Date *</label>
                <input type="date" name="release_date" id="release_date" value="{{ old('release_date', date('Y-m-d')) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
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
            const month = document.getElementById('month').value;
            const deductions = document.getElementById('deductions').value || 0;
            
            if (!employeeId) {
                document.getElementById('preview_section').style.display = 'none';
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
                    month: month,
                    deductions: deductions
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('preview_section').style.display = 'block';
                document.getElementById('preview_base').textContent = 'Rs.' + data.base_salary;
                document.getElementById('preview_commission').textContent = 'Rs.' + data.commission_amount;
                document.getElementById('preview_bonus').textContent = 'Rs.' + data.bonus_amount;
                document.getElementById('preview_deductions').textContent = 'Rs.' + data.deductions;
                document.getElementById('preview_total').textContent = 'Rs.' + data.total_calculated;
                
                // Update invoice list
                let invoiceHtml = '';
                if (data.paid_invoices.length > 0) {
                    invoiceHtml = '<ul class="list-disc list-inside">';
                    data.paid_invoices.forEach(invoice => {
                        invoiceHtml += `<li>${invoice.client}: Rs.${invoice.paid_amount} paid (${invoice.commission_rate}% commission = Rs.${invoice.commission})</li>`;
                    });
                    invoiceHtml += '</ul>';
                } else {
                    invoiceHtml = '<p class="text-gray-500">No payments with unpaid commissions up to this month</p>';
                }
                document.getElementById('invoice_list').innerHTML = invoiceHtml;
                
                // Update bonus list
                let bonusHtml = '';
                if (data.bonuses.length > 0) {
                    bonusHtml = '<ul class="list-disc list-inside">';
                    data.bonuses.forEach(bonus => {
                        bonusHtml += `<li>${bonus.description}: Rs.${bonus.amount}</li>`;
                    });
                    bonusHtml += '</ul>';
                } else {
                    bonusHtml = '<p class="text-gray-500">No unreleased bonuses</p>';
                }
                document.getElementById('bonus_list').innerHTML = bonusHtml;
            })
            .catch(error => console.error('Error:', error));
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('employee_id').value) {
                updatePreview();
            }
            
            // Add event listener for month field
            document.getElementById('month').addEventListener('change', updatePreview);
        });
    </script>
</x-app-layout>
