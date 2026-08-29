@extends('layouts.auth')

@section('title', 'Carrier Details')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    @php
                        $initials = collect(
                            explode(' ', $carrier->trading_brand_name ?: $carrier->carrier_company_name),
                        )
                            ->take(3)
                            ->map(fn($w) => strtoupper($w[0] ?? ''))
                            ->implode('');
                        $colors = [
                            '#0091CD',
                            '#FF6B2B',
                            '#E30613',
                            '#005C9F',
                            '#C60C30',
                            '#007DC6',
                            '#4B286D',
                            '#0B8043',
                            '#6B21A8',
                            '#0F766E',
                        ];
                        $color = $colors[crc32($carrier->carrier_company_name) % count($colors)];
                        $statusStyles = [
                            'draft' => 'bg-gray-100 text-gray-600',
                            'pending_approval' => 'bg-amber-100 text-amber-700',
                            'approved' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0"
                            style="background:{{ $color }}">{{ $initials }}</div>
                        <div>
                            <h1 class="text-gray-900 text-xl font-semibold">
                                {{ $carrier->trading_brand_name ?: $carrier->carrier_company_name }}
                            </h1>
                            @if ($carrier->trading_brand_name)
                                <p class="text-gray-400 text-xs">{{ $carrier->carrier_company_name }}</p>
                            @endif
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusStyles[$carrier->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ \App\Models\LogisticsV2Carriers::STATUSES[$carrier->status] ?? $carrier->status }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Approve / Reject (only for pending) --}}
                    @if ($carrier->status === 'pending_approval')
                        <form action="{{ route('logistics.carriers.v2.approve', $carrier) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-xs"></i> Approve
                            </button>
                        </form>
                        <form action="{{ route('logistics.carriers.v2.reject', $carrier) }}" method="POST"
                            onsubmit="return confirm('Reject this carrier?')">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="px-4 py-2 bg-red-100 text-red-600 text-xs font-medium rounded-lg hover:bg-red-200 transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-times text-xs"></i> Reject
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('logistics.carriers.v2.edit', $carrier) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-pen text-xs"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.carriers.v2.index') }}" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Left: Details --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Company Information --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Company Information</h3>
                        </div>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                            @php
                                $modeIcons = [
                                    'ocean_liner' => '🌊',
                                    'air_freight' => '✈',
                                    'inland_trucking' => '🚛',
                                    'rail_freight' => '🚂',
                                    'multimodal' => '🔀',
                                ];
                                $companyFields = [
                                    [
                                        'Transport Mode',
                                        ($modeIcons[$carrier->transport_modes] ?? '') .
                                        ' ' .
                                        (\App\Models\LogisticsV2Carriers::TRANSPORT_MODES[$carrier->transport_modes] ??
                                            '—'),
                                    ],
                                    ['Country of Incorporation', $carrier->country_of_incorporation ?: '—'],
                                    ['Pakistan Liaison Office', $carrier->pakistan_liaison_office ?: '—'],
                                    ['NTN / SCAC Code', $carrier->ntn_scac_code ?: '—'],
                                    ['Contact Person', $carrier->contact_person ?: '—'],
                                    ['Contact Email', $carrier->contact_email ?: '—'],
                                    ['Phone (Pakistan)', $carrier->phone_pakistan_office ?: '—'],
                                    ['Website', $carrier->website ?: '—'],
                                ];
                            @endphp
                            @foreach ($companyFields as [$label, $value])
                                <div class="px-5 py-3">
                                    <p class="text-xs text-gray-400 mb-0.5">{{ $label }}</p>
                                    <p class="text-sm text-gray-800 font-medium">
                                        @if ($label === 'Website' && $carrier->website)
                                            <a href="{{ $carrier->website }}" target="_blank"
                                                class="text-[#0078C8] hover:underline">{{ $carrier->website }}</a>
                                        @elseif ($label === 'Contact Email')
                                            <a href="mailto:{{ $carrier->contact_email }}"
                                                class="text-[#0078C8] hover:underline">{{ $carrier->contact_email }}</a>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Service Coverage --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Service Coverage</h3>
                        </div>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                            @php
                                $serviceFields = [
                                    ['Key Origin Ports / Airports', $carrier->key_origin_ports_airports ?: '—'],
                                    ['Key Destination Markets', $carrier->key_destination_markets ?: '—'],
                                    [
                                        'Services Offered',
                                        \App\Models\LogisticsV2Carriers::SERVICES_OFFERED[$carrier->services_offered] ??
                                        '—',
                                    ],
                                    [
                                        'Tracking Portal / API',
                                        \App\Models\LogisticsV2Carriers::TRACKING_PORTAL_OPTIONS[
                                            $carrier->tracking_portal_api
                                        ] ?? '—',
                                    ],
                                ];
                            @endphp
                            @foreach ($serviceFields as [$label, $value])
                                <div class="px-5 py-3">
                                    <p class="text-xs text-gray-400 mb-0.5">{{ $label }}</p>
                                    <p class="text-sm text-gray-800 font-medium">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Compliance & Documents --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Compliance & Documents</h3>
                        </div>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                            @php
                                $complianceFields = [
                                    [
                                        'FIATA / IATA Membership',
                                        \App\Models\LogisticsV2Carriers::FIATA_IATA_OPTIONS[
                                            $carrier->fiata_iata_membership
                                        ] ?? '—',
                                    ],
                                    [
                                        'Insurance (Marine Liability)',
                                        \App\Models\LogisticsV2Carriers::INSURANCE_OPTIONS[
                                            $carrier->insurance_marine_liability
                                        ] ?? '—',
                                    ],
                                    ['AEO / C-TPAT / ISO', $carrier->aeo_ctpat_iso_certification ?: '—'],
                                    [
                                        'Payment Terms',
                                        \App\Models\LogisticsV2Carriers::PAYMENT_TERMS[$carrier->payment_terms] ?? '—',
                                    ],
                                    [
                                        'Credit Limit (USD)',
                                        $carrier->credit_limit_usd
                                            ? 'USD ' . number_format($carrier->credit_limit_usd)
                                            : '—',
                                    ],
                                    ['Account Manager', $carrier->account_manager_internal ?: '—'],
                                ];
                            @endphp
                            @foreach ($complianceFields as [$label, $value])
                                <div class="px-5 py-3">
                                    <p class="text-xs text-gray-400 mb-0.5">{{ $label }}</p>
                                    <p class="text-sm text-gray-800 font-medium">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if ($carrier->notes_onboarding_remarks)
                            <div class="px-5 py-3 border-t border-gray-100">
                                <p class="text-xs text-gray-400 mb-1">Notes / Onboarding Remarks</p>
                                <p class="text-sm text-gray-700 whitespace-pre-line">
                                    {{ $carrier->notes_onboarding_remarks }}</p>
                            </div>
                        @endif

                        @if ($carrier->liability_insurance_path)
                            <div class="px-5 py-3 border-t border-gray-100">
                                <p class="text-xs text-gray-400 mb-2">Liability Insurance Document</p>
                                <a href="{{ $carrier->liability_insurance_url }}" target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                    </svg>
                                    View Document
                                </a>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Right: Meta --}}
                <div class="space-y-4">

                    {{-- Status Card --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Record Info</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Status</p>
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusStyles[$carrier->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ \App\Models\LogisticsV2Carriers::STATUSES[$carrier->status] ?? $carrier->status }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Added By</p>
                                <p class="text-sm text-gray-800 font-medium">{{ $carrier->createdBy?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $carrier->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            @if ($carrier->approvedBy)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">
                                        {{ $carrier->status === 'approved' ? 'Approved By' : 'Reviewed By' }}</p>
                                    <p class="text-sm text-gray-800 font-medium">{{ $carrier->approvedBy->name }}</p>
                                    @if ($carrier->approved_at)
                                        <p class="text-xs text-gray-400">
                                            {{ $carrier->approved_at->format('d M Y, h:i A') }}</p>
                                    @endif
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Last Updated</p>
                                <p class="text-sm text-gray-800 font-medium">
                                    {{ $carrier->updated_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Danger Zone --}}
                    <div class="bg-white border border-red-100 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-red-100 bg-red-50">
                            <h3 class="text-sm font-semibold text-red-700">Danger Zone</h3>
                        </div>
                        <div class="p-5">
                            <p class="text-xs text-gray-500 mb-3">Permanently delete this carrier and all associated data.
                            </p>
                            <form action="{{ route('logistics.carriers.v2.destroy', $carrier) }}" method="POST"
                                onsubmit="return confirm('Are you sure? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                                    Delete Carrier
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection
