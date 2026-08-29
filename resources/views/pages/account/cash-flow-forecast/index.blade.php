@extends('layouts.auth')
@section('title')
    Cash Flow Forecast
@endsection

@section('content')
    <div class="p-4">

        {{-- Header --}}
        <div class="flex justify-between items-center mb-5 bg-white p-5">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Cash Flow Forecast</h1>
                <p class="text-xs text-gray-500 mt-0.5">Projected cash position based on outstanding AR & AP (Net-30 terms)
                </p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Horizon:</label>
                <select name="horizon" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                    <option value="30" @selected($horizon == 30)>30 days</option>
                    <option value="60" @selected($horizon == 60)>60 days</option>
                    <option value="90" @selected($horizon == 90)>90 days</option>
                    <option value="180" @selected($horizon == 180)>180 days</option>
                </select>
            </form>
        </div>

        {{-- AI Insights --}}
        @if (!empty($insights))
            <div class="bg-indigo-50 border border-indigo-200 rounded-[5px] p-4 mb-5">
                <div class="flex items-start gap-2">
                    <div class="bg-indigo-100 rounded-full p-1.5 mt-0.5 shrink-0">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-indigo-700 uppercase tracking-wide mb-1">Forecast Insights</p>
                        <ul class="text-sm text-indigo-800 space-y-1">
                            @foreach ($insights as $insight)
                                <li>{{ $insight }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Current Cash</p>
                <p class="text-xl font-bold text-gray-800 mt-1">{{ number_format($currentCash, 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Bank & cash accounts</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Expected Inflows</p>
                <p class="text-xl font-bold text-emerald-600 mt-1">+{{ number_format($totals['inflow'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    AR{{ $totals['recurring_inflow'] > 0 ? ' + Recurring' : '' }}
                </p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Expected Outflows</p>
                <p class="text-xl font-bold text-red-500 mt-1">-{{ number_format($totals['outflow'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    AP{{ $totals['recurring_outflow'] > 0 ? ' + Recurring' : '' }}
                </p>
            </div>
            <div
                class="border rounded-[5px] p-4 {{ $totals['net'] >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }}">
                <p
                    class="text-xs uppercase font-semibold tracking-wide {{ $totals['net'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    Net Cash Flow
                </p>
                <p class="text-xl font-bold mt-1 {{ $totals['net'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                    {{ $totals['net'] >= 0 ? '+' : '' }}{{ number_format($totals['net'], 2) }}
                </p>
                <p class="text-xs mt-0.5 {{ $totals['net'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                    Next {{ $horizon }} days
                </p>
            </div>
        </div>

        {{-- Forecast Chart --}}
        <div class="bg-white border border-gray-200 rounded-[5px] p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Weekly Cash Position</h2>
                <span class="text-[10px] text-gray-400 uppercase tracking-wide">Shaded area = confidence band</span>
            </div>
            <div style="height:220px; position:relative">
                <canvas id="forecastChart"></canvas>
            </div>
        </div>

        {{-- Weekly Breakdown Table --}}
        <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden mb-4">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Weekly Breakdown</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Week of</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Inflow</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Outflow</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Net</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Balance</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Range</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($buckets as $bucket)
                        <tr class="{{ $bucket['balance'] < 0 ? 'bg-red-50/50' : ($bucket['lower'] < 0 && $bucket['balance'] >= 0 ? 'bg-yellow-50/50' : 'hover:bg-gray-50') }}">
                            <td class="px-4 py-2.5 text-gray-700">{{ $bucket['label'] }}</td>
                            <td class="px-4 py-2.5 text-right text-emerald-600">
                                @if ($bucket['inflow'] > 0)
                                    +{{ number_format($bucket['inflow'], 2) }}
                                    @if ($bucket['recurring_inflow'] > 0)
                                        <span class="block text-[10px] text-emerald-400">rec +{{ number_format($bucket['recurring_inflow'], 2) }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right text-red-500">
                                @if ($bucket['outflow'] > 0)
                                    -{{ number_format($bucket['outflow'], 2) }}
                                    @if ($bucket['recurring_outflow'] > 0)
                                        <span class="block text-[10px] text-red-300">rec -{{ number_format($bucket['recurring_outflow'], 2) }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td
                                class="px-4 py-2.5 text-right font-semibold
                        {{ $bucket['net'] > 0 ? 'text-emerald-600' : ($bucket['net'] < 0 ? 'text-red-600' : 'text-gray-400') }}">
                                {{ $bucket['net'] >= 0 ? '+' : '' }}{{ number_format($bucket['net'], 2) }}
                            </td>
                            <td
                                class="px-4 py-2.5 text-right font-bold {{ $bucket['balance'] < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                {{ number_format($bucket['balance'], 2) }}
                                @if ($bucket['balance'] < 0)
                                    <span class="text-xs font-normal text-red-400 ml-1">deficit</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right text-xs text-gray-400">
                                {{ number_format($bucket['lower'], 0) }} — {{ number_format($bucket['upper'], 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Upcoming items detail --}}
        @if ($totalItems > 0)
            <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Upcoming Transactions (Next {{ $horizon }} days)
                    </h2>
                    <span class="text-xs text-gray-400" id="items-count">
                        @php
                            $showTo = min($perPage, $totalItems);
                        @endphp
                        Showing 1–{{ $showTo }} of {{ $totalItems }}
                    </span>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700"
                                data-sort="ref">
                                Reference <span class="sort-icon text-gray-400">↕</span>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700"
                                data-sort="date">
                                Due Date <span class="sort-icon text-gray-400">↕</span>
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700"
                                data-sort="amount">
                                Amount <span class="sort-icon text-gray-400">↕</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody" class="divide-y divide-gray-100">
                        @foreach ($firstPage as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 text-gray-700">{{ $item['ref'] ?? '—' }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($item['type'] === 'inflow')
                                        <span
                                            class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-xs font-semibold">AR
                                            Inflow</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-semibold">AP
                                            Outflow</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-gray-600">{{ $item['due_date'] }}</td>
                                <td
                                    class="px-4 py-2.5 text-right font-semibold
                        {{ $item['type'] === 'inflow' ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ $item['type'] === 'inflow' ? '+' : '-' }}{{ number_format($item['amount'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Pagination bar --}}
                <div id="pagination-bar"
                    class="px-4 py-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                    {{-- Per-page selector --}}
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <label for="per-page-select">Rows per page:</label>
                        <select id="per-page-select"
                            class="border border-gray-300 rounded-[3px] text-xs px-2 py-1 focus:outline-none focus:border-[#0078C8]">
                            @foreach ([15, 25, 50, 100, 200, 300, 400, 500, 1000] as $opt)
                                <option value="{{ $opt }}" @selected($perPage == $opt)>{{ $opt }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Page navigation --}}
                    <div class="flex items-center gap-1.5">
                        <button id="prev-btn"
                            class="px-3 py-1 text-xs border border-gray-300 rounded-[3px] hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                            {{ $totalPages <= 1 ? 'disabled' : '' }}>
                            ← Prev
                        </button>
                        <span id="page-info" class="px-2 text-xs text-gray-500">
                            Page 1 of {{ $totalPages ?: 1 }}
                        </span>
                        <button id="next-btn"
                            class="px-3 py-1 text-xs border border-gray-300 rounded-[3px] hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                            {{ $totalPages <= 1 ? 'disabled' : '' }}>
                            Next →
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
        <script>
            (function() {
                // ── Chart ────────────────────────────────────────────────────────────────
                const labels   = @json(array_column($buckets, 'label'));
                const inflows  = @json(array_column($buckets, 'inflow'));
                const outflows = @json(array_column($buckets, 'outflow'));
                const balance  = @json(array_column($buckets, 'balance'));
                const upper    = @json(array_column($buckets, 'upper'));
                const lower    = @json(array_column($buckets, 'lower'));

                new Chart(document.getElementById('forecastChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Upper Bound',
                                data: upper,
                                type: 'line',
                                borderColor: 'rgba(0,120,200,0.25)',
                                backgroundColor: 'rgba(0,120,200,0.06)',
                                borderWidth: 1,
                                borderDash: [4, 3],
                                pointRadius: 0,
                                fill: false,
                                tension: 0.3,
                                order: 0,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Lower Bound',
                                data: lower,
                                type: 'line',
                                borderColor: 'rgba(0,120,200,0.25)',
                                backgroundColor: 'rgba(0,120,200,0.06)',
                                borderWidth: 1,
                                borderDash: [4, 3],
                                pointRadius: 0,
                                fill: '-1',  // fill between this and the previous dataset (Upper Bound)
                                tension: 0.3,
                                order: 0,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Inflow',
                                data: inflows,
                                backgroundColor: 'rgba(16,185,129,0.7)',
                                borderRadius: 3,
                                order: 2,
                            },
                            {
                                label: 'Outflow',
                                data: outflows.map(v => -v),
                                backgroundColor: 'rgba(239,68,68,0.7)',
                                borderRadius: 3,
                                order: 2,
                            },
                            {
                                label: 'Forecast Balance',
                                data: balance,
                                type: 'line',
                                borderColor: '#0078C8',
                                backgroundColor: 'rgba(0,120,200,0.08)',
                                borderWidth: 2,
                                pointRadius: 3,
                                fill: false,
                                tension: 0.3,
                                order: 1,
                                yAxisID: 'y',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    font: { size: 11 },
                                    filter: function(item) {
                                        // Hide Upper/Lower from legend — they're just the band
                                        return item.text !== 'Upper Bound' && item.text !== 'Lower Bound';
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        const val = ctx.parsed.y;
                                        if (val == null) return '';
                                        const formatted = val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                        return ctx.dataset.label + ': ' + formatted;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 } }
                            },
                            y: {
                                grid: { color: 'rgba(0,0,0,.04)' },
                                ticks: {
                                    font: { size: 10 },
                                    callback: v => v.toLocaleString()
                                },
                            },
                        },
                    },
                });

                // ── Transactions table — sorting + pagination ─────────────────────────────
                const tbody = document.getElementById('items-tbody');
                const countEl = document.getElementById('items-count');
                const pageInfoEl = document.getElementById('page-info');
                const prevBtn = document.getElementById('prev-btn');
                const nextBtn = document.getElementById('next-btn');
                const perPageSel = document.getElementById('per-page-select');

                if (!tbody) return; // table not rendered (0 items)

                const ITEMS_URL = @json(route('account.cash-flow-forecast.items'));
                const HORIZON = {{ $horizon }};

                let sortBy = @json($sortBy);
                let sortDir = @json($sortDir);
                let perPage = {{ $perPage }};
                let currentPage = 1; // page 1 already rendered server-side
                let totalItems = {{ $totalItems }};
                let totalPages = {{ $totalPages ?: 1 }};
                let loading = false;

                // ── Row builder ──────────────────────────────────────────────────────────
                function buildRow(item) {
                    const badge = item.type === 'inflow' ?
                        '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-xs font-semibold">AR Inflow</span>' :
                        '<span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-semibold">AP Outflow</span>';
                    const cls = item.type === 'inflow' ? 'text-emerald-600' : 'text-red-500';
                    const sign = item.type === 'inflow' ? '+' : '-';
                    const formatted = parseFloat(item.amount).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50';
                    tr.innerHTML =
                        '<td class="px-4 py-2.5 text-gray-700">' + (item.ref || '—') + '</td>' +
                        '<td class="px-4 py-2.5">' + badge + '</td>' +
                        '<td class="px-4 py-2.5 text-gray-600">' + item.due_date + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-semibold ' + cls + '">' + sign + formatted + '</td>';
                    return tr;
                }

                // ── Update UI controls ───────────────────────────────────────────────────
                function updateUI() {
                    // Count label: "Showing 16–30 of 120"
                    const from = totalItems === 0 ? 0 : (currentPage - 1) * perPage + 1;
                    const to = Math.min(currentPage * perPage, totalItems);
                    if (countEl) countEl.textContent = 'Showing ' + from + '–' + to + ' of ' + totalItems;
                    if (pageInfoEl) pageInfoEl.textContent = 'Page ' + currentPage + ' of ' + (totalPages || 1);
                    if (prevBtn) prevBtn.disabled = currentPage <= 1 || loading;
                    if (nextBtn) nextBtn.disabled = currentPage >= totalPages || loading;
                    if (perPageSel) perPageSel.disabled = loading;
                }

                // ── Fetch a page (always replaces tbody) ─────────────────────────────────
                function fetchPage(page) {
                    if (loading) return;
                    loading = true;
                    updateUI(); // disable controls immediately

                    const url = ITEMS_URL +
                        '?page=' + page +
                        '&horizon=' + HORIZON +
                        '&sort_by=' + encodeURIComponent(sortBy) +
                        '&sort_dir=' + encodeURIComponent(sortDir) +
                        '&per_page=' + perPage;

                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            tbody.innerHTML = '';
                            data.items.forEach(function(item) {
                                tbody.appendChild(buildRow(item));
                            });

                            currentPage = data.page;
                            totalItems = data.total;
                            totalPages = data.total_pages;
                            loading = false;
                            updateUI();
                        })
                        .catch(function() {
                            loading = false;
                            updateUI();
                        });
                }

                // ── Sort header clicks ────────────────────────────────────────────────────
                function updateSortHeaders() {
                    document.querySelectorAll('[data-sort]').forEach(function(th) {
                        const icon = th.querySelector('.sort-icon');
                        if (!icon) return;
                        if (th.dataset.sort === sortBy) {
                            icon.textContent = sortDir === 'asc' ? ' ↑' : ' ↓';
                            th.classList.add('text-[#0078C8]');
                            th.classList.remove('text-gray-500');
                        } else {
                            icon.textContent = ' ↕';
                            th.classList.remove('text-[#0078C8]');
                            th.classList.add('text-gray-500');
                        }
                    });
                }

                document.querySelectorAll('[data-sort]').forEach(function(th) {
                    th.addEventListener('click', function() {
                        const col = this.dataset.sort;
                        if (sortBy === col) {
                            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                        } else {
                            sortBy = col;
                            sortDir = 'asc';
                        }
                        updateSortHeaders();
                        fetchPage(1);
                    });
                });

                // ── Per-page change ───────────────────────────────────────────────────────
                if (perPageSel) {
                    perPageSel.addEventListener('change', function() {
                        perPage = parseInt(this.value);
                        totalPages = Math.ceil(totalItems / perPage);
                        fetchPage(1);
                    });
                }

                // ── Prev / Next ───────────────────────────────────────────────────────────
                if (prevBtn) prevBtn.addEventListener('click', function() {
                    fetchPage(currentPage - 1);
                });
                if (nextBtn) nextBtn.addEventListener('click', function() {
                    fetchPage(currentPage + 1);
                });

                // Initialise headers & controls from server-rendered state
                updateSortHeaders();
                updateUI();
            })();
        </script>
    @endpush
@endsection
