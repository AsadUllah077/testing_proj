@extends('layouts.auth')
@section('title', 'Business Performance')
@section('content')

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/accounting-kpi.css') }}">
    @endpush

    <div>

        {{-- ════ STICKY HEADER ════ --}}
        <div class="kpi-header">
            <div class="kpi-header-inner">
                <div>
                    <h1>Business Performance &mdash; KPI Dashboard</h1>
                    <div class="subtitle">
                        {{ $kpis['date_range']['formatted_start'] }} &ndash; {{ $kpis['date_range']['formatted_end'] }}
                        &nbsp;&middot;&nbsp;
                        @php
                            $hs = 0;
                            if ($kpis['current_ratio'] >= 1.5) {
                                $hs += 20;
                            } elseif ($kpis['current_ratio'] >= 1) {
                                $hs += 12;
                            }
                            if ($kpis['gross_profit_margin'] >= 40) {
                                $hs += 20;
                            } elseif ($kpis['gross_profit_margin'] >= 20) {
                                $hs += 12;
                            }
                            if ($kpis['net_profit'] > 0) {
                                $hs += 20;
                            }
                            if ($kpis['financial_position']['debt_ratio'] < 50) {
                                $hs += 20;
                            } elseif ($kpis['financial_position']['debt_ratio'] < 70) {
                                $hs += 12;
                            }
                            if ($kpis['roa'] >= 5) {
                                $hs += 20;
                            } elseif ($kpis['roa'] >= 0) {
                                $hs += 10;
                            }
                            $hc = $hs >= 80 ? 'excellent' : ($hs >= 60 ? 'good' : ($hs >= 40 ? 'warning' : 'poor'));
                            $hl =
                                $hs >= 80
                                    ? 'Excellent'
                                    : ($hs >= 60
                                        ? 'Good'
                                        : ($hs >= 40
                                            ? 'Fair'
                                            : 'Needs Attention'));
                        @endphp
                        <span class="health-badge {{ $hc }}">
                            <span class="health-dot"></span> {{ $hl }} &bull; {{ $hs }}/100
                        </span>
                    </div>
                </div>
                <div class="period-select-wrap">
                    <form method="GET" id="pf">
                        <select name="per_date" id="kpi_per_date" onchange="toggleKpiCustomDates(this)" title="Period" style="display:none;">
                            <option value="">Select Period</option>
                            @foreach (config('default.per_date') as $k => $v)
                                <option value="{{ $v }}" {{ request('per_date') == $v ? 'selected' : '' }}>
                                    {{ $k }}</option>
                            @endforeach
                        </select>

                        @php
                            $selectedLabel = 'Select Period';
                            foreach (config('default.per_date') as $k => $v) {
                                if (request('per_date') == $v) {
                                    $selectedLabel = $k;
                                    break;
                                }
                            }
                        @endphp
                        <div class="stripe-dropdown" id="stripeDropdown">
                            <div class="stripe-dropdown-toggle" id="stripeToggle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" style="color:var(--p)">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span id="stripeLabel">{{ $selectedLabel }}</span>
                                <svg class="dd-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="stripe-dropdown-menu">
                                <div class="stripe-dropdown-item {{ !request('per_date') ? 'active' : '' }}"
                                    data-value="">Select Period</div>
                                @foreach (config('default.per_date') as $k => $v)
                                    <div class="stripe-dropdown-item {{ request('per_date') == $v ? 'active' : '' }}"
                                        data-value="{{ $v }}">{{ $k }}</div>
                                @endforeach
                            </div>
                        </div>
                        <div id="custom-dates-kpi" class="{{ request('per_date') == 'CUSTOM' ? 'flex' : 'hidden' }} items-center gap-2 mt-1">
                            <input type="text" name="start_date" value="{{ request('start_date') }}" placeholder="dd/mm/yyyy"
                                class="date bg-white border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" style="color:#333;">
                            <span class="text-gray-500 font-bold text-xs">to</span>
                            <input type="text" name="end_date" value="{{ request('end_date') }}" placeholder="dd/mm/yyyy"
                                class="date bg-white border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" style="color:#333;">
                            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700 transition-colors">
                                Go
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ════ SUMMARY BANNER ════ --}}
        <div class="summary-banner mt-4">
            @php
                function fv($v)
                {
                    if (abs($v) >= 1e6) {
                        return number_format($v / 1e6, 1) . 'M';
                    }
                    if (abs($v) >= 1e3) {
                        return number_format($v / 1e3, 1) . 'K';
                    }
                    return number_format($v, 0);
                }
                function fc($c, $p, $inv = false)
                {
                    if ($p == 0) {
                        return ['p' => 0, 'cl' => 'nt'];
                    }
                    $r = (($c - $p) / abs($p)) * 100;
                    $up = $r >= 0;
                    $g = $inv ? !$up : $up;
                    return ['p' => $r, 'cl' => $g ? 'up' : 'dn'];
                }
                $ic = fc($kpis['total_income'], $kpis['total_income_previous']);
                $ec = fc($kpis['total_expenses'], $kpis['total_expenses_previous'], true);
                $nc = fc($kpis['net_profit'], $kpis['net_profit_previous']);
                $gc = fc($kpis['gross_profit'], $kpis['gross_profit_previous']);
                $wc = fc($kpis['working_capital'], $kpis['working_capital_previous']);
            @endphp
            <div class="s-item">
                <div class="s-label">Revenue</div>
                <div class="s-value">{{ currency()['symbol'] ?? 'RS' }} {{ fv($kpis['total_income']) }}</div>
                <div class="s-change {{ $ic['cl'] }}">{{ $ic['p'] >= 0 ? '▲' : '▼' }}
                    {{ number_format(abs($ic['p']), 1) }}%
                </div>
            </div>
            <div class="s-item">
                <div class="s-label">Gross Profit</div>
                <div class="s-value">{{ currency()['symbol'] ?? 'RS' }} {{ fv($kpis['gross_profit']) }}</div>
                <div class="s-change {{ $gc['cl'] }}">{{ number_format($kpis['gross_profit_margin'], 1) }}% margin
                </div>
            </div>
            <div class="s-item">
                <div class="s-label">Net Profit</div>
                <div class="s-value">{{ currency()['symbol'] ?? 'RS' }} {{ fv($kpis['net_profit']) }}</div>
                <div class="s-change {{ $nc['cl'] }}">{{ number_format($kpis['net_profit_margin'], 1) }}% margin</div>
            </div>
            <div class="s-item">
                <div class="s-label">EBITDA</div>
                <div class="s-value">{{ currency()['symbol'] ?? 'RS' }} {{ fv($kpis['ebitda']) }}</div>
                <div class="s-change nt">{{ number_format($kpis['ebitda_margin'], 1) }}% margin</div>
            </div>
            <div class="s-item">
                <div class="s-label">Total Expenses</div>
                <div class="s-value">{{ currency()['symbol'] ?? 'RS' }} {{ fv($kpis['total_expenses']) }}</div>
                <div class="s-change {{ $ec['cl'] }}">{{ $ec['p'] >= 0 ? '▲' : '▼' }}
                    {{ number_format(abs($ec['p']), 1) }}%
                </div>
            </div>
            <div class="s-item">
                <div class="s-label">Cash &amp; Bank</div>
                <div class="s-value">{{ currency()['symbol'] ?? 'RS' }} {{ fv($kpis['cash_balance']) }}</div>
                <div class="s-change nt">End of period</div>
            </div>
            <div class="s-item">
                <div class="s-label">Working Capital</div>
                <div class="s-value">{{ fv($kpis['working_capital']) }}</div>
                <div class="s-change {{ $wc['cl'] }}">{{ $wc['p'] >= 0 ? '▲' : '▼' }}
                    {{ number_format(abs($wc['p']), 1) }}%
                </div>
            </div>
            <div class="s-item">
                <div class="s-label">Current Ratio</div>
                <div class="s-value">{{ number_format($kpis['current_ratio'], 2) }}</div>
                <div
                    class="s-change {{ $kpis['current_ratio'] >= 1.5 ? 'up' : ($kpis['current_ratio'] >= 1 ? 'nt' : 'dn') }}">
                    {{ $kpis['current_ratio'] >= 1.5 ? 'Healthy' : ($kpis['current_ratio'] >= 1 ? 'Adequate' : 'Low') }}
                </div>
            </div>
        </div>

        <div class="kpi-tabs">
            <div class="kpi-tab active" data-tab="overview">Overview</div>
            <div class="kpi-tab" data-tab="profitability">Profitability</div>
            <div class="kpi-tab" data-tab="cashflow">Cash &amp; Liquidity</div>
            <div class="kpi-tab" data-tab="balance">Balance Sheet</div>
            <div class="kpi-tab" data-tab="efficiency">Efficiency &amp; Turnover</div>
            <div class="kpi-tab" data-tab="operations">Operations &amp; Tax</div>
            <div class="kpi-tab" data-tab="dupont">DuPont Analysis</div>
        </div>

        {{-- ════ MAIN CONTENT ════ --}}
        <div class="p-5 space-y-6">

            {{-- ══ TAB: OVERVIEW ══════════════════════════════════════════ --}}
            <div class="tab-pane active" id="tab-overview">

                <div class="sect-title">Key Performance Indicators</div>

                @php
                    /* KPI card helper — outputs raw HTML string */
                    function kc($title, $val, $prev, $id, $fmt = 'num', $inv = false, $accentColor = 'var(--p)')
                    {
                        $disp = $fmt === 'pct' ? number_format($val, 1) . '%' : number_format($val, 0);
                        $chg = $prev != 0 ? (($val - $prev) / abs($prev)) * 100 : 0;
                        $up = $chg >= 0;
                        $good = $inv ? !$up : $up;
                        $pc = $good ? 'good' : 'bad';
                        $ar = $up ? '▲' : '▼';
                        return "<div class='kpi-card' style='padding-bottom:56px;'>
            <div class='card-accentbar' style='background:{$accentColor}'></div>
            <div class='card-label'>{$title}</div>
            <div class='card-value'>{$disp}</div>
            <div style='display:flex;align-items:center;gap:4px;'>
                <span class='vpill {$pc}'>{$ar} " .
                            number_format(abs($chg), 1) .
                            "%</span>
                <span class='vs-label'>vs prior</span>
            </div>
            <div class='spark-wrap'><canvas id='{$id}'></canvas></div>
        </div>";
                    }
                @endphp

                {{-- Row 1: P&L core --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    {!! kc('Total Revenue', $kpis['total_income'], $kpis['total_income_previous'], 'sp-rev') !!}
                    {!! kc('Cost of Goods Sold', $kpis['cogs'], $kpis['cogs_previous'], 'sp-cogs', 'num', true, 'var(--warning)') !!}
                    {!! kc(
                'Operating Expenses',
                $kpis['operating_expenses'],
                $kpis['operating_expenses_previous'],
                'sp-opex',
                'num',
                true,
                'var(--danger)',
            ) !!}
                    {!! kc(
                        'Net Profit',
                        $kpis['net_profit'],
                        $kpis['net_profit_previous'],
                        'sp-net',
                        'num',
                        false,
                        'var(--success)',
                    ) !!}
                </div>
                {{-- Row 2: Margins --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    {!! kc(
                        'Gross Profit %',
                        $kpis['gross_profit_margin'],
                        $kpis['gross_profit_margin_previous'],
                        'sp-gpm',
                        'pct',
                        false,
                        'var(--teal)',
                    ) !!}
                    {!! kc('Operating Margin', $kpis['operating_margin'], 0, 'sp-opm', 'pct', false, 'var(--p)') !!}
                    {!! kc(
                        'Net Profit Margin',
                        $kpis['net_profit_margin'],
                        $kpis['net_profit_margin_previous'],
                        'sp-npm',
                        'pct',
                        false,
                        'var(--success)',
                    ) !!}
                    {!! kc('EBITDA Margin', $kpis['ebitda_margin'], 0, 'sp-ebitdam', 'pct', false, 'var(--purple)') !!}
                </div>
                {{-- Row 3: AR / AP / Working Capital / Balance --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    {!! kc('Accounts Receivable', $kpis['accounts_receivable'], $kpis['accounts_receivable_previous'], 'sp-ar') !!}
                    {!! kc(
                        'Accounts Payable',
                        $kpis['accounts_payable'],
                        $kpis['accounts_payable_previous'],
                        'sp-ap',
                        'num',
                        true,
                        'var(--warning)',
                    ) !!}
                    {!! kc(
                        'Working Capital',
                        $kpis['working_capital'],
                        $kpis['working_capital_previous'],
                        'sp-wc',
                        'num',
                        false,
                        'var(--teal)',
                    ) !!}
                    {!! kc('Account Balance', $kpis['account_balance'], 0, 'sp-bal') !!}
                </div>
                {{-- Row 4: Returns --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                    {!! kc('Return on Assets', $kpis['roa'], 0, 'sp-roa', 'pct', false, 'var(--p)') !!}
                    {!! kc('Return on Equity', $kpis['roe'], 0, 'sp-roe', 'pct', false, 'var(--teal)') !!}
                    {!! kc('EBIT', $kpis['ebit'], 0, 'sp-ebit', 'num', false, 'var(--navy)') !!}
                    {!! kc('EBITDA', $kpis['ebitda'], 0, 'sp-ebitda', 'num', false, 'var(--purple)') !!}
                </div>

                {{-- Main trend chart --}}
                <div class="chart-card">
                    <div class="cc-title">Revenue vs Expenses &mdash; 12-Month Trend</div>
                    <div class="cc-sub">Monthly comparison with net profit overlay</div>
                    <div style="height:270px;"><canvas id="mainTrendChart"></canvas></div>
                </div>

            </div>{{-- /overview --}}

            {{-- ══ TAB: PROFITABILITY ══════════════════════════════════════ --}}
            <div class="tab-pane" id="tab-profitability">

                <div class="sect-title">Profitability Analysis</div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
                    <div class="chart-card lg:col-span-2">
                        <div class="cc-title">Profit &amp; Loss Breakdown</div>
                        <div class="cc-sub">Revenue → COGS → Gross Profit → Expenses → Net Profit</div>
                        <div style="height:300px;"><canvas id="plBreakdown"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Income Statement Summary</div>
                        <div class="cc-sub">Current period</div>
                        <div class="space-y-1 mt-1">
                            <div class="mrow"><span class="mn">Revenue</span><span
                                    class="mv">{{ number_format($kpis['total_income'], 0) }}</span></div>
                            <div class="mrow"><span class="mn text-yellow-600">(-) Cost of Sales</span><span
                                    class="mv text-yellow-600">({{ number_format($kpis['cogs'], 0) }})</span></div>
                            <div class="mrow" style="background:#ecfdf5;padding:6px 8px;border-radius:6px;">
                                <span class="mn font-semibold text-emerald-700">= Gross Profit</span>
                                <div><span
                                        class="mv text-emerald-700">{{ number_format($kpis['gross_profit'], 0) }}</span>
                                    <span
                                        class="text-xs text-emerald-600">({{ number_format($kpis['gross_profit_margin'], 1) }}%)</span>
                                </div>
                            </div>
                            <div class="mrow"><span class="mn text-red-500">(-) Operating Expenses</span><span
                                    class="mv text-red-500">({{ number_format($kpis['operating_expenses'], 0) }})</span>
                            </div>
                            <div class="mrow"><span class="mn text-purple-500">(-) Finance Costs</span><span
                                    class="mv text-purple-500">({{ number_format($kpis['finance_costs'], 0) }})</span>
                            </div>
                            <div class="mrow"><span class="mn text-orange-500">(-) Tax Expenses</span><span
                                    class="mv text-orange-500">({{ number_format($kpis['tax_expenses_kpi'], 0) }})</span>
                            </div>
                            <div class="mrow"
                                style="background:#{{ $kpis['ebit'] >= 0 ? 'ecfdf5' : 'fff1f2' }};padding:6px 8px;border-radius:6px;">
                                <span
                                    class="mn font-semibold {{ $kpis['ebit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">=
                                    EBIT</span>
                                <span
                                    class="mv {{ $kpis['ebit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ number_format($kpis['ebit'], 0) }}</span>
                            </div>
                            <div class="mrow"><span class="mn">(+) D&amp;A (est.)</span><span
                                    class="mv">{{ number_format($kpis['ebitda'] - $kpis['ebit'], 0) }}</span></div>
                            <div class="mrow" style="background:#f0f9ff;padding:6px 8px;border-radius:6px;">
                                <span class="mn font-semibold text-blue-700">= EBITDA</span>
                                <div><span class="mv text-blue-700">{{ number_format($kpis['ebitda'], 0) }}</span> <span
                                        class="text-xs text-blue-500">({{ number_format($kpis['ebitda_margin'], 1) }}%)</span>
                                </div>
                            </div>
                            <div class="mrow"
                                style="background:#{{ $kpis['net_profit'] >= 0 ? 'ecfdf5' : 'fff1f2' }};padding:6px 8px;border-radius:6px;margin-top:4px;">
                                <span
                                    class="mn font-bold {{ $kpis['net_profit'] >= 0 ? 'text-emerald-800' : 'text-red-700' }}">=
                                    Net Profit</span>
                                <div><span
                                        class="mv {{ $kpis['net_profit'] >= 0 ? 'text-emerald-800' : 'text-red-700' }}">{{ number_format($kpis['net_profit'], 0) }}</span>
                                    <span
                                        class="text-xs ml-1 {{ $kpis['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">({{ number_format($kpis['net_profit_margin'], 1) }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Margin ratios grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--p)"></div>
                        <div class="card-label">COGS Ratio</div>
                        <div class="card-value">{{ number_format($kpis['cogs_ratio'], 1) }}%</div>
                        <div class="text-xs text-gray-400">COGS / Revenue</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--danger)"></div>
                        <div class="card-label">Expense Ratio</div>
                        <div class="card-value">{{ number_format($kpis['expense_ratio'], 1) }}%</div>
                        <div class="text-xs text-gray-400">Opex / Revenue</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--teal)"></div>
                        <div class="card-label">Operating Margin</div>
                        <div
                            class="card-value {{ $kpis['operating_margin'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['operating_margin'], 1) }}%</div>
                        <div class="text-xs text-gray-400">EBIT / Revenue</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--purple)"></div>
                        <div class="card-label">Revenue Growth</div>
                        <div class="card-value {{ $kpis['revenue_growth'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['revenue_growth'], 1) }}%</div>
                        <div class="text-xs text-gray-400">vs prior period</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="chart-card">
                        <div class="cc-title">Gross &amp; Net Margin Trend</div>
                        <div class="cc-sub">Monthly margin % — last 12 months</div>
                        <div style="height:220px;"><canvas id="marginTrendChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Monthly Net Profit</div>
                        <div class="cc-sub">Profit / (Loss) per month</div>
                        <div style="height:220px;"><canvas id="netProfitBarChart"></canvas></div>
                    </div>
                </div>

            </div>{{-- /profitability --}}

            {{-- ══ TAB: CASH & LIQUIDITY ══════════════════════════════════ --}}
            <div class="tab-pane" id="tab-cashflow">

                <div class="sect-title">Cash &amp; Liquidity</div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                    <div class="kpi-card">
                        <div class="card-accentbar"></div>
                        <div class="card-label">Cash &amp; Bank</div>
                        <div class="card-value">{{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['cash_balance'], 0) }}</div>
                        <div class="text-xs text-gray-400 mt-1">End-of-period</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--teal)"></div>
                        <div class="card-label">Working Capital</div>
                        <div class="card-value {{ $kpis['working_capital'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['working_capital'], 0) }}</div>
                        <div class="text-xs text-gray-400 mt-1">CA &minus; CL</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--success)"></div>
                        <div class="card-label">Current Ratio</div>
                        <div
                            class="card-value {{ $kpis['current_ratio'] >= 1.5 ? 'text-emerald-700' : ($kpis['current_ratio'] >= 1 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($kpis['current_ratio'], 2) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Target: 1.5 – 2.5</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--purple)"></div>
                        <div class="card-label">Quick Ratio</div>
                        <div class="card-value {{ $kpis['quick_ratio'] >= 1 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['quick_ratio'], 2) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Target: &ge; 1.0</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
                    <div class="chart-card">
                        <div class="cc-title">Current Ratio Gauge</div>
                        <div class="cc-sub">Liquidity health</div>
                        <div class="gauge-wrap" style="height:170px;"><canvas id="gauCR"></canvas>
                            <div class="gauge-label">
                                <div class="g-val">{{ number_format($kpis['current_ratio'], 2) }}</div>
                                <div class="g-sub">
                                    {{ $kpis['current_ratio'] >= 1.5 ? 'Healthy' : ($kpis['current_ratio'] >= 1 ? 'Adequate' : 'Critical') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>0</span><span>1.5–2.5</span><span>3+</span>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Quick Ratio Gauge</div>
                        <div class="cc-sub">Acid-test ratio</div>
                        <div class="gauge-wrap" style="height:170px;"><canvas id="gauQR"></canvas>
                            <div class="gauge-label">
                                <div class="g-val">{{ number_format($kpis['quick_ratio'], 2) }}</div>
                                <div class="g-sub">{{ $kpis['quick_ratio'] >= 1 ? 'Good' : 'Below Target' }}</div>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1"><span>0</span><span>Target &ge;
                                1.0</span><span>2+</span></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">AR vs AP</div>
                        <div class="cc-sub">Receivables vs payables</div>
                        <div style="height:170px;"><canvas id="arApChart"></canvas></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="chart-card">
                        <div class="cc-title">Bank Balance History</div>
                        <div class="cc-sub">Rolling cumulative balance — 12 months</div>
                        <div style="height:250px;"><canvas id="balanceHistoryChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Monthly Income &amp; Expense Flow</div>
                        <div class="cc-sub">Cash inflows vs outflows</div>
                        <div style="height:250px;"><canvas id="cashFlowCompositionChart"></canvas></div>
                    </div>
                </div>

            </div>{{-- /cashflow --}}

            {{-- ══ TAB: BALANCE SHEET ═════════════════════════════════════ --}}
            <div class="tab-pane" id="tab-balance">

                <div class="sect-title">Balance Sheet Snapshot</div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
                    <div class="chart-card">
                        <div class="cc-title">Assets</div>
                        <div class="text-3xl font-black text-gray-900 my-2">
                            {{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['financial_position']['total_assets'], 0) }}</div>
                        <div class="mrow"><span class="mn">Current Assets</span><span
                                class="mv text-blue-600">{{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['financial_position']['current_assets'], 0) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Non-Current Assets</span><span
                                class="mv">{{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['financial_position']['total_assets'] - $kpis['financial_position']['current_assets'], 0) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Accounts Receivable</span><span
                                class="mv">{{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['accounts_receivable'], 0) }}</span></div>
                        <div style="height:90px;margin-top:10px;"><canvas id="assetsDonut"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Liabilities</div>
                        <div class="text-3xl font-black text-red-600 my-2">
                            {{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['financial_position']['total_liabilities'], 0) }}</div>
                        <div class="mrow"><span class="mn">Current Liabilities</span><span
                                class="mv text-red-500">{{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['financial_position']['current_liabilities'], 0) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Non-Current Liabilities</span><span
                                class="mv">{{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['financial_position']['total_liabilities'] - $kpis['financial_position']['current_liabilities'], 0) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Accounts Payable</span><span
                                class="mv">{{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['accounts_payable'], 0) }}</span></div>
                        <div class="mrow"><span class="mn">Debt Ratio</span>
                            <span
                                class="mv {{ $kpis['financial_position']['debt_ratio'] > 70 ? 'text-red-600' : ($kpis['financial_position']['debt_ratio'] > 50 ? 'text-yellow-600' : 'text-emerald-700') }}">
                                {{ number_format($kpis['financial_position']['debt_ratio'], 1) }}%
                            </span>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Equity</div>
                        <div
                            class="text-3xl font-black {{ $kpis['financial_position']['total_equity'] >= 0 ? 'text-emerald-700' : 'text-red-600' }} my-2">
                            {{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['financial_position']['total_equity'], 0) }}</div>
                        <div class="mrow"><span class="mn">Debt to Equity</span><span
                                class="mv">{{ currency()['symbol'] ?? 'RS' }} {{ number_format($kpis['financial_position']['debt_to_equity'], 2) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Equity Multiplier</span><span
                                class="mv">{{ number_format($kpis['equity_multiplier'], 2) }}x</span></div>
                        <div class="mrow"><span class="mn">Return on Equity</span><span
                                class="mv {{ $kpis['roe'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ number_format($kpis['roe'], 1) }}%</span>
                        </div>
                        <div class="mrow"><span class="mn">Return on Assets</span><span
                                class="mv {{ $kpis['roa'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ number_format($kpis['roa'], 1) }}%</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="chart-card">
                        <div class="cc-title">Capital Structure</div>
                        <div class="cc-sub">Equity vs Liabilities composition</div>
                        <div style="height:250px;"><canvas id="capitalChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Debt Ratio Gauge</div>
                        <div class="cc-sub">Financial leverage indicator</div>
                        <div class="gauge-wrap" style="height:180px;"><canvas id="gauDebt"></canvas>
                            <div class="gauge-label">
                                <div class="g-val">{{ number_format($kpis['financial_position']['debt_ratio'], 1) }}%
                                </div>
                                <div class="g-sub">
                                    {{ $kpis['financial_position']['debt_ratio'] > 70 ? 'High Risk' : ($kpis['financial_position']['debt_ratio'] > 50 ? 'Moderate' : 'Conservative') }}
                                </div>
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <div class="mrow">
                                <span class="mn">Total Assets</span>
                                <div class="mbar-wrap">
                                    <div class="mbar" style="width:100%"></div>
                                </div>
                                <span
                                    class="mv">{{ number_format($kpis['financial_position']['total_assets'], 0) }}</span>
                            </div>
                            <div class="mrow">
                                <span class="mn">Liabilities</span>
                                <div class="mbar-wrap">
                                    <div class="mbar bg-red-400"
                                        style="width:{{ min($kpis['financial_position']['debt_ratio'], 100) }}%"></div>
                                </div>
                                <span
                                    class="mv">{{ number_format($kpis['financial_position']['total_liabilities'], 0) }}</span>
                            </div>
                            <div class="mrow">
                                <span class="mn">Equity</span>
                                <div class="mbar-wrap">
                                    <div class="mbar bg-emerald-400"
                                        style="width:{{ max(0, min(100 - $kpis['financial_position']['debt_ratio'], 100)) }}%">
                                    </div>
                                </div>
                                <span
                                    class="mv">{{ number_format($kpis['financial_position']['total_equity'], 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Altman Z-Score & Equity Ratio --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
                    <div class="chart-card">
                        <div class="cc-title">Altman Z-Score</div>
                        <div class="cc-sub">Bankruptcy / financial distress predictor</div>
                        <div class="gauge-wrap" style="height:170px;"><canvas id="gauZScore"></canvas>
                            <div class="gauge-label">
                                <div class="g-val">{{ number_format($kpis['altman_z_score'], 2) }}</div>
                                <div class="g-sub">
                                    {{ $kpis['altman_z_score'] >= 2.99 ? 'Safe Zone' : ($kpis['altman_z_score'] >= 1.81 ? 'Grey Zone' : 'Distress Zone') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>0 (Distress)</span><span>1.81</span><span>2.99+</span>
                        </div>
                        <div class="mt-3 space-y-1">
                            <div class="mrow"><span class="mn">Working Capital / Assets</span><span
                                    class="mv">{{ number_format($kpis['financial_position']['total_assets'] > 0 ? $kpis['working_capital'] / $kpis['financial_position']['total_assets'] : 0, 3) }}</span>
                            </div>
                            <div class="mrow"><span class="mn">Retained Earnings / Assets</span><span
                                    class="mv">{{ number_format($kpis['financial_position']['total_assets'] > 0 ? $kpis['retained_earnings'] / $kpis['financial_position']['total_assets'] : 0, 3) }}</span>
                            </div>
                            <div class="mrow"><span class="mn">EBIT / Assets</span><span
                                    class="mv">{{ number_format($kpis['financial_position']['total_assets'] > 0 ? $kpis['ebit'] / $kpis['financial_position']['total_assets'] : 0, 3) }}</span>
                            </div>
                            <div class="mrow"><span class="mn">Equity / Liabilities</span><span
                                    class="mv">{{ number_format($kpis['financial_position']['total_liabilities'] > 0 ? $kpis['financial_position']['total_equity'] / $kpis['financial_position']['total_liabilities'] : 0, 3) }}</span>
                            </div>
                            <div class="mrow"><span class="mn">Revenue / Assets</span><span
                                    class="mv">{{ number_format($kpis['efficiency_ratios']['asset_turnover'], 3) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Financial Strength Metrics</div>
                        <div class="cc-sub">Key balance sheet ratios</div>
                        <div class="space-y-1 mt-2">
                            <div class="mrow"><span class="mn">Equity Ratio</span>
                                <span
                                    class="vpill {{ $kpis['equity_ratio'] >= 50 ? 'good' : ($kpis['equity_ratio'] >= 30 ? 'nt' : 'bad') }}">{{ number_format($kpis['equity_ratio'], 1) }}%</span>
                            </div>
                            <div class="mrow"><span class="mn">Debt Ratio</span>
                                <span
                                    class="vpill {{ $kpis['financial_position']['debt_ratio'] < 50 ? 'good' : ($kpis['financial_position']['debt_ratio'] < 70 ? 'nt' : 'bad') }}">{{ number_format($kpis['financial_position']['debt_ratio'], 1) }}%</span>
                            </div>
                            <div class="mrow"><span class="mn">Debt to Equity</span>
                                <span
                                    class="vpill {{ $kpis['financial_position']['debt_to_equity'] < 1 ? 'good' : ($kpis['financial_position']['debt_to_equity'] < 2 ? 'nt' : 'bad') }}">{{ number_format($kpis['financial_position']['debt_to_equity'], 2) }}x</span>
                            </div>
                            <div class="mrow"><span class="mn">Current Ratio</span>
                                <span
                                    class="vpill {{ $kpis['current_ratio'] >= 1.5 ? 'good' : ($kpis['current_ratio'] >= 1 ? 'nt' : 'bad') }}">{{ number_format($kpis['current_ratio'], 2) }}</span>
                            </div>
                            <div class="mrow"><span class="mn">Quick Ratio</span>
                                <span
                                    class="vpill {{ $kpis['quick_ratio'] >= 1 ? 'good' : ($kpis['quick_ratio'] >= 0.5 ? 'nt' : 'bad') }}">{{ number_format($kpis['quick_ratio'], 2) }}</span>
                            </div>
                            <div class="mrow"><span class="mn">Equity Multiplier</span>
                                <span class="vpill nt">{{ number_format($kpis['equity_multiplier'], 2) }}x</span>
                            </div>
                            <div class="mrow"><span class="mn">Sustainable Growth Rate</span>
                                <span
                                    class="vpill {{ $kpis['sustainable_growth_rate'] >= 5 ? 'good' : ($kpis['sustainable_growth_rate'] >= 0 ? 'nt' : 'bad') }}">{{ number_format($kpis['sustainable_growth_rate'], 1) }}%</span>
                            </div>
                            <div class="mrow"><span class="mn">Interest Coverage</span>
                                <span
                                    class="vpill {{ $kpis['interest_coverage'] >= 3 ? 'good' : ($kpis['interest_coverage'] > 0 ? 'nt' : 'bad') }}">{{ $kpis['interest_coverage'] > 0 ? number_format($kpis['interest_coverage'], 1) . 'x' : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /balance --}}

            {{-- ══ TAB: EFFICIENCY & TURNOVER ═════════════════════════════ --}}
            <div class="tab-pane" id="tab-efficiency">

                <div class="sect-title">Operational Efficiency &amp; Turnover Ratios</div>

                {{-- Days metrics --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--p)"></div>
                        <div class="card-label">AR Days (DSO)</div>
                        <div class="card-value">{{ number_format($kpis['efficiency_ratios']['ar_days'], 1) }}</div>
                        <div class="text-xs text-gray-400 mb-2">Days to collect</div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $kpis['efficiency_ratios']['ar_days'] <= 30 ? 'bg-emerald-500' : ($kpis['efficiency_ratios']['ar_days'] <= 60 ? 'bg-yellow-400' : 'bg-red-500') }}"
                                style="width:{{ min(($kpis['efficiency_ratios']['ar_days'] / 90) * 100, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--warning)"></div>
                        <div class="card-label">AP Days (DPO)</div>
                        <div class="card-value">{{ number_format($kpis['efficiency_ratios']['ap_days'], 1) }}</div>
                        <div class="text-xs text-gray-400 mb-2">Days to pay</div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $kpis['efficiency_ratios']['ap_days'] >= 30 ? 'bg-emerald-500' : 'bg-yellow-400' }}"
                                style="width:{{ min(($kpis['efficiency_ratios']['ap_days'] / 90) * 100, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--purple)"></div>
                        <div class="card-label">Inventory Days (DIO)</div>
                        <div class="card-value">{{ number_format($kpis['efficiency_ratios']['inventory_days'], 1) }}
                        </div>
                        <div class="text-xs text-gray-400 mb-2">Days held</div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $kpis['efficiency_ratios']['inventory_days'] <= 30 ? 'bg-emerald-500' : ($kpis['efficiency_ratios']['inventory_days'] <= 60 ? 'bg-yellow-400' : 'bg-red-500') }}"
                                style="width:{{ min(($kpis['efficiency_ratios']['inventory_days'] / 90) * 100, 100) }}%">
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar"
                            style="background:var(--{{ $kpis['efficiency_ratios']['ccc'] <= 30 ? 'success' : 'danger' }})">
                        </div>
                        <div class="card-label">Cash Conv. Cycle</div>
                        <div
                            class="card-value {{ $kpis['efficiency_ratios']['ccc'] <= 30 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['efficiency_ratios']['ccc'], 1) }}d
                        </div>
                        <div class="text-xs text-gray-400">DSO + DIO &minus; DPO</div>
                        <div class="info-pill mt-2">
                            {{ $kpis['efficiency_ratios']['ccc'] <= 30 ? 'Efficient' : 'Needs Improvement' }}</div>
                    </div>
                </div>

                {{-- Turnover ratios --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                    <div class="kpi-card">
                        <div class="card-accentbar"></div>
                        <div class="card-label">AR Turnover</div>
                        <div class="card-value">{{ number_format($kpis['efficiency_ratios']['ar_turnover'], 1) }}x</div>
                        <div class="text-xs text-gray-400">Revenue / AR</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--warning)"></div>
                        <div class="card-label">AP Turnover</div>
                        <div class="card-value">{{ number_format($kpis['efficiency_ratios']['ap_turnover'], 1) }}x</div>
                        <div class="text-xs text-gray-400">COGS / AP</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--purple)"></div>
                        <div class="card-label">Inventory Turnover</div>
                        <div class="card-value">{{ number_format($kpis['efficiency_ratios']['inventory_turnover'], 1) }}x
                        </div>
                        <div class="text-xs text-gray-400">COGS / Inventory</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--teal)"></div>
                        <div class="card-label">Asset Turnover</div>
                        <div class="card-value">{{ number_format($kpis['efficiency_ratios']['asset_turnover'], 2) }}x
                        </div>
                        <div class="text-xs text-gray-400">Revenue / Total Assets</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                    <div class="kpi-card">
                        <div class="card-accentbar"></div>
                        <div class="card-label">Fixed Asset Turnover</div>
                        <div class="card-value">
                            {{ number_format($kpis['efficiency_ratios']['fixed_asset_turnover'], 2) }}x</div>
                        <div class="text-xs text-gray-400">Revenue / Fixed Assets</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--teal)"></div>
                        <div class="card-label">Working Capital Turnover</div>
                        <div class="card-value">
                            {{ number_format($kpis['efficiency_ratios']['working_capital_turnover'], 2) }}x</div>
                        <div class="text-xs text-gray-400">Revenue / Working Capital</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:var(--success)"></div>
                        <div class="card-label">Return on Assets</div>
                        <div
                            class="card-value {{ $kpis['roa'] >= 5 ? 'text-emerald-700' : ($kpis['roa'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($kpis['roa'], 1) }}%</div>
                        <div class="text-xs text-gray-400">Net Profit / Assets</div>
                    </div>
                    @if ($kpis['interest_coverage'] > 0)
                        <div class="kpi-card">
                            <div class="card-accentbar"
                                style="background:var(--{{ $kpis['interest_coverage'] >= 3 ? 'success' : 'danger' }})">
                            </div>
                            <div class="card-label">Interest Coverage</div>
                            <div
                                class="card-value {{ $kpis['interest_coverage'] >= 3 ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ number_format($kpis['interest_coverage'], 1) }}x</div>
                            <div class="text-xs text-gray-400">EBIT / Interest Expense</div>
                        </div>
                    @else
                        <div class="kpi-card">
                            <div class="card-accentbar" style="background:var(--warning)"></div>
                            <div class="card-label">Avg Burn Rate</div>
                            <div class="card-value sm">{{ number_format($kpis['burn_rate'], 0) }}</div>
                            <div class="text-xs text-gray-400">Avg monthly expenses</div>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="chart-card">
                        <div class="cc-title">Cash Conversion Cycle</div>
                        <div class="cc-sub">DSO + DIO − DPO (days)</div>
                        <div style="height:240px;"><canvas id="cccChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Turnover Ratios Comparison</div>
                        <div class="cc-sub">AR, AP, Inventory and Asset turnover</div>
                        <div style="height:240px;"><canvas id="turnoverChart"></canvas></div>
                    </div>
                </div>

            </div>{{-- /efficiency --}}

            {{-- ══ TAB: OPERATIONS & TAX ══════════════════════════════════ --}}
            <div class="tab-pane" id="tab-operations">

                <div class="sect-title">Operations, Cost Structure &amp; Tax</div>

                {{-- Row 1: Contribution / Breakeven --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#13b5ea"></div>
                        <div class="card-label">Contribution Margin</div>
                        <div class="card-value sm">{{ number_format($kpis['contribution_margin'], 0) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Revenue &minus; COGS</div>
                        <div class="vpill good mt-2">{{ number_format($kpis['contribution_margin_ratio'], 1) }}% ratio
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#ff8b00"></div>
                        <div class="card-label">Breakeven Revenue</div>
                        <div
                            class="card-value sm {{ $kpis['total_income'] >= $kpis['breakeven_revenue'] ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['breakeven_revenue'], 0) }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Fixed Costs / CM Ratio</div>
                        <div
                            class="vpill {{ $kpis['total_income'] >= $kpis['breakeven_revenue'] ? 'good' : 'bad' }} mt-2">
                            {{ $kpis['total_income'] >= $kpis['breakeven_revenue'] ? 'Above Breakeven' : 'Below Breakeven' }}
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#4cb653"></div>
                        <div class="card-label">Margin of Safety</div>
                        <div
                            class="card-value {{ $kpis['safety_margin'] >= 20 ? 'text-emerald-700' : ($kpis['safety_margin'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($kpis['safety_margin'], 1) }}%
                        </div>
                        <div class="text-xs text-gray-400 mt-1">(Revenue &minus; Breakeven) / Revenue</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#6554c0"></div>
                        <div class="card-label">Operating Leverage</div>
                        <div class="card-value">{{ number_format($kpis['operating_leverage'], 2) }}x</div>
                        <div class="text-xs text-gray-400 mt-1">Gross Profit / EBIT</div>
                        <div
                            class="text-xs mt-2 {{ $kpis['operating_leverage'] > 3 ? 'text-red-500' : 'text-gray-400' }}">
                            {{ $kpis['operating_leverage'] > 3 ? 'High fixed-cost risk' : 'Moderate leverage' }}
                        </div>
                    </div>
                </div>

                {{-- Row 2: Cost Breakdown --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#e74c3c"></div>
                        <div class="card-label">Payroll Expense</div>
                        <div class="card-value sm">{{ number_format($kpis['payroll_expense'], 0) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Salaries &amp; wages</div>
                        <div
                            class="vpill {{ $kpis['payroll_ratio'] <= 30 ? 'good' : ($kpis['payroll_ratio'] <= 50 ? 'nt' : 'bad') }} mt-2">
                            {{ number_format($kpis['payroll_ratio'], 1) }}% of revenue
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#ff8b00"></div>
                        <div class="card-label">Overhead Expense</div>
                        <div class="card-value sm">{{ number_format($kpis['overhead_expense'], 0) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Opex excl. payroll</div>
                        <div class="vpill nt mt-2">{{ number_format($kpis['overhead_ratio'], 1) }}% of revenue</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#253544"></div>
                        <div class="card-label">Tax Expense</div>
                        <div class="card-value sm">{{ number_format($kpis['tax_expense'], 0) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Income / corporate tax</div>
                        @if ($kpis['effective_tax_rate'] > 0)
                            <div class="vpill nt mt-2">{{ number_format($kpis['effective_tax_rate'], 1) }}% effective
                                rate</div>
                        @endif
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#00b8d9"></div>
                        <div class="card-label">Bad Debt</div>
                        <div class="card-value sm {{ $kpis['bad_debt'] > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                            {{ number_format($kpis['bad_debt'], 0) }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Write-offs / doubtful</div>
                        <div class="vpill {{ $kpis['bad_debt_ratio'] <= 1 ? 'good' : 'bad' }} mt-2">
                            {{ number_format($kpis['bad_debt_ratio'], 2) }}% of revenue
                        </div>
                    </div>
                </div>

                {{-- Row 3: Cash flow operations --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#4cb653"></div>
                        <div class="card-label">Free Cash Flow</div>
                        <div
                            class="card-value sm {{ $kpis['free_cash_flow'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['free_cash_flow'], 0) }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">EBITDA &minus; Interest &minus; Tax</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#13b5ea"></div>
                        <div class="card-label">NPAT</div>
                        <div class="card-value sm {{ $kpis['npat'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['npat'], 0) }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Net Profit After Tax</div>
                        <div class="vpill {{ $kpis['npat'] >= 0 ? 'good' : 'bad' }} mt-2">
                            {{ number_format($kpis['npat_margin'], 1) }}% margin</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#6554c0"></div>
                        <div class="card-label">OCF Ratio</div>
                        <div
                            class="card-value {{ $kpis['operating_cf_ratio'] >= 1 ? 'text-emerald-700' : ($kpis['operating_cf_ratio'] >= 0.5 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($kpis['operating_cf_ratio'], 2) }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">OCF / Current Liabilities</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#ff8b00"></div>
                        <div class="card-label">CF to Debt</div>
                        <div class="card-value {{ $kpis['cf_to_debt'] >= 0.2 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['cf_to_debt'], 2) }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">OCF / Total Liabilities</div>
                    </div>
                </div>

                {{-- Row 4: Operational metrics --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                    <div class="kpi-card">
                        <div class="card-accentbar"></div>
                        <div class="card-label">Revenue per Day</div>
                        <div class="card-value sm">{{ number_format($kpis['revenue_per_day'], 0) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Avg daily revenue</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#4cb653"></div>
                        <div class="card-label">NWC Ratio</div>
                        <div
                            class="card-value {{ $kpis['nwc_ratio'] >= 20 ? 'text-emerald-700' : ($kpis['nwc_ratio'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($kpis['nwc_ratio'], 1) }}%
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Working Capital / Assets</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#13b5ea"></div>
                        <div class="card-label">CEI</div>
                        <div
                            class="card-value {{ $kpis['cei'] >= 80 ? 'text-emerald-700' : ($kpis['cei'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($kpis['cei'], 1) }}%
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Collection Effectiveness</div>
                    </div>
                    @if ($kpis['dscr'] > 0)
                        <div class="kpi-card">
                            <div class="card-accentbar"
                                style="background:{{ $kpis['dscr'] >= 1.25 ? '#4cb653' : '#e74c3c' }}"></div>
                            <div class="card-label">DSCR</div>
                            <div class="card-value {{ $kpis['dscr'] >= 1.25 ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ number_format($kpis['dscr'], 2) }}x
                            </div>
                            <div class="text-xs text-gray-400 mt-1">Debt Service Coverage</div>
                            <div class="vpill {{ $kpis['dscr'] >= 1.25 ? 'good' : 'bad' }} mt-2">
                                {{ $kpis['dscr'] >= 1.25 ? 'Serviceable' : 'Risk' }}</div>
                        </div>
                    @else
                        <div class="kpi-card">
                            <div class="card-accentbar" style="background:#253544"></div>
                            <div class="card-label">Retained Earnings</div>
                            <div
                                class="card-value sm {{ $kpis['retained_earnings'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ number_format($kpis['retained_earnings'], 0) }}
                            </div>
                            <div class="text-xs text-gray-400 mt-1">Accumulated earnings</div>
                        </div>
                    @endif
                </div>

                {{-- Row 5: Global Accounting KPIs --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                    <div class="kpi-card">
                        <div class="card-accentbar"
                            style="background:{{ $kpis['altman_z_score'] >= 2.99 ? '#4cb653' : ($kpis['altman_z_score'] >= 1.81 ? '#ff8b00' : '#e74c3c') }}">
                        </div>
                        <div class="card-label">Altman Z-Score</div>
                        <div
                            class="card-value {{ $kpis['altman_z_score'] >= 2.99 ? 'text-emerald-700' : ($kpis['altman_z_score'] >= 1.81 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($kpis['altman_z_score'], 2) }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Bankruptcy predictor</div>
                        <div
                            class="vpill {{ $kpis['altman_z_score'] >= 2.99 ? 'good' : ($kpis['altman_z_score'] >= 1.81 ? 'nt' : 'bad') }} mt-2">
                            {{ $kpis['altman_z_score'] >= 2.99 ? 'Safe Zone' : ($kpis['altman_z_score'] >= 1.81 ? 'Grey Zone' : 'Distress Zone') }}
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#6554c0"></div>
                        <div class="card-label">Sustainable Growth Rate</div>
                        <div
                            class="card-value {{ $kpis['sustainable_growth_rate'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($kpis['sustainable_growth_rate'], 1) }}%
                        </div>
                        <div class="text-xs text-gray-400 mt-1">ROE &times; Retention</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#e74c3c"></div>
                        <div class="card-label">Gross Burn Rate</div>
                        <div class="card-value sm">{{ number_format($kpis['gross_burn_rate'], 0) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Avg monthly total spend</div>
                    </div>
                    <div class="kpi-card">
                        <div class="card-accentbar" style="background:#13b5ea"></div>
                        <div class="card-label">Equity Ratio</div>
                        <div
                            class="card-value {{ $kpis['equity_ratio'] >= 50 ? 'text-emerald-700' : ($kpis['equity_ratio'] >= 30 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($kpis['equity_ratio'], 1) }}%
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Equity / Total Assets</div>
                    </div>
                </div>

                {{-- Charts Row --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
                    {{-- Cost Structure Donut --}}
                    <div class="chart-card">
                        <div class="cc-title">Cost Structure Breakdown</div>
                        <div class="cc-sub">How revenue is allocated</div>
                        <div style="height:240px;"><canvas id="costStructureChart"></canvas></div>
                    </div>
                    {{-- Breakeven Analysis --}}
                    <div class="chart-card">
                        <div class="cc-title">Breakeven Analysis</div>
                        <div class="cc-sub">Revenue vs total cost curve</div>
                        <div style="height:240px;"><canvas id="breakevenChart"></canvas></div>
                    </div>
                    {{-- Net Cash Flow Monthly --}}
                    <div class="chart-card">
                        <div class="cc-title">Monthly Net Cash Flow</div>
                        <div class="cc-sub">Operating surplus / (deficit) per month</div>
                        <div style="height:240px;"><canvas id="netCashFlowChart"></canvas></div>
                    </div>
                </div>

                {{-- Expense Composition --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="chart-card">
                        <div class="cc-title">Expense Composition</div>
                        <div class="cc-sub">COGS vs Payroll vs Overhead breakdown</div>
                        <div style="height:240px;"><canvas id="expenseCompositionChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Key Ratios Summary</div>
                        <div class="cc-sub">Operational benchmarks</div>
                        <div class="space-y-1 mt-1">
                            @php
                                $opsScorecard = [
                                    [
                                        'Contribution Margin Ratio',
                                        number_format($kpis['contribution_margin_ratio'], 1) . '%',
                                        $kpis['contribution_margin_ratio'] >= 40
                                            ? 'good'
                                            : ($kpis['contribution_margin_ratio'] >= 20
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'Gross Profit Margin',
                                        number_format($kpis['gross_profit_margin'], 1) . '%',
                                        $kpis['gross_profit_margin'] >= 40
                                            ? 'good'
                                            : ($kpis['gross_profit_margin'] >= 20
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'Operating Margin',
                                        number_format($kpis['operating_margin'], 1) . '%',
                                        $kpis['operating_margin'] >= 15
                                            ? 'good'
                                            : ($kpis['operating_margin'] >= 5
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'NPAT Margin',
                                        number_format($kpis['npat_margin'], 1) . '%',
                                        $kpis['npat_margin'] >= 10
                                            ? 'good'
                                            : ($kpis['npat_margin'] >= 0
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'Payroll Ratio',
                                        number_format($kpis['payroll_ratio'], 1) . '%',
                                        $kpis['payroll_ratio'] <= 30
                                            ? 'good'
                                            : ($kpis['payroll_ratio'] <= 50
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'Overhead Ratio',
                                        number_format($kpis['overhead_ratio'], 1) . '%',
                                        $kpis['overhead_ratio'] <= 20
                                            ? 'good'
                                            : ($kpis['overhead_ratio'] <= 35
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'COGS Ratio',
                                        number_format($kpis['cogs_ratio'], 1) . '%',
                                        $kpis['cogs_ratio'] <= 40 ? 'good' : ($kpis['cogs_ratio'] <= 60 ? 'nt' : 'bad'),
                                    ],
                                    ['Effective Tax Rate', number_format($kpis['effective_tax_rate'], 1) . '%', 'nt'],
                                    [
                                        'Free Cash Flow',
                                        number_format($kpis['free_cash_flow'], 0),
                                        $kpis['free_cash_flow'] >= 0 ? 'good' : 'bad',
                                    ],
                                    [
                                        'OCF Ratio',
                                        number_format($kpis['operating_cf_ratio'], 2),
                                        $kpis['operating_cf_ratio'] >= 0.5
                                            ? 'good'
                                            : ($kpis['operating_cf_ratio'] >= 0
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'Margin of Safety',
                                        number_format($kpis['safety_margin'], 1) . '%',
                                        $kpis['safety_margin'] >= 20
                                            ? 'good'
                                            : ($kpis['safety_margin'] >= 0
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'Collection Effectiveness',
                                        number_format($kpis['cei'], 1) . '%',
                                        $kpis['cei'] >= 80 ? 'good' : ($kpis['cei'] >= 60 ? 'nt' : 'bad'),
                                    ],
                                    [
                                        'Altman Z-Score',
                                        number_format($kpis['altman_z_score'], 2),
                                        $kpis['altman_z_score'] >= 2.99
                                            ? 'good'
                                            : ($kpis['altman_z_score'] >= 1.81
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'Sustainable Growth',
                                        number_format($kpis['sustainable_growth_rate'], 1) . '%',
                                        $kpis['sustainable_growth_rate'] >= 5
                                            ? 'good'
                                            : ($kpis['sustainable_growth_rate'] >= 0
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    [
                                        'Equity Ratio',
                                        number_format($kpis['equity_ratio'], 1) . '%',
                                        $kpis['equity_ratio'] >= 50
                                            ? 'good'
                                            : ($kpis['equity_ratio'] >= 30
                                                ? 'nt'
                                                : 'bad'),
                                    ],
                                    ['Gross Burn Rate', number_format($kpis['gross_burn_rate'], 0) . '/mo', 'nt'],
                                ];
                            @endphp
                            @foreach ($opsScorecard as [$label, $val, $status])
                                <div class="mrow">
                                    <span class="mn">{{ $label }}</span>
                                    <span
                                        class="vpill {{ $status === 'good' ? 'good' : ($status === 'bad' ? 'bad' : 'nt') }}">{{ $val }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>{{-- /operations --}}

            {{-- ══ TAB: DUPONT ANALYSIS ════════════════════════════════════ --}}
            <div class="tab-pane" id="tab-dupont">

                <div class="sect-title">DuPont Analysis &amp; Return Decomposition</div>

                {{-- DuPont formula display --}}
                <div class="chart-card mb-5">
                    <div class="cc-title">DuPont ROE Decomposition</div>
                    <div class="cc-sub">ROE = Net Profit Margin &times; Asset Turnover &times; Equity Multiplier</div>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-2 mt-4 items-center text-center">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="text-xs font-bold text-blue-500 uppercase tracking-wide mb-1">Net Profit Margin
                            </div>
                            <div class="text-2xl font-black text-blue-700">
                                {{ number_format($kpis['net_profit_margin'], 1) }}%</div>
                            <div class="text-xs text-gray-400 mt-1">Net Income / Revenue</div>
                        </div>
                        <div class="text-2xl font-black text-gray-400 hidden md:flex items-center justify-center">&times;
                        </div>
                        <div class="bg-teal-50 border border-teal-200 rounded-xl p-4">
                            <div class="text-xs font-bold text-teal-500 uppercase tracking-wide mb-1">Asset Turnover</div>
                            <div class="text-2xl font-black text-teal-700">
                                {{ number_format($kpis['efficiency_ratios']['asset_turnover'], 2) }}x</div>
                            <div class="text-xs text-gray-400 mt-1">Revenue / Total Assets</div>
                        </div>
                        <div class="text-2xl font-black text-gray-400 hidden md:flex items-center justify-center">&times;
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                            <div class="text-xs font-bold text-purple-500 uppercase tracking-wide mb-1">Equity Multiplier
                            </div>
                            <div class="text-2xl font-black text-purple-700">
                                {{ number_format($kpis['equity_multiplier'], 2) }}x</div>
                            <div class="text-xs text-gray-400 mt-1">Total Assets / Equity</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-center mt-4 pt-4 border-t border-gray-100">
                        <div class="text-center">
                            <div class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">= DuPont ROE</div>
                            <div
                                class="text-4xl font-black {{ $kpis['dupont_roe'] >= 10 ? 'text-emerald-600' : ($kpis['dupont_roe'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($kpis['dupont_roe'], 1) }}%
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                    {{-- Return metrics --}}
                    <div class="chart-card">
                        <div class="cc-title">Return Metrics</div>
                        <div class="cc-sub">Performance across capital bases</div>
                        <div style="height:250px;"><canvas id="returnMetricsChart"></canvas></div>
                    </div>

                    {{-- Key ratios summary --}}
                    <div class="chart-card">
                        <div class="cc-title">Financial Health Scorecard</div>
                        <div class="cc-sub">At a glance — all key ratios</div>
                        <div class="space-y-1 mt-1">
                            @php
                                $scorecard = [
                                    [
                                        'Gross Profit Margin',
                                        number_format($kpis['gross_profit_margin'], 1) . '%',
                                        $kpis['gross_profit_margin'] >= 40
                                            ? 'good'
                                            : ($kpis['gross_profit_margin'] >= 20
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'Net Profit Margin',
                                        number_format($kpis['net_profit_margin'], 1) . '%',
                                        $kpis['net_profit_margin'] >= 10
                                            ? 'good'
                                            : ($kpis['net_profit_margin'] >= 0
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'EBITDA Margin',
                                        number_format($kpis['ebitda_margin'], 1) . '%',
                                        $kpis['ebitda_margin'] >= 15
                                            ? 'good'
                                            : ($kpis['ebitda_margin'] >= 5
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'Current Ratio',
                                        number_format($kpis['current_ratio'], 2),
                                        $kpis['current_ratio'] >= 1.5
                                            ? 'good'
                                            : ($kpis['current_ratio'] >= 1
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'Quick Ratio',
                                        number_format($kpis['quick_ratio'], 2),
                                        $kpis['quick_ratio'] >= 1
                                            ? 'good'
                                            : ($kpis['quick_ratio'] >= 0.5
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'Debt Ratio',
                                        number_format($kpis['financial_position']['debt_ratio'], 1) . '%',
                                        $kpis['financial_position']['debt_ratio'] < 50
                                            ? 'good'
                                            : ($kpis['financial_position']['debt_ratio'] < 70
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'Return on Assets',
                                        number_format($kpis['roa'], 1) . '%',
                                        $kpis['roa'] >= 5 ? 'good' : ($kpis['roa'] >= 0 ? 'warn' : 'poor'),
                                    ],
                                    [
                                        'Return on Equity',
                                        number_format($kpis['roe'], 1) . '%',
                                        $kpis['roe'] >= 10 ? 'good' : ($kpis['roe'] >= 0 ? 'warn' : 'poor'),
                                    ],
                                    [
                                        'Asset Turnover',
                                        number_format($kpis['efficiency_ratios']['asset_turnover'], 2) . 'x',
                                        'nt',
                                    ],
                                    [
                                        'DSO (AR Days)',
                                        number_format($kpis['efficiency_ratios']['ar_days'], 1) . 'd',
                                        $kpis['efficiency_ratios']['ar_days'] <= 30
                                            ? 'good'
                                            : ($kpis['efficiency_ratios']['ar_days'] <= 60
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'Altman Z-Score',
                                        number_format($kpis['altman_z_score'], 2),
                                        $kpis['altman_z_score'] >= 2.99
                                            ? 'good'
                                            : ($kpis['altman_z_score'] >= 1.81
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'Equity Ratio',
                                        number_format($kpis['equity_ratio'], 1) . '%',
                                        $kpis['equity_ratio'] >= 50
                                            ? 'good'
                                            : ($kpis['equity_ratio'] >= 30
                                                ? 'warn'
                                                : 'poor'),
                                    ],
                                    [
                                        'Cash Conv. Cycle',
                                        number_format($kpis['efficiency_ratios']['ccc'], 1) . 'd',
                                        $kpis['efficiency_ratios']['ccc'] <= 30 ? 'good' : 'warn',
                                    ],
                                ];
                            @endphp
                            @foreach ($scorecard as [$label, $val, $status])
                                <div class="mrow">
                                    <span class="mn">{{ $label }}</span>
                                    <span
                                        class="vpill {{ $status === 'good' ? 'good' : ($status === 'poor' ? 'bad' : 'nt') }}">{{ $val }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Top accounts --}}
                <div class="chart-card">
                    <div class="cc-title">Top Accounts by Balance</div>
                    <div class="cc-sub">Highest balance accounts this period</div>
                    <div style="height:250px;"><canvas id="topAccountsChart"></canvas></div>
                </div>

            </div>{{-- /dupont --}}

        </div>{{-- /main content --}}
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const kpi = @json($kpis);
        function toggleKpiCustomDates(select) {
            const customDiv = document.getElementById('custom-dates-kpi');
            if (select.value === 'CUSTOM') {
                customDiv.classList.remove('hidden');
                customDiv.classList.add('flex');
            } else {
                customDiv.classList.remove('flex');
                customDiv.classList.add('hidden');
                select.form.submit();
            }
        }
    </script>
    <script src="{{ asset('assets/js/accounting-kpi.js') }}"></script>
@endpush
