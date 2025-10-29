<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Edit Bonus</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('bonuses.update', $bonus) }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="employee_id" class="block text-sm font-semibold text-navy-900 mb-1">Employee *</label>
                <select name="employee_id" id="employee_id" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="">Select Employee...</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $bonus->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="amount" class="block text-sm font-semibold text-navy-900 mb-1">Amount *</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount', $bonus->amount) }}" required step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-navy-900 mb-1">Description</label>
                <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 border border-navy-900 rounded">{{ old('description', $bonus->description) }}</textarea>
            </div>

            <div>
                <label for="date" class="block text-sm font-semibold text-navy-900 mb-1">Date *</label>
                <input type="date" name="date" id="date" value="{{ old('date', $bonus->date->format('Y-m-d')) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="release_type" class="block text-sm font-semibold text-navy-900 mb-1">Release Type *</label>
                <select name="release_type" id="release_type" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    <option value="with_salary" {{ old('release_type', $bonus->release_type) == 'with_salary' ? 'selected' : '' }}>With Salary</option>
                    <option value="separate" {{ old('release_type', $bonus->release_type) == 'separate' ? 'selected' : '' }}>Separate (Immediate)</option>
                </select>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Update Bonus</button>
                <a href="{{ route('bonuses.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
