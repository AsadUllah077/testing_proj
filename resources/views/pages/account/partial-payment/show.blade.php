@extends('layouts.auth')
@section('title', 'Payment Allocation Details: ' . $customerPayment->reference_no)

@section('content')
<div class="p-4">
    <div class="flex justify-between items-start mb-4 p-5 bg-white rounded-lg">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Payment Breakdown: {{ $customerPayment->reference_no }}</h1>
            <p class="text-sm text-gray-500">Allocation details recorded on {{ dateFormat($customerPayment->payment_date) }}</p>
        </div>
        <div>
            @if ($customerPayment->status === 'draft')
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider rounded bg-gray-100 text-gray-500 border border-gray-200">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> Draft Allocation
                    </span>
                    <form action="{{ route('account.partial-payment.confirm', $customerPayment->id) }}" method="POST" onsubmit="return confirm('Confirm this allocation? This will update invoice balances and record accounting transactions.')">
                        @csrf
                        <button type="submit" class="bg-[#0078C8] text-white text-xs font-bold px-4 py-2 rounded-[3px] hover:bg-blue-700 shadow-sm transition-all">
                            <i class="fa-solid fa-check-double mr-1"></i> Confirm & Finalize
                        </button>
                    </form>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider rounded bg-green-100 text-green-600 border border-green-200">
                        <i class="fa-solid fa-circle-check mr-1"></i> Confirmed & Processed
                    </span>
                    <a href="{{ route('account.partial-payment.receipt', $customerPayment->id) }}" 
                       class="border border-[#0078C8] text-[#0078C8] text-xs font-bold px-4 py-2 rounded-[3px] hover:bg-blue-50 transition-all">
                        <i class="fa-solid fa-file-pdf mr-1"></i> Download Receipt PDF
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if ($customerPayment->status === 'draft')
    <div class="mb-4 p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-700 flex items-center gap-3 rounded-r shadow-sm">
        <i class="fa-solid fa-circle-exclamation text-lg"></i>
        <div>
            <p class="text-sm font-bold">Draft Allocation</p>
            <p class="text-xs">This payment has not yet been applied to the customer's balance or recorded in the General Ledger. Click "Confirm & Finalize" to process it.</p>
        </div>
    </div>
    @endif

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-white p-4 rounded-[5px] border border-gray-200 shadow-sm">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Customer</span>
            <span class="text-sm font-bold text-[#0078C8]">{{ $customerPayment->customer->name }}</span>
        </div>
        <div class="bg-white p-4 rounded-[5px] border border-gray-200 shadow-sm">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Amount</span>
            <span class="text-sm font-bold text-green-600">{{ numberFormat($customerPayment->total_amount) }}</span>
        </div>
        <div class="bg-white p-4 rounded-[5px] border border-gray-200 shadow-sm">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Payment Method</span>
            <span class="text-sm font-bold text-gray-700">{{ $customerPayment->account->name }}</span>
        </div>
        <div class="bg-white p-4 rounded-[5px] border border-gray-200 shadow-sm">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Status</span>
            <span class="text-sm font-bold {{ $customerPayment->status === 'draft' ? 'text-gray-500' : 'text-green-600' }}">
                {{ ucfirst($customerPayment->status) }}
            </span>
        </div>
    </div>

    @if ($customerPayment->status === 'confirmed')
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-[5px] flex items-center justify-between text-xs text-green-700">
        <div class="flex items-center gap-6">
            <div>
                <span class="font-semibold uppercase opacity-70 block mb-1 tracking-tight">Confirmed By</span>
                <span class="font-bold border-b border-green-200 pb-0.5">{{ $customerPayment->confirmedBy->username ?? 'System' }}</span>
            </div>
            <div class="h-8 w-px bg-green-200"></div>
            <div>
                <span class="font-semibold uppercase opacity-70 block mb-1 tracking-tight">Confirmed At</span>
                <span class="font-bold border-b border-green-200 pb-0.5">{{ $customerPayment->confirmed_at->format('d M Y, h:i A') }}</span>
            </div>
        </div>
        <div class="hidden md:block italic opacity-75">
            <i class="fa-solid fa-lock mr-1"></i> Ledger entries created and locked
        </div>
    </div>
    @endif

    @if ($customerPayment->note)
    <div class="bg-white p-4 rounded-[5px] border border-gray-200 shadow-sm mb-4">
        <span class="block text-xs font-semibold text-gray-400 uppercase mb-1">Note</span>
        <p class="text-sm text-gray-600 italic">"{{ $customerPayment->note }}"</p>
    </div>
    @endif

    <div class="bg-white rounded-[5px] border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Invoice #</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Invoice Total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Balance Before</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Applied Amount</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Remaining After</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customerPayment->items as $item)
                @php
                    $displayRemaining = $customerPayment->status === 'draft' ? max(0, $item->sale->remaining_amount) : max(0, $item->invoice_remaining);
                    $balanceAfter = $displayRemaining - $item->amount_to_apply;
                    $isOverAllocated = $balanceAfter < 0;
                @endphp
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors {{ $isOverAllocated ? 'bg-red-50/50' : '' }}">
                    <td class="px-4 py-3 font-semibold text-[#0078C8]">
                        {{ $item->sale->invoice_no ?: $item->sale->unique_no }}
                        @if($isOverAllocated)
                            <span class="block text-[10px] text-red-500 font-bold uppercase mt-0.5"><i class="fa-solid fa-triangle-exclamation"></i> Over-Allocated</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ dateFormat($item->sale->date) }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ numberFormat($item->invoice_total) }}</td>
                    <td class="px-4 py-3 text-right text-gray-600 font-medium {{ $customerPayment->status === 'draft' ? 'text-blue-600' : '' }}" title="{{ $customerPayment->status === 'draft' ? 'Live Balance' : 'Balance at confirmation' }}">
                        {{ numberFormat($displayRemaining) }}
                        @if($customerPayment->status === 'draft')
                            <i class="fa-solid fa-rotate text-[9px] ml-1 opacity-50" title="Live updating"></i>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-green-600">{{ numberFormat($item->amount_to_apply) }}</td>
                    <td class="px-4 py-3 text-right font-semibold {{ $isOverAllocated ? 'text-red-600' : ($balanceAfter > 0 ? 'text-amber-600' : 'text-gray-400') }}">
                        {{ numberFormat($balanceAfter) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-right">Total Applied:</td>
                    <td class="px-4 py-3 text-right text-green-700 text-lg">{{ numberFormat($customerPayment->total_amount) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mt-5 flex items-center justify-between">
        <a href="{{ route('account.partial-payment.index') }}" 
           class="px-5 py-2 bg-gray-100 border border-gray-300 rounded text-sm text-gray-600 hover:bg-gray-200 transition-colors">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back to History
        </a>
        
        <div class="text-xs text-center text-gray-400 flex items-center gap-4">
            <span>Created At: {{ $customerPayment->created_at->format('d M Y, h:i A') }}</span>
        </div>
    </div>
</div>
@endsection
