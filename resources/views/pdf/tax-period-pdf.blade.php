<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Period Export</title>
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

        .tax-summary {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            padding: 15px;
            margin-bottom: 20px;
        }

        .tax-summary h3 {
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

        .stats-section {
            margin-bottom: 20px;
        }

        .stats-section h3 {
            color: #0078C8;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats-table td {
            padding: 15px;
            border: 1px solid #e2e8f0;
            text-align: center;
            vertical-align: top;
        }

        .stat-label {
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e293b;
        }

        .transactions {
            margin-top: 30px;
        }

        .transactions h3 {
            color: #0078C8;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 16px;
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

        .text-green {
            color: #16a34a;
        }

        .text-red {
            color: #dc2626;
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
        <h1>{{ $data['taxPeriod']->name }}</h1>
        <p>{{ $data['taxPeriod']->start_date->format('M d, Y') }} - {{ $data['taxPeriod']->end_date->format('M d, Y') }}
            · <span style="text-transform: uppercase;">
                @if (
                    $data['taxPeriod']->status === 'settled' &&
                        $data['taxPeriod']->paid_amount > 0 &&
                        $data['taxPeriod']->paid_amount < abs($data['taxPeriod']->net_amount))
                    Settled (Partial)
                @else
                    {{ $data['taxPeriod']->status }}
                @endif
            </span>
        </p>
        <p><strong>Export Date:</strong> {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Tax Summary Section -->
    @if ($data['taxPeriod']->status !== 'open')
        <div class="tax-summary">
            <h3>Tax Period Summary</h3>
            <table class="summary-table">
                <tr>
                    <td class="summary-label">Total Sales Tax</td>
                    <td class="summary-value">{{ number_format($data['taxPeriod']->total_payable, 2) }}</td>
                </tr>
                <tr>
                    <td class="summary-label">Total Purchase Tax</td>
                    <td class="summary-value">{{ number_format($data['taxPeriod']->total_receivable, 2) }}</td>
                </tr>
                @if ($data['taxPeriod']->carried_forward_amount > 0)
                    <tr>
                        <td class="summary-label">Carried Forward</td>
                        <td class="summary-value" style="color: #16a34a;">
                            {{ number_format($data['taxPeriod']->carried_forward_amount, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="summary-label">Net Tax
                        {{ $data['taxPeriod']->net_amount > 0 ? 'Payable' : 'Refundable' }}</td>
                    <td class="summary-value"
                        style="color: {{ $data['taxPeriod']->net_amount > 0 ? '#dc2626' : '#16a34a' }};">
                        {{ number_format(abs($data['taxPeriod']->net_amount), 2) }}
                        @php $paidAmount = $data['taxPeriod']->paid_amount ?? 0; @endphp
                        @if ($paidAmount > 0)
                            <br><span style="font-size: 10px; color: #64748b;">Paid:
                                {{ number_format($paidAmount, 2) }}</span>
                            <br><span
                                style="font-size: 10px; color: {{ abs($data['taxPeriod']->net_amount) - $paidAmount > 0 ? '#ea580c' : '#16a34a' }};">
                                Remaining: {{ number_format(abs($data['taxPeriod']->net_amount) - $paidAmount, 2) }}
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="summary-label">Status</td>
                    <td class="summary-value">
                        <span
                            style="
                            display: inline-block;
                            padding: 4px 12px;
                            border-radius: 9999px;
                            font-size: 10px;
                            font-weight: 500;
                            text-transform: uppercase;
                            @if (
                                $data['taxPeriod']->status === 'settled' &&
                                    $data['taxPeriod']->paid_amount > 0 &&
                                    $data['taxPeriod']->paid_amount < abs($data['taxPeriod']->net_amount)) background-color: #fed7aa;
                                color: #9a3412;
                            @elseif($data['taxPeriod']->status === 'paid')
                                background-color: #bbf7d0;
                                color: #166534;
                            @else
                                background-color: #fef3c7;
                                color: #92400e; @endif
                        ">
                            @if (
                                $data['taxPeriod']->status === 'settled' &&
                                    $data['taxPeriod']->paid_amount > 0 &&
                                    $data['taxPeriod']->paid_amount < abs($data['taxPeriod']->net_amount))
                                Settled (Partial)
                            @else
                                {{ ucfirst($data['taxPeriod']->status) }}
                            @endif
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <!-- Stats Section -->
    @if (isset($data['balances']))
        <div class="stats-section">
            <h3>Period Statistics</h3>
            <table class="stats-table">
                <tr>
                    <td>
                        <div class="stat-label">Total Payable (Sales)</div>
                        <div class="stat-value">{{ number_format($data['balances']['payable'] ?? 0, 2) }}</div>
                    </td>
                    <td>
                        <div class="stat-label">Total Refund (Purchases)</div>
                        <div class="stat-value">{{ number_format($data['balances']['receivable'] ?? 0, 2) }}</div>
                    </td>
                    <td>
                        <div class="stat-label">Net
                            {{ ($data['balances']['net'] ?? 0) > 0 ? 'Payable' : 'Receivable' }}</div>
                        <div class="stat-value"
                            style="color: {{ ($data['balances']['net'] ?? 0) > 0 ? '#dc2626' : '#16a34a' }};">
                            {{ number_format(abs($data['balances']['net'] ?? 0), 2) }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <!-- Transactions Section -->
    <div class="transactions">
        <h3>Accounting Transactions</h3>
        @if ($data['entries'] && $data['entries']->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Account</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @php $runningBalance = 0; @endphp
                    @foreach ($data['entries'] as $entry)
                        @php
                            $runningBalance += $entry->debit - $entry->credit;
                        @endphp
                        <tr>
                            <td>{{ $entry->date ? $entry->date->format('M d, Y') : '—' }}</td>
                            <td>{{ $entry->account?->name ?? '—' }}</td>
                            <td>{{ $entry->description ?? '—' }}</td>
                            <td class="text-right text-green">
                                {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '' }}
                            </td>
                            <td class="text-right text-red">
                                {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '' }}
                            </td>
                            <td class="text-right" style="color: {{ $runningBalance >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ number_format(abs($runningBalance), 2) }}
                            </td>
                            <td>{{ $entry->unique_no ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p style="font-weight: bold;">Total Transactions: {{ $data['entries']->count() }}</p>
        @else
            <div
                style="text-align: center; padding: 40px 20px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
                <div style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;">📄</div>
                <h4 style="font-size: 16px; font-weight: 500; color: #374151; margin-bottom: 8px;">No transactions found
                </h4>
                <p style="font-size: 14px; color: #6b7280;">This tax period has not been settled yet, or no journal
                    entries match the criteria.</p>
                @if ($data['taxPeriod']->status === 'open')
                    <p style="font-size: 12px; color: #9ca3af; margin-top: 8px;">Settle this tax period first to
                        generate journal entries.</p>
                @endif
            </div>
        @endif
    </div>

    <div class="footer">
        <p>This report was generated on {{ now()->format('M d, Y \a\t H:i') }}</p>
        <p>Tax Period Report - {{ $data['taxPeriod']->name }}</p>
    </div>
</body>

</html>
