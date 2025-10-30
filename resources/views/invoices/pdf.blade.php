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
                <div class="label">From:</div>
                <div>{{ $invoice->user->name }}</div>
                <div>{{ $invoice->user->email }}</div>
            </td>
            <td width="50%">
                <div class="label">To:</div>
                @if($invoice->is_one_time)
                    <div>{{ $invoice->one_time_client_name }}</div>
                    <div style="font-size: 12px; color: #666;">(One-Time Project)</div>
                @else
                    <div>{{ $invoice->client->name }}</div>
                    <div>{{ $invoice->client->email }}</div>
                    <div>{{ $invoice->client->primary_contact }}</div>
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
                <td>Rs.{{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td>Rs.{{ number_format($invoice->tax, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Net Amount</strong></td>
                <td><strong>Rs.{{ number_format($invoice->amount - $invoice->tax, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

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
