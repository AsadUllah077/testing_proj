@extends('layouts.auth')

@section('title', $contract->contract_id . ' — SLA Contract')

@section('content')
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

        <div class="mx-auto px-3 space-y-4">

            {{-- Header --}}
            <div
                class="bg-white rounded-lg border border-black/10 px-5 py-4 flex flex-wrap gap-3 justify-between items-start">
                <div>
                    <div class="flex items-center gap-3 flex-wrap mb-1">
                        <h1 class="text-gray-900 text-xl font-semibold font-mono">{{ $contract->contract_id }}</h1>
                        @php
                            $statusClass =
                                [
                                    'active' => 'bg-lime-100 text-lime-700',
                                    'breach' => 'bg-red-50 text-red-600',
                                    'draft' => 'bg-gray-100 text-gray-500',
                                    'expired' => 'bg-slate-100 text-slate-500',
                                    'terminated' => 'bg-orange-50 text-orange-600',
                                ][$contract->status] ?? 'bg-gray-100 text-gray-500';

                            $perfLabel =
                                [
                                    'met' => 'Met',
                                    'marginal' => 'Marginal',
                                    'breach' => 'Breach',
                                    'pending' => 'Pending',
                                ][$contract->performance_status] ?? '';
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusClass }}">
                            {{ ucfirst($contract->status) }}
                            @if ($perfLabel)
                                &middot; {{ $perfLabel }}
                            @endif
                        </span>
                    </div>
                    <p class="text-xs text-gray-400">
                        {{ $contract->carrier->name ?? '—' }}
                        &middot; {{ $contract->transport_mode_label }}
                        &middot; {{ $contract->contract_term_label }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.carriers.sla-contract.edit', $contract) }}"
                        class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-pen text-xs"></i> Edit Contract
                    </a>
                    <x-common.back-button url="{{ route('logistics.carriers.sla-contract.index') }}" />
                </div>
            </div>

            {{-- Expiry Alerts --}}
            @if ($contract->isExpired())
                <div
                    class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-xmark"></i>
                    This contract expired on {{ $contract->contract_expiry_date->format('M j, Y') }}.
                    Consider <a href="{{ route('logistics.carriers.sla-contract.create') }}" class="underline">creating a
                        renewal</a>.
                </div>
            @elseif($contract->isExpiringSoon())
                <div
                    class="px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    This contract expires on <strong
                        class="mx-1">{{ $contract->contract_expiry_date->format('M j, Y') }}</strong>
                    ({{ now()->diffInDays($contract->contract_expiry_date) }} days remaining) — renewal required soon.
                </div>
            @endif

            {{-- KPI Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                    $kpis = [
                        [
                            'label' => 'Min OTD Guarantee',
                            'value' => $contract->min_otd_guarantee . '%',
                            'icon' => 'fa-gauge-high',
                            'color' => 'text-blue-600',
                            'bg' => 'bg-blue-50',
                        ],
                        [
                            'label' => 'Max Claim Rate',
                            'value' => $contract->max_claim_rate . '%',
                            'icon' => 'fa-file-circle-xmark',
                            'color' => 'text-red-500',
                            'bg' => 'bg-red-50',
                        ],
                        [
                            'label' => 'Volume Commitment',
                            'value' => $contract->volume_commitment ?? '—',
                            'icon' => 'fa-boxes-stacked',
                            'color' => 'text-emerald-600',
                            'bg' => 'bg-emerald-50',
                        ],
                        [
                            'label' => 'Rate Lock',
                            'value' => $contract->rate_lock_label,
                            'icon' => 'fa-lock',
                            'color' => 'text-violet-600',
                            'bg' => 'bg-violet-50',
                        ],
                    ];
                @endphp
                @foreach ($kpis as $kpi)
                    <div class="bg-white rounded-lg border border-black/10 px-5 py-4">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-7 h-7 rounded-md {{ $kpi['bg'] }} flex items-center justify-center">
                                <i class="fa-solid {{ $kpi['icon'] }} {{ $kpi['color'] }} text-xs"></i>
                            </div>
                            <span class="text-xs text-gray-400">{{ $kpi['label'] }}</span>
                        </div>
                        <p class="text-lg font-bold text-gray-900">{{ $kpi['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Detail Panels --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Contract Details --}}
                <div class="bg-white rounded-lg border border-black/10 p-5">
                    <h2
                        class="text-sm font-semibold text-gray-700 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-file-contract text-gray-400 text-xs"></i> Contract Details
                    </h2>
                    <dl class="space-y-3">
                        @php
                            $details = [
                                'Carrier' => $contract->carrier->name ?? '—',
                                'Transport Mode' => $contract->transport_mode_label,
                                'Contract Term' => $contract->contract_term_label,
                                'Start Date' => $contract->contract_start_date?->format('M j, Y') ?? '—',
                                'Expiry Date' => $contract->contract_expiry_date?->format('M j, Y') ?? '—',
                                'Review Frequency' => $contract->review_frequency_label,
                            ];
                        @endphp
                        @foreach ($details as $label => $value)
                            <div class="flex justify-between items-center text-xs">
                                <dt class="text-gray-500 font-medium">{{ $label }}</dt>
                                <dd class="text-gray-900 font-semibold text-right">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- SLA & Penalty Terms --}}
                <div class="bg-white rounded-lg border border-black/10 p-5">
                    <h2
                        class="text-sm font-semibold text-gray-700 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-gray-400 text-xs"></i> SLA & Penalty Terms
                    </h2>
                    <dl class="space-y-3">
                        @php
                            $terms = [
                                'Min OTD Guarantee' => $contract->min_otd_guarantee . '%',
                                'Max Claim Rate' => $contract->max_claim_rate . '%',
                                'Penalty Clause' => $contract->penalty_clause_label,
                                'Penalty Rate' => $contract->penalty_rate_amount ?? '—',
                                'Volume Commitment' => $contract->volume_commitment ?? '—',
                                'Rate Lock Period' => $contract->rate_lock_label,
                            ];
                        @endphp
                        @foreach ($terms as $label => $value)
                            <div class="flex justify-between items-center text-xs">
                                <dt class="text-gray-500 font-medium">{{ $label }}</dt>
                                <dd class="text-gray-900 font-semibold text-right">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

            </div>

            {{-- Special Conditions --}}
            @if ($contract->special_conditions)
                <div class="bg-white rounded-lg border border-black/10 p-5">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-list text-gray-400 text-xs"></i> Special Conditions
                    </h2>
                    <p
                        class="text-xs text-gray-700 leading-relaxed whitespace-pre-wrap bg-gray-50 rounded-lg p-4 border border-gray-100">
                        {{ $contract->special_conditions }}</p>
                </div>
            @endif

            {{-- Carrier Info (if available) --}}
            @if ($contract->carrier)
                <div class="bg-white rounded-lg border border-black/10 p-5">
                    <h2
                        class="text-sm font-semibold text-gray-700 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-building text-gray-400 text-xs"></i> Carrier Information
                    </h2>
                    <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @php
                            $carrierFields = [
                                'Name' => $contract->carrier->carrier_company_name,
                                'Code' => strtoupper($contract->carrier->ntn_scac_code),
                                'Type' =>
                                    \App\Models\LogisticsV2Carriers::TRANSPORT_MODES[
                                        $contract->carrier->transport_modes
                                    ],
                            ];
                        @endphp
                        @foreach ($carrierFields as $label => $value)
                            <div class="text-xs">
                                <dt class="text-gray-400 mb-0.5">{{ $label }}</dt>
                                <dd class="text-gray-900 font-semibold">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Meta Footer --}}
            <div class="text-xs text-gray-400 flex flex-wrap items-center gap-4 pb-6">
                <span>Created {{ $contract->created_at->format('M j, Y H:i') }}</span>
                <span>&middot;</span>
                <span>Last updated {{ $contract->updated_at->diffForHumans() }}</span>
            </div>

        </div>
    </div>
@endsection
