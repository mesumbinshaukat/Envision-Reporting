<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Invoice Details</h2>
            <div class="flex gap-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Download PDF</a>
                <a href="{{ route('invoices.edit', $invoice) }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Edit</a>
                <a href="{{ route('invoices.index') }}" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <div class="bg-white border border-navy-900 rounded-lg p-6 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Client</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->client->name }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Salesperson</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Amount</h3>
                    <p class="text-lg text-navy-900">Rs.{{ number_format($invoice->amount, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Tax</h3>
                    <p class="text-lg text-navy-900">Rs.{{ number_format($invoice->tax, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Net Amount</h3>
                    <p class="text-lg text-navy-900">Rs.{{ number_format($invoice->amount - $invoice->tax, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Status</h3>
                    <span class="px-3 py-1 rounded text-sm {{ $invoice->status == 'Payment Done' ? 'bg-green-100 text-green-800' : ($invoice->status == 'Partial Paid' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ $invoice->status }}
                    </span>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Due Date</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Created</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->created_at->format('M d, Y') }}</p>
                </div>

                @if($invoice->employee)
                <div class="col-span-2">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Commission</h3>
                    <p class="text-lg text-navy-900">Rs.{{ number_format($invoice->calculateCommission(), 2) }} ({{ $invoice->employee->commission_rate }}%)</p>
                </div>
                @endif

                @if($invoice->special_note)
                <div class="col-span-2">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Special Note</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->special_note }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
