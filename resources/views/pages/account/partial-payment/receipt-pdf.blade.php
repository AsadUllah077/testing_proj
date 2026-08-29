@php
    $paymentDate = \Carbon\Carbon::parse($customerPayment->payment_date);
    $generatedAt = now();
    $companyName = $company?->name ?? 'Company';
    $companyLines = array_filter([
        $company?->email ? 'Email: ' . $company->email : null,
        $company?->phone ? 'Phone: ' . $company->phone : null,
        $company?->mobile ? 'Mobile: ' . $company->mobile : null,
        $company?->state?->name ?? null,
        $company?->country?->name ?? null,
    ]);
    $statusLabel = ucfirst((string) $customerPayment->status);
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - {{ $customerPayment->reference_no }}</title>
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

        .topbar {
            border-bottom: 4px solid #13b5ea;
            padding-bottom: 18px;
        }

        .layout-table,
        .meta-table,
        .summary-table,
        .items-table {
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

        .document-title {
            color: #172033;
            font-size: 27px;
            font-weight: 400;
            letter-spacing: 0;
            margin: 2px 0 8px;
            text-align: right;
            text-transform: uppercase;
        }

        .meta-table {
            margin-left: auto;
            width: 245px;
        }

        .meta-table td {
            border-bottom: 1px solid #e4e8ee;
            padding: 5px 0;
        }

        .meta-label,
        .summary-label,
        .section-label {
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

        .intro {
            margin-top: 26px;
        }

        .party-name {
            color: #172033;
            font-size: 15px;
            font-weight: 700;
            margin-top: 6px;
        }

        .amount-card {
            background: #f2fbff;
            border: 1px solid #bdeafa;
            margin-left: auto;
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

        .summary-value {
            color: #172033;
            font-size: 13px;
            font-weight: 700;
            margin-top: 4px;
        }

        .paid {
            color: #15803d;
            font-weight: 700;
        }

        .section-title {
            color: #172033;
            font-size: 13px;
            font-weight: 700;
            margin: 26px 0 8px;
        }

        .items-table th {
            border-bottom: 1.4px solid #172033;
            color: #172033;
            font-size: 8.5px;
            font-weight: 700;
            padding: 7px 6px;
            text-align: left;
            text-transform: uppercase;
        }

        .items-table td {
            border-bottom: 1px solid #e6e9ee;
            color: #2f3b4f;
            padding: 8px 6px;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) td {
            background: #fbfcfd;
        }

        .items-table tfoot td {
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

        .notes {
            background: #fff8e6;
            border-left: 4px solid #f59e0b;
            color: #5f4000;
            margin-top: 22px;
            padding: 12px 14px;
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
    <div class="topbar">
        <table class="layout-table">
            <tr>
                <td style="width:54%;">
                    <div class="brand-name">{{ $companyName }}</div>
                    @foreach ($companyLines as $line)
                        <div class="muted-line">{{ $line }}</div>
                    @endforeach
                </td>
                <td style="width:46%;">
                    <div class="document-title">Payment Receipt</div>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Receipt No.</td>
                            <td class="meta-value">{{ $customerPayment->reference_no }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Payment Date</td>
                            <td class="meta-value">{{ $paymentDate->format('d M Y') }}</td>
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

    <table class="layout-table intro">
        <tr>
            <td style="width:58%;">
                <div class="section-label">Received From</div>
                <div class="party-name">{{ $customerPayment->customer->name }}</div>
                @if ($customerPayment->customer->email)
                    <div class="muted-line">{{ $customerPayment->customer->email }}</div>
                @endif
                <div class="muted-line" style="margin-top:5px;">Account: {{ $customerPayment->account->name ?? 'N/A' }}</div>
            </td>
            <td style="width:42%;">
                <div class="amount-card">
                    <div class="label">Amount Received</div>
                    <div class="amount">{{ numberFormat($customerPayment->total_amount) }}</div>
                    <div class="note">{{ $statusLabel }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Reference No.</div>
                <div class="summary-value">{{ $customerPayment->reference_no }}</div>
            </td>
            <td>
                <div class="summary-label">Payment Date</div>
                <div class="summary-value">{{ $paymentDate->format('d M Y') }}</div>
            </td>
            <td>
                <div class="summary-label">Status</div>
                <div class="summary-value">{{ $statusLabel }}</div>
            </td>
            <td>
                <div class="summary-label">Total Received</div>
                <div class="summary-value paid">{{ numberFormat($customerPayment->total_amount) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Invoice Allocation</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:20%;">Invoice</th>
                <th style="width:14%;">Invoice Date</th>
                <th class="text-right" style="width:18%;">Invoice Total</th>
                <th class="text-right" style="width:22%;">Previous Balance</th>
                <th class="text-right" style="width:18%;">Applied Amount</th>
                <th class="text-right" style="width:8%;">After</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customerPayment->items as $item)
                @php $balanceAfter = max(0, (float) $item->invoice_remaining - (float) $item->amount_to_apply); @endphp
                <tr>
                    <td class="document-no">{{ $item->sale->invoice_no ?: $item->sale->unique_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->sale->date)->format('d M Y') }}</td>
                    <td class="text-right">{{ numberFormat($item->invoice_total) }}</td>
                    <td class="text-right">{{ numberFormat($item->invoice_remaining) }}</td>
                    <td class="text-right paid">{{ numberFormat($item->amount_to_apply) }}</td>
                    <td class="text-right">{{ numberFormat($balanceAfter) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total Received Amount</td>
                <td class="text-right paid">{{ numberFormat($customerPayment->total_amount) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if ($customerPayment->note)
        <div class="notes">
            <strong>Remarks:</strong> {{ $customerPayment->note }}
        </div>
    @endif

    <div class="footer">
        {{ $companyName }} | Receipt generated on {{ $generatedAt->format('d M Y') }}. This is a computer-generated receipt.
    </div>
</body>

</html>
