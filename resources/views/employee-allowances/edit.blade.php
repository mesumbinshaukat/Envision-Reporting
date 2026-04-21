<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Edit Employee Allowance</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('employee-allowances.update', $employeeAllowance) }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="employee_id" class="block text-sm font-semibold text-navy-900 mb-1">Employee *</label>
                <select name="employee_id" id="employee_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select Employee...</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $employeeAllowance->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="allowance_type_id" class="block text-sm font-semibold text-navy-900 mb-1">Allowance Type *</label>
                <select name="allowance_type_id" id="allowance_type_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select Allowance Type...</option>
                    @foreach($allowanceTypes as $type)
                        <option value="{{ $type->id }}" {{ old('allowance_type_id', $employeeAllowance->allowance_type_id) == $type->id ? 'selected' : '' }}>{{ $type->label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="currency_id" class="block text-sm font-semibold text-navy-900 mb-1">Currency *</label>
                <select name="currency_id" id="currency_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ (old('currency_id', $employeeAllowance->currency_id) == $currency->id) ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name }} ({{ $currency->symbol }})
                            @if($currency->is_base) - BASE @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="amount" class="block text-sm font-semibold text-navy-900 mb-1">Monthly Amount *</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount', $employeeAllowance->amount) }}" required step="0.01" min="0" 
                    class="w-full px-4 py-2 border border-navy-900 rounded"
                    placeholder="Enter monthly allowance amount">
                <p class="text-xs text-gray-500 mt-1">This amount will be included in every monthly salary</p>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $employeeAllowance->is_active) ? 'checked' : '' }} 
                    class="w-4 h-4 border-navy-900 rounded">
                <label for="is_active" class="ml-2 text-sm font-semibold text-navy-900">Active (include in salary)</label>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Update Allowance</button>
                <a href="{{ route('employee-allowances.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
