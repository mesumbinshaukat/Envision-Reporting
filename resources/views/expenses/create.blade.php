<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Add Expense</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('expenses.store') }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf

            <div>
                <label for="description" class="block text-sm font-semibold text-navy-900 mb-1">Description *</label>
                <textarea name="description" id="description" rows="3" required class="w-full px-4 py-2 border border-navy-900 rounded">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="amount" class="block text-sm font-semibold text-navy-900 mb-1">Amount *</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required step="0.01" min="0" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="date" class="block text-sm font-semibold text-navy-900 mb-1">Date *</label>
                <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add Expense</button>
                <a href="{{ route('expenses.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
