@extends('layouts.auth')

@section('title', 'Bill Details')

@section('content')
    @php
        $allocatedDebitNoteAmount = $bill->debitNotes->sum('allocated_amount');
        $availableDebitNoteAmount = $bill->debitNotes->unique('id')->sum('remaining_amount');
        $isFullySettled = (float) $bill->remaining_amount <= 0;
    @endphp
    <div class="mb-8">
        <!-- Module Tabs -->
        <div class="mb-6">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.bill') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            <!-- Header Card -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <div class="flex items-center gap-3 mb-4 md:mb-0">
                        <div class="p-2 bg-[#0078C8] rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-gray-900 text-xl font-normal">Bill Invoice Details</h2>
                            <p class="text-gray-600 text-sm mt-1">Bill #{{ $bill->invoice_no }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 relative">
                        <!-- Export Dropdown -->
                        <div class="relative">
                            <button onclick="toggleDropdown('exportDropdown')"
                                class="px-5 py-1.5 border border-[#0078C8] text-[#0078C8] rounded-lg hover:bg-[#0078C8] hover:text-white text-sm font-medium transition-all duration-200 flex items-center gap-2 justify-center w-full">
                                Export
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="exportDropdown"
                                class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                <ul class="py-1 text-sm">
                                    <li>
                                        <button onclick="startExport('pdf','bill-invoice')" data-url=""
                                            class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            PDF
                                        </button>
                                    </li>
                                    <li>
                                        <button onclick="startExport('xlsx','bill-invoice')"
                                            class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Excel
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div></div>
                        <!-- Back Button -->
                        <a href="{{ get_index_url('account.bill-invoice.index') }}"
                            class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            BACK
                        </a>
                    </div>
                </div>

                <!-- Status and Summary Bar -->
                <div class="bg-gray-50 px-8 py-4 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-700">Approval:</span>
                                <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-semibold border {{ $bill->approval_status === 'approved' ? 'bg-green-100 text-green-800 border-green-300' : ($bill->approval_status === 'void' ? 'bg-red-100 text-red-800 border-red-300' : 'bg-amber-100 text-amber-800 border-amber-300') }}">
                                    {{ ucfirst($bill->approval_status) }}
                                </span>
                            </div>
                            <!-- Payment Status Badge -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-700">Payment:</span>
                                @php
                                    $paymentStatus =
                                        $bill->remaining_amount <= 0
                                            ? 'Fully Paid'
                                            : ($bill->paid_amount > 0
                                                ? 'Partially Paid'
                                                : 'Unpaid');
                                    $paymentColors = [
                                        'Fully Paid' => [
                                            'color' => 'bg-green-100 text-green-800 border-green-300',
                                            'icon' => '💰',
                                        ],
                                        'Partially Paid' => [
                                            'color' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                            'icon' => '💸',
                                        ],
                                        'Unpaid' => [
                                            'color' => 'bg-red-100 text-red-800 border-red-300',
                                            'icon' => '⏳',
                                        ],
                                    ];
                                    $payment = $paymentColors[$paymentStatus] ?? $paymentColors['Unpaid'];
                                @endphp
                                <span
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold border {{ $payment['color'] }}">
                                    <span>{{ $payment['icon'] }}</span>
                                    {{ $paymentStatus }}
                                </span>
                            </div>
                        </div>

                        <!-- Total Amount -->
                        <div class="text-right">
                            <span class="text-sm font-medium text-gray-700">Total Amount</span>
                            <div class="mt-1 text-2xl font-bold text-[#0078C8]">
                                {{ $bill->currency->symbol ?? '' }} {{ numberFormat($bill->total) }}
                            </div>
                            <div class="mt-1 text-sm font-medium text-gray-600">
                                Base Total ({{ currency('code') }}):
                                {{ currency('symbol') }} {{ numberFormat($bill->base_currency_total ?? ($bill->total * ($bill->exchange_rate ?: 1))) }}
                                <span class="text-xs text-gray-500">at {{ rtrim(rtrim(number_format((float) ($bill->exchange_rate ?: 1), 8, '.', ''), '0'), '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="p-8">
                    @if ($bill->debitNotes->count() > 0)
                        <div
                            class="mb-6 rounded-lg border {{ $isFullySettled ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }} p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <h3
                                        class="text-sm font-semibold {{ $isFullySettled ? 'text-green-900' : 'text-amber-900' }}">
                                        Debit note applied to this bill
                                    </h3>
                                    <p class="mt-1 text-sm {{ $isFullySettled ? 'text-green-800' : 'text-amber-800' }}">
                                        Debit notes have reduced the payable balance by
                                        <strong>{{ $bill->currency->symbol ?? '' }} {{ numberFormat($allocatedDebitNoteAmount) }}</strong>.
                                        @if ($isFullySettled)
                                            This bill is fully settled.
                                        @else
                                            The remaining amount to pay/reconcile is
                                            <strong>{{ $bill->currency->symbol ?? '' }} {{ numberFormat($bill->remaining_amount) }}</strong>.
                                        @endif
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-right text-sm md:min-w-[360px]">
                                    <div>
                                        <div class="text-gray-500">Bill Total</div>
                                        <div class="font-semibold text-gray-900">
                                            {{ $bill->currency->symbol ?? '' }} {{ numberFormat($bill->total_after_discount) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Allocated Debit Notes</div>
                                        <div class="font-semibold text-red-700">
                                            -{{ $bill->currency->symbol ?? '' }} {{ numberFormat($allocatedDebitNoteAmount) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Payments</div>
                                        <div class="font-semibold text-green-700">
                                            {{ $bill->currency->symbol ?? '' }} {{ numberFormat($bill->paid_amount) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Remaining</div>
                                        <div class="font-semibold {{ $isFullySettled ? 'text-green-700' : 'text-amber-700' }}">
                                            {{ $bill->currency->symbol ?? '' }} {{ numberFormat($bill->remaining_amount) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if ($availableDebitNoteAmount > 0)
                                <p class="mt-3 text-sm text-amber-800">
                                    There is still {{ $bill->currency->symbol ?? '' }} {{ numberFormat($availableDebitNoteAmount) }}
                                    available on linked debit notes. Review the supplier credit before reconciling more payments.
                                </p>
                            @endif
                        </div>
                    @endif

                    <!-- Key Information Cards -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Vendor Card -->
                        <div class="bg-white border border-gray-300 rounded-lg p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-[#0078C8] rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">Vendor Information</h3>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Vendor
                                        Name</label>
                                    <div class="mt-1 text-base font-semibold text-gray-900">
                                        {{ $bill->vendor->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Details Card -->
                        <div class="bg-white border border-gray-300 rounded-lg p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-[#0078C8] rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">Bill Details</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Bill
                                        No</label>
                                    <div
                                        class="mt-1 text-sm font-semibold text-gray-900 bg-gray-50 px-3 py-1.5 rounded-md border border-gray-300">
                                        {{ $bill->invoice_no }}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Date</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $bill->date->format('F d, Y') }}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Ref
                                        No</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $bill->ref_no ?? 'N/A' }}
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="text-xs font-medium text-gray-600 uppercase tracking-wider">Currency</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $bill->currency->code ?? 'N/A' }}
                                    </div>
                                </div>
                                @foreach (['due_date' => 'Due date', 'posting_date' => 'Posting date', 'purchase_order_no' => 'Purchase order', 'goods_receipt_no' => 'GRN', 'project' => 'Project', 'cost_centre' => 'Cost centre'] as $field => $label)
                                    <div>
                                        <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">{{ $label }}</label>
                                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ str_ends_with($field, '_date') && $bill->{$field} ? dateFormat($bill->{$field}) : ($bill->{$field} ?: 'N/A') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white border border-gray-300 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#0078C8]" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Bill Reference</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 truncate">
                                        {{ $bill->invoice_no }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-300 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Payment Type</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $bill->account->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-300 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-purple-100 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Currency</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $bill->currency->code ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-300 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-orange-100 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Created By</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 truncate">
                                        {{ $bill->createdBy->username ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table Section -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#0078C8]" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Bill Items
                                <span
                                    class="text-sm font-normal text-gray-600 bg-gray-100 px-2 py-1 rounded-full border border-gray-300">
                                    {{ count($bill->billItems) }} items
                                </span>
                            </h3>
                        </div>

                        <div class="overflow-x-auto w-full p-4 border border-gray-300 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 border-b border-gray-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Item</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Description</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">HS Code</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Qty</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Kg</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Price</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Account</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Discount %</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Tax</th>
                                        <th class="px-4 py-3 text-left">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @php
                                        $total_dis = 0;
                                    @endphp
                                    @foreach ($bill->billItems as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 border-r border-gray-300">{{ $item->item ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 border-r border-gray-300">
                                                {{ $item->description ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 border-r border-gray-300">{{ $item->hs_code ?? 'N/A' }}
                                            </td>
                                            <td class="px-4 py-2 border-r border-gray-300">{{ $item->quantity }}</td>
                                            <td class="px-4 py-2 border-r border-gray-300">{{ $item->kg ?? '0' }}</td>
                                            <td class="px-4 py-2 border-r border-gray-300">
                                                {{ numberFormat($item->price) }}</td>
                                            <td class="px-4 py-2 border-r border-gray-300">
                                                {{ $item->account->name ?? 'N/A' }}</td>
                                            @php
                                                // Calculate item total before discount
                                                $item_total_before_discount = $item->quantity * $item->price;
                                                // Get discount percentage (assuming you store it as discount_percentage)
                                                $discount_percentage = $item->discount ?? 0;
                                                // Calculate discount amount
                                                $discount_amount =
                                                    ($item_total_before_discount * $discount_percentage) / 100;
                                                // Add to totals
                                                $total_dis += $discount_amount;
                                            @endphp
                                            <td class="px-4 py-2 border-r border-gray-300">
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300">
                                                    {{ $item->discount ?? '0' }}%
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 border-r border-gray-300">
                                                @if ($item->tax_name || $item->tax)
                                                    {{ $item->tax_name ?? $item->tax->name }}
                                                    ({{ number_format($item->tax_rate_snapshot ?? $item->tax->tax_rate ?? 0, 2) }}%)
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-[#0078C8] font-medium">
                                                {{ numberFormat($item->total) }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    <!-- Total Row -->
                                    @if (count($bill->billItems) > 0)
                                        <tr class="bg-gray-50 font-semibold border-t border-gray-300">
                                            <td colspan="9"
                                                class="px-4 py-2 text-right text-gray-700 border-r border-gray-300">TOTAL:
                                            </td>
                                            <td class="px-4 py-2 text-left text-[#0078C8] font-bold">
                                                {{ $bill->currency->symbol ?? '' }}
                                                {{ numberFormat($bill->total_after_discount) }}
                                            </td>
                                        </tr>
                                    @endif

                                    <!-- Empty State -->
                                    @if (count($bill->billItems) === 0)
                                        <tr>
                                            <td colspan="10"
                                                class="px-4 py-8 text-center text-gray-500 border-t border-gray-300">
                                                No items found.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes and Summary Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Notes Card -->
                        <div class="bg-white border border-gray-300 rounded-lg p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-[#0078C8] rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">Notes</h3>
                            </div>
                            <div class="bg-gray-50 border border-gray-300 rounded-lg p-4 min-h-[120px]">
                                @if ($bill->note)
                                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                                        {{ $bill->note }}
                                    </p>
                                @else
                                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm">No notes provided</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Summary Card -->
                        <div class="bg-white text-gray-900 rounded-lg p-6 border border-gray-300">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-2 bg-[#0078C8] rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Financial Summary</h3>
                            </div>

                            <div class="space-y-4">
                                <!-- Sub Total -->
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <span class="text-gray-700">Sub Total</span>
                                    <span class="font-medium text-gray-900">{{ $bill->currency->symbol ?? '' }}
                                        {{ numberFormat($bill->sub_total) }}</span>
                                </div>

                                <!-- Discount -->
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <span class="text-gray-700">Discount</span>
                                    <span class="font-medium text-[#e63946]">
                                        {{ $bill->currency->symbol ?? '' }} {{ numberFormat($bill->discount_total) }}
                                    </span>
                                </div>

                                @if (($bill->withholding_tax ?? 0) > 0)
                                    <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                        <span class="text-gray-700">Less withholding tax</span>
                                        <span class="font-medium text-red-700">-{{ $bill->currency->symbol ?? '' }} {{ numberFormat($bill->withholding_tax) }}</span>
                                    </div>
                                @endif

                                <!-- Tax -->
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <span class="text-gray-700">Tax</span>
                                    <span class="font-medium text-gray-900">{{ $bill->currency->symbol ?? '' }}
                                        {{ numberFormat($bill->tax) }}</span>
                                </div>

                                <!-- Grand Total -->
                                <div class="flex justify-between items-center py-4 border-b border-gray-300">
                                    <span class="text-lg font-semibold text-gray-900">Total Amount</span>
                                    <span class="text-xl font-bold text-[#0078C8]">{{ $bill->currency->symbol ?? '' }}
                                        {{ numberFormat($bill->total_after_discount) }}</span>
                                </div>

                                <!-- Less Debit Notes -->
                                @if ($bill->debitNotes->count() > 0)
                                    <div
                                        class="flex justify-between items-center py-3 bg-red-50 rounded-lg px-4 border border-red-200">
                                        <span class="font-medium text-red-800">Less Allocated Debit Notes</span>
                                        <span class="font-bold text-red-900">-{{ $bill->currency->symbol ?? '' }}
                                            {{ numberFormat($allocatedDebitNoteAmount) }}</span>
                                    </div>
                                @endif

                                <!-- Paid Amount -->
                                <div
                                    class="flex justify-between items-center py-3 bg-green-50 rounded-lg px-4 border border-green-200">
                                    <span class="font-medium text-green-800">Paid Amount</span>
                                    <span class="font-bold text-green-900">{{ $bill->currency->symbol ?? '' }}
                                        {{ numberFormat($bill->paid_amount) }}</span>
                                </div>

                                <!-- Remaining Amount -->
                                <div
                                    class="flex justify-between items-center py-4 bg-blue-50 rounded-lg px-4 border border-blue-200">
                                    <span class="font-bold text-blue-900">Remaining Amount</span>
                                    <span class="text-xl font-bold text-blue-900">{{ $bill->currency->symbol ?? '' }}
                                        {{ numberFormat($bill->remaining_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments Section -->
                    @if ($bill->attachments->count() > 0)
                        <div class="mb-8">
                            <div class="bg-white border border-blue-200 rounded-lg p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-2 bg-[#0078C8] rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-800">Attachments</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    @foreach ($bill->attachments as $attachment)
                                        <div class="bg-white border border-gray-300 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1 mr-3">
                                                    <label
                                                        class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-2 block">File
                                                        Name</label>
                                                    <div class="text-sm font-medium text-gray-900 truncate">
                                                        {{ $attachment->original_name }}
                                                    </div>
                                                </div>
                                                <a href="{{ route('account.bill-invoice.attachment.download', $attachment->id) }}" target="_blank"
                                                    class="text-[#0078C8] hover:underline text-xs whitespace-nowrap">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Linked Debit Notes -->
                    @if ($bill->debitNotes->count() > 0)
                        <div class="mb-8">
                            <div class="bg-white border border-gray-300 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#0078C8]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Linked Debit Notes
                                    </h3>
                                </div>

                                <div class="overflow-x-auto w-full p-4 border border-gray-300 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50 border-b border-gray-300">
                                            <tr>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Date</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">DRN No</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Total</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Allocated</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Available</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Status</th>
                                                <th class="px-4 py-3 text-left">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($bill->debitNotes as $drn)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        {{ $drn->date->format('M d, Y') }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300">{{ $drn->unique_no }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        {{ numberFormat($drn->total) }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300 text-red-700 font-semibold">
                                                        {{ numberFormat($drn->allocated_amount ?? 0) }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        {{ numberFormat($drn->remaining_amount ?? 0) }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        @if (($drn->remaining_amount ?? 0) <= 0)
                                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase border bg-green-100 text-green-700 border-green-200">
                                                                Fully Allocated
                                                            </span>
                                                        @else
                                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase border bg-amber-100 text-amber-700 border-amber-200">
                                                                Credit Available
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <a href="{{ route('account.debit-note.show', $drn) }}"
                                                            class="text-[#0078C8] hover:underline text-sm font-medium">
                                                            View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Payment Allocations -->
                    @if ($bill->paymentAllocations->count() > 0)
                        <div class="mb-8">
                            <div class="bg-white border border-gray-300 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#0078C8]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Payment Allocations (Vendor Payment Runs)
                                    </h3>
                                </div>

                                <div class="overflow-x-auto w-full p-4 border border-gray-300 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50 border-b border-gray-300">
                                            <tr>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Date</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Reference No</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Status</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Amount Paid</th>
                                                <th class="px-4 py-3 text-left">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($bill->paymentAllocations as $allocation)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        {{ $allocation->vendorPaymentRun->payment_date->format('M d, Y') }}
                                                    </td>
                                                    <td class="px-4 py-2 border-r border-gray-300 font-medium">
                                                        {{ $allocation->vendorPaymentRun->run_reference }}
                                                    </td>
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        @php
                                                            $statusBadge = match (
                                                                $allocation->vendorPaymentRun->status
                                                            ) {
                                                                'draft' => 'bg-gray-100 text-gray-500 border-gray-200',
                                                                'processed'
                                                                    => 'bg-green-100 text-green-600 border-green-200',
                                                                default => 'bg-blue-100 text-blue-600 border-blue-200',
                                                            };
                                                        @endphp
                                                        <span
                                                            class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $statusBadge }}">
                                                            {{ $allocation->vendorPaymentRun->status }}
                                                        </span>
                                                    </td>
                                                    <td
                                                        class="px-4 py-2 border-r border-gray-300 font-bold text-green-600">
                                                        {{ numberFormat($allocation->amount_to_pay) }}
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <div class="flex items-center gap-3">
                                                            <a href="{{ route('account.vendor-payment-run.show', $allocation->vendor_payment_run_id) }}"
                                                                class="text-[#0078C8] hover:underline text-xs font-medium">
                                                                View Details
                                                            </a>
                                                            @if ($allocation->vendorPaymentRun->status === 'processed')
                                                                <a href="{{ route('account.vendor-payment-run.remittance-pdf', $allocation->vendor_payment_run_id) }}"
                                                                    class="text-gray-500 hover:text-[#0078C8] transition-colors"
                                                                    title="Download Remittance">
                                                                    <i class="fa-solid fa-file-pdf"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Transaction History -->
                    @if ($bill->transactions->count() > 0)
                        <div class="mb-8">
                            <div class="bg-white border border-gray-300 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#0078C8]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Transaction History
                                    </h3>
                                </div>

                                <div class="overflow-x-auto w-full p-4 border border-gray-300 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50 border-b border-gray-300">
                                            <tr>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Date</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Description</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Account</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Debit</th>
                                                <th class="px-4 py-3 text-left border-r border-gray-300">Credit</th>
                                                <th class="px-4 py-3 text-left">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($bill->transactions as $txn)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        {{ $txn->date->format('M d, Y') }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300">{{ $txn->description }}
                                                    </td>
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        {{ $txn->account->name ?? 'N/A' }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300 text-black">
                                                        {{ formatAmount($txn->debit) }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300 text-black">
                                                        {{ formatAmount($txn->credit) }}</td>
                                                    <td class="px-4 py-2">
                                                        @if ((int) $txn->status === 0)
                                                        <button class="text-[#0078C8] hover:underline text-sm"
                                                            onclick="openTxnModal({{ $txn->id }}, '{{ $txn->description }}', {{ $txn->account_id }}, '{{ \Carbon\Carbon::parse($txn->date)->format('Y-m-d') }}')">
                                                            Edit
                                                        </button>
                                                        @else
                                                            <span class="text-xs text-gray-500">Posted</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white border border-gray-300 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Document versions</h3>
                            @forelse ($bill->versions as $version)
                                <div class="flex justify-between py-2 border-b text-sm">
                                    <span>Version {{ $version->version }} · {{ ucfirst($version->event) }}</span>
                                    <a class="text-[#0078C8]" href="{{ route('account.bill-invoice.version.pdf', [$bill->id, $version->version]) }}">PDF</a>
                                </div>
                            @empty <p class="text-sm text-gray-500">No versions recorded.</p> @endforelse
                        </div>
                        <div class="bg-white border border-gray-300 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Email delivery</h3>
                            @forelse ($bill->deliveries as $delivery)
                                <div class="py-2 border-b text-sm"><strong>{{ ucfirst($delivery->status) }}</strong> · {{ $delivery->recipient }} · {{ dateFormat($delivery->created_at) }}</div>
                            @empty <p class="text-sm text-gray-500">No delivery attempts.</p> @endforelse
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3">
                        <form action="{{ route('account.bill-invoice.duplicate', $bill->id) }}" method="POST">@csrf<button class="px-5 py-2 border border-[#0078C8] text-[#0078C8] rounded-md text-sm">DUPLICATE</button></form>
                        @if ($bill->approval_status === 'approved')
                            <form action="{{ route('account.bill-invoice.resend-email', $bill->id) }}" method="POST">@csrf<button class="px-5 py-2 border border-[#0078C8] text-[#0078C8] rounded-md text-sm">SEND EMAIL</button></form>
                        @endif
                        @if ($bill->approval_status === 'approved' && (float) $bill->paid_amount <= 0)
                            <form action="{{ route('account.bill-invoice.void', $bill->id) }}" method="POST" class="flex gap-2" onsubmit="return confirm('Void this bill and reverse its ledger posting?')">@csrf<input name="reason" required maxlength="1000" placeholder="Void reason" class="border rounded-md px-3 text-sm"><button class="px-5 py-2 border border-red-500 text-red-600 rounded-md text-sm">VOID</button></form>
                        @endif
                        @if ($bill->isEditable())
                        <a href="{{ route('account.bill-invoice.edit', $bill->id) }}"
                            class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                            EDIT INVOICE
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-acccount.edit-transaction-modal-bill />
@endsection
