@extends('layouts.auth')

@section('title', 'Shipment Status Tracker')

@section('content')
    <div class="mt-4" x-data="{
        query: '',
        searched: false,
        shipment: '',

        dataset: {
            'SH-398': {
                id: 'SH-398',
                shipper: 'Faizan Textiles',
                route: 'KHI → LHR',
                carrier: 'Maersk',
                containers: '2 × 40ft',
                overallStatus: 'On Track',
                currentStep: 4,
                steps: ['Booked', 'Docs OK', 'Customs', 'Departed', 'In-Transit', 'Arrived', 'Delivered'],
                events: [
                    {
                        icon: 'check',
                        done: true,
                        title: 'Departed Port Qasim, KHI',
                        meta: 'Vessel: MSC ELSA · Voyage: ME2304W · On-time departure',
                        date: 'Apr 10, 2026 · 06:30 PKT',
                    },
                    {
                        icon: 'check',
                        done: true,
                        title: 'Customs Clearance Approved',
                        meta: 'GD No. 2024-4417 · Duty paid: PKR 1.2M · FBR portal confirmed',
                        date: 'Apr 09, 2026 · 14:15 PKT',
                    },
                    {
                        icon: 'box',
                        done: false,
                        title: 'Cargo Loaded at Container Terminal',
                        meta: 'Gate In: 08:45 · Seal No. PKR-002241 · 2 containers confirmed',
                        date: 'Apr 09, 2026 · 09:00 PKT',
                    },
                    {
                        icon: 'bookmark',
                        done: false,
                        title: 'Booking Confirmed by Maersk',
                        meta: 'Booking Ref: MAEU-20240891 · Rate locked: $1,850/20ft',
                        date: 'Apr 07, 2026 · 10:30 PKT',
                    },
                ],
            },
            'SH-397': {
                id: 'SH-397',
                shipper: 'PakSupply Co.',
                route: 'KHI → DXB',
                carrier: 'Emirates SkyCargo',
                containers: '1 × 20ft',
                overallStatus: 'Delayed',
                currentStep: 5,
                steps: ['Booked', 'Docs OK', 'Customs', 'Departed', 'In-Transit', 'Arrived', 'Delivered'],
                events: [
                    {
                        icon: 'check',
                        done: true,
                        title: 'In-Transit — Dubai Hub',
                        meta: 'Flight EK-702 · Expected arrival DXB: Apr 14, 2026',
                        date: 'Apr 12, 2026 · 18:00 PKT',
                    },
                    {
                        icon: 'check',
                        done: true,
                        title: 'Departed Karachi Airport',
                        meta: 'AWB: 176-12345678 · On-time departure',
                        date: 'Apr 11, 2026 · 22:45 PKT',
                    },
                    {
                        icon: 'check',
                        done: true,
                        title: 'Customs Cleared',
                        meta: 'GD No. 2024-3891 · Duty paid: PKR 0.8M',
                        date: 'Apr 11, 2026 · 15:00 PKT',
                    },
                    {
                        icon: 'bookmark',
                        done: false,
                        title: 'Booking Confirmed by Emirates',
                        meta: 'Booking Ref: EK-20240712 · Rate locked: $2,200/20ft',
                        date: 'Apr 08, 2026 · 09:00 PKT',
                    },
                ],
            },
            'SH-396': {
                id: 'SH-396',
                shipper: 'Masood Exports',
                route: 'SKT → IST',
                carrier: 'Turkish Cargo',
                containers: '3 × 20ft',
                overallStatus: 'Delivered',
                currentStep: 7,
                steps: ['Booked', 'Docs OK', 'Customs', 'Departed', 'In-Transit', 'Arrived', 'Delivered'],
                events: [
                    {
                        icon: 'check',
                        done: true,
                        title: 'Delivered to Consignee, Istanbul',
                        meta: 'POD signed · All 3 containers received in good condition',
                        date: 'Apr 13, 2026 · 11:00 TRT',
                    },
                    {
                        icon: 'check',
                        done: true,
                        title: 'Arrived Istanbul Airport',
                        meta: 'Flight TK-709 · Cleared Turkish customs',
                        date: 'Apr 12, 2026 · 07:30 TRT',
                    },
                    {
                        icon: 'check',
                        done: true,
                        title: 'Departed Sialkot International',
                        meta: 'AWB: 235-99871234 · Departed on schedule',
                        date: 'Apr 10, 2026 · 03:00 PKT',
                    },
                    {
                        icon: 'check',
                        done: true,
                        title: 'Booking Confirmed by Turkish Cargo',
                        meta: 'Booking Ref: TK-20240601 · Rate locked: $1,650/20ft',
                        date: 'Apr 05, 2026 · 12:00 PKT',
                    },
                ],
            },
        },

        recent: [
            { id: 'SH-398', shipper: 'Faizan Textiles', route: 'KHI → LHR', status: 'On Track' },
            { id: 'SH-397', shipper: 'PakSupply Co.', route: 'KHI → DXB', status: 'Delayed' },
            { id: 'SH-396', shipper: 'Masood Exports', route: 'SKT → IST', status: 'Delivered' },
        ],

        result: null,

        search() {
            const key = this.query.trim().toUpperCase();
            if (!key) return;
            this.shipment = key;
            this.result = this.dataset[key] ?? null;
            this.searched = true;
        },

        reset() {
            this.searched = false;
            this.query = '';
            this.shipment = '';
            this.result = null;
        },

        statusBadgeClass(s) {
            if (s === 'On Track') return 'bg-green-50 text-green-600 border border-green-200';
            if (s === 'Delayed') return 'bg-red-50 text-red-500 border border-red-200';
            if (s === 'Delivered') return 'bg-blue-50 text-blue-600 border border-blue-200';
            return 'bg-gray-100 text-gray-500 border border-gray-200';
        },

        recentStatusClass(s) {
            if (s === 'On Track') return 'text-green-600 bg-green-50 border-green-200';
            if (s === 'Delayed') return 'text-red-500 bg-red-50 border-red-200';
            if (s === 'Delivered') return 'text-blue-600 bg-blue-50 border-blue-200';
            return 'text-gray-500 bg-gray-100 border-gray-200';
        },
    }">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">Shipment Status Tracker</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Track real-time status of any shipment by reference number</p>
                </div>
                <x-common.back-button url="{{ route('logistics.shipments.index') }}" />
            </div>

            {{-- Search --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5 mb-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Enter Shipment Reference</p>
                <div class="flex items-center gap-3 max-w-lg">
                    <div class="relative flex-1">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" x-model="query" @keydown.enter="search()"
                            placeholder="e.g. SH-398, SH-397…"
                            class="w-full pl-9 pr-4 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                    </div>
                    <button @click="search()"
                        class="px-5 py-2.5 bg-[#0078C8] hover:bg-[#006bb0] text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                        Track
                    </button>
                    <button x-show="searched" @click="reset()" style="display:none;"
                        class="px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-600 text-sm rounded-lg border border-gray-200 transition-colors">
                        Clear
                    </button>
                </div>
                <p class="text-[10px] text-gray-400 mt-2">Try: SH-398, SH-397, SH-396</p>
            </div>

            {{-- Default: Recent Shipments --}}
            <div x-show="!searched" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                        <h3 class="text-sm font-semibold text-gray-800">Recent Shipments</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Click a row to load its tracking timeline</p>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <template x-for="s in recent" :key="s.id">
                            <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50/50 cursor-pointer transition-colors"
                                @click="query = s.id; search()">
                                <div class="flex items-center gap-4">
                                    <div class="w-9 h-9 rounded-lg bg-[#0078C8]/10 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-ship text-[#0078C8] text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800" x-text="s.id"></p>
                                        <p class="text-xs text-gray-400 mt-0.5" x-text="s.shipper + ' · ' + s.route"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="inline-block px-2.5 py-0.5 text-[10px] font-semibold rounded-full border"
                                        :class="recentStatusClass(s.status)" x-text="s.status"></span>
                                    <i class="fa-solid fa-chevron-right text-gray-300 text-xs"></i>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Tracker Result --}}
            <div x-show="searched" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                style="display:none;">

                {{-- Not Found --}}
                <template x-if="!result">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-16 text-center">
                        <i class="fa-solid fa-circle-exclamation text-gray-200 text-4xl mb-3"></i>
                        <p class="text-sm font-medium text-gray-500">No shipment found for <span class="font-bold text-gray-700" x-text="shipment"></span></p>
                        <p class="text-xs text-gray-400 mt-1">Check the reference number and try again</p>
                    </div>
                </template>

                <template x-if="result">
                    <div class="space-y-4">

                        {{-- Tracker Card --}}
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

                            {{-- Card Header --}}
                            <div class="flex items-start justify-between px-6 py-5 border-b border-gray-100">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">
                                        Shipment Status Tracker —
                                        <span class="text-[#0078C8]" x-text="result.id"></span>
                                    </h3>
                                    <p class="text-xs text-gray-400 mt-0.5"
                                        x-text="result.shipper + ' · ' + result.route + ' · ' + result.carrier + ' · ' + result.containers">
                                    </p>
                                </div>
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full"
                                    :class="statusBadgeClass(result.overallStatus)"
                                    x-text="result.overallStatus"></span>
                            </div>

                            {{-- Step Progress Bar --}}
                            <div class="px-8 py-8">
                                <div class="relative flex items-center justify-between">

                                    {{-- Connecting line (background) --}}
                                    <div class="absolute left-0 right-0 top-5 h-0.5 bg-gray-200 z-0"></div>

                                    {{-- Connecting line (progress) --}}
                                    <div class="absolute left-0 top-5 h-0.5 bg-green-500 z-0 transition-all duration-500"
                                        :style="`width: calc(${(result.currentStep - 1)} / ${result.steps.length - 1} * 100%)`">
                                    </div>

                                    <template x-for="(step, idx) in result.steps" :key="idx">
                                        <div class="relative z-10 flex flex-col items-center gap-2">
                                            {{-- Circle --}}
                                            <template x-if="idx + 1 < result.currentStep">
                                                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center shadow-sm">
                                                    <i class="fa-solid fa-check text-white text-xs"></i>
                                                </div>
                                            </template>
                                            <template x-if="idx + 1 === result.currentStep">
                                                <div class="w-10 h-10 rounded-full bg-[#0078C8] flex items-center justify-center shadow-md ring-4 ring-[#0078C8]/20">
                                                    <span class="text-white text-xs font-bold" x-text="idx + 1"></span>
                                                </div>
                                            </template>
                                            <template x-if="idx + 1 > result.currentStep">
                                                <div class="w-10 h-10 rounded-full bg-white border-2 border-gray-200 flex items-center justify-center">
                                                    <span class="text-gray-400 text-xs font-medium" x-text="idx + 1"></span>
                                                </div>
                                            </template>

                                            {{-- Label --}}
                                            <span class="text-[11px] font-medium whitespace-nowrap"
                                                :class="idx + 1 <= result.currentStep ? (idx + 1 === result.currentStep ? 'text-[#0078C8] font-bold' : 'text-green-600') : 'text-gray-400'"
                                                x-text="step"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Tracking Events --}}
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                                <h3 class="text-sm font-semibold text-gray-800">Detailed Tracking Events</h3>
                            </div>
                            <div class="px-6 py-5 space-y-0">
                                <template x-for="(ev, idx) in result.events" :key="idx">
                                    <div class="relative flex gap-4"
                                        :class="idx < result.events.length - 1 ? 'pb-6' : ''">

                                        {{-- Vertical line --}}
                                        <template x-if="idx < result.events.length - 1">
                                            <div class="absolute left-5 top-10 bottom-0 w-px bg-gray-100"></div>
                                        </template>

                                        {{-- Icon --}}
                                        <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center border-2 z-10"
                                            :class="ev.done
                                                ? 'bg-green-50 border-green-200'
                                                : (ev.icon === 'box' ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200')">
                                            <template x-if="ev.done">
                                                <i class="fa-solid fa-check text-green-500 text-xs"></i>
                                            </template>
                                            <template x-if="!ev.done && ev.icon === 'box'">
                                                <i class="fa-solid fa-box text-blue-400 text-xs"></i>
                                            </template>
                                            <template x-if="!ev.done && ev.icon === 'bookmark'">
                                                <i class="fa-solid fa-bookmark text-gray-400 text-xs"></i>
                                            </template>
                                        </div>

                                        {{-- Content --}}
                                        <div class="pt-1.5">
                                            <p class="text-sm font-semibold text-gray-800" x-text="ev.title"></p>
                                            <p class="text-xs text-gray-400 mt-0.5" x-text="ev.meta"></p>
                                            <p class="text-[11px] text-gray-400 mt-1" x-text="ev.date"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

        </div>
    </div>
@endsection
