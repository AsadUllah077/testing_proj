@extends('layouts.auth')

@section('title', 'Compliance Command Center')

@section('content')
    <div class="min-h-screen bg-[#f8fafc] pb-12">
        {{-- Header --}}
        <div class="bg-white border-b border-gray-200">
            <div class="mx-auto px-6 py-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Compliance Command Center</h1>
                        <p class="text-sm text-gray-500 mt-1">Real-time statutory liability monitoring and risk mitigation.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="checkChangesBtn"
                            class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Regulatory Audit
                        </button>
                        <button id="generateEventsBtn"
                            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Refresh Obligations
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto px-6 mt-8">
            {{-- Top Stats Row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {{-- Compliance Score Card --}}
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Compliance Health</p>
                            <h3 class="text-3xl font-black text-gray-900 mt-1" id="complianceScore">98%</h3>
                        </div>
                        <div class="p-2 bg-green-50 rounded-xl">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                        <div id="scoreProgress" class="bg-green-500 h-full rounded-full transition-all duration-1000"
                            style="width: 98%"></div>
                    </div>
                    <p class="text-[10px] text-green-600 font-bold mt-2 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z"
                                clip-rule="evenodd" />
                        </svg>
                        +2.4% vs last month
                    </p>
                </div>

                {{-- Total Liability --}}
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm group">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Liability</p>
                            <h3 class="text-3xl font-black text-gray-900 mt-1" id="totalLiabilityDisplay">
                                {{ currency()?->symbol }} 0.00</h3>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Unpaid statutory deductions</p>
                </div>

                {{-- Upcoming Deadlines --}}
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm group">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Deadlines (7d)</p>
                            <h3 class="text-3xl font-black text-gray-900 mt-1" id="pendingEvents">0</h3>
                        </div>
                        <div class="p-2 bg-amber-50 rounded-xl">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Upcoming obligations</p>
                </div>

                {{-- Penalty Risk --}}
                <div class="bg-[#1e293b] rounded-2xl p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
                        <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Penalty Exposure</p>
                        <h3 class="text-3xl font-black text-white mt-1" id="penaltyExposure">{{ currency()?->symbol }} 0.00
                        </h3>
                        <div class="mt-3">
                            <div class="w-full bg-slate-700 h-1 rounded-full overflow-hidden">
                                <div id="penaltyProgress" class="bg-red-500 h-full transition-all duration-500"
                                    style="width: 0%"></div>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-2 italic font-medium uppercase tracking-tighter"
                                id="penaltyMsg">Safe: No overdue items</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Left Column: Alerts & Action Center --}}
                <div class="lg:col-span-1 space-y-6">
                    {{-- Action Required --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                            <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Urgent Alerts</h2>
                            <span
                                class="bg-red-100 text-red-700 text-[10px] px-2 py-0.5 rounded-full font-black uppercase tracking-tighter"
                                id="alertCountBadge">0 NEW</span>
                        </div>
                        <div id="recentAlerts" class="divide-y divide-gray-50 max-h-[600px] overflow-y-auto">
                            {{-- Alerts injected here --}}
                        </div>
                    </div>

                    {{-- Compliance Chart & Authority Breakdown --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Liability Breakdown</h2>
                            <div class="p-2 bg-blue-50 rounded-lg">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18m-4-4v4m8-8v8m-4-12v12"/></svg>
                            </div>
                        </div>
                        <canvas id="liabilityChart" height="250"></canvas>
                        
                        <div id="breakdownList" class="mt-8 space-y-4">
                            {{-- Injected via JS --}}
                        </div>
                    </div>

                    {{-- Authority Coverage --}}
                    <div class="bg-[#1e293b] rounded-2xl p-6 border border-slate-700 shadow-xl overflow-hidden relative group">
                        <div class="absolute -right-4 -bottom-4 opacity-10 transform rotate-12 group-hover:scale-110 transition duration-500">
                            <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                        <div class="relative z-10">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Authority Coverage</p>
                            <h4 class="text-white text-lg font-bold mb-4">Active Compliance Profiles</h4>
                            <div id="coverageList" class="space-y-3">
                                {{-- Injected via JS --}}
                            </div>
                            <a href="{{ route('payroll.statutory-bodies.index') }}" class="mt-6 block text-center py-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition border border-slate-700">Manage Authorities</a>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Detailed Obligations Table --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                            <div>
                                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Filing Calendar</h2>
                                <p class="text-xs text-gray-400 mt-1">Upcoming statutory payments and filings.</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('payroll.compliance.calendar') }}"
                                    class="p-2 text-gray-400 hover:text-blue-600 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        <th class="px-6 py-4">Authority / Obligation</th>
                                        <th class="px-6 py-4 text-center">Due Date</th>
                                        <th class="px-6 py-4 text-right">Amount Due</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="upcomingDeadlines" class="divide-y divide-gray-50">
                                    {{-- Deadlines injected here --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {{-- Quick Links --}}
                <div class="bg-white border border-[#dadae7] rounded-xl p-5 shadow-sm">
                    <h3 class="font-bold text-gray-800 mb-3">Quick Links</h3>
                    @php
                        $links = [
                            [
                                'route' => 'payroll.statutory-bodies.index',
                                'label' => 'Setup Authorities',
                                'icon' => 'fa-solid fa-gear',
                            ],
                            [
                                'route' => 'payroll.compliance.calendar',
                                'label' => 'Compliance Calendar',
                                'icon' => 'fa-solid fa-calendar-days',
                            ],
                            [
                                'route' => 'payroll.compliance.alerts',
                                'label' => 'Compliance Alerts',
                                'icon' => 'fa-solid fa-bell',
                            ],
                            [
                                'route' => 'payroll.compliance.statistics',
                                'label' => 'Compliance Statistics',
                                'icon' => 'fa-solid fa-chart-line',
                            ],
                        ];
                    @endphp

                    @foreach ($links as $link)
                        <a href="{{ route($link['route']) }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#0078C8]/5 text-sm text-gray-700 hover:text-[#0078C8] transition">

                            <i class="{{ $link['icon'] }} w-4 text-[#0078C8]"></i>

                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Confirmation Modals --}}
    <div id="confirmModal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[100] items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl">
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Mark as Completed?</h3>
                <p class="text-sm text-gray-500 mt-2">Confirm that this statutory liability has been paid or filed with the
                    authority.</p>
            </div>
            <div class="bg-gray-50 p-4 flex gap-3">
                <button onclick="closeModal()"
                    class="flex-1 px-4 py-3 bg-white border border-gray-200 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-100 transition uppercase tracking-wider">Cancel</button>
                <button id="confirmPayBtn"
                    class="flex-1 px-4 py-3 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 uppercase tracking-wider">Confirm
                    Payment</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const symbol = "{{ currency()?->symbol }}";
        let liabilityChart = null;
        let pendingEventId = null;

        $(document).ready(function() {
            // Init Load
            refreshDashboard();

            // Button Handlers
            $('#generateEventsBtn').click(function() {
                const btn = $(this);
                btn.prop('disabled', true).addClass('opacity-50');
                $.post('{{ route('payroll.compliance.generate-events') }}', {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        toastr.success(res.message);
                        refreshDashboard();
                    })
                    .always(() => btn.prop('disabled', false).removeClass('opacity-50'));
            });

            $('#checkChangesBtn').click(function() {
                const btn = $(this);
                btn.prop('disabled', true).addClass('opacity-50');
                $.post('{{ route('payroll.compliance.check-regulatory-changes') }}', {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        toastr.success(res.message);
                        refreshDashboard();
                    })
                    .always(() => btn.prop('disabled', false).removeClass('opacity-50'));
            });

            $('#confirmPayBtn').click(function() {
                if (!pendingEventId) return;
                const btn = $(this);
                btn.prop('disabled', true).text('PROCESSING...');

                $.post(`/payroll/compliance/events/${pendingEventId}/complete`, {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(res => {
                        toastr.success(res.message);
                        closeModal();
                        refreshDashboard();
                    })
                    .always(() => btn.prop('disabled', false).text('CONFIRM PAYMENT'));
            });
        });

        function refreshDashboard() {
            $.get('{{ route('payroll.compliance.dashboard') }}', {
                    ajax: 1
                })
                .done(data => {
                    updateStats(data.stats, data.penalty_exposure);
                    updateTable(data.upcoming_deadlines);
                    updateAlerts(data.recent_alerts);
                    updateChart(data.liability_breakdown || []);
                });
        }

        function updateStats(stats, penalty) {
            $('#complianceScore').text(calculateScore(stats) + '%');
            $('#scoreProgress').css('width', calculateScore(stats) + '%');
            $('#pendingEvents').text(stats.due_soon_events || 0);

            // Liability Total
            const totalDue = stats.total_amount_due || 0;
            $('#totalLiabilityDisplay').text(symbol + ' ' + new Intl.NumberFormat().format(totalDue));

            // Penalty
            $('#penaltyExposure').text(symbol + ' ' + new Intl.NumberFormat().format(penalty || 0));
            let progress = 0;
            if (penalty > 10000) progress = 100;
            else if (penalty > 5000) progress = 75;
            else if (penalty > 1000) progress = 50;
            else if (penalty > 0) progress = 25;
            $('#penaltyProgress').css('width', progress + '%');
            $('#penaltyMsg').text(penalty > 0 ? 'High Risk: Overdue items detected' : 'Safe: No overdue items');
        }

        function calculateScore(stats) {
            if (stats.total_events === 0) return 100;
            const score = 100 - ((stats.overdue_events * 20) + (stats.due_soon_events * 5));
            return Math.max(0, Math.min(100, score));
        }

        function updateTable(events) {
            const tbody = $('#upcomingDeadlines');
            if (!events || events.length === 0) {
                tbody.html(
                    '<tr><td colspan="5" class="p-12 text-center text-gray-400 text-xs italic font-medium tracking-widest">Global Scan Complete: No pending obligations found.</td></tr>'
                );
                return;
            }

            tbody.html(events.map(ev => {
                const date = new Date(ev.due_date);
                const formattedDate = date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                const isOverdue = new Date() > date && ev.status !== 'completed';

                let statusBadge =
                    '<span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-black rounded uppercase tracking-tighter">Pending</span>';
                if (ev.status === 'completed') statusBadge =
                    '<span class="px-2 py-1 bg-green-50 text-green-600 text-[10px] font-black rounded uppercase tracking-tighter">Paid</span>';
                else if (isOverdue) statusBadge =
                    '<span class="px-2 py-1 bg-red-50 text-red-600 text-[10px] font-black rounded uppercase tracking-tighter animate-pulse">Overdue</span>';

                return `
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 font-black text-[10px]">
                                ${ev.statutory_body?.code || '??'}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">${ev.title}</h4>
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-widest">${ev.statutory_body?.name || 'Unknown Body'}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-5 text-center">
                        <span class="text-xs font-bold ${isOverdue ? 'text-red-500' : 'text-gray-600'}">${formattedDate}</span>
                    </td>
                    <td class="px-6 py-5 text-right font-black text-sm text-gray-900">
                        ${symbol} ${new Intl.NumberFormat().format(ev.amount_due || 0)}
                    </td>
                    <td class="px-6 py-5 text-center">
                        ${statusBadge}
                    </td>
                    <td class="px-6 py-5 text-center">
                        ${ev.status !== 'completed' ? `
                                                        <button onclick="confirmPay(${ev.id})" class="text-[10px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest underline decoration-2 underline-offset-4">Record Payment</button>
                                                    ` : '<span class="text-green-500"><svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></span>'}
                    </td>
                </tr>
            `;
            }).join(''));
        }

        function updateAlerts(alerts) {
            const container = $('#recentAlerts');
            $('#alertCountBadge').text(`${alerts.length} NEW`);

            if (!alerts || alerts.length === 0) {
                container.html(
                    '<div class="p-12 text-center text-gray-400 text-xs italic font-medium tracking-widest">No active compliance alerts.</div>'
                );
                return;
            }

            container.html(alerts.map(a => {
                const severityColors = {
                    'critical': 'bg-red-500 border-red-200 text-red-600',
                    'error': 'bg-red-50 text-red-600',
                    'warning': 'bg-amber-50 text-amber-600',
                    'info': 'bg-blue-50 text-blue-600'
                };

                const icon = a.severity === 'critical' ? '☣️' : (a.severity === 'error' ? '⚠️' : '🔔');

                return `
                <div class="p-4 flex gap-4 hover:bg-gray-50 transition">
                    <div class="text-xl">${icon}</div>
                    <div>
                        <h5 class="text-sm font-bold text-gray-800">${a.title}</h5>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">${a.message}</p>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="text-[9px] font-black uppercase tracking-widest ${a.severity === 'critical' ? 'text-red-600' : 'text-gray-400'}">${a.severity}</span>
                            <button onclick="acknowledgeAlert(${a.id})" class="text-[9px] font-black text-blue-600 uppercase tracking-widest hover:underline">Acknowledge</button>
                        </div>
                    </div>
                </div>
            `;
            }).join(''));
        }

        function updateChart(breakdown) {
            const ctx = document.getElementById('liabilityChart').getContext('2d');
            const listContainer = $('#breakdownList');
            const coverageContainer = $('#coverageList');
            
            if (liabilityChart) liabilityChart.destroy();
            listContainer.empty();
            coverageContainer.empty();

            const colors = ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#ec4899'];

            if (!breakdown || breakdown.length === 0) {
                listContainer.html('<div class="text-center py-4 text-xs text-gray-400 italic">No liability distribution data.</div>');
                coverageContainer.html('<div class="text-center py-4 text-xs text-slate-500 italic">No authorities configured.</div>');
                return;
            }

            liabilityChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: breakdown.map(b => b.name),
                    datasets: [{
                        data: breakdown.map(b => b.amount),
                        backgroundColor: colors,
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    cutout: '80%',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            // Update Breakdown List
            breakdown.forEach((b, i) => {
                const color = colors[i % colors.length];
                listContainer.append(`
                    <div class="flex justify-between items-center group">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full" style="background-color: ${color}"></div>
                            <div>
                                <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest">${b.code}</h4>
                                <p class="text-[9px] text-gray-400 font-bold uppercase">${b.name}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-black text-gray-900">${symbol} ${parseFloat(b.amount).toLocaleString()}</p>
                            <p class="text-[9px] text-blue-600 font-bold uppercase tracking-tighter">${((b.amount / breakdown.reduce((a,c) => a + c.amount, 0)) * 100).toFixed(1)}%</p>
                        </div>
                    </div>
                `);

                // Update Coverage List in the dark card
                coverageContainer.append(`
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-400 shadow-lg shadow-green-400/50"></div>
                            <span class="text-xs font-bold text-slate-300 uppercase tracking-wide">${b.code}</span>
                        </div>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Active</span>
                    </div>
                `);
            });
        }

        function confirmPay(id) {
            pendingEventId = id;
            $('#confirmModal').removeClass('hidden').addClass('flex');
        }

        function closeModal() {
            $('#confirmModal').addClass('hidden').removeClass('flex');
            pendingEventId = null;
        }

        function acknowledgeAlert(id) {
            $.post(`/payroll/compliance/alerts/${id}/acknowledge`, {
                    _token: '{{ csrf_token() }}'
                })
                .done(() => refreshDashboard());
        }
    </script>
@endpush
