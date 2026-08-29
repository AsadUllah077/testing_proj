<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payroll Summary Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            border-bottom: 3px solid #0078C8;
            padding-bottom: 16px;
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
        }

        .header-right {
            display: table-cell;
            text-align: right;
            vertical-align: top;
        }

        h1 {
            color: #0078C8;
            margin: 0 0 4px;
            font-size: 20px;
        }

        .meta {
            font-size: 10px;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #f1f5f9;
            color: #374151;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }

        td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }

        tfoot td {
            background: #f8fafc;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0078C8;
            margin: 16px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .summary-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #0078C8;
            margin-top: 4px;
        }

        .badge-earn {
            color: #16a34a;
            background: #dcfce7;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 9px;
        }

        .badge-ded {
            color: #dc2626;
            background: #fee2e2;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 9px;
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <h1>Payroll Summary Report</h1>
            <p class="meta">Period: {{ $currentRun->period_start->format('M d, Y') }} –
                {{ $currentRun->period_end->format('M d, Y') }}</p>
            <p class="meta">Payment Date: {{ $currentRun->payment_date->format('M d, Y') }} &bull; Status:
                {{ ucfirst($currentRun->status) }}</p>
        </div>
        <div class="header-right">
            <p class="meta">Generated: {{ now()->format('M d, Y H:i') }}</p>
            <p class="meta">Ref: {{ $currentRun->unique_no ?? 'DRAFT' }}</p>
        </div>
    </div>

    @php
        $totalGross = $currentRun->payslips->sum('gross_earnings');
        $totalDed = $currentRun->payslips->sum('total_deductions');
        $totalNet = $currentRun->payslips->sum('net_pay');
        $sym = currency()?->symbol ?? '';
    @endphp

    <div class="summary-row">
        <div class="summary-cell">
            <div class="summary-label">Employees</div>
            <div class="summary-value">{{ $currentRun->payslips->count() }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Total Gross</div>
            <div class="summary-value">{{ $sym }} {{ numberFormat($totalGross) }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Deductions</div>
            <div class="summary-value" style="color:#dc2626">{{ $sym }} {{ numberFormat($totalDed) }}
            </div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Net Pay</div>
            <div class="summary-value">{{ $sym }} {{ numberFormat($totalNet) }}</div>
        </div>
    </div>

    <div class="section-title">Department Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th>Employees</th>
                <th class="text-right">Gross</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Net Pay</th>
                <th class="text-right">% Share</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deptBreakdown as $dept => $data)
                <tr>
                    <td>{{ $dept }}</td>
                    <td>{{ $data['count'] }}</td>
                    <td class="text-right">{{ $sym }} {{ numberFormat($data['gross']) }}</td>
                    <td class="text-right">({{ $sym }} {{ numberFormat($data['deductions']) }})</td>
                    <td class="text-right"><strong>{{ $sym }} {{ numberFormat($data['net']) }}</strong>
                    </td>
                    <td class="text-right">{{ $totalGross > 0 ? round(($data['gross'] / $totalGross) * 100, 1) : 0 }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>TOTAL</strong></td>
                <td class="text-right">{{ $sym }} {{ numberFormat($totalGross) }}</td>
                <td class="text-right">({{ $sym }} {{ numberFormat($totalDed) }})</td>
                <td class="text-right"><strong>{{ $sym }} {{ numberFormat($totalNet) }}</strong></td>
                <td class="text-right">100%</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">Employee Detail</div>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th class="text-right">Gross</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($currentRun->payslips as $slip)
                <tr>
                    <td>{{ $slip->employee->full_name }}</td>
                    <td>{{ $slip->employee->department->department ?? '—' }}</td>
                    <td class="text-right">{{ $sym }} {{ numberFormat($slip->gross_earnings) }}</td>
                    <td class="text-right">({{ $sym }} {{ numberFormat($slip->total_deductions) }})</td>
                    <td class="text-right"><strong>{{ $sym }} {{ numberFormat($slip->net_pay) }}</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">This report was generated by the ERP system on {{ now()->format('M d, Y') }}.</div>
</body>

</html>
