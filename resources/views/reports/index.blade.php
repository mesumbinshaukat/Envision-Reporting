<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900">Reports</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Report Generation Form -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Generate Audit Report</h3>
            <p class="text-gray-600 mb-6">Select a date range to view and generate comprehensive audit report including all invoices, expenses, salary releases, and bonuses.</p>

            <form method="GET" action="{{ route('reports.index') }}" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-navy-900 mb-1">From Date *</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-navy-900 mb-1">To Date *</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to', now()->format('Y-m-d')) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
                    </div>
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-navy-900 text-white rounded hover:bg-opacity-90 font-semibold">
                    View Report
                </button>
            </form>
        </div>

        @if($reportData)
            <!-- PDF Download Button -->
            <div class="flex justify-center">
                <form method="POST" action="{{ route('reports.audit') }}" class="w-full">
                    @csrf
                    <input type="hidden" name="date_from" value="{{ $reportData['date_from'] }}">
                    <input type="hidden" name="date_to" value="{{ $reportData['date_to'] }}">
                    <button type="submit" class="w-full px-6 py-3 bg-navy-900 text-white rounded hover:bg-opacity-90 font-semibold">
                        📄 Generate PDF Report
                    </button>
                </form>
            </div>

            <!-- Summary Section -->
            <div class="bg-white border border-navy-900 rounded-lg p-6">
                <h3 class="text-xl font-bold text-navy-900 mb-4">Summary ({{ date('M d, Y', strtotime($reportData['date_from'])) }} to {{ date('M d, Y', strtotime($reportData['date_to'])) }})</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="border border-gray-300 rounded p-4">
                        <div class="text-sm text-gray-600">Total Invoices</div>
                        <div class="text-2xl font-bold text-navy-900">Rs.{{ number_format($reportData['total_invoices'], 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Paid: Rs.{{ number_format($reportData['total_paid_invoices'], 2) }}</div>
                        <div class="text-xs text-gray-500">Unpaid: Rs.{{ number_format($reportData['total_unpaid_invoices'], 2) }}</div>
                    </div>
                    <div class="border border-gray-300 rounded p-4">
                        <div class="text-sm text-gray-600">Total Expenses</div>
                        <div class="text-2xl font-bold text-red-600">Rs.{{ number_format($reportData['total_expenses'], 2) }}</div>
                    </div>
                    <div class="border border-gray-300 rounded p-4">
                        <div class="text-sm text-gray-600">Total Salaries</div>
                        <div class="text-2xl font-bold text-red-600">Rs.{{ number_format($reportData['total_salaries'], 2) }}</div>
                    </div>
                    <div class="border border-gray-300 rounded p-4">
                        <div class="text-sm text-gray-600">Total Bonuses</div>
                        <div class="text-2xl font-bold text-navy-900">Rs.{{ number_format($reportData['total_bonuses'], 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">(Separate from net income)</div>
                    </div>
                </div>
                <div class="mt-4 p-4 bg-navy-900 text-white rounded">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold">Net Income (Invoices - Expenses - Salaries):</span>
                        <span class="text-2xl font-bold">Rs.{{ number_format($reportData['net_income'], 2) }}</span>
                    </div>
                    <div class="text-sm mt-1 opacity-75">Note: Bonuses are excluded from net income calculation</div>
                </div>
            </div>

            <!-- Detailed Report Table -->
            <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
                <div class="p-4 bg-navy-900 text-white">
                    <h3 class="text-xl font-bold">Detailed Transactions</h3>
                </div>

                <table class="min-w-full">
                    <thead class="bg-gray-100 border-b-2 border-navy-900">
                        <tr>
                            <th class="text-left py-3 px-4 text-navy-900 font-semibold">Date</th>
                            <th class="text-left py-3 px-4 text-navy-900 font-semibold">Type</th>
                            <th class="text-left py-3 px-4 text-navy-900 font-semibold">Description</th>
                            <th class="text-left py-3 px-4 text-navy-900 font-semibold">Related</th>
                            <th class="text-left py-3 px-4 text-navy-900 font-semibold">Status</th>
                            <th class="text-right py-3 px-4 text-navy-900 font-semibold">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $allTransactions = collect();
                            
                            foreach($reportData['invoices'] as $invoice) {
                                $allTransactions->push([
                                    'date' => $invoice->created_at,
                                    'type' => 'Invoice',
                                    'description' => 'Invoice #' . $invoice->id,
                                    'related' => $invoice->client->name . ($invoice->employee ? ' (via ' . $invoice->employee->name . ')' : ''),
                                    'status' => $invoice->status,
                                    'amount' => $invoice->amount,
                                    'is_income' => true,
                                ]);
                            }
                            
                            foreach($reportData['expenses'] as $expense) {
                                $allTransactions->push([
                                    'date' => $expense->date,
                                    'type' => 'Expense',
                                    'description' => $expense->description,
                                    'related' => '-',
                                    'status' => '-',
                                    'amount' => $expense->amount,
                                    'is_income' => false,
                                ]);
                            }
                            
                            foreach($reportData['salaryReleases'] as $salary) {
                                $allTransactions->push([
                                    'date' => $salary->release_date,
                                    'type' => 'Salary',
                                    'description' => 'Salary for ' . ($salary->month ? date('M Y', strtotime($salary->month . '-01')) : 'N/A'),
                                    'related' => $salary->employee->name,
                                    'status' => ucfirst($salary->release_type),
                                    'amount' => $salary->total_amount,
                                    'is_income' => false,
                                ]);
                            }
                            
                            foreach($reportData['bonuses'] as $bonus) {
                                $allTransactions->push([
                                    'date' => $bonus->date,
                                    'type' => 'Bonus',
                                    'description' => $bonus->description ?? 'Bonus',
                                    'related' => $bonus->employee->name,
                                    'status' => $bonus->released ? 'Released' : 'Pending',
                                    'amount' => $bonus->amount,
                                    'is_income' => false,
                                ]);
                            }
                            
                            $allTransactions = $allTransactions->sortByDesc('date');
                        @endphp

                        @foreach($allTransactions as $transaction)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4">{{ $transaction['date']->format('M d, Y') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-sm font-semibold
                                        {{ $transaction['type'] == 'Invoice' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $transaction['type'] == 'Expense' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $transaction['type'] == 'Salary' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $transaction['type'] == 'Bonus' ? 'bg-purple-100 text-purple-800' : '' }}">
                                        {{ $transaction['type'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">{{ $transaction['description'] }}</td>
                                <td class="py-3 px-4 text-sm text-gray-600">{{ $transaction['related'] }}</td>
                                <td class="py-3 px-4">
                                    @if($transaction['status'] != '-')
                                        <span class="text-sm">{{ $transaction['status'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-semibold {{ $transaction['is_income'] ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction['is_income'] ? '+' : '-' }}Rs.{{ number_format($transaction['amount'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
