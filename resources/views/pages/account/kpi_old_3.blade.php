@extends('layouts.auth')
@section('title', 'Business Performance')
@section('content')

    <style>
        :root {
            --p: #0078C8;
            /* project primary blue        */
            --p-dark: #006BB0;
            /* hover / dark variant        */
            --p-light: #F6F9FC;
            /* light bg tint               */
            --p-ring: rgba(0, 120, 200, .18);
            /* --navy: #253544; */
            --navy: #0078C8;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --purple: #6554C0;
            --teal: #0A8DE4;
            --bg: #F6F9FC;
            --card: #ffffff;
            --border: #E0E0E0;
            --text: #1F2937;
            --muted: #6B7280;
            --light: #F3F4F6;
        }

        body {
            background: var(--bg);
        }

        /* ── Header ──────────────────────────────────────────── */
        .kpi-header {
            background: linear-gradient(135deg, #0078C8 0%, #006BB0 100%);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0, 120, 200, .35);
        }

        .kpi-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 24px;
        }

        .kpi-header h1 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }

        .kpi-header .subtitle {
            font-size: .72rem;
            color: rgba(255, 255, 255, .55);
            margin-top: 2px;
        }

        /* health badge */
        .health-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
        }

        .health-badge.excellent {
            background: #d1fae5;
            color: #065f46;
        }

        .health-badge.good {
            background: #dbeafe;
            color: #1e40af;
        }

        .health-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .health-badge.poor {
            background: #fee2e2;
            color: #991b1b;
        }

        .health-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            animation: blink 2s infinite;
        }

        .health-badge.excellent .health-dot {
            background: #059669;
        }

        .health-badge.good .health-dot {
            background: #2563eb;
        }

        .health-badge.warning .health-dot {
            background: #d97706;
        }

        .health-badge.poor .health-dot {
            background: #dc2626;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .35
            }
        }

        /* period selector */
        .period-select-wrap select {
            appearance: none;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .25);
            color: #fff;
            font-size: .78rem;
            font-weight: 600;
            padding: 5px 28px 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 12 12'%3E%3Cpath fill='%23fff' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 9px center;
        }

        .period-select-wrap select:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--p-ring);
        }

        .period-select-wrap select option {
            background: #253544;
        }

        /* ── Tabs ─────────────────────────────────────────────── */
        .kpi-tabs {
            background: #fff;
            border-bottom: 2px solid var(--border);
            display: flex;
            padding: 0 24px;
            overflow-x: auto;
        }

        .kpi-tab {
            padding: 11px 18px;
            font-size: .8rem;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
            transition: all .15s;
            user-select: none;
        }

        .kpi-tab:hover {
            color: var(--navy);
        }

        .kpi-tab.active {
            color: var(--p);
            border-bottom-color: var(--p);
        }

        /* ── Summary Banner ──────────────────────────────────── */
        .summary-banner {
            /* background: linear-gradient(135deg, var(--navy) 0%, #1b3a56 100%); */
            background: linear-gradient(135deg, var(--navy) 0%, #276eac 100%);
            padding: 18px 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            position: relative;
        }

        .summary-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--p), var(--teal), var(--success));
        }

        .s-item {
            padding: 8px 16px;
            border-right: 1px solid rgba(255, 255, 255, .1);
            text-align: center;
        }

        .s-item:last-child {
            border-right: none;
        }

        .s-label {
            font-size: .64rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .5);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 3px;
        }

        .s-value {
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }

        .s-change {
            font-size: .68rem;
            font-weight: 600;
            margin-top: 3px;
        }

        .s-change.up {
            color: #6ee7b7;
        }

        .s-change.dn {
            color: #fca5a5;
        }

        .s-change.nt {
            color: rgba(255, 255, 255, .45);
        }

        /* ── KPI Card ─────────────────────────────────────────── */
        .kpi-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px;
            position: relative;
            overflow: hidden;
            transition: box-shadow .18s, transform .18s;
        }

        .kpi-card:hover {
            box-shadow: 0 4px 18px rgba(0, 0, 0, .09);
            transform: translateY(-1px);
        }

        .kpi-card .card-accentbar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--p);
        }

        .kpi-card .card-label {
            font-size: .64rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-bottom: 5px;
            margin-top: 4px;
        }

        .kpi-card .card-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1.1;
            margin-bottom: 5px;
        }

        .kpi-card .card-value.sm {
            font-size: 1.15rem;
        }

        .vpill {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            font-size: .68rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .vpill.good {
            background: #d1fae5;
            color: #065f46;
        }

        .vpill.bad {
            background: #fee2e2;
            color: #991b1b;
        }

        .vpill.nt {
            background: var(--light);
            color: var(--muted);
        }

        .vs-label {
            font-size: .66rem;
            color: var(--muted);
            margin-left: 4px;
        }

        .spark-wrap {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 44px;
            opacity: .55;
        }

        /* ── Section Title ───────────────────────────────────── */
        .sect-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }

        .sect-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── Chart Card ──────────────────────────────────────── */
        .chart-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 18px;
            transition: box-shadow .18s;
        }

        .chart-card:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, .07);
        }

        .cc-title {
            font-size: .8rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 2px;
        }

        .cc-sub {
            font-size: .7rem;
            color: var(--muted);
            margin-bottom: 14px;
        }

        /* ── Gauge ───────────────────────────────────────────── */
        .gauge-wrap {
            position: relative;
            width: 100%;
        }

        .gauge-label {
            position: absolute;
            bottom: 8%;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
        }

        .g-val {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
        }

        .g-sub {
            font-size: .63rem;
            font-weight: 600;
            color: var(--muted);
            margin-top: 2px;
        }

        /* ── Metric Row ──────────────────────────────────────── */
        .mrow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--light);
        }

        .mrow:last-child {
            border-bottom: none;
        }

        .mrow .mn {
            font-size: .78rem;
            color: var(--muted);
            font-weight: 500;
        }

        .mrow .mv {
            font-size: .85rem;
            font-weight: 700;
            color: var(--text);
        }

        .mrow .mbar-wrap {
            flex: 1;
            margin: 0 10px;
            background: var(--light);
            border-radius: 4px;
            height: 5px;
            overflow: hidden;
        }

        .mrow .mbar {
            height: 5px;
            border-radius: 4px;
            background: var(--p);
        }

        /* ── Tab panes ───────────────────────────────────────── */
        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* ── Info pill ───────────────────────────────────────── */
        .info-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--p-light);
            color: var(--p);
            font-size: .67rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
        }

        /* ── Scrollbar ───────────────────────────────────────── */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 3px;
        }

        @media(max-width:768px) {
            .summary-banner {
                grid-template-columns: repeat(2, 1fr);
            }

            .kpi-header-inner {
                flex-wrap: wrap;
                gap: 6px;
            }
        }
    </style>

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
                        <select name="per_date" onchange="document.getElementById('pf').submit()" title="Period">
                            <option value="">Select Period</option>
                            @foreach (config('default.per_date') as $k => $v)
                                <option value="{{ $v }}" {{ request('per_date') == $v ? 'selected' : '' }}>
                                    {{ $k }}</option>
                            @endforeach
                        </select>
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
                <div class="s-value">{{ fv($kpis['total_income']) }}</div>
                <div class="s-change {{ $ic['cl'] }}">{{ $ic['p'] >= 0 ? '▲' : '▼' }}
                    {{ number_format(abs($ic['p']), 1) }}%
                </div>
            </div>
            <div class="s-item">
                <div class="s-label">Gross Profit</div>
                <div class="s-value">{{ fv($kpis['gross_profit']) }}</div>
                <div class="s-change {{ $gc['cl'] }}">{{ number_format($kpis['gross_profit_margin'], 1) }}% margin
                </div>
            </div>
            <div class="s-item">
                <div class="s-label">Net Profit</div>
                <div class="s-value">{{ fv($kpis['net_profit']) }}</div>
                <div class="s-change {{ $nc['cl'] }}">{{ number_format($kpis['net_profit_margin'], 1) }}% margin</div>
            </div>
            <div class="s-item">
                <div class="s-label">EBITDA</div>
                <div class="s-value">{{ fv($kpis['ebitda']) }}</div>
                <div class="s-change nt">{{ number_format($kpis['ebitda_margin'], 1) }}% margin</div>
            </div>
            <div class="s-item">
                <div class="s-label">Total Expenses</div>
                <div class="s-value">{{ fv($kpis['total_expenses']) }}</div>
                <div class="s-change {{ $ec['cl'] }}">{{ $ec['p'] >= 0 ? '▲' : '▼' }}
                    {{ number_format(abs($ec['p']), 1) }}%
                </div>
            </div>
            <div class="s-item">
                <div class="s-label">Cash &amp; Bank</div>
                <div class="s-value">{{ fv($kpis['cash_balance']) }}</div>
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
                        $kpis['total_expenses'] - $kpis['cogs'],
                        $kpis['total_expenses_previous'] - $kpis['cogs_previous'],
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
                                <div><span class="mv text-emerald-700">{{ number_format($kpis['gross_profit'], 0) }}</span>
                                    <span
                                        class="text-xs text-emerald-600">({{ number_format($kpis['gross_profit_margin'], 1) }}%)</span>
                                </div>
                            </div>
                            <div class="mrow"><span class="mn text-red-500">(-) Operating Exp.</span><span
                                    class="mv text-red-500">({{ number_format($kpis['total_expenses'] - $kpis['cogs'], 0) }})</span>
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
                        <div class="card-value">{{ number_format($kpis['cash_balance'], 0) }}</div>
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
                            {{ number_format($kpis['financial_position']['total_assets'], 0) }}</div>
                        <div class="mrow"><span class="mn">Current Assets</span><span
                                class="mv text-blue-600">{{ number_format($kpis['financial_position']['current_assets'], 0) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Non-Current Assets</span><span
                                class="mv">{{ number_format($kpis['financial_position']['total_assets'] - $kpis['financial_position']['current_assets'], 0) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Accounts Receivable</span><span
                                class="mv">{{ number_format($kpis['accounts_receivable'], 0) }}</span></div>
                        <div style="height:90px;margin-top:10px;"><canvas id="assetsDonut"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="cc-title">Liabilities</div>
                        <div class="text-3xl font-black text-red-600 my-2">
                            {{ number_format($kpis['financial_position']['total_liabilities'], 0) }}</div>
                        <div class="mrow"><span class="mn">Current Liabilities</span><span
                                class="mv text-red-500">{{ number_format($kpis['financial_position']['current_liabilities'], 0) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Non-Current Liabilities</span><span
                                class="mv">{{ number_format($kpis['financial_position']['total_liabilities'] - $kpis['financial_position']['current_liabilities'], 0) }}</span>
                        </div>
                        <div class="mrow"><span class="mn">Accounts Payable</span><span
                                class="mv">{{ number_format($kpis['accounts_payable'], 0) }}</span></div>
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
                            {{ number_format($kpis['financial_position']['total_equity'], 0) }}</div>
                        <div class="mrow"><span class="mn">Debt to Equity</span><span
                                class="mv">{{ number_format($kpis['financial_position']['debt_to_equity'], 2) }}</span>
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
                        <div class="card-value">{{ number_format($kpis['efficiency_ratios']['inventory_days'], 1) }}</div>
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
            <div class="vpill good mt-2">{{ number_format($kpis['contribution_margin_ratio'], 1) }}% ratio</div>
        </div>
        <div class="kpi-card">
            <div class="card-accentbar" style="background:#ff8b00"></div>
            <div class="card-label">Breakeven Revenue</div>
            <div class="card-value sm {{ $kpis['total_income'] >= $kpis['breakeven_revenue'] ? 'text-emerald-700' : 'text-red-600' }}">
                {{ number_format($kpis['breakeven_revenue'], 0) }}
            </div>
            <div class="text-xs text-gray-400 mt-1">Fixed Costs / CM Ratio</div>
            <div class="vpill {{ $kpis['total_income'] >= $kpis['breakeven_revenue'] ? 'good' : 'bad' }} mt-2">
                {{ $kpis['total_income'] >= $kpis['breakeven_revenue'] ? 'Above Breakeven' : 'Below Breakeven' }}
            </div>
        </div>
        <div class="kpi-card">
            <div class="card-accentbar" style="background:#4cb653"></div>
            <div class="card-label">Margin of Safety</div>
            <div class="card-value {{ $kpis['safety_margin'] >= 20 ? 'text-emerald-700' : ($kpis['safety_margin'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ number_format($kpis['safety_margin'], 1) }}%
            </div>
            <div class="text-xs text-gray-400 mt-1">(Revenue &minus; Breakeven) / Revenue</div>
        </div>
        <div class="kpi-card">
            <div class="card-accentbar" style="background:#6554c0"></div>
            <div class="card-label">Operating Leverage</div>
            <div class="card-value">{{ number_format($kpis['operating_leverage'], 2) }}x</div>
            <div class="text-xs text-gray-400 mt-1">Gross Profit / EBIT</div>
            <div class="text-xs mt-2 {{ $kpis['operating_leverage'] > 3 ? 'text-red-500' : 'text-gray-400' }}">
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
            <div class="vpill {{ $kpis['payroll_ratio'] <= 30 ? 'good' : ($kpis['payroll_ratio'] <= 50 ? 'nt' : 'bad') }} mt-2">
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
            @if($kpis['effective_tax_rate'] > 0)
            <div class="vpill nt mt-2">{{ number_format($kpis['effective_tax_rate'], 1) }}% effective rate</div>
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
            <div class="card-value sm {{ $kpis['free_cash_flow'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
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
            <div class="vpill {{ $kpis['npat'] >= 0 ? 'good' : 'bad' }} mt-2">{{ number_format($kpis['npat_margin'], 1) }}% margin</div>
        </div>
        <div class="kpi-card">
            <div class="card-accentbar" style="background:#6554c0"></div>
            <div class="card-label">OCF Ratio</div>
            <div class="card-value {{ $kpis['operating_cf_ratio'] >= 1 ? 'text-emerald-700' : ($kpis['operating_cf_ratio'] >= 0.5 ? 'text-yellow-600' : 'text-red-600') }}">
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
            <div class="card-value {{ $kpis['nwc_ratio'] >= 20 ? 'text-emerald-700' : ($kpis['nwc_ratio'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ number_format($kpis['nwc_ratio'], 1) }}%
            </div>
            <div class="text-xs text-gray-400 mt-1">Working Capital / Assets</div>
        </div>
        <div class="kpi-card">
            <div class="card-accentbar" style="background:#13b5ea"></div>
            <div class="card-label">CEI</div>
            <div class="card-value {{ $kpis['cei'] >= 80 ? 'text-emerald-700' : ($kpis['cei'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ number_format($kpis['cei'], 1) }}%
            </div>
            <div class="text-xs text-gray-400 mt-1">Collection Effectiveness</div>
        </div>
        @if($kpis['dscr'] > 0)
        <div class="kpi-card">
            <div class="card-accentbar" style="background:{{ $kpis['dscr'] >= 1.25 ? '#4cb653' : '#e74c3c' }}"></div>
            <div class="card-label">DSCR</div>
            <div class="card-value {{ $kpis['dscr'] >= 1.25 ? 'text-emerald-700' : 'text-red-600' }}">
                {{ number_format($kpis['dscr'], 2) }}x
            </div>
            <div class="text-xs text-gray-400 mt-1">Debt Service Coverage</div>
            <div class="vpill {{ $kpis['dscr'] >= 1.25 ? 'good' : 'bad' }} mt-2">{{ $kpis['dscr'] >= 1.25 ? 'Serviceable' : 'Risk' }}</div>
        </div>
        @else
        <div class="kpi-card">
            <div class="card-accentbar" style="background:#253544"></div>
            <div class="card-label">Retained Earnings</div>
            <div class="card-value sm {{ $kpis['retained_earnings'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                {{ number_format($kpis['retained_earnings'], 0) }}
            </div>
            <div class="text-xs text-gray-400 mt-1">Accumulated earnings</div>
        </div>
        @endif
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
                    ['Contribution Margin Ratio', number_format($kpis['contribution_margin_ratio'],1).'%',
                        $kpis['contribution_margin_ratio']>=40?'good':($kpis['contribution_margin_ratio']>=20?'nt':'bad')],
                    ['Gross Profit Margin',        number_format($kpis['gross_profit_margin'],1).'%',
                        $kpis['gross_profit_margin']>=40?'good':($kpis['gross_profit_margin']>=20?'nt':'bad')],
                    ['Operating Margin',           number_format($kpis['operating_margin'],1).'%',
                        $kpis['operating_margin']>=15?'good':($kpis['operating_margin']>=5?'nt':'bad')],
                    ['NPAT Margin',                number_format($kpis['npat_margin'],1).'%',
                        $kpis['npat_margin']>=10?'good':($kpis['npat_margin']>=0?'nt':'bad')],
                    ['Payroll Ratio',              number_format($kpis['payroll_ratio'],1).'%',
                        $kpis['payroll_ratio']<=30?'good':($kpis['payroll_ratio']<=50?'nt':'bad')],
                    ['Overhead Ratio',             number_format($kpis['overhead_ratio'],1).'%',
                        $kpis['overhead_ratio']<=20?'good':($kpis['overhead_ratio']<=35?'nt':'bad')],
                    ['COGS Ratio',                 number_format($kpis['cogs_ratio'],1).'%',
                        $kpis['cogs_ratio']<=40?'good':($kpis['cogs_ratio']<=60?'nt':'bad')],
                    ['Effective Tax Rate',         number_format($kpis['effective_tax_rate'],1).'%', 'nt'],
                    ['Free Cash Flow',             number_format($kpis['free_cash_flow'],0),
                        $kpis['free_cash_flow']>=0?'good':'bad'],
                    ['OCF Ratio',                  number_format($kpis['operating_cf_ratio'],2),
                        $kpis['operating_cf_ratio']>=0.5?'good':($kpis['operating_cf_ratio']>=0?'nt':'bad')],
                    ['Margin of Safety',           number_format($kpis['safety_margin'],1).'%',
                        $kpis['safety_margin']>=20?'good':($kpis['safety_margin']>=0?'nt':'bad')],
                    ['Collection Effectiveness',   number_format($kpis['cei'],1).'%',
                        $kpis['cei']>=80?'good':($kpis['cei']>=60?'nt':'bad')],
                ];
                @endphp
                @foreach($opsScorecard as [$label, $val, $status])
                <div class="mrow">
                    <span class="mn">{{ $label }}</span>
                    <span class="vpill {{ $status==='good'?'good':($status==='bad'?'bad':'nt') }}">{{ $val }}</span>
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

        /* ── Colour palette ─────────────────────────────────────────── */
        const C = {
            primary: '#13b5ea', // Xero blue
            pDark: '#0f9fd4',
            pLight: '#eaf7fd',
            navy: '#253544',
            success: '#4cb653', // Xero green
            danger: '#e74c3c', // Xero red
            warning: '#ff8b00', // orange
            purple: '#6554c0',
            teal: '#00b8d9',
            gray: '#dfe1e6',
            muted: '#6b778c',
            bg: '#f4f5f7',
            white: '#ffffff',
        };

        /* ── Helpers ────────────────────────────────────────────────── */
        function hex(h, a) {
            const r = parseInt(h.slice(1, 3), 16),
                g = parseInt(h.slice(3, 5), 16),
                b = parseInt(h.slice(5, 7), 16);
            return `rgba(${r},${g},${b},${a})`;
        }

        function grad(ctx, color, h = 120) {
            const g = ctx.createLinearGradient(0, 0, 0, h);
            g.addColorStop(0, hex(color, .3));
            g.addColorStop(1, hex(color, 0));
            return g;
        }
        Chart.defaults.font.family = "'Inter','Instrument Sans','Segoe UI',sans-serif";
        Chart.defaults.color = C.muted;

        const mini = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1100,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: C.navy,
                    padding: 7,
                    cornerRadius: 5,
                    displayColors: false,
                    callbacks: {
                        label: ctx => Number(ctx.raw).toLocaleString()
                    }
                }
            },
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false
                }
            },
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 4
                },
                line: {
                    borderWidth: 2,
                    tension: .4
                }
            },
            layout: {
                padding: 0
            }
        };

        /* ── Sparkline factory ──────────────────────────────────────── */
        function spark(id, data, color, type = 'line') {
            const el = document.getElementById(id);
            if (!el) return;
            new Chart(el, {
                type,
                data: {
                    labels: kpi.monthly_labels,
                    datasets: [{
                        data,
                        borderColor: color,
                        backgroundColor: type === 'line' ? ctx => grad(ctx.chart.ctx, color, 55) : hex(
                            color, .7),
                        fill: type === 'line',
                        borderRadius: type === 'bar' ? 2 : undefined,
                        barPercentage: .6
                    }]
                },
                options: {
                    ...mini,
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false
                        }
                    }
                }
            });
        }

        const inc = kpi.monthly_income.map(d => d.value);
        const exp = kpi.monthly_expenses.map(d => d.value);
        const cogs = kpi.monthly_cogs.map(d => d.value);
        const netM = inc.map((v, i) => v - exp[i] - cogs[i]);
        const balM = kpi.monthly_balance.map(d => d.balance);
        const gpM = inc.map((v, i) => v > 0 ? ((v - cogs[i]) / v) * 100 : 0);
        const npmM = inc.map((v, i) => v > 0 ? (netM[i] / v) * 100 : 0);

        /* Sparklines */
        spark('sp-rev', inc, C.primary);
        spark('sp-cogs', cogs, C.warning);
        spark('sp-opex', exp, C.danger);
        spark('sp-net', netM, C.success);
        spark('sp-gpm', gpM, C.teal, 'bar');
        spark('sp-opm', gpM.map((_, i) => inc[i] > 0 ? (netM[i] - (inc[i] - exp[i] - cogs[i] - netM[i])) / inc[i] * 100 :
            0), C.primary, 'bar');
        spark('sp-npm', npmM, C.success, 'bar');
        spark('sp-ebitdam', npmM, C.purple, 'bar');
        spark('sp-ar', inc.map(() => kpi.accounts_receivable / 12), C.primary);
        spark('sp-ap', inc.map(() => kpi.accounts_payable / 12), C.warning);
        spark('sp-wc', inc.map(() => kpi.working_capital / 12), C.teal);
        spark('sp-bal', balM, C.navy);
        spark('sp-roa', inc.map((_, i) => i === 11 ? kpi.roa : 0), C.primary, 'bar');
        spark('sp-roe', inc.map((_, i) => i === 11 ? kpi.roe : 0), C.teal, 'bar');
        spark('sp-ebit', netM, C.navy);
        spark('sp-ebitda', netM, C.purple);

        /* ── Main Trend Chart ───────────────────────────────────────── */
        new Chart(document.getElementById('mainTrendChart'), {
            type: 'bar',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                        label: 'Revenue',
                        data: inc,
                        backgroundColor: hex(C.primary, .75),
                        borderRadius: 4,
                        barPercentage: .55,
                        order: 2
                    },
                    {
                        label: 'Expenses',
                        data: exp.map((v, i) => v + cogs[i]),
                        backgroundColor: hex(C.danger, .65),
                        borderRadius: 4,
                        barPercentage: .55,
                        order: 3
                    },
                    {
                        label: 'Net Profit',
                        data: netM,
                        type: 'line',
                        borderColor: C.success,
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        tension: .4,
                        pointRadius: 3,
                        pointBackgroundColor: C.white,
                        pointBorderColor: C.success,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1400,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 16,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: C.navy,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: C.muted
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v.toLocaleString(),
                            font: {
                                size: 10
                            },
                            color: C.muted
                        }
                    }
                }
            }
        });

        /* ── P&L Breakdown ──────────────────────────────────────────── */
        new Chart(document.getElementById('plBreakdown'), {
            type: 'bar',
            data: {
                labels: ['Revenue', 'Cost of Sales', 'Gross Profit', 'Operating Exp.', 'Net Profit'],
                datasets: [{
                    data: [kpi.profit_loss_data.revenue, -Math.abs(kpi.profit_loss_data.cogs),
                        kpi.profit_loss_data.gross_profit, -Math.abs(kpi.profit_loss_data.expenses),
                        kpi.profit_loss_data.net_income
                    ],
                    backgroundColor: [hex(C.primary, .85), hex(C.warning, .85), hex(C.teal, .85), hex(C
                            .danger, .85),
                        kpi.profit_loss_data.net_income >= 0 ? hex(C.success, .9) : hex(C.danger, .9)
                    ],
                    borderRadius: 6,
                    barThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1600,
                    easing: 'easeOutExpo'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: ctx => Math.abs(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            color: C.navy
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v.toLocaleString(),
                            color: C.muted,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Margin Trend ───────────────────────────────────────────── */
        new Chart(document.getElementById('marginTrendChart'), {
            type: 'line',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                        label: 'Gross Margin %',
                        data: gpM,
                        borderColor: C.teal,
                        backgroundColor: ctx => grad(ctx.chart.ctx, C.teal, 220),
                        fill: true,
                        tension: .4,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: C.white,
                        pointBorderColor: C.teal
                    },
                    {
                        label: 'Net Margin %',
                        data: npmM,
                        borderColor: C.primary,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: .4,
                        borderWidth: 2,
                        borderDash: [5, 3],
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 7,
                            padding: 14,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(1) + '%'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: C.muted
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v.toFixed(0) + '%',
                            color: C.muted,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Net Profit Bar ─────────────────────────────────────────── */
        new Chart(document.getElementById('netProfitBarChart'), {
            type: 'bar',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    label: 'Net Profit',
                    data: netM,
                    backgroundColor: netM.map(v => v >= 0 ? hex(C.success, .75) : hex(C.danger, .75)),
                    borderRadius: 4,
                    barPercentage: .65
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: C.muted
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v.toLocaleString(),
                            color: C.muted,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Gauge factory ──────────────────────────────────────────── */
        function gauge(id, value, max, color) {
            const el = document.getElementById(id);
            if (!el) return;
            const v = Math.min(Math.abs(value), max);
            new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: ['Value', 'Rest'],
                    datasets: [{
                        data: [v, max - v],
                        backgroundColor: [color, C.gray],
                        borderWidth: 0,
                        circumference: 180,
                        rotation: -90,
                        hoverOffset: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '80%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1700,
                        easing: 'easeOutCirc'
                    }
                }
            });
        }
        gauge('gauCR', kpi.current_ratio, 3, kpi.current_ratio >= 1.5 ? C.success : (kpi.current_ratio >= 1 ? C.warning : C
            .danger));
        gauge('gauQR', kpi.quick_ratio, 2, kpi.quick_ratio >= 1 ? C.success : C.danger);
        gauge('gauDebt', kpi.financial_position.debt_ratio, 100, kpi.financial_position.debt_ratio > 70 ? C.danger : (kpi
            .financial_position.debt_ratio > 50 ? C.warning : C.success));

        /* ── AR vs AP ───────────────────────────────────────────────── */
        new Chart(document.getElementById('arApChart'), {
            type: 'bar',
            data: {
                labels: ['Receivables', 'Payables'],
                datasets: [{
                    data: [kpi.accounts_receivable, kpi.accounts_payable],
                    backgroundColor: [hex(C.primary, .8), hex(C.warning, .8)],
                    borderRadius: 6,
                    barThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            color: C.navy
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg
                        },
                        ticks: {
                            callback: v => v.toLocaleString(),
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Balance History ────────────────────────────────────────── */
        const ctxBal = document.getElementById('balanceHistoryChart').getContext('2d');
        new Chart(ctxBal, {
            type: 'line',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    label: 'Balance',
                    data: balM,
                    borderColor: C.primary,
                    backgroundColor: ctx => grad(ctx.chart.ctx, C.primary, 280),
                    fill: true,
                    tension: .4,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: C.white,
                    pointBorderColor: C.primary,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: C.navy,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: ctx => 'Balance: ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: C.muted
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v.toLocaleString(),
                            color: C.muted,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Cash Flow Composition ──────────────────────────────────── */
        new Chart(document.getElementById('cashFlowCompositionChart'), {
            type: 'bar',
            data: {
                labels: kpi.monthly_income.map(d => d.month),
                datasets: [{
                        label: 'Income',
                        data: inc,
                        backgroundColor: hex(C.success, .75),
                        borderRadius: 4,
                        barPercentage: .6
                    },
                    {
                        label: 'Expenses',
                        data: exp.map((v, i) => v + cogs[i]),
                        backgroundColor: hex(C.danger, .65),
                        borderRadius: 4,
                        barPercentage: .6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 16,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: C.navy,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: C.muted
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v.toLocaleString(),
                            color: C.muted,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Assets Donut ───────────────────────────────────────────── */
        new Chart(document.getElementById('assetsDonut'), {
            type: 'doughnut',
            data: {
                labels: ['Current', 'Non-Current'],
                datasets: [{
                    data: [kpi.financial_position.current_assets,
                        Math.max(0, kpi.financial_position.total_assets - kpi.financial_position
                            .current_assets)
                    ],
                    backgroundColor: [C.primary, C.teal],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 7,
                            padding: 8,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => ctx.label + ': ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1500
                }
            }
        });

        /* ── Capital Structure ──────────────────────────────────────── */
        new Chart(document.getElementById('capitalChart'), {
            type: 'bar',
            data: {
                labels: ['Capital Structure'],
                datasets: [{
                        label: 'Equity',
                        data: [Math.max(0, kpi.financial_position.total_equity)],
                        backgroundColor: hex(C.success, .8),
                        borderRadius: 4
                    },
                    {
                        label: 'Liabilities',
                        data: [kpi.financial_position.total_liabilities],
                        backgroundColor: hex(C.danger, .75),
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 16,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg
                        },
                        ticks: {
                            callback: v => v.toLocaleString(),
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        /* ── CCC Chart ──────────────────────────────────────────────── */
        new Chart(document.getElementById('cccChart'), {
            type: 'bar',
            data: {
                labels: ['AR Days (DSO)', 'Inventory Days (DIO)', 'AP Days (DPO)'],
                datasets: [{
                    data: [kpi.efficiency_ratios.ar_days, kpi.efficiency_ratios.inventory_days, kpi
                        .efficiency_ratios.ap_days
                    ],
                    backgroundColor: [hex(C.primary, .8), hex(C.purple, .8), hex(C.warning, .8)],
                    borderRadius: 6,
                    barThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => ctx.parsed.y.toFixed(1) + ' days'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: '600'
                            },
                            color: C.navy
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v + 'd',
                            color: C.muted,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Turnover Chart ─────────────────────────────────────────── */
        new Chart(document.getElementById('turnoverChart'), {
            type: 'bar',
            data: {
                labels: ['AR Turnover', 'AP Turnover', 'Inv. Turnover', 'Asset Turnover', 'WC Turnover'],
                datasets: [{
                    data: [kpi.efficiency_ratios.ar_turnover, kpi.efficiency_ratios.ap_turnover,
                        kpi.efficiency_ratios.inventory_turnover, kpi.efficiency_ratios.asset_turnover,
                        kpi.efficiency_ratios.working_capital_turnover
                    ],
                    backgroundColor: [hex(C.primary, .8), hex(C.warning, .8), hex(C.purple, .8), hex(C.teal,
                        .8), hex(C.success, .8)],
                    borderRadius: 6,
                    barThickness: 38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => ctx.parsed.y.toFixed(2) + 'x'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10,
                                weight: '600'
                            },
                            color: C.navy,
                            maxRotation: 30
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v + 'x',
                            color: C.muted,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Return Metrics ─────────────────────────────────────────── */
        new Chart(document.getElementById('returnMetricsChart'), {
            type: 'bar',
            data: {
                labels: ['ROA', 'ROE', 'DuPont ROE', 'Op. Margin', 'Net Margin', 'EBITDA Margin'],
                datasets: [{
                    data: [kpi.roa, kpi.roe, kpi.dupont_roe, kpi.operating_margin, kpi.net_profit_margin,
                        kpi.ebitda_margin
                    ],
                    backgroundColor: [hex(C.primary, .8), hex(C.teal, .8), hex(C.purple, .8), hex(C.warning,
                        .8), hex(C.success, .8), hex(C.pDark, .8)],
                    borderRadius: 6,
                    barThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => ctx.parsed.y.toFixed(1) + '%'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10,
                                weight: '600'
                            },
                            color: C.navy,
                            maxRotation: 30
                        }
                    },
                    y: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            callback: v => v.toFixed(0) + '%',
                            color: C.muted,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        /* ── Top Accounts (horizontal) ──────────────────────────────── */
        new Chart(document.getElementById('topAccountsChart'), {
            type: 'bar',
            data: {
                labels: kpi.top_accounts.map(a => a.name.length > 16 ? a.name.substring(0, 16) + '…' : a.name),
                datasets: [{
                    label: 'Balance',
                    data: kpi.top_accounts.map(a => a.balance),
                    backgroundColor: kpi.top_accounts.map(a => a.balance >= 0 ? hex(C.success, .75) : hex(C
                        .danger, .75)),
                    borderRadius: 4,
                    barThickness: 18
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: C.navy,
                        callbacks: {
                            label: ctx => 'Balance: ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        border: {
                            display: false
                        },
                        grid: {
                            color: C.bg
                        },
                        ticks: {
                            callback: v => v.toLocaleString(),
                            font: {
                                size: 9
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: C.muted
                        }
                    }
                }
            }
        });

        /* ── Operations & Tax Charts ────────────────────────────────── */

// Cost Structure Donut
new Chart(document.getElementById('costStructureChart'), {
    type: 'doughnut',
    data: {
        labels: ['COGS', 'Payroll', 'Overhead', 'Net Profit'],
        datasets: [{
            data: [
                Math.abs(kpi.cogs),
                Math.abs(kpi.payroll_expense),
                Math.abs(kpi.overhead_expense),
                Math.max(0, kpi.net_profit)
            ],
            backgroundColor: [hex(C.warning, .85), hex(C.danger, .8), hex(C.muted, .6), hex(C.success, .85)],
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '60%',
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 12, font: { size: 11 } } },
            tooltip: { backgroundColor: C.navy, callbacks: { label: ctx => ctx.label + ': ' + Number(ctx.raw).toLocaleString() } }
        },
        animation: { animateScale: true, animateRotate: true, duration: 1600 }
    }
});

// Breakeven Analysis (line chart)
(function () {
    const be = kpi.breakeven_revenue;
    const rev = kpi.total_income;
    const fixed = kpi.overhead_expense + kpi.payroll_expense;
    const points = [0, be * 0.4, be * 0.7, be, rev * 0.8, rev];
    const revenueL = points;
    const totalCostL = points.map(r => fixed + r * (1 - kpi.contribution_margin_ratio / 100));
    new Chart(document.getElementById('breakevenChart'), {
        type: 'line',
        data: {
            labels: points.map(v => Math.round(v).toLocaleString()),
            datasets: [
                { label: 'Revenue', data: revenueL, borderColor: C.success, backgroundColor: 'transparent', borderWidth: 2.5, tension: 0, pointRadius: 3 },
                { label: 'Total Cost', data: totalCostL, borderColor: C.danger, backgroundColor: 'transparent', borderWidth: 2.5, tension: 0, borderDash: [6, 3], pointRadius: 3 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 12, font: { size: 11 } } },
                tooltip: { backgroundColor: C.navy, callbacks: { label: ctx => ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString() } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 9 }, color: C.muted, maxRotation: 30 } },
                y: { border: { display: false }, grid: { color: hex(C.muted, .1) }, ticks: { callback: v => v.toLocaleString(), font: { size: 9 }, color: C.muted } }
            }
        }
    });
})();

// Monthly Net Cash Flow
new Chart(document.getElementById('netCashFlowChart'), {
    type: 'bar',
    data: {
        labels: kpi.monthly_net_cash_flow.map(d => d.month),
        datasets: [{
            label: 'Net Cash Flow',
            data: kpi.monthly_net_cash_flow.map(d => d.value),
            backgroundColor: kpi.monthly_net_cash_flow.map(d => d.value >= 0 ? hex(C.success, .75) : hex(C.danger, .75)),
            borderRadius: 4, barPercentage: .65
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { backgroundColor: C.navy, callbacks: { label: ctx => 'Net CF: ' + Number(ctx.raw).toLocaleString() } }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 }, color: C.muted } },
            y: { border: { display: false }, grid: { color: C.bg, borderDash: [4, 4] }, ticks: { callback: v => v.toLocaleString(), color: C.muted, font: { size: 10 } } }
        }
    }
});

// Expense Composition stacked bar
new Chart(document.getElementById('expenseCompositionChart'), {
    type: 'bar',
    data: {
        labels: kpi.monthly_labels,
        datasets: [
            { label: 'COGS',     data: kpi.monthly_cogs.map(d => d.value),     backgroundColor: hex(C.warning, .8), borderRadius: 2 },
            { label: 'Payroll',  data: kpi.monthly_income.map(() => kpi.payroll_expense / 12), backgroundColor: hex(C.danger, .7),  borderRadius: 2 },
            { label: 'Overhead', data: kpi.monthly_income.map(() => kpi.overhead_expense / 12), backgroundColor: hex(C.muted, .5),   borderRadius: 2 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 12, font: { size: 11 } } },
            tooltip: { mode: 'index', intersect: false, backgroundColor: C.navy, padding: 10, cornerRadius: 8,
                callbacks: { label: ctx => ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString() } }
        },
        scales: {
            x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 }, color: C.muted } },
            y: { stacked: true, border: { display: false }, grid: { color: C.bg, borderDash: [4, 4] }, ticks: { callback: v => v.toLocaleString(), color: C.muted, font: { size: 10 } } }
        }
    }
});

/* ── Tab switching ──────────────────────────────────────────── */
        document.querySelectorAll('.kpi-tab').forEach(t => {
            t.addEventListener('click', function() {
                document.querySelectorAll('.kpi-tab').forEach(x => x.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(x => x.classList.remove('active'));
                this.classList.add('active');
                const p = document.getElementById('tab-' + this.dataset.tab);
                if (p) p.classList.add('active');
            });
        });
    </script>
@endpush
