@extends('layouts.auth')

@section('title', 'Product Reports')

@section('content')
    @php
        $statusMeta = [
            'ready' => ['label' => 'Ready for Sale', 'color' => '#16a34a', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'],
            'not_ready' => ['label' => 'Not Ready for Sale', 'color' => '#64748b', 'bg' => 'bg-slate-50', 'text' => 'text-slate-700'],
            'hold' => ['label' => 'Hold', 'color' => '#f59e0b', 'bg' => 'bg-amber-50', 'text' => 'text-amber-700'],
            'issued' => ['label' => 'Issued', 'color' => '#2563eb', 'bg' => 'bg-blue-50', 'text' => 'text-blue-700'],
            'sold' => ['label' => 'Sold', 'color' => '#059669', 'bg' => 'bg-green-50', 'text' => 'text-green-700'],
            'scrap' => ['label' => 'Scrap', 'color' => '#dc2626', 'bg' => 'bg-red-50', 'text' => 'text-red-700'],
        ];
        $agingLabels = [
            '0_7' => '0–7 days',
            '8_15' => '8–15 days',
            '16_30' => '16–30 days',
            '31_60' => '31–60 days',
            '61_90' => '61–90 days',
            '90_plus' => '90+ days',
        ];
        $filterQuery = request()->except(['page']);
    @endphp

    <div class="mx-auto px-3 py-4 space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#0078C8]">
                    Warehouse
                    <span class="text-gray-300">/</span>
                    Products
                </div>
                <h1 class="mt-1 text-2xl font-semibold text-gray-900">Product Reports</h1>
                <p class="mt-1 text-sm text-gray-500">Inventory status, quantity reconciliation, aging and data exceptions.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('warehouse.products.index') }}"
                    class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Product List
                </a>
                @if(auth()->user()->hasPermission('warehouse.products.export-pdf'))
                    <a href="{{ route('warehouse.products.reports.export.pdf', $filterQuery) }}"
                        class="px-4 py-2 text-xs font-semibold rounded-lg border border-[#0078C8] text-[#0078C8] hover:bg-blue-50">
                        Export PDF
                    </a>
                @endif
                @if(auth()->user()->hasPermission('warehouse.products.export-excel'))
                    <a href="{{ route('warehouse.products.reports.export.csv', $filterQuery) }}"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-[#0078C8] text-white hover:bg-[#006bb0]">
                        Export CSV
                    </a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('warehouse.products.reports.index') }}"
            class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                <div class="xl:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                    <input name="q" value="{{ request('q') }}" placeholder="Unique ID, title or details"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-[#0078C8] focus:ring-[#0078C8]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All statuses</option>
                        @foreach($statusMeta as $key => $meta)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                    <select name="category" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Location</label>
                    <select name="location" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) request('location') === (string) $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Technician</label>
                    <select name="technician" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All technicians</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}" @selected((string) request('technician') === (string) $technician->id)>{{ $technician->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Workflow stage</label>
                    <select name="work_flow_stage" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All stages</option>
                        @foreach($workflowStages as $stage)
                            <option value="{{ $stage->id }}" @selected((string) request('work_flow_stage') === (string) $stage->id)>{{ $stage->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Disposition</label>
                    <select name="disposition" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All dispositions</option>
                        @foreach($dispositions as $disposition)
                            <option value="{{ $disposition->id }}" @selected((string) request('disposition') === (string) $disposition->id)>{{ $disposition->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Source</label>
                    <select name="manual_status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All sources</option>
                        <option value="1" @selected(request('manual_status') === '1')>Manual</option>
                        <option value="0" @selected(request('manual_status') === '0')>Barcode</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Assignment</label>
                    <select name="assigned" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All</option>
                        <option value="assigned" @selected(request('assigned') === 'assigned')>Assigned</option>
                        <option value="unassigned" @selected(request('assigned') === 'unassigned')>Unassigned</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Age</label>
                    <select name="age_bucket" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All ages</option>
                        @foreach($agingLabels as $key => $label)
                            <option value="{{ $key }}" @selected(request('age_bucket') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Created from</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Created to</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full rounded-lg border-gray-300 text-sm">
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <a href="{{ route('warehouse.products.reports.index') }}"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-600">Reset</a>
                <button class="px-5 py-2 rounded-lg bg-[#0078C8] text-white text-xs font-semibold">Apply filters</button>
            </div>
        </form>

        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-xs text-gray-500">Product records</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($summary['record_count']) }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-xs text-gray-500">Inventory units</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($summary['total_units']) }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-xs text-gray-500">Available units</div>
                <div class="mt-1 text-2xl font-semibold text-emerald-600">{{ number_format($summary['available_units']) }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-xs text-gray-500">Issued units</div>
                <div class="mt-1 text-2xl font-semibold text-blue-600">{{ number_format($summary['issued_units']) }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-xs text-gray-500">Sold units</div>
                <div class="mt-1 text-2xl font-semibold text-green-600">{{ number_format($summary['sold_units']) }}</div>
            </div>
            <a href="#exceptions" class="bg-white rounded-xl border border-red-200 p-4 hover:bg-red-50">
                <div class="text-xs text-gray-500">Exceptions</div>
                <div class="mt-1 text-2xl font-semibold text-red-600">{{ number_format($summary['exception_count']) }}</div>
            </a>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Inventory Status</h2>
                        <p class="text-xs text-gray-500">Units, with partially sold manual products reconciled.</p>
                    </div>
                </div>
                <div id="statusChart" class="min-h-[320px]"></div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach($statusMeta as $key => $meta)
                        <a href="{{ route('warehouse.products.reports.index', array_merge($filterQuery, ['status' => $key, 'page' => null])) }}"
                            class="rounded-lg {{ $meta['bg'] }} p-2 flex items-center justify-between">
                            <span class="text-xs {{ $meta['text'] }}">{{ $meta['label'] }}</span>
                            <strong class="{{ $meta['text'] }}">{{ number_format($summary['status_units'][$key]) }}</strong>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Inventory Aging</h2>
                <p class="text-xs text-gray-500">Inventory units grouped by product creation age.</p>
                <div id="agingChart" class="min-h-[320px]"></div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-base font-semibold text-gray-900">Top Categories</h2>
            <p class="text-xs text-gray-500">Total units and Ready for Sale units by category.</p>
            <div id="categoryChart" class="min-h-[330px]"></div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Detailed Inventory Register</h2>
                    <p class="text-xs text-gray-500">Both record-level and unit-level reporting values are shown.</p>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    @foreach(request()->except(['per_page', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <label class="text-xs text-gray-500">Rows</label>
                    <select name="per_page" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-xs">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-[11px] uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Category / Location</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Sold</th>
                            <th class="px-4 py-3 text-right">Remaining</th>
                            <th class="px-4 py-3">Workflow / Disposition</th>
                            <th class="px-4 py-3">Technician</th>
                            <th class="px-4 py-3 text-right">Age</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($products as $product)
                            @php($meta = $statusMeta[$product->reporting_status])
                            <tr class="text-sm hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a class="font-mono text-xs font-semibold text-[#0078C8] hover:underline"
                                        href="{{ route('warehouse.products.show', $product) }}">{{ $product->unique_id }}</a>
                                    <div class="mt-0.5 max-w-[230px] truncate text-gray-800" title="{{ $product->title }}">{{ $product->title }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $product->manual_status ? 'Manual' : 'Barcode' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-700">{{ $product->category?->name ?? 'Missing category' }}</div>
                                    <div class="text-xs text-gray-400">{{ $product->warehouseLocation?->name ?? 'Missing location' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $meta['bg'] }} {{ $meta['text'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                    @if($product->is_partially_sold)
                                        <div class="mt-1 text-[11px] text-emerald-600">Partially sold</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format($product->total_units) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600">{{ number_format($product->sold_units) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($product->remaining_units) }}</td>
                                <td class="px-4 py-3">
                                    <div>{{ $product->workflowStage?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $product->disposition?->name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $product->assignedTo?->full_name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div>{{ number_format($product->age_days) }}d</div>
                                    <div class="text-[11px] text-gray-400">{{ number_format($product->days_since_update) }}d idle</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-10 text-center text-sm text-gray-400">No products match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($products->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">{{ $products->links() }}</div>
            @endif
        </div>

        <div id="exceptions" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-900">Data Quality & Reconciliation Exceptions</h2>
                <p class="text-xs text-gray-500">The first 50 issues found under the selected filters.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-red-50">
                        <tr class="text-left text-[11px] uppercase tracking-wide text-red-700">
                            <th class="px-4 py-3">Unique ID</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Exception</th>
                            <th class="px-4 py-3 text-right">Age</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($exceptions as $exception)
                            <tr class="text-sm">
                                <td class="px-4 py-3">
                                    <a href="{{ route('warehouse.products.show', $exception['product_id']) }}"
                                        class="font-mono text-xs font-semibold text-[#0078C8] hover:underline">{{ $exception['unique_id'] }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $exception['title'] }}</td>
                                <td class="px-4 py-3 text-red-700">{{ $exception['message'] }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($exception['age_days']) }}d</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-emerald-600">No exceptions found for these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const statusMeta = @json($statusMeta);
            const statusUnits = @json($summary['status_units']);
            const aging = @json($summary['aging']);
            const categories = @json($categoriesChart);

            new ApexCharts(document.querySelector('#statusChart'), {
                chart: { type: 'donut', height: 320, toolbar: { show: false } },
                series: Object.keys(statusMeta).map(key => Number(statusUnits[key] || 0)),
                labels: Object.values(statusMeta).map(item => item.label),
                colors: Object.values(statusMeta).map(item => item.color),
                legend: { position: 'bottom' },
                dataLabels: { enabled: true },
                noData: { text: 'No inventory data' }
            }).render();

            new ApexCharts(document.querySelector('#agingChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{ name: 'Units', data: Object.values(aging).map(Number) }],
                xaxis: { categories: @json(array_values($agingLabels)) },
                colors: ['#0078C8'],
                plotOptions: { bar: { borderRadius: 5, distributed: true } },
                dataLabels: { enabled: false },
                legend: { show: false }
            }).render();

            new ApexCharts(document.querySelector('#categoryChart'), {
                chart: { type: 'bar', height: 330, stacked: false, toolbar: { show: false } },
                series: [
                    { name: 'Total units', data: categories.map(item => Number(item.units)) },
                    { name: 'Ready units', data: categories.map(item => Number(item.ready_units)) }
                ],
                xaxis: { categories: categories.map(item => item.name), labels: { rotate: -35 } },
                colors: ['#94a3b8', '#16a34a'],
                plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
                dataLabels: { enabled: false },
                legend: { position: 'top' }
            }).render();
        });
    </script>
@endsection
