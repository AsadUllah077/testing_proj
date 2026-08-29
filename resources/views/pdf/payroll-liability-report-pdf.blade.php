<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payroll Liabilities Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0078C8;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #0078C8;
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .summary {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            padding: 15px;
            margin-bottom: 20px;
        }

        .summary h3 {
            color: #1e293b;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px;
            border: 1px solid #e2e8f0;
        }

        .summary-label {
            background-color: #f8fafc;
            font-weight: bold;
            width: 30%;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }

        .summary-value {
            width: 70%;
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px 16px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8fafc;
            color: #374151;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .text-right {
            text-align: right;
        }

        .liability-group {
            margin-bottom: 30px;
        }

        .liability-group h3 {
            color: #0078C8;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Payroll Liabilities Report</h1>
        <p><strong>Export Date:</strong> {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Summary Section -->
    <div class="summary">
        <h3>Report Summary</h3>
        <table class="summary-table">
            <tr>
                <td class="summary-label">Total Liabilities</td>
                <td class="summary-value">{{ number_format($liabilities->sum('amount'), 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Number of Items</td>
                <td class="summary-value">{{ $liabilities->count() }}</td>
            </tr>
            <tr>
                <td class="summary-label">Pay Items</td>
                <td class="summary-value">{{ $groupedLiabilities->count() }}</td>
            </tr>
        </table>
    </div>

    <!-- Liabilities by Pay Item -->
    @foreach ($groupedLiabilities as $payItemId => $liabilities)
        <div class="liability-group">
            <h3>{{ $liabilities->first()->payItem->name }} - {{ number_format($liabilities->sum('amount'), 2) }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Outstanding</th>
                        <th>Due</th>
                        <th>Pay Run</th>
                        <th>Pay Period</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($liabilities as $liability)
                        <tr>
                            <td>{{ $liability->payslip->employee->full_name ?? '—' }}</td>
                            <td class="text-right">{{ number_format($liability->amount, 2) }}</td>
                            <td class="text-right">{{ number_format($liability->paid_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($liability->outstanding_amount, 2) }}</td>
                            <td>{{ $liability->statutory_due_date?->format('M d, Y') ?? '—' }}</td>
                            <td>{{ $liability->payslip->payRun->notes ?? '—' }}</td>
                            <td>{{ $liability->payslip->payRun->period_start ? $liability->payslip->payRun->period_start->format('M d, Y') : '—' }}
                                -
                                {{ $liability->payslip->payRun->period_end ? $liability->payslip->payRun->period_end->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($liability->is_paid)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Paid
                                    </span>
                                @elseif((float) $liability->paid_amount > 0)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-clock mr-1"></i>
                                            Partial
                                    </span>
                                @elseif($liability->payslip->payRun->status === 'draft')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>
                                        Draft
                                    </span>
                                @elseif($liability->payslip->payRun->status === 'cancelled')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Cancelled
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>
                                        Unpaid
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <p>This report was generated by the ERP system. For any questions, please contact the administrator.</p>
    </div>
</body>

</html>
