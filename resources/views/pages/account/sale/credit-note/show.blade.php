@extends('layouts.auth')

@section('title', 'Credit Note Details')

@section('content')
    @php
        $allocatedAmount = (float) ($creditNote->allocated_amount ?? 0);
        $refundedAmount = (float) ($creditNote->paid_amount ?? 0);
        $availableAmount = (float) ($creditNote->remaining_amount ?? 0);
        $creditNoteUsageStatus = $availableAmount <= 0
            ? ($allocatedAmount > 0 && $refundedAmount > 0
                ? 'Fully Used'
                : ($allocatedAmount > 0 ? 'Fully Allocated' : 'Fully Refunded'))
            : ($allocatedAmount > 0 || $refundedAmount > 0 ? 'Partially Used' : 'Available Credit');
    @endphp
    <div class="mb-8 p-4">
        <!-- Module Tabs -->
        <div class="mb-6">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            <!-- Header Card -->
            <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden">
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
                            <h2 class="text-gray-900 text-xl font-normal">Credit Note Details</h2>
                            <p class="text-gray-600 text-sm mt-1">Credit Note #{{ $creditNote->unique_no }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 relative">
                        <!-- Export Dropdown -->

                        @if ($availableAmount > 0 && $openSales->isNotEmpty())
                            <button type="button" onclick="toggleCreditNoteModal('creditAllocationModal', true)"
                                class="px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded">APPLY TO INVOICE</button>
                        @else
                            <div></div>
                        @endif
                        @if ($availableAmount > 0)
                            <button type="button" onclick="toggleCreditNoteModal('customerRefundModal', true)"
                                class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded">REFUND CUSTOMER</button>
                        @else
                            <div></div>
                        @endif
                        <!-- Back to List Button -->
                        <a href="{{ get_index_url('account.sale.credit-notes.index') }}"
                            class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#0078C8] flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            BACK TO LIST
                        </a>
                    </div>
                </div>
                <!-- Status and Summary Bar -->
                <div class="bg-gray-50 px-8 py-4 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <!-- Status Badge -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-700">Status:</span>
                                @php
                                    $statusConfig = [
                                        0 => [
                                            'text' => 'Draft',
                                            'color' => 'bg-gray-100 text-gray-800 border-gray-300',
                                            'icon' => '📋',
                                        ],
                                        1 => [
                                            'text' => 'Approved',
                                            'color' => 'bg-green-100 text-green-800 border-green-300',
                                            'icon' => '✅',
                                        ],
                                        2 => [
                                            'text' => 'Cancelled',
                                            'color' => 'bg-red-100 text-red-800 border-red-300',
                                            'icon' => '❌',
                                        ],
                                        3 => [
                                            'text' => 'Refunded',
                                            'color' => 'bg-blue-100 text-blue-800 border-blue-300',
                                            'icon' => '💳',
                                        ],
                                    ];
                                    $status = $statusConfig[$creditNote->status] ?? $statusConfig[0];
                                @endphp
                                <span
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold border {{ $status['color'] }}">
                                    <span>{{ $status['icon'] }}</span>
                                    {{ $status['text'] }}
                                </span>
                            </div>

                            <!-- Credit Usage Status Badge -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-700">Credit Usage:</span>
                                @php
                                    $paymentStatus = $creditNoteUsageStatus;
                                    $paymentColors = [
                                        'Fully Allocated' => [
                                            'color' => 'bg-green-100 text-green-800 border-green-300',
                                            'icon' => 'OK',
                                        ],
                                        'Fully Used' => [
                                            'color' => 'bg-green-100 text-green-800 border-green-300',
                                            'icon' => 'OK',
                                        ],
                                        'Fully Refunded' => [
                                            'color' => 'bg-green-100 text-green-800 border-green-300',
                                            'icon' => 'OK',
                                        ],
                                        'Partially Used' => [
                                            'color' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                            'icon' => '!',
                                        ],
                                        'Available Credit' => [
                                            'color' => 'bg-blue-100 text-blue-800 border-blue-300',
                                            'icon' => 'CR',
                                        ],
                                    ];
                                    $payment = $paymentColors[$paymentStatus] ?? $paymentColors['Available Credit'];
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
                                {{ $creditNote->currency->symbol ?? '' }} {{ numberFormat($creditNote->total_after_discount) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="p-8">
                    <div
                        class="mb-6 rounded-lg border {{ $availableAmount <= 0 ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }} p-4">
                        <h3 class="text-sm font-semibold {{ $availableAmount <= 0 ? 'text-green-900' : 'text-amber-900' }}">
                            Credit note allocation summary
                        </h3>
                        <p class="mt-1 text-sm {{ $availableAmount <= 0 ? 'text-green-800' : 'text-amber-800' }}">
                            @if ($allocatedAmount > 0)
                                <strong>{{ $creditNote->currency->symbol ?? '' }} {{ numberFormat($allocatedAmount) }}</strong>
                                has been allocated to customer invoices.
                            @endif
                            @if ($refundedAmount > 0)
                                The customer has been refunded
                                <strong>{{ $creditNote->currency->symbol ?? '' }} {{ numberFormat($refundedAmount) }}</strong>.
                            @endif
                            @if ($allocatedAmount <= 0 && $refundedAmount <= 0)
                                This credit note is still available to allocate or refund.
                            @endif

                            @if ($availableAmount <= 0)
                                No credit remains available.
                            @else
                                Available credit remaining:
                                <strong>{{ $creditNote->currency->symbol ?? '' }} {{ numberFormat($availableAmount) }}</strong>.
                            @endif
                        </p>
                    </div>

                    @if ($creditNote->allocations->isNotEmpty() || $creditNote->refunds->isNotEmpty())
                        <div class="mb-8 bg-white border border-gray-300 rounded-lg p-6">
                            <h3 class="mb-4 text-lg font-semibold text-gray-800">Settlement History</h3>
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 border-b">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Date</th>
                                            <th class="px-4 py-3 text-left">Type</th>
                                            <th class="px-4 py-3 text-left">Reference</th>
                                            <th class="px-4 py-3 text-left">Invoice / Account</th>
                                            <th class="px-4 py-3 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($creditNote->allocations as $allocation)
                                            <tr>
                                                <td class="px-4 py-2">{{ $allocation->allocation_date->format('d M Y') }}</td>
                                                <td class="px-4 py-2 font-semibold text-emerald-700">Invoice allocation</td>
                                                <td class="px-4 py-2">{{ $allocation->sale?->unique_no ?? '—' }}</td>
                                                <td class="px-4 py-2">{{ $allocation->sale?->invoice_no ?? '—' }}</td>
                                                <td class="px-4 py-2 text-right font-semibold">{{ numberFormat($allocation->amount) }}</td>
                                            </tr>
                                        @endforeach
                                        @foreach ($creditNote->refunds as $refund)
                                            <tr>
                                                <td class="px-4 py-2">{{ $refund->refund_date->format('d M Y') }}</td>
                                                <td class="px-4 py-2 font-semibold text-indigo-700">Customer refund</td>
                                                <td class="px-4 py-2">{{ $refund->refund_no }}</td>
                                                <td class="px-4 py-2">{{ $refund->account?->name ?? 'Legacy account' }}</td>
                                                <td class="px-4 py-2 text-right font-semibold">{{ numberFormat($refund->amount) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Key Information Cards -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Customer Card -->
                        <div class="bg-white border border-gray-300 rounded-lg p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-[#0078C8] rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">Customer Information</h3>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Customer
                                        Name</label>
                                    <div class="mt-1 text-base font-semibold text-gray-900">
                                        {{ $creditNote->customer->name ?? 'N/A' }}
                                    </div>
                                </div>
                                @if ($creditNote->customer)
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="text-xs font-medium text-gray-600 uppercase tracking-wider">Email</label>
                                            <div class="mt-1 text-sm text-gray-700">
                                                {{ $creditNote->customer->email ?? 'N/A' }}
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="text-xs font-medium text-gray-600 uppercase tracking-wider">Phone</label>
                                            <div class="mt-1 text-sm text-gray-700">
                                                {{ $creditNote->customer->phone ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Credit Note Details Card -->
                        <div class="bg-white border border-gray-300 rounded-lg p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-[#0078C8] rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">Credit Note Details</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Credit Note
                                        No</label>
                                    <div
                                        class="mt-1 text-sm font-semibold text-gray-900 bg-gray-50 px-3 py-1.5 rounded-md border border-gray-300">
                                        {{ $creditNote->unique_no }}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Date</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ \Carbon\Carbon::parse($creditNote->date)->format('F d, Y') }}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Invoice
                                        No</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $creditNote->invoice_no ?? 'N/A' }}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Ref
                                        No</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $creditNote->ref_no ?? 'N/A' }}
                                    </div>
                                </div>
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
                                    <label class="text-xs font-medium text-gray-600">Sale Reference</label>
                                    <div class="mt-1 text-sm font-semibold text-gray-900 truncate flex items-center gap-2">
                                        {{ $creditNote->sale->unique_no ?? ($creditNote->sale_unique_no ?? 'N/A') }}
                                        @if ($creditNote->sale)
                                            <a href="{{ route('account.sale-invoice.show', $creditNote->sale->id) }}"
                                                class="text-[#0078C8] hover:text-[#006bb0]" title="View Sale">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        @endif
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
                                        {{ ucfirst(str_replace('_', ' ', $creditNote->payment_type ?? 'N/A')) }}
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
                                        {{ $creditNote->currency->code ?? 'N/A' }}
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
                                        {{ $creditNote->createdBy->username ?? 'N/A' }}
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
                                Credit Note Items
                                <span
                                    class="text-sm font-normal text-gray-600 bg-gray-100 px-2 py-1 rounded-full border border-gray-300">
                                    {{ count($creditNote->creditNoteItems) }} items
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
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Discount %</th>
                                        <th class="px-4 py-3 text-left border-r border-gray-300">Tax</th>
                                        <th class="px-4 py-3 text-left">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @php $total_dis = 0; @endphp
                                    @foreach ($creditNote->creditNoteItems as $item)
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
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300">
                                                    {{ $item->discount ?? '0' }}%
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 border-r border-gray-300">
                                                @if ($item->tax)
                                                    <span class="text-sm">{{ $item->tax->name ?? 'N/A' }}</span>
                                                    <span
                                                        class="text-xs text-gray-500 ml-1">({{ $item->tax->tax_rate ?? $item->tax->rate }}%)</span>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-[#0078C8] font-medium">
                                                {{ numberFormat($item->total) }}
                                            </td>
                                        </tr>
                                        @php
                                            $item_subtotal =
                                                $item->kg > 0 ? $item->price : $item->quantity * $item->price;
                                            $discount_percentage = $item->discount ?? 0;
                                            $discount_amount = ($item_subtotal * $discount_percentage) / 100;
                                            $total_dis += $discount_amount;
                                        @endphp
                                    @endforeach

                                    <!-- Total Row -->
                                    @if (count($creditNote->creditNoteItems) > 0)
                                        <tr class="bg-gray-50 font-semibold border-t border-gray-300">
                                            <td colspan="8"
                                                class="px-4 py-2 text-right text-gray-700 border-r border-gray-300">TOTAL:
                                            </td>
                                            <td class="px-4 py-2 text-left text-[#0078C8] font-bold">
                                                {{ $creditNote->currency->symbol ?? '' }}
                                                {{ numberFormat($creditNote->total_after_discount) }}
                                            </td>
                                        </tr>
                                    @endif

                                    <!-- Empty State -->
                                    @if (count($creditNote->creditNoteItems) === 0)
                                        <tr>
                                            <td colspan="9"
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
                                @if ($creditNote->note)
                                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                                        {{ $creditNote->note }}</p>
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
                                    <span class="font-medium text-gray-900">{{ $creditNote->currency->symbol ?? '' }}
                                        {{ numberFormat($creditNote->sub_total) }}</span>
                                </div>

                                <!-- Discount -->
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <span class="text-gray-700">Discount</span>
                                    <span class="font-medium text-[#e63946]">
                                        {{ $creditNote->currency->symbol ?? '' }} {{ numberFormat($total_dis) }}
                                    </span>
                                </div>

                                <!-- Tax -->
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <span class="text-gray-700">Tax</span>
                                    <span class="font-medium text-gray-900">{{ $creditNote->currency->symbol ?? '' }}
                                        {{ numberFormat($creditNote->tax) }}</span>
                                </div>

                                <!-- Grand Total -->
                                <div class="flex justify-between items-center py-4 border-b border-gray-300">
                                    <span class="text-lg font-semibold text-gray-900">Total Amount</span>
                                    <span
                                        class="text-xl font-bold text-[#0078C8]">{{ $creditNote->currency->symbol ?? '' }}
                                        {{ numberFormat($creditNote->total_after_discount) }}</span>
                                </div>

                                <!-- Allocated Amount -->
                                <div
                                    class="flex justify-between items-center py-3 bg-red-50 rounded-lg px-4 border border-red-200">
                                    <span class="font-medium text-red-800">Allocated to Invoice</span>
                                    <span class="font-bold text-red-900">{{ $creditNote->currency->symbol ?? '' }}
                                        {{ numberFormat($allocatedAmount) }}</span>
                                </div>

                                <!-- Paid Amount -->
                                <div
                                    class="flex justify-between items-center py-3 bg-green-50 rounded-lg px-4 border border-green-200">
                                    <span class="font-medium text-green-800">Refunded Amount</span>
                                    <span class="font-bold text-green-900">{{ $creditNote->currency->symbol ?? '' }}
                                        {{ numberFormat($creditNote->paid_amount) }}</span>
                                </div>

                                <!-- Remaining Amount -->
                                <div
                                    class="flex justify-between items-center py-4 bg-blue-50 rounded-lg px-4 border border-blue-200">
                                    <span class="font-bold text-blue-900">Available Credit</span>
                                    <span
                                        class="text-xl font-bold text-blue-900">{{ $creditNote->currency->symbol ?? '' }}
                                        {{ numberFormat($creditNote->remaining_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Sale Section -->
                    @if ($creditNote->sale)
                        <div class="mb-8">
                            <div class=" border border-blue-200 rounded-lg p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-2 bg-[#0078C8] rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-800">Related Sale Information</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-white border border-gray-300 rounded-lg p-4">
                                        <label
                                            class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-2 block">Sale
                                            Number</label>
                                        <div class="text-sm font-bold text-[#0078C8]">
                                            {{ $creditNote->sale->unique_no ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="bg-white border border-gray-300 rounded-lg p-4">
                                        <label
                                            class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-2 block">Sale
                                            Invoice</label>
                                        <div class="text-sm font-bold text-[#0078C8]">
                                            {{ $creditNote->sale->invoice_no ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="bg-white border border-gray-300 rounded-lg p-4">
                                        <label
                                            class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-2 block">Sale
                                            Date</label>
                                        <div class="text-sm font-bold text-[#0078C8]">
                                            {{ \Carbon\Carbon::parse($creditNote->sale->date ?? now())->format('F d, Y') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Audit Information -->
                    <div class="bg-gray-50 border border-gray-300 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Audit Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label
                                    class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-2 block">Created
                                    By</label>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center border border-blue-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#0078C8]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-gray-900">{{ $creditNote->createdBy->username ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-2 block">Created
                                    Date</label>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center border border-green-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-gray-900">{{ $creditNote->created_at->format('F d, Y') }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 uppercase tracking-wider mb-2 block">Last
                                    Updated</label>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center border border-purple-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-gray-900">{{ $creditNote->updated_at->format('F d, Y h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Transaction History -->
                    @if ($creditNote->transactions->count() > 0)
                        <div class="mb-8 mt-8">
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
                                            @foreach ($creditNote->transactions as $txn)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        {{ $txn->date->format('M d, Y') }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300">{{ $txn->description }}
                                                    </td>
                                                    <td class="px-4 py-2 border-r border-gray-300">
                                                        {{ $txn->account->name ?? 'N/A' }}</td>
                                                    <td class="px-4 py-2 border-r border-gray-300 text-black">
                                                        {{ formatAmount($txn->debit) }}
                                                    </td>
                                                    <td class="px-4 py-2 border-r border-gray-300 text-black">
                                                        {{ formatAmount($txn->credit) }}</td>
                                                    <td class="px-4 py-2">
                                                        @if ($txn->type !== 'CreditNoteRefund' && !str_starts_with((string) $txn->unique_no, 'REF-') && !str_starts_with((string) $txn->unique_no, 'CRF'))
                                                        <button class="text-[#0078C8] hover:underline text-sm"
                                                            onclick="openTxnModal({{ $txn->id }}, '{{ $txn->description }}', {{ $txn->account_id }}, '{{ \Carbon\Carbon::parse($txn->date)->format('Y-m-d') }}')">
                                                            Edit
                                                        </button>
                                                        @else
                                                            <span class="text-xs text-gray-400">Locked</span>
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
                </div>
            </div>
        </div>
    </div>

    @if ($availableAmount > 0 && $openSales->isNotEmpty())
        <div id="creditAllocationModal" class="hidden fixed inset-0 z-50 bg-gray-900/50 p-4">
            <div class="mx-auto mt-20 max-w-md rounded-lg bg-white p-5 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Apply Customer Credit to Invoice</h3>
                    <button type="button" onclick="toggleCreditNoteModal('creditAllocationModal', false)">&times;</button>
                </div>
                <form method="POST" action="{{ route('account.sale.credit-notes.allocate', $creditNote) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium">Open invoice</label>
                        <select name="sale_id" id="credit_allocation_sale_id" required onchange="limitCreditAllocation()"
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select invoice</option>
                            @foreach ($openSales as $sale)
                                <option value="{{ $sale->id }}" data-remaining="{{ (float) $sale->remaining_amount }}">
                                    {{ $sale->invoice_no ?? $sale->unique_no }} — due {{ numberFormat($sale->remaining_amount) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Allocation date</label>
                        <input type="date" name="allocation_date" value="{{ date('Y-m-d') }}" required class="date w-full rounded border px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Amount</label>
                        <input type="number" id="credit_allocation_amount" name="amount" min="0.01" step="0.01"
                            max="{{ $availableAmount }}" value="{{ $availableAmount }}" required class="w-full rounded border px-3 py-2 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Available credit: {{ numberFormat($availableAmount) }}</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="toggleCreditNoteModal('creditAllocationModal', false)" class="rounded border px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Apply Credit</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($availableAmount > 0)
        <div id="customerRefundModal" class="hidden fixed inset-0 z-50 bg-gray-900/50 p-4">
            <div class="mx-auto mt-20 max-w-md rounded-lg bg-white p-5 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Refund Customer</h3>
                    <button type="button" onclick="toggleCreditNoteModal('customerRefundModal', false)">&times;</button>
                </div>
                <form method="POST" action="{{ route('account.sale.credit-notes.refund', $creditNote) }}" class="space-y-4">
                    @csrf
                    <div><label class="mb-1 block text-sm font-medium">Refund date</label><input type="date" name="refund_date" value="{{ date('Y-m-d') }}" required class="date w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="mb-1 block text-sm font-medium">Amount</label><input type="number" name="amount" min="0.01" step="0.01" max="{{ $availableAmount }}" value="{{ $availableAmount }}" required class="w-full rounded border px-3 py-2 text-sm"></div>
                    <div><label class="mb-1 block text-sm font-medium">Bank / cash account</label><x-chart-account-bank-select name="account_id" id="customer_refund_account_id" /></div>
                    <div><label class="mb-1 block text-sm font-medium">Description</label><textarea name="description" rows="2" class="w-full rounded border px-3 py-2 text-sm"></textarea></div>
                    <div class="rounded bg-indigo-50 p-3 text-xs text-indigo-700">Journal: DR Accounts Receivable, CR Bank/Cash.</div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="toggleCreditNoteModal('customerRefundModal', false)" class="rounded border px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Refund Customer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        function toggleCreditNoteModal(id, show) { document.getElementById(id)?.classList.toggle('hidden', !show); }
        function limitCreditAllocation() {
            const select = document.getElementById('credit_allocation_sale_id');
            const input = document.getElementById('credit_allocation_amount');
            if (!select || !input || !select.selectedOptions.length) return;
            const maximum = Math.min(Number(select.selectedOptions[0].dataset.remaining || 0), {{ json_encode($availableAmount) }});
            input.max = maximum.toFixed(2);
            input.value = maximum.toFixed(2);
        }
    </script>
    @endpush
    <x-acccount.edit-transaction-modal-credit-note />
@endsection
