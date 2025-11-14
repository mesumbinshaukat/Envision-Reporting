<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Invoice Details</h2>
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

                <!-- Milestones Section -->
                @if($invoice->milestones && $invoice->milestones->count() > 0)
                <div class="col-span-2">
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Invoice Milestones</h3>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        @foreach($invoice->milestones as $milestone)
                        <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                            <div>
                                <p class="text-sm font-medium text-navy-900">{{ $milestone->description ?: 'Milestone ' . ($loop->iteration) }}</p>
                            </div>
                            <p class="text-sm font-bold text-navy-900">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($milestone->amount, 2) }}</p>
                        </div>
                        @endforeach
                        <div class="flex justify-between items-center pt-2">
                            <p class="text-sm font-bold text-gray-700">Total:</p>
                            <p class="text-lg font-bold text-navy-900">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->milestones->sum('amount'), 2) }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Attachments Section -->
                @if($invoice->attachments && $invoice->attachments->count() > 0)
                <div class="col-span-2">
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Attachments</h3>
                    <div class="space-y-2">
                        @foreach($invoice->attachments as $attachment)
                        <div class="flex items-center justify-between p-3 border border-gray-300 rounded bg-white hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $attachment->file_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $attachment->formatted_size }}</p>
                                </div>
                            </div>
                            <a href="{{ Storage::url($attachment->file_path) }}" target="_blank" download class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                Download
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
