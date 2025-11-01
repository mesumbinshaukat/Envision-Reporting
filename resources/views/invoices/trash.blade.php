<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Trash Invoices</h2>
            <a href="{{ route('invoices.index') }}" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Back to Invoices</a>
        </div>
    </x-slot>

    <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
        @if($invoices->count() > 0)
            <table class="min-w-full">
                <thead class="bg-navy-900 text-white">
                    <tr>
                        <th class="text-left py-3 px-4">Client</th>
                        <th class="text-left py-3 px-4">Amount</th>
                        <th class="text-left py-3 px-4">Status</th>
                        <th class="text-left py-3 px-4">Deleted By</th>
                        <th class="text-left py-3 px-4">Deleted At</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr class="border-b">
                            <td class="py-3 px-4">
                                {{ $invoice->is_one_time ? $invoice->one_time_client_name : ($invoice->client ? $invoice->client->name : 'N/A') }}
                            </td>
                            <td class="py-3 px-4">Rs.{{ number_format($invoice->amount, 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded text-xs {{ $invoice->approval_status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($invoice->approval_status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($invoice->approval_status == 'rejected')
                                    <span class="text-red-600">Admin (Rejected)</span>
                                @else
                                    <span class="text-gray-600">Admin</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">{{ $invoice->deleted_at->format('M d, Y H:i') }}</td>
                            <td class="py-3 px-4">
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('invoices.restore', $invoice) }}">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:underline text-sm">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('invoices.force-delete', $invoice) }}" onsubmit="return confirm('Permanently delete this invoice? This cannot be undone!');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-sm">Delete Permanently</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="p-4">
                {{ $invoices->links() }}
            </div>
        @else
            <div class="p-8 text-center text-gray-600">
                <p>No deleted invoices.</p>
            </div>
        @endif
    </div>
</x-app-layout>
