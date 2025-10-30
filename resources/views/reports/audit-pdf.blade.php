<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audit Report</title>
    <style>
        body { font-family: Arial, sans-serif; color: #000; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #001F3F; padding-bottom: 15px; }
        .logo { max-height: 60px; margin-bottom: 10px; }
        .title { color: #001F3F; font-size: 22px; font-weight: bold; }
        .section-title { color: #001F3F; font-size: 15px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #001F3F; padding-bottom: 5px; }
        .subsection-title { color: #001F3F; font-size: 13px; font-weight: bold; margin-top: 15px; margin-bottom: 8px; }
        .summary-box { background-color: #f0f0f0; padding: 10px; margin-bottom: 15px; border: 1px solid #001F3F; }
        .summary-item { margin: 5px 0; font-size: 12px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 11px; }
        .data-table th, .data-table td { border: 1px solid #001F3F; padding: 6px 8px; text-align: left; }
        .data-table th { background-color: #001F3F; color: white; font-size: 11px; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; page-break-inside: avoid; }
        .paid { color: #006400; }
        .unpaid { color: #8B0000; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('assets/logo.png') }}" class="logo" alt="Logo">
        <div class="title">AUDIT REPORT</div>
        <div>{{ date('F d, Y', strtotime($date_from)) }} to {{ date('F d, Y', strtotime($date_to)) }}</div>
        <div>Generated for: {{ $user->name }}</div>
    </div>

    <div class="summary-box">
        <div class="section-title">Executive Summary</div>
        <div class="summary">
            <div class="summary-item"><strong>Payments Received in Period:</strong> Rs.{{ number_format($total_payments_in_range, 2) }}</div>
            <div class="summary-item"><strong>From Invoices:</strong> {{ $invoices->count() }} invoice(s)</div>
            <div class="summary-item"><strong>Total Expenses:</strong> Rs.{{ number_format($total_expenses, 2) }}</div>
            <div class="summary-item"><strong>Total Salaries:</strong> Rs.{{ number_format($total_salaries, 2) }}</div>
            <div class="summary-item"><strong>Total Bonuses:</strong> Rs.{{ number_format($total_bonuses, 2) }}</div>
            <div class="summary-item net-income"><strong>Net Income:</strong> Rs.{{ number_format($net_income, 2) }}</div>
            <div class="summary-item" style="font-size: 9px; font-style: italic; color: #666;">Note: Net income is based on actual payments received in this period ({{ date('M d, Y', strtotime($date_from)) }} to {{ date('M d, Y', strtotime($date_to)) }}), not invoice creation dates.</div>
        </div>
    </div>

    <div class="section-title">Invoices ({{ $invoices->count() }})</div>
    
    @if($paid_invoices->count() > 0)
        <div class="subsection-title paid">✓ Paid Invoices ({{ $paid_invoices->count() }})</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice Date</th>
                    <th>Client</th>
                    <th>Salesperson</th>
                    <th>Invoice Total</th>
                    <th>Paid in Period</th>
                    <th>Payment Dates</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paid_invoices as $invoice)
                    @php
                        $paymentsInPeriod = $invoice->payments;
                        $totalPaidInPeriod = $paymentsInPeriod->sum('amount');
                        $paymentDates = $paymentsInPeriod->pluck('payment_date')->map(function($date) {
                            return $date->format('M d');
                        })->join(', ');
                        $clientName = $invoice->is_one_time ? $invoice->one_time_client_name : $invoice->client->name;
                    @endphp
                    <tr>
                        <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                        <td>{{ $clientName }}</td>
                        <td>{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</td>
                        <td>Rs.{{ number_format($invoice->amount, 2) }}</td>
                        <td>Rs.{{ number_format($totalPaidInPeriod, 2) }}</td>
                        <td>{{ $paymentDates }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4"><strong>Total Payments Received</strong></td>
                    <td><strong>Rs.{{ number_format($paid_invoices->sum(function($inv) { return $inv->payments->sum('amount'); }), 2) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <p>No paid invoices in this period.</p>
    @endif

    @if($partial_paid_invoices->count() > 0)
        <div class="subsection-title unpaid">◐ Partial Paid Invoices ({{ $partial_paid_invoices->count() }})</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice Date</th>
                    <th>Client</th>
                    <th>Salesperson</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Latest Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($partial_paid_invoices as $invoice)
                    @php
                        $paymentsInPeriod = $invoice->payments;
                        $totalPaidInPeriod = $paymentsInPeriod->sum('amount');
                        $paymentDates = $paymentsInPeriod->pluck('payment_date')->map(function($date) {
                            return $date->format('M d');
                        })->join(', ');
                        $clientName = $invoice->is_one_time ? $invoice->one_time_client_name : $invoice->client->name;
                    @endphp
                    <tr>
                        <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                        <td>{{ $clientName }}</td>
                        <td>{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</td>
                        <td>Rs.{{ number_format($invoice->amount, 2) }}</td>
                        <td>Rs.{{ number_format($totalPaidInPeriod, 2) }}</td>
                        <td>Rs.{{ number_format($invoice->remaining_amount, 2) }}</td>
                        <td>{{ $paymentDates }}</td>
                        <td>{{ $invoice->status }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4"><strong>Partial Paid Subtotal</strong></td>
                    <td><strong>Rs.{{ number_format($partial_paid_invoices->sum(function($inv) { return $inv->payments->sum('amount'); }), 2) }}</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    @else
        <p>No unpaid invoices in this period.</p>
    @endif

    @if($invoices->count() > 0)
        <table class="data-table">
            <tr class="total-row">
                <td colspan="5"><strong>Total Invoices (All)</strong></td>
                <td><strong>Rs.{{ number_format($total_invoices, 2) }}</strong></td>
            </tr>
        </table>
    @endif

    <div class="section-title">Expenses ({{ $expenses->count() }})</div>
    @if($expenses->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $expense)
                    <tr>
                        <td>{{ $expense->date->format('M d, Y') }}</td>
                        <td>{{ $expense->description }}</td>
                        <td>Rs.{{ number_format($expense->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>Total</strong></td>
                    <td><strong>Rs.{{ number_format($expenses->sum('amount'), 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <p>No expenses in this period.</p>
    @endif

    <div class="section-title">Salary Releases ({{ $salaryReleases->count() }})</div>
    @if($salaryReleases->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Month</th>
                    <th>Base</th>
                    <th>Commission</th>
                    <th>Bonus</th>
                    <th>Deductions</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salaryReleases as $release)
                    <tr>
                        <td>{{ $release->release_date->format('M d, Y') }}</td>
                        <td>{{ $release->employee->name }}</td>
                        <td>{{ $release->month ? date('M Y', strtotime($release->month . '-01')) : 'N/A' }}</td>
                        <td>Rs.{{ number_format($release->base_salary, 2) }}</td>
                        <td>Rs.{{ number_format($release->commission_amount, 2) }}</td>
                        <td>Rs.{{ number_format($release->bonus_amount ?? 0, 2) }}</td>
                        <td>Rs.{{ number_format($release->deductions, 2) }}</td>
                        <td>Rs.{{ number_format($release->total_amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong>Rs.{{ number_format($salaryReleases->sum('base_salary'), 2) }}</strong></td>
                    <td><strong>Rs.{{ number_format($salaryReleases->sum('commission_amount'), 2) }}</strong></td>
                    <td><strong>Rs.{{ number_format($salaryReleases->sum('bonus_amount'), 2) }}</strong></td>
                    <td><strong>Rs.{{ number_format($salaryReleases->sum('deductions'), 2) }}</strong></td>
                    <td><strong>Rs.{{ number_format($salaryReleases->sum('total_amount'), 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
        <p style="font-size: 9px; font-style: italic; margin-top: 5px;">Note: Commissions are from paid invoices only. Bonuses are included in the total amount.</p>
    @else
        <p>No salary releases in this period.</p>
    @endif

    <div class="footer">
        <p>This is a computer-generated report.</p>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>
