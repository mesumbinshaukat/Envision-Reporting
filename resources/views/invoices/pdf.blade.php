<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #000; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #001F3F; padding-bottom: 20px; }
        .logo { max-height: 80px; margin-bottom: 10px; }
        .title { color: #001F3F; font-size: 24px; font-weight: bold; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 8px; }
        .label { font-weight: bold; color: #001F3F; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { border: 1px solid #001F3F; padding: 10px; text-align: left; }
        .details-table th { background-color: #001F3F; color: white; }
        .total { font-size: 18px; font-weight: bold; color: #001F3F; text-align: right; margin-top: 20px; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('assets/logo.png') }}" class="logo" alt="Logo">
        <div class="title">INVOICE</div>
        <div>Invoice #{{ $invoice->id }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <div class="label">Client's Name:</div>
                @if($invoice->is_one_time)
                    <div>{{ $invoice->one_time_client_name }}</div>
                    <div style="font-size: 12px; color: #666;">(One-Time Project)</div>
                @else
                    <div>{{ $invoice->client->name }}</div>
                @endif
            </td>
            <td width="50%">
                @if(!$invoice->is_one_time && $invoice->client)
                    <div class="label">Contact Information:</div>
                    <div>{{ $invoice->client->email ?? 'N/A' }}</div>
                    <div>{{ $invoice->client->primary_contact ?? 'N/A' }}</div>
                    @if($invoice->client->secondary_contact)
                        <div>{{ $invoice->client->secondary_contact }}</div>
                    @endif
                    @if($invoice->client->address)
                        <div style="margin-top: 5px;">{{ $invoice->client->address }}</div>
                    @endif
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Invoice Date:</div>
                <div>{{ $invoice->created_at->format('F d, Y') }}</div>
            </td>
            <td>
                <div class="label">Due Date:</div>
                <div>{{ $invoice->due_date ? $invoice->due_date->format('F d, Y') : 'N/A' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Status:</div>
                <div>{{ $invoice->status }}</div>
            </td>
            <td>
                <div class="label">Salesperson:</div>
                <div>{{ $invoice->employee ? $invoice->employee->name : 'Self' }}</div>
            </td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th>Description</th>
                <th width="20%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Invoice Amount</td>
                <td>{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td>{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->tax, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Amount</strong></td>
                <td><strong>{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->amount, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Payment Transaction Records -->
    <div style="margin-top: 30px;">
        <div class="label" style="font-size: 16px; margin-bottom: 10px; border-bottom: 2px solid #001F3F; padding-bottom: 5px;">Payment Transaction Records</div>
        
        @if($invoice->payments->count() > 0)
            <table class="details-table" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th width="25%">Payment Date</th>
                        <th width="20%">Amount Paid</th>
                        <th width="20%">Payment Month</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $index => $payment)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') : 'N/A' }}</td>
                        <td>{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->payment_month ? \Carbon\Carbon::parse($payment->payment_month . '-01')->format('M Y') : 'N/A' }}</td>
                        <td>{{ $payment->notes ?? ($payment->payment_method ?? 'Payment received') }}</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #f0f0f0;">
                        <td colspan="2" style="text-align: right;"><strong>Total Paid:</strong></td>
                        <td><strong>{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->paid_amount, 2) }}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        @else
            <div style="padding: 15px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin-top: 10px;">
                <strong>No payments received yet</strong>
            </div>
        @endif

        <!-- Payment Summary -->
        <table style="width: 100%; margin-top: 20px; border: 2px solid #001F3F;">
            <tr>
                <td style="padding: 10px; width: 70%; text-align: right;"><strong>Total Invoice Amount:</strong></td>
                <td style="padding: 10px; background-color: #f8f9fa;"><strong>{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; text-align: right;"><strong>Total Paid:</strong></td>
                <td style="padding: 10px; background-color: #d4edda; color: #155724;"><strong>{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->paid_amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; text-align: right;"><strong>Remaining Amount:</strong></td>
                <td style="padding: 10px; background-color: {{ $invoice->remaining_amount > 0 ? '#f8d7da' : '#d4edda' }}; color: {{ $invoice->remaining_amount > 0 ? '#721c24' : '#155724' }};"><strong>{{ $invoice->currency ? $invoice->currency->symbol : 'Rs.' }}{{ number_format($invoice->remaining_amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; text-align: right;"><strong>Payment Status:</strong></td>
                <td style="padding: 10px; background-color: {{ $invoice->status == 'Payment Done' ? '#d4edda' : ($invoice->status == 'Partial Paid' ? '#fff3cd' : '#f8d7da') }}; color: {{ $invoice->status == 'Payment Done' ? '#155724' : ($invoice->status == 'Partial Paid' ? '#856404' : '#721c24') }};"><strong>{{ $invoice->remaining_amount <= 0 ? 'CLEARED ✓' : ($invoice->status == 'Partial Paid' ? 'PARTIALLY PAID' : 'PENDING') }}</strong></td>
            </tr>
        </table>
    </div>

    @if($invoice->special_note)
    <div style="margin-top: 20px;">
        <div class="label">Special Note:</div>
        <div>{{ $invoice->special_note }}</div>
    </div>
    @endif

    @if($invoice->employee)
    <div style="margin-top: 20px;">
        <div class="label">Commission Details:</div>
        <div>Salesperson: {{ $invoice->employee->name }}</div>
        <div>Commission Rate: {{ $invoice->employee->commission_rate }}%</div>
        <div>Commission Amount: Rs.{{ number_format($invoice->calculateCommission(), 2) }}</div>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Generated on {{ now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
