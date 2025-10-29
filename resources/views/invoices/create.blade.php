<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Create Invoice</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('invoices.store') }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf

            <div>
                <label for="client_id" class="block text-sm font-semibold text-navy-900 mb-1">Client *</label>
                <select name="client_id" id="client_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select Client...</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

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

            <div>
                <label for="status" class="block text-sm font-semibold text-navy-900 mb-1">Status *</label>
                <select name="status" id="status" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="Pending" {{ old('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Partial Paid" {{ old('status') == 'Partial Paid' ? 'selected' : '' }}>Partial Paid</option>
                    <option value="Payment Done" {{ old('status') == 'Payment Done' ? 'selected' : '' }}>Payment Done</option>
                </select>
            </div>

            <div>
                <label for="amount" class="block text-sm font-semibold text-navy-900 mb-1">Amount *</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="tax" class="block text-sm font-semibold text-navy-900 mb-1">Tax</label>
                <input type="number" name="tax" id="tax" value="{{ old('tax', 0) }}" step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
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
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Invoice</button>
                <a href="{{ route('invoices.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
