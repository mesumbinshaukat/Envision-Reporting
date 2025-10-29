<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Client Details</h2>
            <div class="flex gap-2">
                <a href="{{ route('clients.edit', $client) }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Edit</a>
                <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <div class="bg-white border border-navy-900 rounded-lg p-6 space-y-6">
            @if($client->picture)
                <div class="flex justify-center">
                    <img src="{{ asset('storage/' . $client->picture) }}" alt="{{ $client->name }}" class="h-32 w-32 rounded-full object-cover border-2 border-navy-900">
                </div>
            @endif

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Name</h3>
                    <p class="text-lg text-navy-900">{{ $client->name }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Email</h3>
                    <p class="text-lg text-navy-900">{{ $client->email ?? 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Primary Contact</h3>
                    <p class="text-lg text-navy-900">{{ $client->primary_contact ?? 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Secondary Contact</h3>
                    <p class="text-lg text-navy-900">{{ $client->secondary_contact ?? 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Website</h3>
                    @if($client->website)
                        <a href="{{ $client->website }}" target="_blank" class="text-lg text-navy-900 hover:underline">{{ $client->website }}</a>
                    @else
                        <p class="text-lg text-navy-900">N/A</p>
                    @endif
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Created</h3>
                    <p class="text-lg text-navy-900">{{ $client->created_at->format('M d, Y') }}</p>
                </div>
            </div>

            <!-- Related Invoices -->
            <div class="border-t border-navy-900 pt-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Invoices</h3>
                @if($client->invoices->count() > 0)
                    <div class="space-y-2">
                        @foreach($client->invoices as $invoice)
                            <div class="flex justify-between items-center p-3 border border-gray-300 rounded">
                                <div>
                                    <span class="font-semibold">Rs.{{ number_format($invoice->amount, 2) }}</span>
                                    <span class="text-sm text-gray-600">- {{ $invoice->status }}</span>
                                </div>
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-navy-900 hover:underline">View</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-600">No invoices for this client yet.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
