@extends('layouts.auth')

@section('title', 'GD — ' . $gd->gd_number)

@section('content')
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.gd') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">{{ $gd->gd_number }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Filed {{ $gd->filed_date?->format('M d, Y') ?? $gd->created_at->format('M d, Y') }}
                        @if ($gd->collectorate)
                            · {{ $gd->collectorate }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.gd.edit', $gd->id) }}"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-pen text-xs"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.gd.index') }}" />
                </div>
            </div>

            {{-- Status Pills --}}
            <div class="flex items-center gap-3 mb-4">
                @php
                    $statusConfig = [
                        'Draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                        'Filed' => 'bg-blue-50 text-blue-600 border-blue-100',
                        'Approved' => 'bg-green-50 text-green-700 border-green-100',
                        'Cleared' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        'On Hold' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'Rejected' => 'bg-red-50 text-red-700 border-red-100',
                    ];
                    $examConfig = [
                        'no_exam' => 'bg-green-50 text-green-700 border-green-100',
                        'doc_check' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'physical_exam' => 'bg-red-50 text-red-700 border-red-100',
                    ];
                    $examLabel = [
                        'no_exam' => 'No Exam',
                        'doc_check' => 'Doc Check',
                        'physical_exam' => 'Physical Exam',
                    ];
                @endphp
                <span
                    class="inline-block px-3 py-1 text-xs font-bold uppercase tracking-wide rounded-full border {{ $statusConfig[$gd->status] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                    {{ $gd->status }}
                </span>
                <span
                    class="inline-block px-3 py-1 text-xs font-bold uppercase tracking-wide rounded-full border {{ $examConfig[$gd->exam_status ?? 'no_exam'] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                    {{ $examLabel[$gd->exam_status ?? 'no_exam'] ?? 'No Exam' }}
                </span>
                @if ($gd->declaration_type)
                    <span
                        class="inline-block px-3 py-1 text-xs font-bold uppercase tracking-wide rounded-full border bg-purple-50 text-purple-600 border-purple-100">
                        {{ ucfirst($gd->declaration_type) }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Left: Declaration Details --}}
                <div class="md:col-span-2 space-y-4">

                    {{-- Declaration Info --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Declaration Details</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-0 divide-x divide-y divide-gray-100">
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">GD Number</p>
                                <p class="text-sm font-bold text-gray-900 mt-1">{{ $gd->gd_number }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Collectorate</p>
                                <p class="text-sm text-gray-700 mt-1">{{ $gd->collectorate ?: '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">BL / AWB Number
                                </p>
                                <p class="text-sm font-mono text-gray-700 mt-1">{{ $gd->bl_awb_number ?: '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Linked Booking
                                </p>
                                <p class="text-sm text-gray-700 mt-1">
                                    @if ($gd->booking)
                                        <a href="{{ route('logistics.manage-booking.v2.show', $gd->booking_v2_id) }}"
                                            class="text-[#0078C8] hover:underline">{{ $gd->booking->booking_number }}</a>
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Filing Date</p>
                                <p class="text-sm text-gray-700 mt-1">{{ $gd->filed_date?->format('M d, Y') ?? '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Approval Date
                                </p>
                                <p class="text-sm text-gray-700 mt-1">{{ $gd->approval_date?->format('M d, Y') ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Importer & Supplier --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Importer & Supplier</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-0 divide-x divide-y divide-gray-100">
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Importer</p>
                                <p class="text-sm font-medium text-gray-800 mt-1">{{ $gd->importer ?: '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">NTN / CNIC</p>
                                <p class="text-sm font-mono text-gray-700 mt-1">{{ $gd->ntn_cnic ?: '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Exporter /
                                    Supplier</p>
                                <p class="text-sm text-gray-700 mt-1">{{ $gd->exporter ?: '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Country of
                                    Origin</p>
                                <p class="text-sm text-gray-700 mt-1">
                                    {{ config('countries.' . $gd->country_of_origin, $gd->country_of_origin ?: '—') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Cargo --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Cargo Details</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-0 divide-x divide-y divide-gray-100">
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">HS Code</p>
                                <p class="text-sm font-mono text-gray-800 mt-1">{{ $gd->hs_code ?: '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Description</p>
                                <p class="text-sm text-gray-700 mt-1">{{ $gd->description ?: '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                                    {{ $gd->unit === 'KG' ? 'Gross Weight (KG)' : 'Quantity' }}
                                </p>
                                <p class="text-sm text-gray-700 mt-1">
                                    {{ $gd->quantity ? number_format($gd->quantity, 0) . ' ' . $gd->unit : '—' }}</p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Net Weight</p>
                                <p class="text-sm text-gray-700 mt-1">
                                    @if ($gd->net_weight)
                                        {{ number_format($gd->net_weight, 0) }} KG
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Remarks --}}
                    @if ($gd->remarks)
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Remarks</p>
                            <p class="text-sm text-gray-700">{{ $gd->remarks }}</p>
                        </div>
                    @endif

                </div>

                {{-- Right: Duty Summary --}}
                <div class="space-y-4">

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Duty Assessment</h3>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">Customs Value</span>
                                <span class="text-sm font-semibold text-gray-800">
                                    {{ $gd->customs_value ? number_format($gd->customs_value, 2) . ' ' . $gd->currency : '—' }}
                                </span>
                            </div>
                            @if ($gd->delivery_term)
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">{{ strtoupper($gd->delivery_term) }} Value</span>
                                    <span class="text-sm font-semibold text-gray-800">
                                        {{ $gd->delivery_term_value ? number_format($gd->delivery_term_value, 2) . ' USD' : 'â€”' }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">Exchange Rate</span>
                                <span class="text-sm text-gray-700">
                                    {{ $gd->exchange_rate ? number_format($gd->exchange_rate, 2) : '—' }}
                                </span>
                            </div>
                            <div class="border-t border-gray-100 pt-4 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Customs Duty</span>
                                    <span
                                        class="text-sm text-gray-700">{{ $gd->customs_duty ? 'PKR ' . number_format($gd->customs_duty, 0) : '—' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Additional Duty</span>
                                    <span
                                        class="text-sm text-gray-700">{{ $gd->additional_duty ? 'PKR ' . number_format($gd->additional_duty, 0) : '—' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Sales Tax</span>
                                    <span
                                        class="text-sm text-gray-700">{{ $gd->sales_tax ? 'PKR ' . number_format($gd->sales_tax, 0) : '—' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Income Tax / WHT</span>
                                    <span
                                        class="text-sm text-gray-700">{{ $gd->income_tax ? 'PKR ' . number_format($gd->income_tax, 0) : '—' }}</span>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Total Duty</span>
                                    <span
                                        class="text-lg font-black text-red-500">{{ $gd->total_duty ? 'PKR ' . number_format($gd->total_duty, 0) : '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 border border-green-100">
                                <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wider">Landed Cost
                                </p>
                                <p class="text-xl font-black text-green-700 mt-1">
                                    {{ $gd->landed_cost ? 'PKR ' . number_format($gd->landed_cost, 0) : '—' }}</p>
                            </div>
                        </div>
                    </div>

                    @if ($gd->duty_payment_mode)
                        @php
                            $paymentDetails = $gd->duty_payment_details ?? [];
                            $paymentDocuments = data_get($paymentDetails, 'documents', []);
                        @endphp
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Payment Mode</p>
                            <p class="text-sm font-medium text-gray-800 mt-1">
                                {{ ucwords(str_replace('_', ' ', $gd->duty_payment_mode)) }}</p>
                            <div class="mt-4 space-y-3 text-sm">
                                @if (data_get($paymentDetails, 'invoice_number'))
                                    <div class="flex justify-between gap-4">
                                        <span class="text-xs text-gray-500">Invoice Number</span>
                                        <span class="font-medium text-gray-800">{{ data_get($paymentDetails, 'invoice_number') }}</span>
                                    </div>
                                @endif
                                @if (data_get($paymentDetails, 'third_party_name'))
                                    <div class="flex justify-between gap-4">
                                        <span class="text-xs text-gray-500">Name</span>
                                        <span class="font-medium text-gray-800">{{ data_get($paymentDetails, 'third_party_name') }}</span>
                                    </div>
                                @endif
                                @if (data_get($paymentDetails, 'invoice_document_path'))
                                    <div class="flex justify-between gap-4">
                                        <span class="text-xs text-gray-500">Document</span>
                                        <a href="{{ asset('storage/' . data_get($paymentDetails, 'invoice_document_path')) }}"
                                            target="_blank" class="font-medium text-[#0078C8] hover:underline">
                                            {{ data_get($paymentDetails, 'invoice_document_original_name', 'View document') }}
                                        </a>
                                    </div>
                                @endif
                                @if (data_get($paymentDetails, 'insurance_type'))
                                    <div class="flex justify-between gap-4">
                                        <span class="text-xs text-gray-500">Insurance</span>
                                        <span class="font-medium text-gray-800">
                                            {{ data_get($paymentDetails, 'insurance_type') === 'percentage' ? data_get($paymentDetails, 'insurance_value') . '%' : data_get($paymentDetails, 'insurance_value') }}
                                        </span>
                                    </div>
                                @endif
                                @foreach ($paymentDocuments as $document)
                                    <div class="flex justify-between gap-4">
                                        <span class="text-xs text-gray-500">{{ $document['name'] ?? 'Document' }}</span>
                                        @if (!empty($document['file_path']))
                                            <a href="{{ asset('storage/' . $document['file_path']) }}" target="_blank"
                                                class="font-medium text-[#0078C8] hover:underline">
                                                {{ $document['original_name'] ?? 'View document' }}
                                            </a>
                                        @else
                                            <span class="font-medium text-gray-800">No file</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
