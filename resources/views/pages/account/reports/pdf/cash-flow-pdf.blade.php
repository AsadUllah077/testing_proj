@php
    $periods = $cashFlow['periods'];
    $pData = $cashFlow['data'];
    $showVariance = $showVarianceAmount || $showVariancePercentage;
    $isMulti = count($periods) > 1;

    // Helper to get union of descriptions across all periods for a specific activity type
    $getDescriptions = function ($type) use ($pData) {
        $descs = [];
        foreach ($pData as $period) {
            foreach ($period[$type] as $activity) {
                $descs[$activity['description']] = true;
            }
        }
        return array_keys($descs);
    };

    $operatingDescriptions = $getDescriptions('operating_activities');
    $investingDescriptions = $getDescriptions('investing_activities');
    $financingDescriptions = $getDescriptions('financing_activities');

    // Calculate column widths
    $totalCols = count($periods);
    if ($showVarianceAmount) {
        $totalCols += count($periods) - 1;
    }
    if ($showVariancePercentage) {
        $totalCols += count($periods) - 1;
    }
    $amountColWidth = $isMulti ? 70 / $totalCols . '%' : '180px';
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Cash Flow Statement - {{ $company->name ?? 'Company' }}</title>
    <style>
        body {
            font-family: {{ $invoiceTemplate->font ?? 'Arial, sans-serif' }};
            font-size: {{ $invoiceTemplate->font_size ?? 10 }}px;
            color: #1a1a1a;
            margin: {{ $invoiceTemplate->top_margin ?? 10 }}px {{ $invoiceTemplate->address_padding ?? 10 }}px {{ $invoiceTemplate->bottom_margin ?? 10 }}px;
        }

        .header {
            text-align: left;
            margin-bottom: 20px;
            border-bottom: 2px solid #0078C8;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #1a1a1a;
        }

        .header .company-name {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .header .report-date {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: right;
            padding: 8px 5px;
            border-bottom: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            word-wrap: break-word;
        }

        th.category {
            text-align: left;
            width: 200px;
            border-left: 1px solid #dee2e6;
        }

        td {
            padding: 6px 5px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 9px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        td.category {
            text-align: left;
            border-right: 1px solid #dee2e6;
            border-left: 1px solid #dee2e6;
        }

        td.amount {
            text-align: right;
            border-right: 1px solid #f0f0f0;
        }

        .section-header {
            background-color: #e8f4f8;
            font-weight: bold;
            font-size: 11px;
            color: #1a1a1a;
        }

        .section-header td {
            padding: 10px 5px;
            border-top: 2px solid #0078C8;
            border-bottom: 1px solid #0078C8;
        }

        .section-total {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .section-total td {
            padding: 8px 5px;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }

        .activity-item {
            padding-left: 20px !important;
        }

        .balance-row {
            background-color: #fff3cd;
            font-weight: 600;
        }

        .balance-row td {
            padding: 10px 5px;
            border-top: 1px solid #ffc107;
            border-bottom: 1px solid #ffc107;
        }

        .net-movement {
            background-color: #e8f4f8;
            font-weight: bold;
            font-size: 11px;
        }

        .net-movement td {
            padding: 12px 5px;
            border-top: 2px solid #0078C8;
            border-bottom: 2px solid #0078C8;
        }

        .closing-balance {
            background-color: #0078C8;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .closing-balance td {
            padding: 14px 5px;
        }

        .positive {
            color: #0f9f6e;
        }

        .negative {
            color: #e02424;
        }

        .footer {
            text-align: center;
            color: #999;
            font-size: 8px;
            margin-top: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
        }

        .variance-col {
            background-color: #f0f7ff;
            color: #444;
        }
    </style>
</head>

<body>
    <div class="header">
        @if (isset($invoiceTemplate) && $invoiceTemplate->show_logo && $invoiceTemplate->logo)
            <img src="{{ public_path('storage/' . $invoiceTemplate->logo) }}" alt="Logo"
                style="width: 100px; height: auto; float: {{ $invoiceTemplate->logo_alignment == 'right' ? 'right' : 'left' }}; margin-bottom: 10px;">
            <div style="clear: both;"></div>
        @endif
        <h1>Cash Flow Statement</h1>
        <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
        <div class="report-date">
            {{ dateParse($startDate) }} to {{ dateParse($endDate) }}
            @if ($isMulti)
                <span style="color: #0078C8; font-weight: bold;">(Multi-period comparison)</span>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="category">Category</th>
                @foreach ($periods as $index => $period)
                    <th style="width:{{ $amountColWidth }}">
                        {{ $period['label'] }}
                    </th>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <th class="variance-col" style="width:{{ $amountColWidth }}">Var Amt</th>
                        @endif
                        @if ($showVariancePercentage)
                            <th class="variance-col" style="width:{{ $amountColWidth }}">Var %</th>
                        @endif
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance -->
            <tr class="balance-row">
                <td class="category">Opening Cash Balance</td>
                @foreach ($pData as $index => $data)
                    <td class="amount">
                        {{ numberFormat($data['opening_balance']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['opening_balance'];
                            $current = $data['opening_balance'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ numberFormat($variance) }}
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($variancePct, 2) }}%
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Operating Activities -->
            <tr class="section-header">
                <td colspan="{{ 1 + $totalCols }}" class="category">CASH FLOWS FROM OPERATING ACTIVITIES</td>
            </tr>
            @foreach ($operatingDescriptions as $desc)
                <tr>
                    <td class="category activity-item">{{ $desc }}</td>
                    @foreach ($pData as $index => $data)
                        @php
                            $found = 0;
                            foreach ($data['operating_activities'] as $act) {
                                if ($act['description'] === $desc) {
                                    $found = $act['amount'];
                                    break;
                                }
                            }
                        @endphp
                        <td class="amount">
                            {{ $found != 0 ? numberFormat($found) : '-' }}
                        </td>
                        @if ($index > 0)
                            @php
                                $primaryFound = 0;
                                foreach ($pData[0]['operating_activities'] as $act) {
                                    if ($act['description'] === $desc) {
                                        $primaryFound = $act['amount'];
                                        break;
                                    }
                                }
                                $variance = $primaryFound - $found;
                                $variancePct =
                                    $found != 0 ? ($variance / abs($found)) * 100 : ($primaryFound != 0 ? 100 : 0);
                            @endphp
                            @if ($showVarianceAmount)
                                <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                    {{ $variance != 0 ? numberFormat($variance) : '-' }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                    {{ $variance != 0 ? number_format($variancePct, 2) . '%' : '-' }}
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach
            <tr class="section-total">
                <td class="category">Net Cash from Operating Activities</td>
                @foreach ($pData as $index => $data)
                    <td class="amount {{ $data['net_cash_from_operating'] >= 0 ? 'positive' : 'negative' }}">
                        {{ numberFormat($data['net_cash_from_operating']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['net_cash_from_operating'];
                            $current = $data['net_cash_from_operating'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ numberFormat($variance) }}
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($variancePct, 2) }}%
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Investing Activities -->
            <tr class="section-header">
                <td colspan="{{ 1 + $totalCols }}" class="category">CASH FLOWS FROM INVESTING ACTIVITIES</td>
            </tr>
            @foreach ($investingDescriptions as $desc)
                <tr>
                    <td class="category activity-item">{{ $desc }}</td>
                    @foreach ($pData as $index => $data)
                        @php
                            $found = 0;
                            foreach ($data['investing_activities'] as $act) {
                                if ($act['description'] === $desc) {
                                    $found = $act['amount'];
                                    break;
                                }
                            }
                        @endphp
                        <td class="amount">
                            {{ $found != 0 ? numberFormat($found) : '-' }}
                        </td>
                        @if ($index > 0)
                            @php
                                $primaryFound = 0;
                                foreach ($pData[0]['investing_activities'] as $act) {
                                    if ($act['description'] === $desc) {
                                        $primaryFound = $act['amount'];
                                        break;
                                    }
                                }
                                $variance = $primaryFound - $found;
                                $variancePct =
                                    $found != 0 ? ($variance / abs($found)) * 100 : ($primaryFound != 0 ? 100 : 0);
                            @endphp
                            @if ($showVarianceAmount)
                                <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                    {{ $variance != 0 ? numberFormat($variance) : '-' }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                    {{ $variance != 0 ? number_format($variancePct, 2) . '%' : '-' }}
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach
            <tr class="section-total">
                <td class="category">Net Cash from Investing Activities</td>
                @foreach ($pData as $index => $data)
                    <td class="amount {{ $data['net_cash_from_investing'] >= 0 ? 'positive' : 'negative' }}">
                        {{ numberFormat($data['net_cash_from_investing']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['net_cash_from_investing'];
                            $current = $data['net_cash_from_investing'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ numberFormat($variance) }}
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($variancePct, 2) }}%
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Financing Activities -->
            <tr class="section-header">
                <td colspan="{{ 1 + $totalCols }}" class="category">CASH FLOWS FROM FINANCING ACTIVITIES</td>
            </tr>
            @foreach ($financingDescriptions as $desc)
                <tr>
                    <td class="category activity-item">{{ $desc }}</td>
                    @foreach ($pData as $index => $data)
                        @php
                            $found = 0;
                            foreach ($data['financing_activities'] as $act) {
                                if ($act['description'] === $desc) {
                                    $found = $act['amount'];
                                    break;
                                }
                            }
                        @endphp
                        <td class="amount">
                            {{ $found != 0 ? numberFormat($found) : '-' }}
                        </td>
                        @if ($index > 0)
                            @php
                                $primaryFound = 0;
                                foreach ($pData[0]['financing_activities'] as $act) {
                                    if ($act['description'] === $desc) {
                                        $primaryFound = $act['amount'];
                                        break;
                                    }
                                }
                                $variance = $primaryFound - $found;
                                $variancePct =
                                    $found != 0 ? ($variance / abs($found)) * 100 : ($primaryFound != 0 ? 100 : 0);
                            @endphp
                            @if ($showVarianceAmount)
                                <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                    {{ $variance != 0 ? numberFormat($variance) : '-' }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                    {{ $variance != 0 ? number_format($variancePct, 2) . '%' : '-' }}
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach
            <tr class="section-total">
                <td class="category">Net Cash from Financing Activities</td>
                @foreach ($pData as $index => $data)
                    <td class="amount {{ $data['net_cash_from_financing'] >= 0 ? 'positive' : 'negative' }}">
                        {{ numberFormat($data['net_cash_from_financing']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['net_cash_from_financing'];
                            $current = $data['net_cash_from_financing'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ numberFormat($variance) }}
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($variancePct, 2) }}%
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Net Movement -->
            <tr class="net-movement">
                <td class="category">Net Increase/(Decrease) in Cash</td>
                @foreach ($pData as $index => $data)
                    <td class="amount {{ $data['net_cash_movement'] >= 0 ? 'positive' : 'negative' }}">
                        {{ numberFormat($data['net_cash_movement']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['net_cash_movement'];
                            $current = $data['net_cash_movement'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ numberFormat($variance) }}
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-col {{ $variance >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($variancePct, 2) }}%
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Closing Balance -->
            <tr class="closing-balance">
                <td class="category">Closing Cash Balance</td>
                @foreach ($pData as $index => $data)
                    <td class="amount">
                        {{ numberFormat($data['closing_balance']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['closing_balance'];
                            $current = $data['closing_balance'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td class="amount" style="background-color:rgba(255,255,255,0.1);">
                                {{ numberFormat($variance) }}
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount" style="background-color:rgba(255,255,255,0.1);">
                                {{ number_format($variancePct, 2) }}%
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>
    </table>

    @if (!empty($narrative))
        <div style="margin: 20px 0 10px; padding: 14px 18px; background: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 4px;">
            <p style="font-size: 10px; font-weight: 700; color: #4338CA; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 8px;">
                Executive Summary
            </p>
            @foreach ($narrative as $sentence)
                <p style="font-size: 10px; color: #312E81; line-height: 1.5; margin: 0 0 4px;">{{ $sentence }}</p>
            @endforeach
        </div>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i:s') }}
        @if ($isMulti)
            | Landscape Layout for Multi-period Comparison
        @endif
    </div>
</body>

</html>
