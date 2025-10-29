<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audit Report</title>
    <style>
        body { font-family: Arial, sans-serif; color: #000; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #001F3F; padding-bottom: 15px; }
        .logo { max-height: 60px; margin-bottom: 10px; }
        .title { color: #001F3F; font-size: 20px; font-weight: bold; }
        .section-title { color: #001F3F; font-size: 14px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #001F3F; padding-bottom: 5px; }
        .subsection-title { color: #001F3F; font-size: 12px; font-weight: bold; margin-top: 15px; margin-bottom: 8px; }
        .summary-box { background-color: #f0f0f0; padding: 10px; margin-bottom: 15px; border: 1px solid #001F3F; }
        .summary-item { margin: 5px 0; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px; }
        .data-table th, .data-table td { border: 1px solid #001F3F; padding: 5px; text-align: left; }
        .data-table th { background-color: #001F3F; color: white; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #666; page-break-inside: avoid; }
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
        <div class="summary-item"><strong>Total Invoices:</strong> Rs.{{ number_format($total_invoices, 2) }}</div>
        <div class="summary-item" style="margin-left: 15px;"><span class="paid">• Paid Invoices:</span> Rs.{{ number_format($total_paid_invoices, 2) }}</div>
        <div class="summary-item" style="margin-left: 15px;"><span class="unpaid">• Unpaid Invoices:</span> Rs.{{ number_format($total_unpaid_invoices, 2) }}</div>
        <div class="summary-item"><strong>Total Expenses:</strong> Rs.{{ number_format($total_expenses, 2) }}</div>
        <div class="summary-item"><strong>Total Salaries Released:</strong> Rs.{{ number_format($total_salaries, 2) }}</div>
        <div class="summary-item"><strong>Total Bonuses:</strong> Rs.{{ number_format($total_bonuses, 2) }} <em>(Separate from net income)</em></div>
        <div class="summary-item" style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #001F3F;"><strong>Net Income (Invoices - Expenses - Salaries):</strong> Rs.{{ number_format($net_income, 2) }}</div>
        <div class="summary-item" style="font-size: 9px; font-style: italic; color: #666;">Note: Bonuses are excluded from net income calculation as they are separate rewards.</div>
    </div>

    <div class="section-title">Invoices ({{ $invoices->count() }})</div>
    
    @if($paid_invoices->count() > 0)
        <div class="subsection-title paid">✓ Paid Invoices ({{ $paid_invoices->count() }})</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Salesperson</th>
                    <th>Amount</th>
                    <th>Tax</th>
                    <th>Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paid_invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                        <td>{{ $invoice->client->name }}</td>
                        <td>{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</td>
                        <td>Rs.{{ number_format($invoice->amount, 2) }}</td>
                        <td>Rs.{{ number_format($invoice->tax, 2) }}</td>
                        <td>Rs.{{ number_format($invoice->amount - $invoice->tax, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3"><strong>Paid Subtotal</strong></td>
                    <td><strong>Rs.{{ number_format($paid_invoices->sum('amount'), 2) }}</strong></td>
                    <td><strong>Rs.{{ number_format($paid_invoices->sum('tax'), 2) }}</strong></td>
                    <td><strong>Rs.{{ number_format($paid_invoices->sum('amount') - $paid_invoices->sum('tax'), 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <p>No paid invoices in this period.</p>
    @endif

    @if($unpaid_invoices->count() > 0)
        <div class="subsection-title unpaid">✗ Unpaid Invoices ({{ $unpaid_invoices->count() }})</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Salesperson</th>
                    <th>Amount</th>
                    <th>Tax</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unpaid_invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                        <td>{{ $invoice->client->name }}</td>
                        <td>{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</td>
                        <td>Rs.{{ number_format($invoice->amount, 2) }}</td>
                        <td>Rs.{{ number_format($invoice->tax, 2) }}</td>
                        <td>{{ $invoice->status }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3"><strong>Unpaid Subtotal</strong></td>
                    <td><strong>Rs.{{ number_format($unpaid_invoices->sum('amount'), 2) }}</strong></td>
                    <td><strong>Rs.{{ number_format($unpaid_invoices->sum('tax'), 2) }}</strong></td>
                    <td></td>
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
                        <td>Rs.{{ number_format($release->deductions, 2) }}</td>
                        <td>Rs.{{ number_format($release->total_amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6"><strong>Total</strong></td>
                    <td><strong>Rs.{{ number_format($salaryReleases->sum('total_amount'), 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
        <p style="font-size: 9px; font-style: italic; margin-top: 5px;">Note: Commissions are from paid invoices only. Bonuses are tracked separately.</p>
    @else
        <p>No salary releases in this period.</p>
    @endif

    <div class="section-title">Bonuses ({{ $bonuses->count() }})</div>
    @if($bonuses->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bonuses as $bonus)
                    <tr>
                        <td>{{ $bonus->date->format('M d, Y') }}</td>
                        <td>{{ $bonus->employee->name }}</td>
                        <td>{{ $bonus->description ?? 'Bonus' }}</td>
                        <td>Rs.{{ number_format($bonus->amount, 2) }}</td>
                        <td>{{ $bonus->released ? 'Released' : 'Pending' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong>Rs.{{ number_format($bonuses->sum('amount'), 2) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <p style="font-size: 9px; font-style: italic; margin-top: 5px;">Note: Bonuses are separate rewards and not included in net income calculation.</p>
    @else
        <p>No bonuses in this period.</p>
    @endif

    <div class="footer">
        <p>This is a computer-generated report.</p>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>
