@extends('layouts.auth')

@section('title', 'Booking Details')

@section('content')
    @php
        $statusConfig = [
            'Booked' => 'bg-blue-50 text-blue-700 border-blue-200',
            'Confirmed' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            'In-Transit' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Pickup' => 'bg-amber-50 text-amber-700 border-amber-200',
            'Delivered' => 'bg-green-50 text-green-700 border-green-200',
            'Cancelled' => 'bg-red-50 text-red-700 border-red-200',
        ];
        $modeLabels = [
            'fcl_sea' => 'FCL Sea',
            'lcl_sea' => 'LCL Sea',
            'air' => 'Air Freight',
            'road_truck' => 'Road / Truck',
            'multimodal' => 'Multimodal',
        ];
        $statusClass = $statusConfig[$booking->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
        $modeLabel = $modeLabels[$booking->freight_mode] ?? Str::headline((string) $booking->freight_mode);
        $subModeLabel = $modeLabels[$booking->freight_sub_mode] ?? Str::headline((string) $booking->freight_sub_mode);
        $formatDate = function ($value) {
            if (blank($value)) {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('M d, Y');
            } catch (\Throwable $e) {
                return $value;
            }
        };
        $carrierName = $booking->carrier?->carrier_company_name ?? '-';
        $hsCode = $booking->hsCode?->hs_code ?? '-';
        $hsDescription = $booking->hsCode?->description ?? null;
        $documentsCount = $documents->count();
        $receivedDocuments = $documents->where('status', 'Received')->count();
        $historyCount = $booking->statusHistories->count();
        $itemsCount = $booking->items->count();
        $inlandInfo = collect($booking->getInlandInfo())
            ->only([
                'gd_number',
                'do_number',
                'customs_channel',
                'carrier_contact',
                'carrier_contract',
                'return_deadline',
                'freight_rate_pkr',
                'transit_stop',
                'oversize_permit',
                'vehicle_reg',
                'vehicle_model',
                'truck_capacity',
                'driver_name',
                'driver_cnic',
                'driver_mobile',
                'co_driver',
                'gps_unit_id',
                'gps_portal',
                'vehicle_inspection',
                'container_return_type',
                'route_option',
                'total_distance',
                'est_transit_time',
                'delivery_address',
                'receiving_contact',
                'delivery_window',
                'unloading_method',
                'delivery_receipt_type',
                'post_delivery_inspection',
            ])
            ->filter(fn($value) => filled($value));
        $inlandLabels = [
            'gd_number' => 'GD Number',
            'do_number' => 'DO Number',
            'customs_channel' => 'Customs Channel',
            'carrier_contact' => 'Carrier Contact',
            'carrier_contract' => 'Carrier Contract',
            'return_deadline' => 'Return Deadline',
            'freight_rate_pkr' => 'Freight Rate PKR',
            'transit_stop' => 'Transit Stop',
            'oversize_permit' => 'Oversize Permit',
            'vehicle_reg' => 'Vehicle Registration',
            'vehicle_model' => 'Vehicle Model',
            'truck_capacity' => 'Truck Capacity',
            'driver_name' => 'Driver Name',
            'driver_cnic' => 'Driver CNIC',
            'driver_mobile' => 'Driver Mobile',
            'co_driver' => 'Co-Driver',
            'gps_unit_id' => 'GPS Unit ID',
            'gps_portal' => 'GPS Portal',
            'vehicle_inspection' => 'Vehicle Inspection',
            'container_return_type' => 'Container Return Type',
            'route_option' => 'Route Option',
            'total_distance' => 'Total Distance',
            'est_transit_time' => 'Estimated Transit Time',
            'delivery_address' => 'Delivery Address',
            'receiving_contact' => 'Receiving Contact',
            'delivery_window' => 'Delivery Window',
            'unloading_method' => 'Unloading Method',
            'delivery_receipt_type' => 'Delivery Receipt Type',
            'post_delivery_inspection' => 'Post Delivery Inspection',
        ];
    @endphp

    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.manage-booking') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3 space-y-4">
            <div class="bg-white rounded-lg border border-black/10 px-5 py-4 flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-gray-900 text-xl font-normal">{{ $booking->booking_number ?: 'Booking #' . $booking->id }}</h1>
                        <span
                            class="inline-flex items-center px-2.5 py-1 text-[10px] font-black uppercase tracking-tighter rounded-full border {{ $statusClass }}">
                            {{ $booking->status ?: 'Draft' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $modeLabel }}
                        @if (filled($booking->freight_sub_mode))
                            / {{ $subModeLabel }}
                        @endif
                        | {{ $booking->origin_port ?: '-' }} to {{ $booking->destination_port ?: '-' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('logistics.manage-booking.v2.edit', $booking->id) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#006bb0] flex items-center gap-2">
                        <i class="fa-solid fa-pen text-[10px]"></i> Edit
                    </a>
                    <a href="{{ route('logistics.manage-booking.v2.documents', ['q' => $booking->booking_number]) }}"
                        class="px-4 py-2 bg-white text-gray-700 text-xs font-medium rounded border border-gray-200 hover:bg-gray-50 flex items-center gap-2">
                        <i class="fa-solid fa-folder-open text-[10px]"></i> Documents
                    </a>
                    <x-common.back-button url="{{ route('logistics.manage-booking.v2.index') }}" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg border border-black/10 px-5 py-4">
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Departure</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $formatDate($booking->estimated_time_departure) }}</p>
                </div>
                <div class="bg-white rounded-lg border border-black/10 px-5 py-4">
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Delivery</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $formatDate($booking->estimated_time_delivery) }}</p>
                </div>
                <div class="bg-white rounded-lg border border-black/10 px-5 py-4">
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Items</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $itemsCount }}</p>
                </div>
                <div class="bg-white rounded-lg border border-black/10 px-5 py-4">
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Documents</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $receivedDocuments }}/{{ $documentsCount }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2 space-y-4">
                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                            <h3 class="text-sm font-semibold text-gray-800">Booking Overview</h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Booking Number</p>
                                <p class="text-sm font-medium text-gray-900">{{ $booking->booking_number ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Booking Type</p>
                                <p class="text-sm font-medium text-gray-900">{{ Str::headline((string) $booking->booking_type) ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Created On</p>
                                <p class="text-sm font-medium text-gray-900">{{ $booking->created_at?->format('M d, Y h:i A') ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Freight Mode</p>
                                <p class="text-sm text-gray-700">{{ $modeLabel }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Sub Mode</p>
                                <p class="text-sm text-gray-700">{{ filled($booking->freight_sub_mode) ? $subModeLabel : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Service Level</p>
                                <p class="text-sm text-gray-700">{{ $booking->service_level ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Origin</p>
                                <p class="text-sm text-gray-700">{{ $booking->origin_port ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Destination</p>
                                <p class="text-sm text-gray-700">{{ $booking->destination_port ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Carrier</p>
                                <p class="text-sm text-gray-700">{{ $carrierName }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Place of Receipt</p>
                                <p class="text-sm text-gray-700">{{ $booking->place_of_receipt ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Final Delivery Place</p>
                                <p class="text-sm text-gray-700">{{ $booking->final_delivery_place ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">HS Code</p>
                                <p class="text-sm text-gray-700">{{ $hsCode }}</p>
                            </div>
                            @if ($hsDescription)
                                <div class="md:col-span-3">
                                    <p class="text-xs text-gray-500 mb-1">HS Description</p>
                                    <p class="text-sm text-gray-700">{{ $hsDescription }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                            <h3 class="text-sm font-semibold text-gray-800">Parties</h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Shipper / Exporter</p>
                                <p class="text-sm font-medium text-gray-900">{{ $booking->exporter ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Consignee / Importer</p>
                                <p class="text-sm font-medium text-gray-900">{{ $booking->importer ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Notify Party</p>
                                <p class="text-sm text-gray-700">{{ $booking->notify_party ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Freight Forwarder / Agent</p>
                                <p class="text-sm text-gray-700">{{ $booking->agent ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                            <h3 class="text-sm font-semibold text-gray-800">Cargo and Commercial</h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Gross Weight</p>
                                <p class="text-sm text-gray-700">{{ $booking->gross_weight ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Volume CBM</p>
                                <p class="text-sm text-gray-700">{{ $booking->volume_cbm ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Declared Cargo Value</p>
                                <p class="text-sm text-gray-700">{{ $booking->declared_cargo_value ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Dangerous Goods</p>
                                <p class="text-sm text-gray-700">{{ $booking->dangerous_good ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Incoterms</p>
                                <p class="text-sm text-gray-700">{{ $booking->incoterms ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Freight Terms</p>
                                <p class="text-sm text-gray-700">{{ $booking->freight_terms ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Quoted Freight Rate</p>
                                <p class="text-sm text-gray-700">{{ $booking->quoted_freight_rate ?: '-' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-xs text-gray-500 mb-1">Remarks / Instructions</p>
                                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $booking->remarks ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Booking Items</h3>
                            <span class="text-xs text-gray-400">{{ $itemsCount }} records</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Mode</th>
                                        <th class="px-4 py-3 text-left font-semibold">Container / Package</th>
                                        <th class="px-4 py-3 text-left font-semibold">Quantity</th>
                                        <th class="px-4 py-3 text-left font-semibold">Pallets</th>
                                        <th class="px-4 py-3 text-left font-semibold">Dimensions</th>
                                        <th class="px-4 py-3 text-left font-semibold">Weight</th>
                                        <th class="px-4 py-3 text-left font-semibold">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($booking->items as $item)
                                        @php
                                            $itemMode = $modeLabels[$item->freight_mode] ?? Str::headline((string) $item->freight_mode);
                                            $containerOrPackage = $item->container_type ?: $item->package_type ?: '-';
                                            $quantity = $item->quantity ?: $item->package ?: '-';
                                            $dimensions = filled($item->length) || filled($item->width) || filled($item->height)
                                                ? trim(($item->length ?: '0') . ' x ' . ($item->width ?: '0') . ' x ' . ($item->height ?: '0') . ' ' . ($item->dimension_unit ?: ''))
                                                : '-';
                                            $description = $item->details_of_commodities ?: $item->item_description ?: '-';
                                        @endphp
                                        <tr class="text-gray-700">
                                            <td class="px-4 py-3">{{ $itemMode }}</td>
                                            <td class="px-4 py-3">{{ $containerOrPackage }}</td>
                                            <td class="px-4 py-3">{{ $quantity }}</td>
                                            <td class="px-4 py-3">{{ $item->number_of_pallets ?: '-' }}</td>
                                            <td class="px-4 py-3">{{ $dimensions }}</td>
                                            <td class="px-4 py-3">{{ $item->weight ?: '-' }}</td>
                                            <td class="px-4 py-3">{{ $description }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No booking items found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($inlandInfo->isNotEmpty())
                        <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                                <h3 class="text-sm font-semibold text-gray-800">Inland Details</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                                @foreach ($inlandInfo as $key => $value)
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">{{ $inlandLabels[$key] ?? Str::headline($key) }}</p>
                                        <p class="text-sm text-gray-700">{{ $value }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Documents</h3>
                            <button type="button" onclick="document.getElementById('uploadDocModal').classList.remove('hidden')"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-[#0078C8] rounded hover:bg-[#006bb0]">
                                Upload
                            </button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse ($documents as $doc)
                                @php
                                    $docStatusClass = match ($doc->status) {
                                        'Received', 'Filed' => 'bg-green-50 text-green-700 border-green-200',
                                        'Missing' => 'bg-red-50 text-red-700 border-red-200',
                                        'In Progress' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-slate-50 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <div class="p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $doc->document_type }}</p>
                                            <p class="text-xs text-gray-500 mt-1 truncate">{{ $doc->original_filename ?: 'No file attached' }}</p>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $docStatusClass }}">
                                            {{ $doc->status }}
                                        </span>
                                    </div>
                                    @if ($doc->description)
                                        <p class="text-xs text-gray-600 mt-2">{{ $doc->description }}</p>
                                    @endif
                                    @if ($doc->required_for)
                                        <p class="text-[11px] text-gray-400 mt-1">Required for: {{ $doc->required_for }}</p>
                                    @endif
                                    <div class="mt-3 flex items-center gap-2">
                                        @if ($doc->file_path)
                                            <a href="{{ asset('storage/' . ltrim($doc->file_path, '/')) }}" target="_blank"
                                                class="px-2.5 py-1 text-[11px] font-medium text-[#0078C8] border border-[#0078C8]/20 rounded hover:bg-blue-50">
                                                View
                                            </a>
                                        @endif
                                        <button type="button" onclick='openEditDocModal(@json($doc))'
                                            class="px-2.5 py-1 text-[11px] font-medium text-gray-700 border border-gray-200 rounded hover:bg-gray-50">
                                            Edit
                                        </button>
                                        <button type="button"
                                            onclick="deleteDoc({{ $doc->id }}, '{{ route('logistics.manage-booking.v2.documents.destroy', $doc->id) }}')"
                                            class="px-2.5 py-1 text-[11px] font-medium text-red-600 border border-red-200 rounded hover:bg-red-50">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="p-8 text-center text-sm text-gray-400">No documents uploaded yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                            <h3 class="text-sm font-semibold text-gray-800">Status History</h3>
                            <p class="text-xs text-gray-400 mt-1">{{ $historyCount }} updates recorded</p>
                        </div>
                        <div class="p-4 space-y-3">
                            @forelse ($booking->statusHistories as $history)
                                <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-medium text-gray-900">{{ $history->event_label }}</p>
                                        <p class="text-[11px] text-gray-400">{{ $history->created_at?->format('M d, Y h:i A') ?: '-' }}</p>
                                    </div>
                                    <div class="mt-2 grid grid-cols-1 gap-1 text-xs text-gray-600">
                                        @if ($history->status_changed_to)
                                            <p>Status: <span class="font-medium">{{ $history->status_changed_to }}</span></p>
                                        @endif
                                        @if ($history->original_date)
                                            <p>Previous date: <span class="font-medium">{{ $history->original_date->format('M d, Y') }}</span></p>
                                        @endif
                                        @if ($history->new_date)
                                            <p>New date: <span class="font-medium">{{ $history->new_date->format('M d, Y') }}</span></p>
                                        @endif
                                        @if ($history->reason)
                                            <p>Reason: <span class="font-medium">{{ $history->reason }}</span></p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">No status history available.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="uploadDocModal"
        class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Upload Document</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $booking->booking_number }}</p>
                </div>
                <button type="button" onclick="closeUploadDocModal()"
                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('logistics.manage-booking.v2.documents.store', $booking->id) }}"
                enctype="multipart/form-data" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">
                        Document Type <span class="text-red-500">*</span>
                    </label>
                    <select name="document_type" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        <option value="">Select type...</option>
                        <option>Bill of Lading (BOL)</option>
                        <option>Airway Bill (AWB)</option>
                        <option>Commercial Invoice</option>
                        <option>Packing List</option>
                        <option>Certificate of Origin (CoO)</option>
                        <option>Letter of Credit (LC)</option>
                        <option>GD / Goods Declaration</option>
                        <option>Insurance Certificate</option>
                        <option>Fumigation Certificate</option>
                        <option>Phytosanitary Certificate</option>
                        <option>Other</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Description</label>
                    <input type="text" name="description" placeholder="Brief description..."
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Required For</label>
                    <input type="text" name="required_for" placeholder="e.g. Customs clearance"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        <option value="Pending">Pending</option>
                        <option value="Received">Received</option>
                        <option value="Missing">Missing</option>
                        <option value="Filed">Filed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Not Required">Not Required</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">File (optional)</label>
                    <label
                        class="flex items-center gap-2.5 w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 border-dashed cursor-pointer hover:border-[#0078C8] hover:bg-blue-50/30 transition group">
                        <i class="fa-solid fa-paperclip text-gray-300 group-hover:text-[#0078C8] text-sm transition"></i>
                        <span id="docFileName" class="text-sm text-gray-400 group-hover:text-[#0078C8] transition truncate">
                            Click to upload file
                        </span>
                        <input type="file" name="file"
                            onchange="document.getElementById('docFileName').textContent = this.files[0]?.name ?? 'Click to upload file'"
                            class="hidden">
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeUploadDocModal()"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-upload text-[10px]"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editDocModal"
        class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Update Document</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $booking->booking_number }}</p>
                </div>
                <button type="button" onclick="closeEditDocModal()"
                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <form id="editDocForm" method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">
                        Document Type <span class="text-red-500">*</span>
                    </label>
                    <select name="document_type" id="edit_document_type" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        <option value="">Select type...</option>
                        <option>Bill of Lading (BOL)</option>
                        <option>Airway Bill (AWB)</option>
                        <option>Commercial Invoice</option>
                        <option>Packing List</option>
                        <option>Certificate of Origin (CoO)</option>
                        <option>Letter of Credit (LC)</option>
                        <option>GD / Goods Declaration</option>
                        <option>Insurance Certificate</option>
                        <option>Fumigation Certificate</option>
                        <option>Phytosanitary Certificate</option>
                        <option>Other</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Description</label>
                    <input type="text" name="description" id="edit_description" placeholder="Brief description..."
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Required For</label>
                    <input type="text" name="required_for" id="edit_required_for" placeholder="e.g. Customs clearance"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="edit_status" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        <option value="Pending">Pending</option>
                        <option value="Received">Received</option>
                        <option value="Missing">Missing</option>
                        <option value="Filed">Filed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Not Required">Not Required</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1.5">File (Keep empty to maintain current file)</label>
                    <label
                        class="flex items-center gap-2.5 w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 border-dashed cursor-pointer hover:border-[#0078C8] hover:bg-blue-50/30 transition group">
                        <i class="fa-solid fa-paperclip text-gray-300 group-hover:text-[#0078C8] text-sm transition"></i>
                        <span id="editDocFileName" class="text-sm text-gray-400 group-hover:text-[#0078C8] transition truncate">
                            Click to upload new file
                        </span>
                        <input type="file" name="file"
                            onchange="document.getElementById('editDocFileName').textContent = this.files[0]?.name ?? 'Click to upload new file'"
                            class="hidden">
                    </label>
                    <p id="currentFileName" class="text-[10px] text-[#0078C8] mt-1 truncate"></p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeEditDocModal()"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-save text-[10px]"></i> Update Document
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeUploadDocModal() {
            const modal = document.getElementById('uploadDocModal');
            modal.classList.add('hidden');
        }

        function closeEditDocModal() {
            const modal = document.getElementById('editDocModal');
            modal.classList.add('hidden');
        }

        function openEditDocModal(doc) {
            const modal = document.getElementById('editDocModal');
            const form = document.getElementById('editDocForm');

            form.action = `/logistics/manage-booking/v2/documents/${doc.id}/update`;
            document.getElementById('edit_document_type').value = doc.document_type;
            document.getElementById('edit_description').value = doc.description || '';
            document.getElementById('edit_required_for').value = doc.required_for || '';
            document.getElementById('edit_status').value = doc.status;
            document.getElementById('currentFileName').textContent = doc.original_filename ? `Current file: ${doc.original_filename}` : '';
            document.getElementById('editDocFileName').textContent = 'Click to upload new file';

            modal.classList.remove('hidden');
        }

        function deleteDoc(id, url) {
            if (!confirm('Delete this document?')) return;

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    location.reload();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const uploadModal = document.getElementById('uploadDocModal');
            const editModal = document.getElementById('editDocModal');

            [uploadModal, editModal].forEach((modal) => {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        modal.classList.add('hidden');
                    }
                });
            });
        });

        document.getElementById('uploadDocModal').classList.add('hidden');
        document.getElementById('editDocModal').classList.add('hidden');
    </script>
@endsection
