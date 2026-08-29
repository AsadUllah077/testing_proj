@extends('layouts.auth')
@section('title', 'Management Review Dashboard')
@section('content')
<div class="mt-4 px-3">

    {{-- Header --}}
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#0078C8] mb-1">Quality Management Protocol</p>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Management Review Dashboard</h1>
                <p class="text-sm text-gray-500 mt-0.5">Executive-level quality monitoring and compliance overview.</p>
            </div>
            <div class="flex gap-2">
                <select id="dateRangeFilter" onchange="filterDashboard(this.value)"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#0078C8]">
                    <option value="6m">Last 6 Months</option>
                    <option value="3m">Last 3 Months</option>
                    <option value="1m">Last Month</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Top KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Policies --}}
        <a href="{{ route('qms.policy.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full font-medium">{{ $stats['policies']['active'] }} active</span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['policies']['total'] }}</p>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mt-1">Policies</p>
            <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $stats['policies']['total'] > 0 ? round($stats['policies']['active']/$stats['policies']['total']*100) : 0 }}%"></div>
            </div>
        </a>

        {{-- SOPs --}}
        <a href="{{ route('qms.sop.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full font-medium">{{ $stats['sops']['approved'] }} approved</span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['sops']['total'] }}</p>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mt-1">SOPs</p>
            <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ $stats['sops']['total'] > 0 ? round($stats['sops']['approved']/$stats['sops']['total']*100) : 0 }}%"></div>
            </div>
        </a>

        {{-- Trainings --}}
        <a href="{{ route('qms.training.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center group-hover:bg-teal-200 transition-colors">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full font-medium">{{ $stats['trainings']['completed'] }} done</span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['trainings']['total'] }}</p>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mt-1">Trainings</p>
            <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-teal-500 h-1.5 rounded-full" style="width: {{ $trainingRate }}%"></div>
            </div>
        </a>

        {{-- CAPAs --}}
        <a href="{{ route('qms.capa.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <span class="text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded-full font-medium">{{ $stats['capas']['open'] }} open</span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['capas']['total'] }}</p>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mt-1">CAPAs</p>
            <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-orange-500 h-1.5 rounded-full" style="width: {{ $stats['capas']['total'] > 0 ? round($stats['capas']['closed']/$stats['capas']['total']*100) : 0 }}%"></div>
            </div>
        </a>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">

        {{-- CAPA Trend (Line Chart) --}}
        <div class="md:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">CAPA Trend</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Open vs Closed issues over last 6 months</p>
                </div>
                <div class="flex gap-3 text-xs">
                    <span class="flex items-center gap-1 text-gray-500"><span class="w-3 h-1 bg-orange-400 rounded inline-block"></span>Open</span>
                    <span class="flex items-center gap-1 text-gray-500"><span class="w-3 h-1 bg-green-400 rounded inline-block"></span>Closed</span>
                </div>
            </div>
            <canvas id="capaTrendChart" height="80"></canvas>
        </div>

        {{-- CAPA Distribution (Pie) --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-700">CAPA Status</h3>
                <p class="text-xs text-gray-400 mt-0.5">Distribution by status</p>
            </div>
            <canvas id="capaDistChart" height="130"></canvas>
            <div class="mt-4 space-y-2">
                @foreach(['open'=>['Open','bg-orange-400'],'in_progress'=>['In Progress','bg-blue-400'],'closed'=>['Closed','bg-green-400']] as $key => [$lbl,$dot])
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $dot }}"></span>
                        <span class="text-gray-600">{{ $lbl }}</span>
                    </div>
                    <span class="font-medium text-gray-900">{{ $stats['capas'][$key] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Compliance + Training Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Compliance Rate --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Policy Compliance Rate</h3>
            <div class="flex items-center gap-6">
                <div class="relative w-28 h-28">
                    <canvas id="complianceChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold text-gray-900">{{ $complianceRate }}%</span>
                        <span class="text-xs text-gray-400">Compliant</span>
                    </div>
                </div>
                <div class="flex-1 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Active</span>
                        <span class="font-medium text-green-600">{{ $stats['policies']['active'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Under Review</span>
                        <span class="font-medium text-yellow-600">{{ $stats['policies']['under_review'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Draft</span>
                        <span class="font-medium text-gray-500">{{ $stats['policies']['draft'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Training Completion (Bar) --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Training Completion</h3>
            <p class="text-xs text-gray-400 mb-4">Overall completion rate across all trainings</p>
            <canvas id="trainingChart" height="100"></canvas>
            <div class="mt-3 flex justify-between text-xs text-gray-500">
                <span>Pending: <strong class="text-gray-800">{{ $stats['trainings']['pending'] }}</strong></span>
                <span>In Progress: <strong class="text-gray-800">{{ $stats['trainings']['in_progress'] }}</strong></span>
                <span>Completed: <strong class="text-green-700">{{ $stats['trainings']['completed'] }}</strong></span>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// CAPA Trend Line Chart
const capaTrendData = @json($capaTrend);
new Chart(document.getElementById('capaTrendChart'), {
    type: 'line',
    data: {
        labels: capaTrendData.map(d => d.month),
        datasets: [
            {
                label: 'Open',
                data: capaTrendData.map(d => d.open),
                borderColor: '#f97316',
                backgroundColor: 'rgba(249,115,22,0.08)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#f97316',
                pointRadius: 4,
            },
            {
                label: 'Closed',
                data: capaTrendData.map(d => d.closed),
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34,197,94,0.08)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#22c55e',
                pointRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { stepSize: 1, font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// CAPA Distribution Pie
new Chart(document.getElementById('capaDistChart'), {
    type: 'doughnut',
    data: {
        labels: ['Open','In Progress','Closed'],
        datasets: [{
            data: [{{ $stats['capas']['open'] }}, {{ $stats['capas']['in_progress'] }}, {{ $stats['capas']['closed'] }}],
            backgroundColor: ['#fb923c','#60a5fa','#4ade80'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        cutout: '65%',
    }
});

// Compliance Doughnut
new Chart(document.getElementById('complianceChart'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [{{ $complianceRate }}, {{ 100 - $complianceRate }}],
            backgroundColor: ['#0078C8','#e5e7eb'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        cutout: '75%',
    }
});

// Training Bar Chart
new Chart(document.getElementById('trainingChart'), {
    type: 'bar',
    data: {
        labels: ['Pending','In Progress','Completed'],
        datasets: [{
            data: [{{ $stats['trainings']['pending'] }}, {{ $stats['trainings']['in_progress'] }}, {{ $stats['trainings']['completed'] }}],
            backgroundColor: ['#e5e7eb','#93c5fd','#4ade80'],
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { stepSize: 1, font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

function filterDashboard(val) {
    // Placeholder for future AJAX-based date filtering
}
window.filterDashboard = filterDashboard;
</script>
@endpush
