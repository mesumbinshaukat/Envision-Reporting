<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Employee Dashboard</h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Welcome Section -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-2">Welcome, {{ $employee->name }}!</h3>
            <p class="text-gray-600">{{ $employee->role }} - {{ $employee->employment_type }}</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <div class="text-sm text-gray-600 mb-1">Total Commission Paid</div>
                <div class="text-3xl font-bold text-green-600">{{ $employee->currency ? $employee->currency->symbol : 'Rs.' }}{{ number_format($total_commission_paid, 2) }}</div>
                <p class="text-xs text-gray-500 mt-2">Commissions released to you</p>
            </div>

            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <div class="text-sm text-gray-600 mb-1">Pending Commission</div>
                <div class="text-3xl font-bold text-yellow-600">{{ $employee->currency ? $employee->currency->symbol : 'Rs.' }}{{ number_format($pending_commission, 2) }}</div>
                <p class="text-xs text-gray-500 mt-2">Awaiting salary release</p>
            </div>

            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <div class="text-sm text-gray-600 mb-1">Commission Rate</div>
                <div class="text-3xl font-bold text-navy-900">{{ $employee->commission_rate }}%</div>
                <p class="text-xs text-gray-500 mt-2">Your commission percentage</p>
            </div>
        </div>

        <!-- Invoice Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <div class="text-sm text-gray-600 mb-1">Pending Approval</div>
                <div class="text-3xl font-bold text-yellow-600">{{ $pending_invoices }}</div>
                <p class="text-xs text-gray-500 mt-2">Invoices awaiting admin approval</p>
            </div>

            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <div class="text-sm text-gray-600 mb-1">Approved Invoices</div>
                <div class="text-3xl font-bold text-green-600">{{ $approved_invoices }}</div>
                <p class="text-xs text-gray-500 mt-2">Invoices approved by admin</p>
            </div>

            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <div class="text-sm text-gray-600 mb-1">Rejected Invoices</div>
                <div class="text-3xl font-bold text-red-600">{{ $rejected_invoices }}</div>
                <p class="text-xs text-gray-500 mt-2">Invoices rejected by admin</p>
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            <div class="p-4 bg-navy-900 text-white">
                <h3 class="text-xl font-bold">Your Recent Invoices</h3>
            </div>
            
            @if($recent_invoices->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold">Client</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold">Amount</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold">Status</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold">Approval</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent_invoices as $invoice)
                            <tr class="border-b">
                                <td class="py-3 px-4">{{ $invoice->is_one_time ? $invoice->one_time_client_name : ($invoice->client ? $invoice->client->name : 'N/A') }}</td>
                                <td class="py-3 px-4">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->amount, 2) }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-xs {{ $invoice->status == 'Payment Done' ? 'bg-green-100 text-green-800' : ($invoice->status == 'Partial Paid' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-xs {{ $invoice->approval_status == 'approved' ? 'bg-green-100 text-green-800' : ($invoice->approval_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ ucfirst($invoice->approval_status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">{{ $invoice->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No invoices created yet.</p>
                    <a href="{{ route('invoices.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Create your first invoice</a>
                </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4">Quick Actions</h3>
            <div class="flex gap-4">
                <a href="{{ route('invoices.create') }}" class="px-6 py-3 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Invoice</a>
                <a href="{{ route('clients.create') }}" class="px-6 py-3 bg-navy-900 text-white rounded hover:bg-opacity-90">Add Client</a>
                <a href="{{ route('invoices.index') }}" class="px-6 py-3 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">View All Invoices</a>
            </div>
        </div>
    </div>
</x-app-layout>
