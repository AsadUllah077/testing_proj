@extends('layouts.auth')

@section('title', 'Rate Card — {{ $ocean->ref_code }}')

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
                $statusStyle = match ($ocean->status) {
                    'live' => 'bg-lime-100 text-lime-700',
                    'expired' => 'bg-red-50 text-red-500',
                    'draft' => 'bg-gray-100 text-gray-500',
                    default => 'bg-gray-100 text-gray-500',
                };
                $typeStyle = match ($ocean->rate_type) {
                    'nac' => 'bg-sky-50 text-[#0078C8]',
                    'spot' => 'bg-amber-100 text-amber-700',
                    'gri' => 'bg-purple-100 text-purple-700',
                    'pss' => 'bg-orange-100 text-orange-700',
                    default => 'bg-gray-100 text-gray-600',
                };
            @endphp

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="font-mono text-lg font-bold text-gray-900">{{ $ocean->ref_code }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $typeStyle }}">
                        {{ strtoupper($ocean->rate_type) }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusStyle }}">
                        {{ $ocean->status_label }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.carriers.rate-card.ocean.edit', $ocean) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-pen text-xs"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.carriers.rate-card.ocean.index') }}" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Left: Main Details --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- All-In Summary Banner --}}
                    <div class="bg-gradient-to-r from-[#0078C8] to-[#005fa3] rounded-xl p-5 text-white">
                        <p class="text-xs text-blue-200 uppercase tracking-wider mb-1">All-In Ocean Rate</p>
                        <p class="text-3xl font-bold">${{ number_format($ocean->all_in_total) }}</p>
                        <p class="text-sm text-blue-200 mt-1">
                            {{ $ocean->carrier_label }} &bull;
                            {{ $ocean->origin_port }} &rarr; {{ $ocean->destination_port }} &bull;
                            {{ $ocean->equipment_label }}
                        </p>
                    </div>

                    {{-- Rate Breakdown --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Rate Breakdown (USD)</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                            @php
                                $breakdown = [
                                    ['Ocean Freight', '$' . number_format($ocean->ocean_freight), false],
                                    ['BAF', '$' . number_format($ocean->baf), false],
                                    ['CAF', '$' . number_format($ocean->caf), false],
                                    ['EIS / ISPS / Other', '$' . number_format($ocean->eis_isps_other), false],
                                    ['THC Origin', '$' . number_format($ocean->thc_origin), false],
                                    ['THC Destination', '$' . number_format($ocean->thc_destination), false],
                                ];
                            @endphp
                            @foreach ($breakdown as [$label, $value, $highlight])
                                <div class="px-5 py-4">
                                    <p class="text-xs text-gray-400 mb-0.5">{{ $label }}</p>
                                    <p class="text-sm font-semibold {{ $highlight ? 'text-[#0078C8]' : 'text-gray-800' }}">
                                        {{ $value }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Route & Equipment --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Route & Equipment</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 divide-gray-100 divide-y md:divide-y-0 md:divide-x">
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Origin Port</p>
                                <p class="text-sm font-semibold text-gray-800 font-mono">{{ $ocean->origin_port }}</p>
                                <p class="text-[10px] text-gray-400">
                                    {{ \App\Models\LogisticsOceanRateCard::ORIGIN_PORTS[$ocean->origin_port] ?? '' }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Destination Port</p>
                                <p class="text-sm font-semibold text-gray-800 font-mono">{{ $ocean->destination_port }}</p>
                                <p class="text-[10px] text-gray-400">
                                    {{ \App\Models\LogisticsOceanRateCard::DESTINATION_PORTS[$ocean->destination_port] ?? '' }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Equipment</p>
                                <p class="text-sm font-semibold text-gray-800">{{ $ocean->equipment_label }}</p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Transit Time</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $ocean->transit_time_days ? $ocean->transit_time_days . ' days' : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Demurrage / Detention --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Demurrage & Detention</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 divide-gray-100 divide-y md:divide-y-0 md:divide-x">
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Free Days (Demurrage)</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $ocean->free_days_demurrage ? $ocean->free_days_demurrage . ' days' : '—' }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Demurrage Rate/Day</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $ocean->demurrage_rate_after_free ? '$' . number_format($ocean->demurrage_rate_after_free) : '—' }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Free Days (Detention)</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $ocean->free_days_detention ? $ocean->free_days_detention . ' days' : '—' }}
                                </p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Detention Rate/Day</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $ocean->detention_rate_after_free ? '$' . number_format($ocean->detention_rate_after_free) : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Remarks --}}
                    @if ($ocean->remarks_gri_pss_notes)
                        <div class="bg-white border border-black/10 rounded-xl shadow-sm p-5">
                            <p class="text-xs text-gray-400 mb-1 font-medium">Remarks / GRI / PSS Notes</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $ocean->remarks_gri_pss_notes }}</p>
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
                                        style="background:{{ $ocean->carrier_color }}"></span>
                                    {{ $ocean->carrier_label }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Rate Type</p>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $typeStyle }}">
                                    {{ strtoupper($ocean->rate_type) }}
                                </span>
                            </div>
                            @if ($ocean->nac_quote_reference)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">NAC / Quote Ref</p>
                                    <p class="text-sm font-mono text-gray-800">{{ $ocean->nac_quote_reference }}</p>
                                </div>
                            @endif
                            @if ($ocean->source_document)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">Source Document</p>
                                    <p class="text-sm text-gray-800">
                                        {{ \App\Models\LogisticsOceanRateCard::SOURCE_DOCUMENTS[$ocean->source_document] ?? $ocean->source_document }}
                                    </p>
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Validity Period</p>
                                <p class="text-sm text-gray-800">
                                    {{ $ocean->valid_from->format('d M Y') }} &ndash;
                                    {{ $ocean->valid_to->format('d M Y') }}
                                </p>
                            </div>
                            @if ($ocean->agreed_volume_teu_month)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">Agreed Volume</p>
                                    <p class="text-sm text-gray-800">{{ $ocean->agreed_volume_teu_month }} TEU/month</p>
                                </div>
                            @endif
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
                                <p class="text-sm font-mono font-bold text-gray-800">{{ $ocean->ref_code }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Added By</p>
                                <p class="text-sm text-gray-800">{{ $ocean->createdBy?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $ocean->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Last Updated</p>
                                <p class="text-sm text-gray-800">{{ $ocean->updated_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Status</p>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusStyle }}">
                                    {{ $ocean->status_label }}
                                </span>
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
                            <form action="{{ route('logistics.carriers.rate-card.ocean.destroy', $ocean) }}"
                                method="POST"
                                onsubmit="return confirm('Delete rate card {{ $ocean->ref_code }}? This cannot be undone.')">
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
