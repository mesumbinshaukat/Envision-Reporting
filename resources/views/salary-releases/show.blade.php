<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">Salary Release Details</h2>
            <div class="flex gap-2">
                <a href="{{ route('salary-releases.pdf', $salaryRelease) }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Download Slip</a>
                <a href="{{ route('salary-releases.index') }}" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <div class="bg-white border border-navy-900 rounded-lg p-6 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Employee</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->employee->name }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Month</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->month ? date('F Y', strtotime($salaryRelease->month . '-01')) : 'N/A' }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Release Date</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->release_date->format('M d, Y') }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Base Salary</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->base_salary, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Commission (from paid invoices)</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->commission_amount, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Bonus Amount</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->bonus_amount, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Deductions</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->deductions, 2) }}</p>
                </div>

                <div class="col-span-2 border-t border-navy-900 pt-4">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Amount</h3>
                    <p class="text-2xl font-bold text-navy-900">{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->total_amount, 2) }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Release Type</h3>
                    <p class="text-lg text-navy-900">{{ ucfirst($salaryRelease->release_type) }}</p>
                </div>

                @if($salaryRelease->partial_amount)
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Partial Amount Released</h3>
                    <p class="text-lg text-yellow-600 font-semibold">{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->partial_amount, 2) }}</p>
                </div>
                @endif

                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Created</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->created_at->format('M d, Y') }}</p>
                </div>

                @if($salaryRelease->notes)
                <div class="col-span-2">
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Notes</h3>
                    <p class="text-lg text-navy-900">{{ $salaryRelease->notes }}</p>
                </div>
                @endif
            </div>

            <!-- Commission Breakdown -->
            @php
                // Get payments whose commission was paid in THIS specific salary release
                // Using salary_release_id ensures we only show payments that were actually included in this release
                $paymentsForThisRelease = \App\Models\Payment::where('salary_release_id', $salaryRelease->id)
                    ->where('commission_paid', true)
                    ->whereHas('invoice', function($q) use ($salaryRelease) {
                        $q->where('employee_id', $salaryRelease->employee_id);
                    })
                    ->with('invoice.client')
                    ->get();
            @endphp

            @if($paymentsForThisRelease->count() > 0 && $salaryRelease->commission_amount > 0)
            <div class="mt-6 border-t border-gray-300 pt-6">
                <h3 class="text-lg font-bold text-navy-900 mb-4">Commission Breakdown</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="text-left py-2 text-sm font-semibold text-gray-700">Client</th>
                                <th class="text-left py-2 text-sm font-semibold text-gray-700">Payment Date</th>
                                <th class="text-right py-2 text-sm font-semibold text-gray-700">Amount Paid</th>
                                <th class="text-right py-2 text-sm font-semibold text-gray-700">Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentsForThisRelease as $payment)
                                @php
                                    $invoice = $payment->invoice;
                                    $taxPerPayment = ($invoice->tax / $invoice->amount) * $payment->amount;
                                    $netAmount = $payment->amount - $taxPerPayment;
                                    $commission = $netAmount * ($salaryRelease->employee->commission_rate / 100);
                                @endphp
                                <tr class="border-b border-gray-200">
                                    <td class="py-2 text-sm">{{ $invoice->client ? $invoice->client->name : 'N/A' }}</td>
                                    <td class="py-2 text-sm">{{ $payment->payment_date->format('M d, Y') }}</td>
                                    <td class="py-2 text-sm text-right">Rs.{{ number_format($payment->amount, 2) }}</td>
                                    <td class="py-2 text-sm text-right text-green-600 font-semibold">Rs.{{ number_format($commission, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Bonuses Breakdown -->
            @php
                $bonuses = $salaryRelease->employee->bonuses()
                    ->where('released', true)
                    ->where('release_type', 'with_salary')
                    ->where('date', '<=', $salaryRelease->release_date)
                    ->get();
            @endphp

            @if($bonuses->count() > 0 && $salaryRelease->bonus_amount > 0)
            <div class="mt-6 border-t border-gray-300 pt-6">
                <h3 class="text-lg font-bold text-navy-900 mb-4">Bonuses Included</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="text-left py-2 text-sm font-semibold text-gray-700">Date</th>
                                <th class="text-left py-2 text-sm font-semibold text-gray-700">Description</th>
                                <th class="text-right py-2 text-sm font-semibold text-gray-700">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bonuses as $bonus)
                                <tr class="border-b border-gray-200">
                                    <td class="py-2 text-sm">{{ $bonus->date->format('M d, Y') }}</td>
                                    <td class="py-2 text-sm">{{ $bonus->description ?? 'Bonus' }}</td>
                                    <td class="py-2 text-sm text-right text-purple-600 font-semibold">Rs.{{ number_format($bonus->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
