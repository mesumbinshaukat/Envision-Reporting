<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Dashboard</h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Total Clients</h3>
                <p class="text-3xl font-bold text-navy-900">{{ $total_clients }}</p>
            </div>
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Total Employees</h3>
                <p class="text-3xl font-bold text-navy-900">{{ $total_employees }}</p>
            </div>
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Pending Invoices</h3>
                <p class="text-3xl font-bold text-navy-900">{{ $pending_invoices }}</p>
            </div>
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Pending Approvals</h3>
                <p class="text-3xl font-bold text-yellow-600">{{ $pending_approvals }}</p>
                <p class="text-xs text-gray-500 mt-1">Employee invoices</p>
            </div>
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Total Expenses</h3>
                <p class="text-3xl font-bold text-navy-900">{{ $baseCurrency->symbol ?? 'Rs.' }}{{ number_format($total_expenses, 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">In base currency</p>
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Recent Invoices</h3>
            @if($recent_invoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-navy-900">
                                <th class="text-left py-2 px-4 text-navy-900">Client</th>
                                <th class="text-left py-2 px-4 text-navy-900">Salesperson</th>
                                <th class="text-left py-2 px-4 text-navy-900">Amount</th>
                                <th class="text-left py-2 px-4 text-navy-900">Status</th>
                                <th class="text-left py-2 px-4 text-navy-900">Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_invoices as $invoice)
                                @if($invoice->client)
                                <tr class="border-b">
                                    <td class="py-2 px-4">{{ $invoice->client->name }}</td>
                                    <td class="py-2 px-4">{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</td>
                                    <td class="py-2 px-4">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->amount, 2) }}</td>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded text-sm {{ $invoice->status == 'Payment Done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $invoice->status }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4">{{ $invoice->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600">No invoices yet.</p>
            @endif
        </div>

        <!-- Recent Expenses -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Recent Expenses</h3>
            @if($recent_expenses->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-navy-900">
                                <th class="text-left py-2 px-4 text-navy-900">Description</th>
                                <th class="text-left py-2 px-4 text-navy-900">Amount</th>
                                <th class="text-left py-2 px-4 text-navy-900">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_expenses as $expense)
                                <tr class="border-b">
                                    <td class="py-2 px-4">{{ $expense->description }}</td>
                                    <td class="py-2 px-4">{{ $expense->currency ? $expense->currency->symbol : 'Rs.' }}{{ number_format($expense->amount, 2) }}</td>
                                    <td class="py-2 px-4">{{ $expense->date->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600">No expenses yet.</p>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('clients.create') }}" class="block bg-navy-900 text-white rounded-lg p-6 text-center hover:bg-opacity-90">
                <h4 class="text-lg font-bold mb-2">Add New Client</h4>
                <p class="text-sm">Create a new client record</p>
            </a>
            <a href="{{ route('employees.create') }}" class="block bg-navy-900 text-white rounded-lg p-6 text-center hover:bg-opacity-90">
                <h4 class="text-lg font-bold mb-2">Add New Employee</h4>
                <p class="text-sm">Register a new employee</p>
            </a>
            <a href="{{ route('invoices.create') }}" class="block bg-navy-900 text-white rounded-lg p-6 text-center hover:bg-opacity-90">
                <h4 class="text-lg font-bold mb-2">Create Invoice</h4>
                <p class="text-sm">Generate a new invoice</p>
            </a>
        </div>
    </div>
</x-app-layout>
