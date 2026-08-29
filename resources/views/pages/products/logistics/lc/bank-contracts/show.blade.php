@extends('layouts.auth')

@section('title', 'Bank Contract Details')

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

            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">{{ $contract->lc_number }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Bank Contract</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('logistics.lc.bank-contracts.edit', $contract->id) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-2">
                        <i class="fa-solid fa-pen text-[10px]"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.lc.bank-contracts.index') }}" />
                </div>
            </div>

            @php
                $statusConfig = [
                    'Draft' => 'bg-gray-50 text-gray-500 border-gray-200',
                    'Active' => 'bg-blue-50 text-blue-600 border-blue-100',
                    'Expiring Soon' => 'bg-amber-50 text-amber-600 border-amber-100',
                    'Settled' => 'bg-green-50 text-green-600 border-green-100',
                    'Expired' => 'bg-red-50 text-red-600 border-red-100',
                    'Cancelled' => 'bg-gray-100 text-gray-400 border-gray-200',
                ];
                $sc = $statusConfig[$contract->status] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                $documents = [
                    'doc_commercial_invoice' => 'Commercial Invoice',
                    'doc_bill_of_lading' => 'Bill of Lading',
                    'doc_packing_list' => 'Packing List',
                    'doc_certificate_of_origin' => 'Certificate of Origin',
                    'doc_insurance_certificate' => 'Insurance Certificate',
                    'doc_phytosanitary' => 'Phytosanitary Certificate',
                    'doc_inspection_certificate' => 'Inspection Certificate',
                    'doc_weight_certificate' => 'Weight Certificate',
                ];
                $requiredDocs = $contract->documents_required ? explode(',', $contract->documents_required) : [];
            @endphp

            <div class="space-y-4">

                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Contract & Bank Details</h3>
                        <span
                            class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $sc }}">
                            {{ $contract->status }}
                        </span>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-y-5 gap-x-8">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Contract Number</p>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $contract->contract_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Issuing Bank</p>
                            <p class="text-sm font-medium text-gray-900">{{ strtoupper($contract->issuing_bank ?? '—') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Applicant</p>
                            <p class="text-sm font-medium text-gray-900">{{ $contract->applicant ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Beneficiary</p>
                            <p class="text-sm font-medium text-gray-900">{{ $contract->beneficiary ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Advising Bank</p>
                            <p class="text-sm text-gray-700">{{ $contract->advising_bank ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Reimbursing Bank</p>
                            <p class="text-sm text-gray-700">{{ $contract->reimbursing_bank ?: '—' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-sm font-semibold text-gray-800">Financial Terms</h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-y-5 gap-x-8">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Amount</p>
                            <p class="text-base font-bold text-gray-900">{{ $contract->currency }}
                                {{ number_format((float) $contract->amount) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Tolerance</p>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $contract->tolerance_pct ? '±' . $contract->tolerance_pct . '%' : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Payment Terms</p>
                            <p class="text-sm font-medium text-gray-900">{{ $contract->payment_terms ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">PO/PI/Indented No</p>
                            <p class="text-sm text-gray-700">{{ $contract->po_pi_indented_no ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Date in Document</p>
                            <p class="text-sm text-gray-700">{{ $contract->date_in_document?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Booking Number</p>
                            <p class="text-sm text-gray-700">{{ $contract->booking_number ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Booking Date</p>
                            <p class="text-sm text-gray-700">{{ $contract->booking_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Issue Date</p>
                            <p class="text-sm text-gray-700">{{ $contract->issue_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Expiry Date</p>
                            <p class="text-sm text-gray-700">{{ $contract->expiry_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Latest Shipment Date</p>
                            <p class="text-sm text-gray-700">
                                {{ $contract->latest_shipment_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                @if ($contract->goods_description)
                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Goods & Shipping</h3>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Description of Goods</p>
                                <p class="text-sm text-gray-800 leading-relaxed">{{ $contract->goods_description }}</p>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-y-5 gap-x-8">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Port of Loading</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $contract->port_of_loading ?: '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Port of Discharge</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $contract->port_of_discharge ?: '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Incoterms</p>
                                    @if ($contract->incoterms)
                                        <span
                                            class="inline-block px-2.5 py-1 text-[10px] font-bold rounded border bg-gray-50 text-gray-600 border-gray-200">{{ $contract->incoterms }}</span>
                                    @else
                                        <p class="text-sm text-gray-700">—</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (!empty($requiredDocs))
                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="text-sm font-semibold text-gray-800">Required Documents</h3>
                        </div>
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
                @endif

                @if ($contract->special_conditions)
                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Special Conditions</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                                {{ $contract->special_conditions }}</p>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
    <x-delete-confirmation />
@endsection
