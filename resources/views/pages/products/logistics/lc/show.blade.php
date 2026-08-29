@extends('layouts.auth')

@section('title', 'LC Details')

@section('content')
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.lc') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">{{ $lc->lc_number ?? 'LC-2026-0041' }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Letter of Credit · UCP 600</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('logistics.lc.edit', $lc->id ?? 1) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-2">
                        <i class="fa-solid fa-pen text-[10px]"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.lc.index') }}" />
                </div>
            </div>

            @php

                $statusConfig = [
                    'Active' => 'bg-blue-50 text-blue-600 border-blue-100',
                    'Expiring Soon' => 'bg-amber-50 text-amber-600 border-amber-100',
                    'Settled' => 'bg-green-50 text-green-600 border-green-100',
                    'Expired' => 'bg-red-50 text-red-600 border-red-100',
                ];
                $sc = $statusConfig[$lc->status] ?? 'bg-gray-50 text-gray-600 border-gray-100';

                $documents = [
                    'doc_commercial_invoice' => 'Commercial Invoice (3 originals + 3 copies)',
                    'doc_bill_of_lading' => 'Full Set of Clean On-Board Bill of Lading',
                    'doc_packing_list' => 'Packing List',
                    'doc_certificate_of_origin' => 'Certificate of Origin (SAARC / GSP)',
                    'doc_insurance_certificate' => 'Insurance Certificate (CIF only)',
                    'doc_phytosanitary' => 'Phytosanitary / Health Certificate',
                    'doc_inspection_certificate' => 'Inspection Certificate (SGS / Intertek)',
                    'doc_weight_certificate' => 'Weight / Analysis Certificate',
                ];
            @endphp

            <div class="space-y-4">

                {{-- LC & Bank Details --}}
                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">LC & Bank Details</h3>
                        <span
                            class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $sc }}">
                            {{ $lc->status }}
                        </span>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-y-5 gap-x-8">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">LC Type</p>
                            <p class="text-sm font-medium text-gray-900">{{ $lc->lc_type }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Issuing Bank</p>
                            <p class="text-sm font-medium text-gray-900">{{ $lc->issuing_bank }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Applicant (Importer)</p>
                            <p class="text-sm font-medium text-gray-900">{{ $lc->applicant }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Beneficiary (Exporter)</p>
                            <p class="text-sm font-medium text-gray-900">{{ $lc->beneficiary }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Advising Bank</p>
                            <p class="text-sm text-gray-700">{{ $lc->advising_bank ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Reimbursing Bank</p>
                            <p class="text-sm text-gray-700">{{ $lc->reimbursing_bank ?: '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Financial Terms --}}
                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-sm font-semibold text-gray-800">Financial Terms</h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-y-5 gap-x-8">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">LC Amount</p>
                            <p class="text-base font-bold text-gray-900">{{ $lc->currency }}
                                {{ number_format((float) $lc->amount) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Tolerance</p>
                            <p class="text-sm font-medium text-gray-900">±{{ $lc->tolerance_pct }}%</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Payment Terms</p>
                            <p class="text-sm font-medium text-gray-900">{{ $lc->payment_terms }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Place of Expiry</p>
                            <p class="text-sm text-gray-700">{{ $lc->place_of_expiry ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Issue Date</p>
                            <p class="text-sm text-gray-700">{{ $lc->issue_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Expiry Date</p>
                            <p class="text-sm text-gray-700">{{ $lc->expiry_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Last Shipment Date</p>
                            <p class="text-sm text-gray-700">{{ $lc->latest_shipment_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Goods & Shipping --}}
                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Goods & Shipping</h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Description of Goods</p>
                            <p class="text-sm text-gray-800 leading-relaxed">{{ $lc->goods_description }}</p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-y-5 gap-x-8">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Port of Loading</p>
                                <p class="text-sm font-medium text-gray-900">{{ $lc->port_of_loading }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Port of Discharge</p>
                                <p class="text-sm font-medium text-gray-900">{{ $lc->port_of_discharge }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Incoterms</p>
                                <span
                                    class="inline-block px-2.5 py-1 text-[10px] font-bold rounded border bg-gray-50 text-gray-600 border-gray-200">
                                    {{ $lc->incoterms }}
                                </span>
                            </div>
                            <div></div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Partial Shipments</p>
                                <p class="text-sm text-gray-700">{{ $lc->partial_shipments }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Transhipment</p>
                                <p class="text-sm text-gray-700">{{ $lc->transhipment }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Required Documents --}}
                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-sm font-semibold text-gray-800">Required Documents</h3>
                    </div>
                    @php
                        $requiredDocs = $lc->documents_required ? explode(',', $lc->documents_required) : [];
                    @endphp
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($documents as $key => $label)
                            @php $checked = in_array($key, $requiredDocs); @endphp
                            <div
                                class="flex items-center gap-3 p-3 rounded-lg border {{ $checked ? 'border-blue-100 bg-blue-50/40' : 'border-slate-100 bg-gray-50/50' }}">
                                @if ($checked)
                                    <i class="fa-solid fa-circle-check text-[#0078C8] text-sm shrink-0"></i>
                                @else
                                    <i class="fa-regular fa-circle text-gray-300 text-sm shrink-0"></i>
                                @endif
                                <span
                                    class="text-sm {{ $checked ? 'text-gray-800 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Special Conditions --}}
                @if ($lc->special_conditions)
                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Special Conditions</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                                {{ $lc->special_conditions }}</p>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
