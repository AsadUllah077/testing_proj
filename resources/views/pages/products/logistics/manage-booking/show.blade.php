@extends('layouts.auth')

@section('title', 'Shipment #SHP-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT))

@section('content')
<div class="mt-4 px-4 pb-20">
    <div class="max-w-6xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-50 text-[#0078C8] rounded-2xl flex items-center justify-center text-2xl shadow-sm">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 leading-tight">
                        SHP-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                    </h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                        {{ $booking->service_type }}
                        &nbsp;·&nbsp;
                        Created {{ $booking->created_at->format('M d, Y h:i A') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                @if($booking->status === 'Active')
                    <span class="px-4 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full text-[10px] font-black uppercase tracking-widest">Active</span>
                @elseif($booking->status === 'Draft')
                    <span class="px-4 py-1.5 bg-amber-50 text-amber-600 border border-amber-100 rounded-full text-[10px] font-black uppercase tracking-widest">Draft</span>
                @else
                    <span class="px-4 py-1.5 bg-slate-100 text-slate-500 border border-slate-200 rounded-full text-[10px] font-black uppercase tracking-widest">{{ $booking->status }}</span>
                @endif
                <a href="{{ route('logistics.manage-booking.edit', $booking->id) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0078C8] text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:scale-105 transition-all shadow-md shadow-blue-100">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </a>
                <a href="{{ route('logistics.manage-booking.index.inbound') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-200 transition-all">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        {{-- Main Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LEFT: Route + Items + Services --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Route Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-route text-[#0078C8]"></i> Route Information
                        </h3>
                        @if($booking->loading_date)
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                <i class="fa-regular fa-calendar me-1 text-[#0078C8]"></i>
                                Loading: {{ \Carbon\Carbon::parse($booking->loading_date)->format('M d, Y') }}
                            </span>
                        @endif
                    </div>
                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8 relative">
                        {{-- Divider --}}
                        <div class="hidden md:block absolute inset-y-0 left-1/2 -translate-x-1/2 border-l-2 border-dashed border-slate-100"></div>

                        {{-- Origin --}}
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-9 h-9 rounded-full bg-blue-50 text-[#0078C8] flex items-center justify-center text-sm font-black shadow-sm">A</div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Origin · {{ $booking->origin_type }}</p>
                            </div>
                            <div class="pl-12 space-y-1">
                                <h4 class="font-black text-slate-900 text-base leading-tight">{{ $booking->origin_company ?: 'Unnamed Origin' }}</h4>
                                @if($booking->origin_street)
                                    <p class="text-xs text-slate-500">{{ $booking->origin_street }}</p>
                                @endif
                                <p class="text-xs text-slate-500">
                                    {{ implode(', ', array_filter([$booking->origin_city, $booking->origin_state])) }}
                                    {{ $booking->origin_postal }}
                                </p>
                                <p class="text-xs font-bold text-slate-700">{{ $booking->origin_country }}</p>
                            </div>
                            {{-- Pickup Contact --}}
                            @if($booking->pickup_first_name || $booking->pickup_email || $booking->pickup_phone)
                            <div class="pl-12 pt-4 border-t border-slate-100 space-y-2">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                    <i class="fa-solid fa-user me-1"></i> Pickup Contact
                                </p>
                                @if($booking->pickup_first_name || $booking->pickup_last_name)
                                    <p class="text-xs font-bold text-slate-800">{{ trim($booking->pickup_first_name . ' ' . $booking->pickup_last_name) }}</p>
                                @endif
                                @if($booking->pickup_email)
                                    <p class="text-xs text-slate-500 flex items-center gap-1">
                                        <i class="fa-regular fa-envelope text-[10px]"></i> {{ $booking->pickup_email }}
                                    </p>
                                @endif
                                @if($booking->pickup_phone)
                                    <p class="text-xs text-slate-500 flex items-center gap-1">
                                        <i class="fa-solid fa-phone text-[10px]"></i> {{ $booking->pickup_phone }}
                                    </p>
                                @endif
                                @if($booking->pickup_time)
                                    <p class="text-[10px] font-bold text-[#0078C8] mt-1">
                                        <i class="fa-regular fa-clock me-1"></i> Window: {{ $booking->pickup_time }}
                                    </p>
                                @endif
                            </div>
                            @endif
                        </div>

                        {{-- Destination --}}
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-black shadow-sm">B</div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Destination · {{ $booking->dest_type }}</p>
                            </div>
                            <div class="pl-12 space-y-1">
                                <h4 class="font-black text-slate-900 text-base leading-tight">
                                    {{ $booking->dest_company ?: ($booking->delivery_location_name ?: 'Unnamed Destination') }}
                                </h4>
                                @if($booking->dest_street)
                                    <p class="text-xs text-slate-500">{{ $booking->dest_street }}</p>
                                @endif
                                <p class="text-xs text-slate-500">
                                    {{ implode(', ', array_filter([$booking->dest_city, $booking->dest_state])) }}
                                    {{ $booking->dest_postal }}
                                </p>
                                <p class="text-xs font-bold text-slate-700">{{ $booking->dest_country }}</p>
                            </div>
                            {{-- Delivery Contact --}}
                            @if($booking->delivery_first_name || $booking->delivery_email || $booking->delivery_phone)
                            <div class="pl-12 pt-4 border-t border-slate-100 space-y-2">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                    <i class="fa-solid fa-user me-1"></i> Delivery Contact
                                </p>
                                @if($booking->delivery_first_name || $booking->delivery_last_name)
                                    <p class="text-xs font-bold text-slate-800">{{ trim($booking->delivery_first_name . ' ' . $booking->delivery_last_name) }}</p>
                                @endif
                                @if($booking->delivery_email)
                                    <p class="text-xs text-slate-500 flex items-center gap-1">
                                        <i class="fa-regular fa-envelope text-[10px]"></i> {{ $booking->delivery_email }}
                                    </p>
                                @endif
                                @if($booking->delivery_phone)
                                    <p class="text-xs text-slate-500 flex items-center gap-1">
                                        <i class="fa-solid fa-phone text-[10px]"></i> {{ $booking->delivery_phone }}
                                    </p>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Services Badges --}}
                @php
                    $services = [];
                    if ($booking->services) {
                        $decoded = json_decode($booking->services, true);
                        if (is_array($decoded)) $services = $decoded;
                    }
                @endphp
                @if(count($services) > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-6 py-4 flex flex-wrap items-center gap-3">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest me-2">Additional Services:</span>
                    @foreach($services as $service)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-[#0078C8] border border-blue-100 rounded-full text-[10px] font-black uppercase tracking-widest">
                            <i class="fa-solid fa-circle-check text-[8px]"></i> {{ $service }}
                        </span>
                    @endforeach
                </div>
                @endif

                {{-- Freight Items --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-boxes-stacked text-[#0078C8]"></i>
                            Freight Items
                            <span class="ml-1 px-2 py-0.5 bg-[#0078C8] text-white rounded-full text-[9px]">{{ $booking->items->count() }}</span>
                        </h3>
                    </div>
                    @if($booking->items->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/40 border-b border-slate-100">
                                    <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                                    <th class="px-4 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Type</th>
                                    <th class="px-4 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Qty</th>
                                    <th class="px-4 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Dimensions (in)</th>
                                    <th class="px-4 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Weight</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($booking->items as $item)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            @if($item->image_path)
                                                <img src="{{ asset('storage/' . $item->image_path) }}"
                                                     alt="{{ $item->description }}"
                                                     class="w-10 h-10 object-cover rounded-lg border border-slate-100">
                                            @else
                                                <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-300">
                                                    <i class="fa-solid fa-image text-sm"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-xs font-black text-slate-900">{{ $item->description ?: '---' }}</p>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ $item->condition }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-xs font-bold text-slate-600">{{ $item->package_type }}</td>
                                    <td class="px-4 py-4 text-sm font-black text-slate-900 text-center">{{ $item->quantity }}</td>
                                    <td class="px-4 py-4 text-xs text-slate-600 text-center whitespace-nowrap">
                                        {{ $item->length }}" × {{ $item->width }}" × {{ $item->height }}"
                                    </td>
                                    <td class="px-4 py-4 text-xs font-black text-slate-900 text-right whitespace-nowrap">
                                        {{ number_format($item->weight, 0) }} lbs
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 bg-slate-50 flex items-center justify-between">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                            Total Weight:
                            <span class="text-slate-700 ml-1">{{ number_format($booking->items->sum('weight'), 0) }} lbs</span>
                        </p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                            Freight Class: <span class="text-[#0078C8] font-black ml-1">{{ $booking->freight_class ?: '---' }}</span>
                        </p>
                    </div>
                    @else
                    <div class="px-6 py-12 text-center">
                        <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <i class="fa-solid fa-box-open text-slate-300 text-xl"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-400">No items recorded</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Overview + Carrier + References --}}
            <div class="space-y-6">

                {{-- Shipment Overview --}}
                <div class="bg-slate-900 rounded-2xl p-6 text-white shadow-xl shadow-slate-200/60">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 mb-5">Shipment Overview</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-400">Service</span>
                            <span class="text-xs font-black bg-white/10 px-3 py-1 rounded-lg">{{ $booking->service_type }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-400">Status</span>
                            @if($booking->status === 'Active')
                                <span class="text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-3 py-1 rounded-full">Active</span>
                            @else
                                <span class="text-[10px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30 px-3 py-1 rounded-full">{{ $booking->status }}</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-400">Freight Class</span>
                            <span class="text-xs font-black">{{ $booking->freight_class ?: 'N/A' }}</span>
                        </div>
                        @if($booking->loading_date)
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-400">Loading Date</span>
                            <span class="text-xs font-black">{{ \Carbon\Carbon::parse($booking->loading_date)->format('M d, Y') }}</span>
                        </div>
                        @endif
                        @if($booking->carrier_rate > 0)
                        <div class="flex items-center justify-between pt-4 border-t border-white/10">
                            <span class="text-xs text-slate-400">Carrier Rate</span>
                            <span class="text-xl font-black tracking-tight">${{ number_format($booking->carrier_rate, 2) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Carrier Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-building text-[#0078C8]"></i> Carrier
                        </h3>
                    </div>
                    <div class="p-6">
                        @if($booking->carrier)
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-[#0078C8]">
                                    <i class="fa-solid fa-truck text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-900">{{ $booking->carrier->legal_carrier_name }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $booking->carrier->logistics_mode }}</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-xs text-slate-500">
                                @if($booking->carrier->scac_iata_code)
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold">SCAC/IATA</span>
                                        <span class="font-black text-slate-700">{{ $booking->carrier->scac_iata_code }}</span>
                                    </div>
                                @endif
                                @if($booking->carrier->dot_number)
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold">DOT #</span>
                                        <span class="font-black text-slate-700">{{ $booking->carrier->dot_number }}</span>
                                    </div>
                                @endif
                                @if($booking->carrier->mc_authority)
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold">MC Authority</span>
                                        <span class="font-black text-slate-700">{{ $booking->carrier->mc_authority }}</span>
                                    </div>
                                @endif
                                @if($booking->carrier->safety_rating)
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold">Safety Rating</span>
                                        <span class="font-black text-slate-700">{{ $booking->carrier->safety_rating }}</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="w-10 h-10 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-2">
                                    <i class="fa-solid fa-truck text-slate-300"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-400">No carrier assigned</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- References --}}
                @if($booking->reference_po || $booking->reference_billing)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest">References</h3>
                    @if($booking->reference_po)
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-file-contract text-[#0078C8] mt-0.5"></i>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PO Reference</p>
                            <p class="text-xs font-black text-slate-900 mt-0.5">{{ $booking->reference_po }}</p>
                        </div>
                    </div>
                    @endif
                    @if($booking->reference_billing)
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-file-invoice text-emerald-500 mt-0.5"></i>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Billing Reference</p>
                            <p class="text-xs font-black text-slate-900 mt-0.5">{{ $booking->reference_billing }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Activity Log --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-5">Activity Log</h3>
                    <div class="relative space-y-6 before:absolute before:left-[9px] before:top-2 before:bottom-2 before:w-px before:bg-slate-100">
                        <div class="relative pl-8">
                            <div class="absolute left-0 top-1 w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center">
                                <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                            </div>
                            <p class="text-xs font-black text-slate-900">Booking Created</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                                {{ $booking->created_at->format('M d, Y · h:i A') }}
                            </p>
                        </div>
                        @if($booking->updated_at && $booking->updated_at->ne($booking->created_at))
                        <div class="relative pl-8">
                            <div class="absolute left-0 top-1 w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            </div>
                            <p class="text-xs font-black text-slate-900">Last Updated</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                                {{ $booking->updated_at->format('M d, Y · h:i A') }}
                            </p>
                        </div>
                        @endif
                        <div class="relative pl-8 opacity-40">
                            <div class="absolute left-0 top-1 w-5 h-5 bg-slate-100 rounded-full flex items-center justify-center">
                                <div class="w-2 h-2 bg-slate-300 rounded-full"></div>
                            </div>
                            <p class="text-xs font-black text-slate-900">Carrier Dispatched</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Awaiting Update</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Event History --}}
        @if($booking->events->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">
                    <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                </div>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest">Pickup & Delivery History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Event</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Scheduled Date</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Actual Date</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Rescheduled To</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Reason</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">By</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Recorded At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($booking->events as $event)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                @php
                                    $badgeMap = [
                                        'pickup_approved'   => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'fa-circle-check',  'Pickup Approved'],
                                        'pickup_rejected'   => ['bg-red-50 text-red-600 border-red-100',             'fa-circle-xmark',  'Pickup Reviewed'],
                                        'delivery_approved' => ['bg-blue-50 text-blue-700 border-blue-100',           'fa-box-open',      'Delivery Approved'],
                                        'delivery_rejected' => ['bg-orange-50 text-orange-600 border-orange-100',     'fa-circle-xmark',  'Delivery Reviewed'],
                                    ];
                                    [$cls, $icon, $label] = $badgeMap[$event->event_type] ?? ['bg-slate-100 text-slate-500 border-slate-200', 'fa-circle', $event->event_type];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border {{ $cls }}">
                                    <i class="fa-solid {{ $icon }}"></i>
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs font-bold text-slate-700">
                                {{ $event->scheduled_date ? $event->scheduled_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-4 py-4 text-xs font-bold text-slate-700">
                                {{ $event->actual_date ? $event->actual_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-700">
                                @if($event->new_date)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-100 rounded-full text-[10px] font-bold">
                                        <i class="fa-solid fa-calendar-days text-[9px]"></i>
                                        {{ $event->new_date->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-500 max-w-xs">
                                {{ $event->reason ?: '—' }}
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-500">
                                {{ $event->creator?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-400">
                                {{ $event->created_at->format('M d, Y · h:i A') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
