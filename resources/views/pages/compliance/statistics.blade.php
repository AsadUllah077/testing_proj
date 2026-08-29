@extends('layouts.auth')

@section('title', 'Compliance Analytics')

@section('content')
    <div class="min-h-screen bg-[#f8fafc] pb-12">
        {{-- Header --}}
        <div class="bg-white border-b border-gray-200">
            <div class="mx-auto px-6 py-6">
                <div class="flex justify-between items-center">
                    <!-- Left: Title -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Compliance Analytics</h1>
                        <p class="text-sm text-gray-500 mt-1">Deep insights into regulatory performance and liability trends.
                        </p>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-3">
                        <button onclick="window.print()"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 transition shadow-sm">
                            Export Report
                        </button>

                        <a href="{{ route('payroll.compliance.dashboard') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 transition shadow-sm">
                            ← Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto px-6 mt-8">
            {{-- High Level Trends --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                {{-- Monthly Filing Trend --}}
                <div class="bg-white rounded-[32px] p-8 border border-gray-100 shadow-xl">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-8">Monthly Obligation Volume
                    </h3>
                    <div class="h-[300px]">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>

                {{-- Authority Distribution --}}
                <div class="bg-white rounded-[32px] p-8 border border-gray-100 shadow-xl">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-8">Liability by Authority</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div class="h-[250px]">
                            <canvas id="authorityChart"></canvas>
                        </div>
                        <div id="authorityLegend" class="space-y-4">
                            {{-- Legend items injected here --}}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {{-- Status Breakdown --}}
                <div class="bg-white rounded-[32px] p-8 border border-gray-100 shadow-xl">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-8">Filing Status</h3>
                    <div class="h-[200px] mb-6">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div id="statusList" class="space-y-2 mt-4"></div>
                </div>

                {{-- Severity Distribution --}}
                <div class="bg-white rounded-[32px] p-8 border border-gray-100 shadow-xl">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-8">Alert Severity</h3>
                    <div class="h-[200px] mb-6">
                        <canvas id="severityChart"></canvas>
                    </div>
                    <div id="severityList" class="space-y-2 mt-4"></div>
                </div>

                {{-- Performance Score Card --}}
                <div
                    class="bg-[#1e293b] rounded-[32px] p-8 border border-slate-700 shadow-2xl relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-5 transform rotate-12 transition group-hover:scale-110">
                        <svg class="w-64 h-64 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Annual Compliance
                            Score</h3>
                        <div class="flex items-baseline gap-2">
                            <span id="avgScore" class="text-6xl font-black text-white">0%</span>
                            <span class="text-xs text-green-400 font-black uppercase tracking-widest">Excellent</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-4 leading-relaxed font-medium">Your organization has maintained
                            a high compliance rating across all Pakistani statutory bodies this year.</p>

                        <div class="mt-8 space-y-4">
                            <div
                                class="flex justify-between text-[10px] font-black uppercase tracking-widest text-slate-400">
                                <span>Regulatory Risk</span>
                                <span class="text-green-400">Low</span>
                            </div>
                            <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div class="bg-green-500 h-full w-[94%]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            loadStatistics();
        });

        async function loadStatistics() {
            $.getJSON('{{ route('payroll.compliance.statistics') }}', function(data) {
                renderMonthlyTrend(data.monthly_events);
                renderAuthorityChart(data.events_by_body);
                renderStatusChart(data.events_by_status);
                renderSeverityChart(data.alerts_by_severity);

                // Calculate average score
                const score = calculateScore(data.events_by_status);
                animateValue("avgScore", 0, score, 1500, "%");
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error("Failed to load statistics:", textStatus, errorThrown);
            });
        }

        function calculateScore(status) {
            const total = Object.values(status).reduce((a, b) => a + b, 0);
            if (total === 0) return 100;
            const overdue = status.overdue || 0;
            const pending = status.pending || 0;
            return Math.max(0, 100 - ((overdue * 20) + (pending * 5)));
        }

        function renderMonthlyTrend(monthlyData) {
            const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const values = months.map((_, i) => monthlyData[i + 1] || 0);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Compliance Events',
                        data: values,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 4,
                        pointRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 3
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
                                borderDash: [5, 5],
                                color: '#f1f5f9'
                            },
                            ticks: {
                                font: {
                                    weight: 'bold',
                                    size: 10
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    weight: 'bold',
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderAuthorityChart(bodyData) {
            const ctx = document.getElementById('authorityChart').getContext('2d');
            const labels = Object.keys(bodyData);
            const values = Object.values(bodyData);
            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'];

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '80%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            const legendContainer = $('#authorityLegend');
            labels.forEach((label, i) => {
                legendContainer.append(`
                <div class="flex justify-between items-center group">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-2.5 rounded-full" style="background-color: ${colors[i % colors.length]}"></div>
                        <span class="text-xs font-black text-gray-700 uppercase tracking-widest">${label}</span>
                    </div>
                    <span class="text-xs font-black text-gray-400 group-hover:text-gray-900 transition">${values[i]}</span>
                </div>
            `);
            });
        }

        function renderStatusChart(statusData) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            const labels = Object.keys(statusData).map(s => s.toUpperCase());
            const values = Object.values(statusData);
            const colors = {
                'COMPLETED': '#10b981',
                'PENDING': '#3b82f6',
                'OVERDUE': '#ef4444'
            };

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: labels.map(l => colors[l] || '#cbd5e1'),
                        borderRadius: 12,
                        barThickness: 24
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
                                display: false
                            },
                            ticks: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    weight: 'black',
                                    size: 8
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderSeverityChart(severityData) {
            const ctx = document.getElementById('severityChart').getContext('2d');
            const labels = ['CRITICAL', 'ERROR', 'WARNING', 'INFO'];
            const values = labels.map(l => severityData[l.toLowerCase()] || 0);
            const colors = ['#ef4444', '#f97316', '#f59e0b', '#3b82f6'];

            new Chart(ctx, {
                type: 'polarArea',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors.map(c => c + '33'),
                        borderColor: colors,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        function animateValue(id, start, end, duration, suffix = "") {
            const obj = document.getElementById(id);
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start) + suffix;
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }
    </script>
@endpush
