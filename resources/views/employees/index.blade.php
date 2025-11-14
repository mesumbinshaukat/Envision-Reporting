<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Employees</h2>
            <a href="{{ route('employees.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add New Employee</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <form method="GET" action="{{ route('employees.index') }}" class="flex gap-4" id="employeeSearchForm">
            <input type="text" name="search" id="employeeSearch" value="{{ request('search') }}" placeholder="Search by name, email, or role..." class="flex-1 px-4 py-2 border border-navy-900 rounded">
            <select name="employment_type" id="employmentTypeFilter" class="px-4 py-2 border border-navy-900 rounded">
                <option value="">All Types</option>
                <option value="Onsite" {{ request('employment_type') == 'Onsite' ? 'selected' : '' }}>Onsite</option>
                <option value="Project-Based (Freelancer) - Remote" {{ request('employment_type') == 'Project-Based (Freelancer) - Remote' ? 'selected' : '' }}>Remote</option>
                <option value="Contract Arbitrage" {{ request('employment_type') == 'Contract Arbitrage' ? 'selected' : '' }}>Contract Arbitrage</option>
                <option value="Monthly/Annual Contract" {{ request('employment_type') == 'Monthly/Annual Contract' ? 'selected' : '' }}>Monthly/Annual Contract</option>
                <option value="Hybrid" {{ request('employment_type') == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Filter</button>
            @if(request('search') || request('employment_type'))
                <a href="{{ route('employees.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Clear</a>
            @endif
        </form>

        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden" id="employeesTableContainer">
            @if($employees->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left py-3 px-4">Name</th>
                            <th class="text-left py-3 px-4 hidden md:table-cell">Email</th>
                            <th class="text-left py-3 px-4 hidden lg:table-cell">Role</th>
                            <th class="text-left py-3 px-4 hidden lg:table-cell">Employment Type</th>
                            <th class="text-left py-3 px-4 hidden sm:table-cell">Salary</th>
                            <th class="text-left py-3 px-4 hidden xl:table-cell">Commission %</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            @php
                                $employeeInitials = collect(explode(' ', trim($employee->name)))
                                    ->filter()
                                    ->map(fn($segment) => mb_strtoupper(mb_substr($segment, 0, 1)))
                                    ->take(2)
                                    ->implode('');
                            @endphp
                            <tr class="border-b">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 sm:w-8 sm:h-8 rounded-full border border-gray-200 overflow-hidden bg-gray-100 flex items-center justify-center text-sm font-semibold text-gray-600">
                                            @if($employee->profile_photo_url)
                                                <img src="{{ $employee->profile_photo_url }}" alt="{{ $employee->name }} profile photo" class="h-full w-full object-cover">
                                            @else
                                                <span>{{ $employeeInitials }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-semibold text-navy-900 flex items-center gap-2">
                                                <span>{{ $employee->name }}</span>
                                                @if($employee->geolocation_required)
                                                    <span class="text-xs text-green-600" title="Geolocation required">📍</span>
                                                @else
                                                    <span class="text-xs text-orange-600" title="Remote employee">🌐</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500 sm:hidden">
                                                {{ $employee->role }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 hidden md:table-cell">{{ $employee->email }}</td>
                                <td class="py-3 px-4 hidden lg:table-cell">{{ $employee->role }}</td>
                                <td class="py-3 px-4 hidden lg:table-cell">{{ $employee->employment_type }}</td>
                                <td class="py-3 px-4 hidden sm:table-cell">{{ $employee->currency ? $employee->currency->symbol : 'Rs.' }}{{ number_format($employee->salary, 2) }}</td>
                                <td class="py-3 px-4 hidden xl:table-cell">{{ $employee->commission_rate }}%</td>
                                <td class="py-3 px-4">
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <a href="{{ route('employees.show', $employee) }}" class="text-navy-900 hover:underline text-sm">View</a>
                                        <a href="{{ route('employees.edit', $employee) }}" class="text-navy-900 hover:underline text-sm">Edit</a>
                                        <button 
                                            onclick="toggleGeolocation({{ $employee->id }}, this)" 
                                            class="text-sm {{ $employee->geolocation_required ? 'text-orange-600' : 'text-green-600' }} hover:underline text-left"
                                            data-geolocation="{{ $employee->geolocation_required ? '1' : '0' }}"
                                        >
                                            {{ $employee->geolocation_required ? '🌐 Disable Location' : '📍 Require Location' }}
                                        </button>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4" id="employeesPagination">{{ $employees->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No employees found.</p>
                    <a href="{{ route('employees.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Add your first employee</a>
                </div>
            @endif
        </div>
    </div>

    <script>
        let searchTimeout;
        const searchInput = document.getElementById('employeeSearch');
        const typeFilter = document.getElementById('employmentTypeFilter');
        const tableContainer = document.getElementById('employeesTableContainer');

        function performSearch() {
            const searchValue = searchInput.value;
            const typeValue = typeFilter.value;
            const url = new URL('{{ route('employees.index') }}');
            
            if (searchValue) url.searchParams.set('search', searchValue);
            if (typeValue) url.searchParams.set('employment_type', typeValue);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('employeesTableContainer');
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

        // Immediate search on filter change
        typeFilter.addEventListener('change', performSearch);

        // Prevent form submission
        document.getElementById('employeeSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });

        // Toggle geolocation requirement for employee
        function toggleGeolocation(employeeId, button) {
            if (!confirm('Are you sure you want to toggle geolocation tracking for this employee?')) {
                return;
            }

            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = '⏳ Updating...';

            fetch(`/employees/${employeeId}/toggle-geolocation`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button appearance and text
                    const isRequired = data.geolocation_required;
                    button.dataset.geolocation = isRequired ? '1' : '0';
                    button.className = `text-sm ${isRequired ? 'text-orange-600' : 'text-green-600'} hover:underline text-left`;
                    button.textContent = isRequired ? '🌐 Disable Location' : '📍 Require Location';
                    
                    // Update icon next to employee name
                    const nameCell = button.closest('tr').querySelector('td:first-child');
                    const icon = nameCell.querySelector('span');
                    if (icon) {
                        icon.className = `text-xs ${isRequired ? 'text-green-600' : 'text-orange-600'} ml-1`;
                        icon.textContent = isRequired ? '📍' : '🌐';
                        icon.title = isRequired ? 'Geolocation required' : 'Remote employee';
                    }
                    
                    // Show success message
                    alert(data.message);
                } else {
                    alert('Failed to update geolocation setting');
                    button.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating geolocation setting');
                button.textContent = originalText;
            })
            .finally(() => {
                button.disabled = false;
            });
        }
    </script>
</x-app-layout>
