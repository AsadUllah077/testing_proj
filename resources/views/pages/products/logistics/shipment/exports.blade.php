@extends('layouts.auth')

@section('title', 'Export Shipments')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">Export Shipments</h1>
                    <p class="text-xs text-gray-400 mt-0.5">All outbound shipments dispatched from your locations</p>
                </div>
                <x-common.export-dropdown name="shipment" />
            </div>

            {{-- Tabs --}}
            <div class="flex items-center gap-1 mb-4">
                <a href="{{ route('logistics.shipments.index') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors text-gray-500 hover:text-gray-800 hover:bg-white">
                    All Shipments
                </a>
                <a href="{{ route('logistics.shipments.imports') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors text-gray-500 hover:text-gray-800 hover:bg-white">
                    Imports
                </a>
                <a href="{{ route('logistics.shipments.exports') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-gray-900 text-white">
                    Exports
                </a>
                <a href="{{ route('logistics.shipments.delayed') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors text-gray-500 hover:text-gray-800 hover:bg-white flex items-center gap-1.5">
                    Delayed
                </a>
            </div>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                    <p class="text-3xl font-black text-green-600">{{ $stats['total'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Total Exports</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                    <p class="text-3xl font-black text-purple-600">{{ $stats['in_transit'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">In-Transit</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                    <p class="text-3xl font-black text-[#0078C8]">{{ $stats['confirmed'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Confirmed</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                    <p class="text-3xl font-black text-amber-500">{{ $stats['pending'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Pending Confirm</p>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('logistics.shipments.exports') }}"
                class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div class="relative md:col-span-2">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}"
                            placeholder="Search shipment #, carrier, shipper, port..."
                            class="w-full pl-8 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                    </div>
                    <select name="status"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        <option value="">All Statuses</option>
                        <option value="Draft"           {{ request('status') === 'Draft'           ? 'selected' : '' }}>Draft</option>
                        <option value="Pending Confirm" {{ request('status') === 'Pending Confirm' ? 'selected' : '' }}>Pending Confirm</option>
                        <option value="Confirmed"       {{ request('status') === 'Confirmed'       ? 'selected' : '' }}>Confirmed</option>
                        <option value="In-Transit"      {{ request('status') === 'In-Transit'      ? 'selected' : '' }}>In-Transit</option>
                        <option value="Doc Missing"     {{ request('status') === 'Doc Missing'     ? 'selected' : '' }}>Doc Missing</option>
                    </select>
                    <select name="freight_mode"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        <option value="">All Modes</option>
                        <option value="fcl_sea"    {{ request('freight_mode') === 'fcl_sea'    ? 'selected' : '' }}>FCL Sea</option>
                        <option value="lcl_sea"    {{ request('freight_mode') === 'lcl_sea'    ? 'selected' : '' }}>LCL Sea</option>
                        <option value="air"        {{ request('freight_mode') === 'air'        ? 'selected' : '' }}>Air Freight</option>
                        <option value="road_truck" {{ request('freight_mode') === 'road_truck' ? 'selected' : '' }}>Road / Truck</option>
                        <option value="multimodal" {{ request('freight_mode') === 'multimodal' ? 'selected' : '' }}>Multimodal</option>
                    </select>
                    <div class="flex gap-2">
                        <input type="date" name="etd_from" value="{{ request('etd_from') }}"
                            title="ETD From"
                            class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        <input type="date" name="etd_to" value="{{ request('etd_to') }}"
                            title="ETD To"
                            class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8] hover:bg-[#006bb0] text-white text-xs font-medium rounded-lg transition-colors">
                        Apply Filters
                    </button>
                    @if(request()->hasAny(['q','status','freight_mode','etd_from','etd_to']))
                        <a href="{{ route('logistics.shipments.exports') }}"
                            class="px-4 py-2 bg-white border border-gray-200 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @include('pages.products.logistics.shipment.partials.table', compact('shipments'))
            </div>

        </div>
    </div>
@endsection
