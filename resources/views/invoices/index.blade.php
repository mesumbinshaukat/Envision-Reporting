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
</x-app-layout>
