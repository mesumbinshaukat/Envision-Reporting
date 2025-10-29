<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Employees</h2>
            <a href="{{ route('employees.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add New Employee</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <form method="GET" action="{{ route('employees.index') }}" class="flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or role..." class="flex-1 px-4 py-2 border border-navy-900 rounded">
            <select name="employment_type" class="px-4 py-2 border border-navy-900 rounded">
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

        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            @if($employees->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Name</th>
                            <th class="text-left py-3 px-4">Email</th>
                            <th class="text-left py-3 px-4">Role</th>
                            <th class="text-left py-3 px-4">Employment Type</th>
                            <th class="text-left py-3 px-4">Salary</th>
                            <th class="text-left py-3 px-4">Commission %</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            <tr class="border-b">
                                <td class="py-3 px-4 font-semibold">{{ $employee->name }}</td>
                                <td class="py-3 px-4">{{ $employee->email }}</td>
                                <td class="py-3 px-4">{{ $employee->role }}</td>
                                <td class="py-3 px-4">{{ $employee->employment_type }}</td>
                                <td class="py-3 px-4">Rs.{{ number_format($employee->salary, 2) }}</td>
                                <td class="py-3 px-4">{{ $employee->commission_rate }}%</td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('employees.show', $employee) }}" class="text-navy-900 hover:underline">View</a>
                                        <a href="{{ route('employees.edit', $employee) }}" class="text-navy-900 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('Are you sure?');">
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
                <div class="p-4">{{ $employees->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No employees found.</p>
                    <a href="{{ route('employees.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Add your first employee</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
