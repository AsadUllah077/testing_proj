@extends('layouts.auth')

@section('title', 'Payroll Dashboard')

@section('content')
<div class="p-4 bg-gray-50 min-h-screen">

    {{-- ── PAGE HEADER ──────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white rounded-xl p-4 shadow">
        <div class="flex flex-col gap-1">
            <h2 class="text-[#101018] text-2xl md:text-3xl font-black tracking-tight flex items-center gap-2">
                Payroll Dashboard
                <span class="animate-pulse inline-block w-2 h-2 rounded-full bg-green-500"></span>
            </h2>
            <p class="text-[#5e5f8d] text-sm">
                {{ now()->format('l, d F Y') }}
                @if($latestRun)
                    &nbsp;·&nbsp; Latest run: <strong>{{ $latestRun->period_start->format('d M') }} – {{ $latestRun->period_end->format('d M Y') }}</strong>
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            @if($pendingApprovals > 0)
            <a href="{{ route('payroll.pay-runs.index') }}?approval_status=pending"
               class="flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-300 text-amber-700 rounded-lg text-sm font-semibold hover:bg-amber-100 transition">
                <i class="fa-solid fa-clock-rotate-left"></i>
                {{ $pendingApprovals }} Pending Approval{{ $pendingApprovals > 1 ? 's' : '' }}
            </a>
            @endif
            <a href="{{ route('payroll.pay-runs.create') }}"
               class="flex items-center gap-2 px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm font-semibold hover:bg-[#006bb0] transition shadow-sm">
                <i class="fa-solid fa-plus"></i>
                New Pay Run
            </a>
        </div>
    </div>

    {{-- ── KPI CARDS ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- Net Payroll --}}
        <div class="bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <p class="text-[#5e5f8d] text-sm font-medium">Net Payroll (Latest)</p>
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-wave text-[#0078C8]"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">
                {{ currency()?->symbol }}{{ number_format($latestNet, 0) }}
            </p>
            <p class="text-xs text-[#5e5f8d] mt-1">
                Gross: {{ currency()?->symbol }}{{ number_format($latestGross, 0) }}
                @if($latestGross > 0)
                    &nbsp;·&nbsp;
                    <span class="text-red-500">Ded: {{ currency()?->symbol }}{{ number_format($latestDeductions, 0) }}</span>
                @endif
            </p>
        </div>

        {{-- Headcount --}}
        <div class="bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <p class="text-[#5e5f8d] text-sm font-medium">Active Employees</p>
                <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                    <i class="fa-solid fa-users text-green-600"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($activeEmployees) }}</p>
            <p class="text-xs text-[#5e5f8d] mt-1">
                {{ $latestHeadcount }} on latest payslip
            </p>
        </div>

        {{-- Pending Approvals --}}
        <div class="bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <p class="text-[#5e5f8d] text-sm font-medium">Pending Approvals</p>
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center">
                    <i class="fa-solid fa-hourglass-half text-amber-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold {{ $pendingApprovals > 0 ? 'text-amber-600' : 'text-gray-900' }}">
                {{ $pendingApprovals }}
            </p>
            <p class="text-xs text-[#5e5f8d] mt-1">
                {{ $pendingApprovals > 0 ? 'Awaiting your approval' : 'All caught up' }}
            </p>
        </div>

        {{-- Pay Runs This Year --}}
        <div class="bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <p class="text-[#5e5f8d] text-sm font-medium">Pay Runs {{ now()->year }}</p>
                <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-check text-purple-600"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $payRunsThisYear }}</p>
            <p class="text-xs text-[#5e5f8d] mt-1">
                Total this year
            </p>
        </div>
    </div>

    {{-- ── CHARTS ROW ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        {{-- Monthly Payroll Trend (2/3 width) --}}
        <div class="lg:col-span-2 bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="font-bold text-gray-800">Monthly Payroll Trend</h3>
                    <p class="text-xs text-[#5e5f8d]">Gross vs Net pay — last 6 months</p>
                </div>
            </div>
            <div style="height:220px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        {{-- Pay Run Status Breakdown (1/3 width) --}}
        <div class="bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="font-bold text-gray-800">Pay Run Status</h3>
                <p class="text-xs text-[#5e5f8d]">All time breakdown</p>
            </div>
            <div style="height:180px;" class="flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="mt-3 space-y-1.5" id="statusLegend"></div>
        </div>
    </div>

    {{-- ── BUDGET ALERTS ────────────────────────────────────────────────── --}}
    @if(!empty($forecastData['alerts']))
    <div class="mb-6 space-y-3">
        @foreach($forecastData['alerts'] as $alert)
        <div class="flex items-center justify-between p-4 rounded-xl border {{ $alert['severity'] === 'critical' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $alert['severity'] === 'critical' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">
                    <i class="fa-solid {{ $alert['severity'] === 'critical' ? 'fa-triangle-exclamation' : 'fa-circle-info' }}"></i>
                </div>
                <div>
                    <p class="text-sm font-bold uppercase tracking-wider">{{ $alert['title'] }}</p>
                    <p class="text-xs opacity-80">{{ $alert['message'] }}</p>
                </div>
            </div>
            <a href="{{ route('account.budget.report') }}" target="_blank" class="text-xs font-bold hover:underline">Review Budget →</a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── FORECASTING ROW ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
        {{-- Forecast Chart (3/4 width) --}}
        <div class="lg:col-span-3 bg-white border border-[#dadae7] rounded-xl p-6 shadow-sm overflow-hidden relative">
            <div class="absolute top-0 right-0 p-4">
                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-bold uppercase tracking-widest border border-indigo-100">
                    {{ $forecastData['growth_rate'] * 100 }}% Annual Growth Projected
                </span>
            </div>
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-indigo-500"></i>
                    Payroll Cost Forecasting
                </h3>
                <p class="text-xs text-[#5e5f8d]">AI-driven projection for the next 6 months based on current staff & historical trends</p>
            </div>
            <div style="height:250px;">
                <canvas id="forecastChart"></canvas>
            </div>
        </div>

        {{-- Forecast Stats (1/4 width) --}}
        <div class="lg:col-span-1 flex flex-col gap-4">
            <div class="bg-[#0078C8] hover:bg-[#0078C8]/90 rounded-xl p-5 text-white shadow-lg shadow-[#0078C8]/20">
                <p class="text-[10px] uppercase font-bold opacity-70 mb-1">Projected Monthly Total</p>
                <p class="text-3xl font-light">{{ currency()?->symbol }}{{ number_format($forecastData['monthly_total'], 0) }}</p>
                <div class="mt-4 pt-4 border-t border-white/10 space-y-2">
                    <div class="flex justify-between text-[11px]">
                        <span class="opacity-70">Fixed Salary:</span>
                        <span class="font-bold">{{ currency()?->symbol }}{{ number_format($forecastData['baseline'], 0) }}</span>
                    </div>
                    <div class="flex justify-between text-[11px]">
                        <span class="opacity-70">Estimated OT:</span>
                        <span class="font-bold">{{ currency()?->symbol }}{{ number_format($forecastData['ot_avg'], 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
                <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Annual Forecast (Est.)</p>
                <p class="text-2xl font-bold text-gray-800">{{ currency()?->symbol }}{{ number_format($forecastData['annual_projection'], 0) }}</p>
                @if($forecastData['monthly_budget'])
                <div class="mt-3">
                    <div class="flex justify-between text-[10px] mb-1">
                        <span class="text-gray-500">Budget Usage:</span>
                        <span class="font-bold {{ $forecastData['monthly_total'] > $forecastData['monthly_budget'] ? 'text-red-500' : 'text-green-600' }}">
                            {{ round(($forecastData['monthly_total'] / $forecastData['monthly_budget']) * 100) }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $forecastData['monthly_total'] > $forecastData['monthly_budget'] ? 'bg-red-500' : 'bg-indigo-500' }}" 
                             style="width: {{ min(100, ($forecastData['monthly_total'] / $forecastData['monthly_budget']) * 100) }}%"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── BOTTOM ROW ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Recent Pay Runs (2/3 width) --}}
        <div class="lg:col-span-2 bg-white border border-[#dadae7] rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-[#dadae7] flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Recent Pay Runs</h3>
                <a href="{{ route('payroll.pay-runs.index') }}"
                   class="text-xs text-[#0078C8] hover:underline font-medium">View all →</a>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-5 py-3 text-left">Period</th>
                        <th class="px-5 py-3 text-left">Employees</th>
                        <th class="px-5 py-3 text-right">Net Pay</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-center">Approval</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentPayRuns as $run)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3">
                            <p class="font-medium text-gray-800">{{ $run->period_start->format('d M') }} – {{ $run->period_end->format('d M Y') }}</p>
                            <p class="text-xs text-gray-400">{{ ucfirst($run->frequency) }}</p>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $run->payslips_count }}</td>
                        <td class="px-5 py-3 text-right font-mono font-semibold text-gray-800">
                            {{ currency()?->symbol }}{{ number_format($run->payslips->sum('net_pay'), 0) }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            @php
                                $statusColor = match($run->status) {
                                    'paid'   => 'bg-green-100 text-green-700',
                                    'posted' => 'bg-blue-100 text-blue-700',
                                    default  => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                {{ ucfirst($run->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @php
                                $approvalColor = match($run->approval_status) {
                                    'approved' => 'bg-green-100 text-green-700',
                                    'rejected' => 'bg-red-100 text-red-600',
                                    default    => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $approvalColor }}">
                                {{ ucfirst($run->approval_status ?? 'pending') }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ route('payroll.pay-runs.show', $run->id) }}"
                               class="text-[#0078C8] hover:text-[#006bb0]">
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-gray-400 text-sm">
                            No pay runs yet. <a href="{{ route('payroll.pay-runs.create') }}" class="text-[#0078C8] underline">Create the first one →</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Right column: Top Pay Items + Quick Links --}}
        <div class="flex flex-col gap-4">

            {{-- Top Pay Items --}}
            <div class="bg-white border border-[#dadae7] rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-[#dadae7]">
                    <h3 class="font-bold text-gray-800">Top Pay Items</h3>
                    <p class="text-xs text-[#5e5f8d]">Latest pay run</p>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($topPayItems as $item)
                    @php
                        $itemColor = $item->type === 'deduction' ? 'text-red-500' : 'text-green-600';
                    @endphp
                    <div class="px-5 py-3 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $item->type === 'deduction' ? 'bg-red-400' : 'bg-green-500' }}"></span>
                            <span class="text-sm text-gray-700">{{ $item->name }}</span>
                        </div>
                        <span class="text-sm font-mono font-semibold {{ $itemColor }}">
                            {{ currency()?->symbol }}{{ number_format($item->total, 0) }}
                        </span>
                    </div>
                    @empty
                    <div class="px-5 py-6 text-center text-xs text-gray-400">No data yet</div>
                    @endforelse
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
                <h3 class="font-bold text-gray-800 mb-3">Quick Links</h3>
                <div class="space-y-2">
                    <a href="{{ route('payroll.pay-runs.create') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition group">
                        <i class="fa-solid fa-circle-plus w-4 text-[#0078C8]"></i>
                        New Pay Run
                    </a>
                    <a href="{{ route('payroll.employee-pay.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-user-tie w-4 text-[#0078C8]"></i>
                        Employee Pay Structures
                    </a>
                    <a href="{{ route('payroll.pay-items.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-list-check w-4 text-[#0078C8]"></i>
                        Manage Pay Items
                    </a>
                    <a href="{{ route('payroll.salary-advances.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-money-bill-transfer w-4 text-[#0078C8]"></i>
                        Salary Advances
                    </a>
                    <a href="{{ route('payroll.tax-brackets.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-percent w-4 text-[#0078C8]"></i>
                        FBR Tax Brackets
                    </a>
                    <a href="{{ route('payroll.liabilities.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-file-invoice-dollar w-4 text-[#0078C8]"></i>
                        Payroll Liabilities
                    </a>
                    <a href="{{ route('payroll.reports.summary') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-chart-bar w-4 text-[#0078C8]"></i>
                        Payroll Summary Report
                    </a>
                    <a href="{{ route('payroll.reports.overtime') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-clock-rotate-left w-4 text-[#0078C8]"></i>
                        Overtime Analysis Report
                    </a>
                    <a href="{{ route('payroll.compliance.dashboard') }}" target="_blank"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-shield w-4 text-[#0078C8]"></i>
                        Compliance Dashboard
                    </a>
                    <a href="{{ route('payroll.salary-benchmarks.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-file-invoice-dollar w-4 text-[#0078C8]"></i>
                        Salary Benchmarks
                    </a>
                    <a href="{{ route('payroll.download-payslip.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-file-arrow-down w-4 text-[#0078C8]"></i>
                        Download Payslips
                    </a>
                    <a href="{{ route('employee.login') }}" target="_blank"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">
                        <i class="fa-solid fa-user-gear w-4 text-[#0078C8]"></i>
                        Employee Self-Service Portal
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const sym = '{{ currency()?->symbol ?? "" }}';

// ── Monthly Trend Chart ────────────────────────────────────────────────────
const trendCtx = document.getElementById('trendChart').getContext('2d');

const grossGrad = trendCtx.createLinearGradient(0, 0, 0, 220);
grossGrad.addColorStop(0, 'rgba(0,120,200,0.25)');
grossGrad.addColorStop(1, 'rgba(0,120,200,0.02)');

const netGrad = trendCtx.createLinearGradient(0, 0, 0, 220);
netGrad.addColorStop(0, 'rgba(16,185,129,0.25)');
netGrad.addColorStop(1, 'rgba(16,185,129,0.02)');

new Chart(trendCtx, {
    type: 'bar',
    data: {
        labels: @json($months),
        datasets: [
            {
                label: 'Gross',
                data: @json($grossTrend),
                backgroundColor: 'rgba(0,120,200,0.75)',
                borderRadius: 4,
                borderSkipped: false,
                order: 2,
            },
            {
                label: 'Net Pay',
                data: @json($netTrend),
                type: 'line',
                borderColor: '#10b981',
                backgroundColor: netGrad,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#10b981',
                fill: true,
                tension: 0.4,
                order: 1,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                labels: { boxWidth: 12, font: { size: 11 }, color: '#5e5f8d' }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.dataset.label}: ${sym}${Number(ctx.raw).toLocaleString()}`
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
            y: {
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: {
                    font: { size: 11 },
                    color: '#9ca3af',
                    callback: v => sym + Number(v).toLocaleString()
                }
            }
        }
    }
});

// ── Status Doughnut Chart ──────────────────────────────────────────────────
const statusColors = {
    draft:  '#f59e0b',
    posted: '#3b82f6',
    paid:   '#10b981',
};
const statusLabels = @json($statusLabels);
const statusValues = @json($statusValues);

const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
        datasets: [{
            data: statusValues,
            backgroundColor: statusLabels.map(l => statusColors[l] ?? '#6b7280'),
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.raw} run${ctx.raw !== 1 ? 's' : ''}`
                }
            }
        }
    }
});

// Build custom legend
const legendEl = document.getElementById('statusLegend');
statusLabels.forEach((label, i) => {
    const color = statusColors[label] ?? '#6b7280';
    legendEl.innerHTML += `
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${color}"></span>
                <span class="text-gray-600 capitalize">${label}</span>
            </div>
            <span class="font-semibold text-gray-800">${statusValues[i]}</span>
        </div>`;
});

// ── Forecast Chart ─────────────────────────────────────────────────────────
const forecastCtx = document.getElementById('forecastChart').getContext('2d');
const forecastData = @json($forecastData['projections']);
const budgetValue = @json($forecastData['monthly_budget']);

const forecastGrad = forecastCtx.createLinearGradient(0, 0, 0, 250);
forecastGrad.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
forecastGrad.addColorStop(1, 'rgba(79, 70, 229, 0)');

new Chart(forecastCtx, {
    type: 'line',
    data: {
        labels: forecastData.map(d => d.month),
        datasets: [
            {
                label: 'Projected Cost',
                data: forecastData.map(d => d.amount),
                borderColor: '#0078C8',
                backgroundColor: forecastGrad,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#0078C8',
                fill: true,
                tension: 0.4,
            },
            {
                label: 'Budget Limit',
                data: Array(forecastData.length).fill(budgetValue),
                borderColor: '#ef4444',
                borderDash: [5, 5],
                borderWidth: 2,
                pointRadius: 0,
                fill: false,
                hidden: !budgetValue
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                labels: { boxWidth: 12, font: { size: 11 }, color: '#5e5f8d' }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.dataset.label}: ${sym}${Number(ctx.raw).toLocaleString()}`
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
            y: {
                grid: { color: 'rgba(0,0,0,0.05)' },
                beginAtZero: false,
                ticks: {
                    font: { size: 11 },
                    color: '#9ca3af',
                    callback: v => sym + Number(v).toLocaleString()
                }
            }
        }
    }
});
</script>
@endpush
