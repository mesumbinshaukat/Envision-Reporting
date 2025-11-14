<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Expenses</h2>
            <a href="{{ route('expenses.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add Expense</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <form method="GET" action="{{ route('expenses.index') }}" class="flex gap-4">
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From" class="px-4 py-2 border border-navy-900 rounded">
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To" class="px-4 py-2 border border-navy-900 rounded">
            <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Filter</button>
            @if(request()->hasAny(['date_from', 'date_to']))
                <a href="{{ route('expenses.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Clear</a>
            @endif
        </form>

        <div class="bg-navy-900 text-white p-4 rounded-lg">
            <h3 class="text-lg font-semibold">Total Expenses: {{ $baseCurrency->symbol ?? 'Rs.' }}{{ number_format($totalAmount, 2) }}</h3>
            <p class="text-xs mt-1">Converted to base currency</p>
        </div>

        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            @if($expenses->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Description</th>
                            <th class="text-left py-3 px-4">Amount</th>
                            <th class="text-left py-3 px-4">Date</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                            <tr class="border-b">
                                <td class="py-3 px-4">{{ $expense->description }}</td>
                                <td class="py-3 px-4 font-semibold">{{ $expense->currency ? $expense->currency->symbol : 'Rs.' }}{{ number_format($expense->amount, 2) }}</td>
                                <td class="py-3 px-4">{{ $expense->date->format('M d, Y') }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('expenses.edit', $expense) }}" class="text-navy-900 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">{{ $expenses->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No expenses found.</p>
                    <a href="{{ route('expenses.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Add your first expense</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
