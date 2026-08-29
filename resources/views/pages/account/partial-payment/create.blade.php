@extends('layouts.auth')
@section('title', 'Partial Payment Allocation')

@section('content')
<div class="p-4">
    <div class="mb-4 p-5 bg-white rounded-lg">
        <h1 class="text-lg font-bold text-gray-800">Partial Payment Allocation</h1>
        <p class="text-sm text-gray-500">Apply a single receipt against multiple outstanding invoices for one customer.</p>
    </div>

    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-700 text-xs rounded flex items-center gap-2">
        <i class="fa-solid fa-circle-info text-sm"></i>
        <span>Only <strong>Approved</strong> invoices are available for payment allocation. Invoices in draft or pending status will not appear here.</span>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">{{ session('error') }}</div>
    @endif

    {{-- Step 1: Select Customer --}}
    <div class="bg-white rounded-[5px] border border-gray-200 p-5 mb-4">
        <h2 class="text-sm font-bold text-gray-700 mb-3">Step 1 — Select Customer</h2>
        <form method="GET" action="{{ route('account.partial-payment.create') }}" class="flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Customer</label>
                <select name="customer_id" required onchange="this.form.submit()"
                    class="select2 w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                    <option value="">— Select a customer —</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"
                            {{ (isset($selectedCustomer) && $selectedCustomer->id == $customer->id) ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($selectedCustomer)
    {{-- Step 2: Payment Details + Invoice Selection --}}
    <form method="POST" action="{{ route('account.partial-payment.store') }}" id="allocationForm">
        @csrf
        <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">

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
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Deposit Account <span class="text-red-500">*</span></label>
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
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description / Reference</label>
                    <input type="text" name="description" value="{{ old('description') }}"
                        placeholder="e.g. Cheque #12345 — {{ $selectedCustomer->name }}"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>
            </div>
        </div>

        {{-- Step 3: Invoice allocation --}}
        <div class="bg-white rounded-[5px] border border-gray-200 overflow-hidden mb-4">
            <div class="flex justify-between items-center p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-700">Step 3 — Allocate Against Open Invoices</h2>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="selectAllInvoices()"
                        class="text-xs text-[#0078C8] hover:underline">Select All</button>
                    <button type="button" onclick="clearAllInvoices()"
                        class="text-xs text-gray-500 hover:underline">Clear All</button>
                    <button type="button" onclick="autoAllocate()"
                        class="text-xs bg-gray-100 border border-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-200">
                        Auto-Fill (Oldest First)
                    </button>
                </div>
            </div>

            @if ($openInvoices->isEmpty())
            <div class="p-8 text-center text-gray-400">
                {{ $selectedCustomer->name }} has no outstanding invoices.
            </div>
            @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 w-10"></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Invoice Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Outstanding</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Allocate Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($openInvoices as $invoice)
                    @php
                        $dueDate  = \Carbon\Carbon::parse($invoice->date)->addDays(30);
                        $isOverdue = $dueDate->isPast();
                    @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50 {{ $isOverdue ? 'bg-red-50/30' : '' }}"
                        data-remaining="{{ $invoice->remaining_amount }}"
                        data-sale-id="{{ $invoice->id }}">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="sale_ids[]" value="{{ $invoice->id }}"
                                class="invoice-checkbox"
                                onchange="toggleAllocation(this, {{ $invoice->id }})">
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[#0078C8] font-semibold">{{ $invoice->invoice_no ?: $invoice->unique_no }}</span>
                            @if ($isOverdue)
                                <span class="ml-1 text-xs text-red-500 font-semibold">Overdue</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ dateFormat($invoice->date) }}</td>
                        <td class="px-4 py-3 {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                            {{ $dueDate->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ numberFormat($invoice->total) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-600">{{ numberFormat($invoice->remaining_amount) }}</td>
                        <td class="px-4 py-3 text-right">
                            <input type="number"
                                name="amounts[{{ $invoice->id }}]"
                                id="amount_{{ $invoice->id }}"
                                min="0.01"
                                max="{{ $invoice->remaining_amount }}"
                                step="0.01"
                                value="{{ old('amounts.' . $invoice->id, '') }}"
                                placeholder="0.00"
                                disabled
                                class="alloc-input w-28 border border-gray-300 rounded px-2 py-1 text-sm text-right focus:outline-none focus:border-[#0078C8] disabled:bg-gray-100 disabled:text-gray-400"
                                oninput="updateTotal()">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Total & Submit --}}
        <div class="flex justify-between items-center">
            <div class="bg-[#E6F3FB] border border-[#0078C8]/30 rounded px-5 py-3">
                <span class="text-sm text-gray-600">Total Being Allocated:</span>
                <span id="totalAllocated" class="text-xl font-bold text-[#0078C8] ml-2">0.00</span>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('account.partial-payment.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-600 hover:bg-gray-50">Reset</a>
                <button type="submit" id="submitBtn" disabled
                    class="bg-[#0078C8] text-white text-sm font-semibold px-6 py-2 rounded-[3px] hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Record Allocation
                </button>
            </div>
        </div>
    </form>
    @endif

</div>

<script>
const remainingMap = {};
@if(isset($openInvoices) && count($openInvoices) > 0)
@foreach ($openInvoices as $invoice)
remainingMap[{{ $invoice->id }}] = {{ $invoice->remaining_amount }};
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

    const selected = document.querySelectorAll('.invoice-checkbox:checked').length;
    document.getElementById('submitBtn').disabled = selected === 0 || total <= 0;
}

function selectAllInvoices() {
    document.querySelectorAll('.invoice-checkbox').forEach(cb => {
        cb.checked = true;
        const id = parseInt(cb.value);
        const inp = document.getElementById('amount_' + id);
        inp.disabled = false;
        inp.value = remainingMap[id].toFixed(2);
    });
    updateTotal();
}

function clearAllInvoices() {
    document.querySelectorAll('.invoice-checkbox').forEach(cb => {
        cb.checked = false;
        const id = parseInt(cb.value);
        const inp = document.getElementById('amount_' + id);
        inp.disabled = true;
        inp.value = '';
    });
    updateTotal();
}

function autoAllocate() {
    // Prompt for total amount, distribute oldest-first
    const totalStr = prompt('Enter total payment amount to allocate (oldest invoices filled first):');
    if (!totalStr) return;
    let remaining = parseFloat(totalStr);
    if (isNaN(remaining) || remaining <= 0) { alert('Invalid amount.'); return; }

    clearAllInvoices();

    // Rows are already sorted oldest-first from the server
    document.querySelectorAll('tr[data-remaining]').forEach(row => {
        if (remaining <= 0) return;
        const id   = parseInt(row.dataset.saleId);
        const maxAmt = parseFloat(row.dataset.remaining);
        const alloc  = Math.min(remaining, maxAmt);
        remaining -= alloc;

        const cb = row.querySelector('.invoice-checkbox');
        const inp = document.getElementById('amount_' + id);
        cb.checked   = true;
        inp.disabled = false;
        inp.value    = alloc.toFixed(2);
    });

    updateTotal();
}
</script>
@endsection
