@php $ri = $ri ?? new \App\Models\RecurringInvoice(); @endphp
<div class="p-4 mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6 bg-white p-5">
        <a href="{{ route('account.recurring-invoice.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-gray-800">
                {{ $editing ? 'Edit Recurring Invoice' : 'New Recurring Invoice' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $editing ? 'Update the schedule — changes apply from the next run date.' : 'Set up a schedule and invoices will be created automatically.' }}
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-[5px] p-4 mb-5">
            <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
        action="{{ $editing ? route('account.recurring-invoice.update', $ri) : route('account.recurring-invoice.store') }}"
        id="recurringForm">
        @csrf
        @if($editing) @method('PUT') @endif

        {{-- ── Schedule Settings ──────────────────────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-[5px] p-5 mb-5">
            <h2 class="text-sm font-bold text-gray-700 mb-4 pb-2 border-b border-gray-100">Schedule Settings</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $ri->title ?? '') }}"
                        placeholder="e.g. Monthly Retainer — Acme Corp"
                        class="w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Customer <span class="text-red-500">*</span></label>
                    <select id="customer_id" name="customer_id"
                        class="w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                        <option value="">Select customer…</option>
                        @php $selCustomerId = old('customer_id', data_get($ri, 'customer_id')); @endphp
                        @if($selCustomerId)
                            @php $selCustomer = $customers->firstWhere('id', $selCustomerId); @endphp
                            @if($selCustomer)
                                <option value="{{ $selCustomer->id }}" selected>{{ $selCustomer->name }}</option>
                            @endif
                        @endif
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Revenue Account <span class="text-red-500">*</span></label>
                    <select name="account_id"
                        class="select2 w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                        <option value="">Select account…</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}"
                                {{ old('account_id', $ri->account_id ?? '') == $a->id ? 'selected' : '' }}>
                                {{ $a->account_number }} — {{ $a->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Frequency <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="number" name="interval" min="1" max="12"
                            value="{{ old('interval', $ri->interval ?? 1) }}"
                            class="w-20 border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                        <select name="frequency"
                            class="select2 flex-1 border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                            <option value="weekly"    {{ old('frequency', $ri->frequency ?? '') === 'weekly'    ? 'selected' : '' }}>Week(s)</option>
                            <option value="monthly"   {{ old('frequency', $ri->frequency ?? 'monthly') === 'monthly'   ? 'selected' : '' }}>Month(s)</option>
                            <option value="quarterly" {{ old('frequency', $ri->frequency ?? '') === 'quarterly' ? 'selected' : '' }}>Quarter(s)</option>
                            <option value="yearly"    {{ old('frequency', $ri->frequency ?? '') === 'yearly'    ? 'selected' : '' }}>Year(s)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Terms (days)</label>
                    <input type="number" name="payment_terms_days" min="0"
                        value="{{ old('payment_terms_days', $ri->payment_terms_days ?? 30) }}"
                        class="w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>

                @if(!$editing)
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date <span class="text-red-500">*</span></label>
                    <input class="date" type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}"
                        class="date w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">End Date <span class="text-xs text-gray-400 font-normal">(leave blank for indefinite)</span></label>
                    <input class="date" type="date" name="end_date"
                        value="{{ old('end_date', optional($ri->end_date ?? null)->toDateString()) }}"
                        class="date w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Max Occurrences <span class="text-xs text-gray-400 font-normal">(leave blank for unlimited)</span></label>
                    <input type="number" name="max_occurrences" min="1"
                        value="{{ old('max_occurrences', $ri->max_occurrences ?? '') }}"
                        placeholder="e.g. 12 for 12 months"
                        class="w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>

                @if($editing)
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status"
                        class="select2 w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]">
                        <option value="active"    {{ old('status', $ri->status ?? 'active') === 'active'    ? 'selected' : '' }}>Active</option>
                        <option value="paused"    {{ old('status', $ri->status ?? '') === 'paused'    ? 'selected' : '' }}>Paused</option>
                        <option value="cancelled" {{ old('status', $ri->status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                @endif

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Notes</label>
                    <textarea name="note" rows="2"
                        class="w-full border border-gray-300 rounded-[3px] px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]"
                        placeholder="Internal notes (appears on each generated invoice)">{{ old('note', $ri->note ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Line Items ──────────────────────────────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-[5px] p-5 mb-5">
            <h2 class="text-sm font-bold text-gray-700 mb-4 pb-2 border-b border-gray-100">Invoice Line Items</h2>

            <div id="itemsContainer" class="space-y-3">
                {{-- Existing items on edit --}}
                @if($editing && $ri->items->isNotEmpty())
                    @foreach($ri->items as $idx => $item)
                        <div class="item-row grid grid-cols-12 gap-2 items-end">
                            <div class="col-span-3">
                                @if($idx === 0)<label class="block text-xs text-gray-500 mb-1">Description</label>@endif
                                <input type="text" name="items[{{ $idx }}][description]"
                                    value="{{ old("items.{$idx}.description", $item->description) }}"
                                    class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                            </div>
                            <div class="col-span-2">
                                @if($idx === 0)<label class="block text-xs text-gray-500 mb-1">Account</label>@endif
                                <select name="items[{{ $idx }}][account_id]"
                                    class="select2 w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                                    @foreach($accounts as $a)
                                        <option value="{{ $a->id }}" {{ $item->account_id == $a->id ? 'selected' : '' }}>
                                            {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-1">
                                @if($idx === 0)<label class="block text-xs text-gray-500 mb-1">Qty</label>@endif
                                <input type="number" name="items[{{ $idx }}][quantity]" step="0.01" min="0.01"
                                    value="{{ old("items.{$idx}.quantity", $item->quantity) }}"
                                    class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                            </div>
                            <div class="col-span-2">
                                @if($idx === 0)<label class="block text-xs text-gray-500 mb-1">Unit Price</label>@endif
                                <input type="number" name="items[{{ $idx }}][price]" step="0.01" min="0"
                                    value="{{ old("items.{$idx}.price", $item->price) }}"
                                    class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                            </div>
                            <div class="col-span-1">
                                @if($idx === 0)<label class="block text-xs text-gray-500 mb-1">Disc %</label>@endif
                                <input type="number" name="items[{{ $idx }}][discount_percentage]" step="0.01" min="0" max="100"
                                    value="{{ old("items.{$idx}.discount_percentage", $item->discount_percentage ?? 0) }}"
                                    class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                            </div>
                            <div class="col-span-2">
                                @if($idx === 0)<label class="block text-xs text-gray-500 mb-1">Tax</label>@endif
                                <select name="items[{{ $idx }}][tax_id]"
                                    class="select2 w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                                    <option value="">No tax</option>
                                    @foreach($taxRates as $t)
                                        <option value="{{ $t->id }}" {{ $item->tax_id == $t->id ? 'selected' : '' }}>
                                            {{ $t->name }} ({{ $t->tax_rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-1 flex justify-center">
                                <button type="button" onclick="removeItem(this)"
                                    class="text-red-400 hover:text-red-600 text-lg leading-none mt-0.5">×</button>
                            </div>
                        </div>
                    @endforeach
                @else
                    {{-- Default empty row for create --}}
                    <div class="item-row grid grid-cols-12 gap-2 items-end">
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-500 mb-1">Description</label>
                            <input type="text" name="items[0][description]"
                                class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]"
                                placeholder="Service description">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">Account</label>
                            <select name="items[0][account_id]"
                                class="select2 w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                                @foreach($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs text-gray-500 mb-1">Qty</label>
                            <input type="number" name="items[0][quantity]" step="0.01" min="0.01" value="1"
                                class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">Unit Price</label>
                            <input type="number" name="items[0][price]" step="0.01" min="0"
                                class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs text-gray-500 mb-1">Disc %</label>
                            <input type="number" name="items[0][discount_percentage]" step="0.01" min="0" max="100" value="0"
                                class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">Tax</label>
                            <select name="items[0][tax_id]"
                                class="select2 w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                                <option value="">No tax</option>
                                @foreach($taxRates as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->tax_rate }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-1"></div>
                    </div>
                @endif
            </div>

            <button type="button" onclick="addItem()"
                class="mt-4 text-sm text-[#0078C8] font-semibold hover:underline flex items-center gap-1">
                <span class="text-base">+</span> Add Line Item
            </button>

            <div class="mt-5 flex justify-end">
                <div class="w-full sm:w-80 border border-gray-200 rounded-[5px] bg-gray-50 p-4 text-sm">
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-semibold text-gray-700" id="recurringSubtotal">0.00</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">Discount</span>
                        <span class="font-semibold text-gray-700" id="recurringDiscount">0.00</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">After Discount</span>
                        <span class="font-semibold text-gray-700" id="recurringAfterDiscount">0.00</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">Tax</span>
                        <span class="font-semibold text-gray-700" id="recurringTax">0.00</span>
                    </div>
                    <div class="flex justify-between pt-2 mt-2 border-t border-gray-200">
                        <span class="font-bold text-gray-700">Total After Tax</span>
                        <span class="font-bold text-[#0078C8]" id="recurringTotal">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Actions ─────────────────────────────────────────────────────── --}}
        <div class="flex gap-3 justify-end">
            <a href="{{ route('account.recurring-invoice.index') }}"
                class="px-5 py-2 border border-gray-300 rounded-[3px] text-sm text-gray-600 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2 bg-[#0078C8] text-white rounded-[3px] text-sm font-semibold hover:bg-[#006BB0]">
                {{ $editing ? 'Save Changes' : 'Create Schedule' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
let itemCount = {{ $editing ? ($ri->items->count()) : 1 }};
const accounts = @json($accounts);
const taxes = @json($taxRates);

const accountOptions = `@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach`;
const taxOptions     = `<option value="">No tax</option>@foreach($taxRates as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->tax_rate }}%)</option>@endforeach`;

@include('pages.account.partials.account-tax-default-script')

function money(value) {
    return Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function numericValue(input) {
    return parseFloat(input?.value || 0) || 0;
}

function taxRateForId(taxId) {
    const tax = taxes.find(item => String(item.id) === String(taxId || ''));

    return parseFloat(tax?.tax_rate || 0) || 0;
}

function calculateRecurringTotals() {
    let subtotal = 0;
    let discountTotal = 0;
    let taxTotal = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const qty = numericValue(row.querySelector('input[name$="[quantity]"]'));
        const price = numericValue(row.querySelector('input[name$="[price]"]'));
        const discountPercent = numericValue(row.querySelector('input[name$="[discount_percentage]"]'));
        const taxId = row.querySelector('select[name$="[tax_id]"]')?.value;
        const rowSubtotal = qty * price;
        const rowDiscount = rowSubtotal * Math.min(Math.max(discountPercent, 0), 100) / 100;
        const rowAfterDiscount = rowSubtotal - rowDiscount;
        const rowTax = rowAfterDiscount * taxRateForId(taxId) / 100;

        subtotal += rowSubtotal;
        discountTotal += rowDiscount;
        taxTotal += rowTax;
    });

    const afterDiscount = subtotal - discountTotal;
    const total = afterDiscount + taxTotal;

    document.getElementById('recurringSubtotal').textContent = money(subtotal);
    document.getElementById('recurringDiscount').textContent = money(discountTotal);
    document.getElementById('recurringAfterDiscount').textContent = money(afterDiscount);
    document.getElementById('recurringTax').textContent = money(taxTotal);
    document.getElementById('recurringTotal').textContent = money(total);
}

function bindRecurringTotalEvents(row) {
    row.querySelectorAll('input, select').forEach(field => {
        field.addEventListener('input', calculateRecurringTotals);
        field.addEventListener('change', calculateRecurringTotals);
    });

    $(row).find('select').on('select2:select select2:clear select2:unselect', calculateRecurringTotals);
}

function addItem() {
    const idx = itemCount++;
    const row = document.createElement('div');
    row.className = 'item-row grid grid-cols-12 gap-2 items-end';
    row.innerHTML = `
        <div class="col-span-3">
            <input type="text" name="items[${idx}][description]"
                class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]"
                placeholder="Service description">
        </div>
        <div class="col-span-2">
            <select name="items[${idx}][account_id]"
                class="select2 w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                ${accountOptions}
            </select>
        </div>
        <div class="col-span-1">
            <input type="number" name="items[${idx}][quantity]" step="0.01" min="0.01" value="1"
                class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
        </div>
        <div class="col-span-2">
            <input type="number" name="items[${idx}][price]" step="0.01" min="0"
                class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
        </div>
        <div class="col-span-1">
            <input type="number" name="items[${idx}][discount_percentage]" step="0.01" min="0" max="100" value="0"
                class="w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
        </div>
        <div class="col-span-2">
            <select name="items[${idx}][tax_id]"
                class="select2 w-full border border-gray-300 rounded-[3px] px-2 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                ${taxOptions}
            </select>
        </div>
        <div class="col-span-1 flex justify-center">
            <button type="button" onclick="removeItem(this)"
                class="text-red-400 hover:text-red-600 text-lg leading-none">×</button>
        </div>`;
    document.getElementById('itemsContainer').appendChild(row);
    // init select2 on the newly added row's selects
    $(row).find('select').select2({ width: '100%' });
    setupAccountDefaultTax(row);
    bindRecurringTotalEvents(row);
    calculateRecurringTotals();
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) { alert('At least one line item is required.'); return; }
    btn.closest('.item-row').remove();
    calculateRecurringTotals();
}
</script>

<script>
$(document).ready(function () {
    // AJAX Select2 for customer (large dataset — paginated)
    document.querySelectorAll('.item-row').forEach(row => {
        setupAccountDefaultTax(row);
        bindRecurringTotalEvents(row);
    });

    calculateRecurringTotals();

    $('#customer_id').select2({
        width: '100%',
        placeholder: 'Select customer…',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: '{{ route("account.sale.customers.paginate") }}',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term ?? '', page: params.page ?? 1 }),
            processResults(data, params) {
                params.page = params.page ?? 1;
                return {
                    results: data.data.map(c => ({ id: c.id, text: c.name })),
                    pagination: { more: !!data.next_page_url }
                };
            },
            cache: true
        }
    });
});
</script>
@endpush
