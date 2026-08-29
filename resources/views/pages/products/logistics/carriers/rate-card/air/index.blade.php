@extends('layouts.auth')

@section('title', 'Logistics Carriers Rate Card - Air')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">

        {{-- Module Tabs --}}
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.carriers') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Rates</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $rates->total() }} air rate slabs found</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-common.export-dropdown name="lc" />
                    <a href="{{ route('logistics.carriers.rate-card.air.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i> New Air Rate Card
                    </a>
                </div>
            </div>

            {{-- Sub Tabs --}}
            <div class="flex items-center space-x-3 flex-wrap mb-3">
                @php
                    $subTabs = [
                        ['route' => 'logistics.carriers.rate-card.ocean.index', 'label' => 'Ocean NAC / Spot'],
                        ['route' => 'logistics.carriers.rate-card.air.index', 'label' => 'Air per-KG Slabs'],
                        ['route' => 'logistics.carriers.rate-card.inland.index', 'label' => 'Inland MOU Zone Matrix'],
                        ['route' => 'logistics.carriers.rate-card.rate-calculator.index', 'label' => 'Rate Calculator'],
                    ];
                @endphp
                @foreach ($subTabs as $tab)
                    <button onclick="window.location.href='{{ route($tab['route']) }}'"
                        class="{{ Request::routeIs($tab['route']) ? 'bg-[#0078C8] border-[#0078C8] text-white' : 'bg-[#eee5e5] border-[#eee5e5] text-black' }} py-2 px-3 rounded-[3px] border text-center uppercase text-sm font-normal">
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            {{-- Filters --}}
            <div class="bg-white border border-black/10 rounded-xl shadow-sm p-4 mb-4">
                <form method="GET" action="{{ route('logistics.carriers.rate-card.air.index') }}"
                    class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[160px]">
                        <label class="text-xs text-gray-500 font-medium block mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Ref, port, notes..."
                            class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8]" />
                    </div>
                    <div class="min-w-[150px]">
                        <label class="text-xs text-gray-500 font-medium block mb-1">Carrier</label>
                        <select name="carrier"
                            class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8]">
                            <option value="">All Carriers</option>
                            @foreach (\App\Models\LogisticsAirRateCard::CARRIERS as $key => $label)
                                <option value="{{ $key }}" @selected(request('carrier') === $key)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[140px]">
                        <label class="text-xs text-gray-500 font-medium block mb-1">Weight Breakpoint</label>
                        <select name="weight_breakpoint"
                            class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8]">
                            <option value="">All Breakpoints</option>
                            @foreach (\App\Models\LogisticsAirRateCard::WEIGHT_BREAKPOINTS as $key => $label)
                                <option value="{{ $key }}" @selected(request('weight_breakpoint') === $key)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[120px]">
                        <label class="text-xs text-gray-500 font-medium block mb-1">Status</label>
                        <select name="status"
                            class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8]">
                            <option value="">All Statuses</option>
                            @foreach (\App\Models\LogisticsAirRateCard::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'carrier', 'weight_breakpoint', 'status']))
                        <a href="{{ route('logistics.carriers.rate-card.air.index') }}"
                            class="px-4 py-2 bg-gray-100 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Ref</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Carrier</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Route</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Weight Breakpoint</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Charge Rate</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Fuel Surcharge</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Security (Xray)</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Handling Origin</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    All-In / KG</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Min Charge</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Valid</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rates as $rate)
                                @php
                                    $isExpired = $rate->status === 'expired' || $rate->rate_valid_to->isPast();
                                    $rowClass = $isExpired ? 'opacity-60' : '';

                                    $statusStyle = match ($rate->status) {
                                        'live' => 'bg-lime-100 text-lime-700',
                                        'expired' => 'bg-red-50 text-red-500',
                                        'draft' => 'bg-gray-100 text-gray-500',
                                        default => 'bg-gray-100 text-gray-500',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors {{ $rowClass }}">
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900 font-mono">
                                        {{ $rate->ref_code }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full shrink-0"
                                                style="background:{{ $rate->carrier_color }}"></span>
                                            {{ $rate->carrierInfo->carrier_company_name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700 font-mono">
                                        {{ $rate->origin_port }} &rarr; {{ $rate->destination_port }}
                                        @if ($rate->via_hub)
                                            <span class="text-gray-400">via {{ $rate->via_hub }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-medium">
                                            {{ $rate->weight_breakpoint_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900">
                                        ${{ number_format($rate->charge_rate_usd_kg, 2) }}/kg
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        ${{ number_format($rate->fuel_surcharge_fsc, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        ${{ number_format($rate->security_xray, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        ${{ number_format($rate->origin_handling, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-[#0078C8]">
                                        ${{ number_format($rate->all_in_per_kg, 2) }}/kg
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        {{ $rate->minimum_charge_usd ? '$' . number_format($rate->minimum_charge_usd) . ' min' : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        @if ($rate->rate_valid_from)
                                            {{ $rate->rate_valid_from->format('M d') }} &ndash;
                                        @endif
                                        {{ $rate->rate_valid_to->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <span class="px-2 py-0.5 rounded-full font-semibold {{ $statusStyle }}">
                                            {{ $rate->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-1.5">
                                            <a href="{{ route('logistics.carriers.rate-card.air.show', $rate) }}"
                                                class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md transition-colors">
                                                View
                                            </a>
                                            <a href="{{ route('logistics.carriers.rate-card.air.edit', $rate) }}"
                                                class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md transition-colors">
                                                Edit
                                            </a>
                                            <form action="{{ route('logistics.carriers.rate-card.air.destroy', $rate) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete rate card {{ $rate->ref_code }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="px-2.5 py-1 text-[11px] font-medium bg-red-50 hover:bg-red-100 text-red-600 border border-red-100 rounded-md transition-colors">
                                                    Del
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="px-4 py-12 text-center text-gray-400 text-sm">
                                        No air rate cards found.
                                        <a href="{{ route('logistics.carriers.rate-card.air.create') }}"
                                            class="text-[#0078C8] hover:underline">Add the first one.</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($rates->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $rates->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
