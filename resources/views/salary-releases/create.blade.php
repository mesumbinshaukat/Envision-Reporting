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
                <label for="release_type" class="block text-sm font-semibold text-navy-900 mb-1">Release Type *</label>
                <select name="release_type" id="release_type" required class="w-full px-4 py-2 border border-navy-900 rounded" onchange="togglePartialAmount()">
                    <option value="full" {{ old('release_type') == 'full' ? 'selected' : '' }}>Full</option>
                    <option value="partial" {{ old('release_type') == 'partial' ? 'selected' : '' }}>Partial</option>
                </select>
            </div>

            <div id="partial_amount_field" style="display: none;">
                <label for="partial_amount" class="block text-sm font-semibold text-navy-900 mb-1">Partial Amount *</label>
                <input type="number" name="partial_amount" id="partial_amount" value="{{ old('partial_amount') }}" step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
                <p class="text-sm text-gray-600 mt-1">Enter amount to release (must be ≤ total calculated)</p>
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
                        <span id="preview_base" class="text-navy-900">$0.00</span>
                    </div>

                    <div>
                        <div class="flex justify-between border-b border-gray-300 pb-2">
                            <span class="font-semibold text-navy-900">Commission from paid invoices:</span>
                            <span id="preview_commission" class="text-navy-900">$0.00</span>
                        </div>
                        <div id="invoice_list" class="ml-4 mt-2 text-sm text-gray-700"></div>
                    </div>

                    <div>
                        <div class="flex justify-between border-b border-gray-300 pb-2">
                            <span class="font-semibold text-navy-900">Bonuses (with salary):</span>
                            <span id="preview_bonus" class="text-navy-900">$0.00</span>
                        </div>
                        <div id="bonus_list" class="ml-4 mt-2 text-sm text-gray-700"></div>
                    </div>

                    <div class="flex justify-between border-b border-gray-300 pb-2">
                        <span class="font-semibold text-navy-900">Deductions:</span>
                        <span id="preview_deductions" class="text-navy-900">$0.00</span>
                    </div>

                    <div class="flex justify-between border-t-2 border-navy-900 pt-4 mt-4">
                        <span class="font-bold text-lg text-navy-900">Total Calculated:</span>
                        <span id="preview_total" class="font-bold text-lg text-navy-900">$0.00</span>
                    </div>

                    <div id="partial_preview" style="display: none;" class="flex justify-between bg-yellow-50 p-3 rounded">
                        <span class="font-bold text-navy-900">Partial Amount to Release:</span>
                        <span id="preview_partial" class="font-bold text-navy-900">$0.00</span>
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
                </ul>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Release Salary</button>
                <a href="{{ route('salary-releases.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        let totalCalculated = 0;

        function togglePartialAmount() {
            const releaseType = document.getElementById('release_type').value;
            const partialField = document.getElementById('partial_amount_field');
            const partialPreview = document.getElementById('partial_preview');
            const partialInput = document.getElementById('partial_amount');
            
            if (releaseType === 'partial') {
                partialField.style.display = 'block';
                partialInput.required = true;
            } else {
                partialField.style.display = 'none';
                partialInput.required = false;
                partialPreview.style.display = 'none';
            }
        }

        function updatePreview() {
            const employeeId = document.getElementById('employee_id').value;
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
                    deductions: deductions
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('preview_section').style.display = 'block';
                document.getElementById('preview_base').textContent = '$' + data.base_salary;
                document.getElementById('preview_commission').textContent = '$' + data.commission_amount;
                document.getElementById('preview_bonus').textContent = '$' + data.bonus_amount;
                document.getElementById('preview_deductions').textContent = '$' + data.deductions;
                document.getElementById('preview_total').textContent = '$' + data.total_calculated;
                
                totalCalculated = parseFloat(data.total_calculated.replace(/,/g, ''));
                
                // Update invoice list
                let invoiceHtml = '';
                if (data.paid_invoices.length > 0) {
                    invoiceHtml = '<ul class="list-disc list-inside">';
                    data.paid_invoices.forEach(invoice => {
                        invoiceHtml += `<li>${invoice.client}: $${invoice.amount} (Commission: $${invoice.commission})</li>`;
                    });
                    invoiceHtml += '</ul>';
                } else {
                    invoiceHtml = '<p class="text-gray-500">No paid invoices with unpaid commissions</p>';
                }
                document.getElementById('invoice_list').innerHTML = invoiceHtml;
                
                // Update bonus list
                let bonusHtml = '';
                if (data.bonuses.length > 0) {
                    bonusHtml = '<ul class="list-disc list-inside">';
                    data.bonuses.forEach(bonus => {
                        bonusHtml += `<li>${bonus.description}: $${bonus.amount}</li>`;
                    });
                    bonusHtml += '</ul>';
                } else {
                    bonusHtml = '<p class="text-gray-500">No unreleased bonuses</p>';
                }
                document.getElementById('bonus_list').innerHTML = bonusHtml;
                
                // Set max for partial amount
                document.getElementById('partial_amount').max = totalCalculated;
            })
            .catch(error => console.error('Error:', error));
        }

        document.getElementById('partial_amount').addEventListener('input', function() {
            const partialAmount = parseFloat(this.value) || 0;
            const partialPreview = document.getElementById('partial_preview');
            
            if (document.getElementById('release_type').value === 'partial' && partialAmount > 0) {
                partialPreview.style.display = 'flex';
                document.getElementById('preview_partial').textContent = '$' + partialAmount.toFixed(2);
            } else {
                partialPreview.style.display = 'none';
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            togglePartialAmount();
            if (document.getElementById('employee_id').value) {
                updatePreview();
            }
        });
    </script>
</x-app-layout>
