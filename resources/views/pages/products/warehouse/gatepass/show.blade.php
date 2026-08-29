{{-- resources/views/pages/products/warehouse/gate-pass/show.blade.php --}}
@extends('layouts.auth')

@section('title', 'Gate Pass - ' . $gatePass->gate_pass_id)

@section('content')
<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header Bar -->
        <div class="mb-4 bg-white rounded-md border border-gray-200 px-5 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <h2 class="text-gray-800 text-base font-semibold tracking-wide">Gate Pass Details</h2>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                @if(!$gatePass->isFullyApproved() && $gatePass->status !== 'REJECTED')
                    @if($gatePass->canApproveQC())
                        <button type="button" onclick="showQcModal({{ $gatePass->id }})"
                            class="px-4 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded hover:bg-blue-700 transition-colors">
                            Approve QC
                        </button>
                    @endif
                    @if($gatePass->canApproveSecurity())
                        <button type="button" onclick="showSecurityModal({{ $gatePass->id }})"
                            class="px-4 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded hover:bg-purple-700 transition-colors">
                            Approve Security
                        </button>
                    @endif
                    <a href="{{ route('warehouse.gate-pass.edit', $gatePass->id) }}"
                        class="px-4 py-1.5 border border-[#0078C8] text-[#0078C8] text-xs font-semibold rounded hover:bg-[#0078C8] hover:text-white transition-colors">
                        Edit
                    </a>
                @endif
                @foreach([
                    'customer' => 'Customer Print',
                    'warehouse_management' => 'Warehouse Management Print',
                    'warehouse_manager' => 'Warehouse Manager Print',
                    'hr_manager' => 'HR Manager Print',
                ] as $copyType => $copyLabel)
                    <a href="{{ route('warehouse.gate-pass.print', ['id' => $gatePass->id, 'copy' => $copyType]) }}" target="_blank"
                        class="no-print px-4 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded hover:bg-gray-200 transition-colors flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                        </svg>
                        {{ $copyLabel }}
                    </a>
                @endforeach
                <a href="{{ route('warehouse.gate-pass.index') }}"
                   class="no-print px-5 py-1.5 bg-[#0078C8] text-white text-sm font-semibold rounded hover:bg-[#0066a0] transition-colors tracking-wide w-full sm:w-auto text-center">
                    BACK
                </a>
            </div>
        </div>

        <!-- Two-Column Layout -->
        <div class="flex gap-4 items-start">

            <!-- LEFT COLUMN -->
            <div class="flex-1 flex flex-col gap-4">

                <!-- Gate Pass Info Card -->
                <div class="bg-white rounded-md border border-gray-200  p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Gate Pass ID</p>
                            <h1 class="text-lg font-semibold font-mono text-gray-900">{{ $gatePass->gate_pass_id }}</h1>
                        </div>
                        @php
                            $statusClass = match($gatePass->status) {
                                'QC_AND_SECURITY_APPROVED'  => 'bg-green-100 text-green-700',
                                'SECURITY_APPROVAL_PENDING' => 'bg-yellow-100 text-yellow-700',
                                'QC_APPROVAL_PENDING'       => 'bg-blue-100 text-blue-700',
                                'REJECTED'                  => 'bg-red-100 text-red-700',
                                default                     => 'bg-gray-100 text-gray-700',
                            };
                            $statusText = match($gatePass->status) {
                                'QC_AND_SECURITY_APPROVED'  => 'QC & Security Approved',
                                'SECURITY_APPROVAL_PENDING' => 'Security Approval Pending',
                                'QC_APPROVAL_PENDING'       => 'QC Approval Pending',
                                'REJECTED'                  => 'Rejected',
                                default                     => $gatePass->status,
                            };
                        @endphp
                        <span class="px-3 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                            {{ $statusText }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 pt-4 border-t border-gray-100">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Source Location</p>
                            <p class="text-sm text-gray-800">{{ $gatePass->warehouseLocation->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Departure Date</p>
                            <p class="text-sm text-gray-800">
                                {{ $gatePass->departure_date instanceof \Carbon\Carbon ? $gatePass->departure_date->format('d M Y, H:i') : \Carbon\Carbon::parse($gatePass->departure_date)->format('d M Y, H:i') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Prepared By</p>
                            <p class="text-sm text-gray-800">{{ $gatePass->preparedBy?->full_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Created At</p>
                            <p class="text-sm text-gray-800">{{ $gatePass->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        @if($gatePass->warranty_days)
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Warranty (Days)</p>
                                <p class="text-sm text-gray-800">{{ $gatePass->warranty_days }}</p>
                            </div>
                        @endif
                        @if($gatePass->invoice_no)
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Invoice No</p>
                                <p class="text-sm text-gray-800">{{ $gatePass->invoice_no }}</p>
                            </div>
                        @endif
                        @if($gatePass->customer_info)
                            <div class="sm:col-span-2">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Customer Info</p>
                                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $gatePass->customer_info }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Products / Manifest Card -->
                @php
                    $activeItems    = $gatePass->items->whereNull('returned_at');
                    $returnedItems  = $gatePass->items->whereNotNull('returned_at');
                    $categoryCounts = $activeItems->groupBy('category_name')->map->count()->sortDesc();
                    $totalItems     = $gatePass->items->count();
                    $activeCount    = $activeItems->count();
                    $returnedCount  = $returnedItems->count();
                    $totalQty       = $gatePass->items->sum(function($item) {
                        return $item->product?->manual_status == 1 ? $item->quantity : 1;
                    });
                @endphp
                <div class="bg-white rounded-md border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-800">Manifest Items</h3>
                        <div class="flex items-center gap-3 text-xs font-medium text-gray-500">
                            <span>Qty: <span class="font-semibold text-gray-800">{{ $totalQty }}</span></span>
                            <span>Items: <span class="font-semibold text-gray-800">{{ $totalItems }}</span></span>
                            @if($returnedCount > 0)
                                <span class="text-orange-600">Returned: <span class="font-semibold">{{ $returnedCount }}</span></span>
                                <span class="text-green-600">Active: <span class="font-semibold">{{ $activeCount }}</span></span>
                            @endif
                        </div>
                    </div>

                    {{-- Category Summary (screen only) --}}
                    @if($categoryCounts->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mb-4 no-print">
                            @foreach($categoryCounts as $category => $count)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 border border-blue-100 rounded-full text-xs font-medium text-blue-800">
                                    {{ $category ?? 'Uncategorized' }}
                                    <span class="bg-[#0078C8] text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif



                    <div class="border border-gray-200 rounded overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">#</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Unique ID</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Product Name</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Category Name</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Specification</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Qty</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @php $rowNum = 1; @endphp
                                {{-- Active items --}}
                                @forelse($activeItems as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-xs text-gray-400">{{ $rowNum++ }}</td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-900">
                                            <a href="javascript:void(0)" onclick="openEditProductModal({{ $item->product_id }})"
                                               class="hover:text-[#0078C8] transition-colors print-no-link">
                                                {{ $item->unique_id }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $item->product_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $item->category_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            @if($item->product && $item->product->specifications && count($item->product->specifications) > 0)
                                                <div class="flex flex-col gap-0.5">
                                                    @foreach($item->product->specifications as $spec)
                                                        @php
                                                            $specKey   = is_array($spec) ? ($spec['key']   ?? '') : $spec;
                                                            $specValue = is_array($spec) ? ($spec['value'] ?? '') : '';
                                                        @endphp
                                                        @if($specKey)
                                                            <span class="text-[11px] text-gray-500 whitespace-nowrap"><span class="font-medium text-gray-700 capitalize">{{ str_replace('_', ' ', $specKey) }}:</span> {{ $specValue ?: '—' }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $item->product?->manual_status == 1 ? $item->quantity : 1 }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                                Active
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    @if($returnedItems->isEmpty())
                                        <tr>
                                            <td colspan="7" class="px-4 py-6 text-center text-gray-400 text-sm">No items found</td>
                                        </tr>
                                    @endif
                                @endforelse
                                {{-- Returned items --}}
                                @foreach($returnedItems as $item)
                                    <tr class="bg-orange-50">
                                        <td class="px-4 py-3 text-xs text-gray-400">{{ $rowNum++ }}</td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-400 line-through">
                                            <a href="javascript:void(0)" onclick="openEditProductModal({{ $item->product_id }})"
                                               class="hover:text-[#0078C8] transition-colors print-no-link">
                                                {{ $item->unique_id }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-400 line-through">{{ $item->product_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-400 line-through">{{ $item->category_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-400">
                                            @if($item->product && $item->product->specifications && count($item->product->specifications) > 0)
                                                <div class="flex flex-col gap-0.5">
                                                    @foreach($item->product->specifications as $spec)
                                                        @php
                                                            $specKey   = is_array($spec) ? ($spec['key']   ?? '') : $spec;
                                                            $specValue = is_array($spec) ? ($spec['value'] ?? '') : '';
                                                        @endphp
                                                        @if($specKey)
                                                            <span class="text-[11px] whitespace-nowrap"><span class="capitalize">{{ str_replace('_', ' ', $specKey) }}:</span> {{ $specValue ?: '—' }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-400">{{ $item->product?->manual_status == 1 ? $item->quantity : 1 }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">
                                                Returned
                                            </span>
                                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $item->returned_at->format('d M Y, H:i') }}</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @if($totalItems > 0)
                                <tfoot class="bg-gray-50 border-t border-gray-200">
                                    <tr>
                                        <td colspan="7" class="px-4 py-2.5 text-xs font-semibold text-gray-700 text-right">
                                            Total Qty: {{ $totalQty }}
                                            &nbsp;·&nbsp; Active: {{ $activeCount }}
                                            @if($returnedCount > 0)
                                                &nbsp;·&nbsp; Returned: {{ $returnedCount }}
                                                &nbsp;·&nbsp; Total Items: {{ $totalItems }}
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Print Note -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-md px-5 py-4">
                    <p class="text-xs font-semibold text-yellow-800 uppercase tracking-wide mb-1">Note</p>
                    <p class="text-sm text-yellow-900">Please do not remove the sticker from the backside of the item. If the sticker is removed, the item will not be eligible for return or repair. Thank you.
                        @if((int)$gatePass->warranty_days > 0)
                            <br><br><strong>Warranty:</strong> This gate pass includes a check warranty of {{ $gatePass->warranty_days }} {{ Str::plural('day', $gatePass->warranty_days) }}.
                        @endif
                    </p>
                </div>

            </div>
            <!-- END LEFT COLUMN -->

            <!-- RIGHT COLUMN -->
            <div class="w-72 flex flex-col gap-4">

                <!-- QC Approval Card -->
                <div class="bg-white rounded-md border border-gray-200  p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">QC Approval</h3>
                        @if($gatePass->qc_approved_at)
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Approved</span>
                        @else
                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs font-medium rounded-full">Pending</span>
                        @endif
                    </div>

                    @if($gatePass->qc_remarks || $gatePass->qc_approved_at)
                        <div class="space-y-3">
                            @if($gatePass->qc_remarks)
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-1">Remarks</p>
                                    <p class="text-sm text-gray-700 bg-gray-50 rounded px-3 py-2 border border-gray-100">
                                        {{ $gatePass->qc_remarks }}
                                    </p>
                                </div>
                            @endif
                            @if($gatePass->qc_approved_at)
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-1">Approved At</p>
                                    <p class="text-sm text-gray-700">{{ $gatePass->qc_approved_at->format('d M Y, H:i') }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-400">QC approval pending.</p>
                        @if($gatePass->canApproveQC())
                            <button type="button" onclick="showQcModal({{ $gatePass->id }})"
                                class="mt-3 w-full px-3 py-2 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors">
                                Approve QC Now
                            </button>
                        @endif
                    @endif
                </div>

                <!-- Security Approval Card -->
                <div class="bg-white rounded-md border border-gray-200  p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Security Approval</h3>
                        @if($gatePass->security_approved_at)
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Approved</span>
                        @else
                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs font-medium rounded-full">Pending</span>
                        @endif
                    </div>

                    @if($gatePass->security_incharge_name || $gatePass->security_approved_at)
                        <div class="space-y-3">
                            @if($gatePass->security_incharge_name)
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-1">Security In charge</p>
                                    <p class="text-sm text-gray-700 bg-gray-50 rounded px-3 py-2 border border-gray-100">
                                        {{ $gatePass->security_incharge_name }}
                                    </p>
                                </div>
                            @endif
                            @if($gatePass->security_approved_at)
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-1">Approved At</p>
                                    <p class="text-sm text-gray-700">{{ $gatePass->security_approved_at->format('d M Y, H:i') }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-400">Security approval pending.</p>
                        @if($gatePass->canApproveSecurity())
                            <button type="button" onclick="showSecurityModal({{ $gatePass->id }})"
                                class="mt-3 w-full px-3 py-2 bg-purple-600 text-white text-xs font-medium rounded hover:bg-purple-700 transition-colors">
                                Approve Security Now
                            </button>
                        @endif
                    @endif
                </div>

                <!-- Authorized Signature Card -->
                <div class="bg-white rounded-md border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-6">Authorized Signature</h3>
                    <div class="text-right">
                        <p class="text-sm text-gray-700 mb-2">
                            {{ $gatePass->preparedBy?->full_name ?? auth('web')->user()?->full_name ?? 'N/A' }}
                        </p>
                        <div class="border-t border-gray-800 pt-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-800">Authorized Signature</p>
                        </div>
                        @if($gatePass->invoice_no)
                            <p class="text-xs text-gray-500 mt-2">Invoice No</p>
                            <p class="text-sm font-medium text-gray-800">{{ $gatePass->invoice_no }}</p>
                        @endif
                    </div>
                </div>

            </div>
            <!-- END RIGHT COLUMN -->

        </div>
    </div>
</div>

<!-- QC Modal -->
<div id="qcModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">QC Approval</h3>
                <button onclick="hideQcModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="qcApprovalForm" class="px-6 py-5">
                @csrf
                <label class="block text-xs font-medium text-gray-600 mb-1.5">QC Remarks</label>
                <textarea name="qc_remarks" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none resize-none placeholder:text-gray-400"
                    placeholder="Enter QC remarks..."></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="hideQcModal()"
                        class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700">Approve QC</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Modal -->
<div id="securityModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Security Approval</h3>
                <button onclick="hideSecurityModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="securityApprovalForm" class="px-6 py-5">
                @csrf
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    Security In charge Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="security_incharge_name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none placeholder:text-gray-400"
                    placeholder="Enter security in charge name">
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="hideSecurityModal()"
                        class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white text-xs font-medium rounded hover:bg-purple-700">Approve Security</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm modal-backdrop"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col z-10">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Edit Product</h3>
                <button onclick="hideEditProductModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <!-- Modal Body (Loaded via AJAX) -->
            <div id="editProductModalBody" class="p-6 overflow-y-auto flex-1 bg-white">
                <div class="flex justify-center items-center py-10">
                    <svg class="animate-spin h-8 w-8 text-[#0078C8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @media print {
        /* ── Hide chrome: sidebar, header, modals, overlay, notifications, action buttons ── */
        #sidebar,
        header,
        #overlay,
        #qcModal,
        #securityModal,
        #editProductModal,
        #notificationContainer,
        #exportNotification,
        #exportNotificationBadge,
        .no-print {
            display: none !important;
        }

        /* ── Remove the left margin that compensates for the fixed sidebar ── */
        [class~="lg:ml-64"] {
            margin-left: 0 !important;
            width: 100% !important;
        }

        /* ── Unlock overflow so the full page content actually renders ── */
        [class~="overflow-hidden"],
        [class~="overflow-y-auto"] {
            overflow: visible !important;
            height: auto !important;
        }

        /* ── Clean backgrounds ── */
        body {
            background: white !important;
            margin: 0 !important;
        }
        main {
            background: white !important;
            padding: 0 !important;
            overflow: visible !important;
        }
        .min-h-screen {
            background: white !important;
            min-height: auto !important;
            padding: 4px 0 !important;
        }
        .max-w-12xl {
            max-width: 100% !important;
            padding: 0 !important;
        }

        /* ── Remove decorative shadows ── */
        * { box-shadow: none !important; }

        /* ── Preserve background colors for badges and status pills ── */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ── Table links look like plain text ── */
        .print-no-link { color: inherit !important; text-decoration: none !important; }

        @page { margin: 1.2cm; size: A4; }
    }
</style>
@endpush

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ---- Toast ----
    function showToast(message, type = 'success') {
        // Uses parent layout toast if available, otherwise a simple alert fallback
        if (typeof window.showToast === 'function' && window.showToast !== showToast) {
            window.showToast(message, type);
        } else {
            const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
            const toast = document.createElement('div');
            toast.style.cssText = `position:fixed;top:1rem;right:1rem;z-index:99999;background:white;border:1px solid #e5e7eb;border-left:4px solid ${colors[type]||colors.info};padding:12px 16px;border-radius:6px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.1);max-width:360px;`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3500);
        }
    }

    // ---- Modals ----
    let currentGatePassId = null;

    window.showQcModal = function (id) { currentGatePassId = id; document.getElementById('qcModal').classList.remove('hidden'); };
    window.hideQcModal = function ()   { currentGatePassId = null; document.getElementById('qcModal').classList.add('hidden'); };
    window.showSecurityModal = function (id) { currentGatePassId = id; document.getElementById('securityModal').classList.remove('hidden'); };
    window.hideSecurityModal = function ()   { currentGatePassId = null; document.getElementById('securityModal').classList.add('hidden'); };

    function submitApproval(formId, urlPath, hideModal) {
        const form      = document.getElementById(formId);
        const submitBtn = form.querySelector('button[type="submit"]');
        const original  = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Approving...';

        fetch(urlPath, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                hideModal();
                setTimeout(() => data.redirect ? (window.location.href = data.redirect) : window.location.reload(), 1500);
            } else {
                submitBtn.disabled = false;
                submitBtn.textContent = original;
            }
        })
        .catch(() => {
            showToast('Network error. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = original;
        });
    }

    document.getElementById('qcApprovalForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentGatePassId) return;
        submitApproval('qcApprovalForm', `{{ url('warehouse/gate-pass') }}/${currentGatePassId}/qc-approval`, hideQcModal);
    });

    document.getElementById('securityApprovalForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentGatePassId) return;
        submitApproval('securityApprovalForm', `{{ url('warehouse/gate-pass') }}/${currentGatePassId}/security-approval`, hideSecurityModal);
    });

    // Close on backdrop click
    ['qcModal','securityModal'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function (e) {
            if (e.target === this || e.target.classList.contains('absolute')) {
                id === 'qcModal' ? hideQcModal() : hideSecurityModal();
            }
        });
    });

    // Session messages
    @if(session('success')) showToast('{{ session('success') }}', 'success'); @endif
    @if(session('error'))   showToast('{{ session('error') }}',   'error');   @endif

    // ---- Edit Product Modal ----
    window.openEditProductModal = function (productId) {
        const modal = document.getElementById('editProductModal');
        const body = document.getElementById('editProductModalBody');
        
        body.innerHTML = `
            <div class="flex justify-center items-center py-20">
                <svg class="animate-spin h-8 w-8 text-[#0078C8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        `;
        modal.classList.remove('hidden');

        const editUrl = `{{ route('warehouse.products.edit', ':id') }}`.replace(':id', productId);
        const redirectUrl = encodeURIComponent(`{{ request()->fullUrl() }}`);
        
        fetch(`${editUrl}?redirect_to=${redirectUrl}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            body.innerHTML = html;

            // Scripts inside HTML inserted with innerHTML do not execute automatically.
            // Recreate them so the specification editor and the other product controls initialize.
            Array.from(body.querySelectorAll('script')).forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.textContent));
                oldScript.replaceWith(newScript);
            });

            if (window.jQuery && jQuery.fn.select2) {
                jQuery('#editProductModalBody .select2').select2({
                    width: '100%',
                    dropdownParent: jQuery('#editProductModal')
                });
            }
        })
        .catch(error => {
            console.error('Error loading product edit form:', error);
            body.innerHTML = `
                <div class="text-center py-10 text-red-600 font-medium">
                    Failed to load the edit form. Please try again.
                </div>
            `;
        });
    };

    window.hideEditProductModal = function () {
        document.getElementById('editProductModal').classList.add('hidden');
    };

    document.getElementById('editProductModal')?.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-backdrop')) {
            hideEditProductModal();
        }
    });
</script>
@endpush
@endsection
