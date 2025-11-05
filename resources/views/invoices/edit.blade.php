<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Edit Invoice</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('invoices.update', $invoice) }}" enctype="multipart/form-data" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
        <form method="POST" action="{{ route('invoices.update', $invoice) }}" enctype="multipart/form-data" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="client_id" class="block text-sm font-semibold text-navy-900 mb-1">Client *</label>
                <select name="client_id" id="client_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select Client...</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $invoice->client_id) == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-navy-900 mb-1">Salesperson *</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="salesperson_type" value="self" {{ !$invoice->employee_id ? 'checked' : '' }} onclick="document.getElementById('employee_id').value=''; document.getElementById('employee_id').disabled=true;" class="mr-2">
                        <span>Self (Default)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="salesperson_type" value="employee" {{ $invoice->employee_id ? 'checked' : '' }} onclick="document.getElementById('employee_id').disabled=false;" class="mr-2">
                        <span>Employee</span>
                    </label>
                </div>
            </div>

            <div>
                <label for="employee_id" class="block text-sm font-semibold text-navy-900 mb-1">Select Employee</label>
                <select name="employee_id" id="employee_id" {{ !$invoice->employee_id ? 'disabled' : '' }} class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select Employee...</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $invoice->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->name }} ({{ $employee->commission_rate }}% commission)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="currency_id" class="block text-sm font-semibold text-navy-900 mb-1">Currency *</label>
                <select name="currency_id" id="currency_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ (old('currency_id', $invoice->currency_id ?? $baseCurrency->id) == $currency->id) ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name }} ({{ $currency->symbol }})
                            @if($currency->is_base) - BASE @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-navy-900 mb-1">Status *</label>
                <select name="status" id="status" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="Pending" {{ old('status', $invoice->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Partial Paid" {{ old('status', $invoice->status) == 'Partial Paid' ? 'selected' : '' }}>Partial Paid</option>
                    <option value="Payment Done" {{ old('status', $invoice->status) == 'Payment Done' ? 'selected' : '' }}>Payment Done</option>
                </select>
            </div>

            <!-- Milestones Section -->
            @include('components.invoice-milestones', ['milestones' => $invoice->milestones])

            <div>
                <label for="amount" class="block text-sm font-semibold text-navy-900 mb-1">Amount * <span class="text-xs text-gray-600">(Auto-calculated from milestones)</span></label>
                <input type="number" name="amount" id="amount" value="{{ old('amount', $invoice->amount) }}" required step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded bg-gray-100" readonly>
            </div>

            <div>
                <label for="tax" class="block text-sm font-semibold text-navy-900 mb-1">Tax</label>
                <input type="number" name="tax" id="tax" value="{{ old('tax', $invoice->tax) }}" step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="due_date" class="block text-sm font-semibold text-navy-900 mb-1">Due Date</label>
                <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="special_note" class="block text-sm font-semibold text-navy-900 mb-1">Special Note</label>
                <textarea name="special_note" id="special_note" rows="3" class="w-full px-4 py-2 border border-navy-900 rounded">{{ old('special_note', $invoice->special_note) }}</textarea>
            </div>

            <!-- Payment Method -->
            <div>
                <label for="payment_method" class="block text-sm font-semibold text-navy-900 mb-1">Payment Method</label>
                <select name="payment_method" id="payment_method" class="w-full px-4 py-2 border border-navy-900 rounded" onchange="toggleCustomPaymentMethod()">
                    <option value="">Select Payment Method...</option>
                    <option value="PayPal" {{ old('payment_method', $invoice->payment_method) == 'PayPal' ? 'selected' : '' }}>PayPal</option>
                    <option value="Stripe" {{ old('payment_method', $invoice->payment_method) == 'Stripe' ? 'selected' : '' }}>Stripe</option>
                    <option value="Bank" {{ old('payment_method', $invoice->payment_method) == 'Bank' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="Venmo" {{ old('payment_method', $invoice->payment_method) == 'Venmo' ? 'selected' : '' }}>Venmo</option>
                    <option value="CashApp" {{ old('payment_method', $invoice->payment_method) == 'CashApp' ? 'selected' : '' }}>CashApp</option>
                    <option value="Other" {{ old('payment_method', $invoice->payment_method) == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <!-- Custom Payment Method (Hidden by default) -->
            <div id="custom_payment_method_section" style="display: none;">
                <label for="custom_payment_method" class="block text-sm font-semibold text-navy-900 mb-1">Custom Payment Method *</label>
                <input type="text" name="custom_payment_method" id="custom_payment_method" value="{{ old('custom_payment_method', $invoice->custom_payment_method) }}" class="w-full px-4 py-2 border border-navy-900 rounded" placeholder="Enter custom payment method" disabled>
                <p class="text-xs text-gray-600 mt-1">Please specify the payment method</p>
            </div>

            <!-- Payment Processing Fee -->
            <div>
                <label for="payment_processing_fee" class="block text-sm font-semibold text-navy-900 mb-1">Payment Processing Fee</label>
                <input type="number" name="payment_processing_fee" id="payment_processing_fee" value="{{ old('payment_processing_fee', $invoice->payment_processing_fee ?? 0) }}" step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded" placeholder="0.00">
                <p class="text-xs text-gray-600 mt-1">Enter any payment processing fees (e.g., transaction fees)</p>
            </div>

            <!-- Existing Attachments -->
            @if($invoice->attachments && count($invoice->attachments) > 0)
            <div>
                <label class="block text-sm font-semibold text-navy-900 mb-1">Current Attachments</label>
                <div class="space-y-2">
                    @foreach($invoice->attachments as $attachment)
                        <div class="flex items-center gap-2">
                            <a href="{{ Storage::url($attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                📎 {{ basename($attachment) }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Attachments -->
            <div>
                <label for="attachments" class="block text-sm font-semibold text-navy-900 mb-1">Add More Attachments</label>
                <input type="file" name="attachments[]" id="attachments" multiple accept=".jpg,.jpeg,.png,.pdf" class="w-full px-4 py-2 border border-navy-900 rounded">
                <p class="text-xs text-gray-600 mt-1">Upload payment receipts or screenshots (JPG, PNG, PDF - Max 2MB each)</p>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Update Invoice</button>
                <a href="{{ route('invoices.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function toggleCustomPaymentMethod() {
            const paymentMethod = document.getElementById('payment_method').value;
            const customSection = document.getElementById('custom_payment_method_section');
            const customInput = document.getElementById('custom_payment_method');
            
            if (paymentMethod === 'Other') {
                customSection.style.display = 'block';
                customInput.required = true;
                customInput.disabled = false;
            } else {
                customSection.style.display = 'none';
                customInput.required = false;
                customInput.disabled = true;
                customInput.value = '';
            }
        }

        // Initialize custom payment method toggle on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('payment_method').value === 'Other') {
                toggleCustomPaymentMethod();
            }
        });
    </script>
</x-app-layout>
