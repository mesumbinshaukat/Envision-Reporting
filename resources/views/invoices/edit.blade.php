<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Edit Invoice</h2>
    </x-slot>

    <div class="max-w-2xl">
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

            <!-- Attachments Section -->
            @include('components.invoice-attachments', ['attachments' => $invoice->attachments])

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Update Invoice</button>
                <a href="{{ route('invoices.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
