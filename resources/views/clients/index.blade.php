<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Clients</h2>
            <a href="{{ route('clients.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add New Client</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search Form -->
        <form method="GET" action="{{ route('clients.index') }}" class="flex gap-4" id="clientSearchForm">
            <input type="text" name="search" id="clientSearch" value="{{ request('search') }}" placeholder="Search by name or email..." class="flex-1 px-4 py-2 border border-navy-900 rounded">
            <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Search</button>
            @if(request('search'))
                <a href="{{ route('clients.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Clear</a>
            @endif
        </form>

        <!-- Clients Table -->
        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden" id="clientsTableContainer">
            @if($clients->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Picture</th>
                            <th class="text-left py-3 px-4">Name</th>
                            <th class="text-left py-3 px-4">Email</th>
                            <th class="text-left py-3 px-4">Primary Contact</th>
                            <th class="text-left py-3 px-4">Website</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                            <tr class="border-b">
                                <td class="py-3 px-4">
                                    @if($client->picture)
                                        <img src="{{ asset('storage/' . $client->picture) }}" alt="{{ $client->name }}" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        <div class="h-10 w-10 rounded-full bg-navy-900 text-white flex items-center justify-center">
                                            {{ substr($client->name, 0, 1) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-semibold">{{ $client->name }}</td>
                                <td class="py-3 px-4">{{ $client->email ?? 'N/A' }}</td>
                                <td class="py-3 px-4">{{ $client->primary_contact ?? 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    @if($client->website)
                                        <a href="{{ $client->website }}" target="_blank" class="text-navy-900 hover:underline">Visit</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('clients.show', $client) }}" class="text-navy-900 hover:underline">View</a>
                                        <a href="{{ route('clients.edit', $client) }}" class="text-navy-900 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this client?');">
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
                
                <!-- Pagination -->
                <div class="p-4">
                   <div class="p-4" id="clientsPagination">{{ $clients->links() }}</div>
                </div>
            @else
                <div class="p-8 text-center text-gray-600" id="noClientsFound">
                    <p>No clients found.</p>
                    <a href="{{ route('clients.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Create your first client</a>
                </div>
            @endif
        </div>
    </div>

    <script>
        let searchTimeout;
        const searchInput = document.getElementById('clientSearch');
        const tableContainer = document.getElementById('clientsTableContainer');

        function performSearch() {
            const searchValue = searchInput.value;
            const url = new URL('{{ route('clients.index') }}');
            
            if (searchValue) url.searchParams.set('search', searchValue);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('clientsTableContainer');
                if (newContent) {
                    tableContainer.innerHTML = newContent.innerHTML;
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

        // Prevent form submission
        document.getElementById('clientSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    </script>
</x-app-layout>
