@extends('layouts.auth')
@section('title', 'Kpi Dashboard')
@section('content')
    <div class="mb-8">
        <div class="p-3">

            <div class="flex justify-end mb-3.5">
                <div class="relative inline-block">
                    <form method="GET">
                        <select name="per_date" onchange="this.form.submit()"
                            class="appearance-none bg-[#F6F9FC] border-2 border-[#0078C8] text-[#0078C8] font-semibold px-4 py-1 rounded-[3px] cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <option value="">Select option</option>
                            @foreach (config('default.per_date') as $key => $value)
                                <option value="{{ $value }}" {{ request('per_date') == $value ? 'selected' : '' }}>
                                    {{ $key }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[#0078C8]">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 ">
                <!-- Gross Profit Margin Card -->
                <div class="bg-white border border-gray-300 p-4 rounded-[10px]">
                    <div class="flex justify-between items-center mb-8">
                        <div></div>
                        <h2 class="text-lg text-center font-semibold text-black">Gross Profit Margin</h2>
                    </div>
                    <div class="flex justify-center items-center">
                        <div class="relative w-48 h-48">
                            <canvas id="grossProfitChart"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-5xl font-bold text-black">
                                    {{ number_format($kpis['gross_profit_margin'], 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-2 text-sm">
                        @php
                            $change = $kpis['gross_profit_margin'] - $kpis['gross_profit_margin_previous'];
                            $changePercent =
                                $kpis['gross_profit_margin_previous'] > 0
                                    ? ($change / $kpis['gross_profit_margin_previous']) * 100
                                    : 0;
                        @endphp
                        <span class="{{ $change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $change >= 0 ? '+' : '' }}{{ number_format($changePercent, 1) }}%
                            vs previous period
                        </span>
                    </div>
                </div>
                <!-- Net Profit Card -->
                <div class="bg-white border border-gray-300 p-4 rounded-[10px]">
                    <div class="flex justify-between items-center mb-8">
                        <div></div>
                        <h2 class="text-lg font-semibold text-black">Net Profit</h2>
                    </div>
                    <div class="flex justify-center items-center">
                        <div class="relative w-48 h-48">
                            <canvas id="netProfitChart"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-5xl font-bold text-black">
                                    {{ number_format($kpis['net_profit'], 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-2 text-sm">
                        @php
                            $change = $kpis['net_profit'] - $kpis['net_profit_previous'];
                            $changePercent =
                                $kpis['net_profit_previous'] > 0 ? ($change / $kpis['net_profit_previous']) * 100 : 0;
                        @endphp
                        <span class="{{ $change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $change >= 0 ? '+' : '' }}{{ number_format($changePercent, 1) }}%
                            vs previous period
                        </span>
                    </div>
                </div>
            </div>

            {{-- Income and Expenses Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5  mt-5">
                <!-- Total Income Card -->
                <div class="bg-white border border-gray-300 p-6 rounded-[10px] ">
                    <div class="flex justify-between items-center mb-6">
                        <div></div>
                        <h2 class="text-lg font-semibold text-black">Total income</h2>
                    </div>
                    <div class="mb-4">
                        <div class="text-right mb-1">
                            <span class="text-3xl font-bold text-black">
                                {{ number_format($kpis['total_income'], 0) }}
                            </span>
                        </div>
                        <div
                            class="text-right text-sm {{ $kpis['total_income'] - $kpis['total_income_previous'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            @php
                                $change = $kpis['total_income'] - $kpis['total_income_previous'];
                                $changePercent =
                                    $kpis['total_income_previous'] > 0
                                        ? ($change / $kpis['total_income_previous']) * 100
                                        : 0;
                            @endphp
                            {{ $change >= 0 ? '+' : '' }}{{ number_format($changePercent, 1) }}%
                            vs previous period
                        </div>
                    </div>
                    <div class="h-32">
                        <canvas id="incomeChart"></canvas>
                    </div>
                </div>

                <!-- Total Expenses Card -->
                <div class="bg-white border border-gray-300 p-6 rounded-[10px] ">
                    <div class="flex justify-between items-center mb-6">
                        <div></div>
                        <h2 class="text-lg font-semibold text-black">Total expenses</h2>
                    </div>
                    <div class="mb-4">
                        <div class="text-right mb-1">
                            <span class="text-3xl font-bold text-black">
                                {{ number_format($kpis['total_expenses'], 0) }}
                            </span>
                        </div>
                        <div
                            class="text-right text-sm {{ $kpis['total_expenses'] - $kpis['total_expenses_previous'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
                            @php
                                $change = $kpis['total_expenses'] - $kpis['total_expenses_previous'];
                                $changePercent =
                                    $kpis['total_expenses_previous'] > 0
                                        ? ($change / $kpis['total_expenses_previous']) * 100
                                        : 0;
                            @endphp
                            {{ $change <= 0 ? '' : '+' }}{{ number_format($changePercent, 1) }}%
                            vs previous period
                        </div>
                    </div>
                    <div class="h-32">
                        <canvas id="expensesChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Accounts Receivable and Operating Cash Flow Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5  mt-5">
                <!-- Accounts Receivable Card -->
                <div class="bg-white border border-gray-300 p-6 rounded-[10px] ">
                    <div class="flex justify-between items-center mb-6">
                        <div></div>
                        <h2 class="text-lg font-semibold text-black">Accounts receivable</h2>
                    </div>
                    <div class="mb-4">
                        <div class="text-right mb-1">
                            <span class="text-3xl font-bold text-black">
                                {{ number_format($kpis['accounts_receivable'], 0) }}
                            </span>
                        </div>
                        <div
                            class="text-right text-sm {{ $kpis['accounts_receivable'] - $kpis['accounts_receivable_previous'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
                            @php
                                $change = $kpis['accounts_receivable'] - $kpis['accounts_receivable_previous'];
                                $changePercent =
                                    $kpis['accounts_receivable_previous'] > 0
                                        ? ($change / $kpis['accounts_receivable_previous']) * 100
                                        : 0;
                            @endphp
                            {{ $change <= 0 ? '' : '+' }}{{ number_format($changePercent, 1) }}%
                            vs previous period
                        </div>
                    </div>
                    <div class="h-32">
                        <canvas id="accountRelivable"></canvas>
                    </div>
                </div>

                <!-- Operating Cash Flow Card -->
                <div class="bg-white border border-gray-300 p-6 rounded-[10px] ">
                    <div class="flex justify-between items-center mb-6">
                        <div></div>
                        <h2 class="text-lg font-semibold text-black">Operating cash flow</h2>
                    </div>
                    <div class="mb-4">
                        <div class="text-right mb-1">
                            <span class="text-3xl font-bold text-black">
                                {{ number_format($kpis['operating_cash_flow'], 0) }}
                            </span>
                        </div>
                        <div
                            class="text-right text-sm {{ $kpis['operating_cash_flow'] - $kpis['operating_cash_flow_previous'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            @php
                                $change = $kpis['operating_cash_flow'] - $kpis['operating_cash_flow_previous'];
                                $changePercent =
                                    $kpis['operating_cash_flow_previous'] > 0
                                        ? ($change / $kpis['operating_cash_flow_previous']) * 100
                                        : 0;
                            @endphp
                            {{ $change >= 0 ? '+' : '' }}{{ number_format($changePercent, 1) }}%
                            vs previous period
                        </div>
                    </div>
                    <div class="h-32">
                        <canvas id="operationCashFlow"></canvas>
                    </div>
                </div>
            </div>

            {{-- Gauge Charts Row --}}
            <div class="grid grid-cols-1 md:grid-cols-3   mt-5">
                <!-- Total Accounts Payable Card -->
                <div class="bg-white border border-gray-300   rounded-l-[10px]">
                    <div class="text-center border-b border-gray-300 py-3 mb-2">
                        <h2 class="text-lg font-semibold text-black">Total Accounts Payable</h2>
                        <div class="text-2xl font-bold text-black">
                            {{ number_format($kpis['accounts_payable'], 0) }}
                        </div>
                    </div>
                    <div class="mb-4 p-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm font-semibold text-black">Current Ratio</div>
                                <div class="text-xs text-gray-600">Current Assets / Current Liabilities</div>
                            </div>
                        </div>
                    </div>
                    <div class="relative h-40">
                        <canvas id="currentRatioChart"></canvas>
                        <div class="absolute bottom-0 left-0 right-0 flex justify-between px-8 text-xs text-gray-600">
                            <span>0</span>
                            <span>3</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Ratio Card -->
                <div class="bg-white border border-gray-300 ">
                    <div class="text-center mb-2 border-b border-gray-300  py-3">
                        <h2 class="text-lg font-semibold text-black">Quick Ratio</h2>
                        <div class="text-2xl font-bold text-[#00BCD4]">
                            {{ number_format($kpis['quick_ratio'], 2) }}:1
                        </div>
                    </div>
                    <div class="mb-4 p-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm font-semibold text-black">Quick Ratio</div>
                                <div class="text-xs text-gray-600">(Current Assets - Inventory) / Current Liabilities</div>
                            </div>
                        </div>
                    </div>
                    <div class="relative h-40">
                        <canvas id="quickRatioChart"></canvas>
                        <div class="absolute bottom-0 left-0 right-0 flex justify-between px-8 text-xs text-gray-600">
                            <span>0</span>
                            <span>2</span>
                        </div>
                    </div>
                </div>

                <!-- Debt Equity Card -->
                <div class="bg-white border border-gray-300  rounded-r-[10px]">
                    <div class="text-center mb-2 border-b border-gray-300 py-3">
                        <h2 class="text-lg font-semibold text-black">Account Balance</h2>
                        <div class="text-2xl font-bold text-black">
                            {{ number_format($kpis['account_balance'], 0) }}
                        </div>
                    </div>
                    <div class="mb-4 p-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm font-semibold text-black">Period Summary</div>
                                <div class="text-xs text-gray-600">{{ $kpis['date_range']['formatted_start'] }} to
                                    {{ $kpis['date_range']['formatted_end'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="relative h-40">
                        <canvas id="accountBalanceGauge"></canvas>
                    </div>
                </div>
            </div>

            {{-- Quick Ratio, Current Ratio, and Account Balance Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5  mt-5">
                <!-- Quick Ratio and Current Ratio Card -->
                <div class="bg-white border border-gray-300 p-6 rounded-[10px]">
                    <div class="flex justify-end mb-4">
                    </div>

                    <!-- Quick Ratio -->
                    <div class="mb-6">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="bg-[#0078C8] p-3 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-black mb-1">Quick ratio</div>
                                <div class="text-2xl font-bold text-black">
                                    {{ number_format($kpis['quick_ratio'], 2) }}:1
                                </div>
                            </div>
                        </div>
                        <div class="ml-16">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    @php
                                        $quickRatioPercent = min(($kpis['quick_ratio'] / 2) * 100, 100);
                                    @endphp
                                    <div class="bg-[#0078C8] h-2 rounded-full" style="width: {{ $quickRatioPercent }}%">
                                    </div>
                                </div>
                                <span class="text-xs text-gray-600">Quick ratio goal 1.0 or higher</span>
                            </div>
                        </div>
                    </div>

                    <!-- Current Ratio -->
                    <div>
                        <div class="flex items-center gap-4 mb-3">
                            <div class="bg-[#0078C8] p-3 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-black mb-1">Current ratio</div>
                                <div class="text-2xl font-bold text-black">
                                    {{ number_format($kpis['current_ratio'], 2) }}:1
                                </div>
                            </div>
                        </div>
                        <div class="ml-16">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    @php
                                        $currentRatioPercent = min(($kpis['current_ratio'] / 3) * 100, 100);
                                    @endphp
                                    <div class="bg-[#B8D9F0] h-2 rounded-full"
                                        style="width: {{ $currentRatioPercent }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600">Current ratio goal 2.0 or higher</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Balance Card -->
                <div class="bg-white border border-gray-300 p-6 rounded-[10px]">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-black">Account Balance Trend</h2>
                    </div>
                    <div class="mb-4">
                        <div class="text-left mb-1">
                            <span class="text-3xl font-bold text-black">
                                {{ number_format($kpis['account_balance'], 0) }}
                            </span>
                        </div>
                    </div>
                    <div class="h-40">
                        <canvas id="accountBalanceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 mt-5">
                <div class="bg-white border border-gray-300 p-6 rounded-[10px]">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-black">Profit and Loss Summary</h2>
                        <div class="text-sm text-gray-600">
                            <div>Period: {{ $kpis['date_range']['formatted_start'] }} -
                                {{ $kpis['date_range']['formatted_end'] }}</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="text-sm text-gray-600 mb-2">Period Total</div>
                    </div>

                    <div class="h-[500px] relative">
                        <canvas id="profitLossChart"></canvas>
                    </div>

                    {{-- Legend --}}
                    <div class="flex justify-end gap-6 mt-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-8 bg-green-500"></div>
                            <div>
                                <div class="font-semibold">Net Income</div>
                                <div class="text-gray-600">
                                    {{ number_format($kpis['profit_loss_data']['net_income'], 0) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-black"></div>
                            <div>
                                <div class="font-semibold">Revenue</div>
                                <div class="text-gray-600">
                                    {{ number_format($kpis['profit_loss_data']['revenue'], 0) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-teal-500"></div>
                            <div>
                                <div class="font-semibold">COGS</div>
                                <div class="text-gray-600">
                                    {{ number_format($kpis['profit_loss_data']['cogs'], 0) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-green-600"></div>
                            <div>
                                <div class="font-semibold">Expenses</div>
                                <div class="text-gray-600">
                                    {{ number_format($kpis['profit_loss_data']['expenses'], 0) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const kpiData = @json($kpis);

        // Chart.js Configuration for Doughnut Charts
        const chartConfig = {
            type: 'doughnut',
            options: {
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                },
                responsive: true,
                maintainAspectRatio: true
            }
        };

        // Gross Profit Margin Chart
        const grossProfitCtx = document.getElementById('grossProfitChart').getContext('2d');
        new Chart(grossProfitCtx, {
            ...chartConfig,
            data: {
                datasets: [{
                    data: [
                        Math.min(kpiData.gross_profit_margin, 100),
                        Math.max(100 - kpiData.gross_profit_margin, 0)
                    ],
                    backgroundColor: ['#0066B3', '#B8D9F0'],
                    borderWidth: 0,
                    borderRadius: 0
                }]
            }
        });

        // Net Profit Chart
        const netProfitCtx = document.getElementById('netProfitChart').getContext('2d');
        const netProfitValue = Math.abs(kpiData.net_profit);
        const netProfitScale = Math.min(netProfitValue / 10000, 100);

        new Chart(netProfitCtx, {
            ...chartConfig,
            data: {
                datasets: [{
                    data: [
                        netProfitScale,
                        100 - netProfitScale
                    ],
                    backgroundColor: kpiData.net_profit >= 0 ? ['#0066B3', '#B8D9F0'] : ['#DC2626',
                        '#FECACA'
                    ],
                    borderWidth: 0,
                    borderRadius: 0
                }]
            }
        });

        // Bar Chart Configuration
        const barChartConfig = {
            type: 'bar',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString();
                            }
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
                            color: '#666'
                        }
                    },
                    y: {
                        display: false,
                        beginAtZero: true
                    }
                }
            }
        };

        // Total Income Chart
        const incomeCtx = document.getElementById('incomeChart').getContext('2d');
        new Chart(incomeCtx, {
            ...barChartConfig,
            data: {
                labels: kpiData.monthly_income.map(item => item.month),
                datasets: [{
                    data: kpiData.monthly_income.map(item => item.value),
                    backgroundColor: '#14B8A6',
                    borderRadius: 4,
                    barThickness: 20
                }]
            }
        });

        // Total Expenses Chart
        const expensesCtx = document.getElementById('expensesChart').getContext('2d');
        new Chart(expensesCtx, {
            ...barChartConfig,
            data: {
                labels: kpiData.monthly_expenses.map(item => item.month),
                datasets: [{
                    data: kpiData.monthly_expenses.map(item => item.value),
                    backgroundColor: '#DC2626',
                    borderRadius: 4,
                    barThickness: 20
                }]
            }
        });

        const accountRelivableCtx = document.getElementById('accountRelivable').getContext('2d');
        new Chart(accountRelivableCtx, {
            ...barChartConfig,
            data: {
                labels: kpiData.monthly_income.map(item => item.month),
                datasets: [{
                    data: kpiData.monthly_income.map(item => Math.max(item.value * 0.3, 0)),
                    backgroundColor: '#14B8A6',
                    borderRadius: 4,
                    barThickness: 20
                }]
            }
        });

        const operationCashFlowCtx = document.getElementById('operationCashFlow').getContext('2d');
        new Chart(operationCashFlowCtx, {
            ...barChartConfig,
            data: {
                labels: kpiData.monthly_income.map((item, index) => item.month),
                datasets: [{
                    data: kpiData.monthly_income.map((item, index) => {
                        const income = item.value;
                        const expenses = kpiData.monthly_expenses[index]?.value || 0;
                        return income - expenses;
                    }),
                    backgroundColor: '#14B8A6',
                    borderRadius: 4,
                    barThickness: 20
                }]
            }
        });

        // Gauge Chart Configuration
        function createGaugeChart(ctx, value, maxValue, color, label) {
            const percentage = Math.min((value / maxValue) * 100, 100);
            const angle = (percentage / 100) * 180;

            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [value, Math.max(maxValue - value, 0)],
                        backgroundColor: [color, '#E5E7EB'],
                        borderWidth: 0,
                        circumference: 180,
                        rotation: 270
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    }
                },
                plugins: [{
                    id: 'gaugeNeedle',
                    afterDatasetDraw(chart) {
                        const {
                            ctx,
                            chartArea: {
                                width,
                                height
                            }
                        } = chart;
                        const cx = width / 2;
                        const cy = height;
                        const radius = Math.min(width, height * 2) / 2.5;

                        ctx.save();
                        ctx.translate(cx, cy);
                        ctx.rotate((270 + angle) * Math.PI / 180);

                        // Draw needle
                        ctx.beginPath();
                        ctx.moveTo(0, -8);
                        ctx.lineTo(radius * 0.7, 0);
                        ctx.lineTo(0, 8);
                        ctx.fillStyle = '#000';
                        ctx.fill();

                        ctx.restore();

                        // Draw center circle
                        ctx.beginPath();
                        ctx.arc(cx, cy, 8, 0, 2 * Math.PI);
                        ctx.fillStyle = '#000';
                        ctx.fill();

                        // Draw value text
                        ctx.fillStyle = '#000';
                        ctx.font = 'bold 20px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText(label, cx, cy - 30);
                    }
                }]
            });
        }

        // Current Ratio Chart
        const currentRatioCtx = document.getElementById('currentRatioChart').getContext('2d');
        createGaugeChart(currentRatioCtx, kpiData.current_ratio, 3, '#000000', kpiData.current_ratio.toFixed(2));

        // Quick Ratio Chart
        const quickRatioCtx = document.getElementById('quickRatioChart').getContext('2d');
        createGaugeChart(quickRatioCtx, kpiData.quick_ratio, 2, '#00BCD4', kpiData.quick_ratio.toFixed(2));

        // Account Balance Gauge
        const accountBalanceGaugeCtx = document.getElementById('accountBalanceGauge').getContext('2d');
        const maxBalance = Math.max(kpiData.account_balance * 1.5, 100000);
        const balancePercent = Math.min((kpiData.account_balance / maxBalance) * 100, 100);

        new Chart(accountBalanceGaugeCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [balancePercent, 100 - balancePercent],
                    backgroundColor: ['#14B8A6', '#E5E7EB'],
                    borderWidth: 0,
                    circumference: 180,
                    rotation: 270
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });

        // Account Balance Area Chart
        const accountBalanceCtx = document.getElementById('accountBalanceChart').getContext('2d');

        new Chart(accountBalanceCtx, {
            type: 'line',
            data: {
                labels: kpiData.monthly_balance.map(item => `${item.month} ${item.year}`),
                datasets: [{
                    data: kpiData.monthly_balance.map(item => item.balance),
                    backgroundColor: function(context) {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                        gradient.addColorStop(0, 'rgba(20, 184, 166, 0.5)');
                        gradient.addColorStop(1, 'rgba(20, 184, 166, 0.05)');
                        return gradient;
                    },
                    borderColor: '#14B8A6',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#14B8A6'
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
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 0,
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        display: false,
                        beginAtZero: false
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Profit and Loss Summary Chart
        const profitLossCtx = document.getElementById('profitLossChart').getContext('2d');

        new Chart(profitLossCtx, {
            type: 'bar',
            data: {
                labels: ['Revenue', 'COGS', 'Expenses', 'Net Income'],
                datasets: [{
                    label: 'Amount',
                    data: [
                        kpiData.profit_loss_data.revenue,
                        -kpiData.profit_loss_data.cogs,
                        -kpiData.profit_loss_data.expenses,
                        kpiData.profit_loss_data.net_income
                    ],
                    backgroundColor: [
                        '#000000',
                        '#14B8A6',
                        '#DC2626',
                        kpiData.profit_loss_data.net_income >= 0 ? '#22C55E' : '#DC2626'
                    ],
                    barThickness: 60,
                    borderRadius: 4
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
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 10,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const value = Math.abs(context.parsed.y);
                                label += value.toLocaleString();
                                return label;
                            }
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
                                size: 12
                            },
                            color: '#000'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#666',
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        },
                        grid: {
                            color: '#E5E7EB',
                            borderColor: '#000',
                            borderWidth: 1
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                }
            }
        });
    </script>
@endpush
