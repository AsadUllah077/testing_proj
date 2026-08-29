@php
    $periods = $profitLoss['periods'];
    // $showVarianceAmount and $showVariancePercentage are passed from controller
    $numCols = count($periods);
    if ($showVarianceAmount) {
        $numCols += count($periods) - 1;
    }
    if ($showVariancePercentage) {
        $numCols += count($periods) - 1;
    }
    $totalCols = $numCols + 1; // +1 for Account column
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Profit & Loss - {{ $company->name ?? 'Company' }}</title>
    <style>
        @page {
            margin: {{ $invoiceTemplate->top_margin ?? '0.5' }}cm {{ $invoiceTemplate->address_padding ?? '0.5' }}cm {{ $invoiceTemplate->bottom_margin ?? '0.5' }}cm;
        }

        body {
            font-family: {{ $invoiceTemplate->font ?? "'Helvetica', 'Arial', sans-serif" }};
            font-size: {{ $invoiceTemplate->font_size ?? 8 }}pt;
            /* Reduced for landscape multi-column */
            color: #1a1a1a;
            line-height: 1.2;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2pt solid #0078C8;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18pt;
            margin: 0 0 5px 0;
            color: #1a1a1a;
        }

        .header .company-name {
            font-size: 11pt;
            color: #666;
            margin: 2px 0;
        }

        .header .report-date {
            font-size: 9pt;
            color: #666;
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }

        th {
            background-color: #f9f9f9;
            padding: 6pt 4pt;
            text-align: left;
            font-size: 7pt;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            border-bottom: 1pt solid #e0e0e0;
        }

        th.amount {
            text-align: right;
        }

        td {
            padding: 5pt 4pt;
            border-bottom: 0.5pt solid #f0f0f0;
            font-size: 8pt;
            word-wrap: break-word;
        }

        td.amount {
            text-align: right;
            font-family: 'Helvetica', sans-serif;
        }

        .category-header {
            background-color: #e8f4f8;
            font-weight: bold;
            font-size: 9pt;
        }

        .category-header td {
            padding: 8pt 4pt;
            border-top: 2pt solid #0078C8;
            border-bottom: 1.5pt solid #0078C8;
        }

        .group-header {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .group-header td {
            border-top: 1pt solid #e0e0e0;
        }

        .sub-account {
            padding-left: 20pt !important;
            color: #444;
        }

        .category-total {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .category-total td {
            padding: 8pt 4pt;
            border-top: 1.5pt solid #e0e0e0;
            border-bottom: 1.5pt solid #e0e0e0;
        }

        .profit-row {
            background-color: #e8f5e9;
            font-weight: bold;
            font-size: 10pt;
        }

        .profit-row.negative {
            background-color: #ffebee;
        }

        .profit-row td {
            padding: 10pt 4pt;
            border-top: 1.5pt solid #4caf50;
            border-bottom: 1.5pt solid #4caf50;
        }

        .profit-row.negative td {
            border-top-color: #f44336;
            border-bottom-color: #f44336;
        }

        .net-profit {
            background-color: #0078C8;
            color: white;
            font-weight: bold;
            font-size: 11pt;
        }

        .net-profit td {
            padding: 12pt 4pt;
        }

        .positive {
            color: #0f9f6e;
        }

        .negative-amount {
            color: #e02424;
        }

        .footer {
            text-align: center;
            color: #999;
            font-size: 7pt;
            margin-top: 20px;
            border-top: 0.5pt solid #e0e0e0;
            padding-top: 5px;
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
        <h1>Profit & Loss Statement</h1>
        <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
        <div class="report-date">
            {{ $periods[0]['label'] }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Account</th>
                @foreach ($periods as $index => $period)
                    <th class="amount">{{ $period['label'] }}</th>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <th class="amount">Variance</th>
                        @endif
                        @if ($showVariancePercentage)
                            <th class="amount">Var %</th>
                        @endif
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            <!-- REVENUE -->
            <tr class="category-header">
                <td colspan="{{ $totalCols }}">REVENUE</td>
            </tr>

            @foreach ($profitLoss['revenue']['groups'] as $group)
                <tr class="group-header">
                    <td>{{ $group['group']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td>
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
                                        class="amount {{ $account['variance_' . $index] < 0 ? 'negative-amount' : '' }}">
                                        {{ numberFormat($account['variance_' . $index]) }}
                                    </td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td
                                        class="amount {{ $account['variance_pct_' . $index] < 0 ? 'negative-amount' : '' }}">
                                        {{ numberFormat($account['variance_pct_' . $index]) }}%
                                    </td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endforeach

            @foreach ($profitLoss['revenue']['accounts'] as $account)
                <tr>
                    <td>{{ $account['account']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount {{ $account['variance_' . $index] < 0 ? 'negative-amount' : '' }}">
                                    {{ numberFormat($account['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td
                                    class="amount {{ $account['variance_pct_' . $index] < 0 ? 'negative-amount' : '' }}">
                                    {{ numberFormat($account['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <tr class="category-total">
                <td><strong>Total Revenue</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['total_revenue'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_revenue']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_revenue']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- COST OF GOODS SOLD -->
            @if (!empty($profitLoss['cogs']['accounts']) || !empty($profitLoss['cogs']['groups']))
                <tr class="category-header">
                    <td colspan="{{ $totalCols }}">COST OF GOODS SOLD</td>
                </tr>

                @foreach ($profitLoss['cogs']['groups'] as $group)
                    <tr class="group-header">
                        <td>{{ $group['group']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%
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
                                        <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                                    @endif
                                    @if ($showVariancePercentage)
                                        <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                                    @endif
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach

                @foreach ($profitLoss['cogs']['accounts'] as $account)
                    <tr>
                        <td>{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach

                <tr class="category-total">
                    <td><strong>Total COGS</strong></td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount"><strong>{{ numberFormat($profitLoss['total_cogs'][$key]) }}</strong></td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">
                                    <strong>{{ numberFormat($profitLoss['total_cogs']['variance_' . $index]) }}</strong>
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">
                                    <strong>{{ numberFormat($profitLoss['total_cogs']['variance_pct_' . $index]) }}%</strong>
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endif

            <!-- GROSS PROFIT -->
            <tr class="profit-row {{ $profitLoss['gross_profit']['current'] < 0 ? 'negative' : '' }}">
                <td><strong>GROSS PROFIT</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['gross_profit'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['gross_profit']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['gross_profit']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- EXPENSES -->
            <tr class="category-header">
                <td colspan="{{ $totalCols }}">EXPENSES</td>
            </tr>

            @foreach ($profitLoss['expenses']['groups'] as $group)
                <tr class="group-header">
                    <td>{{ $group['group']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td>
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
                                    <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endforeach

            @foreach ($profitLoss['expenses']['accounts'] as $account)
                <tr>
                    <td>{{ $account['account']->name }}</td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <tr class="category-total">
                <td><strong>Total Expenses</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['total_expenses'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_expenses']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_expenses']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- OPERATING PROFIT -->
            <tr class="profit-row {{ $profitLoss['operating_profit']['current'] < 0 ? 'negative' : '' }}">
                <td><strong>OPERATING PROFIT</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['operating_profit'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['operating_profit']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['operating_profit']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- OTHER INCOME -->
            @if (!empty($profitLoss['other_income']['accounts']) || !empty($profitLoss['other_income']['groups']))
                <tr class="category-header">
                    <td colspan="{{ $totalCols }}">OTHER INCOME</td>
                </tr>
                @foreach ($profitLoss['other_income']['groups'] as $group)
                    <tr class="group-header">
                        <td style="padding-left: 15px;"><strong>{{ $group['group']->name }}</strong></td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                    @foreach ($group['accounts'] as $account)
                        <tr>
                            <td style="padding-left: 30px;">{{ $account['account']->name }}</td>
                            @foreach ($periods as $index => $period)
                                @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                                <td class="amount">{{ numberFormat($account[$key]) }}</td>
                                @if ($index > 0)
                                    @if ($showVarianceAmount)
                                        <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                                    @endif
                                    @if ($showVariancePercentage)
                                        <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                                    @endif
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
                @foreach ($profitLoss['other_income']['accounts'] as $account)
                    <tr>
                        <td style="padding-left: 15px;">{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                <tr class="category-total">
                    <td><strong>Total Other Income</strong></td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount"><strong>{{ numberFormat($profitLoss['total_other_income'][$key]) }}</strong></td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount"><strong>{{ numberFormat($profitLoss['total_other_income']['variance_' . $index]) }}</strong></td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount"><strong>{{ numberFormat($profitLoss['total_other_income']['variance_pct_' . $index]) }}%</strong></td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endif

            <!-- FINANCE COSTS -->
            @if (!empty($profitLoss['finance_costs']['accounts']) || !empty($profitLoss['finance_costs']['groups']))
                <tr class="category-header"><td colspan="{{ $totalCols }}">FINANCE COSTS</td></tr>
                @foreach ($profitLoss['finance_costs']['groups'] ?? [] as $group)
                    <tr class="group-header"><td style="padding-left: 15px;"><strong>{{ $group['group']->name }}</strong></td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount) <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td> @endif
                                @if ($showVariancePercentage) <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td> @endif
                            @endif
                        @endforeach
                    </tr>
                    @foreach ($group['accounts'] as $account)
                        <tr><td style="padding-left: 30px;">{{ $account['account']->name }}</td>
                            @foreach ($periods as $index => $period)
                                @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                                <td class="amount">{{ numberFormat($account[$key]) }}</td>
                                @if ($index > 0)
                                    @if ($showVarianceAmount) <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td> @endif
                                    @if ($showVariancePercentage) <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td> @endif
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
                @foreach ($profitLoss['finance_costs']['accounts'] ?? [] as $account)
                    <tr><td style="padding-left: 15px;">{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount) <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td> @endif
                                @if ($showVariancePercentage) <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td> @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                <tr class="category-total"><td><strong>Total Finance Costs</strong></td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount"><strong>{{ numberFormat($profitLoss['total_finance_costs'][$key]) }}</strong></td>
                        @if ($index > 0)
                            @if ($showVarianceAmount) <td class="amount"><strong>{{ numberFormat($profitLoss['total_finance_costs']['variance_' . $index]) }}</strong></td> @endif
                            @if ($showVariancePercentage) <td class="amount"><strong>{{ numberFormat($profitLoss['total_finance_costs']['variance_pct_' . $index]) }}%</strong></td> @endif
                        @endif
                    @endforeach
                </tr>
            @endif

            <!-- PROFIT BEFORE TAX -->
            <tr class="profit-row {{ ($profitLoss['profit_before_tax']['current'] ?? 0) < 0 ? 'negative' : '' }}">
                <td><strong>PROFIT BEFORE TAX</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['profit_before_tax'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount) <td class="amount"><strong>{{ numberFormat($profitLoss['profit_before_tax']['variance_' . $index]) }}</strong></td> @endif
                        @if ($showVariancePercentage) <td class="amount"><strong>{{ numberFormat($profitLoss['profit_before_tax']['variance_pct_' . $index]) }}%</strong></td> @endif
                    @endif
                @endforeach
            </tr>

            <!-- TAX EXPENSE -->
            @if (!empty($profitLoss['tax_expenses']['accounts']) || !empty($profitLoss['tax_expenses']['groups']))
                <tr class="category-header"><td colspan="{{ $totalCols }}">TAX EXPENSE</td></tr>
                @foreach ($profitLoss['tax_expenses']['accounts'] ?? [] as $account)
                    <tr><td style="padding-left: 15px;">{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount) <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td> @endif
                                @if ($showVariancePercentage) <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td> @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endif

            <!-- NET PROFIT -->
            <tr class="net-profit">
                <td><strong>NET PROFIT</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['net_profit'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['net_profit']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['net_profit']['variance_pct_' . $index]) }}%</strong>
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
