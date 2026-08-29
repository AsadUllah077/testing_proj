@extends('layouts.auth')
@section('title') Debit Note {{ $debitNote->unique_no }} @endsection

@section('content')
@php
    $allocatedAmount = (float) ($debitNote->allocated_amount ?? 0);
    $refundedAmount = (float) ($debitNote->refunded_amount ?? 0);
    $availableAmount = (float) ($debitNote->remaining_amount ?? 0);
    $hasLaterSettlements = $debitNote->refunds->isNotEmpty()
        || $debitNote->allocations->count() > 1
        || ($debitNote->allocations->count() === 1
            && (int) $debitNote->allocations->first()->bill_id !== (int) $debitNote->bill_id);
@endphp
<div class="p-4 mx-auto">

    @if ($errors->any())
        <div class="mb-4 rounded-[4px] border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ get_index_url('account.debit-note.index') }}"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&larr;</a>
            <div>
                <h1 class="text-lg font-bold text-gray-800">{{ $debitNote->unique_no }}</h1>
                <p class="text-xs text-gray-500 mt-0.5">Debit Note · {{ $debitNote->date->format('d M Y') }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            @if ($availableAmount > 0 && $openBills->isNotEmpty())
                <button type="button" onclick="toggleDebitNoteModal('allocationModal', true)"
                    class="bg-emerald-600 text-white text-sm font-semibold px-4 py-2 rounded-[3px]">Apply to Bill</button>
            @endif
            @if ($availableAmount > 0)
                <button type="button" onclick="toggleDebitNoteModal('refundModal', true)"
                    class="bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded-[3px]">Receive Refund</button>
            @endif
            <button onclick="openExportModal('debit-note', {{ $debitNote->id }})"
                class="bg-[#0078C8] text-white text-sm font-semibold px-4 py-2 rounded-[3px]">Export</button>
            @unless ($hasLaterSettlements)
                <a href="{{ route('account.debit-note.edit', $debitNote) }}"
                    class="bg-amber-500 text-white text-sm font-semibold px-4 py-2 rounded-[3px]">Edit</a>
                <form method="POST" action="{{ route('account.debit-note.destroy', $debitNote) }}"
                    onsubmit="return confirm('Delete this debit note and its journal entries?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="bg-red-500 text-white text-sm font-semibold px-4 py-2 rounded-[3px]">Delete</button>
                </form>
            @endunless
        </div>
    </div>

    <div
        class="mb-5 rounded-[5px] border {{ $availableAmount <= 0 ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }} p-4">
        <h2 class="text-sm font-semibold {{ $availableAmount <= 0 ? 'text-green-900' : 'text-amber-900' }}">
            Debit note allocation summary
        </h2>
        <p class="mt-1 text-sm {{ $availableAmount <= 0 ? 'text-green-800' : 'text-amber-800' }}">
            @if ($allocatedAmount > 0)
                <strong>{{ number_format($allocatedAmount, 2) }}</strong> has been applied to supplier bills.
            @elseif ($refundedAmount > 0)
                The supplier has refunded <strong>{{ number_format($refundedAmount, 2) }}</strong>.
            @else
                This debit note is available to apply to a supplier bill or receive as a refund.
            @endif

            @if ($availableAmount <= 0)
                No supplier credit remains available.
            @else
                Supplier credit still available:
                <strong>{{ number_format($availableAmount, 2) }}</strong>.
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Details --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Vendor & Bill info --}}
            <div class="bg-white border border-gray-200 rounded-[5px] p-5">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Vendor</p>
                        <p class="font-semibold text-gray-800">{{ $debitNote->vendor?->name ?? '—' }}</p>
                        @if ($debitNote->vendor?->email)
                            <p class="text-xs text-gray-500">{{ $debitNote->vendor->email }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Linked Bill</p>
                        <p class="text-gray-700">{{ $debitNote->bill_unique_no ?? '—' }}</p>
                        @if ($debitNote->ref_no)
                            <p class="text-xs text-gray-500">Ref: {{ $debitNote->ref_no }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Account</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Price</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Disc%</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tax</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($debitNote->items as $item)
                        <tr>
                            <td class="px-4 py-3 text-gray-800">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->account?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->price, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ number_format($item->discount_percentage, 2) }}%</td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $item->taxRate ? $item->taxRate->name . ' (' . $item->taxRate->tax_rate . '%)' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Totals --}}
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <div class="w-56 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Sub-Total</span>
                            <span>{{ number_format($debitNote->sub_total, 2) }}</span>
                        </div>
                        @if ($debitNote->tax > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Tax</span>
                            <span>{{ number_format($debitNote->tax, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-bold text-gray-800 border-t border-gray-200 pt-1">
                            <span>Total</span>
                            <span>{{ number_format($debitNote->total, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-red-700 border-t border-gray-200 pt-1">
                            <span>Allocated to Bill</span>
                            <span>{{ number_format($allocatedAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-indigo-700">
                            <span>Supplier Refunds</span>
                            <span>{{ number_format($refundedAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-blue-700">
                            <span>Available Credit</span>
                            <span>{{ number_format($availableAmount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($debitNote->allocations->isNotEmpty() || $debitNote->refunds->isNotEmpty())
                <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase">Settlement History</h3>
                    </div>
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-4 py-2 text-left font-semibold text-gray-500">Date</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500">Type</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500">Reference</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500">Account / Bill</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-500">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($debitNote->allocations as $allocation)
                                <tr>
                                    <td class="px-4 py-2">{{ $allocation->allocation_date->format('d M Y') }}</td>
                                    <td class="px-4 py-2"><span class="text-emerald-700 font-semibold">Bill allocation</span></td>
                                    <td class="px-4 py-2">{{ $allocation->bill?->invoice_no ?? $allocation->bill?->unique_no ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $debitNote->vendor?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($allocation->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            @foreach ($debitNote->refunds as $refund)
                                <tr>
                                    <td class="px-4 py-2">{{ $refund->refund_date->format('d M Y') }}</td>
                                    <td class="px-4 py-2"><span class="text-indigo-700 font-semibold">Supplier refund</span></td>
                                    <td class="px-4 py-2">{{ $refund->refund_no }}</td>
                                    <td class="px-4 py-2">{{ $refund->account?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($refund->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Journal Entries --}}
            @if ($debitNote->transactions->isNotEmpty())
            <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase">Journal Entries</h3>
                </div>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-4 py-2 text-left font-semibold text-gray-500">Account</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-500">Debit</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-500">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($debitNote->transactions as $txn)
                        <tr>
                            <td class="px-4 py-2 text-gray-700">{{ $txn->account?->name ?? 'Account #' . $txn->account_id }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">
                                {{ $txn->debit > 0 ? number_format($txn->debit, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700">
                                {{ $txn->credit > 0 ? number_format($txn->credit, 2) : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            <div class="bg-white border border-gray-200 rounded-[5px] p-4 space-y-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500 font-semibold uppercase">Status</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-semibold
                        bg-emerald-100 text-emerald-700">
                        Confirmed
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-semibold uppercase">Date</p>
                    <p class="text-gray-700 mt-0.5">{{ $debitNote->date->format('d M Y') }}</p>
                </div>
                @if ($debitNote->accountingPeriod)
                <div>
                    <p class="text-xs text-gray-500 font-semibold uppercase">Accounting Period</p>
                    <p class="text-gray-700 mt-0.5">{{ $debitNote->accountingPeriod->name }}</p>
                </div>
                @endif
                @if ($debitNote->note)
                <div>
                    <p class="text-xs text-gray-500 font-semibold uppercase">Note</p>
                    <p class="text-gray-700 mt-0.5 text-xs">{{ $debitNote->note }}</p>
                </div>
                @endif
                <div>
                    <p class="text-xs text-gray-500 font-semibold uppercase">Created By</p>
                    <p class="text-gray-700 mt-0.5 text-xs">{{ $debitNote->createdBy?->name ?? '—' }}</p>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-100 rounded-[4px] p-3">
                <p class="text-xs font-semibold text-amber-700 mb-1">AP Impact</p>
                <p class="text-xs text-amber-600">
                    This debit note reduced Accounts Payable to
                    <strong>{{ $debitNote->vendor?->name }}</strong> by
                    <strong>{{ number_format($debitNote->total, 2) }}</strong>.
                    @if ($allocatedAmount > 0)
                        <br>
                        <strong>{{ number_format($allocatedAmount, 2) }}</strong> has been applied to bill
                        balances.
                    @endif
                    @if ($refundedAmount > 0)
                        <br>
                        <strong>{{ number_format($refundedAmount, 2) }}</strong> has been received from the supplier.
                    @endif
                </p>
            </div>
        </div>

    </div>
</div>

@if ($availableAmount > 0 && $openBills->isNotEmpty())
    <div id="allocationModal" class="hidden fixed inset-0 z-50 bg-gray-900/50 p-4">
        <div class="mx-auto mt-20 max-w-md rounded-lg bg-white p-5 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Apply Supplier Credit to Bill</h3>
                <button type="button" onclick="toggleDebitNoteModal('allocationModal', false)" class="text-gray-500">&times;</button>
            </div>
            <form method="POST" action="{{ route('account.debit-note.allocate', $debitNote) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Open bill</label>
                    <select name="bill_id" id="allocation_bill_id" required onchange="limitAllocationAmount()"
                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Select bill</option>
                        @foreach ($openBills as $bill)
                            <option value="{{ $bill->id }}" data-remaining="{{ (float) $bill->remaining_amount }}">
                                {{ $bill->invoice_no ?? $bill->unique_no }} — due {{ number_format($bill->remaining_amount, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Allocation date</label>
                    <input type="date" name="allocation_date" value="{{ date('Y-m-d') }}" required
                        class="date w-full rounded border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Amount</label>
                    <input type="number" id="allocation_amount" name="amount" step="0.01" min="0.01"
                        max="{{ $availableAmount }}" value="{{ $availableAmount }}" required
                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-gray-500">Available supplier credit: {{ number_format($availableAmount, 2) }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="toggleDebitNoteModal('allocationModal', false)" class="rounded border px-4 py-2 text-sm">Cancel</button>
                    <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Apply Credit</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($availableAmount > 0)
    <div id="refundModal" class="hidden fixed inset-0 z-50 bg-gray-900/50 p-4">
        <div class="mx-auto mt-20 max-w-md rounded-lg bg-white p-5 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Receive Supplier Refund</h3>
                <button type="button" onclick="toggleDebitNoteModal('refundModal', false)" class="text-gray-500">&times;</button>
            </div>
            <form method="POST" action="{{ route('account.debit-note.refund', $debitNote) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Refund date</label>
                    <input type="date" name="refund_date" value="{{ date('Y-m-d') }}" required
                        class="date w-full rounded border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Amount received</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $availableAmount }}"
                        value="{{ $availableAmount }}" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Bank / cash account</label>
                    <x-chart-account-bank-select name="account_id" id="refund_account_id" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="2" class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                        placeholder="Optional supplier refund reference"></textarea>
                </div>
                <div class="rounded bg-indigo-50 p-3 text-xs text-indigo-700">Journal: DR Bank/Cash, CR Accounts Payable.</div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="toggleDebitNoteModal('refundModal', false)" class="rounded border px-4 py-2 text-sm">Cancel</button>
                    <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Receive Refund</button>
                </div>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
function toggleDebitNoteModal(id, show) {
    document.getElementById(id)?.classList.toggle('hidden', !show);
}

function limitAllocationAmount() {
    const select = document.getElementById('allocation_bill_id');
    const input = document.getElementById('allocation_amount');
    if (!select || !input || !select.selectedOptions.length) return;
    const billBalance = Number(select.selectedOptions[0].dataset.remaining || 0);
    const available = {{ json_encode($availableAmount) }};
    const maximum = Math.min(billBalance, available);
    input.max = maximum.toFixed(2);
    input.value = maximum.toFixed(2);
}
</script>
@endpush
    <x-export-column-modal />
@endsection
