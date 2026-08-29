@extends('layouts.auth')
@section('title')
    Account Dashboard
@endsection

@push('styles')
    <style>
        [data-dashboard-editing="true"] .dashboard-widget-toolbar {
            display: flex;
        }

        [data-dashboard-editing="true"] .dashboard-widget {
            outline: 1px dashed rgba(0, 120, 200, 0.45);
            outline-offset: 3px;
        }

        .dashboard-widget.sortable-ghost {
            opacity: 0.45;
        }

        .dashboard-widget.sortable-chosen {
            transform: scale(1.01);
        }
    </style>
@endpush

@section('content')
    @php
        $dashboardWidgetMeta = $dashboardWidgetMeta ?? [];
        $dashboardLayout = $dashboardLayout ?? [
            'render_order' => array_keys($dashboardWidgetMeta),
            'hidden' => [],
        ];
    @endphp

    <div class="p-4">
        @if (session('success'))
            <div class="mb-4 rounded-[5px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- ── Period Filter ──────────────────────────────────────────── --}}
        <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-center lg:justify-between">
            <h1 class="text-lg font-bold text-gray-800">Overview</h1>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <form method="POST" action="{{ route('account.dashboard.sync-document-balances') }}"
                    onsubmit="return confirm('Sync sales and bill document balances from the ledger for this company?')">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-[3px] border border-emerald-600 bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        <i class="fa-solid fa-arrows-rotate text-xs"></i>
                        Sync Balances
                    </button>
                </form>
                <button type="button" id="dashboardCustomizeToggle"
                    class="inline-flex items-center justify-center gap-2 rounded-[3px] border border-[#0078C8] bg-white px-3 py-1.5 text-sm font-semibold text-[#0078C8] hover:bg-[#F6F9FC] focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <i class="fa-solid fa-sliders text-xs"></i>
                    Customize Dashboard
                </button>
                <button type="button" id="dashboardResetButton"
                    class="inline-flex items-center justify-center gap-2 rounded-[3px] border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <i class="fa-solid fa-rotate-left text-xs"></i>
                    Reset Dashboard
                </button>
                <div class="relative inline-block">
                <form method="GET" class="flex items-center gap-2">
                    <div class="relative">
                        <select id="main_per_date" name="per_date" onchange="toggleDashboardCustomDates(this, '')"
                            class="appearance-none bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-4 py-1.5 pr-10 rounded-[3px] cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm">
                            <option value="">This Month</option>
                            @foreach (config('default.per_date') as $key => $value)
                                <option value="{{ $value }}" {{ request('per_date') == $value ? 'selected' : '' }}>
                                    {{ $key }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-[#0078C8]">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </div>
                    </div>

                    <div id="custom-dates-main_per_date" class="{{ request('per_date') == 'CUSTOM' ? 'flex' : 'hidden' }} items-center gap-2">
                        <input type="text" name="start_date" value="{{ request('start_date') }}" placeholder="dd/mm/yyyy"
                            class="date bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-2 py-1 rounded-[3px] text-xs focus:outline-none">
                        <span class="text-[#0078C8] font-bold text-xs">to</span>
                        <input type="text" name="end_date" value="{{ request('end_date') }}" placeholder="dd/mm/yyyy"
                            class="date bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-2 py-1 rounded-[3px] text-xs focus:outline-none">
                        <button type="submit" class="bg-[#0078C8] text-white px-3 py-1 rounded-[3px] text-xs font-bold hover:bg-blue-700 transition-colors">
                            Go
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <div id="dashboardCustomizePanel" class="hidden mb-4 rounded-[5px] border border-gray-200 bg-white p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-bold text-gray-800">Hidden Cards</p>
                    <p class="text-xs text-gray-500">Restore cards you have hidden from this dashboard.</p>
                </div>
                <div id="dashboardSaveStatus" class="text-xs font-semibold text-gray-400"></div>
            </div>
            <div id="dashboardHiddenList" class="mt-3 flex flex-wrap gap-2"></div>
        </div>

        <div id="accountDashboardGrid" data-dashboard-editing="false" class="grid grid-cols-1 gap-4 md:grid-cols-6 xl:grid-cols-12 mb-6" role="list"></div>

        {{-- ── Anomaly Alert ──────────────────────────────────────────── --}}
        @if (($anomalySummary['critical'] ?? 0) > 0 || ($anomalySummary['warning'] ?? 0) > 0)
            <div class="dashboard-source-widget md:col-span-6 xl:col-span-12 mb-4 border rounded-[5px] p-4 flex items-center justify-between {{ ($anomalySummary['critical'] ?? 0) > 0 ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200' }}"
                data-dashboard-widget="anomaly_alert" data-widget-label="Anomaly Alert" data-widget-size="full" role="listitem">
                <div class="flex items-center gap-3">
                    <div class="rounded-full p-2 {{ ($anomalySummary['critical'] ?? 0) > 0 ? 'bg-red-100' : 'bg-yellow-100' }}">
                        <svg class="w-5 h-5 {{ ($anomalySummary['critical'] ?? 0) > 0 ? 'text-red-600' : 'text-yellow-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold {{ ($anomalySummary['critical'] ?? 0) > 0 ? 'text-red-800' : 'text-yellow-800' }}">
                            {{ $anomalySummary['total'] }} Transaction Anomal{{ $anomalySummary['total'] === 1 ? 'y' : 'ies' }} Detected
                        </p>
                        <p class="text-xs {{ ($anomalySummary['critical'] ?? 0) > 0 ? 'text-red-600' : 'text-yellow-600' }}">
                            @if (($anomalySummary['critical'] ?? 0) > 0)
                                {{ $anomalySummary['critical'] }} critical
                            @endif
                            @if (($anomalySummary['warning'] ?? 0) > 0)
                                {{ ($anomalySummary['critical'] ?? 0) > 0 ? ', ' : '' }}{{ $anomalySummary['warning'] }} warning{{ $anomalySummary['warning'] !== 1 ? 's' : '' }}
                            @endif
                            — last 90 days
                        </p>
                    </div>
                </div>
                <a href="{{ route('account.anomaly-detection') }}"
                    class="px-4 py-2 text-sm font-medium rounded-[3px] transition-all {{ ($anomalySummary['critical'] ?? 0) > 0 ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-yellow-600 text-white hover:bg-yellow-700' }}">
                    Review
                </a>
            </div>
        @endif

        @if (($payrollAccountingRequests ?? collect())->isNotEmpty())
            <div class="dashboard-source-widget md:col-span-6 xl:col-span-12 mb-4 rounded-[5px] border border-blue-200 bg-blue-50 p-4"
                data-dashboard-widget="payroll_accounting_requests" data-widget-label="Payroll Requests" data-widget-size="full" role="listitem">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-blue-100 p-2 text-blue-700">
                            <i class="fa-solid fa-bell text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-blue-900">
                                {{ $payrollAccountingRequests->count() }} Payroll Pay {{ $payrollAccountingRequests->count() === 1 ? 'Run' : 'Runs' }} Awaiting Accounting
                            </p>
                            <p class="text-xs text-blue-700">Payroll has notified accounting. Review and post the journal entries from here.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto rounded-[5px] border border-blue-100 bg-white">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Pay Period</th>
                                <th class="px-4 py-3 text-left">Payment Date</th>
                                <th class="px-4 py-3 text-right">Employees</th>
                                <th class="px-4 py-3 text-right">Net Pay</th>
                                <th class="px-4 py-3 text-left">Notified By</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($payrollAccountingRequests as $requestPayRun)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-800">
                                        {{ $requestPayRun->period_start->format('M d') }} - {{ $requestPayRun->period_end->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $requestPayRun->payment_date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $requestPayRun->payslips_count }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ numberFormat($requestPayRun->net_pay_total ?? 0) }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $requestPayRun->accountingNotifiedBy->first_name . ' ' . $requestPayRun->accountingNotifiedBy->last_name ?? 'Payroll' }}
                                        @if ($requestPayRun->accounting_notified_at)
                                            <span class="block text-[11px] text-gray-400">{{ $requestPayRun->accounting_notified_at->format('M d, Y H:i') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('account.payroll-accounting.post', $requestPayRun->id) }}"
                                            onsubmit="return confirm('Post this payroll pay run to accounting now?')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center justify-center gap-2 rounded-[3px] bg-[#0078C8] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#006bb0]">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                                Post to Accounting
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ── KPI Chart Cards (4 cols) ────────────────────────────────── --}}
        <div class="dashboard-source-container grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
            <div class="dashboard-source-widget md:col-span-3 xl:col-span-3" data-dashboard-widget="total_revenue" data-widget-label="Total Revenue" data-widget-size="quarter" role="listitem">
            <x-acccount.dashboard-card
                title="Total Revenue"
                total="{{ numberFormat($totalRevenue) }}"
                subtitle="Period sales income"
                revenueChart="revenueChartRevenue"
                date_filter="dateFilterRevenue"
                :data="$revenueChartData"
                :labels="$chartLabels"
                color="#10B981"
                colorRgba="16, 185, 129" />
            </div>

            <div class="dashboard-source-widget md:col-span-3 xl:col-span-3" data-dashboard-widget="gross_profit" data-widget-label="Gross Profit" data-widget-size="quarter" role="listitem">
            <x-acccount.dashboard-card
                title="Gross Profit"
                total="{{ numberFormat($grossProfit) }}"
                subtitle="Margin: {{ $grossMargin }}%"
                revenueChart="grossProfitChart"
                date_filter="dateFilterGross"
                :data="$grossProfitChartData"
                :labels="$chartLabels"
                color="#0078C8"
                colorRgba="0, 120, 200" />
            </div>

            <div class="dashboard-source-widget md:col-span-3 xl:col-span-3" data-dashboard-widget="net_income" data-widget-label="Net Income" data-widget-size="quarter" role="listitem">
            <x-acccount.dashboard-card
                title="Net Income"
                total="{{ numberFormat($netIncome) }}"
                subtitle="Net margin: {{ $netMargin }}%"
                revenueChart="incomeChart"
                date_filter="dateFilterIncome"
                :data="$incomeChartData"
                :labels="$chartLabels"
                color="#6554C0"
                colorRgba="101, 84, 192" />
            </div>

            <div class="dashboard-source-widget md:col-span-4 xl:col-span-3" data-dashboard-widget="operating_expenses" data-widget-label="Operating Expenses" data-widget-size="quarter" role="listitem">
            <x-acccount.dashboard-card
                title="Operating Expenses"
                total="{{ numberFormat($totalOperatingExpenses) }}"
                subtitle="Operating costs only"
                revenueChart="operatingExpensesChart"
                date_filter="dateFilterOperatingExpenses"
                :data="$operatingExpensesChartData"
                :labels="$chartLabels"
                color="#F59E0B"
                colorRgba="245, 158, 11" />
            </div>

            <div class="dashboard-source-widget md:col-span-3 xl:col-span-3" data-dashboard-widget="finance_tax_costs" data-widget-label="Finance & Tax Costs" data-widget-size="quarter" role="listitem">
            <x-acccount.dashboard-card
                title="Finance & Tax Costs"
                total="{{ numberFormat($totalFinanceCosts + $totalTaxExpenses) }}"
                subtitle="Finance + Tax expenses"
                revenueChart="financeTaxCostsChart"
                date_filter="dateFilterFinanceTaxCosts"
                :data="$financeTaxCostsChartData"
                :labels="$chartLabels"
                color="#8B5CF6"
                colorRgba="139, 92, 246" />
            </div>

            <div class="dashboard-source-widget md:col-span-3 xl:col-span-3" data-dashboard-widget="total_expenses" data-widget-label="Total Expenses" data-widget-size="quarter" role="listitem">
            <x-acccount.dashboard-card
                title="Total Expenses"
                total="{{ numberFormat($totalExpenses) }}"
                subtitle="All operating + finance + tax costs"
                revenueChart="expensesChart"
                date_filter="dateFilterExpenses"
                :data="$expenseChartData"
                :labels="$chartLabels"
                color="#EF4444"
                colorRgba="239, 68, 68" />
            </div>
        </div>

        {{-- ── Quick Stat Cards (Cash, AR, AP) ───────────────────────── --}}
        <div class="dashboard-source-container grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            {{-- Cash Balance --}}
            <div class="dashboard-source-widget md:col-span-2 xl:col-span-4 bg-white rounded-[5px] border border-gray-200 p-5 flex items-center gap-4"
                data-dashboard-widget="cash_balance" data-widget-label="Cash Balance" data-widget-size="third" role="listitem">
                <div class="w-11 h-11 rounded-full bg-[#E6F3FB] flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cash Balance</p>
                    <p class="text-xl font-bold text-gray-800">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($totalCashBalance) }}</p>
                    <p class="text-xs text-gray-400">Across all bank accounts</p>
                </div>
            </div>

            {{-- AR Outstanding --}}
            <div class="dashboard-source-widget md:col-span-2 xl:col-span-4 bg-white rounded-[5px] border border-gray-200 p-5 flex items-center gap-4"
                data-dashboard-widget="ar_outstanding" data-widget-label="AR Outstanding" data-widget-size="third" role="listitem">
                <div class="w-11 h-11 rounded-full bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">AR Outstanding</p>
                    <p class="text-xl font-bold text-gray-800">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($arOutstanding) }}</p>
                    <p class="text-xs text-amber-500">{{ $overdueCount }} unpaid invoice{{ $overdueCount != 1 ? 's' : '' }}</p>
                </div>
            </div>

            {{-- AP Outstanding --}}
            <div class="dashboard-source-widget md:col-span-2 xl:col-span-4 bg-white rounded-[5px] border border-gray-200 p-5 flex items-center gap-4"
                data-dashboard-widget="ap_outstanding" data-widget-label="AP Outstanding" data-widget-size="third" role="listitem">
                <div class="w-11 h-11 rounded-full bg-red-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">AP Outstanding</p>
                    <p class="text-xl font-bold text-gray-800">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($apOutstanding) }}</p>
                    <p class="text-xs text-red-500">Amount owed to vendors</p>
                </div>
            </div>
        </div>

        {{-- ── DSO / DPO + Top 5 Overdue ──────────────────────────────── --}}
        <div class="dashboard-source-container grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            {{-- DSO --}}
            <div class="dashboard-source-widget md:col-span-2 xl:col-span-4 bg-white rounded-[5px] border border-gray-200 p-5"
                data-dashboard-widget="dso" data-widget-label="Days Sales Outstanding" data-widget-size="third" role="listitem">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Days Sales Outstanding (DSO)</p>
                <p class="text-3xl font-bold text-[#0078C8] mt-2">{{ $dso }}<span class="text-base font-normal text-gray-400 ml-1">days</span></p>
                <p class="text-xs text-gray-400 mt-1">Avg days to collect receivables</p>
                <div class="mt-3 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $dso <= 30 ? 'bg-green-500' : ($dso <= 60 ? 'bg-amber-400' : 'bg-red-500') }}"
                         style="width: {{ min(100, ($dso / 90) * 100) }}%"></div>
                </div>
                <p class="text-xs mt-1 {{ $dso <= 30 ? 'text-green-600' : ($dso <= 60 ? 'text-amber-600' : 'text-red-500') }}">
                    {{ $dso <= 30 ? 'Excellent' : ($dso <= 60 ? 'Monitor closely' : 'Action required') }}
                </p>
            </div>

            {{-- DPO --}}
            <div class="dashboard-source-widget md:col-span-2 xl:col-span-4 bg-white rounded-[5px] border border-gray-200 p-5"
                data-dashboard-widget="dpo" data-widget-label="Days Payable Outstanding" data-widget-size="third" role="listitem">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Days Payable Outstanding (DPO)</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">{{ $dpo }}<span class="text-base font-normal text-gray-400 ml-1">days</span></p>
                <p class="text-xs text-gray-400 mt-1">Avg days to pay vendors</p>
                <div class="mt-3 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-purple-400"
                         style="width: {{ min(100, ($dpo / 90) * 100) }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Higher = better cash retention</p>
            </div>

            {{-- Top 5 Overdue --}}
            <div class="dashboard-source-widget md:col-span-2 xl:col-span-4 bg-white rounded-[5px] border border-gray-200 p-5"
                data-dashboard-widget="top_overdue_customers" data-widget-label="Top Overdue Customers" data-widget-size="third" role="listitem">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Top Overdue Customers</p>
                @forelse ($topOverdueCustomers as $row)
                <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $row->customer->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-400">{{ $row->invoice_count }} invoice{{ $row->invoice_count != 1 ? 's' : '' }}</p>
                    </div>
                    <span class="text-sm font-bold text-red-600">{{ numberFormat($row->total_overdue) }}</span>
                </div>
                @empty
                <p class="text-xs text-gray-400 text-center py-4">No overdue invoices</p>
                @endforelse
                @if ($topOverdueCustomers->isNotEmpty())
                <a href="{{ route('account.customer-statements.index') }}"
                   class="mt-3 block text-center text-xs text-[#0078C8] hover:underline">View all statements →</a>
                @endif
            </div>
        </div>

        {{-- ── Revenue vs Expenses + Cash Flow Charts ─────────────────── --}}
        <div class="dashboard-source-container grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- Revenue vs Expenses Trend --}}
            <div class="dashboard-source-widget md:col-span-3 xl:col-span-6 bg-white rounded-[5px] border border-gray-200"
                data-dashboard-widget="revenue_expenses" data-widget-label="Revenue vs Expenses" data-widget-size="half" role="listitem">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-black text-sm font-bold">Revenue vs Expenses</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Daily comparison for selected period</p>
                </div>
                <div class="p-5" style="height:260px">
                    <canvas id="revVsExpChart"></canvas>
                </div>
            </div>

            {{-- Cash Flow --}}
            <div class="dashboard-source-widget md:col-span-3 xl:col-span-6 bg-white rounded-[5px] border border-gray-200"
                data-dashboard-widget="cash_flow" data-widget-label="Cash In & Out" data-widget-size="half" role="listitem">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-black text-sm font-bold">Cash In &amp; Out</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Actual movement from Bank Accounts</p>
                </div>
                <div class="p-5" style="height:260px">
                    <canvas id="cashFlowChart"></canvas>
                </div>
            </div>
        </div>

        {{-- ── Invoices & Bills Tables ──────────────────────────────────── --}}
        <div class="dashboard-source-container grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            {{-- Invoices --}}
            <div class="dashboard-source-widget md:col-span-3 xl:col-span-6 bg-white rounded-[5px] border border-gray-200"
                data-dashboard-widget="invoices" data-widget-label="Invoices" data-widget-size="half" role="listitem">
                <div class="flex justify-between items-center p-5 border-b border-gray-100">
                    <h1 class="text-black text-sm font-bold">Invoices</h1>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <form method="GET" class="flex items-center gap-2">
                                <div class="relative">
                                    <select id="sale_per_date_select" name="sale_per_date" onchange="toggleDashboardCustomDates(this, 'sale_')"
                                        class="appearance-none bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-3 py-1 pr-10 rounded-[3px] cursor-pointer focus:outline-none text-xs">
                                        <option value="">All time</option>
                                        @foreach (config('default.per_date') as $key => $value)
                                            <option value="{{ $value }}"
                                                {{ request('sale_per_date') == $value ? 'selected' : '' }}>{{ $key }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-[#0078C8]">
                                        <svg class="fill-current h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                        </svg>
                                    </div>
                                </div>

                                <div id="custom-dates-sale_per_date_select" class="{{ request('sale_per_date') == 'CUSTOM' ? 'flex' : 'hidden' }} items-center gap-2">
                                    <input type="text" name="sale_start_date" value="{{ request('sale_start_date') }}" placeholder="dd/mm/yyyy"
                                        class="date bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-1 py-0.5 rounded-[2px] text-[10px] focus:outline-none w-24">
                                    <input type="text" name="sale_end_date" value="{{ request('sale_end_date') }}" placeholder="dd/mm/yyyy"
                                        class="date bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-1 py-0.5 rounded-[2px] text-[10px] focus:outline-none w-24">
                                    <button type="submit" class="bg-[#0078C8] text-white px-2 py-0.5 rounded-[2px] text-[10px] font-bold">Go</button>
                                </div>
                            </form>
                        </div>
                        <button onclick="window.location.href='{{ route('account.sale-invoice.create') }}'"
                            class="bg-[#0078C8] rounded-[3px] text-white font-semibold px-3 py-1 flex items-center gap-1 text-xs">
                            <span class="text-base leading-none">+</span> New Invoice
                        </button>
                    </div>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Invoice #</th>
                            <th class="text-left py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                            <th class="text-right py-2.5 pr-4 text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                            <th class="text-left py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($saleInvoices as $invoice)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-[#0078C8]"><a href="{{ route('account.sale-invoice.edit', $invoice->id) }}" target="_blank">{{ $invoice->invoice_no ?: $invoice->unique_no }}</a></p>
                                    <p class="text-xs text-gray-400">{{ dateFormat($invoice->date) }}</p>
                                </td>
                                <td class="py-3 text-sm text-gray-700">{{ $invoice->customer->name }}</td>
                                <td class="py-3 pr-4 text-sm font-semibold text-gray-800 text-right">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($invoice->total) }}</td>
                                <td class="py-3">
                                    @if ($invoice->remaining_amount <= 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Paid</span>
                                    @elseif ($invoice->remaining_amount < $invoice->total)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Partial</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-600">Unpaid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Bills --}}
            <div class="dashboard-source-widget md:col-span-3 xl:col-span-6 bg-white rounded-[5px] border border-gray-200"
                data-dashboard-widget="bills" data-widget-label="Bills" data-widget-size="half" role="listitem">
                <div class="flex justify-between items-center p-5 border-b border-gray-100">
                    <h1 class="text-black text-sm font-bold">Bills</h1>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <form method="GET" class="flex items-center gap-2">
                                <div class="relative">
                                    <select id="bill_per_date_select" name="bill_per_date" onchange="toggleDashboardCustomDates(this, 'bill_')"
                                        class="appearance-none bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-3 py-1 pr-10 rounded-[3px] cursor-pointer focus:outline-none text-xs">
                                        <option value="">All time</option>
                                        @foreach (config('default.per_date') as $key => $value)
                                            <option value="{{ $value }}"
                                                {{ request('bill_per_date') == $value ? 'selected' : '' }}>{{ $key }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-[#0078C8]">
                                        <svg class="fill-current h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                        </svg>
                                    </div>
                                </div>

                                <div id="custom-dates-bill_per_date_select" class="{{ request('bill_per_date') == 'CUSTOM' ? 'flex' : 'hidden' }} items-center gap-2">
                                    <input type="text" name="bill_start_date" value="{{ request('bill_start_date') }}" placeholder="dd/mm/yyyy"
                                        class="date bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-1 py-0.5 rounded-[2px] text-[10px] focus:outline-none w-24">
                                    <input type="text" name="bill_end_date" value="{{ request('bill_end_date') }}" placeholder="dd/mm/yyyy"
                                        class="date bg-[#F6F9FC] border border-[#0078C8] text-[#0078C8] font-semibold px-1 py-0.5 rounded-[2px] text-[10px] focus:outline-none w-24">
                                    <button type="submit" class="bg-[#0078C8] text-white px-2 py-0.5 rounded-[2px] text-[10px] font-bold">Go</button>
                                </div>
                            </form>
                        </div>
                        <button onclick="window.location.href='{{ route('account.bill-invoice.create') }}'"
                            class="bg-[#0078C8] rounded-[3px] text-white font-semibold px-3 py-1 flex items-center gap-1 text-xs">
                            <span class="text-base leading-none">+</span> New Bill
                        </button>
                    </div>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Bill #</th>
                            <th class="text-left py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendor</th>
                            <th class="text-right py-2.5 pr-4 text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                            <th class="text-left py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($billInvoices as $invoice)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-[#0078C8]"><a href="{{ route('account.bill-invoice.edit', $invoice->id) }}" target="_blank">{{ $invoice->invoice_no ?: $invoice->unique_no }}</a></p>
                                    <p class="text-xs text-gray-400">{{ dateFormat($invoice->date) }}</p>
                                </td>
                                <td class="py-3 text-sm text-gray-700">{{ $invoice->vendor->name }}</td>
                                <td class="py-3 pr-4 text-sm font-semibold text-gray-800 text-right">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($invoice->total) }}</td>
                                <td class="py-3">
                                    @if ($invoice->remaining_amount <= 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Paid</span>
                                    @elseif ($invoice->remaining_amount < $invoice->total)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Partial</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-600">Unpaid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">No bills found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Bank Accounts ────────────────────────────────────────────── --}}
        @if ($bankAccounts->isNotEmpty())
            <div class="dashboard-source-widget md:col-span-6 xl:col-span-12" data-dashboard-widget="bank_accounts" data-widget-label="Bank Accounts" data-widget-size="full" role="listitem">
            <div class="mb-2">
                <h2 class="text-sm font-bold text-gray-700 mb-3">Bank Accounts</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                @foreach ($bankAccounts as $bank)
                    <div class="bg-white rounded-[5px] border border-gray-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                        <div class="p-5 relative z-10">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="text-base font-bold text-[#0078C8] hover:underline cursor-pointer">
                                        <a href="{{ route('account.bank-reconciliation.index', $bank->id) }}">
                                            {{ $bank->name }}
                                        </a>
                                    </h4>
                                    <p class="text-xs text-gray-500">{{ $bank->acc_no ?? $bank->account_number}}</p>
                                </div>
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false"
                                        class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <svg class="w-5 h-5 cursor-pointer" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>
                                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-100 py-1"
                                        style="display: none;">
                                        <a href="{{ route('account.bank-reconciliation.spend-money', $bank->id) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Spend Money</a>
                                        <a href="{{ route('account.bank-reconciliation.receive-money', $bank->id) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Receive Money</a>
                                        <a href="{{ route('account.bank-reconciliation.transfer-money', $bank->id) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transfer Money</a>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <a href="{{ route('account.bank-reconciliation.report', $bank->id) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Reconciliation Report</a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 mb-1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Statement Balance</p>
                                <p class="text-xl font-bold text-gray-800">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($bank->statement_balance) }}</p>
                                <p class="text-[10px] text-gray-400">As of {{ date('d M Y') }}</p>
                            </div>
                            <div class="mb-2">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Balance in Account</p>
                                <p class="text-base font-semibold text-gray-600">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($bank->book_balance) }}</p>
                            </div>
                        </div>

                        {{-- Sparkline --}}
                        <div class="h-14 w-full absolute bottom-14 left-0 right-0 z-0 opacity-20 pointer-events-none">
                            <canvas id="bankChart_{{ $bank->id }}"></canvas>
                        </div>

                        <div class="bg-gray-50 border-t p-3 flex justify-between items-center z-10">
                            @if ($bank->unreconciled_count > 0)
                                <a href="{{ route('account.bank-reconciliation.index', $bank->id) }}"
                                    class="bg-[#0078C8] text-white text-xs font-bold py-1.5 px-4 rounded-[3px]">
                                    Reconcile {{ $bank->unreconciled_count }} item{{ $bank->unreconciled_count != 1 ? 's' : '' }}
                                </a>
                            @else
                                <span class="text-emerald-600 font-bold text-sm flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Reconciled
                                </span>
                            @endif
                        </div>
                    </div>

                    @push('scripts')
                        <script>
                            (function() {
                                const ctx = document.getElementById('bankChart_{{ $bank->id }}').getContext('2d');
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: @json($chartLabels),
                                        datasets: [{
                                            data: @json($bank->graph_data),
                                            borderColor: '#0078C8',
                                            borderWidth: 2,
                                            fill: true,
                                            backgroundColor: 'rgba(0,120,200,0.08)',
                                            tension: 0.3,
                                            pointRadius: 0,
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false }, tooltip: { enabled: false } },
                                        scales: { x: { display: false }, y: { display: false } },
                                        layout: { padding: 0 }
                                    }
                                });
                            })();
                        </script>
                    @endpush
                @endforeach
            </div>
            </div>
        @endif

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function toggleDashboardCustomDates(select, prefix) {
    const customDiv = document.getElementById(`custom-dates-${select.id}`);
    if (select.value === 'CUSTOM') {
        customDiv.classList.remove('hidden');
        customDiv.classList.add('flex');
    } else {
        customDiv.classList.remove('flex');
        customDiv.classList.add('hidden');
        select.form.submit();
    }
}

(function () {
    const grid = document.getElementById('accountDashboardGrid');
    if (!grid) return;

    const meta = @json($dashboardWidgetMeta);
    const layout = @json($dashboardLayout);
    const saveUrl = @json(route('account.dashboard.layout.save'));
    const resetUrl = @json(route('account.dashboard.layout.reset'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const hidden = new Set((layout.hidden || []).filter(Boolean));
    const panel = document.getElementById('dashboardCustomizePanel');
    const hiddenList = document.getElementById('dashboardHiddenList');
    const status = document.getElementById('dashboardSaveStatus');
    const toggle = document.getElementById('dashboardCustomizeToggle');
    const reset = document.getElementById('dashboardResetButton');
    let saveTimer;
    let editing = false;

    const widgets = Array.from(document.querySelectorAll('.dashboard-source-widget[data-dashboard-widget]'));
    const widgetById = new Map(widgets.map(widget => [widget.dataset.dashboardWidget, widget]));
    const defaultOrder = Object.keys(meta).filter(id => widgetById.has(id));
    const savedOrder = Array.isArray(layout.order) ? layout.order : [];
    const renderOrder = [...new Set([...savedOrder, ...defaultOrder])].filter(id => widgetById.has(id));

    function injectToolbar(widget) {
        if (widget.querySelector(':scope > .dashboard-widget-toolbar')) return;

        const label = widget.dataset.widgetLabel || 'Card';
        const toolbar = document.createElement('div');
        toolbar.className = 'dashboard-widget-toolbar absolute right-2 top-2 z-20 hidden items-center gap-1 rounded-[3px] border border-gray-200 bg-white/95 p-1 shadow-sm';
        toolbar.innerHTML = `
            <button type="button" class="dashboard-drag-handle cursor-grab rounded-[3px] px-2 py-1 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#0078C8]" aria-label="Drag ${label}">
                <i class="fa-solid fa-grip-vertical text-xs"></i>
            </button>
            <button type="button" data-dashboard-move="up" class="rounded-[3px] px-2 py-1 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#0078C8]" aria-label="Move ${label} up">
                <i class="fa-solid fa-arrow-up text-xs"></i>
            </button>
            <button type="button" data-dashboard-move="down" class="rounded-[3px] px-2 py-1 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#0078C8]" aria-label="Move ${label} down">
                <i class="fa-solid fa-arrow-down text-xs"></i>
            </button>
            <div class="relative">
                <button type="button" data-dashboard-menu class="rounded-[3px] px-2 py-1 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#0078C8]" aria-label="${label} menu">
                    <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                </button>
                <div data-dashboard-menu-panel class="absolute right-0 mt-1 hidden w-36 rounded-[3px] border border-gray-200 bg-white py-1 shadow-lg">
                    <button type="button" data-dashboard-hide class="block w-full px-3 py-2 text-left text-xs font-medium text-gray-700 hover:bg-gray-50">Hide Card</button>
                </div>
            </div>
        `;
        widget.prepend(toolbar);
    }

    function prepareWidget(widget) {
        widget.classList.add('dashboard-widget', 'relative');
        widget.classList.remove('mb-4', 'mb-6');
        injectToolbar(widget);
        if (hidden.has(widget.dataset.dashboardWidget)) {
            widget.classList.add('hidden');
        }
    }

    renderOrder.forEach(id => {
        const widget = widgetById.get(id);
        prepareWidget(widget);
        grid.appendChild(widget);
    });

    widgets
        .filter(widget => !grid.contains(widget))
        .forEach(widget => {
            prepareWidget(widget);
            grid.appendChild(widget);
        });

    document.querySelectorAll('.dashboard-source-container').forEach(container => {
        container.classList.add('hidden');
    });

    function visibleWidgets() {
        return Array.from(grid.querySelectorAll('.dashboard-widget:not(.hidden)'));
    }

    function currentOrder() {
        return visibleWidgets().map(widget => widget.dataset.dashboardWidget);
    }

    function currentSizes() {
        return Array.from(grid.querySelectorAll('.dashboard-widget')).reduce((sizes, widget) => {
            sizes[widget.dataset.dashboardWidget] = widget.dataset.widgetSize || 'third';
            return sizes;
        }, {});
    }

    function renderHiddenList() {
        const hiddenWidgets = Array.from(hidden)
            .filter(id => widgetById.has(id))
            .map(id => ({ id, label: widgetById.get(id).dataset.widgetLabel || meta[id]?.label || id }));

        if (!hiddenWidgets.length) {
            hiddenList.innerHTML = '<span class="text-xs text-gray-400">No hidden cards</span>';
            return;
        }

        hiddenList.innerHTML = hiddenWidgets.map(widget => `
            <button type="button" data-dashboard-restore="${widget.id}" class="inline-flex items-center gap-2 rounded-[3px] border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-white">
                <i class="fa-solid fa-plus text-[10px]"></i>
                Restore ${widget.label}
            </button>
        `).join('');
    }

    function saveLayout() {
        window.clearTimeout(saveTimer);
        status.textContent = 'Saving...';
        saveTimer = window.setTimeout(() => {
            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    order: currentOrder(),
                    hidden: Array.from(hidden),
                    sizes: currentSizes(),
                }),
            })
                .then(response => {
                    if (!response.ok) throw new Error('Save failed');
                    return response.json();
                })
                .then(() => {
                    status.textContent = 'Saved';
                    window.setTimeout(() => status.textContent = '', 1200);
                })
                .catch(() => {
                    status.textContent = 'Could not save';
                });
        }, 350);
    }

    function setEditing(value) {
        editing = value;
        grid.dataset.dashboardEditing = editing ? 'true' : 'false';
        panel.classList.toggle('hidden', !editing);
        toggle.classList.toggle('bg-[#0078C8]', editing);
        toggle.classList.toggle('text-white', editing);
        toggle.classList.toggle('bg-white', !editing);
        toggle.classList.toggle('text-[#0078C8]', !editing);
        toggle.innerHTML = editing
            ? '<i class="fa-solid fa-check text-xs"></i> Done'
            : '<i class="fa-solid fa-sliders text-xs"></i> Customize Dashboard';
        sortable?.option('disabled', !editing);
        renderHiddenList();
    }

    const sortable = window.Sortable ? Sortable.create(grid, {
        animation: 160,
        easing: 'cubic-bezier(0.2, 0, 0, 1)',
        handle: '.dashboard-drag-handle',
        draggable: '.dashboard-widget:not(.hidden)',
        disabled: true,
        onEnd: saveLayout,
    }) : null;

    grid.addEventListener('click', event => {
        const menuButton = event.target.closest('[data-dashboard-menu]');
        const hideButton = event.target.closest('[data-dashboard-hide]');
        const moveButton = event.target.closest('[data-dashboard-move]');

        if (menuButton) {
            const panel = menuButton.parentElement.querySelector('[data-dashboard-menu-panel]');
            panel?.classList.toggle('hidden');
            return;
        }

        if (hideButton) {
            const widget = hideButton.closest('.dashboard-widget');
            hidden.add(widget.dataset.dashboardWidget);
            widget.classList.add('hidden');
            renderHiddenList();
            saveLayout();
            return;
        }

        if (moveButton) {
            const widget = moveButton.closest('.dashboard-widget');
            const visible = visibleWidgets();
            const index = visible.indexOf(widget);
            if (moveButton.dataset.dashboardMove === 'up' && index > 0) {
                grid.insertBefore(widget, visible[index - 1]);
                saveLayout();
            }
            if (moveButton.dataset.dashboardMove === 'down' && index < visible.length - 1) {
                grid.insertBefore(visible[index + 1], widget);
                saveLayout();
            }
        }
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('[data-dashboard-menu]') && !event.target.closest('[data-dashboard-menu-panel]')) {
            grid.querySelectorAll('[data-dashboard-menu-panel]').forEach(panel => panel.classList.add('hidden'));
        }

        const restore = event.target.closest('[data-dashboard-restore]');
        if (restore) {
            const id = restore.dataset.dashboardRestore;
            const widget = widgetById.get(id);
            hidden.delete(id);
            widget.classList.remove('hidden');
            grid.appendChild(widget);
            renderHiddenList();
            saveLayout();
            window.dispatchEvent(new Event('resize'));
        }
    });

    toggle?.addEventListener('click', () => setEditing(!editing));
    reset?.addEventListener('click', () => {
        if (!window.confirm('Reset dashboard layout and restore hidden cards?')) return;
        fetch(resetUrl, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        }).then(() => window.location.reload());
    });

    renderHiddenList();
})();

(function () {
    // ── Revenue vs Expenses Line Chart ────────────────────────────
    const revVsExpCtx = document.getElementById('revVsExpChart').getContext('2d');
    new Chart(revVsExpCtx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Revenue',
                    data: @json($revenueChartData),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                },
                {
                    label: 'Expenses',
                    data: @json($expenseChartData),
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239,68,68,0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                },
                {
                    label: 'Gross Profit',
                    data: @json($grossProfitChartData),
                    borderColor: '#0078C8',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 3],
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 16, font: { size: 11 } }
                },
                tooltip: {
                    backgroundColor: 'rgba(255,255,255,0.97)',
                    titleColor: '#1F2937',
                    bodyColor: '#374151',
                    borderColor: '#E5E7EB',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 10 } } }
            }
        }
    });

    // ── Cash Flow Bar Chart ────────────────────────────────────────
    const cashCtx = document.getElementById('cashFlowChart').getContext('2d');
    new Chart(cashCtx, {
        type: 'bar',
        data: {
            labels: @json($cashFlowLabels),
            datasets: [
                {
                    label: 'Cash In',
                    data: @json($cashInData),
                    backgroundColor: 'rgba(16,185,129,0.75)',
                    borderColor: '#10B981',
                    borderWidth: 1,
                    borderRadius: 3,
                },
                {
                    label: 'Cash Out',
                    data: @json($cashOutData),
                    backgroundColor: 'rgba(239,68,68,0.75)',
                    borderColor: '#EF4444',
                    borderWidth: 1,
                    borderRadius: 3,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 16, font: { size: 11 } }
                },
                tooltip: {
                    backgroundColor: 'rgba(255,255,255,0.97)',
                    titleColor: '#1F2937',
                    bodyColor: '#374151',
                    borderColor: '#E5E7EB',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 10 } } }
            }
        }
    });
})();
</script>
@endpush
