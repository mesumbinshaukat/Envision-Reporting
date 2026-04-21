<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Employee Allowances</h2>
            <div class="flex gap-2">
                <a href="{{ route('allowance-types.index') }}" class="px-4 py-2 bg-white border border-navy-900 text-navy-900 rounded hover:bg-navy-50">Manage Allowance Types</a>
                <a href="{{ route('employee-allowances.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Assign Allowance</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            @if($employeeAllowances->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Employee</th>
                            <th class="text-left py-3 px-4">Allowance Type</th>
                            <th class="text-left py-3 px-4">Amount</th>
                            <th class="text-left py-3 px-4">Status</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employeeAllowances as $allowance)
                            <tr class="border-b">
                                <td class="py-3 px-4 font-semibold">{{ $allowance->employee->name }}</td>
                                <td class="py-3 px-4">{{ $allowance->allowanceType->label }}</td>
                                <td class="py-3 px-4">{{ $allowance->currency ? $allowance->currency->symbol : ($baseCurrency->symbol ?? 'Rs.') }}{{ number_format($allowance->amount, 2) }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-sm {{ $allowance->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $allowance->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('employee-allowances.edit', $allowance) }}" class="text-navy-900 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('employee-allowances.destroy', $allowance) }}" class="inline" onsubmit="return confirm('Are you sure?');">
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
                <div class="p-4">{{ $employeeAllowances->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No employee allowances found.</p>
                    <div class="mt-4 flex justify-center gap-4">
                        <a href="{{ route('allowance-types.index') }}" class="text-navy-900 hover:underline">Manage Allowance Types</a>
                        <span class="text-gray-400">|</span>
                        <a href="{{ route('employee-allowances.create') }}" class="text-navy-900 hover:underline">Assign your first allowance</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
