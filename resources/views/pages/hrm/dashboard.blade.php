@extends('layouts.auth')
@section('title')
    HRM Dashboard
@endsection

@section('content')
    <div class="p-4 bg-gray-50 min-h-screen">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div class="flex flex-col gap-1">
                <h2 class="text-[#101018] text-2xl md:text-3xl font-black tracking-tight flex items-center gap-2">
                    Welcome back, HR Manager
                    <span class="animate-pulse inline-block w-2 h-2 rounded-full bg-green-500"></span>
                </h2>
                <div class="flex items-center gap-2 text-[#5e5f8d] text-sm md:text-base">
                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                    {{ now()->format('l, d F Y') }}
                    <span class="mx-1">•</span>
                    <span class="text-primary font-medium">
                        {{ $dashboardData['hasDateFilter'] ? $dashboardData['startDate'].' to '.$dashboardData['endDate'] : 'All time' }}
                    </span>
                </div>
            </div>

            <form
                id="date_range_filter_form"
                method="GET"
                action="{{ route('hrm.index') }}"
                class="flex flex-wrap items-end gap-2 bg-white p-2 rounded-lg border border-[#dadae7] group hover:border-primary transition-colors">
                <div class="flex items-center gap-2 pl-2 border-r border-gray-100 pr-2">
                    <span class="material-symbols-outlined text-base text-primary">calendar_month</span>
                </div>
                <div>
                    <label for="dashboard_from_date" class="block px-1 text-[10px] font-bold uppercase tracking-wide text-gray-500">From</label>
                    <input id="dashboard_from_date" name="from_date" type="date"
                        value="{{ $dashboardData['startDate'] }}"
                        class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-800 focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="dashboard_to_date" class="block px-1 text-[10px] font-bold uppercase tracking-wide text-gray-500">To</label>
                    <input id="dashboard_to_date" name="to_date" type="date"
                        value="{{ $dashboardData['endDate'] }}"
                        class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-800 focus:border-primary focus:ring-primary">
                </div>
                {{-- <span class="px-2 py-2 text-xs font-semibold text-gray-500">Changes load automatically</span> --}}
            </form>
        </div>

        @php
            $periodFilterParams = $dashboardData['hasDateFilter'] ? [
                'from_date' => $dashboardData['startDate'],
                'to_date' => $dashboardData['endDate'],
            ] : [];

            $allEmployeesUrl = route('hrm.employee.index', array_merge(['status' => 'all'], $periodFilterParams));
            $attendancePeriodUrl = route('hrm.attendance.index', $periodFilterParams);
            $openPositionsUrl = route('hrm.jobs.index', array_merge(['status' => 'open'], $periodFilterParams));
            $leavePeriodUrl = route('hrm.leave.index', array_merge($periodFilterParams, ['overlap' => 1]));
        @endphp

        @if($tenureMilestonesDailyCount > 0)
            <div class="mb-6">
                <a href="{{ route('hrm.tenure_milestones.index') }}" class="flex items-center justify-between p-4 bg-primary/5 border border-[#005fa3] rounded-xl hover:bg-primary/10 transition-colors group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center animate-bounce">
                            <span class="material-symbols-outlined text-primary text-2xl">celebration</span>
                        </div>
                        <div >
                            <h4 class="text-sm font-bold text-gray-900">Tenure Milestone Notifications</h4>
                            <p class="text-xs text-gray-600">You have {{ $tenureMilestonesDailyCount }} upcoming/new employee tenure milestones to acknowledge.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-[#005fa3] font-bold text-xs group-hover:underline">
                        VIEW ALL
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </div>
                </a>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <a href="{{ $allEmployeesUrl }}"
                class="block bg-white border border-[#dadae7] rounded-xl p-5 transition hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-[#5e5f8d] text-sm font-medium">Total Employees</p>
                    <span class="material-symbols-outlined text-primary">groups</span>
                </div>
                <p class="text-2xl font-bold">{{ $totalEmployees }}</p>
                <div class="mt-3 flex gap-1 h-1.5 w-full rounded-full overflow-hidden bg-gray-100">
                    @if($totalEmployees > 0)
                        <div class="bg-green-500 transition-all" style="width: {{ round($activeEmployees / $totalEmployees * 100) }}%" title="Active"></div>
                        <div class="bg-yellow-400 transition-all" style="width: {{ round($inactiveEmployees / $totalEmployees * 100) }}%" title="Inactive"></div>
                        <div class="bg-blue-400 transition-all" style="width: {{ round($resignedEmployees / $totalEmployees * 100) }}%" title="Resigned"></div>
                        <div class="bg-red-400 transition-all" style="width: {{ round($terminatedEmployees / $totalEmployees * 100) }}%" title="Terminated"></div>
                    @endif
                </div>
            </a>

            <a href="{{ $attendancePeriodUrl }}"
                class="block bg-white border border-[#dadae7] rounded-xl p-5 transition hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-[#5e5f8d] text-sm font-medium">Attendance</p>
                    <span class="material-symbols-outlined text-green-600">how_to_reg</span>
                </div>

                <p class="text-2xl font-bold">
                    {{ number_format($dashboardData['todayCount']) }}
                    {{-- {{ number_format($dashboardData['attendance_rate_today'], 1) }}% --}}
                </p>

                <p class="text-xs text-[#5e5f8d] mt-1">
                    @if (! $dashboardData['hasDateFilter'])
                        <span class="text-gray-500 font-bold">All attendance records</span>
                    @elseif ($dashboardData['attendance_rate_today'] == 0)
                        <span class="text-gray-400 font-bold">Waiting for check-ins</span>
                    @else
                        <span
                            class="{{ $dashboardData['attendance_change_percentage'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                            {{ $dashboardData['attendance_change_percentage'] > 0 ? '+' : '' }}{{ number_format($dashboardData['attendance_change_percentage'], 1) }}%
                        </span>
                        vs previous period
                    @endif
                </p>
            </a>

            <a href="{{ $openPositionsUrl }}"
                class="block bg-white border border-[#dadae7] rounded-xl p-5 transition hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-[#5e5f8d] text-sm font-medium">Open Positions</p>
                    <span class="material-symbols-outlined text-orange-500">work_outline</span>
                </div>
                <p class="text-2xl font-bold">{{ number_format($dashboardData['openPositionsCount']) }}</p>
                <p class="text-xs text-[#5e5f8d] mt-1">
                    Active in this period</p>
            </a>

            <a href="{{ $leavePeriodUrl }}"
                class="block bg-white border border-[#dadae7] rounded-xl p-5 transition hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-[#5e5f8d] text-sm font-medium">Total Leaves</p>
                    <span class="material-symbols-outlined text-red-500">pending_actions</span>
                </div>
                <p class="text-2xl font-bold">
                    {{ number_format($dashboardData['onLeaveCount']) }}
                </p>
                {{-- <p class="text-xs text-[#5e5f8d] mt-1"><span class="text-red-500 font-bold">3 urgent</span> needs approval
                </p> --}}
            </a>
        </div>

        <!-- Employee Status Breakdown -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <a href="{{ route('hrm.employee.index', array_merge(['status' => 'active'], $periodFilterParams)) }}"
                class="bg-white border border-[#dadae7] rounded-xl p-5 flex items-center gap-4 transition hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-green-600 text-base">check_circle</span>
                </div>
                <div>
                    <p class="text-[#5e5f8d] text-xs font-medium uppercase tracking-wide">Active</p>
                    <p class="text-2xl font-bold text-green-600">{{ $activeEmployees }}</p>
                    <p class="text-xs text-[#5e5f8d] mt-0.5">
                        {{ $totalEmployees > 0 ? round($activeEmployees / $totalEmployees * 100) : 0 }}% of total
                    </p>
                </div>
            </a>

            <a href="{{ route('hrm.employee.index', array_merge(['status' => 'inactive'], $periodFilterParams)) }}"
                class="bg-white border border-[#dadae7] rounded-xl p-5 flex items-center gap-4 transition hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-yellow-600 text-base">pause_circle</span>
                </div>
                <div>
                    <p class="text-[#5e5f8d] text-xs font-medium uppercase tracking-wide">Inactive</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $inactiveEmployees }}</p>
                    <p class="text-xs text-[#5e5f8d] mt-0.5">
                        {{ $totalEmployees > 0 ? round($inactiveEmployees / $totalEmployees * 100) : 0 }}% of total
                    </p>
                </div>
            </a>

            <a href="{{ route('hrm.employee.index', array_merge(['status' => 'resigned'], $periodFilterParams)) }}"
                class="bg-white border border-[#dadae7] rounded-xl p-5 flex items-center gap-4 transition hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-blue-600 text-base">logout</span>
                </div>
                <div>
                    <p class="text-[#5e5f8d] text-xs font-medium uppercase tracking-wide">Resigned</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $resignedEmployees }}</p>
                    <p class="text-xs text-[#5e5f8d] mt-0.5">
                        {{ $totalEmployees > 0 ? round($resignedEmployees / $totalEmployees * 100) : 0 }}% of total
                    </p>
                </div>
            </a>

            <a href="{{ route('hrm.employee.index', array_merge(['status' => 'terminated'], $periodFilterParams)) }}"
                class="bg-white border border-[#dadae7] rounded-xl p-5 flex items-center gap-4 transition hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-red-600 text-base">person_remove</span>
                </div>
                <div>
                    <p class="text-[#5e5f8d] text-xs font-medium uppercase tracking-wide">Terminated</p>
                    <p class="text-2xl font-bold text-red-600">{{ $terminatedEmployees }}</p>
                    <p class="text-xs text-[#5e5f8d] mt-0.5">
                        {{ $totalEmployees > 0 ? round($terminatedEmployees / $totalEmployees * 100) : 0 }}% of total
                    </p>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <div class="lg:col-span-4 bg-white border border-[#dadae7] rounded-xl p-6 ">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold">Employee Attendance</h3>
                    <a href="{{ $attendancePeriodUrl }}" class="text-primary text-xs font-bold hover:underline">View All</a>
                </div>
                <div class="flex flex-col gap-4">
                    @foreach ($todayAttendances as $attendance)
                        <div class="flex gap-3 items-start border-l-2 border-primary pl-4 py-1">
                            <div class="size-8 rounded-full bg-gray-100 flex items-center justify-center"><span
                                    class="material-symbols-outlined text-sm">person</span></div>
                            <div class="flex-1">
                                <p class="text-sm font-bold">{{ $attendance->employee?->full_name }} <span
                                        class="text-xs font-normal text-[#5e5f8d]">Checked-in</span></p>
                                <p class="text-xs text-[#5e5f8d]">{{ $attendance->employee?->department?->department }} •
                                    {{ $attendance->check_in ? $attendance->check_in->format('H:i A') : '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-4 bg-white border border-[#dadae7] rounded-xl p-6 ">
                <h3 class="text-lg font-bold mb-6">Department Overview</h3>

                <div class="relative size-56 mx-auto flex items-center justify-center">
                    <canvas id="departmentChart"></canvas>

                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-2xl font-bold">{{ number_format($dashboardData['total']) }}</span>
                        <span class="text-xs text-[#5e5f8d]">Active Employees</span>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3">
                    @foreach ($dashboardData['labels'] as $index => $label)
                        <a href="{{ route('hrm.employee.index', array_merge(['status' => 'active', 'department_id' => $dashboardData['departmentIds'][$index] ?? null], $periodFilterParams)) }}"
                            class="flex items-center justify-between rounded-lg px-2 py-1 text-sm transition hover:bg-[#f0f0f5] focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <div class="flex items-center gap-2">
                                {{-- Use the color array based on index --}}
                                <div class="size-3 rounded-full"
                                    style="background-color: {{ $dashboardData['colors'][$index] ?? '#cbd5e1' }}">
                                </div>
                                <span class="text-[#5e5f8d]">{{ $label }}</span>
                            </div>
                            <span class="font-bold">{{ number_format($dashboardData['counts'][$index]) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-4 bg-white border border-[#dadae7] rounded-xl p-6 ">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold">Workforce Composition</h3>
                    <span class="text-xs text-[#5e5f8d]">Active employees</span>
                </div>
                <div class="h-72 w-full relative">
                    <canvas id="employmentTypeChart"></canvas>
                    @if ($employmentTypeChart['counts']->sum() === 0)
                        <div class="absolute inset-0 flex items-center justify-center text-sm text-[#5e5f8d] pointer-events-none">
                            No active employee data available
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-6 bg-white border border-[#dadae7] rounded-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold">{{ ucfirst($period) }} Attendance Trend</h3>
                        <p class="text-xs text-[#5e5f8d] mt-1">Check-ins and late arrivals for {{ strtolower($dashboardData['periodLabel']) }}</p>
                    </div>
                    <a href="{{ route('hrm.attendance.index') }}" class="text-primary text-xs font-bold hover:underline">View Attendance</a>
                </div>

                <div class="h-72 w-full">
                    <canvas id="attendanceTrendChart"></canvas>
                </div>
            </div>

            <div class="lg:col-span-6 bg-white border border-[#dadae7] rounded-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold">HR Cases Trend</h3>
                        <p class="text-xs text-[#5e5f8d] mt-1">Warnings, complaints and resignations</p>
                    </div>
                    <span class="text-xs text-[#5e5f8d]">{{ $dashboardData['periodLabel'] }}</span>
                </div>
                <div class="h-72 w-full">
                    <canvas id="hrCasesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const dateRangeForm = document.getElementById('date_range_filter_form');
            const fromDateInput = document.getElementById('dashboard_from_date');
            const toDateInput = document.getElementById('dashboard_to_date');

            if (!dateRangeForm || !fromDateInput || !toDateInput) {
                return;
            }

            let submitTimer;
            const loadDateRange = () => {
                const bothEmpty = !fromDateInput.value && !toDateInput.value;
                const completeValidRange = fromDateInput.value
                    && toDateInput.value
                    && fromDateInput.value <= toDateInput.value;

                if (!bothEmpty && !completeValidRange) {
                    return;
                }

                clearTimeout(submitTimer);
                submitTimer = setTimeout(() => {
                    if (typeof dateRangeForm.requestSubmit === 'function') {
                        dateRangeForm.requestSubmit();
                        return;
                    }

                    dateRangeForm.submit();
                }, 250);
            };

            fromDateInput.addEventListener('change', loadDateRange);
            toDateInput.addEventListener('change', loadDateRange);
        })();
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('departmentChart').getContext('2d');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($dashboardData['labels']) !!},
                    datasets: [{
                        data: {!! json_encode($dashboardData['counts']) !!},
                        backgroundColor: {!! json_encode($dashboardData['colors']) !!},
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 8,
                        cutout: '75%'
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
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const employmentTypeCtx = document.getElementById('employmentTypeChart').getContext('2d');
            new Chart(employmentTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($employmentTypeChart['labels']),
                    datasets: [{
                        data: @json($employmentTypeChart['counts']),
                        backgroundColor: @json($employmentTypeChart['colors']),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                padding: 16,
                                color: '#5e5f8d',
                                font: { size: 10, weight: 'bold' }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                    const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                                    return ` ${context.label}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            const attendanceTrendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
            new Chart(attendanceTrendCtx, {
                type: 'line',
                data: {
                    labels: @json($attendanceTrend['labels']),
                    datasets: [
                        {
                            label: 'Check-ins',
                            data: @json($attendanceTrend['checkIns']),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.12)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Late arrivals',
                            data: @json($attendanceTrend['lateArrivals']),
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.08)',
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 8, color: '#5e5f8d' }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#5e5f8d', font: { size: 10, weight: 'bold' } }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, precision: 0 },
                            grid: { color: '#f0f0f5' }
                        }
                    }
                }
            });

            const hrCasesCtx = document.getElementById('hrCasesChart').getContext('2d');
            new Chart(hrCasesCtx, {
                type: 'bar',
                data: {
                    labels: @json($hrCasesChart['labels']),
                    datasets: [
                        {
                            label: 'Warnings',
                            data: @json($hrCasesChart['warnings']),
                            backgroundColor: '#f59e0b',
                            borderRadius: 4,
                        },
                        {
                            label: 'Complaints',
                            data: @json($hrCasesChart['complaints']),
                            backgroundColor: '#ef4444',
                            borderRadius: 4,
                        },
                        {
                            label: 'Resignations',
                            data: @json($hrCasesChart['resignations']),
                            backgroundColor: '#6366f1',
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 8, color: '#5e5f8d' }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#5e5f8d', font: { size: 10, weight: 'bold' } }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, precision: 0 },
                            grid: { color: '#f0f0f5' }
                        }
                    }
                }
            });
        });
    </script>
@endpush
