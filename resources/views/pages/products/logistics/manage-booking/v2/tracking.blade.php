@extends('layouts.auth')

@section('title','Shipment Tracking')

@section('content')
    @php
        $shipperName = $booking->exporter ?: $booking->shipper ?: '-';
        $consigneeName = $booking->importer ?: $booking->consignee ?: '-';
        $routeLabel =
            ($booking->origin_port ?: $booking->place_of_receipt ?: 'Origin') .
            ' -> ' .
            ($booking->destination_port ?: $booking->final_delivery_place ?: 'Destination');
        $steps = $tracking['path_steps'] ?? [];
        $completeCount = collect($steps)->where('state', 'complete')->count();
        $linePercent = count($steps) > 1 ? (($completeCount - 1) / (count($steps) - 1)) * 100 : 0;
        $linePercent = max(0, min(100, $linePercent));
        $statusTone = $tracking['all_documents_received']
            ? 'bg-green-50 text-green-700 border-green-200'
            : 'bg-amber-50 text-amber-700 border-amber-200';
    @endphp
<div class="mt-6 px-6">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.manage-booking') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
    <div class="p-6 space-y-6">
        {{-- <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Shipment Status Tracker</h2>
                <p class="text-xs text-gray-500">Progress is driven by the documents you upload and mark as received.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('logistics.manage-booking.v2.index') }}"
                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all">
                    Back to Bookings
                </a>
                <a href="{{ route('logistics.manage-booking.v2.documents', ['q' => $booking->booking_number]) }}"
                    class="px-4 py-2 text-sm font-medium text-[#0078C8] bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition-all">
                    View Documents
                </a>
            </div>
        </div> --}}

        <div class="bg-white rounded-2xl border border-black/5 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/40 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900">Shipment Status Tracker - {{ $booking->booking_number }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $shipperName }} - {{ $routeLabel }} -
                        {{ $tracking['carrier_name'] ?? 'Carrier pending' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $consigneeName }}</p>
                </div>
                <span class="px-3 py-1.5 text-[10px] font-bold uppercase rounded-full border {{ $statusTone }}">
                    {{ $tracking['all_documents_received'] ? 'All Gates Cleared' : 'Waiting On Documents' }}
                </span>
            </div>

            <div class="px-8 py-8 overflow-x-auto">
                <div class="relative min-w-max">
                    <div class="absolute left-0 right-0 top-4 h-[2px] bg-gray-200"></div>
                    <div class="absolute left-0 top-4 h-[2px] bg-green-600 transition-all duration-500"
                        style="width: {{ $linePercent }}%"></div>

                    <div class="relative grid gap-4"
                        style="grid-template-columns: repeat({{ max(count($steps), 1) }}, minmax(130px, 1fr));">
                        @foreach ($steps as $index => $step)
                            @php
                                $isComplete = $step['state'] === 'complete';
                                $isCurrent = $step['state'] === 'current';
                                $circleClass = $isComplete
                                    ? 'bg-green-600 text-white border-green-600'
                                    : ($isCurrent
                                        ? 'bg-[#0F62B3] text-white border-[#0F62B3]'
                                        : 'bg-white text-gray-400 border-gray-300');
                                $labelClass = $isComplete
                                    ? 'text-green-700'
                                    : ($isCurrent
                                        ? 'text-[#0F62B3]'
                                        : 'text-gray-400');
                            @endphp

                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-xs font-bold relative z-10 {{ $circleClass }}">
                                    @if ($isComplete)
                                        <i class="fa-solid fa-check"></i>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </div>
                                <p class="mt-4 text-sm font-medium {{ $labelClass }}">{{ $step['label'] }}</p>
                                <p class="mt-1 text-[11px] text-gray-400">{{ $step['meta'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-black/5 shadow-sm p-6">
                <h3 class="text-2xl font-medium text-gray-900 mb-8">Detailed Tracking Events</h3>

                <div
                    class="relative space-y-8 before:absolute before:left-[17px] before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-200">
                    @foreach ($tracking['events'] as $event)
                        @php
                            $isComplete = $event['state'] === 'complete';
                            $isCurrent = $event['state'] === 'current';
                            $dotClass = $isComplete
                                ? 'bg-green-100 text-green-700'
                                : ($isCurrent
                                    ? 'bg-blue-100 text-[#0F62B3]'
                                    : 'bg-gray-100 text-gray-400');
                        @endphp

                        <div class="relative flex items-start gap-4">
                            <div
                                class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center {{ $dotClass }}">
                                <i class="fa-solid {{ $event['icon'] }}"></i>
                            </div>
                            <div class="pt-0.5">
                                <h4 class="text-xl leading-none font-medium text-gray-900">{{ $event['title'] }}</h4>
                                <p class="text-sm text-slate-500 mt-2">{{ $event['description'] }}</p>
                                <p class="text-sm text-slate-400 mt-2">{{ $event['date'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-black/5 shadow-sm p-6">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Summary</h4>
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Booking</p>
                            <p class="text-sm font-bold text-gray-900">{{ $booking->booking_number }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Status</p>
                            <p class="text-sm font-medium text-gray-800">{{ $booking->status ?: 'Booked' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Route</p>
                            <p class="text-sm font-medium text-gray-800">{{ $booking->origin_port ?: '-' }} ->
                                {{ $booking->destination_port ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Child Legs</p>
                            <p class="text-sm font-medium text-gray-800">{{ max(count($tracking['segments']) - 1, 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-black/5 shadow-sm p-6">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">How It Works</h4>
                    <div class="space-y-3 text-sm text-gray-600">
                        <p>Flow is now: origin -> uploaded documents -> destination -> child booking -> child documents ->
                            next stop.</p>
                        <p>If any document is not marked as `Received`, the next steps stay disabled and gray.</p>
                        <p>So progress follows your real uploaded document statuses, not a hardcoded shipment path.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-black/5 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                <h3 class="text-sm font-bold text-gray-800">Segment Details</h3>
                <p class="text-xs text-gray-500 mt-1">Parent and child booking document gates</p>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach ($tracking['segments'] as $segment)
                    <div class="p-6 {{ $segment['unlocked'] ? 'bg-white' : 'bg-gray-50/70' }}">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h4 class="text-base font-bold text-gray-900">{{ $segment['booking_number'] }}</h4>
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $segment['completed'] ? 'bg-green-50 text-green-700 border-green-200' : ($segment['unlocked'] ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200') }}">
                                        {{ $segment['type'] }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ $segment['status'] }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">{{ $segment['route'] }}</p>
                            </div>

                            <div class="text-xs">
                                <span
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border {{ $segment['document_summary']['all_received'] ? 'bg-green-50 text-green-700 border-green-200' : ($segment['unlocked'] ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-gray-100 text-gray-400 border-gray-200') }}">
                                    {{ $segment['document_summary']['received'] }}/{{ $segment['document_summary']['total'] }}
                                    docs received
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-5">
                            <div class="rounded-xl border border-gray-200 px-4 py-3 bg-gray-50/70">
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Origin</p>
                                <p class="text-sm font-semibold text-gray-800 mt-2">{{ $segment['origin_label'] }}</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 px-4 py-3 bg-gray-50/70">
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Destination</p>
                                <p class="text-sm font-semibold text-gray-800 mt-2">{{ $segment['destination_label'] }}</p>
                            </div>

                            @foreach ($segment['dates'] as $label => $value)
                                <div class="rounded-xl border border-gray-200 px-4 py-3 bg-gray-50/70">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">
                                        {{ $label }}</p>
                                    <p class="text-sm font-semibold text-gray-800 mt-2">{{ $value ?: '-' }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-400 mb-3">Documents</p>

                            @if (count($segment['documents']))
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                    @foreach ($segment['documents'] as $document)
                                        @php
                                            $docReceived = strcasecmp((string) $document->status, 'Received') === 0;
                                        @endphp
                                        <div
                                            class="rounded-xl border px-4 py-3 {{ !$segment['unlocked'] ? 'border-gray-200 bg-gray-100 text-gray-400' : ($docReceived ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50') }}">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p
                                                        class="text-sm font-semibold {{ !$segment['unlocked'] ? 'text-gray-500' : 'text-gray-900' }}">
                                                        {{ $document->document_type }}
                                                    </p>
                                                    <p class="text-xs mt-1">
                                                        {{ $document->description ?: ($document->required_for ?: 'No description') }}
                                                    </p>
                                                </div>
                                                <span
                                                    class="px-2 py-1 rounded-full text-[10px] font-bold uppercase border {{ !$segment['unlocked'] ? 'border-gray-300 bg-white text-gray-400' : ($docReceived ? 'border-green-200 bg-white text-green-700' : 'border-amber-200 bg-white text-amber-700') }}">
                                                    {{ !$segment['unlocked'] ? 'Locked' : $document->status }}
                                                </span>
                                            </div>
                                            <p
                                                class="text-[11px] mt-3 {{ !$segment['unlocked'] ? 'text-gray-400' : 'text-gray-500' }}">
                                                {{ $document->created_at ? $document->created_at->format('M d, Y') : '-' }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div
                                    class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-5 text-sm text-gray-400">
                                    No documents in this segment.
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
