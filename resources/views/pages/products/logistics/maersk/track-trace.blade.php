@extends('layouts.auth')
@section('title', 'Maersk Track & Trace')
@section('content')
    <x-acccount.loader />

    @php
        $eventTypeLabels = [
            // Transport
            'DEPA' => 'Departure',
            'ARRI' => 'Arrival',
            // Shipment
            'CONF' => 'Confirmed',
            'RECE' => 'Received',
            'DRFT' => 'Draft Issued',
            'APPR' => 'Approved',
            'REJE' => 'Rejected',
            'PENA' => 'Pending Approval',
            'ISSU' => 'Issued',
            'SURR' => 'Surrendered',
            'SUBM' => 'Submitted',
            'VOID' => 'Voided',
            // Equipment
            'LOAD' => 'Loaded on Vessel',
            'DISC' => 'Discharged',
            'GTIN' => 'Gate In',
            'GTOT' => 'Gate Out',
            'PICK' => 'Picked Up',
            'DROP' => 'Dropped Off',
            'INSP' => 'Inspected',
            'RSEA' => 'Resealed',
            'RMVD' => 'Removed',
        ];

        $docTypeLabels = [
            'BKG' => 'Booking',
            'TRD' => 'Transport Document',
            'VGM' => 'VGM',
            'SHI' => 'Shipping Instruction',
            'SRM' => 'Surrender Request',
            'TRP' => 'Transport Plan',
        ];

        $modeIcons = [
            'VESSEL' => ['fa-ship',   'text-[#0078C8]', 'bg-blue-50'],
            'TRUCK'  => ['fa-truck',  'text-amber-600', 'bg-amber-50'],
            'RAIL'   => ['fa-train',  'text-purple-600','bg-purple-50'],
            'AIR'    => ['fa-plane',  'text-sky-600',   'bg-sky-50'],
        ];

        $facilityLabels = [
            'POTE' => 'Port Terminal',
            'INTE' => 'Inland Terminal',
            'BRTH' => 'Berth',
            'CLOC' => 'Customer Location',
            'COFS' => 'Container Freight Station',
            'DEPO' => 'Depot',
            'RAMP' => 'Rail Ramp',
        ];

        // Counts
        $countByType = ['TRANSPORT' => 0, 'SHIPMENT' => 0, 'EQUIPMENT' => 0];
        foreach ($events as $e) {
            $t = $e['eventType'] ?? '';
            if (isset($countByType[$t])) $countByType[$t]++;
        }

        // Sort by eventDateTime ascending
        $sorted = $events;
        usort($sorted, fn($a, $b) =>
            strtotime($a['eventDateTime'] ?? 0) <=> strtotime($b['eventDateTime'] ?? 0)
        );
    @endphp

    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Track &amp; Trace</h2>
                    <p class="text-xs text-slate-500 mt-1">Real-time shipment event tracking — Maersk API v1.5.0</p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Maersk Hub
                </a>
            </div>

            {{-- Search Card --}}
            <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-slate-50/50">
                    <form method="GET" action="{{ route('logistics.maersk.track-trace') }}">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="w-full md:w-56">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Booking Reference</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                        <i class="fa-solid fa-hashtag text-xs"></i>
                                    </span>
                                    <input type="text" name="carrierBookingReference"
                                        value="{{ request('carrierBookingReference') }}"
                                        placeholder="e.g. 265305368"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                            </div>
                            <div class="w-full md:w-56">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Transport Document / B/L</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                        <i class="fa-solid fa-file-contract text-xs"></i>
                                    </span>
                                    <input type="text" name="transportDocumentReference"
                                        value="{{ request('transportDocumentReference') }}"
                                        placeholder="e.g. 260029935"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                            </div>
                            <div class="w-full md:w-48">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Container / Equipment</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                        <i class="fa-solid fa-box text-xs"></i>
                                    </span>
                                    <input type="text" name="equipmentReference"
                                        value="{{ request('equipmentReference') }}"
                                        placeholder="e.g. TLLU5361825"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                            </div>
                            <div class="w-full md:w-40">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Event Type</label>
                                <select name="eventType"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    <option value="">All Types</option>
                                    <option value="TRANSPORT" {{ request('eventType') == 'TRANSPORT' ? 'selected' : '' }}>Transport</option>
                                    <option value="SHIPMENT"  {{ request('eventType') == 'SHIPMENT'  ? 'selected' : '' }}>Shipment</option>
                                    <option value="EQUIPMENT" {{ request('eventType') == 'EQUIPMENT' ? 'selected' : '' }}>Equipment</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="px-6 py-[9px] bg-[#0078C8] text-white text-xs font-semibold rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-magnifying-glass"></i> Track
                            </button>
                        </div>
                    </form>
                </div>

                {{-- States --}}
                @if ($error)
                    <div class="p-6">
                        <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            <i class="fa-solid fa-circle-xmark mt-0.5 text-red-400 shrink-0"></i>
                            <span>{{ $error }}</span>
                        </div>
                    </div>
                @elseif (!$searched)
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 mb-4">
                            <i class="fa-solid fa-satellite-dish text-2xl text-slate-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Enter a booking reference, B/L, or container number</p>
                        <p class="text-xs text-slate-400 mt-1">All shipment, transport and equipment events will appear here</p>
                    </div>
                @elseif (empty($events))
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-50 mb-4">
                            <i class="fa-solid fa-circle-question text-2xl text-amber-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No events found for this reference.</p>
                    </div>
                @else

                    {{-- Summary Stats --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-100 border-b border-gray-100">
                        <div class="bg-white px-5 py-4 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-list text-slate-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Total Events</p>
                                <p class="text-xl font-bold text-slate-800 leading-none">{{ count($events) }}</p>
                            </div>
                        </div>
                        <div class="bg-white px-5 py-4 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-route text-amber-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Transport</p>
                                <p class="text-xl font-bold text-amber-600 leading-none">{{ $countByType['TRANSPORT'] }}</p>
                            </div>
                        </div>
                        <div class="bg-white px-5 py-4 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-file-lines text-blue-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Shipment</p>
                                <p class="text-xl font-bold text-blue-600 leading-none">{{ $countByType['SHIPMENT'] }}</p>
                            </div>
                        </div>
                        <div class="bg-white px-5 py-4 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-box text-green-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Equipment</p>
                                <p class="text-xl font-bold text-green-600 leading-none">{{ $countByType['EQUIPMENT'] }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Filter Tabs --}}
                    <div class="border-b border-gray-100 px-5 flex gap-1 overflow-x-auto">
                        @foreach (['all' => 'All', 'TRANSPORT' => 'Transport', 'SHIPMENT' => 'Shipment', 'EQUIPMENT' => 'Equipment'] as $key => $label)
                            <button type="button" onclick="filterEvents('{{ $key }}')"
                                data-filter="{{ $key }}"
                                class="filter-btn flex items-center gap-1.5 px-4 py-3 text-xs font-semibold border-b-2 whitespace-nowrap transition-all
                                    {{ $key === 'all' ? 'text-[#0078C8] border-[#0078C8]' : 'text-slate-500 border-transparent hover:text-slate-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Timeline --}}
                    <div class="p-6">
                        <div class="relative" id="events-timeline">
                            {{-- Vertical spine --}}
                            <div class="absolute left-[22px] top-3 bottom-3 w-px bg-gray-200"></div>

                            <div class="space-y-3">
                                @foreach ($sorted as $idx => $event)
                                    @php
                                        $type     = $event['eventType'] ?? '';
                                        $typeCode = $event['transportEventTypeCode']
                                            ?? $event['shipmentEventTypeCode']
                                            ?? $event['equipmentEventTypeCode']
                                            ?? '';
                                        $classifier = $event['eventClassifierCode'] ?? '';
                                        $label    = $eventTypeLabels[$typeCode] ?? $typeCode;
                                        $evtDt    = isset($event['eventDateTime'])
                                            ? \Carbon\Carbon::parse($event['eventDateTime'])
                                            : null;

                                        // Type config
                                        [$dotColor, $borderCls, $badgeCls, $dotIcon] = match($type) {
                                            'TRANSPORT' => ['bg-amber-400',  'border-l-amber-300',  'bg-amber-100 text-amber-700 border-amber-200',  'fa-route'],
                                            'SHIPMENT'  => ['bg-blue-400',   'border-l-blue-300',   'bg-blue-100 text-blue-700 border-blue-200',      'fa-file-lines'],
                                            'EQUIPMENT' => ['bg-green-400',  'border-l-green-300',  'bg-green-100 text-green-700 border-green-200',   'fa-box'],
                                            default     => ['bg-gray-400',   'border-l-gray-300',   'bg-gray-100 text-gray-700 border-gray-200',      'fa-circle'],
                                        };

                                        $classifierCls = $classifier === 'ACT'
                                            ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                                            : 'bg-amber-50 text-amber-600 border-amber-200';
                                        $classifierLabel = $classifier === 'ACT' ? 'Actual' : 'Estimated';

                                        // Transport call details
                                        $tc       = $event['transportCall'] ?? [];
                                        $loc      = $tc['location'] ?? [];
                                        $vessel   = $tc['vessel'] ?? [];
                                        $mode     = $tc['modeOfTransport'] ?? '';
                                        $facility = $facilityLabels[$tc['facilityTypeCode'] ?? ''] ?? '';
                                        [$modeIcon, $modeColor, $modeBg] = $modeIcons[$mode] ?? ['fa-circle-dot', 'text-slate-500', 'bg-slate-50'];
                                        $voyage   = $tc['exportVoyageNumber'] ?? $tc['carrierVoyageNumber'] ?? '';
                                        $unCode   = $tc['UNLocationCode'] ?? '';

                                        // Seals
                                        $seals = $event['seals'] ?? [];

                                        // References
                                        $docRefs = $event['documentReferences'] ?? $event['references'] ?? [];
                                    @endphp

                                    <div class="event-row pl-12 relative" data-type="{{ $type }}">
                                        {{-- Timeline dot --}}
                                        <div class="absolute left-0 top-3 w-[46px] flex items-center justify-center">
                                            <div class="w-11 h-11 rounded-full {{ $dotColor }}/10 border-2 {{ $dotColor === 'bg-amber-400' ? 'border-amber-300' : ($dotColor === 'bg-blue-400' ? 'border-blue-300' : 'border-green-300') }} flex items-center justify-center z-10 bg-white">
                                                <i class="fa-solid {{ $dotIcon }} text-sm {{ $dotColor === 'bg-amber-400' ? 'text-amber-500' : ($dotColor === 'bg-blue-400' ? 'text-blue-500' : 'text-green-500') }}"></i>
                                            </div>
                                        </div>

                                        {{-- Event Card --}}
                                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:border-gray-300 hover:shadow-sm transition-all border-l-4 {{ $borderCls }}">

                                            {{-- Card Header --}}
                                            <div class="px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    {{-- Type badge --}}
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $badgeCls }}">
                                                        <i class="fa-solid {{ $dotIcon }} text-[9px]"></i>
                                                        {{ $type }}
                                                    </span>
                                                    {{-- Event code label --}}
                                                    <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                                                    @if ($typeCode)
                                                        <span class="text-[10px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">{{ $typeCode }}</span>
                                                    @endif
                                                    {{-- Classifier --}}
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $classifierCls }}">
                                                        <span class="w-1.5 h-1.5 rounded-full {{ $classifier === 'ACT' ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                                                        {{ $classifierLabel }}
                                                    </span>
                                                </div>
                                                {{-- Date/time --}}
                                                @if ($evtDt)
                                                    <div class="text-right shrink-0">
                                                        <p class="text-xs font-semibold text-slate-700">{{ $evtDt->format('d M Y') }}</p>
                                                        <p class="text-[10px] text-slate-400">{{ $evtDt->format('H:i T') }}</p>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Card Body --}}
                                            @if ($type === 'TRANSPORT' || $type === 'EQUIPMENT')
                                                <div class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">

                                                    {{-- Location --}}
                                                    @if (!empty($loc['locationName']) || $unCode)
                                                        <div class="flex items-start gap-2.5 p-3 bg-slate-50 rounded-lg">
                                                            <div class="w-7 h-7 rounded-md bg-white border border-slate-200 flex items-center justify-center shrink-0 mt-0.5">
                                                                <i class="fa-solid fa-location-dot text-[#0078C8] text-xs"></i>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Location</p>
                                                                <p class="text-xs font-semibold text-slate-800 leading-snug">{{ $loc['locationName'] ?? '—' }}</p>
                                                                @if ($unCode)
                                                                    <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ $unCode }}
                                                                        @if ($facility)
                                                                            · {{ $facility }}
                                                                        @endif
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Mode of Transport --}}
                                                    @if ($mode)
                                                        <div class="flex items-start gap-2.5 p-3 {{ $modeBg }} rounded-lg">
                                                            <div class="w-7 h-7 rounded-md bg-white border border-slate-200 flex items-center justify-center shrink-0 mt-0.5">
                                                                <i class="fa-solid {{ $modeIcon }} {{ $modeColor }} text-xs"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Transport Mode</p>
                                                                <p class="text-xs font-semibold text-slate-800">{{ ucfirst(strtolower($mode)) }}</p>
                                                                @if ($voyage)
                                                                    <p class="text-[10px] text-slate-500 font-mono mt-0.5">Voyage {{ $voyage }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Vessel --}}
                                                    @if (!empty($vessel['vesselName']))
                                                        <div class="flex items-start gap-2.5 p-3 bg-blue-50 rounded-lg">
                                                            <div class="w-7 h-7 rounded-md bg-white border border-blue-100 flex items-center justify-center shrink-0 mt-0.5">
                                                                <i class="fa-solid fa-ship text-[#0078C8] text-xs"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Vessel</p>
                                                                <p class="text-xs font-semibold text-slate-800">{{ $vessel['vesselName'] }}</p>
                                                                <div class="flex items-center gap-2 mt-0.5">
                                                                    @if (!empty($vessel['vesselFlag']))
                                                                        <span class="text-[10px] text-slate-500">🏳 {{ $vessel['vesselFlag'] }}</span>
                                                                    @endif
                                                                    @if (!empty($vessel['vesselIMONumber']))
                                                                        <span class="text-[10px] font-mono text-slate-400">IMO {{ $vessel['vesselIMONumber'] }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Equipment ref (equipment events) --}}
                                                    @if (!empty($event['equipmentReference']))
                                                        <div class="flex items-start gap-2.5 p-3 bg-green-50 rounded-lg">
                                                            <div class="w-7 h-7 rounded-md bg-white border border-green-100 flex items-center justify-center shrink-0 mt-0.5">
                                                                <i class="fa-solid fa-box-open text-green-600 text-xs"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Container</p>
                                                                <p class="text-xs font-mono font-bold text-slate-800">{{ $event['equipmentReference'] }}</p>
                                                                @if (!empty($event['emptyIndicatorCode']))
                                                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold
                                                                        {{ $event['emptyIndicatorCode'] === 'LADEN' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                                                        {{ $event['emptyIndicatorCode'] }}
                                                                    </span>
                                                                @endif
                                                                @if (!empty($event['ISOEquipmentCode']))
                                                                    <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ $event['ISOEquipmentCode'] }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Seals --}}
                                                    @if (!empty($seals))
                                                        <div class="flex items-start gap-2.5 p-3 bg-slate-50 rounded-lg">
                                                            <div class="w-7 h-7 rounded-md bg-white border border-slate-200 flex items-center justify-center shrink-0 mt-0.5">
                                                                <i class="fa-solid fa-lock text-slate-500 text-xs"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Seals</p>
                                                                <div class="flex flex-wrap gap-1 mt-1">
                                                                    @foreach ($seals as $seal)
                                                                        <span class="font-mono text-[10px] bg-white border border-slate-200 px-1.5 py-0.5 rounded text-slate-700">
                                                                            {{ $seal['sealNumber'] ?? '?' }}
                                                                            @if (!empty($seal['sealType']))
                                                                                <span class="text-slate-400">· {{ $seal['sealType'] }}</span>
                                                                            @endif
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                </div>
                                            @elseif ($type === 'SHIPMENT')
                                                <div class="px-4 pb-4 flex flex-wrap gap-3">
                                                    @if (!empty($event['documentTypeCode']))
                                                        <div class="flex items-center gap-2 p-3 bg-blue-50 rounded-lg">
                                                            <i class="fa-solid fa-file text-blue-400 text-xs"></i>
                                                            <div>
                                                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Document</p>
                                                                <p class="text-xs font-semibold text-slate-800">
                                                                    {{ $docTypeLabels[$event['documentTypeCode']] ?? $event['documentTypeCode'] }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if (!empty($event['documentID']))
                                                        <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg">
                                                            <i class="fa-solid fa-fingerprint text-slate-400 text-xs"></i>
                                                            <div>
                                                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Document ID</p>
                                                                <p class="text-xs font-mono font-bold text-slate-800">{{ $event['documentID'] }}</p>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    {{-- References --}}
                                                    @if (!empty($docRefs))
                                                        @foreach ($docRefs as $ref)
                                                            @if (!empty($ref['referenceValue']))
                                                                <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg">
                                                                    <i class="fa-solid fa-link text-slate-400 text-xs"></i>
                                                                    <div>
                                                                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">{{ $ref['referenceType'] ?? $ref['documentReferenceType'] ?? 'Ref' }}</p>
                                                                        <p class="text-xs font-mono text-slate-700">{{ $ref['referenceValue'] ?? $ref['documentReferenceValue'] ?? '—' }}</p>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                @endif
            </div>
        </div>
    </div>

    <script>
        function filterEvents(type) {
            // Update active tab
            document.querySelectorAll('.filter-btn').forEach(btn => {
                const isActive = btn.dataset.filter === type;
                btn.className = btn.className
                    .replace(/text-\[#0078C8\]|border-\[#0078C8\]|text-slate-500|border-transparent/g, '')
                    .trim();
                btn.classList.add(...(isActive
                    ? ['text-[#0078C8]', 'border-[#0078C8]']
                    : ['text-slate-500', 'border-transparent']));
            });
            // Show/hide rows
            document.querySelectorAll('.event-row').forEach(row => {
                row.style.display = (type === 'all' || row.dataset.type === type) ? '' : 'none';
            });
        }
    </script>
@endsection
