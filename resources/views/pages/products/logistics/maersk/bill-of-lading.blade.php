@extends('layouts.auth')
@section('title', 'Maersk Bill of Lading')
@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Bill of Lading</h2>
                    <p class="text-xs text-slate-500 mt-1">Retrieve B/L details using DCSA standard v2.0 — API v1.2.0</p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Maersk Hub
                </a>
            </div>

            {{-- Search Bar --}}
            <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <form method="GET" action="{{ route('logistics.maersk.bill-of-lading') }}">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="flex-1 min-w-[220px] max-w-sm">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Transport Document Reference <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                        <i class="fa-solid fa-file-contract text-xs"></i>
                                    </span>
                                    <input type="text" name="transportDocumentReference"
                                        value="{{ request('transportDocumentReference') }}" placeholder="e.g. 265305368"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                            </div>
                            <button type="submit"
                                class="px-6 py-[9px] bg-[#0078C8] text-white text-xs font-semibold rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-magnifying-glass"></i> Retrieve B/L
                            </button>
                        </div>
                    </form>
                </div>

                {{-- States --}}
                @if ($error)
                    <div class="p-6">
                        <div
                            class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            <i class="fa-solid fa-circle-xmark mt-0.5 text-red-400 shrink-0"></i>
                            <span>{{ $error }}</span>
                        </div>
                    </div>
                @elseif (!$searched)
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 mb-4">
                            <i class="fa-solid fa-file-lines text-2xl text-slate-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Enter a B/L reference to get started</p>
                        <p class="text-xs text-slate-400 mt-1">Retrieve full shipment details, parties, cargo & charges</p>
                    </div>
                @elseif (empty($bol))
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-50 mb-4">
                            <i class="fa-solid fa-file-circle-question text-2xl text-amber-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No Bill of Lading found for that reference.</p>
                    </div>
                @else
                    @php
                        $si = $bol['shippingInstruction'] ?? [];
                        $partyLabels = [
                            'OS' => 'Shipper',
                            'CN' => 'Consignee',
                            'N1' => 'Notify Party',
                            'SCO' => 'Shipper (SCO)',
                            'BA' => 'Booking Agent',
                            'DDR' => 'Destination Drayage',
                            'NI' => 'Notify Party',
                        ];
                        $modeIcons = [
                            'VESSEL' => 'fa-ship',
                            'TRUCK' => 'fa-truck',
                            'RAIL' => 'fa-train',
                            'BARGE' => 'fa-water',
                        ];
                        $modeColors = [
                            'VESSEL' => 'text-blue-600 bg-blue-50',
                            'TRUCK' => 'text-orange-600 bg-orange-50',
                            'RAIL' => 'text-purple-600 bg-purple-50',
                            'BARGE' => 'text-teal-600 bg-teal-50',
                        ];
                        $statusMap = [
                            'RECE' => ['Received', 'bg-blue-100 text-blue-700 border-blue-200'],
                            'DRFT' => ['Draft', 'bg-yellow-100 text-yellow-700 border-yellow-200'],
                            'PENA' => ['Pending', 'bg-orange-100 text-orange-700 border-orange-200'],
                            'PENU' => ['Pending Update', 'bg-orange-100 text-orange-700 border-orange-200'],
                            'CONF' => ['Confirmed', 'bg-green-100 text-green-700 border-green-200'],
                            'ISSU' => ['Issued', 'bg-green-100 text-green-700 border-green-200'],
                            'SURR' => ['Surrendered', 'bg-purple-100 text-purple-700 border-purple-200'],
                            'VOID' => ['Void', 'bg-red-100 text-red-700 border-red-200'],
                        ];
                        $docStatus = $si['documentStatus'] ?? null;
                        $statusInfo = $statusMap[$docStatus] ?? [
                            $docStatus,
                            'bg-gray-100 text-gray-600 border-gray-200',
                        ];
                        $fnColors = [
                            'OS' => ['bg-emerald-100 text-emerald-700', 'bg-emerald-500'],
                            'CN' => ['bg-blue-100 text-blue-700', 'bg-blue-500'],
                            'N1' => ['bg-amber-100 text-amber-700', 'bg-amber-500'],
                            'SCO' => ['bg-purple-100 text-purple-700', 'bg-purple-500'],
                            'BA' => ['bg-teal-100 text-teal-700', 'bg-teal-500'],
                        ];
                    @endphp

                    {{-- B/L Document Banner --}}
                    <div class="border-b border-gray-100 bg-gradient-to-r from-[#0078C8]/5 via-white to-white px-6 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-xl bg-[#0078C8] flex items-center justify-center shadow-sm shrink-0">
                                    <i class="fa-solid fa-file-contract text-white text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Bill of Lading
                                    </p>
                                    <p class="text-xl font-bold text-slate-900 tracking-wide font-mono">
                                        {{ $bol['transportDocumentReference'] ?? '-' }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ $si['transportDocumentTypeCode'] ?? '' }}
                                        @if (!empty($bol['transportDocumentCreatedDateTime']))
                                            &nbsp;·&nbsp; Issued
                                            {{ \Carbon\Carbon::parse($bol['transportDocumentCreatedDateTime'])->format('d M Y') }}
                                        @endif
                                        @if (!empty($bol['transportDocumentUpdatedDateTime']))
                                            &nbsp;·&nbsp; Updated
                                            {{ \Carbon\Carbon::parse($bol['transportDocumentUpdatedDateTime'])->format('d M Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                @if ($docStatus)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border {{ $statusInfo[1] }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                                        {{ $statusInfo[0] }}
                                    </span>
                                @endif
                                @if (!empty($bol['shippedOnBoardDate']))
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        <i class="fa-solid fa-calendar-check text-slate-400"></i>
                                        Shipped {{ \Carbon\Carbon::parse($bol['shippedOnBoardDate'])->format('d M Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">

                        {{-- Key Info Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                            @php
                                $keyInfo = array_filter(
                                    [
                                        ['Vessel', $bol['vesselName'] ?? null, 'fa-ship', 'text-blue-500'],
                                        [
                                            'Voyage',
                                            $bol['carrierExportVoyageNumber'] ?? null,
                                            'fa-hashtag',
                                            'text-slate-400',
                                        ],
                                        [
                                            'Booking Ref',
                                            $si['carrierBookingReference'] ?? null,
                                            'fa-bookmark',
                                            'text-slate-400',
                                        ],
                                        [
                                            'Cargo Type',
                                            isset($bol['cargoMovementTypeAtOrigin'])
                                                ? $bol['cargoMovementTypeAtOrigin'] .
                                                    ' / ' .
                                                    ($bol['cargoMovementTypeAtDestination'] ?? '')
                                                : null,
                                            'fa-boxes-stacked',
                                            'text-slate-400',
                                        ],
                                        [
                                            'Originals',
                                            isset($si['numberOfOriginalsWithoutCharges'])
                                                ? $si['numberOfOriginalsWithoutCharges'] . ' copies'
                                                : null,
                                            'fa-copy',
                                            'text-slate-400',
                                        ],
                                        [
                                            'Receipt Type',
                                            $bol['receiptTypeAtOrigin'] ?? null,
                                            'fa-warehouse',
                                            'text-slate-400',
                                        ],
                                    ],
                                    fn($c) => !empty($c[1]),
                                );
                            @endphp
                            @foreach ($keyInfo as [$label, $value, $icon, $iconColor])
                                <div
                                    class="bg-white rounded-xl border border-gray-200 p-3.5 hover:border-[#0078C8]/30 hover:shadow-sm transition-all">
                                    <div class="flex items-center gap-1.5 mb-1.5">
                                        <i class="fa-solid {{ $icon }} text-xs {{ $iconColor }}"></i>
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
                                            {{ $label }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-800 truncate" title="{{ $value }}">
                                        {{ $value }}</p>
                                </div>
                            @endforeach
                        </div>

                        {{-- Route Visual --}}
                        @if (
                            !empty($si['displayedNameForPlaceOfReceipt']) ||
                                !empty($si['displayedNameForPortOfLoad']) ||
                                !empty($si['displayedNameForPortOfDischarge']))
                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                                    <i class="fa-solid fa-route text-[#0078C8] text-sm"></i>
                                    <h4 class="font-semibold text-slate-800 text-sm">Shipment Route</h4>
                                </div>
                                <div class="px-6 py-5">
                                    <div class="flex flex-wrap items-stretch gap-0">
                                        @php
                                            $routeStops = [];
                                            if (!empty($si['displayedNameForPlaceOfReceipt'])) {
                                                $routeStops[] = [
                                                    'Place of Receipt',
                                                    implode(', ', $si['displayedNameForPlaceOfReceipt']),
                                                    'fa-circle-dot',
                                                    'text-slate-400',
                                                ];
                                            }
                                            if (!empty($si['displayedNameForPortOfLoad'])) {
                                                $routeStops[] = [
                                                    'Port of Load',
                                                    implode(', ', $si['displayedNameForPortOfLoad']),
                                                    'fa-anchor',
                                                    'text-[#0078C8]',
                                                ];
                                            }
                                            if (!empty($si['displayedNameForPortOfDischarge'])) {
                                                $routeStops[] = [
                                                    'Port of Discharge',
                                                    implode(', ', $si['displayedNameForPortOfDischarge']),
                                                    'fa-anchor',
                                                    'text-emerald-500',
                                                ];
                                            }
                                        @endphp
                                        @foreach ($routeStops as $i => [$rlabel, $rvalue, $ricon, $riconColor])
                                            <div
                                                class="flex items-center {{ $i < count($routeStops) - 1 ? 'flex-1' : '' }}">
                                                <div class="flex flex-col items-center min-w-[90px]">
                                                    <div
                                                        class="w-9 h-9 rounded-full border-2 border-gray-200 bg-white flex items-center justify-center mb-2 shadow-sm">
                                                        <i
                                                            class="fa-solid {{ $ricon }} text-sm {{ $riconColor }}"></i>
                                                    </div>
                                                    <p
                                                        class="text-[10px] text-slate-400 uppercase tracking-wider text-center">
                                                        {{ $rlabel }}</p>
                                                    <p class="text-sm font-bold text-slate-800 text-center mt-0.5">
                                                        {{ $rvalue }}</p>
                                                </div>
                                                @if ($i < count($routeStops) - 1)
                                                    <div class="flex-1 flex items-center justify-center mb-6 px-2">
                                                        <div class="h-px flex-1 border-t-2 border-dashed border-gray-200">
                                                        </div>
                                                        <i class="fa-solid fa-ship text-gray-300 mx-2 text-sm"></i>
                                                        <div class="h-px flex-1 border-t-2 border-dashed border-gray-200">
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Parties --}}
                        @if (!empty($si['documentParties']))
                            @php
                                $uniqueParties = [];
                                $seen = [];
                                foreach ($si['documentParties'] as $dp) {
                                    $key = ($dp['party']['partyName'] ?? '') . '|' . ($dp['partyFunction'] ?? '');
                                    if (!in_array($key, $seen)) {
                                        $seen[] = $key;
                                        $uniqueParties[] = $dp;
                                    }
                                }
                            @endphp
                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                                    <i class="fa-solid fa-users text-[#0078C8] text-sm"></i>
                                    <h4 class="font-semibold text-slate-800 text-sm">Parties</h4>
                                    <span class="ml-auto text-xs text-slate-400">{{ count($uniqueParties) }} parties</span>
                                </div>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                                    @foreach ($uniqueParties as $index => $party)
                                        @php
                                            $fn = $party['partyFunction'] ?? '-';
                                            $p = $party['party'] ?? [];
                                            $addr = $p['address'] ?? [];
                                            $contact = $p['partyContactDetails'][0] ?? [];
                                            $displayAddr = $p['displayedAddresses'][0]['addressLine'] ?? null;
                                            $fnLabel = $partyLabels[$fn] ?? $fn;
                                            [$fnBadge, $fnDot] = $fnColors[$fn] ?? [
                                                'bg-gray-100 text-gray-600',
                                                'bg-gray-400',
                                            ];
                                            $initials = strtoupper(
                                                implode(
                                                    '',
                                                    array_map(
                                                        fn($w) => $w[0],
                                                        array_slice(explode(' ', $p['partyName'] ?? 'X'), 0, 2),
                                                    ),
                                                ),
                                            );
                                        @endphp
                                        @if ($index > 0 && $index % 2 === 0)
                                </div>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-100 border-t border-gray-100">
                        @endif
                        <div class="p-5 flex gap-4 hover:bg-slate-50/50 transition-colors">
                            <div
                                class="w-10 h-10 rounded-xl {{ $fnDot }} flex items-center justify-center shrink-0 shadow-sm">
                                <span class="text-white text-xs font-bold">{{ $initials }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-2 mb-1">
                                    <p class="text-sm font-semibold text-slate-800 truncate flex-1">
                                        {{ $p['partyName'] ?? '-' }}</p>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $fnBadge }} shrink-0">{{ $fnLabel }}</span>
                                </div>
                                @if ($displayAddr && trim($displayAddr))
                                    <p class="text-xs text-slate-500 whitespace-pre-line leading-relaxed">
                                        {{ trim($displayAddr) }}</p>
                                @elseif (!empty($addr))
                                    <p class="text-xs text-slate-500">
                                        {{ trim(($addr['streetNumber'] ?? '') . ' ' . ($addr['streetName'] ?? '')) }}{{ !empty($addr['cityName']) ? ', ' . $addr['cityName'] : '' }}{{ !empty($addr['stateRegion']) && trim($addr['stateRegion']) ? ', ' . trim($addr['stateRegion']) : '' }}{{ !empty($addr['postCode']) ? ' ' . $addr['postCode'] : '' }}{{ !empty($addr['country']) ? ', ' . $addr['country'] : '' }}
                                    </p>
                                @endif
                                <div class="flex flex-wrap gap-3 mt-2">
                                    @if (!empty($contact['phone']))
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                            <i class="fa-solid fa-phone text-[10px]"></i>{{ $contact['phone'] }}
                                        </span>
                                    @endif
                                    @if (!empty($contact['email']))
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                            <i class="fa-solid fa-envelope text-[10px]"></i>{{ $contact['email'] }}
                                        </span>
                                    @endif
                                    @if (!empty($p['taxReference1']))
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                            <i class="fa-solid fa-id-card text-[10px]"></i>{{ $p['taxReference1'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Cargo & Equipment side by side --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            {{-- Consignment Items --}}
            @if (!empty($si['consignmentItems']))
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-box-open text-[#0078C8] text-sm"></i>
                        <h4 class="font-semibold text-slate-800 text-sm">Consignment Items</h4>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($si['consignmentItems'] as $item)
                            @php
                                $totalPkgs = array_sum(array_column($item['cargoItems'] ?? [], 'numberOfPackages'));
                                $equipRefs = array_column($item['cargoItems'] ?? [], 'equipmentReference');
                            @endphp
                            <div class="p-5">
                                <p class="text-sm font-semibold text-slate-800 mb-3">
                                    {{ $item['descriptionOfGoods'] ?? '-' }}</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-slate-50 rounded-lg p-2.5">
                                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">HS
                                            Code</p>
                                        <p class="text-sm font-mono font-semibold text-slate-700 mt-0.5">
                                            {{ $item['HSCode'] ?? ($item['hscode'] ?? '-') }}</p>
                                    </div>
                                    <div class="bg-slate-50 rounded-lg p-2.5">
                                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Weight
                                        </p>
                                        <p class="text-sm font-semibold text-slate-700 mt-0.5">
                                            @if (!empty($item['weight']))
                                                {{ number_format($item['weight']) }} <span
                                                    class="text-xs font-normal text-slate-400">{{ $item['weightUnit'] ?? '' }}</span>
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                    @if ($totalPkgs)
                                        <div class="bg-slate-50 rounded-lg p-2.5">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
                                                Packages</p>
                                            <p class="text-sm font-semibold text-slate-700 mt-0.5">{{ $totalPkgs }}</p>
                                        </div>
                                    @endif
                                    @if (!empty($equipRefs))
                                        <div class="bg-slate-50 rounded-lg p-2.5">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
                                                Container</p>
                                            <p class="text-sm font-mono font-semibold text-slate-700 mt-0.5">
                                                {{ implode(', ', $equipRefs) }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Equipment / Containers --}}
            @if (!empty($si['utilizedTransportEquipments']))
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-cube text-[#0078C8] text-sm"></i>
                        <h4 class="font-semibold text-slate-800 text-sm">Containers</h4>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($si['utilizedTransportEquipments'] as $ute)
                            <div class="p-5">
                                <div class="flex items-center gap-3 mb-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-[#0078C8]/10 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-cube text-[#0078C8] text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold font-mono text-slate-800">
                                            {{ $ute['equipment']['equipmentReference'] ?? '-' }}</p>
                                        <p class="text-xs text-slate-400">ISO:
                                            {{ $ute['equipment']['ISOEquipmentCode'] ?? '-' }}</p>
                                    </div>
                                    <div class="ml-auto">
                                        @if (isset($ute['isShipperOwned']))
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $ute['isShipperOwned'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $ute['isShipperOwned'] ? 'Shipper Owned' : 'Carrier Owned' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-slate-50 rounded-lg p-2.5">
                                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Gross
                                            Wt.</p>
                                        <p class="text-sm font-semibold text-slate-700 mt-0.5">
                                            @if (!empty($ute['cargoGrossWeight']))
                                                {{ number_format($ute['cargoGrossWeight']) }} <span
                                                    class="text-xs font-normal text-slate-400">{{ $ute['cargoGrossWeightUnit'] ?? '' }}</span>
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                    <div class="bg-slate-50 rounded-lg p-2.5">
                                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
                                            Packages</p>
                                        <p class="text-sm font-semibold text-slate-700 mt-0.5">
                                            {{ $ute['numberOfPackages'] ?? '—' }}</p>
                                    </div>
                                    <div class="bg-slate-50 rounded-lg p-2.5">
                                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Seal
                                            No.</p>
                                        <p class="text-sm font-mono font-semibold text-slate-700 mt-0.5">
                                            @foreach ($ute['seals'] ?? [] as $seal)
                                                {{ $seal['sealNumber'] ?? '' }}
                                            @endforeach
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Transport Timeline --}}
        @if (!empty($bol['transports']))
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                    <i class="fa-solid fa-map-location-dot text-[#0078C8] text-sm"></i>
                    <h4 class="font-semibold text-slate-800 text-sm">Transport Plan</h4>
                    <span class="ml-auto text-xs text-slate-400">{{ count($bol['transports']) }} legs</span>
                </div>
                <div class="p-5 space-y-0">
                    @foreach ($bol['transports'] as $idx => $transport)
                        @php
                            $mode = $transport['modeOfTransport'] ?? 'VESSEL';
                            $modeIcon = $modeIcons[$mode] ?? 'fa-ship';
                            $modeColor = $modeColors[$mode] ?? 'text-blue-600 bg-blue-50';
                            $stage = $transport['transportPlanStage'] ?? '';
                            $stageLabel =
                                $stage === 'PRC' ? 'Pre-Carriage' : ($stage === 'MNC' ? 'Main Carriage' : $stage);
                            $isLast = $idx === count($bol['transports']) - 1;
                        @endphp
                        <div class="flex gap-4">
                            {{-- Timeline spine --}}
                            <div class="flex flex-col items-center">
                                <div
                                    class="w-9 h-9 rounded-xl {{ $modeColor }} flex items-center justify-center shrink-0 shadow-sm border border-white">
                                    <i class="fa-solid {{ $modeIcon }} text-sm"></i>
                                </div>
                                @if (!$isLast)
                                    <div class="w-px flex-1 bg-gray-200 my-1 min-h-[20px]"></div>
                                @endif
                            </div>
                            {{-- Content --}}
                            <div class="flex-1 pb-5 {{ $isLast ? '' : '' }}">
                                <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $transport['loadLocation']['locationName'] ?? '—' }}
                                            <span class="text-slate-300 mx-1.5">→</span>
                                            {{ $transport['dischargeLocation']['locationName'] ?? '—' }}
                                        </p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            {{ $stageLabel }} · Leg
                                            {{ $transport['transportPlanStageSequenceNumber'] ?? $idx + 1 }}
                                            @if (!empty($transport['loadLocation']['UNLocationCode']))
                                                · <span
                                                    class="font-mono">{{ $transport['loadLocation']['UNLocationCode'] }}</span>
                                                → <span
                                                    class="font-mono">{{ $transport['dischargeLocation']['UNLocationCode'] ?? '' }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <span
                                        class="px-2 py-0.5 rounded-md text-[10px] font-semibold {{ $modeColor }} uppercase">{{ $mode }}</span>
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    @if (!empty($transport['plannedDepartureDate']))
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                            <i class="fa-solid fa-plane-departure text-slate-300 text-[10px]"></i>
                                            <span>Departs <strong
                                                    class="text-slate-700">{{ $transport['plannedDepartureDate'] }}</strong></span>
                                        </div>
                                    @endif
                                    @if (!empty($transport['plannedArrivalDate']))
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                            <i class="fa-solid fa-plane-arrival text-slate-300 text-[10px]"></i>
                                            <span>Arrives <strong
                                                    class="text-slate-700">{{ $transport['plannedArrivalDate'] }}</strong></span>
                                        </div>
                                    @endif
                                    @if (!empty($transport['vesselName']))
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                            <i class="fa-solid fa-ship text-slate-300 text-[10px]"></i>
                                            <span><strong class="text-slate-700">{{ $transport['vesselName'] }}</strong>
                                                @if (!empty($transport['carrierExportVoyageNumber']) && trim($transport['carrierExportVoyageNumber']))
                                                    · Voyage {{ $transport['carrierExportVoyageNumber'] }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Charges --}}
        @if (!empty($bol['charges']))
            @php
                $totalCharges = array_sum(array_column($bol['charges'], 'currencyAmount'));
                $currency = $bol['charges'][0]['currencyCode'] ?? 'USD';
                $prepaidTotal = array_sum(
                    array_map(fn($c) => $c['paymentTermCode'] === 'PRE' ? $c['currencyAmount'] : 0, $bol['charges']),
                );
                $collectTotal = $totalCharges - $prepaidTotal;
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-[#0078C8] text-sm"></i>
                        <h4 class="font-semibold text-slate-800 text-sm">Charges</h4>
                        <span class="text-xs text-slate-400">({{ count($bol['charges']) }} items)</span>
                    </div>
                    <div class="flex items-center gap-4">
                        @if ($prepaidTotal > 0)
                            <div class="text-right">
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider">Prepaid</p>
                                <p class="text-sm font-bold text-blue-700">{{ $currency }}
                                    {{ number_format($prepaidTotal, 2) }}</p>
                            </div>
                        @endif
                        <div class="text-right">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-sm font-bold text-slate-900">{{ $currency }}
                                {{ number_format($totalCharges, 2) }}</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-gray-500">
                                <th class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider">Charge Type</th>
                                <th class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider">Basis</th>
                                <th class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-center">Qty</th>
                                <th class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-right">Unit
                                    Price</th>
                                <th class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-center">Payment
                                </th>
                                <th class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-right">Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($bol['charges'] as $charge)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-5 py-3 font-medium text-gray-800">{{ $charge['chargeType'] ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $charge['calculationBasis'] ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-600 text-center">{{ $charge['quantity'] ?? '-' }}</td>
                                    <td class="px-5 py-3 text-gray-600 text-right font-mono text-xs">
                                        {{ $charge['currencyCode'] ?? '' }}
                                        {{ isset($charge['unitPrice']) ? number_format($charge['unitPrice'], 2) : '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @if (!empty($charge['paymentTermCode']))
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $charge['paymentTermCode'] === 'PRE' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                                                {{ $charge['paymentTermCode'] === 'PRE' ? 'Prepaid' : $charge['paymentTermCode'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 font-bold text-gray-900 text-right font-mono">
                                        {{ $charge['currencyCode'] ?? '' }}
                                        {{ isset($charge['currencyAmount']) ? number_format($charge['currencyAmount'], 2) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-[#0078C8]/5 border-t-2 border-[#0078C8]/20">
                                <td colspan="5" class="px-5 py-3 text-sm font-bold text-slate-700 text-right">Grand
                                    Total</td>
                                <td class="px-5 py-3 text-base font-bold text-[#0078C8] text-right font-mono">
                                    {{ $currency }} {{ number_format($totalCharges, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif


    </div>
    @endif
    </div>
    </div>
    </div>
@endsection
