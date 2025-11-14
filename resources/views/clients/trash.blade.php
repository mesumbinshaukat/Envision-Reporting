<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Trash Clients</h2>
            <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Back to Clients</a>
        </div>
    </x-slot>

    <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
        @if($clients->count() > 0)
            <table class="min-w-full">
                <thead class="bg-navy-900 text-white">
                    <tr>
                        <th class="text-left py-3 px-4">Name</th>
                        <th class="text-left py-3 px-4">Email</th>
                        <th class="text-left py-3 px-4">Deleted By</th>
                        <th class="text-left py-3 px-4">Deleted At</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                        <tr class="border-b">
                            <td class="py-3 px-4">{{ $client->name }}</td>
                            <td class="py-3 px-4">{{ $client->email ?? 'N/A' }}</td>
                            <td class="py-3 px-4">
                                @if($client->deletedByEmployee)
                                    <span class="text-blue-600">{{ $client->deletedByEmployee->name }} (Employee)</span>
                                @else
                                    <span class="text-gray-600">Self (Admin)</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">{{ $client->deleted_at->format('M d, Y H:i') }}</td>
                            <td class="py-3 px-4">
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('clients.restore', $client) }}">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:underline text-sm">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('clients.force-delete', $client) }}" onsubmit="return confirm('Permanently delete this client? This cannot be undone!');">
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
                {{ $clients->links() }}
            </div>
        @else
            <div class="p-8 text-center text-gray-600">
                <p>No deleted clients.</p>
            </div>
        @endif
    </div>
</x-app-layout>
