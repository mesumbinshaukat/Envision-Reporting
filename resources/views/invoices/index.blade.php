<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Invoices</h2>
            <a href="{{ route('invoices.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Invoice</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search client..." class="px-4 py-2 border border-navy-900 rounded">
            <select name="status" class="px-4 py-2 border border-navy-900 rounded">
                <option value="">All Status</option>
                <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Partial Paid" {{ request('status') == 'Partial Paid' ? 'selected' : '' }}>Partial Paid</option>
                <option value="Payment Done" {{ request('status') == 'Payment Done' ? 'selected' : '' }}>Payment Done</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From" class="px-4 py-2 border border-navy-900 rounded">
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To" class="px-4 py-2 border border-navy-900 rounded">
            <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Filter</button>
            @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                <a href="{{ route('invoices.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Clear</a>
            @endif
        </form>

        <div class="bg-navy-900 text-white p-4 rounded-lg">
            <h3 class="text-lg font-semibold">Total Amount: Rs.{{ number_format($totalAmount, 2) }}</h3>
        </div>

        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            @if($invoices->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Client</th>
                            <th class="text-left py-3 px-4">Salesperson</th>
                            <th class="text-left py-3 px-4">Amount</th>
                            <th class="text-left py-3 px-4">Tax</th>
                            <th class="text-left py-3 px-4">Status</th>
                            <th class="text-left py-3 px-4">Due Date</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                            <tr class="border-b">
                                <td class="py-3 px-4 font-semibold">{{ $invoice->client->name }}</td>
                                <td class="py-3 px-4">{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</td>
                                <td class="py-3 px-4">Rs.{{ number_format($invoice->amount, 2) }}</td>
                                <td class="py-3 px-4">Rs.{{ number_format($invoice->tax, 2) }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-sm {{ $invoice->status == 'Payment Done' ? 'bg-green-100 text-green-800' : ($invoice->status == 'Partial Paid' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        @if($invoice->status != 'Payment Done')
                                            <button onclick="openPaymentModal({{ $invoice->id }}, '{{ $invoice->client->name }}', {{ $invoice->amount }}, {{ $invoice->paid_amount }}, {{ $invoice->remaining_amount > 0 ? $invoice->remaining_amount : $invoice->amount }})" class="text-green-600 hover:underline font-semibold">Pay</button>
                                        @endif
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-navy-900 hover:underline">View</a>
                                        <a href="{{ route('invoices.pdf', $invoice) }}" class="text-navy-900 hover:underline">PDF</a>
                                        <a href="{{ route('invoices.edit', $invoice) }}" class="text-navy-900 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="inline" onsubmit="return confirm('Are you sure?');">
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
                <div class="p-4">{{ $invoices->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No invoices found.</p>
                    <a href="{{ route('invoices.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Create your first invoice</a>
                </div>
            @endif
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md border-2 border-navy-900">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-navy-900">Pay Invoice</h3>
                <button onclick="closePaymentModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>

            <form id="paymentForm" method="POST" action="">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Client</label>
                        <input type="text" id="modalClientName" readonly class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Total Invoice Amount</label>
                        <input type="text" id="modalTotalAmount" readonly class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Already Paid</label>
                        <input type="text" id="modalPaidAmount" readonly class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Remaining Amount Due</label>
                        <input type="text" id="modalRemainingAmount" readonly class="w-full px-4 py-2 border border-gray-300 rounded bg-red-50 font-bold text-red-600">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Payment Amount *</label>
                        <input type="number" name="payment_amount" id="paymentAmount" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-navy-900 rounded" placeholder="Enter amount to pay">
                        <p class="text-xs text-gray-600 mt-1">Enter the amount you're paying now</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Payment Date *</label>
                        <input type="date" name="payment_date" id="paymentDate" required class="w-full px-4 py-2 border border-navy-900 rounded" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded p-3">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> If payment amount equals remaining amount, status will be "Payment Done". If less, status will be "Partial Paid".
                        </p>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90 font-semibold">
                        Process Payment
                    </button>
                    <button type="button" onclick="closePaymentModal()" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let maxPaymentAmount = 0;

        function openPaymentModal(invoiceId, clientName, totalAmount, paidAmount, remainingAmount) {
            document.getElementById('paymentModal').classList.remove('hidden');
            document.getElementById('paymentForm').action = `/invoices/${invoiceId}/pay`;
            document.getElementById('modalClientName').value = clientName;
            document.getElementById('modalTotalAmount').value = 'Rs. ' + parseFloat(totalAmount).toFixed(2);
            document.getElementById('modalPaidAmount').value = 'Rs. ' + parseFloat(paidAmount).toFixed(2);
            document.getElementById('modalRemainingAmount').value = 'Rs. ' + parseFloat(remainingAmount).toFixed(2);
            document.getElementById('paymentAmount').value = parseFloat(remainingAmount).toFixed(2);
            document.getElementById('paymentAmount').max = parseFloat(remainingAmount).toFixed(2);
            maxPaymentAmount = parseFloat(remainingAmount);
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.getElementById('paymentForm').reset();
        }

        // Prevent overpayment
        document.addEventListener('DOMContentLoaded', function() {
            const paymentAmountInput = document.getElementById('paymentAmount');
            if (paymentAmountInput) {
                paymentAmountInput.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    if (value > maxPaymentAmount) {
                        this.value = maxPaymentAmount.toFixed(2);
                        alert('Payment amount cannot exceed the remaining amount due: Rs. ' + maxPaymentAmount.toFixed(2));
                    }
                    if (value < 0) {
                        this.value = 0;
                    }
                });
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePaymentModal();
            }
        });
    </script>
</x-app-layout>
