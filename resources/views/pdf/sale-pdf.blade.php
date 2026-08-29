@php
    $invoiceTemplate = $invoiceTemplate ?? new \App\Models\InvoiceTemplate();
    $exportColumns = $exportColumns ?? [];
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        @page {
            margin: {{ $invoiceTemplate->top_margin ?? '1.0' }}{{ $invoiceTemplate->measures_in ?? 'cm' }} 1.0{{ $invoiceTemplate->measures_in ?? 'cm' }} {{ $invoiceTemplate->bottom_margin ?? '1.0' }}{{ $invoiceTemplate->measures_in ?? 'cm' }} 1.0{{ $invoiceTemplate->measures_in ?? 'cm' }};
        }

        body {
            font-family: {{ $invoiceTemplate->font ?? 'Arial' }}, sans-serif;
            font-size: {{ $invoiceTemplate->font_size ?? '9pt' }};
            color: #333;
            line-height: 1.4;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #333;
        }

        .report-title {
            font-size: 14pt;
            color: #777;
            margin-top: 5px;
        }

        .report-info {
            margin-top: 10px;
            font-size: 8pt;
            color: #999;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            border-bottom: 1px solid #333;
            padding: 10px 5px;
            text-align: left;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            font-size: 7pt;
        }

        td {
            padding: 8px 5px;
            border-bottom: 1px solid #eee;
            font-size: 8pt;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            text-align: center;
            font-size: 7pt;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #333;
            border-bottom: 2px solid #333;
            padding: 10px 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="company-name">{{ auth()->user()->company->name ?? 'Company Name' }}</div>
        <div class="report-title">Sales Report</div>
        <div class="report-info">
            Generated on: {{ now()->format('d M Y, h:i A') }}<br>
            Total Records: {{ $sales->count() }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($exportColumns as $column)
                    <th style="{{ ($column['align'] ?? 'left') === 'right' ? 'text-align: right;' : '' }}">
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $totalSums = [];

                foreach ($exportColumns as $key => $column) {
                    if ($column['is_total'] ?? false) {
                        $totalSums[$key] = 0;
                    }
                }
            @endphp
            @forelse($sales as $index => $sale)
                @php
                    $rowValues = [];

                    foreach ($exportColumns as $key => $column) {
                        $rowValues[$key] = $column['value']($sale, $index);

                        if (($column['is_total'] ?? false) && is_numeric($rowValues[$key])) {
                            $totalSums[$key] += (float) $rowValues[$key];
                        }
                    }
                @endphp
                <tr>
                    @foreach ($exportColumns as $key => $column)
                        <td class="{{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                            <small>
                                {{ ($column['align'] ?? 'left') === 'right' && is_numeric($rowValues[$key]) ? number_format((float) $rowValues[$key], 2) : ($rowValues[$key] ?? 'N/A') }}
                            </small>
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($exportColumns), 1) }}" class="text-center" style="padding: 30px; color: #999;">
                        No records found.
                    </td>
                </tr>
            @endforelse
            @if ($sales->count() > 0 && !empty($totalSums))
                <tr class="total-row">
                    @php $totalLabelRendered = false; @endphp
                    @foreach ($exportColumns as $key => $column)
                        <td class="{{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                            @if (array_key_exists($key, $totalSums))
                                {{ number_format($totalSums[$key], 2) }}
                            @elseif (!$totalLabelRendered)
                                TOTAL
                                @php $totalLabelRendered = true; @endphp
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        {{ auth()->user()->company->name ?? 'Company Name' }} | Page {{ '{PAGENO}' }} | Generated by Enterprise ERP
    </div>
</body>

</html>
