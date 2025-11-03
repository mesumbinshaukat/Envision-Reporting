<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Create Invoice</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('invoices.store') }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf

            <!-- One-Time Invoice Checkbox -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_one_time" id="is_one_time" value="1" {{ old('is_one_time') ? 'checked' : '' }} class="mr-2" onchange="toggleOneTimeInvoice()">
                    <span class="text-sm font-semibold text-navy-900">One-Time Project (No Regular Client)</span>
                </label>
            </div>

            <!-- Client Selection -->
            <div id="client_section">
                <label for="client_id" class="block text-sm font-semibold text-navy-900 mb-1">Client *</label>
                <select name="client_id" id="client_id" class="w-full px-4 py-2 border border-navy-900 rounded" onchange="toggleNewClientField()">
                    <option value="">Select Client...</option>
                    <option value="new_client" style="background-color: #f0f9ff; font-weight: bold;">➕ Create New Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- New Client Name Field (Hidden by default) -->
            <div id="new_client_section" style="display: none;">
                <label for="new_client_name" class="block text-sm font-semibold text-navy-900 mb-1">New Client Name *</label>
                <input type="text" name="new_client_name" id="new_client_name" value="{{ old('new_client_name') }}" class="w-full px-4 py-2 border border-navy-900 rounded" placeholder="Enter client name" disabled>
            </div>

            <!-- One-Time Client Name Field (Hidden by default) -->
            <div id="one_time_client_section" style="display: none;">
                <label for="one_time_client_name" class="block text-sm font-semibold text-navy-900 mb-1">Project/Client Name *</label>
                <input type="text" name="one_time_client_name" id="one_time_client_name" value="{{ old('one_time_client_name') }}" class="w-full px-4 py-2 border border-navy-900 rounded" placeholder="Enter project or client name" disabled>
            </div>

            @if(!isset($isEmployee) || !$isEmployee)
                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Salesperson *</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="salesperson_type" value="self" checked onclick="document.getElementById('employee_id').value=''; document.getElementById('employee_id').disabled=true;" class="mr-2">
                            <span>Self (Default)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="salesperson_type" value="employee" onclick="document.getElementById('employee_id').disabled=false;" class="mr-2">
                            <span>Employee</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="employee_id" class="block text-sm font-semibold text-navy-900 mb-1">Select Employee</label>
                    <select name="employee_id" id="employee_id" disabled class="w-full px-4 py-2 border border-navy-900 rounded">
                        <option value="">Select Employee...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->name }} ({{ $employee->commission_rate }}% commission)</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="currency_id" class="block text-sm font-semibold text-navy-900 mb-1">Currency *</label>
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
                <label for="status" class="block text-sm font-semibold text-navy-900 mb-1">Status *</label>
                <select name="status" id="status" required class="w-full px-4 py-2 border border-navy-900 rounded" onchange="togglePartialPaymentField()">
                    <option value="Pending" {{ old('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Partial Paid" {{ old('status') == 'Partial Paid' ? 'selected' : '' }}>Partial Paid</option>
                    <option value="Payment Done" {{ old('status') == 'Payment Done' ? 'selected' : '' }}>Payment Done</option>
                </select>
            </div>

            <div>
                <label for="amount" class="block text-sm font-semibold text-navy-900 mb-1">Total Amount *</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded" onchange="calculateRemainingAmount()">
            </div>

            <div>
                <label for="tax" class="block text-sm font-semibold text-navy-900 mb-1">Tax</label>
                <input type="number" name="tax" id="tax" value="{{ old('tax', 0) }}" step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <!-- Partial Payment Amount Field (Hidden by default) -->
            <div id="partial_payment_section" style="display: none;">
                <label for="paid_amount" class="block text-sm font-semibold text-navy-900 mb-1">Partial Payment Amount *</label>
                <input type="number" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', 0) }}" step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded" onchange="calculateRemainingAmount()">
                <p class="text-xs text-gray-600 mt-1">Enter the amount that has been paid</p>
            </div>

            <!-- Remaining Amount Display -->
            <div id="remaining_amount_section" style="display: none;">
                <label class="block text-sm font-semibold text-navy-900 mb-1">Remaining Amount</label>
                <div class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50">
                    <span class="text-lg font-bold text-red-600"><span id="currency_symbol">{{ $baseCurrency->symbol ?? 'Rs.' }}</span><span id="remaining_amount_display">0.00</span></span>
                </div>
            </div>

            <div>
                <label for="due_date" class="block text-sm font-semibold text-navy-900 mb-1">Due Date</label>
                <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="special_note" class="block text-sm font-semibold text-navy-900 mb-1">Special Note</label>
                <textarea name="special_note" id="special_note" rows="3" class="w-full px-4 py-2 border border-navy-900 rounded">{{ old('special_note') }}</textarea>
            </div>

            <div class="flex gap-4">
                <button type="submit" id="submit_btn" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Create Invoice</button>
                <a href="{{ route('invoices.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Update currency symbol when currency changes
        document.getElementById('currency_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const currencyText = selectedOption.text;
            const symbolMatch = currencyText.match(/\(([^)]+)\)/);
            if (symbolMatch) {
                document.getElementById('currency_symbol').textContent = symbolMatch[1];
            }
        });

        function validateForm() {
            const isOneTime = document.getElementById('is_one_time').checked;
            const clientSelect = document.getElementById('client_id');
            const newClientInput = document.getElementById('new_client_name');
            const oneTimeInput = document.getElementById('one_time_client_name');
            const amountInput = document.getElementById('amount');
            const statusSelect = document.getElementById('status');
            const submitBtn = document.getElementById('submit_btn');
            
            let isValid = true;
            
            // Check amount (required)
            if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
                isValid = false;
            }
            
            // Check status (required)
            if (!statusSelect.value) {
                isValid = false;
            }
            
            // Check client/project name based on type
            if (isOneTime) {
                if (!oneTimeInput.value || oneTimeInput.value.trim() === '') {
                    isValid = false;
                }
            } else {
                if (clientSelect.value === 'new_client') {
                    if (!newClientInput.value || newClientInput.value.trim() === '') {
                        isValid = false;
                    }
                } else if (!clientSelect.value) {
                    isValid = false;
                }
            }
            
            submitBtn.disabled = !isValid;
        }
        
        function toggleOneTimeInvoice() {
            const isOneTime = document.getElementById('is_one_time').checked;
            const clientSection = document.getElementById('client_section');
            const oneTimeSection = document.getElementById('one_time_client_section');
            const newClientSection = document.getElementById('new_client_section');
            const clientSelect = document.getElementById('client_id');
            const oneTimeInput = document.getElementById('one_time_client_name');
            const newClientInput = document.getElementById('new_client_name');
            
            if (isOneTime) {
                clientSection.style.display = 'none';
                oneTimeSection.style.display = 'block';
                newClientSection.style.display = 'none';
                clientSelect.required = false;
                clientSelect.disabled = true;
                clientSelect.value = '';
                newClientInput.required = false;
                newClientInput.disabled = true;
                newClientInput.value = '';
                oneTimeInput.required = true;
                oneTimeInput.disabled = false;
            } else {
                clientSection.style.display = 'block';
                oneTimeSection.style.display = 'none';
                clientSelect.required = true;
                clientSelect.disabled = false;
                oneTimeInput.required = false;
                oneTimeInput.disabled = true;
                oneTimeInput.value = '';
            }
            validateForm();
        }
        
        function toggleNewClientField() {
            const clientSelect = document.getElementById('client_id');
            const newClientSection = document.getElementById('new_client_section');
            const newClientInput = document.getElementById('new_client_name');
            
            if (clientSelect.value === 'new_client') {
                newClientSection.style.display = 'block';
                newClientInput.required = true;
                newClientInput.disabled = false;
            } else {
                newClientSection.style.display = 'none';
                newClientInput.required = false;
                newClientInput.disabled = true;
                newClientInput.value = '';
            }
            validateForm();
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for validation
            document.getElementById('amount').addEventListener('input', validateForm);
            document.getElementById('status').addEventListener('change', validateForm);
            document.getElementById('client_id').addEventListener('change', validateForm);
            document.getElementById('new_client_name').addEventListener('input', validateForm);
            document.getElementById('one_time_client_name').addEventListener('input', validateForm);
            document.getElementById('is_one_time').addEventListener('change', validateForm);
            document.getElementById('paid_amount').addEventListener('input', validateForm);
            
            if (document.getElementById('is_one_time').checked) {
                toggleOneTimeInvoice();
            }
            if (document.getElementById('client_id').value === 'new_client') {
                toggleNewClientField();
            }
            
            // Check if status is Partial Paid on page load
            togglePartialPaymentField();
            
            // Initial validation
            validateForm();
        });

        function togglePartialPaymentField() {
            const status = document.getElementById('status').value;
            const partialSection = document.getElementById('partial_payment_section');
            const remainingSection = document.getElementById('remaining_amount_section');
            const paidAmountInput = document.getElementById('paid_amount');
            
            if (status === 'Partial Paid') {
                partialSection.style.display = 'block';
                remainingSection.style.display = 'block';
                paidAmountInput.required = true;
                paidAmountInput.disabled = false;
                calculateRemainingAmount();
            } else if (status === 'Payment Done') {
                partialSection.style.display = 'none';
                remainingSection.style.display = 'none';
                paidAmountInput.required = false;
                paidAmountInput.disabled = true;
                paidAmountInput.value = document.getElementById('amount').value || 0;
            } else {
                partialSection.style.display = 'none';
                remainingSection.style.display = 'none';
                paidAmountInput.required = false;
                paidAmountInput.disabled = true;
                paidAmountInput.value = 0;
            }
            
            validateForm();
        }

        function calculateRemainingAmount() {
            const totalAmount = parseFloat(document.getElementById('amount').value) || 0;
            const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
            const status = document.getElementById('status').value;
            
            if (status === 'Partial Paid') {
                const remaining = totalAmount - paidAmount;
                document.getElementById('remaining_amount_display').textContent = remaining.toFixed(2);
                
                // Validation: paid amount should not exceed total amount
                if (paidAmount > totalAmount) {
                    document.getElementById('paid_amount').setCustomValidity('Paid amount cannot exceed total amount');
                } else if (paidAmount <= 0) {
                    document.getElementById('paid_amount').setCustomValidity('Paid amount must be greater than 0');
                } else if (paidAmount >= totalAmount) {
                    document.getElementById('paid_amount').setCustomValidity('For full payment, select "Payment Done" status');
                } else {
                    document.getElementById('paid_amount').setCustomValidity('');
                }
            }
        }
    </script>
</x-app-layout>
