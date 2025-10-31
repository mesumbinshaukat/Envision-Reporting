<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Invoices</h2>
            <a href="{{ route('invoices.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Invoice</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4" id="invoiceSearchForm">
            <input type="text" name="search" id="invoiceSearch" value="{{ request('search') }}" placeholder="Search client..." class="px-4 py-2 border border-navy-900 rounded">
            <select name="status" id="statusFilter" class="px-4 py-2 border border-navy-900 rounded">
                <option value="">All Status</option>
                <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Partial Paid" {{ request('status') == 'Partial Paid' ? 'selected' : '' }}>Partial Paid</option>
                <option value="Payment Done" {{ request('status') == 'Payment Done' ? 'selected' : '' }}>Payment Done</option>
            </select>
            <input type="date" name="date_from" id="dateFrom" value="{{ request('date_from') }}" placeholder="From" class="px-4 py-2 border border-navy-900 rounded">
            <input type="date" name="date_to" id="dateTo" value="{{ request('date_to') }}" placeholder="To" class="px-4 py-2 border border-navy-900 rounded">
            <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Filter</button>
            @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                <a href="{{ route('invoices.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Clear</a>
            @endif
        </form>

        <div class="bg-navy-900 text-white p-4 rounded-lg" id="totalAmountBox">
            <h3 class="text-lg font-semibold">Total Amount: Rs.{{ number_format($totalAmount, 2) }}</h3>
        </div>

        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden" id="invoicesTableContainer">
            @if($invoices->count() > 0)
                <!-- Mobile scroll hint -->
                <div class="md:hidden bg-blue-50 border-b border-blue-200 px-4 py-2 text-xs text-blue-800">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                        Scroll horizontally to view all columns
                    </span>
                </div>
                
                <!-- Responsive table wrapper with horizontal scroll -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-navy-900 text-white">
                            <tr>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Client</th>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Salesperson</th>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Amount</th>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Paid</th>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Remaining</th>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Status</th>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Due Date</th>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Latest Payment</th>
                                <th class="text-left py-3 px-3 md:px-4 text-xs md:text-sm font-semibold whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm">
                                        <div class="font-semibold whitespace-nowrap">
                                            @if($invoice->is_one_time)
                                                <span class="text-blue-600">{{ Str::limit($invoice->one_time_client_name, 20) }}</span>
                                                <span class="text-xs text-gray-500 block md:inline md:ml-1">(One-Time)</span>
                                            @else
                                                {{ Str::limit($invoice->client->name, 20) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm whitespace-nowrap">{{ $invoice->employee ? Str::limit($invoice->employee->name, 15) : 'Self' }}</td>
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm whitespace-nowrap">Rs.{{ number_format($invoice->amount, 2) }}</td>
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm text-green-600 font-semibold whitespace-nowrap">Rs.{{ number_format($invoice->paid_amount, 2) }}</td>
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm text-red-600 font-semibold whitespace-nowrap">Rs.{{ number_format($invoice->remaining_amount, 2) }}</td>
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm">
                                        <span class="px-2 py-1 rounded text-xs whitespace-nowrap {{ $invoice->status == 'Payment Done' ? 'bg-green-100 text-green-800' : ($invoice->status == 'Partial Paid' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $invoice->status }}
                                        </span>
                                    </td>
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm whitespace-nowrap">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</td>
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm whitespace-nowrap">
                                        @php
                                            $latestPayment = $invoice->payments()->latest('payment_date')->first();
                                        @endphp
                                        @if($latestPayment)
                                            <span class="text-blue-600 font-semibold">{{ $latestPayment->payment_date->format('M d, Y') }}</span>
                                        @else
                                            <span class="text-gray-400 text-xs">Not paid yet</span>
                                        @endif
                                    </td>
                                    <td class="py-2 md:py-3 px-3 md:px-4 text-xs md:text-sm">
                                        <div class="flex flex-col gap-1">
                                            @if($invoice->status != 'Payment Done')
                                                <button onclick="openPaymentModal({{ $invoice->id }}, '{{ $invoice->is_one_time ? addslashes($invoice->one_time_client_name) : addslashes($invoice->client->name) }}', {{ $invoice->amount }}, {{ $invoice->paid_amount }}, {{ $invoice->remaining_amount > 0 ? $invoice->remaining_amount : $invoice->amount }})" class="text-green-600 hover:underline font-semibold text-xs text-left">Pay</button>
                                            @endif
                                            <a href="{{ route('invoices.show', $invoice) }}" class="text-navy-900 hover:underline text-xs">View</a>
                                            <a href="{{ route('invoices.pdf', $invoice) }}" class="text-navy-900 hover:underline text-xs">PDF</a>
                                            <a href="{{ route('invoices.edit', $invoice) }}" class="text-navy-900 hover:underline text-xs">Edit</a>
                                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline text-xs text-left">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $invoices->appends(request()->query())->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No invoices found.</p>
                    <a href="{{ route('invoices.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Create your first invoice</a>
                </div>
            @endif
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-3xl border-2 border-navy-900 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-navy-900">Pay Invoice</h3>
                <button onclick="closePaymentModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>

            <form id="paymentForm" method="POST" action="">
                @csrf
                @method('PUT')

                <!-- Invoice Details (Read-only) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Client</label>
                        <input type="text" id="modalClientName" readonly class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Total Invoice Amount</label>
                        <input type="text" id="modalTotalAmount" readonly class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Already Paid</label>
                        <input type="text" id="modalPaidAmount" readonly class="w-full px-4 py-2 border border-gray-300 rounded bg-green-50 text-green-700 font-semibold text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Remaining Amount Due</label>
                        <input type="text" id="modalRemainingAmount" readonly class="w-full px-4 py-2 border border-gray-300 rounded bg-red-50 font-bold text-red-600 text-sm">
                    </div>
                </div>

                <!-- Payment Input Fields -->
                <div class="border-t border-gray-300 pt-6 mb-6">
                    <h4 class="text-lg font-semibold text-navy-900 mb-4">Payment Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-navy-900 mb-1">Payment Amount *</label>
                            <input type="number" name="payment_amount" id="paymentAmount" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-navy-900 rounded" placeholder="Enter amount to pay">
                            <p class="text-xs text-gray-600 mt-1">Enter the amount you're paying now</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-navy-900 mb-1">Payment Date *</label>
                            <input type="date" name="payment_date" id="paymentDate" required class="w-full px-4 py-2 border border-navy-900 rounded" value="{{ date('Y-m-d') }}">
                            <p class="text-xs text-gray-600 mt-1">Date when payment was received</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-navy-900 mb-1">Notes (Optional)</label>
                        <textarea name="notes" id="paymentNotes" rows="2" class="w-full px-4 py-2 border border-navy-900 rounded" placeholder="Add payment notes or milestone details (e.g., '20% upfront', 'Milestone 1 completed')"></textarea>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-6">
                    <p class="text-sm text-blue-800">
                        <strong>Note:</strong> If payment amount equals remaining amount, status will be "Payment Done". If less, status will be "Partial Paid".
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" class="flex-1 px-6 py-3 bg-navy-900 text-white rounded hover:bg-opacity-90 font-semibold">
                        Process Payment
                    </button>
                    <button type="button" onclick="closePaymentModal()" class="px-6 py-3 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">
                        Cancel
                    </button>
                </div>
            </form>
            </div>
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

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closePaymentModal();
            }
        });

        // Async Search Functionality
        let searchTimeout;
        const searchInput = document.getElementById('invoiceSearch');
        const statusFilter = document.getElementById('statusFilter');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const tableContainer = document.getElementById('invoicesTableContainer');
        const totalAmountBox = document.getElementById('totalAmountBox');

        function performSearch() {
            const searchValue = searchInput.value;
            const statusValue = statusFilter.value;
            const dateFromValue = dateFrom.value;
            const dateToValue = dateTo.value;
            const url = new URL('{{ route('invoices.index') }}');
            
            if (searchValue) url.searchParams.set('search', searchValue);
            if (statusValue) url.searchParams.set('status', statusValue);
            if (dateFromValue) url.searchParams.set('date_from', dateFromValue);
            if (dateToValue) url.searchParams.set('date_to', dateToValue);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newTableContent = doc.getElementById('invoicesTableContainer');
                const newTotalAmount = doc.getElementById('totalAmountBox');
                
                if (newTableContent) {
                    tableContainer.innerHTML = newTableContent.innerHTML;
                }
                if (newTotalAmount) {
                    totalAmountBox.innerHTML = newTotalAmount.innerHTML;
                }
                
                // Update URL without reload
                window.history.pushState({}, '', url);
            })
            .catch(error => console.error('Search error:', error));
        }

        // Debounced search on input
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        });

        // Immediate search on filter changes
        statusFilter.addEventListener('change', performSearch);
        dateFrom.addEventListener('change', performSearch);
        dateTo.addEventListener('change', performSearch);

        // Prevent form submission
        document.getElementById('invoiceSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    </script>
</x-app-layout>
