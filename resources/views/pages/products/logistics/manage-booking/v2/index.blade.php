@extends('layouts.auth')

@section('title', 'Manage Bookings')

@section('content')
    @if (session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Success!</span> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Error!</span> {{ session('error') }}
        </div>
    @endif
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Header --}}
            {{-- <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Manage Bookings</h2>
                    <p class="text-gray-500 text-sm">Booking creation, carrier allocation, and documentation - <strong
                            class="text-green-500">{{ count($bookings) }} Active</strong></p>
                </div>
                <div class="flex items-center flex-wrap gap-2 mt-3 md:mt-0">
                    <a href="{{ route('logistics.manage-booking.v2.download-template') }}"
                        class="px-3 py-2 bg-white text-gray-700 border border-gray-300 text-xs font-medium rounded hover:bg-gray-50 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-download"></i> Template
                    </a>
                    <button onclick="openImportModal()"
                        class="px-3 py-2 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-file-excel"></i> Import
                    </button>
                    <button onclick="openApiSyncModal()"
                        class="px-3 py-2 bg-indigo-600 text-white text-xs font-medium rounded hover:bg-indigo-700 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-rotate text-[10px]"></i> UPDATE APIs
                    </button>
                    <x-common.export-dropdown name="bookings" />
                    <a href="{{ route('logistics.manage-booking.v2.create') }}"
                        class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Booking
                    </a>
                </div>
            </div> --}}

            {{-- Minimalist Header Design --}}
            <div class="bg-white rounded-xl border border-gray-200 mb-6">
                <div class="px-6 py-5">
                    {{-- Header Title --}}
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Manage Bookings</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Manage your logistics operations</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600">Active:</span>
                            <span
                                class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-md">{{ count($bookings) }}</span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100">
                        {{-- Spacer --}}

                        {{-- Primary Button --}}
                        <a href="{{ route('logistics.manage-booking.v2.create') }}"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium text-white bg-[#0078C8] rounded-lg hover:bg-[#006bb0]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            New Booking
                        </a>
                        <div class="flex-1"></div>

                        {{-- Left Buttons --}}
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('logistics.manage-booking.v2.download-template') }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                                <i class="fa-solid fa-download text-xs"></i> Template
                            </a>
                            <button onclick="openImportModal()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                                <i class="fa-solid fa-file-excel text-xs"></i> Import
                            </button>
                            <button onclick="openApiSyncModal()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                <i class="fa-solid fa-rotate text-xs"></i> Sync APIs
                            </button>
                            {{-- <x-common.export-dropdown name="bookings" /> --}}
                        </div>


                    </div>
                </div>
            </div>
            <div class="mb-6">
                <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                    @foreach (config('modules.manage-booking') as $module)
                        <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                            active="{{ Request::routeIs($module['is_active']) }}" />
                    @endforeach
                </div>
            </div>

            {{-- Action Alerts (pickup / delivery due today) --}}
            <div id="actionAlertsWrapper" class="mb-4 space-y-3"></div>

            {{-- Filters + Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden ">

                {{-- Filters --}}

                <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <x-common.filter-form action="{{ route('logistics.manage-booking.v2.index') }}">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Booking Type</label>
                            <select name="type"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Types</option>
                                <option value="import">Import</option>
                                <option value="export">Export</option>
                                <option value="cross_trade">Cross-Trade</option>
                                <option value="inland">Inland</option>
                                <option value="return_shipment">Return Shipment</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                            <select name="status"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Status</option>
                                <option value="Booked">Booked</option>
                                <option value="Pickup">Pickup</option>
                                <option value="In-Transit">In-Transit</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Draft">Draft</option>
                            </select>
                        </div>
                        <x-common.date-filter name="etd_from" id="etd_from" value="{{ request('etd_from') }}"
                            label="ETD From" />
                        <x-common.search-filter name="q" value="{{ request('q') }}" label="Search" id="search" />
                        <x-filter-button />
                    </x-common.filter-form>
                </div>

                {{-- Table --}}
                <div class="h-[calc(100vh-180px)] overflow-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Booking #</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Exporter / Importer
                                </th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Origin → Dest</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Mode</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Carrier</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">HS Code</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">ETD</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">ETA</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Status</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Value</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-right">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $statusConfig = [
                                    'Booked' => [
                                        'pill' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                                        'btn' => 'text-indigo-600 border-indigo-200 hover:bg-indigo-50',
                                        'label' => 'View',
                                    ],
                                    'Pickup' => [
                                        'pill' => 'bg-orange-50 text-orange-600 border-orange-200',
                                        'btn' => 'text-orange-600 border-orange-200 hover:bg-orange-50',
                                        'label' => 'View',
                                    ],
                                    'Pending Confirm' => [
                                        'pill' => 'bg-amber-50 text-amber-600 border-amber-200',
                                        'btn' => 'text-gray-700 border-gray-200 hover:bg-gray-50',
                                        'label' => 'View',
                                    ],
                                    'Confirmed' => [
                                        'pill' => 'bg-blue-50 text-blue-600 border-blue-200',
                                        'btn' => 'text-gray-700 border-gray-200 hover:bg-gray-50',
                                        'label' => 'View',
                                    ],
                                    'In-Transit' => [
                                        'pill' => 'bg-green-50 text-green-600 border-green-200',
                                        'btn' => 'text-[#0078C8] border-blue-200 hover:bg-blue-50',
                                        'label' => 'Track',
                                    ],
                                    'Delivered' => [
                                        'pill' => 'bg-teal-50 text-teal-600 border-teal-200',
                                        'btn' => 'text-gray-700 border-gray-200 hover:bg-gray-50',
                                        'label' => 'View',
                                    ],
                                    'Doc Missing' => [
                                        'pill' => 'bg-red-50 text-red-600 border-red-200',
                                        'btn' => 'text-[#0078C8] border-blue-200 hover:bg-blue-50',
                                        'label' => 'Upload',
                                    ],
                                    'Draft' => [
                                        'pill' => 'bg-gray-50 text-gray-500 border-gray-200',
                                        'btn' => 'text-gray-700 border-gray-200 hover:bg-gray-50',
                                        'label' => 'Edit',
                                    ],
                                ];
                                $modeIcon = [
                                    'fcl_sea' => '<i class="fa-solid fa-ship text-xs text-gray-400 mr-1"></i>',
                                    'lcl_sea' => '<i class="fa-solid fa-ship text-xs text-gray-400 mr-1"></i>',
                                    'air' => '<i class="fa-solid fa-plane text-xs text-gray-400 mr-1"></i>',
                                    'road_truck' => '<i class="fa-solid fa-truck text-xs text-gray-400 mr-1"></i>',
                                    'multimodal' => '<i class="fa-solid fa-route text-xs text-gray-400 mr-1"></i>',
                                ];
                                $modeLabel = [
                                    'fcl_sea' => 'FCL Sea',
                                    'lcl_sea' => 'LCL Sea',
                                    'air' => 'Air Freight',
                                    'road_truck' => 'Road / Truck',
                                    'multimodal' => 'Multimodal',
                                ];
                            @endphp

                            @forelse ($bookings as $booking)
                                @php
                                    $sc = $statusConfig[$booking->status] ?? [
                                        'pill' => 'bg-gray-50 text-gray-500 border-gray-200',
                                        'btn' => 'text-gray-700 border-gray-200 hover:bg-gray-50',
                                        'label' => 'View',
                                    ];
                                    $icon =
                                        $modeIcon[$booking->freight_mode] ??
                                        '<i class="fa-solid fa-truck text-xs text-gray-400 mr-1"></i>';
                                    $mode = $modeLabel[$booking->freight_mode] ?? $booking->freight_mode;
                                    $shipperName = $booking->exporter ?: $booking->shipper ?? '—';
                                    $consigneeName = $booking->importer ?: $booking->consignee ?? '—';
                                    $originDest =
                                        ($booking->origin_port ?: $booking->place_of_receipt ?? '—') .
                                        ' → ' .
                                        ($booking->destination_port ?: $booking->final_delivery_place ?? '—');
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-5 py-4 text-sm font-bold text-gray-900 whitespace-nowrap">
                                        {{ $booking->booking_number ?? $booking->id }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ $shipperName }}</span>
                                            <span class="text-xs text-gray-400">{{ $consigneeName }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-sm font-medium text-gray-800 whitespace-nowrap">
                                        {{ $originDest }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
                                        {!! $icon !!}{{ $mode }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
                                        {{ $booking->carrier?->carrier_company_name ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
                                        {{ $booking->hsCode?->hs_code ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
                                        {{ $booking->estimated_time_departure ? \Carbon\Carbon::parse($booking->estimated_time_departure)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
                                        {{ $booking->estimated_time_delivery ? \Carbon\Carbon::parse($booking->estimated_time_delivery)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $sc['pill'] }}">
                                            {{ $booking->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">
                                        {{ $booking->declared_cargo_value ? '$' . number_format((float) $booking->declared_cargo_value) : '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ $booking->status === 'In-Transit'
                                                ? route('logistics.manage-booking.v2.tracking', $booking->id)
                                                : route('logistics.manage-booking.v2.show', $booking->id) }}"
                                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors {{ $sc['btn'] }}">
                                                {{ $sc['label'] }}
                                            </a>
                                            <div class="relative inline-block" x-data="{ open: false }">
                                                <button @click="open = !open" @click.outside="open = false"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 transition-all">
                                                    <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                                                </button>
                                                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-100 z-20 overflow-hidden"
                                                    style="display:none;">
                                                    <a href="{{ route('logistics.manage-booking.v2.show', $booking->id) }}"
                                                        class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                        <i class="fa-solid fa-eye w-3.5 text-gray-400"></i> View Details
                                                    </a>
                                                    <a href="{{ route('logistics.manage-booking.v2.tracking', $booking->id) }}"
                                                        class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                        <i class="fa-solid fa-location-dot w-3.5 text-blue-500"></i> Track
                                                        Shipment
                                                    </a>
                                                    <a href="{{ route('logistics.manage-booking.v2.edit', $booking->id) }}"
                                                        class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                        <i class="fa-solid fa-pen w-3.5 text-gray-400"></i> Edit
                                                    </a>
                                                    <a href="{{ route('logistics.manage-booking.v2.child-booking', $booking->id) }}"
                                                        class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                        <i class="fa-solid fa-sitemap w-3.5 text-emerald-500"></i> Child
                                                        Bookings
                                                    </a>
                                                    @if ($booking->status === 'Booked')
                                                        <button
                                                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-orange-600 hover:bg-orange-50 transition-colors"
                                                            onclick="openCancelModal({{ $booking->id }}, '{{ $booking->booking_number ?? $booking->id }}')">
                                                            <i class="fa-solid fa-ban w-3.5"></i> Cancel
                                                        </button>
                                                    @endif
                                                    <button
                                                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-red-600 hover:bg-red-50 transition-colors"
                                                        onclick="confirmBookingDelete({{ $booking->id }}, '{{ route('logistics.manage-booking.v2.delete-summary', $booking->id) }}', '{{ route('logistics.manage-booking.v2.destroy', $booking->id) }}')">
                                                        <i class="fa-solid fa-trash-can w-3.5"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="11" class="px-5 py-12 text-center text-sm text-gray-400">
                                        <i class="fa-solid fa-box-open text-2xl mb-2 block text-gray-300"></i>
                                        No bookings found. <a href="{{ route('logistics.manage-booking.v2.create') }}"
                                            class="text-[#0078C8] hover:underline">Create the first one</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Showing <span
                            class="font-semibold text-gray-700">{{ $bookings->firstItem() }}–{{ $bookings->lastItem() }}</span>
                        of <span class="font-semibold text-gray-700">{{ $bookings->total() }}</span> bookings
                    </p>
                    {{ $bookings->withQueryString()->links() }}
                </div>

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL 1: Pickup / Delivery Action Modal
         Must be a direct child of <body>, NOT nested inside anything
    ═══════════════════════════════════════════════════════════ --}}
    <div id="actionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4">

            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                    <i id="actionModalIcon" class="fa-solid fa-check-circle text-green-500 text-xl"></i>
                </div>
                <div>
                    <h3 id="actionModalTitle" class="text-sm font-bold text-gray-900">Confirm Action</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Booking: <span id="actionModalBookingNum"
                            class="font-bold text-gray-700"></span></p>
                </div>
            </div>

            <div class="px-6 py-5 space-y-4">
                <div id="actionModalDateRow" class="hidden">
                    <label id="actionModalDateLabel" class="text-xs font-medium text-gray-600 block mb-1.5">New
                        Date</label>
                    <input type="date" id="actionModalNewDate"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-[#0078C8] transition">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Reason <span
                            class="text-red-500">*</span></label>
                    <select id="actionModalReasonSelect" onchange="toggleActionOtherReason(this.value)"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-[#0078C8] transition">
                    </select>
                </div>
                <div id="actionModalOtherWrapper" class="hidden">
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Custom Reason <span
                            class="text-red-500"></span></label>
                    <textarea id="actionModalReason" rows="3" placeholder="Enter custom reason..."
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-[#0078C8] transition resize-none"></textarea>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <button onclick="closeActionModal()"
                    class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="actionModalSubmitBtn" onclick="submitAction()"
                    class="px-4 py-2 text-xs font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-check text-[10px]"></i> Confirm
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL 5: Bulk Action Modal
    ═══════════════════════════════════════════════════════════ --}}
    <div id="bulkActionModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                    <i id="bulkActionModalIcon" class="fa-solid fa-layer-group text-blue-500 text-xl"></i>
                </div>
                <div>
                    <h3 id="bulkActionModalTitle" class="text-sm font-bold text-gray-900">Bulk Action</h3>
                    <p class="text-xs text-gray-500 mt-0.5"><span id="bulkActionCount"
                            class="font-bold text-gray-700"></span> items selected</p>
                </div>
            </div>

            <div class="px-6 py-5 space-y-4">
                <div id="bulkActionDateRow" class="hidden">
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">New Target Date for All <span
                            class="text-red-500">*</span></label>
                    <input type="date" id="bulkActionNewDate"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-[#0078C8] transition">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Shared Reason <span
                            class="text-red-500">*</span></label>
                    <select id="bulkActionReasonSelect" onchange="toggleBulkOtherReason(this.value)"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-[#0078C8] transition">
                    </select>
                </div>
                <div id="bulkActionOtherWrapper" class="hidden">
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Custom Reason <span
                            class="text-red-500">*</span></label>
                    <textarea id="bulkActionReason" rows="3" placeholder="Enter custom reason..."
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-[#0078C8] transition resize-none"></textarea>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <button onclick="closeBulkActionModal()"
                    class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="bulkActionSubmitBtn" onclick="submitBulkAction()"
                    class="px-4 py-2 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-check text-[10px]"></i> Process Bulk Action
                </button>
            </div>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════════
         MODAL 2: Booking Delete Confirmation Modal
         Also a direct child of <body>, separate from Modal 1
    ═══════════════════════════════════════════════════════════ --}}
    <div id="bookingDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4">

            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Delete Booking?</h3>
                    <p class="text-xs text-gray-500 mt-0.5">This action <strong>cannot be undone</strong></p>
                </div>
            </div>

            <div class="px-6 py-5 space-y-3">
                <p class="text-sm text-gray-700">
                    You are about to permanently delete booking
                    <span id="deleteBookingNumber" class="font-bold text-gray-900"></span>
                    and <strong class="text-red-600">all associated records</strong>:
                </p>

                <div id="deleteSummaryList"
                    class="bg-red-50 border border-red-100 rounded-lg px-4 py-3 space-y-1.5 text-xs text-red-700">
                    <div class="flex items-center gap-2"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
                </div>

                <p class="text-xs text-gray-500 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                    <i class="fa-solid fa-circle-info text-amber-500 mr-1"></i>
                    Records linked to Finance, Customs, and Trade Documents will also be removed.
                </p>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">
                        Type <span class="font-bold text-red-600" id="deleteConfirmHint"></span> to confirm
                    </label>
                    <input type="text" id="deleteConfirmInput" placeholder="Type booking number..."
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400 transition">
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <button onclick="closeBookingDeleteModal()"
                    class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="confirmDeleteBtn" onclick="executeBookingDelete()" disabled
                    class="px-4 py-2 text-xs font-medium text-white bg-red-500 rounded-lg transition-colors opacity-50 cursor-not-allowed flex items-center gap-2">
                    <i class="fa-solid fa-trash-can text-[10px]"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL 3: Import Excel Modal
    ═══════════════════════════════════════════════════════════ --}}
    <div id="importModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div
            class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-md mx-4 overflow-hidden transform transition-all">
            <form id="importBookingsForm" action="{{ route('logistics.manage-booking.v2.import') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="relative px-6 py-8 bg-gradient-to-br from-green-600 to-green-700 text-white">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 shadow-inner">
                            <i class="fa-solid fa-file-excel text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Import Bookings</h3>
                            <p class="text-green-100 text-xs opacity-90">Upload your Excel or CSV data file</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeImportModal()"
                        class="absolute top-4 right-4 text-white/50 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="px-8 py-8 space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Data
                                Source</label>
                            <a href="{{ route('logistics.manage-booking.v2.download-template') }}"
                                class="text-[10px] font-bold text-green-600 hover:text-green-700 underline uppercase tracking-tighter">Download
                                Template</a>
                        </div>

                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i
                                    class="fa-solid fa-paperclip text-gray-400 group-focus-within:text-green-500 transition-colors"></i>
                            </div>
                            <input type="file" name="file" id="importFileInput" accept=".xlsx,.csv,.xls" required
                                class="block w-full pl-10 pr-3 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-xl focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all file:hidden cursor-pointer bg-gray-50/50 hover:bg-gray-50"
                                onchange="updateFileName(this)">
                            <div id="fileStatusLabel" class="mt-2 text-[10px] text-gray-400 italic">No file selected</div>
                        </div>
                    </div>

                    {{-- Progress Bar Container --}}
                    <div id="importProgressWrapper" class="hidden space-y-2">
                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider">
                            <span class="text-gray-500" id="progressStatusText">Uploading Data...</span>
                            <span class="text-green-600" id="progressPercentage">0%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="importProgressBar"
                                class="bg-gradient-to-r from-green-500 to-green-400 h-full w-0 transition-all duration-300 ease-out">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeImportModal()" id="importCancelBtn"
                        class="px-5 py-2.5 text-xs font-bold text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-all">
                        Cancel
                    </button>
                    <button type="submit" id="importSubmitBtn"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-lg shadow-green-600/20 active:scale-95 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-[10px]"></i>
                        <span id="importBtnText">Import Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL 4: Cancel Booking Modal
    ═══════════════════════════════════════════════════════════ --}}
    <div id="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4 overflow-hidden">
            <form id="cancelBookingForm" method="POST">
                @csrf
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-ban text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Cancel Booking</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Booking: <span id="cancelModalBookingNum"
                                class="font-bold text-gray-700"></span></p>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Reason for Cancellation <span
                                class="text-red-500">*</span></label>
                        <select name="cancel_reason" id="cancel_reason_select" required
                            onchange="toggleOtherReason(this.value)"
                            class="w-full text-sm text-gray-700 border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all">
                            <option value="">Select a reason...</option>
                            @foreach ($cancelReasons as $reason)
                                <option value="{{ $reason->reason_text }}">{{ $reason->reason_text }}</option>
                            @endforeach
                            <option value="Other">Other (Write Custom Reason)</option>
                        </select>
                    </div>

                    <div id="other_reason_wrapper" class="hidden">
                        <label class="block text-xs font-medium text-gray-700 mb-2">Custom Reason <span
                                class="text-red-500">*</span></label>
                        <textarea name="other_reason" id="other_reason" rows="3"
                            placeholder="Enter the custom reason why this booking is being cancelled..."
                            class="w-full text-sm text-gray-700 border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all resize-none"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeCancelModal()"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Close
                    </button>
                    <button type="submit" id="cancelModalSubmitBtn"
                        class="px-4 py-2 text-xs font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-ban text-[10px]"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- API Sync Modal -->
    <div id="apiSyncModal" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title"
        role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true"
                onclick="closeApiSyncModal()"></div>

            <div
                class="relative inline-block align-middle bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full border border-gray-100">
                <div class="bg-linear-to-r from-indigo-600 to-blue-600 px-6 py-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-md shadow-inner">
                            <i class="fa-solid fa-rotate text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white leading-tight">API Synchronization</h3>
                            <p class="text-indigo-100 text-xs font-medium opacity-80 mt-0.5">Maersk & FedEx Integration</p>
                        </div>
                    </div>
                    <button onclick="closeApiSyncModal()"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-black/10 text-white hover:bg-black/20 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-8">
                    <!-- Initial State -->
                    <div id="apiSyncInitial" class="text-center py-10">
                        <div
                            class="w-24 h-24 bg-indigo-50 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                            <i class="fa-solid fa-cloud-arrow-down text-4xl text-indigo-500"></i>
                        </div>
                        <h4 class="text-gray-900 font-bold text-xl mb-3">Ready to Synchronize?</h4>
                        <p class="text-gray-500 text-sm max-w-sm mx-auto mb-8 leading-relaxed">Fetch the latest tracking
                            milestones and document updates from carrier systems for all active bookings.</p>
                        <button onclick="startApiSync()"
                            class="px-10 py-4 bg-indigo-600 text-white font-bold rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20 flex items-center justify-center gap-3 mx-auto active:scale-95">
                            <i class="fa-solid fa-play"></i> START SYNC PROCESS
                        </button>
                    </div>

                    <!-- Progress State -->
                    <div id="apiSyncProgressWrapper" class="hidden">
                        <div class="mb-8 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="flex justify-between items-end mb-3">
                                <div>
                                    <p id="syncStatusText" class="text-sm font-bold text-gray-900">Connecting...</p>
                                    <p id="syncDetailText" class="text-[11px] text-gray-500 mt-1 italic">Establishing
                                        secure API session</p>
                                </div>
                                <span id="syncProgressPercent"
                                    class="text-3xl font-black text-indigo-600 tabular-nums">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden p-0.5 shadow-inner">
                                <div id="syncProgressBar"
                                    class="bg-linear-to-r from-indigo-600 to-blue-500 h-full rounded-full transition-all duration-700 ease-out shadow-sm"
                                    style="width: 0%"></div>
                            </div>
                        </div>

                        <div id="apiSyncResults" class="space-y-4 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                            <!-- Results will be injected here -->
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-8 py-5 flex justify-between items-center border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></div>
                        <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">Live System Sync</p>
                    </div>
                    <button onclick="closeApiSyncModal()" id="syncCloseBtn"
                        class="px-6 py-2.5 text-xs font-bold text-gray-500 hover:text-indigo-600 hover:bg-white rounded-xl border border-transparent hover:border-gray-200 transition-all">CLOSE</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>
@endsection


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ─── Shared state ─────────────────────────────────────────────────────────────
        let _actionBookingId = null;
        let _actionType = null;
        let _actionMode = null;
        let _actionChildBookingNumber = null;
        let _deleteUrl = null;
        let _bookingNumber = null;
        let _pendingPickups = [];
        let _pendingDeliveries = [];
        let _pendingChildArrivals = [];
        let _pendingChildDeliveries = [];
        let _bulkActionMode = null;

        const _confirmReasons = @json($confirmReasons->pluck('reason_text'));
        const _rescheduleReasons = @json($rescheduleReasons->pluck('reason_text'));

        // ─── On load ──────────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            loadPendingActionAlerts();
        });

        // ─── Pending action alerts ────────────────────────────────────────────────────
        function loadPendingActionAlerts() {
            fetch('{{ route('logistics.manage-booking.v2.pending-actions') }}')
                .then(r => r.json())
                .then(data => {
                    _pendingPickups = data.pickup_due;
                    _pendingDeliveries = data.delivery_due;
                    _pendingChildArrivals = data.child_arrival_due || [];
                    _pendingChildDeliveries = data.child_delivery_due || [];
                    const _legActions = data.leg_actions || [];

                    const wrapper = document.getElementById('actionAlertsWrapper');
                    wrapper.innerHTML = '';

                    const totalPending = _pendingPickups.length + _pendingDeliveries.length + _pendingChildArrivals.length +
                        _pendingChildDeliveries.length + _legActions.length;

                    if (totalPending > 0) {
                        wrapper.insertAdjacentHTML('beforeend', `
                            <div class="flex items-center justify-between mb-3 px-1">
                                <span class="text-sm font-semibold text-gray-700">Pending Actions (<span class="font-bold">${totalPending}</span>)</span>
                                <div class="flex gap-2">
                                    <button onclick="openBulkActionModal('approve')" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-500 hover:bg-green-600 rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-[10px]"></i> Bulk Approve All
                                    </button>
                                    <button onclick="openBulkActionModal('reject')" class="px-3 py-1.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
                                        <i class="fa-solid fa-calendar-xmark text-[10px]"></i> Bulk Reschedule All
                                    </button>
                                </div>
                            </div>
                        `);
                    }

                    _pendingPickups.forEach(b => {
                        wrapper.insertAdjacentHTML('beforeend', `
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5 bg-orange-50 border border-orange-200 rounded-lg mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-truck-pickup text-orange-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-orange-800">Pickup Due Today — <span class="font-bold">${b.booking_number}</span></p>
                                <p class="text-xs text-orange-600 mt-0.5">${b.shipper ?? ''} · ${b.origin_port} → ${b.destination_port}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button onclick="openActionModal(${b.id}, 'pickup', 'approve', '${b.booking_number}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-[10px]"></i> Approve
                            </button>
                            <button onclick="openActionModal(${b.id}, 'pickup', 'reject', '${b.booking_number}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i> Reschedule
                            </button>
                        </div>
                    </div>
                `);
                    });

                    _pendingDeliveries.forEach(b => {
                        wrapper.insertAdjacentHTML('beforeend', `
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5 bg-teal-50 border border-teal-200 rounded-lg mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-box-open text-teal-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-teal-800">Delivery Due — <span class="font-bold">${b.booking_number}</span></p>
                                <p class="text-xs text-teal-600 mt-0.5">${b.consignee ?? ''} · ${b.origin_port} → ${b.destination_port}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button onclick="openActionModal(${b.id}, 'delivery', 'approve', '${b.booking_number}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-teal-500 hover:bg-teal-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-[10px]"></i> Confirm Delivered
                            </button>
                            <button onclick="openActionModal(${b.id}, 'delivery', 'reject', '${b.booking_number}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i> Reschedule
                            </button>
                        </div>
                    </div>
                `);
                    });

                    _pendingChildArrivals.forEach(b => {
                        wrapper.insertAdjacentHTML('beforeend', `
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5 bg-sky-50 border border-sky-200 rounded-lg mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-location-dot text-sky-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-sky-800">Child Arrival Due — <span class="font-bold">${b.child_booking_number}</span></p>
                                <p class="text-xs text-sky-600 mt-0.5">Parent: ${b.parent_booking_number} · ${b.origin_port} → ${b.destination_port}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button onclick="openActionModal(${b.parent_id}, 'child_arrival', 'approve', '${b.parent_booking_number}', '${b.child_booking_number}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-sky-500 hover:bg-sky-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-[10px]"></i> Confirm Arrival
                            </button>
                            <button onclick="openActionModal(${b.parent_id}, 'child_arrival', 'reject', '${b.parent_booking_number}', '${b.child_booking_number}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i> Reschedule
                            </button>
                        </div>
                    </div>
                `);
                    });

                    _pendingChildDeliveries.forEach(b => {
                        wrapper.insertAdjacentHTML('beforeend', `
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5 bg-fuchsia-50 border border-fuchsia-200 rounded-lg mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-fuchsia-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-box-open text-fuchsia-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-fuchsia-800">Child Delivery Due — <span class="font-bold">${b.child_booking_number}</span></p>
                                <p class="text-xs text-fuchsia-600 mt-0.5">Parent: ${b.parent_booking_number} · ${b.origin_port} → ${b.destination_port}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button onclick="openActionModal(${b.parent_id}, 'child_delivery', 'approve', '${b.parent_booking_number}', '${b.child_booking_number}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-fuchsia-500 hover:bg-fuchsia-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-[10px]"></i> Confirm Delivered
                            </button>
                            <button onclick="openActionModal(${b.parent_id}, 'child_delivery', 'reject', '${b.parent_booking_number}', '${b.child_booking_number}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i> Reschedule
                            </button>
                        </div>
                    </div>
                `);
                    });

                    _legActions.forEach(leg => {
                        wrapper.insertAdjacentHTML('beforeend', `
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5 bg-blue-50 border border-blue-200 rounded-lg mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-route text-blue-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-800">Leg Timing Confirmation — <span class="font-bold">${leg.booking?.booking_id ?? 'N/A'}</span></p>
                                <p class="text-xs text-blue-600 mt-0.5">${leg.origin} → ${leg.destination} · ETD: ${leg.etd} · ETA: ${leg.eta}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button onclick="openLegActionModal(${leg.id}, 'approve', '${leg.booking?.booking_id}', '${leg.origin} → ${leg.destination}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-[10px]"></i> Accept
                            </button>
                            <button onclick="openLegActionModal(${leg.id}, 'reject', '${leg.booking?.booking_id}', '${leg.origin} → ${leg.destination}')"
                                class="px-3 py-1.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-xmark text-[10px]"></i> Reject
                            </button>
                        </div>
                    </div>
                `);
                    });
                })
                .catch(err => console.error('Pending actions error:', err));
        }

        function openLegActionModal(legId, mode, bookingId, route) {
            const title = mode === 'approve' ? 'Accept Leg Timing' : 'Reject & Reschedule Leg';
            const text = mode === 'approve' ?
                `Confirm timing for ${route} (Booking: ${bookingId})?` :
                `Reschedule timing for ${route} (Booking: ${bookingId})`;
            const icon = mode === 'approve' ? 'question' : 'warning';
            const confirmBtn = mode === 'approve' ? 'Accept & Confirm' : 'Next: Set New Date';

            const html = mode === 'approve' ? `
                <div class="text-left space-y-3 px-1">
                    <p class="text-sm text-gray-600 font-medium">Update Status:</p>
                    <select id="swal_leg_status" class="swal2-input !mt-0 !w-full !text-sm">
                        <option value="Pickup">Pickup</option>
                        <option value="In-Transit">In-Transit</option>
                        <option value="Delivered">Delivered</option>
                    </select>
                    <p class="text-sm text-gray-600 font-medium mt-3">Reason / Notes:</p>
                    <textarea id="swal_leg_reason" class="swal2-textarea !mt-0 !w-full !text-sm" placeholder="Enter reason/notes..."></textarea>
                </div>
            ` : null;

            Swal.fire({
                title: title,
                text: mode === 'approve' ? null : text,
                html: html,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: mode === 'approve' ? '#10b981' : '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: confirmBtn,
                preConfirm: () => {
                    if (mode === 'approve') {
                        const status = document.getElementById('swal_leg_status').value;
                        const reason = document.getElementById('swal_leg_reason').value;
                        if (!reason) {
                            Swal.showValidationMessage('You need to provide a reason!');
                            return false;
                        }
                        return {
                            status: status,
                            reason: reason
                        };
                    } else {
                        // For reject, we still need the reason first?
                        // The original logic used 'input: textarea'. Let's adapt.
                        return Swal.fire({
                            title: 'Reason Required',
                            input: 'textarea',
                            inputPlaceholder: 'Why is this being rescheduled?',
                            inputValidator: (v) => !v && 'Reason is required!'
                        }).then(r => r.value);
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (mode === 'approve') {
                        submitLegAction(legId, 'approve', {
                            status: result.value.status,
                            reason: result.value.reason
                        });
                    } else {
                        const reason = result.value;
                        Swal.fire({
                            title: 'Reschedule Leg',
                            html: `
                                    <p class="text-sm text-gray-600 font-medium">Update Status:</p>
                                    <select id="swal_leg_status_reject" class="swal2-input !mt-0 !w-full !text-sm">
                                        <option value="Pending">Pending</option>
                                        <option value="Pickup">Pickup</option>
                                        <option value="In-Transit">In-Transit</option>
                                        <option value="Delivered">Delivered</option>
                                    </select>
                                    <p class="text-sm text-gray-600 font-medium mt-3">Select which date to update:</p>
                                    <select id="swal_date_type" class="swal2-input !mt-0 !w-full !text-sm">
                                        <option value="eta">ETA (Arrival)</option>
                                        <option value="etd">ETD (Departure)</option>
                                        <option value="estimated_delivery_date">Estimated Delivery</option>
                                    </select>
                                    <p class="text-sm text-gray-600 font-medium">New Date:</p>
                                    <input type="date" id="swal_new_date" class="swal2-input !mt-0 !w-full !text-sm" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                            `,
                            focusConfirm: false,
                            showCancelButton: true,
                            confirmButtonText: 'Reschedule Now',
                            preConfirm: () => {
                                return {
                                    status: document.getElementById('swal_leg_status_reject').value,
                                    date_type: document.getElementById('swal_date_type').value,
                                    new_date: document.getElementById('swal_new_date').value
                                }
                            }
                        }).then((dateResult) => {
                            if (dateResult.isConfirmed) {
                                submitLegAction(legId, 'reject', {
                                    reason: reason,
                                    status: dateResult.value.status,
                                    date_type: dateResult.value.date_type,
                                    new_date: dateResult.value.new_date
                                });
                            }
                        });
                    }
                }
            });
        }

        function submitLegAction(legId, mode, data) {
            const url = mode === 'approve' ?
                `/logistics/manage-booking/v2/legs/${legId}/approve-timing` :
                `/logistics/manage-booking/v2/legs/${legId}/reject-timing`;

            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire('Success!', res.message, 'success').then(() => {
                            loadPendingActionAlerts();
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message || 'Something went wrong', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Communication error with server', 'error');
                });
        }

        // ─── Action Modal ─────────────────────────────────────────────────────────────
        function openActionModal(id, type, mode, bookingNumber, childBookingNumber = null) {
            _actionBookingId = id;
            _actionType = type;
            _actionMode = mode;
            _actionChildBookingNumber = childBookingNumber;

            const isApprove = mode === 'approve';
            const isPickup = type === 'pickup';
            const isChildArrival = type === 'child_arrival';
            const isChildDelivery = type === 'child_delivery';

            document.getElementById('actionModalTitle').textContent = isApprove ?
                (isPickup ? 'Confirm Pickup' : (isChildArrival ? 'Confirm Child Arrival' : (isChildDelivery ?
                    'Confirm Child Delivery' : 'Confirm Delivery'))) :
                (isPickup ? 'Reschedule Pickup' : (isChildArrival ? 'Reschedule Child Arrival' : (
                    isChildDelivery ? 'Reschedule Child Delivery' : 'Reschedule Delivery')));

            document.getElementById('actionModalIcon').className = isApprove ?
                'fa-solid fa-check-circle text-green-500 text-xl' :
                'fa-solid fa-calendar-xmark text-red-500 text-xl';

            document.getElementById('actionModalBookingNum').textContent = childBookingNumber ?? bookingNumber;

            // Populate reasons
            const select = document.getElementById('actionModalReasonSelect');
            select.innerHTML = '<option value="">Select a reason...</option>';
            const reasonsList = isApprove ? _confirmReasons : _rescheduleReasons;
            reasonsList.forEach(r => {
                select.insertAdjacentHTML('beforeend', `<option value="${r}">${r}</option>`);
            });
            select.insertAdjacentHTML('beforeend', '<option value="Other">Other (Write Custom Reason)</option>');

            document.getElementById('actionModalReason').value = '';
            document.getElementById('actionModalOtherWrapper').classList.add('hidden');
            document.getElementById('actionModalReason').required = false;

            const dateRow = document.getElementById('actionModalDateRow');
            if (!isApprove) {
                dateRow.classList.remove('hidden');
                document.getElementById('actionModalDateLabel').textContent = isPickup ? 'New Pickup Date' :
                    (isChildArrival ? 'New Arrival Date' : 'New Delivery Date');
                const di = document.getElementById('actionModalNewDate');
                di.min = new Date().toISOString().split('T')[0];
                di.value = '';
            } else {
                dateRow.classList.add('hidden');
            }

            const btn = document.getElementById('actionModalSubmitBtn');
            if (isApprove) {
                btn.className =
                    'px-4 py-2 text-xs font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors flex items-center gap-2';
                btn.innerHTML = isPickup ?
                    '<i class="fa-solid fa-check text-[10px]"></i> Approve Pickup' :
                    (isChildArrival ? '<i class="fa-solid fa-check text-[10px]"></i> Confirm Arrival' :
                        '<i class="fa-solid fa-check text-[10px]"></i> Confirm Delivered');
            } else {
                btn.className =
                    'px-4 py-2 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-2';
                btn.innerHTML = '<i class="fa-solid fa-calendar-alt text-[10px]"></i> Reschedule';
            }

            document.getElementById('actionModal').classList.remove('hidden');
        }

        function closeActionModal() {
            document.getElementById('actionModal').classList.add('hidden');
            _actionBookingId = null;
            _actionType = null;
            _actionMode = null;
            _actionChildBookingNumber = null;
        }

        document.getElementById('actionModal').addEventListener('click', function(e) {
            if (e.target === this) closeActionModal();
        });

        function submitAction() {
            let reason = document.getElementById('actionModalReasonSelect').value;
            if (reason === 'Other') {
                reason = document.getElementById('actionModalReason').value.trim();
            }

            const isApprove = _actionMode === 'approve';
            const isPickup = _actionType === 'pickup';
            const isChildArrival = _actionType === 'child_arrival';
            const isChildDelivery = _actionType === 'child_delivery';
            const body = {
                reason
            };
            let url;

            if (_actionChildBookingNumber) {
                body.child_booking_number = _actionChildBookingNumber;
            }

            if (isApprove) {
                if (isPickup) {
                    url = `/logistics/manage-booking/v2/${_actionBookingId}/approve-pickup`;
                } else if (isChildArrival) {
                    url = `/logistics/manage-booking/v2/${_actionBookingId}/child-actions/approve-arrival`;
                } else if (isChildDelivery) {
                    url = `/logistics/manage-booking/v2/${_actionBookingId}/child-actions/approve-delivery`;
                } else {
                    url = `/logistics/manage-booking/v2/${_actionBookingId}/approve-delivery`;
                }
            } else {
                const newDate = document.getElementById('actionModalNewDate').value;
                if (!newDate) {
                    alert('Please select a new date.');
                    return;
                }
                if (isPickup) {
                    body.new_pickup_date = newDate;
                    url = `/logistics/manage-booking/v2/${_actionBookingId}/reject-pickup`;
                } else if (isChildArrival) {
                    body.new_arrival_date = newDate;
                    url = `/logistics/manage-booking/v2/${_actionBookingId}/child-actions/reject-arrival`;
                } else if (isChildDelivery) {
                    body.new_delivery_date = newDate;
                    url = `/logistics/manage-booking/v2/${_actionBookingId}/child-actions/reject-delivery`;
                } else {
                    body.new_delivery_date = newDate;
                    url = `/logistics/manage-booking/v2/${_actionBookingId}/reject-delivery`;
                }
            }

            const btn = document.getElementById('actionModalSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Processing...';

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeActionModal();
                        window.location.reload();
                    } else {
                        alert(data.message ?? 'Action failed.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i> Confirm';
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i> Confirm';
                });
        }

        // ─── Bulk Action Modal ─────────────────────────────────────────────────────────────
        function openBulkActionModal(mode) {
            _bulkActionMode = mode;
            const totalPending = _pendingPickups.length + _pendingDeliveries.length + _pendingChildArrivals.length +
                _pendingChildDeliveries.length;

            if (totalPending === 0) {
                alert('No pending actions available to process.');
                return;
            }

            document.getElementById('bulkActionCount').textContent = totalPending;

            const isApprove = mode === 'approve';

            // Populate reasons
            const select = document.getElementById('bulkActionReasonSelect');
            select.innerHTML = '<option value="">Select a reason...</option>';
            const reasonsList = isApprove ? _confirmReasons : _rescheduleReasons;
            reasonsList.forEach(r => {
                select.insertAdjacentHTML('beforeend', `<option value="${r}">${r}</option>`);
            });
            select.insertAdjacentHTML('beforeend', '<option value="Other">Other (Write Custom Reason)</option>');

            document.getElementById('bulkActionReason').value = '';
            document.getElementById('bulkActionOtherWrapper').classList.add('hidden');
            document.getElementById('bulkActionReason').required = false;
            const dateRow = document.getElementById('bulkActionDateRow');

            if (isApprove) {
                document.getElementById('bulkActionModalTitle').textContent = 'Bulk Approve Pending Actions';
                document.getElementById('bulkActionModalIcon').className =
                    'fa-solid fa-check-double text-green-500 text-xl';
                document.getElementById('bulkActionModalIcon').parentElement.className =
                    'w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0';
                dateRow.classList.add('hidden');

                const btn = document.getElementById('bulkActionSubmitBtn');
                btn.className =
                    'px-4 py-2 text-xs font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors flex items-center gap-2';
                btn.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i> Approve All';
            } else {
                document.getElementById('bulkActionModalTitle').textContent = 'Bulk Reschedule Pending Actions';
                document.getElementById('bulkActionModalIcon').className = 'fa-solid fa-calendar-days text-red-500 text-xl';
                document.getElementById('bulkActionModalIcon').parentElement.className =
                    'w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0';
                dateRow.classList.remove('hidden');

                const di = document.getElementById('bulkActionNewDate');
                di.min = new Date().toISOString().split('T')[0];
                di.value = '';

                const btn = document.getElementById('bulkActionSubmitBtn');
                btn.className =
                    'px-4 py-2 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-2';
                btn.innerHTML = '<i class="fa-solid fa-calendar-alt text-[10px]"></i> Reschedule All';
            }

            document.getElementById('bulkActionModal').classList.remove('hidden');
        }

        function closeBulkActionModal() {
            document.getElementById('bulkActionModal').classList.add('hidden');
            _bulkActionMode = null;
        }

        document.getElementById('bulkActionModal').addEventListener('click', function(e) {
            if (e.target === this) closeBulkActionModal();
        });

        function submitBulkAction() {
            let reason = document.getElementById('bulkActionReasonSelect').value;
            if (reason === 'Other') {
                reason = document.getElementById('bulkActionReason').value.trim();
            }

            if (!reason) {
                alert('Please provide a reason.');
                return;
            }

            const isApprove = _bulkActionMode === 'approve';
            let newDate = null;

            if (!isApprove) {
                newDate = document.getElementById('bulkActionNewDate').value;
                if (!newDate) {
                    alert('Please select a new target date.');
                    return;
                }
            }

            const items = [];
            _pendingPickups.forEach(p => items.push({
                id: p.id,
                type: 'pickup'
            }));
            _pendingDeliveries.forEach(d => items.push({
                id: d.id,
                type: 'delivery'
            }));
            _pendingChildArrivals.forEach(c => items.push({
                id: c.parent_id,
                type: 'child_arrival',
                child_booking_number: c.child_booking_number
            }));
            _pendingChildDeliveries.forEach(c => items.push({
                id: c.parent_id,
                type: 'child_delivery',
                child_booking_number: c.child_booking_number
            }));

            const body = {
                action_type: isApprove ? 'approve' : 'reject',
                reason: reason,
                items: items
            };

            if (!isApprove) {
                body.new_date = newDate;
            }

            const btn = document.getElementById('bulkActionSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Processing...';

            fetch('{{ route('logistics.manage-booking.v2.bulk-action') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeBulkActionModal();
                        window.location.reload();
                    } else {
                        alert(data.message ?? 'Bulk action failed.');
                        btn.disabled = false;
                        btn.innerHTML = isApprove ? '<i class="fa-solid fa-check text-[10px]"></i> Approve All' :
                            '<i class="fa-solid fa-calendar-alt text-[10px]"></i> Reschedule All';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred. Check console for details.');
                    btn.disabled = false;
                    btn.innerHTML = isApprove ? '<i class="fa-solid fa-check text-[10px]"></i> Approve All' :
                        '<i class="fa-solid fa-calendar-alt text-[10px]"></i> Reschedule All';
                });
        }


        // ─── Delete Modal ─────────────────────────────────────────────────────────────
        function confirmBookingDelete(id, summaryUrl, deleteUrl) {
            _deleteUrl = deleteUrl;
            _bookingNumber = null;
            document.getElementById('deleteConfirmInput').value = '';
            document.getElementById('confirmDeleteBtn').disabled = true;
            document.getElementById('confirmDeleteBtn').classList.add('opacity-50', 'cursor-not-allowed');
            document.getElementById('deleteSummaryList').innerHTML =
                '<div class="flex items-center gap-2"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
            document.getElementById('bookingDeleteModal').classList.remove('hidden');

            fetch(summaryUrl, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(async r => {
                    const contentType = r.headers.get('content-type') || '';
                    const data = contentType.includes('application/json') ? await r.json() : null;

                    if (!r.ok) {
                        throw new Error(data?.message ?? 'Failed to load delete summary.');
                    }

                    return data;
                })
                .then(data => {
                    _bookingNumber = data.booking_number;
                    document.getElementById('deleteBookingNumber').textContent = data.booking_number;
                    document.getElementById('deleteConfirmHint').textContent = data.booking_number;

                    const items = [{
                            icon: 'fa-list-check',
                            label: 'Shipment Stages',
                            count: data.stages
                        },
                        {
                            icon: 'fa-file-lines',
                            label: 'Trade Documents',
                            count: data.documents
                        },
                        {
                            icon: 'fa-ship',
                            label: 'Shipments',
                            count: data.shipments
                        },
                        {
                            icon: 'fa-stamp',
                            label: 'Goods Declarations',
                            count: data.goods_decls
                        },
                    ];

                    document.getElementById('deleteSummaryList').innerHTML = items.map(item =>
                        `<div class="flex items-center justify-between">
                <span class="flex items-center gap-2"><i class="fa-solid ${item.icon} w-3.5 opacity-70"></i> ${item.label}</span>
                <span class="font-bold">${item.count} record${item.count !== 1 ? 's' : ''}</span>
            </div>`
                    ).join('');
                })
                .catch(err => {
                    console.error('Delete summary error:', err);
                    document.getElementById('deleteSummaryList').innerHTML =
                        `<div class="text-red-600 text-sm">${err.message}</div>`;
                });
        }

        function closeBookingDeleteModal() {
            document.getElementById('bookingDeleteModal').classList.add('hidden');
            _deleteUrl = null;
            _bookingNumber = null;
        }

        document.getElementById('deleteConfirmInput').addEventListener('input', function() {
            const match = this.value.trim().toUpperCase() === (_bookingNumber ?? '').toUpperCase();
            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = !match;
            btn.classList.toggle('opacity-50', !match);
            btn.classList.toggle('cursor-not-allowed', !match);
        });

        function executeBookingDelete() {
            if (!_deleteUrl) return;
            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Deleting...';

            fetch(_deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(async r => {
                    const contentType = r.headers.get('content-type') || '';
                    const data = contentType.includes('application/json') ? await r.json() : null;

                    if (!r.ok) {
                        throw new Error(data?.message ?? 'Delete failed.');
                    }

                    return data;
                })
                .then(data => {
                    if (data.success) {
                        closeBookingDeleteModal();
                        window.location.reload();
                    } else {
                        alert(data.message ?? 'Delete failed.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-trash-can text-[10px]"></i> Delete Permanently';
                    }
                })
                .catch(err => {
                    console.error('Delete booking error:', err);
                    alert(err.message ?? 'Delete failed.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-trash-can text-[10px]"></i> Delete Permanently';
                });
        }

        document.getElementById('bookingDeleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeBookingDeleteModal();
        });

        function toggleActionOtherReason(val) {
            const wrapper = document.getElementById('actionModalOtherWrapper');
            const textarea = document.getElementById('actionModalReason');
            if (val === 'Other') {
                wrapper.classList.remove('hidden');
                textarea.required = true;
                textarea.focus();
            } else {
                wrapper.classList.add('hidden');
                textarea.required = false;
            }
        }

        function toggleBulkOtherReason(val) {
            const wrapper = document.getElementById('bulkActionOtherWrapper');
            const textarea = document.getElementById('bulkActionReason');
            if (val === 'Other') {
                wrapper.classList.remove('hidden');
                textarea.required = true;
                textarea.focus();
            } else {
                wrapper.classList.add('hidden');
                textarea.required = false;
            }
        }

        // ─── Import Modal ─────────────────────────────────────────────────────────────
        function openImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
            document.getElementById('importProgressWrapper').classList.add('hidden');
            document.getElementById('importSubmitBtn').disabled = false;
            document.getElementById('importCancelBtn').disabled = false;
            document.getElementById('importFileInput').value = '';
            document.getElementById('fileStatusLabel').textContent = 'No file selected';
            document.getElementById('fileStatusLabel').className = 'mt-2 text-[10px] text-gray-400 italic';
            document.getElementById('importProgressBar').style.width = '0%';
            document.getElementById('progressPercentage').textContent = '0%';
        }

        function closeImportModal() {
            // Prevent closing if uploading
            if (document.getElementById('importSubmitBtn').disabled && !document.getElementById('importProgressWrapper')
                .classList.contains('hidden')) {
                return;
            }
            document.getElementById('importModal').classList.add('hidden');
        }

        function updateFileName(input) {
            const label = document.getElementById('fileStatusLabel');
            if (input.files && input.files[0]) {
                label.textContent = `Selected: ${input.files[0].name}`;
                label.className = 'mt-2 text-[10px] text-green-600 font-bold';
            } else {
                label.textContent = 'No file selected';
                label.className = 'mt-2 text-[10px] text-gray-400 italic';
            }
        }

        document.getElementById('importBookingsForm').addEventListener('submit', function(e) {
            // We use standard submission but show a fake progress bar for UX if it's small,
            // or we could use AJAX. Given the request, let's use a "processing" state with a nice animation.

            const btn = document.getElementById('importSubmitBtn');
            const cancelBtn = document.getElementById('importCancelBtn');
            const progressWrapper = document.getElementById('importProgressWrapper');
            const progressBar = document.getElementById('importProgressBar');
            const progressPercent = document.getElementById('progressPercentage');
            const statusText = document.getElementById('progressStatusText');
            const btnText = document.getElementById('importBtnText');

            btn.disabled = true;
            cancelBtn.disabled = true;
            progressWrapper.classList.remove('hidden');
            btnText.textContent = 'Processing...';
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Processing...';

            // Fake progress animation for standard form submission
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress > 95) {
                    progress = 95;
                    statusText.textContent = 'Finalizing Import...';
                    clearInterval(interval);
                }
                progressBar.style.width = progress + '%';
                progressPercent.textContent = Math.round(progress) + '%';
            }, 300);
        });

        document.getElementById('importModal').addEventListener('click', function(e) {
            if (e.target === this) closeImportModal();
        });

        // ─── Cancel Modal ─────────────────────────────────────────────────────────────
        function openCancelModal(id, bookingNumber) {
            const form = document.getElementById('cancelBookingForm');
            form.action = `/logistics/manage-booking/v2/${id}/cancel`;
            document.getElementById('cancelModalBookingNum').textContent = bookingNumber;

            document.getElementById('cancel_reason_select').value = '';
            document.getElementById('other_reason').value = '';
            document.getElementById('other_reason_wrapper').classList.add('hidden');
            document.getElementById('other_reason').required = false;

            // Reset button state
            const btn = document.getElementById('cancelModalSubmitBtn');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-ban text-[10px]"></i> Confirm Cancellation';

            document.getElementById('cancelModal').classList.remove('hidden');
        }

        document.getElementById('cancelBookingForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('cancelModalSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Processing...';
        });

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }

        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) closeCancelModal();
        });

        function toggleOtherReason(val) {
            const wrapper = document.getElementById('other_reason_wrapper');
            const textarea = document.getElementById('other_reason');
            if (val === 'Other') {
                wrapper.classList.remove('hidden');
                textarea.required = true;
                textarea.focus();
            } else {
                wrapper.classList.add('hidden');
                textarea.required = false;
            }
        }

        // ─── API Sync Logic ─────────────────────────────────────────────────────────────
        function openApiSyncModal() {
            document.getElementById('apiSyncModal').classList.remove('hidden');
            document.getElementById('apiSyncInitial').classList.remove('hidden');
            document.getElementById('apiSyncProgressWrapper').classList.add('hidden');
            document.getElementById('apiSyncResults').innerHTML = '';
            document.getElementById('syncProgressBar').style.width = '0%';
            document.getElementById('syncProgressPercent').textContent = '0%';
            document.getElementById('syncCloseBtn').disabled = false;
        }

        function closeApiSyncModal() {
            const btn = document.getElementById('syncCloseBtn');
            if (btn.disabled) return;
            document.getElementById('apiSyncModal').classList.add('hidden');
        }

        async function startApiSync() {
            document.getElementById('apiSyncInitial').classList.add('hidden');
            document.getElementById('apiSyncProgressWrapper').classList.remove('hidden');
            document.getElementById('syncCloseBtn').disabled = true;

            const statusText = document.getElementById('syncStatusText');
            const detailText = document.getElementById('syncDetailText');
            const resultsContainer = document.getElementById('apiSyncResults');
            const progressBar = document.getElementById('syncProgressBar');
            const progressPercent = document.getElementById('syncProgressPercent');

            try {
                // Step 1: Get Tasks
                statusText.textContent = 'Fetching tasks...';
                const tasksResponse = await fetch("{{ route('logistics.manage-booking.v2.sync-apis') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const tasksData = await tasksResponse.json();

                if (!tasksData.success || !tasksData.tasks.length) {
                    statusText.textContent = 'No tasks found.';
                    detailText.textContent = 'All bookings are up to date or no carrier APIs configured.';
                    progressPercent.textContent = '100%';
                    progressBar.style.width = '100%';
                    document.getElementById('syncCloseBtn').disabled = false;
                    return;
                }

                const tasks = tasksData.tasks;
                const total = tasks.length;
                let completed = 0;

                for (const task of tasks) {
                    completed++;
                    const percent = Math.round((completed / total) * 100);

                    statusText.textContent = `Syncing ${task.carrier}...`;
                    detailText.textContent = `Processing Tracking ID: ${task.booking_id}`;

                    // Create result item placeholder
                    const resultId = `sync-res-${task.id}`;
                    const itemHtml = `
                        <div class="border border-gray-100 rounded-lg overflow-hidden bg-white shadow-sm">
                            <div class="px-4 py-3 flex items-center justify-between bg-gray-50/50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded flex items-center justify-center ${task.provider === 'maersk' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600'}">
                                        <i class="fa-solid ${task.provider === 'maersk' ? 'fa-ship' : 'fa-plane'}"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">${task.booking_id}</p>
                                        <p class="text-[9px] text-gray-400 uppercase font-black">${task.carrier}</p>
                                    </div>
                                </div>
                                <div id="${resultId}-status">
                                    <i class="fa-solid fa-spinner fa-spin text-indigo-500"></i>
                                </div>
                            </div>
                            <div id="${resultId}-body" class="hidden border-t border-gray-100 p-4 bg-gray-50/30">
                                <pre class="text-[10px] text-gray-600 overflow-x-auto whitespace-pre-wrap font-mono"></pre>
                            </div>
                            <button onclick="toggleSyncDetail('${resultId}-body')" class="w-full py-1.5 text-[9px] font-bold text-gray-400 hover:text-indigo-600 border-t border-gray-50 flex items-center justify-center gap-1 transition-colors">
                                <i class="fa-solid fa-chevron-down"></i> <span class="label-text">SHOW API RESPONSE</span>
                            </button>
                        </div>
                    `;
                    resultsContainer.insertAdjacentHTML('afterbegin', itemHtml);

                    // Step 2: Process Task
                    try {
                        const processResponse = await fetch(
                            "{{ route('logistics.manage-booking.v2.process-sync-task') }}", {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    id: task.id
                                })
                            });
                        const resData = await processResponse.json();

                        const statusEl = document.getElementById(`${resultId}-status`);
                        const bodyEl = document.getElementById(`${resultId}-body`);
                        const preEl = bodyEl.querySelector('pre');

                        if (resData.success) {
                            statusEl.innerHTML = '<i class="fa-solid fa-circle-check text-green-500"></i>';
                            preEl.textContent = JSON.stringify(resData.data, null, 2);
                        } else {
                            statusEl.innerHTML = '<i class="fa-solid fa-circle-exclamation text-amber-500" title="' +
                                resData.message + '"></i>';
                            preEl.textContent = resData.message + "\n\n" + (resData.data ? JSON.stringify(resData.data,
                                null, 2) : '');
                        }
                    } catch (err) {
                        document.getElementById(`${resultId}-status`).innerHTML =
                            '<i class="fa-solid fa-circle-xmark text-red-500"></i>';
                    }

                    // Update Progress
                    progressBar.style.width = percent + '%';
                    progressPercent.textContent = percent + '%';
                }

                statusText.textContent = 'Synchronization Complete!';
                detailText.textContent = `Successfully processed ${total} items.`;
                document.getElementById('syncCloseBtn').disabled = false;

            } catch (error) {
                statusText.textContent = 'Synchronization Failed';
                detailText.textContent = error.message;
                document.getElementById('syncCloseBtn').disabled = false;
            }
        }

        function toggleSyncDetail(id) {
            const el = document.getElementById(id);
            const btn = el.nextElementSibling;
            const label = btn.querySelector('.label-text');
            const icon = btn.querySelector('i');

            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                label.textContent = 'HIDE API RESPONSE';
            } else {
                el.classList.add('hidden');
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                label.textContent = 'SHOW API RESPONSE';
            }
        }

        async function confirmLegStatusUpdate(legId, newStatus, origin, destination) {
            const title = `Update Leg Status?`;
            const text =
                `Are you sure you want to change the status of leg "${origin} → ${destination}" to "${newStatus}"?`;

            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: title,
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0078C8',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, update it!',
                    cancelButtonText: 'No, cancel'
                });

                if (result.isConfirmed) {
                    performLegStatusUpdate(legId, newStatus);
                }
            } else {
                if (confirm(`${title}\n\n${text}`)) {
                    performLegStatusUpdate(legId, newStatus);
                }
            }
        }

        async function performLegStatusUpdate(legId, newStatus) {
            try {
                // Show loading state if Swal is available
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Updating...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }

                const response = await fetch(`/logistics/manage-booking/v2/legs/${legId}/status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                });

                const data = await response.json();

                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Something went wrong.');
                }
            } catch (error) {
                console.error('Error updating leg status:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error!', error.message, 'error');
                } else {
                    alert('Error: ' + error.message);
                }
            }
        }
    </script>
@endpush
