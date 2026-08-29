<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Customer Aged Receivable - {{ $as_of_date }}</title>
    <style>
        @page {
            margin: {{ $invoiceTemplate->top_margin ?? 50 }}px {{ $invoiceTemplate->address_padding ?? 30 }}px {{ $invoiceTemplate->bottom_margin ?? 50 }}px;
        }
        body {
            font-family: {{ $invoiceTemplate->font ?? 'Helvetica Neue, Arial, sans-serif' }};
            font-size: {{ $invoiceTemplate->font_size ?? 10 }}px;
            color: #333;
            line-height: 1.4;
        }
        .report-header { border-bottom: 3px solid #0078C8; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 24px; color: #0078C8; font-weight: bold; margin: 0 0 5px 0; }
        .report-title { font-size: 18px; color: #333; font-weight: bold; margin: 5px 0; }
        .report-subtitle { font-size: 12px; color: #666; margin: 5px 0; }
        .summary-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin-bottom: 25px; }
        .summary-title { font-size: 12px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .summary-item { text-align: center; }
        .summary-label { font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 3px; }
        .summary-value { font-size: 14px; font-weight: bold; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .customer-header { background: linear-gradient(to bottom, #f8f9fa, #e9ecef); border: 1px solid #dee2e6; border-bottom: none; padding: 8px 12px; font-weight: bold; color: #333; font-size: 11px; margin-top: 15px; }
        .customer-name { font-size: 12px; color: #0078C8; }
        thead th { background: #0078C8; color: white; font-weight: bold; font-size: 10px; text-transform: uppercase; padding: 8px 6px; text-align: right; border: 1px solid #0078C8; }
        thead th:first-child { text-align: left; }
        tbody td { padding: 6px; text-align: right; border: 1px solid #dee2e6; font-size: 10px; }
        tbody td:first-child { text-align: left; }
        .invoice-row { background: white; }
        .amount { font-weight: bold; }
        .overdue { color: #dc3545; }
        .customer-total-row { background: #e9ecef; font-weight: bold; }
        .customer-total-row td { border-top: 2px solid #adb5bd; border-bottom: 2px solid #adb5bd; padding: 8px 6px; }
        .grand-total-row { background: #0078C8; color: white; font-weight: bold; }
        .grand-total-row td { border: 1px solid #0078C8; padding: 10px 6px; font-size: 11px; }
        .bucket-amount { min-width: 80px; }
        .report-footer { border-top: 1px solid #dee2e6; padding-top: 10px; margin-top: 30px; font-size: 9px; color: #666; text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
    </style>
</head>

<body>
    <div class="report-header">
        @if (isset($invoiceTemplate) && $invoiceTemplate->show_logo && $invoiceTemplate->logo)
            <img src="{{ public_path('storage/' . $invoiceTemplate->logo) }}" alt="Logo"
                style="width: 100px; height: auto; float: {{ $invoiceTemplate->logo_alignment == 'right' ? 'right' : 'left' }}; margin-bottom: 10px;">
            <div style="clear: both;"></div>
        @endif
        <div class="company-name">{{ loggedCompanyId()['company_name'] }}</div>
        <div class="report-title">Customer Aged Receivable (Trade Customers Only)</div>
        <div class="report-subtitle">
            @if ($from_date)
                From {{ \Carbon\Carbon::parse($from_date)->format('j F Y') }}
            @endif
            As of {{ \Carbon\Carbon::parse($as_of_date)->format('j F Y') }} |
            Period: {{ strtoupper($period) }} |
            Generated: {{ \Carbon\Carbon::now()->format('j F Y g:i A') }}
        </div>
    </div>

    <div class="summary-box">
        <div class="summary-title">Summary</div>
        <div class="summary-grid">
            @foreach ($labels as $index => $label)
                <div class="summary-item">
                    <div class="summary-label">{{ $label }}</div>
                    <div class="summary-value {{ $index >= count($labels) - 1 ? 'overdue' : '' }}">
                        {{ numberFormat($grandTotals[$index]) }}
                    </div>
                </div>
            @endforeach
            <div class="summary-item">
                <div class="summary-label">Total Balance</div>
                <div class="summary-value">{{ numberFormat($grandTotal) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Customers</div>
                <div class="summary-value">{{ count($reportData) }}</div>
            </div>
        </div>
    </div>

    @foreach ($reportData as $customerIndex => $customerData)
        @if ($customerIndex > 0)
            <div style="height: 20px;"></div>
        @endif

        <div class="customer-header">
            <span class="customer-name">{{ $customerData['name'] ?? $customerData['vendor'] ?? 'Unknown' }}</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-left" width="20%">Invoice</th>
                    <th class="text-left" width="15%">Date</th>
                    @foreach ($labels as $label)
                        <th width="10%">{{ $label }}</th>
                    @endforeach
                    <th width="10%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customerData['rows'] as $row)
                    <tr class="invoice-row">
                        <td class="text-left">{{ $row['invoice_no'] }}</td>
                        <td class="text-left">{{ \Carbon\Carbon::parse($row['invoice_date'])->format('d/m/Y') }}</td>
                        @foreach ($row['buckets'] as $index => $amount)
                            <td class="amount bucket-amount {{ $index >= count($row['buckets']) - 1 ? 'overdue' : '' }}">
                                {{ $amount > 0 ? numberFormat($amount) : '-' }}
                            </td>
                        @endforeach
                        <td class="amount font-bold">{{ numberFormat($row['total']) }}</td>
                    </tr>
                @endforeach

                <tr class="customer-total-row">
                    <td class="text-left" colspan="2">Total for {{ $customerData['name'] ?? $customerData['vendor'] ?? 'Unknown' }}</td>
                    @foreach ($customerData['totals'] as $index => $total)
                        <td class="amount {{ $index >= count($customerData['totals']) - 1 ? 'overdue' : '' }}">
                            {{ numberFormat($total) }}
                        </td>
                    @endforeach
                    <td class="amount font-bold">{{ numberFormat($customerData['total']) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <table>
        <tr class="grand-total-row">
            <td class="text-left" colspan="2">TOTAL</td>
            @foreach ($grandTotals as $index => $grandTotalBucket)
                <td class="amount {{ $index >= count($grandTotals) - 1 ? 'overdue' : '' }}">
                    {{ numberFormat($grandTotalBucket) }}
                </td>
            @endforeach
            <td class="amount font-bold">{{ numberFormat($grandTotal) }}</td>
        </tr>
    </table>

    <div class="report-footer">
        <div>Report generated on {{ \Carbon\Carbon::now()->format('j F Y \a\t g:i A') }}</div>
        <div style="margin-top: 5px;">
            <span style="color: #0078C8;">■</span> Current
            <span style="margin-left: 10px; color: #dc3545;">■</span> Overdue
        </div>
        <div style="margin-top: 8px; font-style: italic;">
            Trade customer balances only. Statutory/tax receivables are tracked in separate reports.
        </div>
    </div>
</body>

</html>
