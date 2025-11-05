<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>
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
        .total-row { font-weight: bold; background-color: #f0f0f0; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('assets/logo.png') }}" class="logo" alt="Logo">
        <div class="title">SALARY SLIP</div>
        <div>Salary for {{ $salaryRelease->month ? date('F Y', strtotime($salaryRelease->month . '-01')) : 'N/A' }}</div>
        <div style="font-size: 14px; margin-top: 5px;">Released on {{ $salaryRelease->release_date->format('F d, Y') }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <div class="label">Employee Name:</div>
                <div>{{ $salaryRelease->employee->name }}</div>
            </td>
            <td width="50%">
                <div class="label">Employee Email:</div>
                <div>{{ $salaryRelease->employee->email }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Role:</div>
                <div>{{ $salaryRelease->employee->role }}</div>
            </td>
            <td>
                <div class="label">Salary Month:</div>
                <div>{{ $salaryRelease->month ? date('F Y', strtotime($salaryRelease->month . '-01')) : 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th>Description</th>
                <th width="30%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Base Salary</td>
                <td>{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->base_salary, 2) }}</td>
            </tr>
            <tr>
                <td>Commission (from previous month's paid invoices)</td>
                <td>{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->commission_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Bonus</td>
                <td>{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->bonus_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Deductions</td>
                <td class="text-red">-{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->deductions, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Amount</strong></td>
                <td><strong>{{ $salaryRelease->currency ? $salaryRelease->currency->symbol : 'Rs.' }}{{ number_format($salaryRelease->total_amount, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if($salaryRelease->notes)
    <div style="margin-top: 20px;">
        <div class="label">Notes:</div>
        <div>{{ $salaryRelease->notes }}</div>
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated salary slip and does not require a signature.</p>
        <p>Generated on {{ now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
