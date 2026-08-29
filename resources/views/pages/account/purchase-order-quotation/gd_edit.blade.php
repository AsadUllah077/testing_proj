@extends('layouts.auth')

@section('title', 'Edit Purchase Order Quotation')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.purchase-order') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="p-2">
            <div class="mx-auto">
                <div class="bg-white border border-gray-300 rounded-md shadow-sm">
                    <!-- Header -->
                    <div
                        class="p-5 border-b border-gray-200 flex rounded-md justify-between items-center bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="text-xl font-semibold text-gray-800">Edit Purchase Order Quotation</h2>
                        <div class="flex gap-2">
                            <a href="{{ route('account.purchase-order.order.list') }}"
                                class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg">
                                ← BACK
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <form action_url="{{ route('account.purchase-order-quotation.gd_update', $purchaseOrder->id) }}"
                    back_url="{{ route('account.purchase-order-quotation.good-recieve') }}" method="POST"
                    class="bg-white border border-gray-300 rounded-lg shadow-sm my-4" id="formSubmit">
                    @csrf
                    @method('PUT')

                    <!-- Details -->
                    <div class="p-6">
                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Vendor</label>
                                <select name="vendor_id"
                                    class="select2 w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                                    <option value="">Select Vendor</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}"
                                            {{ $purchaseOrder->vendor_id == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order No</label>
                                <input type="text" name="order_no" value="{{ $purchaseOrder->order_no }}"
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order Date</label>
                                <input class="date" type="date" name="order_date"
                                    value="{{ $purchaseOrder->order_date->format('Y-m-d') }}"
                                    class="date w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Departure Date</label>
                                <input class="date" type="date" name="departure_date"
                                    value="{{ $purchaseOrder->departure_date->format('Y-m-d') }}"
                                    class="date w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ref No</label>
                                <input type="text" name="ref_no" value="{{ $purchaseOrder->ref_no ?? '' }}"
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Requested By</label>
                                <input type="text" name="requested_by" value="{{ $purchaseOrder->requested_by ?? '' }}"
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Invoice No</label>
                                <input type="text" name="invoice_no" value="{{ $purchaseOrder->invoice_no ?? '' }}"
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                <select name="currency_id"
                                    class="select2 w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                                    <option value="">Select Currency</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ $purchaseOrder->currency_id == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->code }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status"
                                    class="select2 w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 outline-none">
                                    <option value="0" {{ $purchaseOrder->status == 0 ? 'selected' : '' }}>Draft
                                    </option>
                                    <option value="1" {{ $purchaseOrder->status == 1 ? 'selected' : '' }}>Sent
                                    </option>
                                    <option value="2" {{ $purchaseOrder->status == 2 ? 'selected' : '' }}>Approve &
                                        Delivered</option>
                                    <option value="3" {{ $purchaseOrder->status == 3 ? 'selected' : '' }}>Cancelled
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6 shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm border-collapse">
                                    <thead
                                        class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 text-gray-700">
                                        <tr>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left w-24 font-semibold">Item
                                            </th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left font-semibold">
                                                Description</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-center w-20 font-semibold">HS
                                                Code</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-center w-20 font-semibold">
                                                Qty</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-center w-20 font-semibold">Kg
                                            </th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-right w-24 font-semibold">
                                                Price</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left w-32 font-semibold">
                                                Account</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left w-24 font-semibold">Tax
                                            </th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-right w-20 font-semibold">
                                                Project</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-right w-28 font-semibold">
                                                Total</th>
                                            <th class="px-3 py-3 text-center w-20 font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white" id="items-table-body">
                                        @foreach ($purchaseOrder->purchaseOrderQuotationItems as $index => $item)
                                            <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors">
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <input type="text" name="items[{{ $index }}][item]"
                                                        value="{{ $item->item ?? '' }}"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <textarea name="items[{{ $index }}][description]" rows="1"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">{{ $item->description ?? '' }}</textarea>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-center">
                                                    <input type="text" name="items[{{ $index }}][hs_code]"
                                                        value="{{ $item->hs_code ?? '' }}"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-center">
                                                    <input type="number" name="items[{{ $index }}][quantity]"
                                                        value="{{ $item->quantity }}" min="1"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 quantity-input">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-center">
                                                    <input type="number" name="items[{{ $index }}][kg]"
                                                        value="{{ $item->kg ?? 0 }}" min="0"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-right">
                                                    <input type="number" name="items[{{ $index }}][price]"
                                                        value="{{ $item->price }}" step="0.01" min="0"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-right focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 price-input">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <select name="items[{{ $index }}][account_id]"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                                        <option value="">Select Account</option>
                                                        @foreach ($accounts as $account)
                                                            <option value="{{ $account->id }}"
                                                                {{ $item->account_id == $account->id ? 'selected' : '' }}>
                                                                {{ $account->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <select name="items[{{ $index }}][tax_id]"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                                        <option value="">No Tax</option>
                                                        @foreach ($taxes as $tax)
                                                            <option value="{{ $tax->id }}"
                                                                {{ $item->tax_id == $tax->id ? 'selected' : '' }}>
                                                                {{ $tax->name }} ({{ $tax->rate }}%)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-right">
                                                    <input type="text" name="items[{{ $index }}][project]"
                                                        value="{{ $item->project ?? '' }}"
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-right focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-right">
                                                    <input type="number" name="items[{{ $index }}][total]"
                                                        value="{{ $item->total }}" step="0.01" readonly
                                                        class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-right bg-gray-100 total-input">
                                                </td>
                                                <td class="px-2 py-2 text-center">
                                                    <button type="button"
                                                        class="text-red-600 hover:text-red-800 remove-item">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                            class="w-5 h-5">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-3 border-t border-gray-300">
                                <button type="button" id="add-item"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded hover:bg-[#006bb0] transition">
                                    + ADD NEW ITEM
                                </button>
                            </div>
                        </div>

                        <!-- Buttons and Totals -->
                        <div class="grid grid-cols-1 md:grid-cols-2 mb-6 gap-4">
                            <div>
                                <div class="mt-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                                        <textarea name="note" rows="4"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">{{ $purchaseOrder->note ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-lg p-4 shadow-sm w-full">
                                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                                    <div class="text-left text-gray-600 font-medium">Sub Total:</div>
                                    <div class="text-left font-semibold text-gray-800" id="sub-total">
                                        {{ number_format($purchaseOrder->sub_total, 2) }}</div>
                                    <div class="text-left text-gray-600 font-medium">Tax:</div>
                                    <div class="text-left font-semibold text-gray-800" id="tax-total">
                                        {{ number_format($purchaseOrder->tax, 2) }}</div>
                                    <div class="text-left text-[#0078C8] font-semibold border-t border-gray-300 pt-2">
                                        Total:</div>
                                    <div class="text-left font-bold text-[#0078C8] border-t border-gray-300 pt-2 text-lg"
                                        id="grand-total">{{ number_format($purchaseOrder->total, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">Payment Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type of Payment</label>
                                    <x-acccount.coa.coa-select name="payment_type" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Paid Amount</label>
                                    <input type="number" name="paid_amount"
                                        value="{{ $purchaseOrder->paid_amount ?? 0 }}" step="0.01" min="0"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                        id="paid-amount">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Remaining Amount</label>
                                    <input type="number" name="remaining_amount"
                                        value="{{ $purchaseOrder->remaining_amount ?? $purchaseOrder->total }}"
                                        step="0.01" readonly
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-100"
                                        id="remaining-amount">
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Section -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">Delivery Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
                                    <textarea name="delivery_address" rows="3"
                                        class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">{{ $purchaseOrder->delivery_address ?? '' }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery
                                        Instruction</label>
                                    <textarea name="delivery_instruction" rows="3"
                                        class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">{{ $purchaseOrder->delivery_instruction ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="submit"
                                class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6A2.25 2.25 0 016 3.75h1.5m9 0h-9" />
                                </svg>
                                UPDATE PURCHASE ORDER
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let itemCounter = {{ $purchaseOrder->purchaseOrderQuotationItems->count() }};

        // Add new item row
        document.getElementById('add-item').addEventListener('click', function() {
            const tbody = document.getElementById('items-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'border-b border-gray-200 hover:bg-blue-50 transition-colors';
            newRow.innerHTML = `
            <td class="border-r border-gray-300 px-2 py-2">
                <input type="text" name="items[${itemCounter}][item]"
                    class="w-full h-9 px-2 border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
            </td>
            <td class="border-r border-gray-300 px-2 py-2">
                <textarea name="items[${itemCounter}][description]" rows="1"
                    class="w-full h-9 px-2 border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"></textarea>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center">
                <input type="text" name="items[${itemCounter}][hs_code]"
                    class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center">
                <input type="number" name="items[${itemCounter}][quantity]" value="1" min="1"
                    class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 quantity-input">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center">
                <input type="number" name="items[${itemCounter}][kg]" value="0" min="0"
                    class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right">
                <input type="number" name="items[${itemCounter}][price]" value="0" step="0.01" min="0"
                    class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-right focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 price-input">
            </td>
            <td class="border-r border-gray-300 px-2 py-2">
                <select name="items[${itemCounter}][account_id]" class="w-full h-9 px-2 border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                    <option value="">Select Account</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="border-r border-gray-300 px-2 py-2">
                <select name="items[${itemCounter}][tax_id]" class="w-full h-9 px-2 border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                    <option value="">No Tax</option>
                    @foreach ($taxes as $tax)
                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                    @endforeach
                </select>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right">
                <input type="text" name="items[${itemCounter}][project]"
                    class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-right focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right">
                <input type="number" name="items[${itemCounter}][total]" value="0" step="0.01" readonly
                    class="w-full h-9 px-2 border border-gray-200 rounded text-sm text-right bg-gray-100 total-input">
            </td>
            <td class="px-2 py-2 text-center">
                <button type="button" class="text-red-600 hover:text-red-800 remove-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" />
                    </svg>
                </button>
            </td>
        `;
            tbody.appendChild(newRow);

            // Add event listeners to new row
            const quantityInput = newRow.querySelector('.quantity-input');
            const priceInput = newRow.querySelector('.price-input');
            const totalInput = newRow.querySelector('.total-input');
            const removeBtn = newRow.querySelector('.remove-item');

            [quantityInput, priceInput].forEach(input => {
                input.addEventListener('input', calculateRowTotal);
            });

            removeBtn.addEventListener('click', function() {
                if (tbody.children.length > 1) {
                    tbody.removeChild(newRow);
                    calculateTotals();
                }
            });

            itemCounter++;
        });

        // Remove item
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                const row = e.target.closest('tr');
                const tbody = document.getElementById('items-table-body');
                if (tbody.children.length > 1) {
                    tbody.removeChild(row);
                    calculateTotals();
                }
            }
        });

        // Calculate row total
        function calculateRowTotal() {
            const row = this.closest('tr');
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 1;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const totalInput = row.querySelector('.total-input');

            const total = quantity * price;
            totalInput.value = total.toFixed(2);

            calculateTotals();
        }

        // Calculate all totals
        function calculateTotals() {
            let subTotal = 0;

            document.querySelectorAll('#items-table-body tr').forEach(row => {
                const total = parseFloat(row.querySelector('.total-input').value) || 0;
                subTotal += total;
            });

            // For now, tax is calculated separately. You can add tax calculation logic here
            const taxTotal = 0; // You can implement tax calculation based on tax rates
            const grandTotal = subTotal + taxTotal;

            // Update display
            document.getElementById('sub-total').textContent = subTotal.toFixed(2);
            document.getElementById('tax-total').textContent = taxTotal.toFixed(2);
            document.getElementById('grand-total').textContent = grandTotal.toFixed(2);

            // Calculate remaining amount
            calculateRemainingAmount(grandTotal);
        }

        // Calculate remaining amount
        function calculateRemainingAmount(grandTotal) {
            const paidAmount = parseFloat(document.getElementById('paid-amount').value) || 0;
            const remainingAmount = grandTotal - paidAmount;

            document.getElementById('remaining-amount').value = remainingAmount.toFixed(2);
        }

        // Paid amount change event
        document.getElementById('paid-amount').addEventListener('input', function() {
            const grandTotal = parseFloat(document.getElementById('grand-total').textContent) || 0;
            calculateRemainingAmount(grandTotal);
        });

        // Initialize event listeners for existing rows
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.quantity-input, .price-input').forEach(input => {
                input.addEventListener('input', calculateRowTotal);
            });

            // Calculate initial totals
            calculateTotals();
        });
    </script>
@endpush
