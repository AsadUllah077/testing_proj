@extends('layouts.auth')
@section('title', 'Payment Run — ' . $vendorPaymentRun->run_reference)

@section('content')
<div class="p-4">
    <div class="flex justify-between items-start mb-4 bg-white rounded-lg p-5">
        <div>
            <a href="{{ route('account.vendor-payment-run.index') }}" class="text-sm text-[#0078C8] hover:underline">← Back</a>
            <h1 class="text-xl font-bold text-gray-800 mt-1">{{ $vendorPaymentRun->run_reference }}</h1>
            <p class="text-sm text-gray-500">Payment Date: {{ dateFormat($vendorPaymentRun->payment_date) }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('account.vendor-payment-run.remittance-pdf', $vendorPaymentRun) }}"
               class="border border-[#0078C8] text-[#0078C8] text-sm font-semibold px-4 py-1.5 rounded-[3px] hover:bg-blue-50">
               Download Remittance PDF
            </a>
            @if ($vendorPaymentRun->status === 'draft')
            <form method="POST" action="{{ route('account.vendor-payment-run.confirm', $vendorPaymentRun) }}">
                @csrf
                <button type="submit"
                    onclick="return confirm('Confirm this run? Payments will be applied to all selected bills.')"
                    class="bg-green-600 text-white text-sm font-semibold px-4 py-1.5 rounded-[3px] hover:bg-green-700">
                    Confirm & Process
                </button>
            </form>
            @endif
        </div>
    </div>

    @if ($vendorPaymentRun->status === 'draft')
    <div class="mb-4 p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-700 flex items-center gap-3 rounded-r shadow-sm">
        <i class="fa-solid fa-circle-exclamation text-lg"></i>
        <div>
            <p class="text-sm font-bold">Draft Payment Run</p>
            <p class="text-xs">This payment run has not yet been processed. Bills will not be updated and transactions will not be recorded until you click "Confirm & Process".</p>
        </div>
    </div>
    @endif

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">{{ session('success') }}</div>
    @endif

    {{-- Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-5 text-sm">
        <div class="bg-white border border-gray-200 rounded-[5px] p-4 shadow-sm flex flex-col justify-between">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Status</span>
            @php
                $badge = match($vendorPaymentRun->status) {
                    'draft'     => 'bg-gray-100 text-gray-500 border-gray-200',
                    'processed' => 'bg-green-100 text-green-600 border-green-200',
                    default     => 'bg-blue-100 text-blue-600 border-blue-200',
                };
            @endphp
            <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold uppercase border w-max {{ $badge }}">
                {{ ucfirst($vendorPaymentRun->status) }}
            </span>
        </div>
        <div class="bg-white border border-gray-200 rounded-[5px] p-4 shadow-sm">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Account</span>
            <span class="font-bold text-gray-700">{{ $vendorPaymentRun->account->name ?? '—' }}</span>
        </div>
        <div class="bg-white border border-gray-200 rounded-[5px] p-4 shadow-sm">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Bills</span>
            <span class="font-bold text-gray-700">{{ $vendorPaymentRun->items->count() }}</span>
        </div>
        <div class="bg-white border border-gray-200 rounded-[5px] p-4 shadow-sm">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Amount</span>
            <span class="font-bold text-green-600">{{ numberFormat($vendorPaymentRun->total_amount) }}</span>
        </div>
        <div class="bg-white border border-gray-200 rounded-[5px] p-4 shadow-sm">
            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Created By</span>
            <span class="font-bold text-gray-700">{{ $vendorPaymentRun->createdBy->username ?? 'System' }}</span>
        </div>
    </div>

    @if ($vendorPaymentRun->status === 'processed' && $vendorPaymentRun->confirmedBy)
    <div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-[5px] flex items-center justify-between text-xs text-green-700">
        <div class="flex items-center gap-6">
            <div>
                <span class="font-semibold uppercase opacity-70 block mb-1 tracking-tight">Confirmed By</span>
                <span class="font-bold border-b border-green-200 pb-0.5">{{ $vendorPaymentRun->confirmedBy->username ?? 'System' }}</span>
            </div>
            <div class="h-8 w-px bg-green-200"></div>
            <div>
                <span class="font-semibold uppercase opacity-70 block mb-1 tracking-tight">Processed At</span>
                <span class="font-bold border-b border-green-200 pb-0.5">{{ $vendorPaymentRun->confirmed_at ? $vendorPaymentRun->confirmed_at->format('d M Y, h:i A') : 'N/A' }}</span>
            </div>
        </div>
        <div class="italic opacity-75">
            <i class="fa-solid fa-lock mr-1"></i> Ledger entries locked
        </div>
    </div>
    @endif

    @if ($vendorPaymentRun->notes)
    <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-4 text-sm text-blue-800">
        <strong>Notes:</strong> {{ $vendorPaymentRun->notes }}
    </div>
    @endif

    {{-- Items Table --}}
    <div class="bg-white rounded-[5px] border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-800">Bills Included</h2>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bill #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bill Date</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bill Total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Was Remaining</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vendorPaymentRun->items as $item)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('account.bill-invoice.show', $item->bill_id) }}"
                           class="text-[#0078C8] font-semibold hover:underline">
                            {{ $item->bill->invoice_no ?: $item->bill->unique_no }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $item->vendor->name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ dateFormat($item->bill->date) }}</td>
                    <td class="px-4 py-3 text-right text-gray-800">{{ numberFormat($item->bill_total) }}</td>
                    <td class="px-4 py-3 text-right text-amber-600">{{ numberFormat($item->bill_remaining) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-green-600">{{ numberFormat($item->amount_to_pay) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-200">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-700">Total Payment</td>
                    <td class="px-4 py-3 text-right font-bold text-green-600 text-base">{{ numberFormat($vendorPaymentRun->total_amount) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
