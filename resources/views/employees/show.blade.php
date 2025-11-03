<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Employee Details</h2>
            <div class="flex gap-2">
                <a href="{{ route('employees.edit', $employee) }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Edit</a>
                <a href="{{ route('employees.index') }}" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Name</h3>
                    <p class="text-lg text-navy-900">{{ $employee->name }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Email</h3>
                    <p class="text-lg text-navy-900">{{ $employee->email }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Role</h3>
                    <p class="text-lg text-navy-900">{{ $employee->role }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Marital Status</h3>
                    <p class="text-lg text-navy-900">{{ $employee->marital_status ?? 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Primary Contact</h3>
                    <p class="text-lg text-navy-900">{{ $employee->primary_contact }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Secondary Contact</h3>
                    <p class="text-lg text-navy-900">{{ $employee->secondary_contact ?? 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Employment Type</h3>
                    <p class="text-lg text-navy-900">{{ $employee->employment_type }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Monthly Salary</h3>
                    <p class="text-lg text-navy-900">{{ $employee->currency ? $employee->currency->symbol : 'Rs.' }}{{ number_format($employee->salary, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Commission Rate</h3>
                    <p class="text-lg text-navy-900">{{ $employee->commission_rate }}%</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Joining Date</h3>
                    <p class="text-lg text-navy-900">{{ $employee->joining_date ? $employee->joining_date->format('M d, Y') : 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Last Date</h3>
                    <p class="text-lg text-navy-900">{{ $employee->last_date ? $employee->last_date->format('M d, Y') : 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Created</h3>
                    <p class="text-lg text-navy-900">{{ $employee->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Invoices -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Invoices (as Salesperson)</h3>
            @if($employee->invoices->count() > 0)
                <div class="space-y-2">
                    @foreach($employee->invoices as $invoice)
                        <div class="flex justify-between items-center p-3 border border-gray-300 rounded">
                            <div>
                                <span class="font-semibold">{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->amount, 2) }}</span>
                                <span class="text-sm text-gray-600">- {{ $invoice->client->name }} - {{ $invoice->status }}</span>
                                <span class="text-sm text-gray-600">- Commission: {{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->calculateCommission(), 2) }}</span>
                            </div>
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-navy-900 hover:underline">View</a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-600">No invoices assigned yet.</p>
            @endif
        </div>

        <!-- Bonuses -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Bonuses</h3>
            @if($employee->bonuses->count() > 0)
                <div class="space-y-2">
                    @foreach($employee->bonuses as $bonus)
                        <div class="flex justify-between items-center p-3 border border-gray-300 rounded">
                            <div>
                                <span class="font-semibold">{{ $bonus->currency ? $bonus->currency->symbol : 'Rs.' }}{{ number_format($bonus->amount, 2) }}</span>
                                <span class="text-sm text-gray-600">- {{ $bonus->description ?? 'Bonus' }}</span>
                                <span class="text-sm text-gray-600">- {{ $bonus->date->format('M d, Y') }}</span>
                            </div>
                            <span class="text-sm {{ $bonus->released ? 'text-green-600' : 'text-yellow-600' }}">
                                {{ $bonus->released ? 'Released' : 'Pending' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-600">No bonuses yet.</p>
            @endif
        </div>

        <!-- Salary Releases -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Salary History</h3>
            @if($employee->salaryReleases->count() > 0)
                <div class="space-y-2">
                    @foreach($employee->salaryReleases as $release)
                        <div class="flex justify-between items-center p-3 border border-gray-300 rounded">
                            <div>
                                <span class="font-semibold">{{ $release->currency ? $release->currency->symbol : 'Rs.' }}{{ number_format($release->total_amount, 2) }}</span>
                                <span class="text-sm text-gray-600">- {{ $release->release_date->format('M d, Y') }}</span>
                                <span class="text-sm text-gray-600">- {{ $release->release_type }}</span>
                            </div>
                            <a href="{{ route('salary-releases.show', $release) }}" class="text-navy-900 hover:underline">View</a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-600">No salary releases yet.</p>
            @endif
        </div>
    </div>
</x-app-layout>
