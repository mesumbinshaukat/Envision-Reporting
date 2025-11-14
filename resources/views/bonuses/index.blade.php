<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Bonuses</h2>
            <a href="{{ route('bonuses.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add Bonus</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            @if($bonuses->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Employee</th>
                            <th class="text-left py-3 px-4">Amount</th>
                            <th class="text-left py-3 px-4">Description</th>
                            <th class="text-left py-3 px-4">Date</th>
                            <th class="text-left py-3 px-4">Release Type</th>
                            <th class="text-left py-3 px-4">Status</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bonuses as $bonus)
                            <tr class="border-b">
                                <td class="py-3 px-4 font-semibold">{{ $bonus->employee->name }}</td>
                                <td class="py-3 px-4">{{ $bonus->currency ? $bonus->currency->symbol : ($baseCurrency->symbol ?? 'Rs.') }}{{ number_format($bonus->amount, 2) }}</td>
                                <td class="py-3 px-4">{{ $bonus->description ?? 'N/A' }}</td>
                                <td class="py-3 px-4">{{ $bonus->date->format('M d, Y') }}</td>
                                <td class="py-3 px-4">{{ ucfirst(str_replace('_', ' ', $bonus->release_type)) }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-sm {{ $bonus->released ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $bonus->released ? 'Released' : 'Pending' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('bonuses.edit', $bonus) }}" class="text-navy-900 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('bonuses.destroy', $bonus) }}" class="inline" onsubmit="return confirm('Are you sure?');">
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
                <div class="p-4">{{ $bonuses->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No bonuses found.</p>
                    <a href="{{ route('bonuses.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Add your first bonus</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
