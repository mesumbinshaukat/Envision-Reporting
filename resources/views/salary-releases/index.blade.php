<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Salary Releases</h2>
            <a href="{{ route('salary-releases.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Release Salary</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            @if($salaryReleases->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Employee</th>
                            <th class="text-left py-3 px-4">Month</th>
                            <th class="text-left py-3 px-4">Base Salary</th>
                            <th class="text-left py-3 px-4">Commission</th>
                            <th class="text-left py-3 px-4">Bonus</th>
                            <th class="text-left py-3 px-4">Deductions</th>
                            <th class="text-left py-3 px-4">Total</th>
                            <th class="text-left py-3 px-4">Release Date</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salaryReleases as $release)
                            <tr class="border-b">
                                <td class="py-3 px-4 font-semibold">{{ $release->employee->name }}</td>
                                <td class="py-3 px-4">{{ $release->month ? date('M Y', strtotime($release->month . '-01')) : 'N/A' }}</td>
                                <td class="py-3 px-4">{{ $release->currency ? $release->currency->symbol : ($baseCurrency->symbol ?? 'Rs.') }}{{ number_format($release->base_salary, 2) }}</td>
                                <td class="py-3 px-4">{{ $release->currency ? $release->currency->symbol : ($baseCurrency->symbol ?? 'Rs.') }}{{ number_format($release->commission_amount, 2) }}</td>
                                <td class="py-3 px-4">{{ $release->currency ? $release->currency->symbol : ($baseCurrency->symbol ?? 'Rs.') }}{{ number_format($release->bonus_amount, 2) }}</td>
                                <td class="py-3 px-4">{{ $release->currency ? $release->currency->symbol : ($baseCurrency->symbol ?? 'Rs.') }}{{ number_format($release->deductions, 2) }}</td>
                                <td class="py-3 px-4 font-bold">{{ $release->currency ? $release->currency->symbol : ($baseCurrency->symbol ?? 'Rs.') }}{{ number_format($release->total_amount, 2) }}</td>
                                <td class="py-3 px-4">{{ $release->release_date->format('M d, Y') }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('salary-releases.show', $release) }}" class="text-navy-900 hover:underline">View</a>
                                        <a href="{{ route('salary-releases.pdf', $release) }}" class="text-navy-900 hover:underline">PDF</a>
                                        <form method="POST" action="{{ route('salary-releases.destroy', $release) }}" class="inline" onsubmit="return confirm('Are you sure?');">
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
                <div class="p-4">{{ $salaryReleases->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No salary releases found.</p>
                    <a href="{{ route('salary-releases.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Release your first salary</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
