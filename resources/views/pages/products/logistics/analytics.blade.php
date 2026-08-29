@extends('layouts.auth')
@section('title', 'Logistics Analytics')

@section('content')
<div class="mt-4 mx-auto px-3 space-y-5 pb-10">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between bg-white rounded-xl border border-black/10 px-6 py-4">
        <div>
            <h1 class="text-xl font-normal text-gray-900">Logistics Analytics</h1>
            <p class="text-xs text-gray-400 mt-0.5">Performance insights across bookings, customs, and trade finance</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('logistics.dashboard') }}"
                class="px-4 py-2 border border-gray-200 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-gauge-high text-xs"></i> Dashboard
            </a>
            <span class="text-xs text-gray-400 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                <i class="fa-solid fa-calendar-days mr-1.5 text-gray-400"></i>
                As of {{ now()->format('d M Y') }}
            </span>
        </div>
    </div>

    {{-- ── KPI Strip ────────────────────────────────────────────────────── --}}
    @php
        $kpiCards = [
            ['label' => 'Total Bookings',    'value' => $kpis['total_bookings'],   'icon' => 'fa-boxes-stacked',      'color' => 'text-[#0078C8]',  'bg' => 'bg-blue-50',   'border' => 'border-blue-100'],
            ['label' => 'Imports',           'value' => $kpis['total_imports'],    'icon' => 'fa-arrow-down-to-line', 'color' => 'text-purple-600', 'bg' => 'bg-purple-50', 'border' => 'border-purple-100'],
            ['label' => 'Exports',           'value' => $kpis['total_exports'],    'icon' => 'fa-arrow-up-from-line', 'color' => 'text-emerald-600','bg' => 'bg-emerald-50','border' => 'border-emerald-100'],
            ['label' => 'Total GDs',         'value' => $kpis['total_gds'],        'icon' => 'fa-file-lines',         'color' => 'text-amber-600',  'bg' => 'bg-amber-50',  'border' => 'border-amber-100'],
            ['label' => 'Cleared GDs',       'value' => $kpis['cleared_gds'],      'icon' => 'fa-circle-check',       'color' => 'text-green-600',  'bg' => 'bg-green-50',  'border' => 'border-green-100'],
            ['label' => 'Active LCs',        'value' => $kpis['active_lcs'],       'icon' => 'fa-file-invoice-dollar','color' => 'text-[#0078C8]',  'bg' => 'bg-blue-50',   'border' => 'border-blue-100'],
            ['label' => 'LC Exposure (USD)', 'value' => ($kpis['total_lc_value'] >= 1000000 ? '$'.number_format($kpis['total_lc_value']/1000000,1).'M' : '$'.number_format($kpis['total_lc_value']/1000,1).'K'), 'icon' => 'fa-dollar-sign', 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-50', 'border' => 'border-indigo-100'],
            ['label' => 'Total Duty (PKR)',  'value' => ($kpis['total_duty_pkr'] >= 1000000 ? 'PKR '.number_format($kpis['total_duty_pkr']/1000000,1).'M' : 'PKR '.number_format($kpis['total_duty_pkr']/1000,0).'K'), 'icon' => 'fa-landmark', 'color' => 'text-rose-600', 'bg' => 'bg-rose-50', 'border' => 'border-rose-100'],
            ['label' => 'Bank Charges (PKR)','value' => ($kpis['total_charges_pkr'] >= 1000000 ? 'PKR '.number_format($kpis['total_charges_pkr']/1000000,1).'M' : 'PKR '.number_format($kpis['total_charges_pkr']/1000,0).'K'), 'icon' => 'fa-building-columns', 'color' => 'text-orange-600', 'bg' => 'bg-orange-50', 'border' => 'border-orange-100'],
            ['label' => 'Exam Queue',        'value' => $kpis['exam_queue'],       'icon' => 'fa-magnifying-glass',   'color' => 'text-red-600',    'bg' => 'bg-red-50',    'border' => 'border-red-100'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        @foreach($kpiCards as $card)
        <div class="bg-white rounded-xl border {{ $card['border'] }} px-4 py-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg {{ $card['bg'] }} {{ $card['color'] }} flex items-center justify-center shrink-0">
                <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider leading-none">{{ $card['label'] }}</p>
                <p class="text-lg font-black {{ $card['color'] }} mt-0.5 leading-none">{{ $card['value'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Row 1: Monthly Bookings + Freight Mode ───────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Monthly Bookings Bar Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Monthly Booking Volume</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Last 12 months — imports vs exports</p>
                </div>
                <div class="flex items-center gap-4 text-[10px] font-semibold uppercase tracking-wider">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-[#0078C8] inline-block"></span>Imports</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span>Exports</span>
                </div>
            </div>
            <div class="flex items-end gap-1.5 h-36">
                @foreach($monthlyBookings as $m)
                    @php
                        $h     = $maxMonthly > 0 ? round($m['total'] / $maxMonthly * 100) : 0;
                        $himp  = $maxMonthly > 0 ? round($m['imports'] / $maxMonthly * 100) : 0;
                        $hexp  = $maxMonthly > 0 ? round($m['exports'] / $maxMonthly * 100) : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-0.5 group relative">
                        {{-- Tooltip --}}
                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                            {{ $m['label'] }}: {{ $m['total'] }} total<br>↓ {{ $m['imports'] }} imp · ↑ {{ $m['exports'] }} exp
                        </div>
                        <div class="w-full flex flex-col justify-end gap-px" style="height:128px;">
                            @if($m['imports'] > 0)
                            <div class="w-full rounded-t-sm bg-[#0078C8] transition-all duration-500"
                                style="height: {{ $himp }}%;"></div>
                            @endif
                            @if($m['exports'] > 0)
                            <div class="w-full rounded-t-sm bg-emerald-500 transition-all duration-500"
                                style="height: {{ $hexp }}%;"></div>
                            @endif
                            @if($m['total'] === 0)
                            <div class="w-full rounded-t-sm bg-gray-100" style="height: 4px;"></div>
                            @endif
                        </div>
                        <span class="text-[9px] text-gray-400 font-semibold mt-1">{{ $m['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Freight Mode Donut --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-bold text-gray-900 mb-1">Freight Mode</h3>
            <p class="text-xs text-gray-400 mb-5">All bookings by transport</p>
            @if(count($freightData))
                @php
                    $circumference = 100;
                    $offset = 25; // start at top
                    $fSegments = [];
                    foreach($freightData as $fd) {
                        $fSegments[] = ['pct' => $fd['pct'], 'color' => $fd['color'], 'offset' => (100 - $offset)];
                        $offset += $fd['pct'];
                    }
                @endphp
                <div class="flex items-center gap-4">
                    <div class="relative shrink-0 w-28 h-28">
                        <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#f1f5f9" stroke-width="5"/>
                            @php $off2 = 25; @endphp
                            @foreach($freightData as $fd)
                                <circle cx="18" cy="18" r="14" fill="none"
                                    stroke="{{ $fd['color'] }}"
                                    stroke-width="5"
                                    stroke-dasharray="{{ round($fd['pct'] * 87.96 / 100, 1) }} 87.96"
                                    stroke-dashoffset="{{ -round(($off2 - 25) * 87.96 / 100, 1) }}"/>
                                @php $off2 += $fd['pct']; @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-black text-gray-900">{{ $freightTotal }}</span>
                            <span class="text-[9px] text-gray-400 font-semibold uppercase">Total</span>
                        </div>
                    </div>
                    <ul class="flex-1 space-y-2.5">
                        @foreach($freightData as $fd)
                        <li class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $fd['color'] }}"></span>
                                <span class="text-xs text-gray-600">{{ $fd['mode'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold" style="color:{{ $fd['color'] }}">{{ $fd['pct'] }}%</span>
                                <span class="text-[10px] text-gray-400 ml-1">({{ $fd['count'] }})</span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-32 text-center">
                    <i class="fa-solid fa-ship text-gray-200 text-3xl mb-2"></i>
                    <p class="text-xs text-gray-400">No booking data yet</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Row 2: Top Routes + GD Status ───────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Top Routes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Top Trade Routes</h3>
                    <p class="text-xs text-gray-400 mt-0.5">By number of bookings</p>
                </div>
                <a href="{{ route('logistics.manage-booking.v2.index') }}" class="text-xs text-[#0078C8] hover:underline">View all →</a>
            </div>
            @if($topRoutes->count())
                @php $maxRoute = $topRoutes->max('total') ?: 1; @endphp
                <div class="space-y-3">
                    @foreach($topRoutes as $i => $route)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-[10px] font-black text-gray-400 w-4 shrink-0">{{ $i + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-gray-800 truncate">
                                        {{ $route->origin_port }} <span class="text-gray-400 mx-1">→</span> {{ $route->dest_port }}
                                    </p>
                                    <p class="text-[10px] text-gray-400">
                                        ↓ {{ $route->imports }} import{{ $route->imports != 1 ? 's' : '' }} ·
                                        ↑ {{ $route->exports }} export{{ $route->exports != 1 ? 's' : '' }}
                                        @if($route->total_value)
                                        · ${{ number_format($route->total_value / 1000, 0) }}K value
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <span class="text-sm font-black text-[#0078C8] shrink-0 ml-2">{{ $route->total }}</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-[#0078C8] transition-all duration-700"
                                style="width: {{ round($route->total / $maxRoute * 100) }}%; opacity: {{ 1 - $i * 0.12 }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-center">
                    <i class="fa-solid fa-route text-gray-200 text-3xl mb-2"></i>
                    <p class="text-xs text-gray-400">No route data yet</p>
                </div>
            @endif
        </div>

        {{-- GD Status + Exam Queue --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Customs GD Status</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Goods declaration pipeline</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($kpis['exam_queue'] > 0)
                    <a href="{{ route('logistics.gd.exam') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-700 text-[10px] font-bold rounded-full hover:bg-amber-100 transition-colors">
                        <i class="fa-solid fa-magnifying-glass text-[9px]"></i>
                        {{ $kpis['exam_queue'] }} in exam queue
                    </a>
                    @endif
                    <a href="{{ route('logistics.gd.index') }}" class="text-xs text-[#0078C8] hover:underline">View all →</a>
                </div>
            </div>
            @if(count($gdData))
                <div class="space-y-3 mb-6">
                    @foreach($gdData as $gd)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $gd['color'] }}"></span>
                                <span class="text-xs font-medium text-gray-700">{{ $gd['status'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold" style="color:{{ $gd['color'] }}">{{ $gd['count'] }}</span>
                                <span class="text-[10px] text-gray-400 ml-1">{{ $gd['pct'] }}%</span>
                            </div>
                        </div>
                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                style="width: {{ $gd['pct'] }}%; background: {{ $gd['color'] }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Mini duty totals --}}
                <div class="grid grid-cols-3 gap-2 pt-4 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-base font-black text-gray-900">{{ $kpis['total_gds'] }}</p>
                        <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Total GDs</p>
                    </div>
                    <div class="text-center border-x border-gray-100">
                        <p class="text-base font-black text-green-600">{{ $kpis['cleared_gds'] }}</p>
                        <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Cleared</p>
                    </div>
                    <div class="text-center">
                        <p class="text-base font-black text-rose-600">
                            @if($kpis['total_duty_pkr'] >= 1000000)
                                PKR {{ number_format($kpis['total_duty_pkr'] / 1000000, 1) }}M
                            @else
                                PKR {{ number_format($kpis['total_duty_pkr'] / 1000, 0) }}K
                            @endif
                        </p>
                        <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Total Duty</p>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-center">
                    <i class="fa-solid fa-file-lines text-gray-200 text-3xl mb-2"></i>
                    <p class="text-xs text-gray-400">No GD data yet</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Row 3: Monthly Duty Breakdown ───────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Duty Stacked Bar --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Monthly Duty Assessment</h3>
                    <p class="text-xs text-gray-400 mt-0.5">PKR — customs + sales tax + income tax</p>
                </div>
                <div class="flex items-center gap-3 text-[10px] font-semibold uppercase tracking-wider">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-[#0078C8] inline-block"></span>Customs</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400 inline-block"></span>ST</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-rose-400 inline-block"></span>IT</span>
                </div>
            </div>
            @php $anyDuty = array_sum(array_column($monthlyDuty, 'duty')) > 0; @endphp
            @if($anyDuty)
            <div class="flex items-end gap-3 h-36">
                @foreach($monthlyDuty as $md)
                    @php
                        $hc = $maxDuty > 0 ? round($md['customs'] / $maxDuty * 100) : 0;
                        $hs = $maxDuty > 0 ? round($md['st']      / $maxDuty * 100) : 0;
                        $hi = $maxDuty > 0 ? round($md['it']      / $maxDuty * 100) : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center group relative">
                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] rounded px-2 py-1.5 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-left">
                            <p class="font-semibold mb-1">{{ $md['label'] }}</p>
                            <p>Customs: PKR {{ number_format($md['customs'], 0) }}</p>
                            <p>Sales Tax: PKR {{ number_format($md['st'], 0) }}</p>
                            <p>Income Tax: PKR {{ number_format($md['it'], 0) }}</p>
                        </div>
                        <div class="w-full flex flex-col justify-end gap-px rounded-t overflow-hidden" style="height:128px;">
                            @if($hi > 0)<div class="w-full bg-rose-400" style="height:{{ $hi }}%"></div>@endif
                            @if($hs > 0)<div class="w-full bg-amber-400" style="height:{{ $hs }}%"></div>@endif
                            @if($hc > 0)<div class="w-full bg-[#0078C8]" style="height:{{ $hc }}%"></div>@endif
                            @if($md['duty'] == 0)<div class="w-full bg-gray-100" style="height:4px"></div>@endif
                        </div>
                        <span class="text-[9px] text-gray-400 font-semibold mt-1">{{ $md['label'] }}</span>
                    </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center justify-center h-36 text-center">
                <i class="fa-solid fa-landmark text-gray-200 text-3xl mb-2"></i>
                <p class="text-xs text-gray-400">No filed GDs in the last 6 months</p>
            </div>
            @endif
        </div>

        {{-- Bank Charges by Type --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Bank Charges Breakdown</h3>
                    <p class="text-xs text-gray-400 mt-0.5">PKR — by charge type</p>
                </div>
                <a href="{{ route('logistics.lc.bank-charges.index') }}" class="text-xs text-[#0078C8] hover:underline">View all →</a>
            </div>
            @php
                $chargeColors = ['#0078C8','#8b5cf6','#f59e0b','#10b981','#ef4444','#06b6d4'];
            @endphp
            @if($chargesByType->count())
                <div class="space-y-3">
                    @foreach($chargesByType as $ci => $ct)
                    @php
                        $barPct = $totalChargesPkr > 0 ? round($ct->total_pkr / $totalChargesPkr * 100) : 0;
                        $clr    = $chargeColors[$ci % count($chargeColors)];
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $clr }}"></span>
                                <span class="text-xs text-gray-700 truncate">{{ ucwords(str_replace('_', ' ', $ct->charge_type)) }}</span>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <span class="text-xs font-bold" style="color:{{ $clr }}">PKR {{ number_format($ct->total_pkr / 1000, 0) }}K</span>
                                <span class="text-[10px] text-gray-400 ml-1">{{ $barPct }}%</span>
                            </div>
                        </div>
                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                style="width: {{ $barPct }}%; background: {{ $clr }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Total recorded</p>
                    <p class="text-sm font-black text-gray-900">PKR {{ number_format($totalChargesPkr / 1000, 0) }}K</p>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-center">
                    <i class="fa-solid fa-building-columns text-gray-200 text-3xl mb-2"></i>
                    <p class="text-xs text-gray-400">No bank charges recorded</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Row 4: LC Exposure + Monthly LC Volume ───────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- LC by Status --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">LC Exposure by Status</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Active portfolio breakdown (USD)</p>
                </div>
                <a href="{{ route('logistics.lc.index') }}" class="text-xs text-[#0078C8] hover:underline">View LCs →</a>
            </div>
            @if($lcByStatus->count())
                <div class="space-y-3">
                    @php $maxLcVal = $lcByStatus->max('total') ?: 1; @endphp
                    @foreach($lcByStatus as $ls)
                    @php
                        $clr  = $lcStatusColors[$ls->status] ?? '#94a3b8';
                        $barP = round($ls->total / $maxLcVal * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $clr }}"></span>
                                <span class="text-xs font-medium text-gray-700">{{ $ls->status }}</span>
                                <span class="text-[10px] text-gray-400">({{ $ls->cnt }} LC{{ $ls->cnt != 1 ? 's' : '' }})</span>
                            </div>
                            <span class="text-xs font-bold" style="color:{{ $clr }}">
                                ${{ number_format($ls->total / 1000, 0) }}K
                            </span>
                        </div>
                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                style="width: {{ $barP }}%; background: {{ $clr }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-3 gap-3">
                    <div class="text-center">
                        <p class="text-base font-black text-gray-900">{{ $kpis['total_lcs'] }}</p>
                        <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Total LCs</p>
                    </div>
                    <div class="text-center border-x border-gray-100">
                        <p class="text-base font-black text-[#0078C8]">{{ $kpis['active_lcs'] }}</p>
                        <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Active</p>
                    </div>
                    <div class="text-center">
                        <p class="text-base font-black text-indigo-600">
                            ${{ $kpis['total_lc_value'] >= 1000000 ? number_format($kpis['total_lc_value']/1000000, 1).'M' : number_format($kpis['total_lc_value']/1000, 0).'K' }}
                        </p>
                        <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Exposure</p>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-center">
                    <i class="fa-solid fa-file-invoice-dollar text-gray-200 text-3xl mb-2"></i>
                    <p class="text-xs text-gray-400">No LC data yet</p>
                </div>
            @endif
        </div>

        {{-- Monthly LC Opened --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">LCs Opened per Month</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Count & total USD value — last 6 months</p>
                </div>
            </div>
            @php $anyLc = array_sum(array_column($monthlyLc, 'count')) > 0; @endphp
            @if($anyLc)
                @php $maxLcCount = max(array_column($monthlyLc, 'count') ?: [1]); @endphp
                <div class="flex items-end gap-3 h-28 mb-4">
                    @foreach($monthlyLc as $ml)
                    @php $lh = $maxLcCount > 0 ? round($ml['count'] / $maxLcCount * 100) : 0; @endphp
                    <div class="flex-1 flex flex-col items-center group relative">
                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                            {{ $ml['label'] }}: {{ $ml['count'] }} LC(s)<br>
                            ${{ number_format($ml['amount'] / 1000, 0) }}K value
                        </div>
                        <div class="w-full flex flex-col justify-end" style="height:112px;">
                            <div class="w-full rounded-t-sm bg-indigo-500 transition-all duration-700"
                                style="height: {{ $lh }}%; min-height: {{ $ml['count'] > 0 ? 4 : 0 }}px;"></div>
                        </div>
                        <span class="text-[9px] text-gray-400 font-semibold mt-1">{{ $ml['label'] }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="space-y-2 border-t border-gray-100 pt-4">
                    @foreach($monthlyLc as $ml)
                    @if($ml['count'] > 0)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">{{ $ml['label'] }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-gray-700">{{ $ml['count'] }} LC{{ $ml['count'] != 1 ? 's' : '' }}</span>
                            <span class="text-indigo-600 font-bold">${{ number_format($ml['amount'] / 1000, 0) }}K</span>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-40 text-center">
                    <i class="fa-solid fa-chart-bar text-gray-200 text-3xl mb-2"></i>
                    <p class="text-xs text-gray-400">No LC issue dates in recent months</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Quick Navigation ─────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Quick Navigation</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2">
            @php
                $quickLinks = [
                    ['route' => 'logistics.manage-booking.v2.index', 'icon' => 'fa-boxes-stacked',       'label' => 'Bookings',     'color' => 'text-[#0078C8] bg-blue-50   hover:bg-blue-100'],
                    ['route' => 'logistics.gd.index',                'icon' => 'fa-file-lines',           'label' => 'GDs',          'color' => 'text-amber-600 bg-amber-50  hover:bg-amber-100'],
                    ['route' => 'logistics.gd.exam',                 'icon' => 'fa-magnifying-glass',     'label' => 'Exam Queue',   'color' => 'text-red-600   bg-red-50    hover:bg-red-100'],
                    ['route' => 'logistics.lc.index',                'icon' => 'fa-file-invoice-dollar',  'label' => 'Letters of Credit', 'color' => 'text-indigo-600 bg-indigo-50 hover:bg-indigo-100'],
                    ['route' => 'logistics.lc.bank-contracts.index', 'icon' => 'fa-file-contract',        'label' => 'Bank Contracts','color' => 'text-purple-600 bg-purple-50 hover:bg-purple-100'],
                    ['route' => 'logistics.lc.bank-charges.index',   'icon' => 'fa-building-columns',     'label' => 'Bank Charges', 'color' => 'text-orange-600 bg-orange-50 hover:bg-orange-100'],
                    ['route' => 'logistics.lc.payments.index',        'icon' => 'fa-clock',                'label' => 'Payments',     'color' => 'text-teal-600  bg-teal-50   hover:bg-teal-100'],
                    ['route' => 'logistics.lc.landed-cost',          'icon' => 'fa-calculator',           'label' => 'Landed Cost',  'color' => 'text-green-600 bg-green-50  hover:bg-green-100'],
                ];
            @endphp
            @foreach($quickLinks as $ql)
            <a href="{{ route($ql['route']) }}"
                class="flex flex-col items-center gap-2 py-3 px-2 rounded-xl {{ $ql['color'] }} transition-colors text-center">
                <i class="fa-solid {{ $ql['icon'] }} text-lg"></i>
                <span class="text-[10px] font-semibold leading-tight">{{ $ql['label'] }}</span>
            </a>
            @endforeach
        </div>
    </div>

</div>
@endsection
