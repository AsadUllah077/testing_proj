@extends('layouts.auth')

@section('title', 'Product Operations Dashboard')

@section('content')
    @php
        $warehouseStats = $warehouse['stats'];
        $logisticsStats = $logistics['stats'];

        $formatCompact = function ($value) {
            $value = (float) $value;
            if (abs($value) >= 1000000000) {
                return number_format($value / 1000000000, 1) . 'B';
            }
            if (abs($value) >= 1000000) {
                return number_format($value / 1000000, 1) . 'M';
            }
            if (abs($value) >= 1000) {
                return number_format($value / 1000, 1) . 'K';
            }
            return number_format($value);
        };

        $money = function ($value, $prefix = '') use ($formatCompact) {
            return $prefix . $formatCompact($value);
        };

        $readyRate = $warehouseStats['total_products'] > 0
            ? round(($warehouseStats['ready_for_sale'] / $warehouseStats['total_products']) * 100)
            : 0;
        $assignedRate = $warehouseStats['total_products'] > 0
            ? round(($warehouseStats['assigned'] / $warehouseStats['total_products']) * 100)
            : 0;
        $delayRate = $logisticsStats['total_shipments'] > 0
            ? round(($logisticsStats['delayed_shipments'] / $logisticsStats['total_shipments']) * 100)
            : 0;

        $kpis = [
            [
                'label' => 'Warehouse Products',
                'value' => number_format($warehouseStats['total_products']),
                'note' => $readyRate . '% ready for sale',
                'icon' => 'fa-boxes-stacked',
                'tone' => 'blue',
                'href' => route('warehouse.products.index'),
            ],
            [
                'label' => 'Ready Inventory',
                'value' => number_format($warehouseStats['ready_for_sale']),
                'note' => number_format($warehouseStats['not_ready']) . ' still in progress',
                'icon' => 'fa-circle-check',
                'tone' => 'green',
                'href' => route('warehouse.products.index', ['status' => 'active']),
            ],
            [
                'label' => 'Active Shipments',
                'value' => number_format($logisticsStats['active_shipments']),
                'note' => $delayRate . '% delayed',
                'icon' => 'fa-route',
                'tone' => $logisticsStats['delayed_shipments'] > 0 ? 'red' : 'teal',
                'href' => route('logistics.shipments.index'),
            ],
            [
                'label' => 'Open Bookings',
                'value' => number_format($logisticsStats['total_bookings']),
                'note' => number_format($logisticsStats['imports']) . ' imports, ' . number_format($logisticsStats['exports']) . ' exports',
                'icon' => 'fa-clipboard-list',
                'tone' => 'purple',
                'href' => route('logistics.manage-booking.v2.index'),
            ],
            [
                'label' => 'Customs Exposure',
                'value' => $money($logisticsStats['total_duty'], 'PKR '),
                'note' => number_format($logisticsStats['pending_customs']) . ' GDs pending',
                'icon' => 'fa-file-invoice-dollar',
                'tone' => 'amber',
                'href' => route('logistics.gd.index'),
            ],
        ];

        $toneClasses = [
            'blue' => 'bg-blue-50 text-[#0078C8] border-blue-100',
            'green' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
            'teal' => 'bg-teal-50 text-teal-600 border-teal-100',
            'red' => 'bg-red-50 text-red-600 border-red-100',
            'purple' => 'bg-violet-50 text-violet-600 border-violet-100',
            'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
        ];

        $actionToneClasses = [
            'blue' => 'bg-blue-50 text-[#0078C8] border-blue-100',
            'red' => 'bg-red-50 text-red-600 border-red-100',
            'purple' => 'bg-violet-50 text-violet-600 border-violet-100',
            'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
        ];
    @endphp

    <div class="min-h-screen bg-slate-50 px-3 py-4 md:px-5 lg:px-6">
        <div class="mx-auto max-w-[1600px] space-y-5">
            <header class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-5 py-4  md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Product operations</p>
                    <h1 class="mt-1 text-xl font-semibold text-slate-950">Warehouse and Logistics Dashboard</h1>
                    <p class="mt-1 text-sm text-slate-500">Live operational view across inventory readiness, dispatch flow, bookings, customs, and shipments.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500">
                        Updated {{ $updatedAt }}
                    </span>
                    <a href="{{ route('warehouse.dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-[#0078C8] hover:text-[#0078C8]">
                        <i class="fa-solid fa-warehouse text-[11px]"></i>
                        Warehouse
                    </a>
                    <a href="{{ route('logistics.dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#0078C8] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#006bb0]">
                        <i class="fa-solid fa-truck-fast text-[11px]"></i>
                        Logistics
                    </a>
                </div>
            </header>

            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($kpis as $card)
                    <a href="{{ $card['href'] }}"
                        class="group rounded-lg border border-slate-200 bg-white p-4  transition hover:-translate-y-0.5 hover:border-[#0078C8]/40 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $card['value'] }}</p>
                            </div>
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border {{ $toneClasses[$card['tone']] ?? $toneClasses['blue'] }}">
                                <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
                            </span>
                        </div>
                        <p class="mt-3 text-xs font-medium text-slate-500">{{ $card['note'] }}</p>
                    </a>
                @endforeach
            </section>

            <section class="grid grid-cols-1 gap-5 xl:grid-cols-12">
                <div class="space-y-5 xl:col-span-8">
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-950">Warehouse Health</h2>
                                    <p class="text-xs text-slate-500">Ready, in-progress, sold, and scrap split</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $readyRate }}% ready</span>
                            </div>
                            @if ($warehouse['health_total'] > 0)
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[190px_1fr]">
                                    <div class="relative h-48">
                                        <canvas id="warehouseHealthChart"></canvas>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($warehouse['health'] as $item)
                                            @php
                                                $pct = $warehouse['health_total'] > 0 ? round(($item['value'] / $warehouse['health_total']) * 100) : 0;
                                            @endphp
                                            <div>
                                                <div class="mb-1 flex items-center justify-between text-xs">
                                                    <span class="flex items-center gap-2 font-medium text-slate-600">
                                                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $item['color'] }}"></span>
                                                        {{ $item['label'] }}
                                                    </span>
                                                    <span class="font-semibold text-slate-900">{{ number_format($item['value']) }}</span>
                                                </div>
                                                <div class="h-2 rounded-full bg-slate-100">
                                                    <div class="h-2 rounded-full" style="width: {{ $pct }}%; background: {{ $item['color'] }}"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="flex h-48 items-center justify-center rounded-lg border border-dashed border-slate-200 text-sm text-slate-400">No warehouse data yet.</div>
                            @endif
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-950">Shipment Status</h2>
                                    <p class="text-xs text-slate-500">Current logistics execution state</p>
                                </div>
                                <span class="rounded-full {{ $logisticsStats['delayed_shipments'] > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} px-2.5 py-1 text-xs font-semibold">
                                    {{ number_format($logisticsStats['delayed_shipments']) }} delayed
                                </span>
                            </div>
                            @if (count($logistics['shipment_status']) > 0)
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[190px_1fr]">
                                    <div class="relative h-48">
                                        <canvas id="shipmentStatusChart"></canvas>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($logistics['shipment_status'] as $item)
                                            <div>
                                                <div class="mb-1 flex items-center justify-between text-xs">
                                                    <span class="flex items-center gap-2 font-medium text-slate-600">
                                                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $item['color'] }}"></span>
                                                        {{ $item['label'] }}
                                                    </span>
                                                    <span class="font-semibold text-slate-900">{{ $item['pct'] }}%</span>
                                                </div>
                                                <div class="h-2 rounded-full bg-slate-100">
                                                    <div class="h-2 rounded-full" style="width: {{ $item['pct'] }}%; background: {{ $item['color'] }}"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="flex h-48 items-center justify-center rounded-lg border border-dashed border-slate-200 text-sm text-slate-400">No shipment data yet.</div>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-950">Product Intake Trend</h2>
                                    <p class="text-xs text-slate-500">Products created in the last 14 days</p>
                                </div>
                                <a href="{{ route('warehouse.products.index') }}" class="text-xs font-semibold text-[#0078C8] hover:underline">View products</a>
                            </div>
                            <div class="h-64">
                                <canvas id="warehouseTrendChart"></canvas>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-950">Booking Volume</h2>
                                    <p class="text-xs text-slate-500">Imports and exports across 12 months</p>
                                </div>
                                <a href="{{ route('logistics.manage-booking.v2.index') }}" class="text-xs font-semibold text-[#0078C8] hover:underline">View bookings</a>
                            </div>
                            <div class="h-64">
                                <canvas id="bookingTrendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                            <div class="mb-4">
                                <h2 class="text-sm font-semibold text-slate-950">Top Product Categories</h2>
                                <p class="text-xs text-slate-500">Inventory concentration by category</p>
                            </div>
                            @if ($warehouse['category_chart']->count())
                                <div class="h-72">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            @else
                                <div class="flex h-72 items-center justify-center rounded-lg border border-dashed border-slate-200 text-sm text-slate-400">No category data yet.</div>
                            @endif
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                            <div class="mb-4">
                                <h2 class="text-sm font-semibold text-slate-950">Freight Mode Mix</h2>
                                <p class="text-xs text-slate-500">Booking count by movement mode</p>
                            </div>
                            @if (count($logistics['freight_split']) > 0)
                                <div class="space-y-4">
                                    @foreach ($logistics['freight_split'] as $item)
                                        <div>
                                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                                <span class="font-semibold text-slate-700">{{ ucfirst($item['label']) }}</span>
                                                <span class="text-slate-500">{{ number_format($item['value']) }} bookings</span>
                                            </div>
                                            <div class="h-3 rounded-full bg-slate-100">
                                                <div class="h-3 rounded-full" style="width: {{ max($item['pct'], 2) }}%; background: {{ $item['color'] }}"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex h-72 items-center justify-center rounded-lg border border-dashed border-slate-200 text-sm text-slate-400">No freight data yet.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <aside class="space-y-5 xl:col-span-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-950">Action Center</h2>
                                <p class="text-xs text-slate-500">Items that need follow-up</p>
                            </div>
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">{{ count($actionItems) }}</span>
                        </div>
                        @forelse ($actionItems as $item)
                            <a href="{{ $item['href'] }}" class="mb-3 flex items-start gap-3 rounded-lg border p-3 transition hover:border-[#0078C8]/40 hover:bg-slate-50 {{ $actionToneClasses[$item['tone']] ?? $actionToneClasses['blue'] }}">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/80">
                                    <i class="fa-solid {{ $item['icon'] }} text-sm"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $item['title'] }}</span>
                                    <span class="mt-1 block text-xs font-medium text-slate-500">{{ $item['label'] }}</span>
                                </span>
                                <span class="text-lg font-semibold text-slate-950">{{ number_format($item['value']) }}</span>
                            </a>
                        @empty
                            <div class="rounded-lg border border-dashed border-emerald-200 bg-emerald-50 px-4 py-8 text-center">
                                <i class="fa-solid fa-circle-check text-2xl text-emerald-500"></i>
                                <p class="mt-2 text-sm font-semibold text-slate-800">No urgent actions</p>
                                <p class="mt-1 text-xs text-slate-500">Warehouse and logistics queues are clear.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                        <div class="mb-4">
                            <h2 class="text-sm font-semibold text-slate-950">Warehouse Queue</h2>
                            <p class="text-xs text-slate-500">Assignment and movement signals</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="mb-1.5 flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-600">Assigned products</span>
                                    <span class="font-semibold text-slate-900">{{ $assignedRate }}%</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-slate-100">
                                    <div class="h-2.5 rounded-full bg-[#0078C8]" style="width: {{ $assignedRate }}%"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="{{ route('warehouse.technicians.index') }}" class="rounded-lg border border-slate-200 p-3 transition hover:border-[#0078C8]/40">
                                    <p class="text-xs text-slate-500">Active tasks</p>
                                    <p class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($warehouseStats['active_assignments']) }}</p>
                                </a>
                                <a href="{{ route('warehouse.gate-pass.index') }}" class="rounded-lg border border-slate-200 p-3 transition hover:border-[#0078C8]/40">
                                    <p class="text-xs text-slate-500">Pending gate passes</p>
                                    <p class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($warehouseStats['pending_gate_passes']) }}</p>
                                </a>
                                <a href="{{ route('warehouse.products.index', ['status' => 'issued']) }}" class="rounded-lg border border-slate-200 p-3 transition hover:border-[#0078C8]/40">
                                    <p class="text-xs text-slate-500">Issued</p>
                                    <p class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($warehouseStats['issued']) }}</p>
                                </a>
                                <a href="{{ route('warehouse.scrap.index') }}" class="rounded-lg border border-slate-200 p-3 transition hover:border-[#0078C8]/40">
                                    <p class="text-xs text-slate-500">Scrap</p>
                                    <p class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($warehouseStats['scrap']) }}</p>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 ">
                        <div class="mb-4">
                            <h2 class="text-sm font-semibold text-slate-950">Financial Logistics Snapshot</h2>
                            <p class="text-xs text-slate-500">LC, duty, and bank charge exposure</p>
                        </div>
                        <div class="space-y-3">
                            <a href="{{ route('logistics.lc.index') }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 transition hover:border-[#0078C8]/40">
                                <span>
                                    <span class="block text-xs text-slate-500">Active LCs</span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ number_format($logisticsStats['active_lcs']) }}</span>
                                </span>
                                <span class="text-sm font-semibold text-[#0078C8]">{{ $money($logisticsStats['total_lc_value'], '$') }}</span>
                            </a>
                            <a href="{{ route('logistics.gd.payment-tracker') }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 transition hover:border-[#0078C8]/40">
                                <span>
                                    <span class="block text-xs text-slate-500">Total Duty</span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ number_format($logisticsStats['cleared_gds']) }} cleared GDs</span>
                                </span>
                                <span class="text-sm font-semibold text-amber-700">{{ $money($logisticsStats['total_duty'], 'PKR ') }}</span>
                            </a>
                            <a href="{{ route('logistics.lc.bank-charges.index') }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 transition hover:border-[#0078C8]/40">
                                <span>
                                    <span class="block text-xs text-slate-500">Bank Charges</span>
                                    <span class="block text-sm font-semibold text-slate-900">Posted charges</span>
                                </span>
                                <span class="text-sm font-semibold text-violet-600">{{ $money($logisticsStats['bank_charges'], 'PKR ') }}</span>
                            </a>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white ">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-950">Recent Warehouse Products</h2>
                            <p class="text-xs text-slate-500">Latest additions and current readiness</p>
                        </div>
                        <a href="{{ route('warehouse.products.index') }}" class="text-xs font-semibold text-[#0078C8] hover:underline">All products</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Product</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Location</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($warehouse['recent_products'] as $product)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('warehouse.products.show', $product->id) }}" class="text-sm font-semibold text-[#0078C8] hover:underline">{{ $product->unique_id }}</a>
                                            <p class="mt-0.5 max-w-xs truncate text-xs text-slate-500">{{ $product->title ?? 'Untitled product' }}</p>
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-600">{{ $product->warehouseLocation?->name ?? 'Not assigned' }}</td>
                                        <td class="px-5 py-3">
                                            @php
                                                $statusText = $product->sold_status ? 'Sold' : ($product->scrap_status ? 'Scrap' : ($product->status ?? 'Unknown'));
                                                $statusClass = $product->sold_status
                                                    ? 'bg-violet-50 text-violet-700'
                                                    : ($product->scrap_status
                                                        ? 'bg-red-50 text-red-600'
                                                        : (in_array(strtolower($product->status ?? ''), ['active', 'ready for sale'])
                                                            ? 'bg-emerald-50 text-emerald-700'
                                                            : 'bg-slate-100 text-slate-600'));
                                            @endphp
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusText }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-400">No products yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white ">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-950">Recent Logistics Activity</h2>
                            <p class="text-xs text-slate-500">Newest bookings and shipments</p>
                        </div>
                        <a href="{{ route('logistics.manage-booking.v2.index') }}" class="text-xs font-semibold text-[#0078C8] hover:underline">All bookings</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($logistics['recent_bookings']->take(4) as $booking)
                            @php
                                $origin = $booking->origin_port ?? $booking->origin_country ?? $booking->origin_city ?? '?';
                                $destination = $booking->dest_port ?? $booking->destination_port ?? $booking->dest_country ?? $booking->dest_city ?? '?';
                                $bookingMode = $booking->freight_mode ?? $booking->service_type ?? 'mode pending';
                            @endphp
                            <a href="{{ route('logistics.manage-booking.v2.show', $booking->id) }}" class="flex items-center justify-between gap-4 px-5 py-3 transition hover:bg-slate-50">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $booking->booking_number ?? 'Booking #' . $booking->id }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $origin }} to {{ $destination }} - {{ ucfirst($bookingMode) }}</span>
                                </span>
                                <span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-[#0078C8]">{{ $booking->status ?? 'Draft' }}</span>
                            </a>
                        @empty
                            <div class="px-5 py-8 text-center text-sm text-slate-400">No bookings yet.</div>
                        @endforelse

                        @foreach ($logistics['recent_shipments']->take(3) as $shipment)
                            <a href="{{ route('logistics.shipments.index') }}" class="flex items-center justify-between gap-4 px-5 py-3 transition hover:bg-slate-50">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $shipment->shipment_number ?? 'Shipment #' . $shipment->id }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $shipment->origin_port ?? '?' }} to {{ $shipment->dest_port ?? '?' }} - {{ $shipment->carrier ?? 'carrier pending' }}</span>
                                </span>
                                <span class="shrink-0 rounded-full {{ $shipment->status === 'Delayed' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-xs font-semibold">{{ $shipment->status ?? 'Booked' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const palette = {
                blue: '#0078C8',
                green: '#10b981',
                amber: '#f59e0b',
                red: '#ef4444',
                purple: '#8b5cf6',
                slate: '#64748b',
                teal: '#14b8a6',
            };

            const data = {
                warehouseHealth: @json($warehouse['health']),
                shipmentStatus: @json($logistics['shipment_status']),
                warehouseTrend: @json($warehouse['daily_trend']),
                bookingTrend: @json($logistics['monthly_bookings']),
                categories: @json($warehouse['category_chart']),
            };

            const gridColor = '#e2e8f0';
            const labelColor = '#64748b';

            function getCanvas(id) {
                const el = document.getElementById(id);
                return el ? el.getContext('2d') : null;
            }

            function doughnut(id, rows) {
                const ctx = getCanvas(id);
                if (!ctx || !rows.length) return;

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: rows.map(row => row.label),
                        datasets: [{
                            data: rows.map(row => row.value),
                            backgroundColor: rows.map(row => row.color),
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 4,
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
                                    label: context => `${context.label}: ${Number(context.raw).toLocaleString()}`
                                }
                            }
                        }
                    }
                });
            }

            function lineChart(id, labels, datasets) {
                const ctx = getCanvas(id);
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'line',
                    data: { labels, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        tension: 0.35,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                labels: { color: labelColor, usePointStyle: true, boxWidth: 8 }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: labelColor, maxRotation: 0, autoSkip: true }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: labelColor, precision: 0 }
                            }
                        }
                    }
                });
            }

            function barChart(id, rows) {
                const ctx = getCanvas(id);
                if (!ctx || !rows.length) return;

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: rows.map(row => row.label),
                        datasets: [{
                            label: 'Products',
                            data: rows.map(row => row.value),
                            backgroundColor: palette.blue,
                            borderRadius: 6,
                            maxBarThickness: 34,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: labelColor, precision: 0 }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { color: labelColor }
                            }
                        }
                    }
                });
            }

            doughnut('warehouseHealthChart', data.warehouseHealth);
            doughnut('shipmentStatusChart', data.shipmentStatus);

            lineChart(
                'warehouseTrendChart',
                data.warehouseTrend.map(row => row.label),
                [{
                    label: 'Products',
                    data: data.warehouseTrend.map(row => row.value),
                    borderColor: palette.blue,
                    backgroundColor: 'rgba(0, 120, 200, 0.12)',
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                }]
            );

            lineChart(
                'bookingTrendChart',
                data.bookingTrend.map(row => row.label),
                [
                    {
                        label: 'Total',
                        data: data.bookingTrend.map(row => row.total),
                        borderColor: palette.blue,
                        backgroundColor: 'rgba(0, 120, 200, 0.08)',
                        fill: true,
                        pointRadius: 2,
                    },
                    {
                        label: 'Imports',
                        data: data.bookingTrend.map(row => row.imports),
                        borderColor: palette.green,
                        backgroundColor: 'transparent',
                        pointRadius: 2,
                    },
                    {
                        label: 'Exports',
                        data: data.bookingTrend.map(row => row.exports),
                        borderColor: palette.purple,
                        backgroundColor: 'transparent',
                        pointRadius: 2,
                    }
                ]
            );

            barChart('categoryChart', data.categories);
        });
    </script>
@endpush
