@php
    $isAllTransactions = blank($payRun ?? null);
    $summary = $summary ?? ['employee_count' => 0, 'gross' => 0, 'deductions' => 0, 'net' => 0, 'pay_run_count' => 0];

    if ($isAllTransactions) {
        $payRun = (object) [
            'unique_no' => 'All',
            'period_start' => now(),
            'period_end' => now(),
            'payment_date' => now(),
            'status' => 'all',
            'approvedBy' => null,
            'payslips' => collect(),
        ];
    }
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Pay Run Transactions – {{ $payRun->unique_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @font-face {
            font-family: 'NotoSans';
            font-style: normal;
            font-weight: 400;
            src: url("{{ storage_path('fonts/NotoSans-Regular.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: 'NotoSans';
            font-style: normal;
            font-weight: 700;
            src: url("{{ storage_path('fonts/NotoSans-Bold.ttf') }}") format("truetype");
        }

        body {
            font-family: 'NotoSans';
            font-size: 10px;
            color: #333;
        }

        .header {
            padding: 20px 30px;
            border-bottom: 3px solid #0078C8;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 300;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 9px;
            color: #666;
        }

        .summary {
            padding: 0 30px;
            margin-bottom: 15px;
        }

        .summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            padding: 8px 12px;
            text-align: center;
        }

        .summary .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            font-weight: bold;
        }

        .summary .value {
            font-size: 14px;
            font-weight: 300;
            color: #1a1a1a;
        }

        .summary .value.net {
            color: #0078C8;
            font-weight: 600;
        }

        .content {
            padding: 0 30px;
        }

        table.transactions {
            width: 100%;
            border-collapse: collapse;
        }

        table.transactions thead th {
            background: #f8f9fa;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            font-weight: bold;
            padding: 8px 6px;
            border-bottom: 2px solid #e5e7eb;
            text-align: left;
        }

        table.transactions thead th.right {
            text-align: right;
        }

        table.transactions tbody td {
            padding: 6px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 9px;
        }

        table.transactions tbody td.right {
            text-align: right;
        }

        table.transactions tbody td.muted {
            color: #999;
        }

        table.transactions tbody td .acct-num {
            font-size: 7px;
            color: #aaa;
            font-family: monospace;
        }

        tr.subtotal td {
            background: #ebf5ff;
            font-weight: bold;
            color: #0078C8;
            font-size: 9px;
            border-top: 1px solid #c5ddf5;
            padding: 5px 6px;
        }

        tr.grand-total td {
            background: #f8f9fa;
            font-weight: bold;
            color: #1a1a1a;
            font-size: 10px;
            border-top: 3px double #ccc;
            padding: 8px 6px;
        }

        .footer {
            margin-top: 20px;
            padding: 10px 30px;
            font-size: 8px;
            color: #aaa;
            text-align: center;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Pay Run Transactions: {{ $payRun->unique_no }}</h1>
        <p>
            Period: {{ $payRun->period_start->format('M d') }} – {{ $payRun->period_end->format('M d, Y') }}
            &nbsp;&bull;&nbsp; Payment Date: {{ $payRun->payment_date->format('M d, Y') }}
            &nbsp;&bull;&nbsp; Status: {{ strtoupper($payRun->status) }}
        </p>
        @if ($payRun->approvedBy)
            <p>Approved by
                {{ $payRun->approvedBy->name ?? $payRun->approvedBy->first_name . ' ' . $payRun->approvedBy->last_name }}
                on {{ $payRun->approved_at->format('M d, Y H:i') }}</p>
        @endif
    </div>

    <div class="summary">
        <table>
            <tr>
                <td>
                    <div class="label">Total Gross Earnings</div>
                    <div>{{ $currencySymbol }}
                        {{ number_format($summary['gross'], 2) }}</div>
                </td>
                <td>
                    <div class="label">Total Deductions & Tax</div>
                    <div>({{ $currencySymbol }}
                        {{ number_format($summary['deductions'], 2) }})</div>
                </td>
                <td>
                    <div class="label">Net Pay to Employees</div>
                    <div class="net">{{ $currencySymbol }}
                        {{ number_format($summary['net'], 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table class="transactions">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Account</th>
                    <th>Description</th>
                    <th>Counterparty</th>
                    <th class="right">Debit</th>
                    <th class="right">Credit</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalDebit = 0;
                    $totalCredit = 0;
                    $groupedTransactions = $transactions->groupBy(function ($txn) {
                        return $txn->counterparty ?: 'General Ledger';
                    });
                @endphp

                @foreach ($groupedTransactions as $counterparty => $txns)
                    @php
                        $counterpartyDebit = 0;
                        $counterpartyCredit = 0;
                    @endphp
                    @foreach ($txns as $txn)
                        @php
                            $counterpartyDebit += $txn->debit;
                            $counterpartyCredit += $txn->credit;
                            $totalDebit += $txn->debit;
                            $totalCredit += $txn->credit;
                        @endphp
                        <tr>
                            <td class="muted">{{ $txn->date->format('M d, Y') }}</td>
                            <td>
                                {{ $txn->account->name ?? 'Unknown Account' }}
                                <br><span class="acct-num">{{ $txn->account->account_number ?? '' }}</span>
                            </td>
                            <td>{{ $txn->description ?: 'Payroll Journal Entry' }}</td>
                            <td class="muted">{{ $txn->counterparty ?? '-' }}</td>
                            <td class="right">
                                {{ $txn->debit > 0 ? number_format($txn->debit, 2) : '-' }}
                            </td>
                            <td class="right">
                                {{ $txn->credit > 0 ? number_format($txn->credit, 2) : '-' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="4"
                            style="text-align: right; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Total for {{ $counterparty }}</td>
                        <td class="right">{{ $currencySymbol }} {{ number_format($counterpartyDebit, 2) }}</td>
                        <td class="right">{{ $currencySymbol }} {{ number_format($counterpartyCredit, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="4" style="text-align: right; text-transform: uppercase; letter-spacing: 0.5px;">
                        Grand Totals</td>
                    <td class="right">{{ $currencySymbol }} {{ number_format($totalDebit, 2) }}</td>
                    <td class="right">{{ $currencySymbol }} {{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        Generated on {{ now()->format('M d, Y H:i') }} &nbsp;&bull;&nbsp; {{ $transactions->count() }} entries
    </div>
</body>

</html>
