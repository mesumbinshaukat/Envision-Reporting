<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip - {{ $employee->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #001F3F;
            padding-bottom: 20px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo {
            max-height: 80px;
            max-width: 200px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #001F3F;
            margin: 10px 0;
        }
        .title {
            color: #001F3F;
            font-size: 22px;
            font-weight: bold;
            margin-top: 10px;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #001F3F;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
        }
        .info-table td:first-child {
            width: 35%;
            font-weight: bold;
            color: #001F3F;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .details-table th, .details-table td {
            border: 1px solid #001F3F;
            padding: 10px;
            text-align: left;
        }
        .details-table th {
            background-color: #001F3F;
            color: white;
            font-weight: bold;
        }
        .details-table td.amount {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .total-row td {
            border-top: 2px solid #001F3F;
        }
        .deduction {
            color: #dc2626;
        }
        .earning {
            color: #16a34a;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .notes {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9fafb;
            border-left: 4px solid #001F3F;
        }
        .notes-title {
            font-weight: bold;
            color: #001F3F;
            margin-bottom: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-released {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .commission-item, .bonus-item {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <img src="{{ public_path('assets/logo.png') }}" class="logo" alt="SierraLP Logo">
        </div>
        <div class="company-name">{{ $companyName }}</div>
        <div class="title">SALARY SLIP</div>
        <div class="subtitle">Salary for {{ $salaryData['month_formatted'] }}</div>
        @if($salaryData['has_salary_release'] && $salaryData['release_date'])
            <div class="subtitle">Released on {{ $salaryData['release_date']->format('F d, Y') }}</div>
            <span class="status-badge status-released">Released</span>
        @else
            <span class="status-badge status-pending">Provisional</span>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Employee Information</div>
        <table class="info-table">
            <tr>
                <td>Employee Name:</td>
                <td>{{ $employee->name }}</td>
            </tr>
            <tr>
                <td>Email:</td>
                <td>{{ $employee->email }}</td>
            </tr>
            <tr>
                <td>Role:</td>
                <td>{{ $employee->role }}</td>
            </tr>
            <tr>
                <td>Employment Type:</td>
                <td>{{ $employee->employment_type }}</td>
            </tr>
            <tr>
                <td>Commission Rate:</td>
                <td>{{ $employee->commission_rate }}%</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Salary Breakdown</div>
        <table class="details-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th width="35%" style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Base Salary</strong>
                    </td>
                    <td class="amount earning">{{ $salaryData['base_salary_formatted'] }}</td>
                </tr>

                <tr>
                    <td>
                        <strong>Commission</strong>
                        @if(count($salaryData['commission_details']) > 0)
                            @foreach($salaryData['commission_details'] as $detail)
                                <div class="commission-item">
                                    • {{ $detail['client'] }}: {{ $detail['paid_amount_formatted'] }} paid
                                </div>
                            @endforeach
                        @else
                            <div class="commission-item">No commissionable invoices this month</div>
                        @endif
                    </td>
                    <td class="amount earning">{{ $salaryData['commission_amount_formatted'] }}</td>
                </tr>

                <tr>
                    <td>
                        <strong>Bonus</strong>
                        @if(count($salaryData['bonus_details']) > 0)
                            @foreach($salaryData['bonus_details'] as $detail)
                                <div class="bonus-item">
                                    • {{ $detail['description'] }} ({{ $detail['date'] }}): {{ $detail['amount_formatted'] }}
                                </div>
                            @endforeach
                        @else
                            <div class="bonus-item">No bonuses this month</div>
                        @endif
                    </td>
                    <td class="amount earning">{{ $salaryData['bonus_amount_formatted'] }}</td>
                </tr>

                <tr>
                    <td>
                        <strong>Deductions</strong>
                    </td>
                    <td class="amount deduction">-{{ $salaryData['deductions_formatted'] }}</td>
                </tr>

                <tr class="total-row">
                    <td><strong>TOTAL PAID</strong></td>
                    <td class="amount"><strong>{{ $salaryData['total_amount_formatted'] }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($salaryData['notes'])
    <div class="notes">
        <div class="notes-title">Notes:</div>
        <div>{{ $salaryData['notes'] }}</div>
    </div>
    @endif

    <div class="footer">
        <p><strong>This is a computer-generated salary slip and does not require a signature.</strong></p>
        @if($salaryData['has_salary_release'])
            <p>Salary released on {{ $salaryData['release_date']->format('F d, Y') }}</p>
        @else
            <p>This is a provisional calculation. Final amounts may vary upon actual salary release.</p>
        @endif
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <p style="margin-top: 10px; font-size: 10px;">SierraLP - Attendance & Salary Management System</p>
    </div>
</body>
</html>
