@extends('layouts.auth')
@section('title', 'Kpi Dashboard')
@section('content')
    <style>
        .text-xero-blue {
            color: #13b5ea;
        }

        .text-xero-navy {
            color: #253544;
        }

        .text-xero-green {
            color: #4cb653;
        }

        .text-xero-red {
            color: #e74c3c;
        }

        .bg-xero-green-light {
            background-color: #f0fbf2;
            color: #4cb653;
        }

        .bg-xero-red-light {
            background-color: #fdf2f2;
            color: #e74c3c;
        }
    </style>

    <div class="mb-8">
        <div class="p-4 space-y-6">

            <!-- Date Filter Header -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold tracking-tight text-xero-navy">Business Performance</h1>
                <div class="relative inline-block">
                    <form method="GET">
                        <select name="per_date" onchange="this.form.submit()"
                            class="appearance-none bg-white border border-gray-300 text-gray-700 text-sm font-medium px-4 py-2 pr-8 rounded shadow-sm hover:border-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Select Period</option>
                            @foreach (config('default.per_date') as $key => $value)
                                <option value="{{ $value }}" {{ request('per_date') == $value ? 'selected' : '' }}>
                                    {{ $key }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                            <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                                <path
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Performance Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <!-- Helper Component for Cards -->
                @php
                    function renderCard(
                        $title,
                        $value,
                        $prevValue,
                        $isPercent = false,
                        $chartId,
                        $canvasHeight = 'h-16',
                    ) {
                        $valueFormatted = $isPercent ? number_format($value, 1) . '%' : number_format($value, 0);
                        $change = $value - $prevValue;

                        // Default High is Good (Green)
                        $isGood = $change >= 0;

                        // Logic flip for Expenses/Payable
                        if (
                            str_contains(strtolower($title), 'expense') ||
                            str_contains(strtolower($title), 'payable')
                        ) {
                            $isGood = $change <= 0;
                        }

                        $varianceClass = $isGood ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
                        $arrow = $change >= 0 ? '↑' : '↓';

                        $percentChange = $prevValue != 0 ? ($change / abs($prevValue)) * 100 : 0;
                        $varianceText = $arrow . ' ' . number_format(abs($percentChange), 1) . '%';

                        echo '
                        <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between h-[180px] relative overflow-hidden group">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">' .
                            $title .
                            '</h3>
                                    <div class="text-2xl font-black text-gray-900">' .
                            $valueFormatted .
                            '</div>
                                </div>
                                <span class="text-gray-200 group-hover:text-yellow-400 transition-colors cursor-pointer text-xl leading-none">★</span>
                            </div>
                            
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full ' .
                            $varianceClass .
                            '">' .
                            $varianceText .
                            '</span>
                                <span class="text-[10px] text-gray-400 font-medium">vs last month</span>
                            </div>
                            
                            <div class="absolute bottom-0 left-0 right-0 ' .
                            $canvasHeight .
                            ' opacity-60 group-hover:opacity-100 transition-opacity">
                                <canvas id="' .
                            $chartId .
                            '"></canvas>
                            </div>
                        </div>';
                    }
                @endphp

                {{ renderCard('Gross Profit %', $kpis['gross_profit_margin'], $kpis['gross_profit_margin_previous'], true, 'chartGrossProfit') }}

                {{ renderCard('Net Profit on Sales', $kpis['net_profit'], $kpis['net_profit_previous'], false, 'chartNetProfit') }}

                {{ renderCard('Total Income', $kpis['total_income'], $kpis['total_income_previous'], false, 'chartIncome') }}

                {{ renderCard('Total Expenses', $kpis['total_expenses'], $kpis['total_expenses_previous'], false, 'chartExpenses') }}

                {{ renderCard('Operating Cash Flow', $kpis['operating_cash_flow'], $kpis['operating_cash_flow_previous'], false, 'chartCashFlow') }}

                {{ renderCard('Account Balance', $kpis['account_balance'], 0, false, 'chartBalance') }}

                <!-- Ratios Card (Custom) -->
                <div
                    class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between h-[180px] relative overflow-hidden group">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Liquidity Ratios</h3>
                        <span
                            class="text-gray-200 group-hover:text-yellow-400 transition-colors cursor-pointer text-xl leading-none">★</span>
                    </div>
                    <div class="space-y-4 mt-1">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Current Ratio</span>
                                <span class="font-bold text-gray-800">{{ number_format($kpis['current_ratio'], 2) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="bg-blue-500 h-1.5 rounded-full"
                                    style="width: {{ min(($kpis['current_ratio'] / 3) * 100, 100) }}%"></div>
                            </div>
                            @php
                                $currentRatioChange = $kpis['current_ratio'] - ($kpis['current_ratio_previous'] ?? 0);
                                $currentRatioPercentChange =
                                    ($kpis['current_ratio_previous'] ?? 0) != 0
                                        ? ($currentRatioChange / abs($kpis['current_ratio_previous'])) * 100
                                        : 0;
                                $currentRatioVarianceClass =
                                    $currentRatioChange >= 0 ? 'bg-xero-green-light' : 'bg-xero-red-light';
                            @endphp
                            <div class="flex items-center mt-1">
                                <span
                                    class="text-[10px] font-black px-2 py-0.5 rounded-full {{ $currentRatioVarianceClass }}">
                                    {{ $currentRatioChange >= 0 ? '+' : '' }}{{ number_format($currentRatioPercentChange, 1) }}%
                                </span>
                                <span class="text-[10px] text-gray-400 font-medium ml-1">vs last month</span>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Quick Ratio</span>
                                <span class="font-bold text-gray-800">{{ number_format($kpis['quick_ratio'], 2) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="bg-teal-500 h-1.5 rounded-full"
                                    style="width: {{ min(($kpis['quick_ratio'] / 2) * 100, 100) }}%"></div>
                            </div>
                            @php
                                $quickRatioChange = $kpis['quick_ratio'] - ($kpis['quick_ratio_previous'] ?? 0);
                                $quickRatioPercentChange =
                                    ($kpis['quick_ratio_previous'] ?? 0) != 0
                                        ? ($quickRatioChange / abs($kpis['quick_ratio_previous'])) * 100
                                        : 0;
                                $quickRatioVarianceClass =
                                    $quickRatioChange >= 0 ? 'bg-xero-green-light' : 'bg-xero-red-light';
                            @endphp
                            <div class="flex items-center mt-1">
                                <span
                                    class="text-[10px] font-black px-2 py-0.5 rounded-full {{ $quickRatioVarianceClass }}">
                                    {{ $quickRatioChange >= 0 ? '+' : '' }}{{ number_format($quickRatioPercentChange, 1) }}%
                                </span>
                                <span class="text-[10px] text-gray-400 font-medium ml-1">vs last month</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROA Card -->
                {{ renderCard('Return on Assets', $kpis['roa'], 0, true, 'chartROA') }}

            </div>

            <!-- New Efficiency and Financial Strength Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                <!-- Efficiency Ratios -->
                <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 font-primary">Efficiency Ratios</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">AR
                                Days</span>
                            <div class="text-xl font-black text-gray-900">
                                {{ number_format($kpis['efficiency_ratios']['ar_days'], 1) }}</div>
                            <span class="text-[10px] text-gray-400 font-medium">Avg collection period</span>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">AP
                                Days</span>
                            <div class="text-xl font-black text-gray-900">
                                {{ number_format($kpis['efficiency_ratios']['ap_days'], 1) }}</div>
                            <span class="text-[10px] text-gray-400 font-medium">Avg payment period</span>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Asset
                                Turnover</span>
                            <div class="text-xl font-black text-gray-900">
                                {{ number_format($kpis['efficiency_ratios']['asset_turnover'], 2) }}</div>
                            <span class="text-[10px] text-gray-400 font-medium">Revenue per $ asset</span>
                        </div>
                    </div>
                </div>

                <!-- Financial Position -->
                <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 font-primary">Financial Position</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Debt to
                                Equity</span>
                            <div class="text-xl font-black text-gray-900">
                                {{ number_format($kpis['financial_position']['debt_to_equity'], 2) }}</div>
                            <span class="text-[10px] text-gray-400 font-medium">Liabilities / Equity</span>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Debt
                                Ratio</span>
                            <div class="text-xl font-black text-gray-900">
                                {{ number_format($kpis['financial_position']['debt_ratio'], 1) }}%</div>
                            <span class="text-[10px] text-gray-400 font-medium">Liabilities / Assets</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Analysis Section -->
            <div class="mt-8">
                <h3 class="text-xl font-bold mb-4" style="color: #253544">Detailed Performance Analysis</h3>

                <!-- Margins & Ratios Row (Expanded) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <!-- ROA Gauge -->
                    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm h-80 flex flex-col">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Return on Assets (ROA)
                        </h4>
                        <div class="grow relative w-full h-full">
                            <canvas id="detailedROAChart"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <span id="val-roa"
                                    class="text-2xl font-black text-gray-900">{{ number_format($kpis['roa'], 1) }}%</span>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <span class="text-[10px] text-gray-400 font-medium tracking-wide uppercase">Net Profit / Total
                                Assets</span>
                        </div>
                    </div>

                    <!-- Debt Ratio Gauge -->
                    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm h-80 flex flex-col">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Debt Ratio</h4>
                        <div class="grow relative w-full h-full">
                            <canvas id="detailedDebtRatioChart"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <span id="val-debt-ratio"
                                    class="text-2xl font-black text-gray-900">{{ number_format($kpis['financial_position']['debt_ratio'], 1) }}%</span>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <span class="text-[10px] text-gray-400 font-medium tracking-wide uppercase">Total Liabilities /
                                Assets</span>
                        </div>
                    </div>

                    <!-- Liquidity Summary -->
                    <div
                        class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm h-80 flex flex-col justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-6">Liquidity Summary
                            </h4>
                            <div class="space-y-6">
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-gray-500 font-medium">Current Ratio</span>
                                        <span
                                            class="font-black text-gray-900">{{ number_format($kpis['current_ratio'], 2) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full"
                                            style="width: {{ min(($kpis['current_ratio'] / 3) * 100, 100) }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-gray-500 font-medium">Quick Ratio</span>
                                        <span
                                            class="font-black text-gray-900">{{ number_format($kpis['quick_ratio'], 2) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full"
                                            style="width: {{ min(($kpis['quick_ratio'] / 3) * 100, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-gray-50">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-400">Target Range</span>
                                <span class="text-gray-900 font-bold">1.5 - 2.5</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Balance & Cash Flow Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Account Balance Trend -->
                    <div class="bg-white border border-gray-200 rounded-md p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-lg font-bold" style="color: #253544">Bank Balance History</h4>
                            <span class="text-sm font-medium text-gray-500">
                                Total: <span
                                    class="text-gray-900 font-bold">{{ number_format($kpis['account_balance'], 0) }}</span>
                            </span>
                        </div>
                        <div class="w-full h-80">
                            <canvas id="detailedAccountBalanceChart"></canvas>
                        </div>
                    </div>

                    <!-- Cash Flow Composition -->
                    <div class="bg-white border border-gray-200 rounded-md p-6 shadow-sm">
                        <h4 class="text-lg font-bold mb-6" style="color: #253544">Cash Flow Composition</h4>
                        <div class="w-full h-80">
                            <canvas id="detailedCashFlowChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Balances Section -->
            <div class="mt-8">
                <h3 class="text-xl font-bold mb-4" style="color: #253544">Account Balances</h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Top Accounts Chart -->
                    <div class="bg-white border border-gray-200 rounded-md p-6 shadow-sm">
                        <h4 class="text-lg font-bold mb-6" style="color: #253544">Top Accounts by Balance</h4>
                        <div class="w-full h-80">
                            <canvas id="topAccountsChart"></canvas>
                        </div>
                    </div>
                    <!-- Category Breakdown -->
                    <div class="bg-white border border-gray-200 rounded-md p-6 shadow-sm">
                        <h4 class="text-lg font-bold mb-6" style="color: #253544">Balance by Category</h4>
                        <div class="w-full h-80">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                    <!-- Account List -->
                    <div class="bg-white border border-gray-200 rounded-md p-6 shadow-sm">
                        <h4 class="text-lg font-bold mb-6" style="color: #253544">Account Details</h4>
                        <div class="space-y-3 max-h-80 overflow-y-auto">
                            @foreach ($kpis['top_accounts'] as $account)
                                <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                    <div>
                                        <span class="text-sm font-medium text-gray-800">{{ $account['name'] }}</span>
                                        <span class="text-xs text-gray-500 block">{{ $account['category'] }}</span>
                                    </div>
                                    <span
                                        class="text-sm font-bold {{ $account['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($account['balance'], 0) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Profit & Loss Section -->
            <div class="mt-8 bg-white border border-gray-200 rounded-md shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold" style="color: #253544">Profit & Loss Performance</h3>
                        <p class="text-sm text-gray-500">Comparative breakdown of Income vs Expenses</p>
                    </div>
                </div>
                <div class="h-80 w-full">
                    <canvas id="chartProfitLossMain"></canvas>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const kpi = @json($kpis);

        // Xero Palette
        const colors = {
            blue: '#13b5ea', // Xero Blue
            navy: '#253544', // Text/Heading
            green: '#4cb653', // Success
            red: '#e74c3c', // Warning
            gray: '#eff3f4', // Background
            text: '#5c6873', // Body Text
            lightGray: '#e5e7eb',
            purple: '#8B5CF6'
        };

        // Common Styles
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = colors.text;

        // Helper for Gradients
        function getGradient(ctx, color) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, color.replace('1)', '0.5').replace(')', ', 0.5)')); // Opacity 0.5
            gradient.addColorStop(1, color.replace('1)', '0.0').replace(')', ', 0.0)')); // Opacity 0
            return gradient;
        }

        // Hex to RGBA helper for gradients
        function hexToRgba(hex, alpha) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1500,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1F2937',
                    bodyColor: '#1F2937',
                    borderColor: '#E5E7EB',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 6,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false,
                    min: 0
                }
            },
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 5,
                    hoverBorderWidth: 2
                },
                line: {
                    borderWidth: 2,
                    tension: 0.4
                }
            },
            layout: {
                padding: {
                    bottom: 0,
                    left: 0,
                    right: 0
                }
            }
        };

        // 1. Gross Profit Trend (Line with Gradient)
        const ctxGross = document.getElementById('chartGrossProfit').getContext('2d');
        new Chart(ctxGross, {
            type: 'line',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    data: kpi.monthly_income.map(d => d.value),
                    borderColor: colors.blue,
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 100);
                        gradient.addColorStop(0, hexToRgba(colors.blue, 0.2));
                        gradient.addColorStop(1, hexToRgba(colors.blue, 0.0));
                        return gradient;
                    },
                    fill: true
                }]
            },
            options: commonOptions
        });

        // 2. Net Profit Trend (Line with Gradient)
        const ctxNet = document.getElementById('chartNetProfit').getContext('2d');
        new Chart(ctxNet, {
            type: 'line',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    data: kpi.monthly_income.map((inc, i) => {
                        let exp = kpi.monthly_expenses[i] ? kpi.monthly_expenses[i].value : 0;
                        return inc.value - exp;
                    }),
                    borderColor: colors.navy,
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 100);
                        gradient.addColorStop(0, hexToRgba(colors.navy, 0.2));
                        gradient.addColorStop(1, hexToRgba(colors.navy, 0.0));
                        return gradient;
                    },
                    fill: true
                }]
            },
            options: commonOptions
        });

        // 3. Income (Bar)
        new Chart(document.getElementById('chartIncome'), {
            type: 'bar',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    data: kpi.monthly_income.map(d => d.value),
                    backgroundColor: colors.green,
                    borderRadius: 3,
                    barPercentage: 0.6,
                    hoverBackgroundColor: '#3aa140'
                }]
            },
            options: commonOptions
        });

        // 4. Expenses (Bar)
        new Chart(document.getElementById('chartExpenses'), {
            type: 'bar',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    data: kpi.monthly_expenses.map(d => d.value),
                    backgroundColor: colors.red,
                    borderRadius: 3,
                    barPercentage: 0.6,
                    hoverBackgroundColor: '#c0392b'
                }]
            },
            options: commonOptions
        });

        // 5. Cash Flow (Line with Gradient)
        const ctxCF = document.getElementById('chartCashFlow').getContext('2d');
        new Chart(ctxCF, {
            type: 'line',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    data: kpi.monthly_income.map((inc, i) => {
                        let exp = kpi.monthly_expenses[i] ? kpi.monthly_expenses[i].value : 0;
                        return inc.value - exp;
                    }),
                    borderColor: colors.purple,
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 100);
                        gradient.addColorStop(0, hexToRgba(colors.purple, 0.2));
                        gradient.addColorStop(1, hexToRgba(colors.purple, 0.0));
                        return gradient;
                    },
                    fill: true
                }]
            },
            options: commonOptions
        });

        // 6. Account Balance (Line)
        new Chart(document.getElementById('chartBalance'), {
            type: 'line',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    data: kpi.monthly_balance.map(d => d.balance),
                    borderColor: colors.blue,
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.4
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        ...commonOptions.plugins.tooltip,
                        callbacks: {
                            label: (ctx) => 'Balance: ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                }
            }
        });

        // Main Profit & Loss Chart
        new Chart(document.getElementById('chartProfitLossMain'), {
            type: 'bar',
            data: {
                labels: ['Revenue', 'COGS', 'Expenses', 'Net Income'],
                datasets: [{
                    label: 'Current Period',
                    data: [
                        kpi.profit_loss_data.revenue,
                        kpi.profit_loss_data.cogs * -1,
                        kpi.profit_loss_data.expenses * -1,
                        kpi.profit_loss_data.net_income
                    ],
                    backgroundColor: [
                        colors.navy,
                        '#F59E0B',
                        colors.red,
                        kpi.profit_loss_data.net_income >= 0 ? colors.green : colors.red
                    ],
                    borderRadius: 6,
                    barThickness: 50,
                    hoverBackgroundColor: [
                        '#1a2530', '#d97706', '#b91c1c',
                        kpi.profit_loss_data.net_income >= 0 ? '#15803d' : '#b91c1c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeOutExpo'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (val) => val.toLocaleString(),
                            font: {
                                size: 11
                            },
                            color: colors.text
                        },
                        grid: {
                            color: colors.gray,
                            borderDash: [4, 4]
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            color: colors.navy
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: colors.navy,
                        callbacks: {
                            label: (ctx) => Math.abs(ctx.raw).toLocaleString()
                        }
                    }
                }
            }
        });

        // 9. ROA Sparkline (on card)
        new Chart(document.getElementById('chartROA'), {
            type: 'line',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    data: kpi.monthly_income.map((inc, i) => {
                        // Rough monthly ROA trend
                        const exp = kpi.monthly_expenses[i] ? kpi.monthly_expenses[i].value : 0;
                        const assets = kpi.financial_position.total_assets || 1;
                        return ((inc.value - exp) / assets) * 100;
                    }),
                    borderColor: colors.blue,
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.4
                }]
            },
            options: commonOptions
        });

        // --- Detailed Analysis Charts ---

        // CountUp Animation
        function animateValue(obj, start, end, duration, isPercent = false, decimals = 1) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                // EaseOutQuart implementation
                const ease = 1 - Math.pow(1 - progress, 4);

                const current = start + (end - start) * ease;
                obj.innerHTML = current.toFixed(decimals) + (isPercent ? '%' : '');
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Trigger Animations
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('val-net-profit'))
                animateValue(document.getElementById('val-net-profit'), 0, kpi.net_profit_margin, 2000, true);
            if (document.getElementById('val-gross-profit'))
                animateValue(document.getElementById('val-gross-profit'), 0, kpi.gross_profit_margin, 2000, true);
            if (document.getElementById('val-current-ratio'))
                animateValue(document.getElementById('val-current-ratio'), 0, kpi.current_ratio, 2000, false, 2);
            if (document.getElementById('val-quick-ratio'))
                animateValue(document.getElementById('val-quick-ratio'), 0, kpi.quick_ratio, 2000, false, 2);
            if (document.getElementById('val-roa'))
                animateValue(document.getElementById('val-roa'), 0, kpi.roa, 2000, true);
            if (document.getElementById('val-debt-ratio'))
                animateValue(document.getElementById('val-debt-ratio'), 0, kpi.financial_position.debt_ratio, 2000,
                    true);
        });

        const detailedOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 2000,
                easing: 'easeOutCirc'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: colors.navy,
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => ' ' + ctx.label + ': ' + Number(ctx.raw).toFixed(1) + '%'
                    }
                }
            },
            cutout: '85%'
        };


        // 5. ROA Gauge
        new Chart(document.getElementById('detailedROAChart'), {
            type: 'doughnut',
            data: {
                labels: ['ROA', 'Rest'],
                datasets: [{
                    data: [Math.min(Math.abs(kpi.roa), 20), 20 - Math.min(Math.abs(kpi.roa), 20)],
                    backgroundColor: [kpi.roa >= 5 ? colors.green : colors.blue, colors.lightGray],
                    borderWidth: 0,
                    circumference: 180,
                    rotation: -90,
                    hoverOffset: 4
                }]
            },
            options: detailedOptions
        });

        // 6. Debt Ratio Gauge
        new Chart(document.getElementById('detailedDebtRatioChart'), {
            type: 'doughnut',
            data: {
                labels: ['Debt', 'Equity'],
                datasets: [{
                    data: [kpi.financial_position.debt_ratio, 100 - kpi.financial_position.debt_ratio],
                    backgroundColor: [kpi.financial_position.debt_ratio > 50 ? colors.red : colors.blue,
                        colors.lightGray
                    ],
                    borderWidth: 0,
                    circumference: 180,
                    rotation: -90,
                    hoverOffset: 4
                }]
            },
            options: detailedOptions
        });

        // 5. Account Balance History (Detailed Area with Gradient)
        const ctxBalDetailed = document.getElementById('detailedAccountBalanceChart').getContext('2d');
        new Chart(ctxBalDetailed, {
            type: 'line',
            data: {
                labels: kpi.monthly_labels,
                datasets: [{
                    label: 'Balance',
                    data: kpi.monthly_balance.map(d => d.balance),
                    borderColor: colors.blue,
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                        gradient.addColorStop(0, hexToRgba(colors.blue, 0.4));
                        gradient.addColorStop(1, hexToRgba(colors.blue, 0.0));
                        return gradient;
                    },
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: colors.blue,
                    pointHoverRadius: 7,
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: colors.navy,
                        titleFont: {
                            family: "'Inter', sans-serif",
                            size: 13
                        },
                        bodyFont: {
                            family: "'Inter', sans-serif",
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => 'Balance: ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: colors.text,
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        beginAtZero: false,
                        border: {
                            display: false
                        },
                        grid: {
                            color: colors.gray,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            color: colors.text,
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    }
                }
            }
        });

        // 6. Cash Flow Composition (Detailed Bar with Hover)
        new Chart(document.getElementById('detailedCashFlowChart'), {
            type: 'bar',
            data: {
                labels: kpi.monthly_income.map(d => d.month),
                datasets: [{
                        label: 'Income',
                        data: kpi.monthly_income.map(d => d.value),
                        backgroundColor: colors.green,
                        hoverBackgroundColor: '#3aa140',
                        borderRadius: 4,
                        barPercentage: 0.6
                    },
                    {
                        label: 'Expenses',
                        data: kpi.monthly_expenses.map(d => d.value),
                        backgroundColor: colors.red,
                        hoverBackgroundColor: '#c0392b',
                        borderRadius: 4,
                        barPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: colors.navy,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        stacked: false,
                        ticks: {
                            color: colors.text,
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            color: colors.gray,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            color: colors.text,
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    }
                }
            }
        });

        // 7. Top Accounts Chart
        new Chart(document.getElementById('topAccountsChart'), {
            type: 'bar',
            data: {
                labels: kpi.top_accounts.map(acc => acc.name),
                datasets: [{
                    label: 'Balance',
                    data: kpi.top_accounts.map(acc => acc.balance),
                    backgroundColor: kpi.top_accounts.map(acc => acc.balance >= 0 ? colors.green : colors
                        .red),
                    borderRadius: 4,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: colors.navy,
                        callbacks: {
                            label: (ctx) => 'Balance: ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: colors.text,
                            font: {
                                size: 10
                            },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            color: colors.gray,
                            borderDash: [4, 4]
                        },
                        ticks: {
                            color: colors.text,
                            callback: (val) => val.toLocaleString()
                        }
                    }
                }
            }
        });

        // 8. Category Breakdown Pie Chart
        new Chart(document.getElementById('categoryChart'), {
            type: 'pie',
            data: {
                labels: kpi.category_data.map(cat => cat.category),
                datasets: [{
                    data: kpi.category_data.map(cat => Math.abs(cat.total)),
                    backgroundColor: [colors.blue, colors.green, colors.red, colors.purple, colors.navy],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 2000,
                    easing: 'easeOutCirc'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: colors.navy,
                        callbacks: {
                            label: (ctx) => ctx.label + ': ' + Number(ctx.raw).toLocaleString()
                        }
                    }
                }
            }
        });
    </script>
@endpush
