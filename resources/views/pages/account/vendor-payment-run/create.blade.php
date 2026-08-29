@extends('layouts.auth')
@section('title', 'New Vendor Payment Run')

@section('content')
<div class="p-4">
    <div class="mb-4 p-5 bg-white rounded-lg">
        <h1 class="text-lg font-bold text-gray-800">New Vendor Payment Run</h1>
        <p class="text-sm text-gray-500">Create a batch payment run for a selected vendor's outstanding bills.</p>
    </div>

    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-700 text-xs rounded flex items-center gap-2">
        <i class="fa-solid fa-circle-info text-sm"></i>
        <span>Only <strong>Approved</strong> bills are available for payment runs. Bills in draft or pending status will not appear here.</span>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Step 1: Select Vendor --}}
    <div class="bg-white rounded-[5px] border border-gray-200 p-5 mb-4">
        <h2 class="text-sm font-bold text-gray-700 mb-3">Step 1 — Select Vendor</h2>
        <form method="GET" action="{{ route('account.vendor-payment-run.create') }}" class="flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Vendor</label>
                <select name="vendor_id" required onchange="this.form.submit()"
                    class="select2 w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                    <option value="">— Select a vendor —</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}"
                            {{ (isset($selectedVendor) && $selectedVendor->id == $vendor->id) ? 'selected' : '' }}>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($selectedVendor)
    {{-- Step 2: Payment Details + Bill Selection --}}
    <form method="POST" action="{{ route('account.vendor-payment-run.store') }}" id="paymentRunForm">
        @csrf
        <input type="hidden" name="vendor_id" value="{{ $selectedVendor->id }}">

        {{-- Payment header --}}
        <div class="bg-white rounded-[5px] border border-gray-200 p-5 mb-4">
            <h2 class="text-sm font-bold text-gray-700 mb-3">Step 2 — Payment Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Date <span class="text-red-500">*</span></label>
                    <input class="date" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required
                        class="date w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Account <span class="text-red-500">*</span></label>
                    <select name="account_id" required
                        class="select2 w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                        <option value="">— Select bank/cash account —</option>
                        @foreach ($bankAccounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                        placeholder="e.g. Bulk payment for {{ $selectedVendor->name }}"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>
            </div>
        </div>

        {{-- Step 3: Bill allocation --}}
        <div class="bg-white rounded-[5px] border border-gray-200 overflow-hidden mb-4 shadow-sm">
            <div class="flex justify-between items-center p-4 border-b border-gray-100 bg-gray-50/50">
                <h2 class="text-sm font-bold text-gray-700">Step 3 — Select Bills to Pay</h2>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="selectAllBills()"
                        class="text-xs text-[#0078C8] font-semibold hover:underline">Select All</button>
                    <button type="button" onclick="clearAllBills()"
                        class="text-xs text-gray-400 font-semibold hover:underline">Clear All</button>
                    <button type="button" onclick="autoAllocate()"
                        class="text-xs bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded hover:bg-gray-50 transition-colors">
                        Auto-Fill (Oldest First)
                    </button>
                </div>
            </div>

            @if ($openBills->isEmpty())
            <div class="p-8 text-center text-gray-400 italic">
                {{ $selectedVendor->name }} has no outstanding approved bills.
            </div>
            @else
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 w-10"></th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Bill #</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Outstanding</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase w-32">Pay Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($openBills as $bill)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                        data-remaining="{{ $bill->remaining_amount }}"
                        data-bill-id="{{ $bill->id }}">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="bills[]" value="{{ $bill->id }}"
                                class="bill-checkbox w-4 h-4 text-[#0078C8]"
                                onchange="toggleAllocation(this, {{ $bill->id }})">
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[#0078C8] font-bold">{{ $bill->invoice_no ?: $bill->unique_no }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ dateFormat($bill->date) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ numberFormat($bill->total) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-amber-600">{{ numberFormat($bill->remaining_amount) }}</td>
                        <td class="px-4 py-3 text-right">
                            <input type="number"
                                name="amounts[{{ $bill->id }}]"
                                id="amount_{{ $bill->id }}"
                                min="0.01"
                                max="{{ $bill->remaining_amount }}"
                                step="0.01"
                                value=""
                                placeholder="0.00"
                                disabled
                                class="alloc-input w-full border border-gray-300 rounded px-2 py-1.5 text-sm text-right focus:outline-none focus:border-[#0078C8] disabled:bg-gray-100 disabled:text-gray-400"
                                oninput="updateTotal()">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Total & Submit --}}
        <div class="flex justify-between items-center bg-white p-5 rounded-[5px] border border-gray-200 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-500 uppercase">Total Run Amount:</span>
                <span id="totalAllocated" class="text-2xl font-black text-[#0078C8]">0.00</span>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('account.vendor-payment-run.index') }}"
                   class="px-5 py-2 border border-gray-300 rounded text-sm text-gray-600 hover:bg-gray-50 transition-colors">Cancel</a>
                <button type="submit" id="submitBtn" disabled
                    class="bg-[#0078C8] text-white text-sm font-bold px-8 py-2 rounded-[3px] hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-md transition-all">
                    Create Payment Run
                </button>
            </div>
        </div>
    </form>
    @endif

</div>

<script>
const remainingMap = {};
@if(isset($openBills) && count($openBills) > 0)
@foreach ($openBills as $bill)
remainingMap[{{ $bill->id }}] = {{ $bill->remaining_amount }};
@endforeach
@endif

function toggleAllocation(checkbox, id) {
    const input = document.getElementById('amount_' + id);
    if (checkbox.checked) {
        input.disabled = false;
        input.value = remainingMap[id].toFixed(2);
    } else {
        input.disabled = true;
        input.value = '';
    }
    updateTotal();
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.alloc-input:not(:disabled)').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    document.getElementById('totalAllocated').textContent = total.toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const selected = document.querySelectorAll('.bill-checkbox:checked').length;
    document.getElementById('submitBtn').disabled = selected === 0 || total <= 0;
}

function selectAllBills() {
    document.querySelectorAll('.bill-checkbox').forEach(cb => {
        cb.checked = true;
        const id = parseInt(cb.value);
        const inp = document.getElementById('amount_' + id);
        inp.disabled = false;
        inp.value = remainingMap[id].toFixed(2);
    });
    updateTotal();
}

function clearAllBills() {
    document.querySelectorAll('.bill-checkbox').forEach(cb => {
        cb.checked = false;
        const id = parseInt(cb.value);
        const inp = document.getElementById('amount_' + id);
        inp.disabled = true;
        inp.value = '';
    });
    updateTotal();
}

function autoAllocate() {
    const totalStr = prompt('Enter total amount for this payment run (distributed oldest-first):');
    if (!totalStr) return;
    let remaining = parseFloat(totalStr);
    if (isNaN(remaining) || remaining <= 0) { alert('Invalid amount.'); return; }

    clearAllBills();

    document.querySelectorAll('tr[data-remaining]').forEach(row => {
        if (remaining <= 0) return;
        const id   = parseInt(row.dataset.billId);
        const maxAmt = parseFloat(row.dataset.remaining);
        const alloc  = Math.min(remaining, maxAmt);
        remaining -= alloc;

        const cb = row.querySelector('.bill-checkbox');
        const inp = document.getElementById('amount_' + id);
        cb.checked   = true;
        inp.disabled = false;
        inp.value    = alloc.toFixed(2);
    });

    updateTotal();
}
</script>
@endsection
