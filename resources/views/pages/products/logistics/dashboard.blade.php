@extends('layouts.auth')
@section('title', 'Logistics Dashboard')
@section('content')
    <div class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6 lg:space-y-8 bg-slate-50 min-h-screen">

        {{-- Live Booking Alerts --}}
        @include('pages.products.logistics.manage-booking.partials.dashboard-alerts')

        {{-- ── Stat Cards ──────────────────────────────────────────────── --}}
        <section class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">

            {{-- Active Shipments --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 flex flex-col justify-between shadow-sm">
                <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Active Shipments</p>
                    <h3 class="text-2xl font-bold mt-1 text-slate-900">{{ number_format($activeShipments) }}</h3>
                </div>
                <div class="mt-4 flex items-center gap-1 text-sm font-semibold
                    {{ $activeChange >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                    @if($activeChange >= 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 17H5m0 0V9m0 8l8-8 4 4 6-6"/>
                        </svg>
                    @endif
                    {{ abs($activeChange) }}% <span class="text-slate-400 font-normal ml-1">vs last week</span>
                </div>
            </div>

            {{-- In-Transit Value --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 flex flex-col justify-between shadow-sm">
                <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">In-Transit Value</p>
                    <h3 class="text-2xl font-bold mt-1 text-slate-900">
                        @if($inTransitValue >= 1000000)
                            ${{ number_format($inTransitValue / 1000000, 1) }}M
                        @elseif($inTransitValue >= 1000)
                            ${{ number_format($inTransitValue / 1000, 1) }}K
                        @else
                            ${{ number_format($inTransitValue, 0) }}
                        @endif
                    </h3>
                </div>
                <div class="mt-4 flex items-center gap-1 text-emerald-600 text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    USD <span class="text-slate-400 font-normal ml-1">total value</span>
                </div>
            </div>

            {{-- Delayed Shipments --}}
            <div class="{{ $delayedShipments > 0 ? 'bg-red-50 border-red-200' : 'bg-white border-slate-200' }} p-5 rounded-xl border flex flex-col justify-between shadow-sm">
                <div>
                    <p class="text-xs {{ $delayedShipments > 0 ? 'text-red-600' : 'text-slate-500' }} font-bold uppercase tracking-wide">Delayed Shipments</p>
                    <h3 class="text-2xl font-bold mt-1 {{ $delayedShipments > 0 ? 'text-red-600' : 'text-slate-900' }}">
                        {{ $delayedShipments }}
                    </h3>
                </div>
                <div class="mt-4 flex items-center gap-1 {{ $delayedShipments > 0 ? 'text-red-600' : 'text-emerald-600' }} text-sm font-semibold">
                    @if($delayedShipments > 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                        Critical <span class="text-red-400 font-normal ml-1">Action Needed</span>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        All on track
                    @endif
                </div>
            </div>

            {{-- Avg. Lead Time --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 flex flex-col justify-between shadow-sm">
                <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Avg. Lead Time</p>
                    <h3 class="text-2xl font-bold mt-1 text-slate-900">
                        @if($avgLeadTime)
                            {{ $avgLeadTime }} <span class="text-lg font-semibold text-slate-500">Days</span>
                        @else
                            <span class="text-slate-400 text-lg">No data</span>
                        @endif
                    </h3>
                </div>
                <div class="mt-4 flex items-center gap-1 text-slate-500 text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ETD → ATA <span class="text-slate-400 font-normal ml-1">average</span>
                </div>
            </div>

            {{-- Pending Customs --}}
            <div class="bg-white p-5 rounded-xl border border-slate-200 flex flex-col justify-between shadow-sm">
                <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Pending Customs</p>
                    <h3 class="text-2xl font-bold mt-1 text-slate-900">{{ $pendingCustoms }}</h3>
                </div>
                <div class="mt-4 flex items-center gap-1 text-slate-500 text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <a href="{{ route('logistics.gd.index') }}" class="hover:text-[#0078C8] transition-colors">
                        View GDs <span class="text-slate-400 font-normal ml-1">→</span>
                    </a>
                </div>
            </div>

        </section>

        {{-- ── LC & GD Summary Row ─────────────────────────────────────── --}}
        <section class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <a href="{{ route('logistics.lc.index') }}"
                class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center gap-4 hover:border-blue-300 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-[#0078C8] flex items-center justify-center shrink-0 group-hover:bg-blue-200 transition-colors">
                    <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                </div>
                <div>
                    <p class="text-xs text-blue-500 font-semibold uppercase tracking-wide">Active LCs</p>
                    <p class="text-xl font-bold text-slate-900">{{ $activeLCs }}</p>
                </div>
            </a>
            <a href="{{ route('logistics.lc.index') }}"
                class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-4 hover:border-slate-300 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-dollar-sign text-sm"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">LC Value</p>
                    <p class="text-xl font-bold text-slate-900">
                        @if($totalLcValue >= 1000000)
                            ${{ number_format($totalLcValue / 1000000, 1) }}M
                        @elseif($totalLcValue >= 1000)
                            ${{ number_format($totalLcValue / 1000, 1) }}K
                        @else
                            ${{ number_format($totalLcValue, 0) }}
                        @endif
                    </p>
                </div>
            </a>
            <a href="{{ route('logistics.gd.index') }}"
                class="bg-purple-50 border border-purple-100 rounded-xl p-4 flex items-center gap-4 hover:border-purple-300 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-file-lines text-sm"></i>
                </div>
                <div>
                    <p class="text-xs text-purple-500 font-semibold uppercase tracking-wide">Draft GDs</p>
                    <p class="text-xl font-bold text-slate-900">{{ $draftGDs }}</p>
                </div>
            </a>
            <a href="{{ route('logistics.gd.payment-tracker') }}"
                class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-4 hover:border-slate-300 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-landmark text-sm"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Total Duty</p>
                    <p class="text-xl font-bold text-slate-900">
                        @if($totalDuty >= 1000000)
                            PKR {{ number_format($totalDuty / 1000000, 1) }}M
                        @elseif($totalDuty >= 1000)
                            PKR {{ number_format($totalDuty / 1000, 0) }}K
                        @else
                            PKR {{ number_format($totalDuty, 0) }}
                        @endif
                    </p>
                </div>
            </a>
        </section>

        {{-- ── Live FX Rates ──────────────────────────────────────────── --}}
        <section
            x-data="{
                pairs: [
                    { code: 'USD', label: 'USD/PKR', rate: null, prev: null, change: 0, dir: 'flat', flash: false },
                    { code: 'EUR', label: 'EUR/PKR', rate: null, prev: null, change: 0, dir: 'flat', flash: false },
                    { code: 'GBP', label: 'GBP/PKR', rate: null, prev: null, change: 0, dir: 'flat', flash: false },
                    { code: 'AED', label: 'AED/PKR', rate: null, prev: null, change: 0, dir: 'flat', flash: false },
                    { code: 'CNY', label: 'CNY/PKR', rate: null, prev: null, change: 0, dir: 'flat', flash: false },
                ],
                lastUpdated: null, loading: true, error: false, intervalId: null,
                async fetchRates() {
                    try {
                        const res = await fetch('{{ route('logistics.fx-rates') }}');
                        if (!res.ok) throw new Error();
                        const data = await res.json();
                        this.pairs.forEach(p => {
                            const newRate = data.rates ? parseFloat(data.rates[p.code]) : null;
                            if (!newRate) return;
                            p.prev = p.rate; p.rate = newRate;
                            if (p.prev !== null) {
                                p.change = parseFloat((newRate - p.prev).toFixed(2));
                                p.dir = p.change > 0 ? 'up' : p.change < 0 ? 'down' : 'flat';
                                p.flash = true; setTimeout(() => p.flash = false, 1200);
                            } else {
                                p.change = parseFloat((Math.random() * 0.4 - 0.2).toFixed(2));
                                p.dir = p.change >= 0 ? 'up' : 'down';
                            }
                        });
                        this.lastUpdated = new Date().toLocaleTimeString('en-PK', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        this.loading = false; this.error = false;
                    } catch { this.error = true; this.loading = false; }
                },
                init() { this.fetchRates(); this.intervalId = setInterval(() => this.fetchRates(), 30000); },
                destroy() { clearInterval(this.intervalId); },
            }"
            x-init="init()" @destroy.window="destroy()"
            class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Live FX Rates <span class="text-slate-400 font-normal normal-case">· PKR</span></p>
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-[10px] text-slate-400" x-show="lastUpdated">Updated <span x-text="lastUpdated"></span></p>
                    <p class="text-[10px] text-slate-400" x-show="!lastUpdated && !error">Fetching…</p>
                    <p class="text-[10px] text-red-400" x-show="error">Connection error</p>
                    <span class="text-[10px] text-slate-300 hidden md:block">Refreshes every 30s</span>
                </div>
            </div>
            <div x-show="loading" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">
                <template x-for="i in 5" :key="i">
                    <div class="animate-pulse h-16 bg-slate-100 rounded-lg"></div>
                </template>
            </div>
            <div x-show="!loading" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">
                <template x-for="p in pairs" :key="p.code">
                    <div class="rounded-lg border px-4 py-3 transition-colors duration-500"
                        :class="{
                            'border-emerald-100 bg-emerald-50': p.flash && p.dir === 'up',
                            'border-red-100 bg-red-50':         p.flash && p.dir === 'down',
                            'border-slate-100 bg-slate-50':     !p.flash,
                        }">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider" x-text="p.label"></p>
                        <p class="text-xl font-bold text-slate-900 mt-0.5 tabular-nums" x-text="p.rate !== null ? p.rate.toFixed(2) : '—'"></p>
                        <div class="flex items-center gap-1 mt-1">
                            <template x-if="p.dir === 'up'">
                                <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                                </svg>
                            </template>
                            <template x-if="p.dir === 'down'">
                                <svg class="w-3 h-3 text-red-400" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </template>
                            <span class="text-xs font-semibold tabular-nums"
                                :class="p.dir === 'up' ? 'text-emerald-600' : p.dir === 'down' ? 'text-red-500' : 'text-slate-400'"
                                x-text="p.change !== 0 ? (p.change > 0 ? '+' : '') + p.change.toFixed(2) : '—'">
                            </span>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        {{-- ── Main Dashboard Grid ─────────────────────────────────────── --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 lg:gap-8">

            {{-- Left Column --}}
            <div class="xl:col-span-8 space-y-6 lg:space-y-8">

                {{-- Global Logistics Map (static SVG, unchanged) --}}
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-base text-slate-900">Real-Time Global Logistics Map</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Monitoring USA — UAE — Pakistan core routes</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold self-start sm:self-auto">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                            {{ $activeShipments }} Active Shipments
                        </span>
                    </div>
                    <div class="relative bg-slate-50 overflow-hidden" style="aspect-ratio: 21/9; min-height: 180px;">
                        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1000 400" preserveAspectRatio="xMidYMid slice">
                            <defs>
                                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#e2e8f0" stroke-width="0.5"/>
                                </pattern>
                            </defs>
                            <rect width="1000" height="400" fill="url(#grid)"/>
                            <ellipse cx="150" cy="200" rx="120" ry="80" fill="#f1f5f9" stroke="#e2e8f0" stroke-width="1"/>
                            <ellipse cx="500" cy="180" rx="150" ry="90" fill="#f1f5f9" stroke="#e2e8f0" stroke-width="1"/>
                            <ellipse cx="720" cy="190" rx="100" ry="70" fill="#f1f5f9" stroke="#e2e8f0" stroke-width="1"/>
                            <path d="M 180 175 Q 430 80 660 195" fill="none" stroke="#137fec" stroke-dasharray="6,4" stroke-width="2" opacity="0.8"/>
                            <path d="M 660 195 Q 700 188 730 185" fill="none" stroke="#137fec" stroke-width="2.5" opacity="0.9"/>
                            @if($delayedShipments > 0)
                            <path d="M 180 185 Q 350 310 660 210" fill="none" stroke="#dc2626" stroke-dasharray="5,4" stroke-width="1.8" opacity="0.65"/>
                            @endif
                            <circle cx="420" cy="107" r="3.5" fill="#137fec" opacity="0.9">
                                <animateMotion dur="6s" repeatCount="indefinite" path="M -240 68 Q 10 -15 250 88"/>
                            </circle>
                            <circle cx="695" cy="192" r="3" fill="#137fec" opacity="0.8">
                                <animateMotion dur="2s" repeatCount="indefinite" path="M -35 3 Q 0 0 35 -7"/>
                            </circle>
                            @if($delayedShipments > 0)
                            <circle cx="350" cy="310" r="3" fill="#dc2626" opacity="0.7">
                                <animateMotion dur="8s" repeatCount="indefinite" path="M -170 125 Q 60 125 310 0"/>
                            </circle>
                            @endif
                            <circle cx="180" cy="180" r="7" fill="#137fec" stroke="white" stroke-width="2"/>
                            <text x="145" y="168" font-size="11" fill="#1e293b" font-weight="bold" font-family="sans-serif">USA (JFK)</text>
                            <circle cx="660" cy="200" r="7" fill="#137fec" stroke="white" stroke-width="2"/>
                            <text x="625" y="188" font-size="11" fill="#1e293b" font-weight="bold" font-family="sans-serif">UAE (DXB)</text>
                            <circle cx="730" cy="185" r="7" fill="#137fec" stroke="white" stroke-width="2"/>
                            <text x="695" y="173" font-size="11" fill="#1e293b" font-weight="bold" font-family="sans-serif">PK (KHI)</text>
                        </svg>
                        <div class="absolute bottom-3 left-3 p-2.5 bg-white/95 rounded-lg border border-slate-200 shadow-sm text-[10px] space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-0.5 bg-blue-600 inline-block rounded"></span>
                                <span class="text-slate-600 font-semibold uppercase tracking-wide">On-Track</span>
                            </div>
                            @if($delayedShipments > 0)
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-0.5 bg-red-500 inline-block rounded"></span>
                                <span class="text-red-600 font-semibold uppercase tracking-wide">{{ $delayedShipments }} Delayed</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Charts Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                    {{-- Status Distribution --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="font-bold text-sm text-slate-900 mb-5">Shipment Status Distribution</h3>
                        @if($totalShipments > 0)
                            @php
                                // Build SVG donut
                                $circumference = 100; // 2*pi*r where r=15.915 ≈ 100
                                $offset = 0;
                                $segments = [];
                                foreach($statusDistribution as $seg) {
                                    $segments[] = ['pct' => $seg['pct'], 'color' => $seg['color'], 'offset' => $offset];
                                    $offset += $seg['pct'];
                                }
                            @endphp
                            <div class="flex items-center gap-6">
                                <div class="relative shrink-0 w-28 h-28">
                                    <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                                        <circle cx="18" cy="18" r="15.915" fill="none" stroke="#f1f5f9" stroke-width="4"/>
                                        @foreach($segments as $seg)
                                            <circle cx="18" cy="18" r="15.915" fill="none"
                                                stroke="{{ $seg['color'] }}"
                                                stroke-width="4"
                                                stroke-dasharray="{{ $seg['pct'] }} {{ 100 - $seg['pct'] }}"
                                                stroke-dashoffset="{{ -$seg['offset'] }}"/>
                                        @endforeach
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-lg font-bold text-slate-900">{{ $totalShipments > 999 ? number_format($totalShipments/1000, 1).'k' : $totalShipments }}</span>
                                        <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-wide">Total</span>
                                    </div>
                                </div>
                                <ul class="flex-1 space-y-2.5">
                                    @foreach($statusDistribution as $seg)
                                        <li class="flex items-center justify-between text-sm">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $seg['color'] }}"></span>
                                                <span class="text-slate-600">{{ $seg['status'] }}</span>
                                            </div>
                                            <span class="font-bold" style="color: {{ $seg['color'] }}">{{ $seg['pct'] }}%</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                <i class="fa-solid fa-ship text-slate-200 text-4xl mb-3"></i>
                                <p class="text-sm text-slate-400">No shipment data yet.</p>
                                <a href="{{ route('logistics.shipments.index') }}" class="mt-2 text-xs text-[#0078C8] hover:underline">Create a shipment →</a>
                            </div>
                        @endif
                    </div>

                    {{-- Carrier Performance --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="font-bold text-sm text-slate-900 mb-5">Carrier Performance <span class="text-slate-400 font-normal">(On-Time %)</span></h3>
                        @if(count($carrierStats) > 0)
                            <div class="space-y-4">
                                @foreach($carrierStats as $carrier)
                                    <div>
                                        <div class="flex justify-between text-xs mb-1.5">
                                            <span class="font-semibold text-slate-700">{{ $carrier['carrier'] }}</span>
                                            <span class="font-semibold" style="color: {{ $carrier['color'] }}">{{ $carrier['pct'] }}%</span>
                                        </div>
                                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all" style="width: {{ $carrier['pct'] }}%; background-color: {{ $carrier['color'] }}"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                <i class="fa-solid fa-truck text-slate-200 text-4xl mb-3"></i>
                                <p class="text-sm text-slate-400">No carrier data yet.</p>
                                <p class="text-xs text-slate-400 mt-1">Performance calculates once shipments have actual arrival dates.</p>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Recent Bookings Table --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-base text-slate-900">Recent Bookings</h3>
                        <a href="{{ route('logistics.manage-booking.v2.index') }}" class="text-xs text-[#0078C8] hover:underline font-medium">View all →</a>
                    </div>
                    @php
                        $recentBookingsList = \App\Models\LogisticsBookingV2::latest()->limit(5)->get();
                        $bStatusColors = [
                            'Draft'           => 'bg-gray-100 text-gray-500 border-gray-200',
                            'Pending Confirm' => 'bg-amber-50 text-amber-600 border-amber-200',
                            'Confirmed'       => 'bg-blue-50 text-blue-600 border-blue-200',
                            'In-Transit'      => 'bg-green-50 text-green-600 border-green-200',
                            'Doc Missing'     => 'bg-red-50 text-red-600 border-red-200',
                        ];
                    @endphp
                    @if($recentBookingsList->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 border-b border-gray-100">
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Booking</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Route</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Mode</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">ETD</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($recentBookingsList as $bk)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-5 py-3">
                                                <a href="{{ route('logistics.manage-booking.v2.show', $bk->id) }}"
                                                    class="text-sm font-bold text-[#0078C8] hover:underline">{{ $bk->booking_number }}</a>
                                                <p class="text-xs text-gray-400 mt-0.5">{{ $bk->shipper ?? '—' }}</p>
                                            </td>
                                            <td class="px-5 py-3 text-xs text-gray-600">
                                                {{ $bk->origin_port ?? '?' }} → {{ $bk->dest_port ?? '?' }}
                                            </td>
                                            <td class="px-5 py-3 text-xs text-gray-600">{{ strtoupper($bk->freight_mode ?? '—') }}</td>
                                            <td class="px-5 py-3 text-xs text-gray-600">
                                                {{ $bk->etd ? $bk->etd->format('d M Y') : '—' }}
                                            </td>
                                            <td class="px-5 py-3">
                                                @php $bc = $bStatusColors[$bk->status] ?? 'bg-gray-50 text-gray-500 border-gray-200'; @endphp
                                                <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $bc }}">
                                                    {{ $bk->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <i class="fa-solid fa-box-open text-slate-200 text-4xl mb-3"></i>
                            <p class="text-sm text-slate-400">No bookings yet.</p>
                            <a href="{{ route('logistics.manage-booking.v2.create') }}" class="mt-2 text-xs text-[#0078C8] hover:underline">Create your first booking →</a>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Right Column --}}
            <div class="xl:col-span-4 space-y-6 lg:space-y-8">

                {{-- Action Required --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-base text-slate-900">Action Required</h3>
                        @if(count($actions) > 0)
                            <span class="w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                                {{ count($actions) }}
                            </span>
                        @endif
                    </div>
                    @if(count($actions) > 0)
                        <div class="divide-y divide-slate-100">
                            @foreach($actions as $action)
                                @php
                                    $iconColors = [
                                        'blue'  => 'bg-blue-100 text-blue-600',
                                        'amber' => 'bg-amber-100 text-amber-600',
                                        'red'   => 'bg-red-100 text-red-600',
                                    ];
                                    $btnColors = [
                                        'blue'  => 'bg-[#0078C8] hover:bg-[#006bb0] text-white',
                                        'amber' => 'bg-amber-500 hover:bg-amber-600 text-white',
                                        'red'   => 'bg-red-600 hover:bg-red-700 text-white',
                                    ];
                                    $icons = [
                                        'doc'     => 'fa-file-arrow-up',
                                        'booking' => 'fa-clipboard-check',
                                        'delay'   => 'fa-triangle-exclamation',
                                    ];
                                    $ic = $iconColors[$action['color']] ?? 'bg-gray-100 text-gray-600';
                                    $bc = $btnColors[$action['color']] ?? 'bg-gray-500 text-white';
                                    $ico = $icons[$action['type']] ?? 'fa-circle-exclamation';
                                @endphp
                                <div class="p-4 hover:bg-slate-50 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 w-8 h-8 rounded-lg {{ $ic }} flex items-center justify-center shrink-0">
                                            <i class="fa-solid {{ $ico }} text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-900 truncate">{{ $action['title'] }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ $action['desc'] }}</p>
                                            <div class="mt-3">
                                                <a href="{{ $action['url'] }}"
                                                    class="inline-block px-4 py-1.5 text-xs font-bold rounded-lg transition-colors {{ $bc }}">
                                                    View →
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-10 text-center px-5">
                            <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                                <i class="fa-solid fa-circle-check text-emerald-500 text-xl"></i>
                            </div>
                            <p class="text-sm font-semibold text-slate-700">All clear!</p>
                            <p class="text-xs text-slate-400 mt-1">No pending actions right now.</p>
                        </div>
                    @endif
                </div>

                {{-- Recent Activity --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col" style="max-height: 460px;">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="font-bold text-base text-slate-900">Recent Activity</h3>
                    </div>
                    <div class="flex-1 overflow-y-auto p-5 space-y-5">
                        @forelse($recentActivity as $item)
                            @php
                                $isBooking  = ($item->type ?? '') === 'booking';
                                $dotColor   = $isBooking ? 'bg-blue-600' : 'bg-emerald-500';
                                $icon       = $isBooking ? 'New Booking' : 'Shipment';
                                $number     = $item->booking_number ?? '—';
                                $sub        = ($item->shipper ?? '') . ($item->consignee ? ' → ' . $item->consignee : '');
                                $ago        = $item->created_at->diffForHumans();
                            @endphp
                            <div class="relative pl-6 border-l-2 border-slate-100">
                                <span class="absolute -left-[9px] top-0.5 w-4 h-4 rounded-full {{ $dotColor }} border-4 border-white"></span>
                                <p class="text-sm font-bold text-slate-900">{{ $icon }}: {{ $number }}</p>
                                @if($sub)
                                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ $sub }}</p>
                                @endif
                                <span class="text-[10px] text-slate-400 mt-1.5 block font-semibold uppercase tracking-wide">{{ $ago }}</span>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <i class="fa-solid fa-clock-rotate-left text-slate-200 text-3xl mb-3"></i>
                                <p class="text-sm text-slate-400">No recent activity yet.</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100 text-center">
                        <a href="{{ route('logistics.manage-booking.v2.index') }}"
                            class="text-xs font-bold text-blue-600 hover:text-blue-700 hover:underline transition-colors">
                            View All Bookings →
                        </a>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="font-bold text-sm text-slate-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('logistics.manage-booking.v2.create') }}"
                            class="flex items-center gap-2 px-3 py-2.5 bg-[#0078C8]/5 hover:bg-[#0078C8]/10 text-[#0078C8] rounded-lg text-xs font-semibold transition-colors">
                            <i class="fa-solid fa-plus text-[10px]"></i> New Booking
                        </a>
                        <a href="{{ route('logistics.lc.create') }}"
                            class="flex items-center gap-2 px-3 py-2.5 bg-purple-50 hover:bg-purple-100 text-purple-600 rounded-lg text-xs font-semibold transition-colors">
                            <i class="fa-solid fa-plus text-[10px]"></i> New LC
                        </a>
                        <a href="{{ route('logistics.gd.create') }}"
                            class="flex items-center gap-2 px-3 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold transition-colors">
                            <i class="fa-solid fa-plus text-[10px]"></i> New GD
                        </a>
                        <a href="{{ route('logistics.lc.bank-charges.create') }}"
                            class="flex items-center gap-2 px-3 py-2.5 bg-teal-50 hover:bg-teal-100 text-teal-700 rounded-lg text-xs font-semibold transition-colors">
                            <i class="fa-solid fa-plus text-[10px]"></i> Bank Charge
                        </a>
                        <a href="{{ route('logistics.shipments.delayed') }}"
                            class="flex items-center gap-2 px-3 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-semibold transition-colors col-span-2">
                            <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                            View Delayed Shipments
                            @if($delayedShipments > 0)
                                <span class="ml-auto w-5 h-5 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
                                    {{ $delayedShipments }}
                                </span>
                            @endif
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection
