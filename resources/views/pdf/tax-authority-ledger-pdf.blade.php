<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Authority Ledger Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 3px solid #0078C8;
        }

        .header h1 {
            color: #0078C8;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .header p {
            color: #666;
            margin: 5px 0;
        }

        .summary-section {
            margin-bottom: 30px;
        }

        .summary-section h2 {
            color: #0078C8;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .summary-table td {
            padding: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
            font-weight: bold;
        }

        .summary-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 16px;
            color: #1e293b;
        }

        .tax-periods-section h2 {
            color: #0078C8;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #0078C8;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-open {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-settled {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-paid {
            background-color: #bbf7d0;
            color: #166534;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Tax Authority Ledger</h1>
        <p>Comprehensive overview of all tax transactions and balances.</p>
        <p>Generated on: {{ now()->format('F d, Y - h:i A') }}</p>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <h2>Summary Overview</h2>
        <table class="summary-table">
            <tr>
                <td>
                    <div class="summary-label">Total Sales Tax</div>
                    <div class="summary-value" style="color: #dc2626;">{{ number_format($data['totalSalesTax'], 2) }}</div>
                </td>
                <td>
                    <div class="summary-label">Total Purchase Tax</div>
                    <div class="summary-value" style="color: #16a34a;">{{ number_format($data['totalPurchaseTax'], 2) }}</div>
                </td>
                <td>
                    <div class="summary-label">Tax Settlements</div>
                    <div class="summary-value" style="color: #2563eb;">{{ number_format($data['totalSettlements'], 2) }}</div>
                </td>
                <td>
                    <div class="summary-label">Tax Payments</div>
                    <div class="summary-value" style="color: #7c3aed;">{{ number_format($data['totalPayments'], 2) }}</div>
                </td>
                <td>
                    <div class="summary-label">Remaining Balance</div>
                    <div class="summary-value" style="color: {{ $data['remainingBalance'] >= 0 ? '#dc2626' : '#16a34a' }};">
                        {{ number_format(abs($data['remainingBalance']), 2) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tax Periods Table -->
    <h2>Tax Period History</h2>
    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Period</th>
                <th>Type</th>
                <th>Date Range</th>
                <th>Status</th>
                <th class="text-right">Sales Tax</th>
                <th class="text-right">Purchase Tax</th>
                <th class="text-right">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['taxPeriods'] as $index => $period)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $period->name }}</td>
                    <td>{{ ucfirst($period->type) }}</td>
                    <td>{{ $period->start_date->format('M d, Y') }} - {{ $period->end_date->format('M d, Y') }}</td>
                    <td>
                        @if ($period->status === 'open')
                            <span class="status-badge status-open">Open</span>
                        @elseif($period->status === 'settled')
                            @if ($period->paid_amount > 0 && $period->paid_amount < abs($period->net_amount))
                                <span class="status-badge" style="background-color: #fed7aa; color: #9a3412;">Settled (Partial)</span>
                            @else
                                <span class="status-badge status-settled">Settled</span>
                            @endif
                        @elseif($period->status === 'paid')
                            <span class="status-badge status-paid">Paid</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if ($period->status !== 'open')
                            {{ number_format($period->total_payable, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if ($period->status !== 'open')
                            {{ number_format($period->total_receivable, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if ($period->status !== 'open')
                            <span style="color: {{ $period->net_amount > 0 ? 'red' : 'green' }}">
                                {{ number_format(abs($period->net_amount), 2) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #999;">
                        No tax periods found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>

</html>
