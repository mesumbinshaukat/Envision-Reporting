<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Salary Release Details</h2>
            <div class="flex gap-2">
                <a href="{{ route('salary-releases.pdf', $salaryRelease) }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Download Slip</a>
                <a href="{{ route('salary-releases.index') }}" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <div class="bg-white border border-navy-900 rounded-lg p-6 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Employee</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->employee->name }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Month</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->month ? date('F Y', strtotime($salaryRelease->month . '-01')) : 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Release Date</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->release_date->format('M d, Y') }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Base Salary</h3>
                    <p class="text-lg text-navy-900">Rs.{{ number_format($salaryRelease->base_salary, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Commission (from paid invoices)</h3>
                    <p class="text-lg text-navy-900">Rs.{{ number_format($salaryRelease->commission_amount, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Bonus Amount</h3>
                    <p class="text-lg text-navy-900">Rs.{{ number_format($salaryRelease->bonus_amount, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Deductions</h3>
                    <p class="text-lg text-navy-900">Rs.{{ number_format($salaryRelease->deductions, 2) }}</p>
                </div>

                <div class="col-span-2 border-t border-navy-900 pt-4">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Amount</h3>
                    <p class="text-2xl font-bold text-navy-900">Rs.{{ number_format($salaryRelease->total_amount, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Release Type</h3>
                    <p class="text-lg text-navy-900">{{ ucfirst($salaryRelease->release_type) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Created</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->created_at->format('M d, Y') }}</p>
                </div>

                @if($salaryRelease->notes)
                <div class="col-span-2">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Notes</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
