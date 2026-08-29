@extends('layouts.auth')

@section('title', 'Overtime Pattern Analysis')

@section('content')
    <div class="p-6 bg-gray-50 min-h-screen">

        {{-- HEADER ── --}}
        <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

            <div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">
                    Overtime Analysis
                </h2>

                <p class="text-gray-500 text-sm mt-1">
                    Identifying efficiency gaps and workforce patterns over the last {{ $months }} months
                </p>
            </div>

            <div class="flex items-center gap-3">

                <form action="{{ route('payroll.reports.overtime') }}" method="GET" class="flex items-center gap-3">

                    <select name="months" onchange="this.form.submit()"
                        class="bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium focus:ring-indigo-500 focus:border-indigo-500 px-4 py-2">
                        <option value="3" {{ $months == 3 ? 'selected' : '' }}>
                            Last 3 Months
                        </option>

                        <option value="6" {{ $months == 6 ? 'selected' : '' }}>
                            Last 6 Months
                        </option>

                        <option value="12" {{ $months == 12 ? 'selected' : '' }}>
                            Last 12 Months
                        </option>
                    </select>

                    <button type="button" onclick="window.print()"
                        class="p-2.5 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <i class="fa-solid fa-print"></i>
                    </button>

                </form>

                @if (request()->query())
                    <a href="{{ route('payroll.reports.overtime') }}"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                        Reset
                    </a>
                @endif

            </div>

        </div>

        @include('pages.payroll.reports.partials.tabs')

        {{-- ALERTS ── --}}
        @if (!empty($analysis['alerts']))
            <div class="mb-8 space-y-3">
                @foreach ($analysis['alerts'] as $alert)
                    <div
                        class="flex items-center justify-between p-4 bg-white border-l-4 {{ $alert['severity'] === 'critical' ? 'border-red-500' : 'border-amber-500' }} rounded-r-xl shadow-sm">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center {{ $alert['severity'] === 'critical' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">
                                <i
                                    class="fa-solid {{ $alert['severity'] === 'critical' ? 'fa-bolt' : 'fa-chart-line' }}"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider">{{ $alert['title'] }}
                                </h4>
                                <p class="text-xs text-gray-500">{{ $alert['message'] }}</p>
                            </div>
                        </div>
                        @if ($alert['type'] === 'excessive_ot')
                            <span class="text-[10px] font-bold bg-red-100 text-red-700 px-2 py-1 rounded uppercase">Review
                                Resource</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- CHARTS ROW ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Trend Chart --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-chart-area text-indigo-500"></i>
                    Monthly Overtime Trend (Hours)
                </h3>
                <div style="height: 300px;">
                    <canvas id="otTrendChart"></canvas>
                </div>
            </div>

            {{-- Department Breakdown --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-indigo-500"></i>
                    OT Hours by Department
                </h3>
                <div style="height: 300px;">
                    <canvas id="deptOtChart"></canvas>
                </div>
            </div>
        </div>

        {{-- TOP EARNERS ── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex justify-between items-center">

                <h3 class="text-sm font-bold text-gray-800">
                    Top Overtime Earners
                </h3>

                <div class="flex items-center gap-4">

                    <form method="GET" id="perPageForm">
                        <input type="hidden" name="months" value="{{ $months }}">

                        <select name="per_page"
                            class="select2 w-24 bg-white py-1.5 px-3 rounded-lg text-sm border border-gray-300 focus:ring-0 focus:outline-none"
                            onchange="document.getElementById('perPageForm').submit();">
                            @foreach ([10, 20, 30, 50, 100, 200, 500, 1000] as $size)
                                <option value="{{ $size }}"
                                    {{ (int) request('per_page', 10) == $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    <span class="text-xs text-gray-400 whitespace-nowrap">
                        Showing top {{ $analysis['top_earners']->count() }}
                        {{ $analysis['top_earners']->count() === 1 ? 'employee' : 'employees' }}
                        by total OT hours
                    </span>

                </div>

            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-widest">
                        <tr>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4 text-center">Total Hours</th>
                            <th class="px-6 py-4 text-center">Avg/Month</th>
                            <th class="px-6 py-4 text-right">Total Cost</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($analysis['top_earners'] as $earner)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $earner->full_name }}</div>
                                    <div class="text-xs text-gray-400">ID: #{{ $earner->employee_id ?? '---' }}</div>
                                </td>
                                <td class="px-6 py-4 text-center font-mono font-bold">
                                    {{ number_format($earner->total_hours, 1) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2 py-1 rounded text-[11px] font-bold {{ $earner->avg_monthly_hours > $analysis['threshold'] ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-600' }}">
                                        {{ number_format($earner->avg_monthly_hours, 1) }}h
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-indigo-600">
                                    {{ currency()?->symbol }}{{ number_format($earner->total_amount, 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($earner->avg_monthly_hours > $analysis['threshold'])
                                        <div class="flex items-center gap-1.5 text-red-500 text-[11px] font-bold">
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            EXCESSIVE
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1.5 text-green-500 text-[11px] font-bold">
                                            <i class="fa-solid fa-circle-check"></i>
                                            OPTIMAL
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No overtime data
                                    found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if (
                    $analysis['top_earners'] instanceof \Illuminate\Pagination\LengthAwarePaginator &&
                        $analysis['top_earners']->hasPages())
                    <div class="p-4 bg-white border-t border-gray-100">
                        <x-data-table-paginatior :paginator="$analysis['top_earners']" />
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const trendCtx = document.getElementById('otTrendChart').getContext('2d');
        const deptCtx = document.getElementById('deptOtChart').getContext('2d');

        // ── Trend Chart ──
        const trendData = @json($analysis['monthly_trend']);
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(d => d.month),
                datasets: [{
                    label: 'Total OT Hours',
                    data: trendData.map(d => d.total_hours),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#6366f1',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // ── Dept Chart ──
        const deptData = @json($analysis['dept_breakdown']);
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: deptData.map(d => d.dept_name || 'Unassigned'),
                datasets: [{
                    label: 'OT Hours',
                    data: deptData.map(d => d.total_hours),
                    backgroundColor: '#818cf8',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
@endpush
