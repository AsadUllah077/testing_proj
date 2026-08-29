@props([
    'date_filter' => null,
    'revenueChart' => null,
    'total' => null,
    'title' => null,
    'data' => [],
    'labels' => [],
    'color' => '#3B82F6',
    'colorRgba' => '59, 130, 246',
    'subtitle' => null,
])

<div>
    <div class="bg-white rounded-[5px] border border-gray-200">

        <!-- Header Section -->
        <div class="px-5 pt-4 pb-1">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ currency()['symbol'] ?? 'RS' }} {{ $total }}</p>
            @if ($subtitle)
                <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>

        <!-- Chart Container -->
        <div class="relative h-[100px]">
            <canvas id="{{ $revenueChart }}"></canvas>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            var ctx = document.getElementById('{{ $revenueChart }}').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        data: @json($data),
                        borderColor: '{{ $color }}',
                        backgroundColor: (context) => {
                            const chartCtx = context.chart.ctx;
                            const gradient = chartCtx.createLinearGradient(0, 0, 0, 100);
                            gradient.addColorStop(0, 'rgba({{ $colorRgba }}, 0.3)');
                            gradient.addColorStop(1, 'rgba({{ $colorRgba }}, 0.0)');
                            return gradient;
                        },
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '{{ $color }}',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(255,255,255,0.95)',
                            titleColor: '#1F2937',
                            bodyColor: '#1F2937',
                            borderColor: '#E5E7EB',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '{{ $title }}: ' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        })();
    </script>
@endpush
