@extends('layouts.auth')

@section('title')
    User Insights Dashboard
@endsection

@push('styles')
<style>
    .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    .chart-container { position: relative; width: 100%; }
</style>
@endpush

@section('content')
<div class="p-3 sm:p-4 lg:p-6">

    {{-- ── Header ── --}}
    <div class="flex flex-col xs:flex-row xs:items-center justify-between gap-2 sm:gap-3 mb-4 sm:mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">User Insights</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Real-time overview of user activity and engagement.</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <span class="px-2.5 py-1.5 bg-white rounded-lg border text-xs font-semibold text-gray-600 flex items-center gap-2 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                Live
            </span>
            <button onclick="window.location.reload()"
                class="p-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-colors shadow-sm"
                title="Refresh">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">

        <div class="stat-card bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
                    </svg>
                </div>
                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">All</span>
            </div>
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Total Users</p>
            <p class="text-2xl sm:text-3xl font-black text-gray-900 mt-0.5">{{ $userCount }}</p>
            <div class="mt-2 h-1 bg-blue-100 rounded-full"><div class="h-1 bg-blue-500 rounded-full w-full"></div></div>
        </div>

        <div class="stat-card bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 bg-emerald-50 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Today</span>
            </div>
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Active Today</p>
            <p class="text-2xl sm:text-3xl font-black text-gray-900 mt-0.5">{{ $activeUserCount }}</p>
            <div class="mt-2 h-1 bg-emerald-100 rounded-full">
                <div class="h-1 bg-emerald-500 rounded-full"
                    style="width: {{ $userCount > 0 ? min(100, round(($activeUserCount/$userCount)*100)) : 0 }}%"></div>
            </div>
        </div>

        <div class="stat-card bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 bg-purple-50 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <span class="text-xs font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">Week</span>
            </div>
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">New This Week</p>
            <p class="text-2xl sm:text-3xl font-black text-gray-900 mt-0.5">{{ $newUserCount }}</p>
            <div class="mt-2 h-1 bg-purple-100 rounded-full">
                <div class="h-1 bg-purple-500 rounded-full"
                    style="width: {{ $userCount > 0 ? min(100, round(($newUserCount/$userCount)*100)) : 0 }}%"></div>
            </div>
        </div>

        <div class="stat-card bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 bg-amber-50 rounded-lg">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Total</span>
            </div>
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Total Events</p>
            <p class="text-2xl sm:text-3xl font-black text-gray-900 mt-0.5">{{ $activity_logs->total() }}</p>
            <div class="mt-2 h-1 bg-amber-100 rounded-full"><div class="h-1 bg-amber-500 rounded-full w-3/4"></div></div>
        </div>

    </div>

    {{-- ── Row 1: Trend Chart + Top Performers ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6">

        {{-- 7-Day Activity Trend --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5">
            <div class="flex flex-col xs:flex-row xs:items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Activity Trend</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Daily event volume — last 7 days</p>
                </div>
                <span class="self-start xs:self-auto text-xs font-semibold text-[#0078C8] bg-blue-50 px-2 py-1 rounded-lg">7 Days</span>
            </div>
            <div class="chart-container" style="height: 220px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        {{-- Top Performers --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5">
            <div class="mb-4">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Top Performers</h2>
                <p class="text-xs text-gray-400 mt-0.5">Most active users by events</p>
            </div>
            <div class="chart-container mb-4" style="height: 160px;">
                <canvas id="topPerformersChart"></canvas>
            </div>
            <div class="space-y-2.5 mt-2">
                @foreach($top_contributors as $i => $actor)
                @php $maxCount = $top_contributors->first()->count; @endphp
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-md bg-gray-900 text-white flex items-center justify-center text-[9px] font-bold flex-shrink-0">{{ $i+1 }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center mb-0.5">
                            <span class="text-xs font-semibold text-gray-800 truncate">{{ $actor->user->full_name ?? 'Unknown' }}</span>
                            <span class="text-[10px] text-gray-400 ml-1 flex-shrink-0">{{ $actor->count }}</span>
                        </div>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full">
                            <div class="bg-[#0078C8] h-1.5 rounded-full transition-all"
                                style="width: {{ $maxCount > 0 ? round(($actor->count/$maxCount)*100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ── Row 2: Behavioral Matrix + Action Distribution ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 lg:gap-6">

        {{-- Behavioral Matrix (scatter) --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5">
            <div class="mb-4">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Behavioral Matrix</h2>
                <p class="text-xs text-gray-400 mt-0.5">User activity timeline by session</p>
            </div>
            <div class="chart-container" style="height: 260px;">
                <canvas id="behaviorChart"></canvas>
            </div>
        </div>

        {{-- Right column: Daily Volume + Action Breakdown stacked --}}
        <div class="flex flex-col gap-3 sm:gap-4">

            {{-- Daily Volume Bar --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5">
                <div class="mb-4">
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Daily Volume</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Events per day (7 days)</p>
                </div>
                <div class="chart-container" style="height: 120px;">
                    <canvas id="volumeBarChart"></canvas>
                </div>
            </div>

            {{-- Action Type Donut --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 flex-1">
                <div class="mb-4">
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Action Breakdown</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Recent events by type</p>
                </div>
                @php
                    $creates = $activity_logs->filter(fn($l) => str_contains(strtolower($l->action), 'creat') || str_contains(strtolower($l->action), 'add'))->count();
                    $updates = $activity_logs->filter(fn($l) => str_contains(strtolower($l->action), 'updat') || str_contains(strtolower($l->action), 'edit'))->count();
                    $deletes = $activity_logs->filter(fn($l) => str_contains(strtolower($l->action), 'delet'))->count();
                    $others  = $activity_logs->count() - $creates - $updates - $deletes;
                @endphp
                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <div class="chart-container flex-shrink-0" style="width: 110px; height: 110px;">
                        <canvas id="actionDonutChart"></canvas>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-1 gap-2 sm:gap-2.5 w-full">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm bg-emerald-500 flex-shrink-0"></div>
                                <span class="text-xs text-gray-600">Create</span>
                            </div>
                            <span class="text-xs font-bold text-gray-900">{{ $creates }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm bg-blue-500 flex-shrink-0"></div>
                                <span class="text-xs text-gray-600">Update</span>
                            </div>
                            <span class="text-xs font-bold text-gray-900">{{ $updates }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm bg-red-500 flex-shrink-0"></div>
                                <span class="text-xs text-gray-600">Delete</span>
                            </div>
                            <span class="text-xs font-bold text-gray-900">{{ $deletes }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm bg-gray-400 flex-shrink-0"></div>
                                <span class="text-xs text-gray-600">Other</span>
                            </div>
                            <span class="text-xs font-bold text-gray-900">{{ $others }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const blue    = '#2563EB';
    const emerald = '#10B981';
    const purple  = '#8B5CF6';
    const amber   = '#F59E0B';
    const red     = '#EF4444';
    const colors  = [blue, emerald, purple, amber, red, '#06B6D4'];

    const trendData = @json($trend_data);

    // ── 1. Activity Trend Line ──
    (function() {
        const ctx = document.getElementById('trendChart').getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 220);
        grad.addColorStop(0, 'rgba(37,99,235,0.18)');
        grad.addColorStop(1, 'rgba(37,99,235,0)');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.map(d => d.date),
                datasets: [{
                    label: 'Events',
                    data: trendData.map(d => d.count),
                    fill: true,
                    backgroundColor: grad,
                    borderColor: blue,
                    borderWidth: 2.5,
                    pointBackgroundColor: blue,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F3F4F6' }, border: { display: false },
                         ticks: { color: '#9CA3AF', font: { size: 11 }, precision: 0 } },
                    x: { grid: { display: false },
                         ticks: { color: '#9CA3AF', font: { size: 11 } } }
                }
            }
        });
    })();

    // ── 2. Top Performers Horizontal Bar ──
    (function() {
        const contributors = @json($top_contributors);
        const ctx = document.getElementById('topPerformersChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: contributors.map(c => c.user?.full_name ?? 'Unknown'),
                datasets: [{
                    label: 'Events',
                    data: contributors.map(c => c.count),
                    backgroundColor: colors,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#F3F4F6' }, border: { display: false },
                         ticks: { color: '#9CA3AF', font: { size: 10 }, precision: 0 } },
                    y: { grid: { display: false }, ticks: { color: '#374151', font: { size: 10, weight: '600' } } }
                }
            }
        });
    })();

    // ── 3. Behavioral Matrix Scatter ──
    (function() {
        const userActivity = @json($user_activity);
        const users = [...new Set(userActivity.map(u => u.user_name))];
        const datasets = users.map((name, i) => {
            const points = [];
            userActivity.filter(u => u.user_name === name).forEach(u => {
                points.push({ x: name, y: new Date(u.login_time), action: 'Login' });
                (u.activities || []).forEach(a => points.push({ x: name, y: new Date(a.created_at), action: a.action }));
            });
            return { label: name, data: points, backgroundColor: colors[i % colors.length],
                     pointRadius: 5, pointHoverRadius: 8, showLine: false };
        });
        const ctx = document.getElementById('behaviorChart').getContext('2d');
        new Chart(ctx, {
            type: 'scatter',
            data: { datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 }, padding: 10 } },
                    tooltip: { callbacks: { label: c => `${c.dataset.label}: [${c.raw.action}] at ${new Date(c.raw.y).toLocaleTimeString()}` } }
                },
                scales: {
                    x: { type: 'category', labels: users, grid: { display: false },
                         ticks: { color: '#374151', font: { size: 10 } } },
                    y: { type: 'time', time: { unit: 'hour', tooltipFormat: 'HH:mm' },
                         grid: { color: '#F3F4F6', borderDash: [3,3] },
                         ticks: { color: '#9CA3AF', font: { size: 10 } } }
                }
            }
        });
    })();

    // ── 4. Daily Volume Bar ──
    (function() {
        const ctx = document.getElementById('volumeBarChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: trendData.map(d => d.date),
                datasets: [{
                    data: trendData.map(d => d.count),
                    backgroundColor: trendData.map((_, i) => i === trendData.length - 1 ? blue : 'rgba(37,99,235,0.25)'),
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { display: false },
                    x: { grid: { display: false }, ticks: { color: '#9CA3AF', font: { size: 10 } } }
                }
            }
        });
    })();

    // ── 5. Action Donut ──
    (function() {
        const ctx = document.getElementById('actionDonutChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Create', 'Update', 'Delete', 'Other'],
                datasets: [{
                    data: [{{ $creates }}, {{ $updates }}, {{ $deletes }}, {{ max(0, $others) }}],
                    backgroundColor: [emerald, blue, red, '#9CA3AF'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: false } }
            }
        });
    })();
});
</script>
@endsection
