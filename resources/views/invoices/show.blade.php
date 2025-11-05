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
                    <p class="text-lg text-navy-900">
                        @if($invoice->is_one_time)
                            {{ $invoice->one_time_client_name }}
                            <span class="text-xs text-gray-500">(One-Time Project)</span>
                        @else
                            {{ $invoice->client->name }}
                        @endif
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Salesperson</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Amount</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->amount, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Tax</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->tax, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Net Amount</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->amount - $invoice->tax, 2) }}</p>
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
                    <p class="text-lg text-navy-900">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->calculateCommission(), 2) }} ({{ $invoice->employee->commission_rate }}%)</p>
                </div>
                @endif

                @if($invoice->special_note)
                <div class="col-span-2">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Special Note</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->special_note }}</p>
                </div>
                @endif

                @if($invoice->payment_method)
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Payment Method</h3>
                    <p class="text-lg text-navy-900">
                        @if($invoice->payment_method === 'Other' && $invoice->custom_payment_method)
                            {{ $invoice->custom_payment_method }}
                        @else
                            {{ $invoice->payment_method }}
                        @endif
                    </p>
                </div>
                @endif

                @if($invoice->payment_processing_fee > 0)
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Payment Processing Fee</h3>
                    <p class="text-lg text-navy-900">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->payment_processing_fee, 2) }}</p>
                </div>
                @endif

                @if($invoice->attachments && count($invoice->attachments) > 0)
                <div class="col-span-2">
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Attachments</h3>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($invoice->attachments as $attachment)
                            <a href="{{ Storage::url($attachment) }}" target="_blank" class="flex items-center gap-2 px-4 py-3 border border-gray-300 rounded hover:bg-gray-50 transition">
                                @if(str_ends_with($attachment, '.pdf'))
                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                                <span class="text-sm text-navy-900 truncate">{{ basename($attachment) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
