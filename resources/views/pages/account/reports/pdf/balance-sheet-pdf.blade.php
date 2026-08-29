@php
    $periods = $balanceSheet['periods'];
    $colSpan = 1;
    foreach ($periods as $index => $period) {
        $colSpan++;
        if ($index > 0) {
            if ($showVarianceAmount) {
                $colSpan++;
            }
            if ($showVariancePercentage) {
                $colSpan++;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Balance Sheet - {{ $company->name ?? 'Company' }}</title>
    <style>
        body {
            /* font-family: Arial, sans-serif; */
            font-family: {{ $invoiceTemplate->font ?? 'Arial, sans-serif' }};
            font-size: {{ $invoiceTemplate->font_size ?? 10 }}px;
            color: #1a1a1a;
            margin: {{ $invoiceTemplate->top_margin ?? 10 }}px {{ $invoiceTemplate->address_padding ?? 10 }}px {{ $invoiceTemplate->bottom_margin ?? 10 }}px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0078C8;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            color: #1a1a1a;
        }

        .header .company-name {
            font-size: 12px;
            color: #666;
            margin: 3px 0;
        }

        .header .report-date {
            font-size: 10px;
            color: #666;
            margin: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            table-layout: fixed;
        }

        th {
            background-color: #f9f9f9;
            padding: 6px 4px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            border-bottom: 1px solid #e0e0e0;
        }

        th.amount {
            text-align: right;
        }

        td {
            padding: 6px 4px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 9px;
            word-wrap: break-word;
        }

        td.amount {
            text-align: right;
            font-weight: 500;
        }

        .category-header {
            background-color: #e8f4f8;
            font-weight: bold;
            font-size: 10px;
        }

        .category-header td {
            padding: 8px 4px;
            border-top: 2px solid #0078C8;
            border-bottom: 1px solid #0078C8;
        }

        .group-header {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .group-header td {
            border-top: 1px solid #e0e0e0;
            padding: 6px 4px;
        }

        .sub-account {
            padding-left: 20px !important;
            color: #555;
        }

        .category-total {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .category-total td {
            padding: 8px 4px;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }

        .grand-total {
            background-color: #0078C8;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }

        .grand-total td {
            padding: 10px 4px;
        }

        .variance-column {
            color: #666;
            font-size: 8px;
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
            border-top: 0.5px solid #e0e0e0;
            padding-top: 8px;
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
        <h1>Balance Sheet</h1>
        <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
        <div class="report-date">As of {{ $periods[0]['label'] }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Account</th>
                @foreach ($periods as $index => $period)
                    <th class="amount">{{ $period['label'] }}</th>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <th class="amount">Var</th>
                        @endif
                        @if ($showVariancePercentage)
                            <th class="amount">Var %</th>
                        @endif
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            <!-- ASSETS -->
            <tr class="category-header">
                <td colspan="{{ $colSpan }}">ASSETS</td>
            </tr>

            @foreach ($balanceSheet['assets']['groups'] as $group)
                <tr class="group-header">
                    <td>{{ $group['group']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount variance-column">
                                    {{ numberFormat($group['total']['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-column">
                                    {{ numberFormat($group['total']['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr>
                        <td class="sub-account">{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td
                                        class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                        {{ numberFormat($account['variance_' . $index]) }}
                                    </td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td
                                        class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                        {{ numberFormat($account['variance_pct_' . $index]) }}%
                                    </td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endforeach

            @foreach ($balanceSheet['assets']['accounts'] as $account)
                <tr>
                    <td>{{ $account['account']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td
                                    class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($account['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td
                                    class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($account['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <tr class="category-total">
                <td><strong>Total Assets</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount">
                        <strong>{{ numberFormat($balanceSheet['total_assets'][$key]) }}</strong>
                    </td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount variance-column">
                                <strong>{{ numberFormat($balanceSheet['total_assets']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-column">
                                <strong>{{ numberFormat($balanceSheet['total_assets']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- LIABILITIES -->
            <tr class="category-header">
                <td colspan="{{ $colSpan }}">LIABILITIES</td>
            </tr>

            @foreach ($balanceSheet['liabilities']['groups'] as $group)
                <tr class="group-header">
                    <td>{{ $group['group']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount variance-column">
                                    {{ numberFormat($group['total']['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-column">
                                    {{ numberFormat($group['total']['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr>
                        <td class="sub-account">{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td
                                        class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                        {{ numberFormat($account['variance_' . $index]) }}
                                    </td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td
                                        class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                        {{ numberFormat($account['variance_pct_' . $index]) }}%
                                    </td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endforeach

            @foreach ($balanceSheet['liabilities']['accounts'] as $account)
                <tr>
                    <td>{{ $account['account']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td
                                    class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($account['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td
                                    class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($account['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <tr class="category-total">
                <td><strong>Total Liabilities</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount">
                        <strong>{{ numberFormat($balanceSheet['total_liabilities'][$key]) }}</strong>
                    </td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount variance-column">
                                <strong>{{ numberFormat($balanceSheet['total_liabilities']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-column">
                                <strong>{{ numberFormat($balanceSheet['total_liabilities']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- EQUITY -->
            <tr class="category-header">
                <td colspan="{{ $colSpan }}">EQUITY</td>
            </tr>

            @foreach ($balanceSheet['equity']['groups'] as $group)
                <tr class="group-header">
                    <td>{{ $group['group']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount variance-column">
                                    {{ numberFormat($group['total']['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-column">
                                    {{ numberFormat($group['total']['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr>
                        <td class="sub-account">{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td
                                        class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                        {{ numberFormat($account['variance_' . $index]) }}
                                    </td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td
                                        class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                        {{ numberFormat($account['variance_pct_' . $index]) }}%
                                    </td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endforeach

            @foreach ($balanceSheet['equity']['accounts'] as $account)
                <tr>
                    <td>{{ $account['account']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td
                                    class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($account['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td
                                    class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($account['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <!-- Prior Year Retained Earnings -->
            @if (isset($balanceSheet['prior_year_retained']) && ($balanceSheet['prior_year_retained']['current'] ?? 0) != 0)
                <tr>
                    <td>Prior Year Retained Earnings</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($balanceSheet['prior_year_retained'][$key] ?? 0) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount variance-column">{{ numberFormat($balanceSheet['prior_year_retained']['variance_' . $index] ?? 0) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-column">{{ numberFormat($balanceSheet['prior_year_retained']['variance_pct_' . $index] ?? 0) }}%</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endif

            <!-- Current Year Earnings -->
            <tr>
                <td>Current Year Earnings</td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount">
                        {{ numberFormat($balanceSheet['current_year_earnings'][$key]) }}
                    </td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td
                                class="amount variance-column {{ $balanceSheet['current_year_earnings']['variance_' . $index] > 0 ? 'positive' : ($balanceSheet['current_year_earnings']['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                {{ numberFormat($balanceSheet['current_year_earnings']['variance_' . $index]) }}
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td
                                class="amount variance-column {{ $balanceSheet['current_year_earnings']['variance_pct_' . $index] > 0 ? 'positive' : ($balanceSheet['current_year_earnings']['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                {{ numberFormat($balanceSheet['current_year_earnings']['variance_pct_' . $index]) }}%
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <tr class="category-total">
                <td><strong>Total Equity</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount">
                        <strong>{{ numberFormat($balanceSheet['total_equity'][$key]) }}</strong>
                    </td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount variance-column">
                                <strong>{{ numberFormat($balanceSheet['total_equity']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-column">
                                <strong>{{ numberFormat($balanceSheet['total_equity']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- GRAND TOTAL -->
            <tr class="grand-total">
                <td><strong>TOTAL LIABILITIES & EQUITY</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount">
                        <strong>{{ numberFormat($balanceSheet['total_liabilities_equity'][$key]) }}</strong>
                    </td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($balanceSheet['total_liabilities_equity']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($balanceSheet['total_liabilities_equity']['variance_pct_' . $index]) }}%</strong>
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
    </div>
</body>

</html>
