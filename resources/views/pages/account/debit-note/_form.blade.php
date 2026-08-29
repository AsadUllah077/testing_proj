@php $dn = $dn ?? new \App\Models\DebitNote(); @endphp
<div class="p-4 mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5 bg-white p-5">
        <a href="{{ get_index_url('account.debit-note.index') }}"
            class="text-gray-400 hover:text-gray-600 text-xl leading-none">&larr;</a>
        <div>
            <h1 class="text-lg font-bold text-gray-800">
                {{ $editing ? 'Edit Debit Note: ' . $dn->unique_no : 'New Debit Note' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">Purchase return — reduces Accounts Payable</p>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-[4px] p-3 mb-4 text-sm text-red-700">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
        action="{{ $editing ? route('account.debit-note.update', $dn) : route('account.debit-note.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- ── Left: Main fields (2 cols) ─────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Vendor & Bill ──────────────────────────────────────────── --}}
                <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Supplier</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Vendor --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Vendor <span class="text-red-500">*</span></label>
                            <select name="vendor_id" id="vendorSelect" required
                                class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                                <option value="">— Select vendor —</option>
                                @php $selVendorId = old('vendor_id', data_get($dn, 'vendor_id')); @endphp
                                @if($selVendorId)
                                    @php $selVendor = $vendors->firstWhere('id', $selVendorId); @endphp
                                    @if($selVendor)
                                        <option value="{{ $selVendor->id }}" selected>{{ $selVendor->name }}</option>
                                    @endif
                                @endif
                            </select>
                            @error('vendor_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Linked Bill (optional) --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Linked Bill (optional)</label>
                            <select name="bill_id" id="billSelect"
                                class="select2 w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                                <option value="">— None —</option>
                                @foreach ($bills as $b)
                                    <option value="{{ $b->id }}"
                                        @selected(old('bill_id', $dn?->bill_id) == $b->id)>
                                        {{ $b->unique_no }} — {{ $b->date->format('d M Y') }}
                                        ({{ number_format($b->total, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('bill_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Line Items ─────────────────────────────────────────────── --}}
                <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Line Items</h2>
                        <button type="button" onclick="addItem()"
                            class="text-[#0078C8] text-xs font-semibold hover:underline">+ Add Row</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="itemsTable">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="pb-2 text-left text-xs font-semibold text-gray-500">Description</th>
                                    <th class="pb-2 text-left text-xs font-semibold text-gray-500 w-36">Account</th>
                                    <th class="pb-2 text-right text-xs font-semibold text-gray-500 w-20">Qty</th>
                                    <th class="pb-2 text-right text-xs font-semibold text-gray-500 w-24">Unit Price</th>
                                    <th class="pb-2 text-right text-xs font-semibold text-gray-500 w-20">Disc %</th>
                                    <th class="pb-2 text-left text-xs font-semibold text-gray-500 w-28">Tax</th>
                                    <th class="pb-2 text-right text-xs font-semibold text-gray-500 w-24">Total</th>
                                    <th class="pb-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">

                                @php
                                    $existingItems = $editing
                                        ? $dn->items
                                        : collect(array_map(fn($i) => (object) $i, old('items', [])));
                                @endphp

                                @forelse ($existingItems as $idx => $item)
                                <tr class="item-row border-b border-gray-100">
                                    <td class="py-2 pr-2">
                                        <input type="text" name="items[{{ $idx }}][description]"
                                            value="{{ old("items.{$idx}.description", $item->description ?? '') }}"
                                            placeholder="Description" required
                                            class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8]">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <select name="items[{{ $idx }}][account_id]" required
                                            class="select2 w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8]">
                                            <option value="">Select…</option>
                                            @foreach ($accounts as $acc)
                                                <option value="{{ $acc->id }}"
                                                    @selected(old("items.{$idx}.account_id", $item->account_id ?? '') == $acc->id)>
                                                    {{ $acc->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" name="items[{{ $idx }}][quantity]"
                                            value="{{ old("items.{$idx}.quantity", $item->quantity ?? 1) }}"
                                            step="0.01" min="0.01" required
                                            class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-qty">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" name="items[{{ $idx }}][price]"
                                            value="{{ old("items.{$idx}.price", $item->price ?? 0) }}"
                                            step="0.01" min="0" required
                                            class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-price">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" name="items[{{ $idx }}][discount_percentage]"
                                            value="{{ old("items.{$idx}.discount_percentage", $item->discount_percentage ?? 0) }}"
                                            step="0.01" min="0" max="100"
                                            class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-disc">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <select name="items[{{ $idx }}][tax_id]"
                                            class="select2 w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8] item-tax">
                                            <option value="">No Tax</option>
                                            @foreach ($taxRates as $tax)
                                                <option value="{{ $tax->id }}"
                                                    data-rate="{{ $tax->tax_rate }}"
                                                    @selected(old("items.{$idx}.tax_id", $item->tax_id ?? '') == $tax->id)>
                                                    {{ $tax->name }} ({{ $tax->tax_rate }}%)
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2 text-right">
                                        <span class="item-total text-xs font-semibold text-gray-700">0.00</span>
                                    </td>
                                    <td class="py-2 text-center">
                                        <button type="button" onclick="removeItem(this)"
                                            class="text-red-400 hover:text-red-600 text-xs">&times;</button>
                                    </td>
                                </tr>
                                @empty
                                {{-- one blank row --}}
                                <tr class="item-row border-b border-gray-100">
                                    <td class="py-2 pr-2">
                                        <input type="text" name="items[0][description]"
                                            placeholder="Description" required
                                            class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8]">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <select name="items[0][account_id]" required
                                            class="select2 w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8]">
                                            <option value="">Select…</option>
                                            @foreach ($accounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" name="items[0][quantity]" value="1"
                                            step="0.01" min="0.01" required
                                            class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-qty">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" name="items[0][price]" value="0"
                                            step="0.01" min="0" required
                                            class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-price">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" name="items[0][discount_percentage]" value="0"
                                            step="0.01" min="0" max="100"
                                            class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-disc">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <select name="items[0][tax_id]"
                                            class="select2 w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8] item-tax">
                                            <option value="">No Tax</option>
                                            @foreach ($taxRates as $tax)
                                                <option value="{{ $tax->id }}" data-rate="{{ $tax->tax_rate }}">
                                                    {{ $tax->name }} ({{ $tax->tax_rate }}%)
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2 text-right">
                                        <span class="item-total text-xs font-semibold text-gray-700">0.00</span>
                                    </td>
                                    <td class="py-2 text-center">
                                        <button type="button" onclick="removeItem(this)"
                                            class="text-red-400 hover:text-red-600 text-xs">&times;</button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals --}}
                    <div class="mt-4 flex justify-end">
                        <div class="w-64 space-y-1 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Sub-Total</span>
                                <span id="sumSubTotal">0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Discount</span>
                                <span id="sumDiscount">0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>After Discount</span>
                                <span id="sumAfterDiscount">0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Tax</span>
                                <span id="sumTax">0.00</span>
                            </div>
                            <div class="flex justify-between font-semibold text-gray-800 border-t border-gray-200 pt-1">
                                <span>Total</span>
                                <span id="sumTotal">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /left --}}

            {{-- ── Right: Meta ────────────────────────────────────────────── --}}
            <div class="space-y-4">

                <div class="bg-white border border-gray-200 rounded-[5px] p-4 space-y-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Details</h2>

                    {{-- Date --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date <span class="text-red-500">*</span></label>
                        <input class="date" type="date" name="date"
                            value="{{ old('date', $dn?->date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                            required
                            class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Default Expense Account --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Default Expense Account <span class="text-red-500">*</span></label>
                        <select name="account_id" required
                            class="select2 w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                            <option value="">— Select —</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}"
                                    @selected(old('account_id', $dn?->account_id) == $acc->id)>
                                    {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Ref No --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Reference No</label>
                        <input type="text" name="ref_no"
                            value="{{ old('ref_no', $dn?->ref_no) }}"
                            placeholder="e.g. RET-2026-001"
                            class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                    </div>

                    {{-- Note --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Note</label>
                        <textarea name="note" rows="3"
                            placeholder="Optional internal note…"
                            class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8] resize-none">{{ old('note', $dn?->note) }}</textarea>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-[#0078C8] text-white text-sm font-semibold py-2.5 rounded-[3px] hover:bg-[#0068b0] transition">
                        {{ $editing ? 'Update Debit Note' : 'Create Debit Note' }}
                    </button>
                    <a href="{{ get_index_url('account.debit-note.index') }}"
                        class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm rounded-[3px] hover:bg-gray-200 transition">
                        Cancel
                    </a>
                </div>

                {{-- Info box --}}
                <div class="bg-blue-50 border border-blue-100 rounded-[4px] p-3">
                    <p class="text-xs font-semibold text-blue-700 mb-1">Journal Entry Preview</p>
                    <p class="text-xs text-blue-600">DR Accounts Payable (total)</p>
                    <p class="text-xs text-blue-600">CR Expense Account (per item)</p>
                    <p class="text-xs text-blue-600">CR Input Tax (if applicable)</p>
                </div>

            </div>{{-- /right --}}
        </div>
    </form>
</div>

@push('scripts')
<script>
let itemCount = {{ $editing ? $dn->items->count() : (count(old('items', [])) ?: 1) }};
const accounts = @json($accounts);
const taxes = @json($taxRates);

@include('pages.account.partials.account-tax-default-script')

const accountsHtml = `
    <option value="">Select…</option>
    @foreach ($accounts as $acc)
        <option value="{{ $acc->id }}">{{ addslashes($acc->name) }}</option>
    @endforeach
`;

const taxHtml = `
    <option value="">No Tax</option>
    @foreach ($taxRates as $tax)
        <option value="{{ $tax->id }}" data-rate="{{ $tax->tax_rate }}">{{ addslashes($tax->name) }} ({{ $tax->tax_rate }}%)</option>
    @endforeach
`;

function addItem() {
    const idx = itemCount++;
    const row = document.createElement('tr');
    row.className = 'item-row border-b border-gray-100';
    row.innerHTML = `
        <td class="py-2 pr-2">
            <input type="text" name="items[${idx}][description]" placeholder="Description" required
                class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8]">
        </td>
        <td class="py-2 pr-2">
            <select name="items[${idx}][account_id]" required
                class="select2 w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8]">
                ${accountsHtml}
            </select>
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="items[${idx}][quantity]" value="1" step="0.01" min="0.01" required
                class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-qty">
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="items[${idx}][price]" value="0" step="0.01" min="0" required
                class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-price">
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="items[${idx}][discount_percentage]" value="0" step="0.01" min="0" max="100"
                class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-disc">
        </td>
        <td class="py-2 pr-2">
            <select name="items[${idx}][tax_id]"
                class="select2 w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8] item-tax">
                ${taxHtml}
            </select>
        </td>
        <td class="py-2 pr-2 text-right">
            <span class="item-total text-xs font-semibold text-gray-700">0.00</span>
        </td>
        <td class="py-2 text-center">
            <button type="button" onclick="removeItem(this)" class="text-red-400 hover:text-red-600 text-xs">&times;</button>
        </td>
    `;
    document.getElementById('itemsBody').appendChild(row);
    bindRowEvents(row);
    setupAccountDefaultTax(row);
    // init select2 on the newly added row's selects
    $(row).find('select').select2({ width: '100%' });
    recalcTotals();
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalcTotals();
}

function bindRowEvents(row) {
    row.querySelectorAll('.item-qty, .item-price, .item-disc, .item-tax').forEach(el => {
        el.addEventListener('input', () => { recalcRow(row); recalcTotals(); });
        el.addEventListener('change', () => { recalcRow(row); recalcTotals(); });
    });
}

function recalcRow(row) {
    const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
    const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
    const disc  = parseFloat(row.querySelector('.item-disc')?.value)  || 0;
    const taxSel = row.querySelector('.item-tax');
    const rate  = taxSel ? (parseFloat(taxSel.selectedOptions[0]?.dataset.rate) || 0) : 0;

    const sub   = qty * price;
    const after = sub * (1 - disc / 100);
    const tax   = after * rate / 100;
    const total = after + tax;

    row.querySelector('.item-total').textContent = total.toFixed(2);
}

function recalcTotals() {
    let subTotal = 0, discountTotal = 0, afterDiscountTotal = 0, taxTotal = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        recalcRow(row);
        const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        const disc  = parseFloat(row.querySelector('.item-disc')?.value)  || 0;
        const taxSel = row.querySelector('.item-tax');
        const rate  = taxSel ? (parseFloat(taxSel.selectedOptions[0]?.dataset.rate) || 0) : 0;

        const sub   = qty * price;
        const discount = sub * disc / 100;
        const after = sub - discount;
        subTotal += sub;
        discountTotal += discount;
        afterDiscountTotal += after;
        taxTotal += after * rate / 100;
    });

    document.getElementById('sumSubTotal').textContent = subTotal.toFixed(2);
    document.getElementById('sumDiscount').textContent = discountTotal.toFixed(2);
    document.getElementById('sumAfterDiscount').textContent = afterDiscountTotal.toFixed(2);
    document.getElementById('sumTax').textContent = taxTotal.toFixed(2);
    document.getElementById('sumTotal').textContent = (afterDiscountTotal + taxTotal).toFixed(2);
}

// Vendor → load bills via AJAX (handled via select2 change event below)
function loadVendorBills(vendorId) {
    const $billSel = $('#billSelect');
    $billSel.select2('destroy');
    $billSel.html('<option value="">Loading…</option>');

    if (!vendorId) {
        $billSel.html('<option value="">— None —</option>');
        $billSel.select2({ width: '100%', placeholder: '— None —', allowClear: true });
        return;
    }

    fetch(`/account/debit-notes/vendor/${vendorId}/bills`)
        .then(r => r.json())
        .then(bills => {
            let html = '<option value="">— None —</option>';
            bills.forEach(b => {
                html += `<option value="${b.id}">${b.unique_no} — ${b.date} (${parseFloat(b.total).toFixed(2)})</option>`;
            });
            $billSel.html(html);
        })
        .catch(() => { $billSel.html('<option value="">— None —</option>'); })
        .finally(() => {
            $billSel.select2({ width: '100%', placeholder: '— None —', allowClear: true });
        });
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.item-row').forEach(row => {
        bindRowEvents(row);
        setupAccountDefaultTax(row);
    });
    recalcTotals();
});
</script>

<script>
$(document).ready(function () {
    // AJAX Select2 for vendor (large dataset — paginated)
    $('#vendorSelect').select2({
        width: '100%',
        placeholder: '— Select vendor —',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: '{{ route("account.bill.vendors.paginate") }}',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term ?? '', page: params.page ?? 1 }),
            processResults(data, params) {
                params.page = params.page ?? 1;
                return {
                    results: data.data.map(v => ({ id: v.id, text: v.name })),
                    pagination: { more: !!data.next_page }
                };
            },
            cache: true
        }
    }).on('change', function () {
        loadVendorBills($(this).val());
    });

    // Init bill select2
    $('#billSelect').select2({ width: '100%', placeholder: '— None —', allowClear: true });

    $(document).on('change select2:select select2:clear select2:unselect', '#itemsBody .item-tax', function () {
        const row = this.closest('tr');
        if (!row) return;

        recalcRow(row);
        recalcTotals();
    });

    // Bill selected → auto-fill line items from bill
    $(document).on('change', '#billSelect', function () {
        const billId = $(this).val();
        if (!billId) return;

        fetch(`{{ url('/account/debit-notes/bill') }}/${billId}/items`)
            .then(r => r.json())
            .then(items => {
                if (!items.length) return;

                // Clear existing rows
                document.getElementById('itemsBody').innerHTML = '';
                itemCount = 0;

                // Add one row per bill item, pre-filled
                items.forEach(item => addItemWithData(item));
                recalcTotals();
            })
            .catch(err => console.error('Failed to load bill items:', err));
    });
});

// Add a new row pre-filled with item data (used for bill auto-fill)
function addItemWithData(data) {
    const idx = itemCount++;
    const row = document.createElement('tr');
    row.className = 'item-row border-b border-gray-100';
    const desc = (data.description || '').replace(/"/g, '&quot;');
    row.innerHTML = `
        <td class="py-2 pr-2">
            <input type="text" name="items[${idx}][description]" placeholder="Description" required
                value="${desc}"
                class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8]">
        </td>
        <td class="py-2 pr-2">
            <select name="items[${idx}][account_id]" required
                class="select2 w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8]">
                ${accountsHtml}
            </select>
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="items[${idx}][quantity]" value="${data.quantity ?? 1}" step="0.01" min="0.01" required
                class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-qty">
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="items[${idx}][price]" value="${data.price ?? 0}" step="0.01" min="0" required
                class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-price">
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="items[${idx}][discount_percentage]" value="${data.discount_percentage ?? 0}" step="0.01" min="0" max="100"
                class="w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 text-right focus:outline-none focus:border-[#0078C8] item-disc">
        </td>
        <td class="py-2 pr-2">
            <select name="items[${idx}][tax_id]"
                class="select2 w-full border border-gray-300 rounded-[3px] text-xs px-2 py-1.5 focus:outline-none focus:border-[#0078C8] item-tax">
                ${taxHtml}
            </select>
        </td>
        <td class="py-2 pr-2 text-right">
            <span class="item-total text-xs font-semibold text-gray-700">0.00</span>
        </td>
        <td class="py-2 text-center">
            <button type="button" onclick="removeItem(this)" class="text-red-400 hover:text-red-600 text-xs">&times;</button>
        </td>
    `;
    document.getElementById('itemsBody').appendChild(row);

    const $row = $(row);
    $row.find('select').select2({ width: '100%' });
    setupAccountDefaultTax(row);

    // Set pre-selected account and tax
    if (data.account_id) {
        $row.find('select[name$="[account_id]"]').val(data.account_id).trigger('change');
    }
    if (data.tax_id) {
        $row.find('select[name$="[tax_id]"]').val(data.tax_id).trigger('change');
    }

    bindRowEvents(row);
    recalcRow(row);
}
</script>
@endpush
