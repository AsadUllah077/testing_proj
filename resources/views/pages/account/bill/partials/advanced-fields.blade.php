@php($editingBill = $bill ?? null)
<details class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
    <summary class="cursor-pointer text-sm font-semibold text-gray-700">Invoice, Tax & Operational Details</summary>
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'vendor_invoice_date' => ['Vendor invoice date', 'date'],
            'posting_date' => ['Posting date', 'date'],
            'due_date' => ['Due date', 'date'],
            'vendor_tax_invoice_reference' => ['Vendor tax reference', 'text'],
            'purchase_order_no' => ['Purchase order', 'text'],
            'goods_receipt_no' => ['Goods receipt (GRN)', 'text'],
            'project' => ['Project', 'text'],
            'cost_centre' => ['Cost centre', 'text'],
            'department' => ['Department', 'text'],
            'warehouse' => ['Warehouse', 'text'],
        ] as $field => [$label, $type])
            @php($value = $editingBill?->{$field})
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
                <input type="{{ $type }}" name="{{ $field }}" value="{{ old($field, $type === 'date' && $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : $value) }}" class="{{ $type === 'date' ? 'date ' : '' }}w-full h-10 px-3 border border-gray-300 rounded-md text-sm">
            </div>
        @endforeach
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Exchange rate to {{ currency('code') }}</label>
            <input type="number" name="exchange_rate" id="exchange_rate" min="0.000001" step="0.000001" value="{{ old('exchange_rate', $editingBill?->exchange_rate ?? 1) }}" title="1 invoice currency unit equals this many {{ currency('code') }}" oninput="updateBaseCurrencyTotal()" class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Withholding tax</label>
            <input type="number" id="withholding_tax" name="withholding_tax" min="0" step="0.01" value="{{ old('withholding_tax', $editingBill?->withholding_tax ?? 0) }}" class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Withholding payable account</label>
            <select name="withholding_account_id" id="withholding_account_id" class="select2 w-full h-10 px-3 border border-gray-300 rounded-md text-sm">
                <option value="">-- Select when withholding applies --</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" {{ (string) old('withholding_account_id', $editingBill?->withholding_account_id) === (string) $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</details>
