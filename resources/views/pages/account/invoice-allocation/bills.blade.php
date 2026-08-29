@extends('layouts.auth')
@section('title', 'Vendor Invoice Allocation')

@section('content')
    <x-acccount.loader />
    <div class="p-4">

        {{-- Header --}}
        <div class="flex justify-between items-center mb-4 bg-white rounded-lg p-5">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Vendor Invoice Allocation</h1>
                <p class="text-sm text-gray-500 mt-0.5">Select approved bills across any vendor and record payments in one
                    step.</p>
                <a href="{{ route('account.vendor-payment-run.index') }}" class="text-sm text-[#0078C8] hover:underline">
                    ← View Payment History
                </a>
            </div>
            <a href="{{ route('account.invoice-allocation.sales') }}" class="text-sm text-[#0078C8] hover:underline">
                Switch to Customers invoices →
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
            <form method="GET" class="flex flex-wrap items-end gap-4" id="filterForm">
                <input type="hidden" name="selected_bill_ids" id="selected-bill-ids-input">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Select Vendor</label>
                    <select name="vendor_id"
                        class="ajax-vendor-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </select>
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="date w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="date w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Invoice / Bill No</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 text-sm border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white">Filter</button>
                    <a href="{{ route('account.invoice-allocation.bills') }}" id="invoice-allocation-bills-reset-link"
                        class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded hover:bg-gray-50">Reset</a>
                </div>
            </form>
        </div>

        <form method="POST" action="{{ route('account.invoice-allocation.bills.store') }}" id="allocationForm">
            @csrf

            <div id="formError" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded"></div>

            {{-- Payment Details --}}
            <div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
                <h2 class="text-sm font-bold text-gray-700 mb-3">Payment Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Date <span
                                class="text-red-500">*</span></label>
                        <input class="date" type="date" name="payment_date" value="{{ now()->toDateString() }}" required
                            class="date w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                        @error('payment_date')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Account <span
                                class="text-red-500">*</span></label>
                        <select name="account_id" id="account_id" required
                            class="select2 w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                            <option value="">-- Select Account --</option>
                            @foreach ($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('account_id')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Note</label>
                        <input type="text" name="note" placeholder="Optional note for this allocation"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                    </div>
                </div>
            </div>

            {{-- Bill Table --}}
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
                <div class="p-3 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" id="selectAllHeader" onclick="toggleSelectAll(this)">
                            <span>Select All</span>
                        </label>
                        <button type="button" onclick="deselectAll()"
                            class="text-xs text-gray-500 hover:underline">Clear</button>
                        <button type="button" onclick="autoFill()" class="text-xs text-[#0078C8] hover:underline">Auto-fill
                            remaining amounts</button>
                    </div>
                    <span class="text-xs text-gray-400">{{ $bills->total() }} open bill(s)</span>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                            <th class="px-4 py-3 w-10"></th>
                            <th class="px-4 py-3 text-left">Bill #</th>
                            <th class="px-4 py-3 text-left">Vendor</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Remaining</th>
                            <th class="px-4 py-3 text-right">Amount to Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bills as $bill)
                            <tr class="border-b border-gray-100 hover:bg-gray-50" data-vendor="{{ $bill->vendor_id }}"
                                data-remaining="{{ $bill->remaining_amount }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="bill_ids[]" value="{{ $bill->id }}"
                                        class="inv-checkbox" onchange="updateTotal()">
                                </td>
                                <td class="px-4 py-3 text-[#0078C8] font-semibold">
                                    {{ $bill->invoice_no ?: $bill->unique_no }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $bill->vendor->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ dateFormat($bill->date) }}</td>
                                <td class="px-4 py-3 text-right text-gray-800">{{ numberFormat($bill->total) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-amber-600">
                                    {{ numberFormat($bill->remaining_amount) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <input type="number" name="amounts[{{ $bill->id }}]"
                                        value="{{ $bill->remaining_amount }}" min="0.01"
                                        max="{{ $bill->remaining_amount }}" step="0.01"
                                        class="w-32 border border-gray-300 rounded px-2 py-1 text-sm text-right focus:outline-none focus:border-[#0078C8]"
                                        onchange="updateTotal()">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                                    No approved bills with outstanding balances found.
                                    <br>
                                    <span class="text-xs text-amber-600 mt-1 block">
                                        Bills must be approved via
                                        <a href="{{ route('account.approvals.bills.index') }}"
                                            class="underline text-[#0078C8]">Invoice Approvals</a>
                                        before they appear here.
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($bills->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $bills->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>

            {{-- Footer: total + submit --}}
            <div class="flex justify-between items-center bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-center gap-6">
                    <div>
                        <span class="text-xs text-gray-500">Selected Bills:</span>
                        <span id="selectedCount" class="ml-1 font-bold text-gray-800">0</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500">Total to Pay:</span>
                        <span id="selectedTotal" class="ml-1 text-lg font-bold text-[#0078C8]">0.00</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('account.invoice-allocation.bills') }}"
                        class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-600 hover:bg-gray-50">Cancel</a>
                    <button type="submit"
                        class="bg-[#0078C8] text-white text-sm font-semibold px-6 py-2 rounded-[3px] hover:bg-[#0486dd]">
                        Record Payments
                    </button>
                </div>
            </div>

        </form>
    </div>

    <script>
        function updateTotal() {
            persistSelection();
            let total = 0;
            let count = 0;
            document.querySelectorAll('.inv-checkbox:checked').forEach(cb => {
                const row = cb.closest('tr');
                const amt = parseFloat(row.querySelector('input[type="number"]').value) || 0;
                total += amt;
                count++;
            });
            document.getElementById('selectedTotal').textContent = total.toLocaleString('en', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('selectedCount').textContent = count;
        }

        function toggleSelectAll(cb) {
            document.querySelectorAll('.inv-checkbox').forEach(c => c.checked = cb.checked);
            updateTotal();
        }

        function deselectAll() {
            document.getElementById('selectAllHeader').checked = false;
            document.querySelectorAll('.inv-checkbox').forEach(c => c.checked = false);
            updateTotal();
        }

        const billAllocationSelectionStorageKey = 'account.invoice-allocation.bills.selected-bills';
        const selectedBillAllocations = new Map(JSON.parse(localStorage.getItem(billAllocationSelectionStorageKey) || '[]'));

        function syncSelectedBillIdsInput() {
            const input = document.getElementById('selected-bill-ids-input');
            if (input) input.value = Array.from(selectedBillAllocations.keys()).join(',');
        }

        function persistSelection() {
            document.querySelectorAll('.inv-checkbox').forEach(checkbox => {
                if (checkbox.checked) {
                    selectedBillAllocations.set(checkbox.value, checkbox.value);
                } else {
                    selectedBillAllocations.delete(checkbox.value);
                }
            });
            localStorage.setItem(billAllocationSelectionStorageKey, JSON.stringify(Array.from(selectedBillAllocations.entries())));
            syncSelectedBillIdsInput();
        }

        function restoreSelection() {
            document.querySelectorAll('.inv-checkbox').forEach(checkbox => {
                checkbox.checked = selectedBillAllocations.has(checkbox.value);
            });

            const checkboxes = Array.from(document.querySelectorAll('.inv-checkbox'));
            const selectAll = document.getElementById('selectAllHeader');
            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && checkboxes.every(checkbox => checkbox.checked);
                selectAll.indeterminate = checkboxes.some(checkbox => checkbox.checked) && !selectAll.checked;
            }
            updateTotal();
        }

        document.getElementById('filterForm')?.addEventListener('submit', syncSelectedBillIdsInput);
        document.getElementById('invoice-allocation-bills-reset-link')?.addEventListener('click', function(event) {
            syncSelectedBillIdsInput();
            if (selectedBillAllocations.size === 0) return;

            event.preventDefault();
            const url = new URL(this.href);
            url.searchParams.set('selected_bill_ids', Array.from(selectedBillAllocations.keys()).join(','));
            window.location.href = url.toString();
        });

        function autoFill() {
            document.querySelectorAll('.inv-checkbox:checked').forEach(cb => {
                const row = cb.closest('tr');
                const remaining = parseFloat(row.dataset.remaining) || 0;
                row.querySelector('input[type="number"]').value = remaining.toFixed(2);
            });
            updateTotal();
        }

        function showFormError(msg) {
            const el = document.getElementById('formError');
            el.textContent = msg;
            el.classList.remove('hidden');
            el.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        document.getElementById('allocationForm').addEventListener('submit', function(e) {
            document.getElementById('formError').classList.add('hidden');

            if (!document.getElementById('account_id').value) {
                e.preventDefault();
                showFormError('Please select a payment account.');
                return;
            }

            const checked = document.querySelectorAll('.inv-checkbox:checked').length;
            if (checked === 0) {
                e.preventDefault();
                showFormError('Please select at least one bill to pay.');
            }
        });

        restoreSelection();
    </script>
@endsection

@push('scripts')
    <script>
        // Initialize AJAX Vendor Select
        $('.ajax-vendor-select').select2({
            placeholder: '-- select vendor --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.bill.vendors.paginate') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data.map(vendor => ({
                            id: vendor.id,
                            text: vendor.name
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });
    </script>
@endpush
