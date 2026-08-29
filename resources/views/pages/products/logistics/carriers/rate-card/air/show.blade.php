@extends('layouts.auth')

@section('title', 'Air Rate — {{ $air->ref_code }}')

@section('content')
    <div class="mt-4">
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

            @php
                $statusStyle = match ($air->status) {
                    'live' => 'bg-lime-100 text-lime-700',
                    'expired' => 'bg-red-50 text-red-500',
                    'draft' => 'bg-gray-100 text-gray-500',
                    default => 'bg-gray-100 text-gray-500',
                };
            @endphp

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="font-mono text-lg font-bold text-gray-900">{{ $air->ref_code }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusStyle }}">
                        {{ $air->status_label }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.carriers.rate-card.air.edit', $air) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-pen text-xs"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.carriers.rate-card.air.index') }}" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Left: Main Details --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- All-In Summary Banner --}}
                    <div class="bg-gradient-to-r from-[#0078C8] to-[#005fa3] rounded-xl p-5 text-white">
                        <p class="text-xs text-blue-200 uppercase tracking-wider mb-1">All-In Rate per KG</p>
                        <p class="text-3xl font-bold">${{ number_format($air->all_in_per_kg, 2) }}<span
                                class="text-lg font-normal text-blue-200">/kg</span></p>
                        <p class="text-sm text-blue-200 mt-1">
                            {{ $air->carrier_label }} &bull;
                            {{ $air->origin_port }} &rarr; {{ $air->destination_port }}
                            @if ($air->via_hub)
                                via {{ $air->via_hub }}
                            @endif
                            &bull; {{ $air->weight_breakpoint_label }}
                        </p>
                    </div>

                    {{-- Rate Breakdown --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Rate Breakdown (USD/KG)</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Charge Rate</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    ${{ number_format($air->charge_rate_usd_kg, 2) }}/kg
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Fuel Surcharge (FSC)</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    ${{ number_format($air->fuel_surcharge_fsc, 2) }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Security / XRAY</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    ${{ number_format($air->security_xray, 2) }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Origin Handling</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    ${{ number_format($air->origin_handling, 2) }}
                                </p>
                            </div>
                        </div>
                        @if ($air->minimum_charge_usd)
                            <div class="px-5 py-3 border-t border-gray-100 bg-amber-50">
                                <p class="text-xs text-amber-700">
                                    Minimum charge: <strong>${{ number_format($air->minimum_charge_usd) }}</strong>
                                    (applies when shipment weight × all-in rate is below minimum)
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Route --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Route Details</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Origin</p>
                                <p class="text-sm font-semibold text-gray-800 font-mono">{{ $air->origin_port }}</p>
                                <p class="text-[10px] text-gray-400">
                                    {{ \App\Models\LogisticsAirRateCard::ORIGIN_PORTS[$air->origin_port] ?? '' }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Destination</p>
                                <p class="text-sm font-semibold text-gray-800 font-mono">{{ $air->destination_port }}</p>
                                <p class="text-[10px] text-gray-400">
                                    {{ \App\Models\LogisticsAirRateCard::DESTINATION_PORTS[$air->destination_port] ?? '' }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Via Hub</p>
                                <p class="text-sm font-semibold text-gray-800 font-mono">
                                    {{ $air->via_hub ?: '—' }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Weight Breakpoint</p>
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                                    {{ $air->weight_breakpoint_label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if ($air->notes)
                        <div class="bg-white border border-black/10 rounded-xl shadow-sm p-5">
                            <p class="text-xs text-gray-400 mb-1 font-medium">Notes</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $air->notes }}</p>
                        </div>
                    @endif

                </div>

                {{-- Right: Meta --}}
                <div class="space-y-4">

                    {{-- Source & Validity --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Source & Validity</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Carrier</p>
                                <p class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full inline-block"
                                        style="background:{{ $air->carrier_color }}"></span>
                                    {{ $air->carrier_label }}
                                </p>
                            </div>
                            @if ($air->source_document)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">Source Document</p>
                                    <p class="text-sm text-gray-800">
                                        {{ \App\Models\LogisticsAirRateCard::SOURCE_DOCUMENTS[$air->source_document] ?? $air->source_document }}
                                    </p>
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Validity Period</p>
                                <p class="text-sm text-gray-800">
                                    @if ($air->rate_valid_from)
                                        {{ $air->rate_valid_from->format('d M Y') }} &ndash;
                                    @endif
                                    {{ $air->rate_valid_to->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Record Info --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Record Info</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Ref Code</p>
                                <p class="text-sm font-mono font-bold text-gray-800">{{ $air->ref_code }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Status</p>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusStyle }}">
                                    {{ $air->status_label }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Added By</p>
                                <p class="text-sm text-gray-800">{{ $air->createdBy?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $air->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Last Updated</p>
                                <p class="text-sm text-gray-800">{{ $air->updated_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Danger Zone --}}
                    <div class="bg-white border border-red-100 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-red-100 bg-red-50">
                            <h3 class="text-sm font-semibold text-red-700">Danger Zone</h3>
                        </div>
                        <div class="p-5">
                            <p class="text-xs text-gray-500 mb-3">Permanently delete this rate card.</p>
                            <form action="{{ route('logistics.carriers.rate-card.air.destroy', $air) }}" method="POST"
                                onsubmit="return confirm('Delete {{ $air->ref_code }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                                    Delete Rate Card
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection
