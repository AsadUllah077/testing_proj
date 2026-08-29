@php
    $statementDate = \Carbon\Carbon::parse($asOf);
    $generatedAt = now();
    $companyName = $company?->name ?? 'Company';
    $companyLines = array_filter([
        $company?->email ? 'Email: ' . $company->email : null,
        $company?->phone ? 'Phone: ' . $company->phone : null,
        $company?->mobile ? 'Mobile: ' . $company->mobile : null,
        $company?->state?->name ?? null,
        $company?->country?->name ?? null,
    ]);
    $vendorLines = array_filter([
        $vendor->billing_address ?? $vendor->address ?? null,
        $vendor->email ? 'Email: ' . $vendor->email : null,
        $vendor->phone ? 'Phone: ' . $vendor->phone : null,
    ]);
    $statementNumber = 'VS-' . str_pad((string) $vendor->id, 5, '0', STR_PAD_LEFT) . '-' . $statementDate->format('Ymd');
    $currentTotal = 0;
    $overdueTotal = 0;
    $overdueCount = 0;

    foreach ($bills as $bill) {
        $balance = (float) ($bill->remaining_amount ?? 0);

        if ($balance <= 0) {
            continue;
        }

        $dueDate = \Carbon\Carbon::parse($bill->date)->addDays(30);

        if ($dueDate->lt($statementDate)) {
            $overdueTotal += $balance;
            $overdueCount++;
        } else {
            $currentTotal += $balance;
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Statement</title>
    <style>
        @page {
            margin: 22px 28px 42px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #172033;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10.5px;
            line-height: 1.35;
            margin: 0;
        }

        .page {
            width: 100%;
        }

        .topbar {
            border-bottom: 4px solid #13b5ea;
            padding-bottom: 18px;
        }

        .layout-table,
        .summary-table,
        .ledger-table {
            border-collapse: collapse;
            width: 100%;
        }

        .layout-table td {
            vertical-align: top;
        }

        .brand-name {
            color: #172033;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .muted-line {
            color: #697386;
            font-size: 9.5px;
            margin-bottom: 2px;
        }

        .statement-title {
            color: #172033;
            font-size: 28px;
            font-weight: 400;
            letter-spacing: 0;
            margin: 2px 0 8px;
            text-align: right;
            text-transform: uppercase;
        }

        .meta-table {
            border-collapse: collapse;
            margin-left: auto;
            width: 230px;
        }

        .meta-table td {
            border-bottom: 1px solid #e4e8ee;
            padding: 5px 0;
        }

        .meta-label {
            color: #697386;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta-value {
            color: #172033;
            font-size: 10px;
            font-weight: 700;
            text-align: right;
        }

        .statement-to {
            margin-top: 26px;
        }

        .section-label {
            color: #697386;
            font-size: 8.5px;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .party-name {
            color: #172033;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .amount-card {
            background: #f2fbff;
            border: 1px solid #bdeafa;
            margin-left: auto;
            margin-top: 24px;
            padding: 14px 16px;
            text-align: right;
            width: 245px;
        }

        .amount-card .label {
            color: #426071;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .amount-card .amount {
            color: #172033;
            font-size: 23px;
            font-weight: 700;
            margin-top: 5px;
        }

        .amount-card .note {
            color: #697386;
            font-size: 9px;
            margin-top: 5px;
        }

        .summary-table {
            margin-top: 24px;
        }

        .summary-table td {
            border: 1px solid #e4e8ee;
            padding: 11px 13px;
            width: 25%;
        }

        .summary-label {
            color: #697386;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .summary-value {
            color: #172033;
            font-size: 13px;
            font-weight: 700;
            margin-top: 4px;
        }

        .summary-value.positive {
            color: #15803d;
        }

        .summary-value.warning {
            color: #b42318;
        }

        .ledger-title {
            color: #172033;
            font-size: 13px;
            font-weight: 700;
            margin: 26px 0 8px;
        }

        .ledger-table th {
            border-bottom: 1.4px solid #172033;
            color: #172033;
            font-size: 8.5px;
            font-weight: 700;
            padding: 7px 6px;
            text-align: left;
            text-transform: uppercase;
        }

        .ledger-table td {
            border-bottom: 1px solid #e6e9ee;
            color: #2f3b4f;
            padding: 8px 6px;
            vertical-align: top;
        }

        .ledger-table tbody tr:nth-child(even) td {
            background: #fbfcfd;
        }

        .ledger-table tfoot td {
            background: #f6f8fa;
            border-bottom: 0;
            border-top: 1.4px solid #172033;
            color: #172033;
            font-weight: 700;
            padding: 9px 6px;
        }

        .text-right {
            text-align: right;
        }

        .document-no {
            color: #0078c8;
            font-weight: 700;
        }

        .status {
            border-radius: 9px;
            display: inline-block;
            font-size: 8px;
            font-weight: 700;
            padding: 2px 7px;
            text-transform: uppercase;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-open {
            background: #e0f2fe;
            color: #075985;
        }

        .empty-row td {
            color: #697386;
            padding: 26px 6px;
            text-align: center;
        }

        .footer {
            border-top: 1px solid #e4e8ee;
            bottom: -26px;
            color: #697386;
            font-size: 8.5px;
            left: 0;
            padding-top: 8px;
            position: fixed;
            right: 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <table class="layout-table">
                <tr>
                    <td style="width: 54%;">
                        <div class="brand-name">{{ $companyName }}</div>
                        @foreach ($companyLines as $line)
                            <div class="muted-line">{{ $line }}</div>
                        @endforeach
                    </td>
                    <td style="width: 46%;">
                        <div class="statement-title">Statement</div>
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">Statement No.</td>
                                <td class="meta-value">{{ $statementNumber }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Statement Date</td>
                                <td class="meta-value">{{ $statementDate->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Generated</td>
                                <td class="meta-value">{{ $generatedAt->format('d M Y H:i') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <table class="layout-table statement-to">
            <tr>
                <td style="width: 58%;">
                    <div class="section-label">Statement From</div>
                    <div class="party-name">{{ $vendor->name }}</div>
                    @foreach ($vendorLines as $line)
                        <div class="muted-line">{{ $line }}</div>
                    @endforeach
                </td>
                <td style="width: 42%;">
                    <div class="amount-card">
                        <div class="label">Amount Payable</div>
                        <div class="amount">{{ numberFormat($totalOutstanding) }}</div>
                        <div class="note">
                            @if ($overdueCount > 0)
                                {{ $overdueCount }} overdue bill{{ $overdueCount === 1 ? '' : 's' }}
                            @else
                                No overdue bills as of {{ $statementDate->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="summary-table">
            <tr>
                <td>
                    <div class="summary-label">Total Billed</div>
                    <div class="summary-value">{{ numberFormat($totalBilled) }}</div>
                </td>
                <td>
                    <div class="summary-label">Paid To Vendor</div>
                    <div class="summary-value positive">{{ numberFormat($totalPaid) }}</div>
                </td>
                <td>
                    <div class="summary-label">Current</div>
                    <div class="summary-value">{{ numberFormat($currentTotal) }}</div>
                </td>
                <td>
                    <div class="summary-label">Overdue</div>
                    <div class="summary-value warning">{{ numberFormat($overdueTotal) }}</div>
                </td>
            </tr>
        </table>

        <div class="ledger-title">Bill Activity</div>
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 17%;">Bill</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 12%;">Due</th>
                    <th class="text-right" style="width: 13%;">Amount</th>
                    <th class="text-right" style="width: 13%;">Paid</th>
                    <th class="text-right" style="width: 13%;">Balance</th>
                    <th class="text-right" style="width: 12%;">Running</th>
                    <th style="width: 8%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBalance = 0; @endphp
                @forelse ($bills as $bill)
                    @php
                        $runningBalance += (float) ($bill->remaining_amount ?? 0);
                        $dueDate = \Carbon\Carbon::parse($bill->date)->addDays(30);
                        $isOverdue = (float) ($bill->remaining_amount ?? 0) > 0 && $dueDate->lt($statementDate);
                    @endphp
                    <tr>
                        <td class="document-no">{{ $bill->invoice_no ?: $bill->unique_no }}</td>
                        <td>{{ \Carbon\Carbon::parse($bill->date)->format('d M Y') }}</td>
                        <td style="{{ $isOverdue ? 'color:#b42318;font-weight:700;' : '' }}">{{ $dueDate->format('d M Y') }}</td>
                        <td class="text-right">{{ numberFormat($bill->total) }}</td>
                        <td class="text-right" style="color:#15803d;">{{ numberFormat($bill->paid_amount) }}</td>
                        <td class="text-right" style="{{ (float) $bill->remaining_amount > 0 ? 'font-weight:700;color:#172033;' : 'color:#697386;' }}">
                            {{ numberFormat($bill->remaining_amount) }}
                        </td>
                        <td class="text-right">{{ numberFormat($runningBalance) }}</td>
                        <td>
                            @if ((float) $bill->remaining_amount <= 0)
                                <span class="status status-paid">Paid</span>
                            @elseif ($isOverdue)
                                <span class="status status-overdue">Due</span>
                            @else
                                <span class="status status-open">Open</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="8">No bills found for this statement period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">Statement Totals</td>
                    <td class="text-right">{{ numberFormat($totalBilled) }}</td>
                    <td class="text-right" style="color:#15803d;">{{ numberFormat($totalPaid) }}</td>
                    <td class="text-right">{{ numberFormat($totalOutstanding) }}</td>
                    <td class="text-right">{{ numberFormat($totalOutstanding) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        {{ $companyName }} | This vendor statement was generated on {{ $generatedAt->format('d M Y') }}. Please contact our accounts team if anything looks incorrect.
    </div>
</body>
</html>
