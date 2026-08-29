@extends('layouts.auth')

@section('title', 'Edit Credit Note')

@section('content')
    <div class="mb-8">
        <div class="p-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active'] . '*') }}" />
                @endforeach
            </div>
        </div>
        <div class="py-8">
            <div class="mx-auto px-3">
                <div class="bg-white border border-gray-300 rounded-md">

                    <!-- Header -->
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Edit Credit Note - {{ $creditNote->unique_no }}</h2>
                        <a href="{{ get_index_url('account.sale.credit-notes.index') }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded hover:bg-[#006bb0] transition">
                            BACK
                        </a>
                    </div>

                    <!-- Form -->
                    <form id="formSubmit" back_url="{{ get_index_url('account.sale.credit-notes.index') }}"
                        action_url="{{ route('account.sale.credit-notes.update', $creditNote->id) }}" method="POST"
                        class="p-4">
                        @csrf
                        @method('PUT')

                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Ref No</label>
                                <input type="text" name="ref_no" value="{{ $creditNote->ref_no }}"
                                    class="w-full px-2 py-2 border border-gray-300 outline-none rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                    placeholder="Enter reference number">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Invoice No</label>
                                <input type="text" name="invoice_no" value="{{ $creditNote->invoice_no }}"
                                    class="w-full px-2 py-2 border border-gray-300 rounded outline-none text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                    placeholder="Enter invoice number">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Select Customer *</label>
                                <select name="customer_id" id="customer_id" required
                                    class="select2 w-full h-9 border border-gray-300 rounded text-sm outline-none focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                    <option value="">Select Customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            {{ $creditNote->customer_id == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Select Sales</label>
                                <select name="sale_id" id="sale_id"
                                    class=" select2 w-full h-9 border border-gray-300 rounded text-sm outline-none focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                    <option value="">Select Sales</option>
                                    @if ($creditNote->sale_id)
                                        <option value="{{ $creditNote->sale_id }}" selected>
                                            {{ $creditNote->sale_unique_no }}</option>
                                    @endif
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Date *</label>
                                <input type="date" name="date" required
                                    class="date w-full px-2 py-2 border border-gray-300 outline-none rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                    value="{{ $creditNote->date->format('Y-m-d') }}">
                            </div>

                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto -mx-4 sm:mx-0">
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden border border-gray-300 rounded mb-4">
                                    <table id="invoice-table"
                                        class="min-w-[1000px] w-full text-sm border-collapse table-fixed">
                                        <thead class="bg-gray-50 border-b border-gray-300 text-gray-700">
                                            <tr>
                                                <th class="border-r border-gray-300 px-3 py-2 text-left w-24 column-item">
                                                    Item *
                                                </th>
                                                <th
                                                    class="border-r border-gray-300 px-3 py-2 text-left w-64 column-description">
                                                    Description</th>
                                                <th class="border-r border-gray-300 px-3 py-2 text-center w-20 column-hs-code"
                                                    style="display: none">HS
                                                    Code</th>
                                                <th class="border-r border-gray-300 px-3 py-2 text-center w-20 column-qty">
                                                    Qty *
                                                </th>
                                                <th class="border-r border-gray-300 px-3 py-2 text-center w-20 column-kg"
                                                    style="display: none">Kg</th>
                                                <th class="border-r border-gray-300 px-3 py-2 text-right w-24 column-price">
                                                    Price *
                                                </th>
                                                <th
                                                    class="border-r border-gray-300 px-3 py-2 text-left w-32 column-account">
                                                    Account
                                                </th>
                                                <th class="border-r border-gray-300 px-3 py-2 text-right w-20 column-tax">
                                                    Tax</th>
                                                <th class="border-r border-gray-300 px-3 py-2 text-right w-24 column-discount"
                                                    style="display: none">
                                                    Discount %</th>
                                                <th class="px-3 py-2 text-right w-28 column-total">Total</th>
                                                <th class="px-2 py-2 w-8 text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoice-table-body">
                                            <!-- Rows will be added dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons and Totals -->
                        <div class="flex flex-wrap justify-between items-start mb-4 gap-3">
                            <div class="flex gap-2">
                                <button type="button" id="add-new-line"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded hover:bg-[#006bb0] transition">
                                    ADD NEW LINE
                                </button>

                                <!-- Column Dropdown -->
                                <div class="relative">
                                    <button type="button" id="column-toggle-btn"
                                        class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm rounded hover:bg-[#0078C8] hover:text-white transition">
                                        COLUMN ▼
                                    </button>
                                    <div id="columnDropdown"
                                        class="hidden absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                        <ul class="py-1 text-sm text-gray-700">
                                            <li>
                                                <label class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="item"
                                                        checked> Item
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2"
                                                        data-column="description" checked> Description
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="hs-code">
                                                    HS Code
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="qty"
                                                        checked> Qty
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="kg"> Kg
                                                </label>
                                            </li>
                                            <li>
                                                <label
                                                    class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="price"
                                                        checked> Price
                                                </label>
                                            </li>
                                            <li>
                                                <label
                                                    class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2"
                                                        data-column="account" checked> Account
                                                </label>
                                            </li>
                                            <li>
                                                <label
                                                    class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="tax"
                                                        checked> Tax
                                                </label>
                                            </li>
                                            <li>
                                                <label
                                                    class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2"
                                                        data-column="discount"> Discount
                                                </label>
                                            </li>
                                            <li>
                                                <label
                                                    class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="total"
                                                        checked> Total
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-6 text-sm text-gray-700">
                                <div class="text-right">Sub Total:</div>
                                <div class="text-right font-medium" id="sub-total">
                                    {{ number_format($creditNote->sub_total, 2, '.', '') }}</div>
                                <div class="text-right">Tax:</div>
                                <div class="text-right font-medium" id="tax-total">
                                    {{ number_format($creditNote->tax, 2, '.', '') }}</div>
                                <div class="text-right">Discount:</div>
                                <div class="text-right font-medium" id="discount-total">
                                    {{ number_format($creditNote->discount, 2, '.', '') }}</div>
                                <div class="text-right font-semibold">Total:</div>
                                <div class="text-right font-semibold" id="grand-total">
                                    {{ number_format($creditNote->total_after_discount, 2, '.', '') }}</div>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Type of Payment <span
                                        id="payment_type_asterisk" class="text-red-500 hidden">*</span></label>
                                <x-chart-account-bank-select name="payment_type" id="payment_type"
                                    :selected="old('payment_type', $creditNote->account_id)" />
                                <span class="text-red-500 text-sm hidden" id="payment_type_error">Please select a
                                    payment type</span>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Select Currency *</label>
                                <select name="currency_id" id="currency_id" required
                                    class="select2 w-full h-9 border border-gray-300 rounded text-sm outline-none focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                    <option value="">Select Currency</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ $creditNote->currency_id == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->code }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Paid Amount *</label>
                                <input type="number" name="paid_amount" id="paid_amount" required step="0.01"
                                    min="0"
                                    class="w-full px-2 py-2 border border-gray-300 outline-none rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                    value="{{ number_format($creditNote->paid_amount, 2, '.', '') }}">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Remaining Amount</label>
                                <input type="number" id="remaining_amount" name="remaining_amount"
                                    value="{{ number_format($creditNote->remaining_amount, 2, '.', '') }}" step="0.01"
                                    readonly
                                    class="w-full px-2 py-2 border border-gray-300 outline-none rounded text-sm bg-gray-100">
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex justify-end">
                            <button type="submit" id="submit-btn"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded hover:bg-[#006bb0] transition flex items-center">
                                <span id="submit-text">UPDATE & APPROVE</span>
                                <div id="loading-spinner" class="hidden ml-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <x-acccount.loader />
@endsection

@push('scripts')
    <script>
        // Global variables
        let accounts = [];
        let taxes = [];
        let rowCounter = 0;
        const initialItems = @json($creditNote->creditNoteItems);

        @include('pages.account.partials.account-tax-default-script')

        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            setupEventListeners();
        });

        // Initialize form
        async function initializeForm() {
            showLoading();
            await loadDropdownData();

            if (initialItems.length > 0) {
                populateExistingItems(initialItems);
            } else {
                addNewRow();
            }

            loadColumnVisibility();
            calculateTotals();
            hideLoading();
        }

        // Load dropdown data via AJAX
        async function loadDropdownData() {
            try {
                // Load accounts
                const accountsResponse = await fetch('{{ route('account.sale.credit-notes.get-accounts') }}');
                accounts = await accountsResponse.json();

                // Load taxes
                const taxesResponse = await fetch('{{ route('account.sale.credit-notes.get-taxes') }}');
                taxes = await taxesResponse.json();
            } catch (error) {
                console.error('Error loading dropdown data:', error);
                Notification.error('Error loading configuration data');
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            // Customer change event
            $('#customer_id').on('change', function() {
                loadCustomerSales($(this).val());
            });

            // Sale change event
            $('#sale_id').on('change', function() {
                if ($(this).val() && $(this).val() != '{{ $creditNote->sale_id }}') {
                    loadSaleDetails($(this).val());
                }
            });

            // Paid amount change event
            document.getElementById('paid_amount').addEventListener('input', function() {
                calculateRemainingAmount();
                if (typeof togglePaymentTypeRequired === 'function') {
                    togglePaymentTypeRequired();
                }
            });

            setupPaymentValidation();

            // Add new row button
            document.getElementById('add-new-line').addEventListener('click', addNewRow);

            // Column toggle
            document.getElementById('column-toggle-btn').addEventListener('click', function() {
                document.getElementById('columnDropdown').classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('columnDropdown');
                const button = document.getElementById('column-toggle-btn');
                if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        // Populate existing items
        function populateExistingItems(items) {
            const tbody = document.getElementById('invoice-table-body');
            tbody.innerHTML = '';

            items.forEach(item => {
                addNewRow(item);
            });
        }

        // Add new row to the table
        function addNewRow(itemData = null) {
            const tbody = document.getElementById('invoice-table-body');
            const rowId = `row-${++rowCounter}`;

            const newRow = document.createElement('tr');
            newRow.id = rowId;
            newRow.className = 'border-b border-gray-200';
            newRow.innerHTML = `
                                <td class="border-r border-gray-300 px-2 py-2 column-item">
                                    <input type="text" name="items[${rowCounter}][item]" required
                                        class="w-full px-2 py-2  outline-none border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 item-input" 
                                        value="${itemData ? (itemData.item || '') : ''}">
                                </td>
                                <td class="border-r border-gray-300 px-2 py-2 w-64 column-description">
                                    <textarea name="items[${rowCounter}][description]" rows="1"
                                        class="w-full px-2 py-2  outline-none border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 description-input">${itemData ? (itemData.description || '') : ''}</textarea>
                                </td>
                                <td class="border-r border-gray-300 px-2 py-2 text-center column-hs-code" style="display: none">
                                    <input type="text" name="items[${rowCounter}][hs_code]"
                                        class="w-full px-2 py-2 border border-gray-200 outline-none rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 hs-code-input"
                                        value="${itemData ? (itemData.hs_code || '') : ''}">
                                </td>
                                <td class="border-r border-gray-300 px-2 py-2 text-center column-qty">
                                    <input type="number" name="items[${rowCounter}][quantity]" required min="0" step="1"
                                        class="w-full px-2 py-2 outline-none border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 quantity-input" 
                                        value="${itemData ? (itemData.quantity || 0) : '1'}">
                                </td>
                                <td class="border-r border-gray-300 px-2 py-2 text-center column-kg" style="display: none">
                                    <input type="number" name="items[${rowCounter}][kg]" min="0" step="1"
                                        class="w-full px-2 py-2 outline-none border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 kg-input" 
                                        value="${itemData ? (itemData.kg || 0) : '0'}">
                                </td>
                                <td class="border-r border-gray-300 px-2 py-2 text-right column-price">
                                    <input type="number" name="items[${rowCounter}][price]" required min="0" step="0.01"
                                        class="w-full px-2 py-2 outline-none border border-gray-200 rounded text-sm text-right focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 price-input" 
                                        value="${itemData ? parseFloat(itemData.price).toFixed(2) : '0.00'}">
                                </td>
                                <td class="border-r border-gray-300 px-2 py-2 column-account">
                                    <select name="items[${rowCounter}][account_id]"
                                        class=" select2 w-full px-2 py-2 outline-none border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 account-select">
                                        <option value="">Select Account</option>
                                    </select>
                                </td>
                                <td class="border-r border-gray-300 px-2 py-2 text-right column-tax">
                                    <select name="items[${rowCounter}][tax_id]"
                                        class="select2 w-full px-2 py-2 outline-none border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 tax-select">
                                        <option value="">No Tax</option>
                                    </select>
                                </td>
                                <td class="border-r border-gray-300 px-2 py-2 text-right column-discount" style="display: none">
                                    <input type="number" name="items[${rowCounter}][discount]" min="0" max="100" step="0.01"
                                        class="w-full px-2 py-2 outline-none border border-gray-200 rounded text-sm text-right focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 discount-input" 
                                        value="${itemData ? (itemData.discount || 0) : '0.00'}">
                                </td>
                                <td class="px-2 py-2 text-right column-total">
                                    <input type="number" name="items[${rowCounter}][total]" readonly
                                        class="w-full px-2 py-2 border border-gray-200 outline-none rounded text-sm text-right bg-gray-100 total-input" 
                                        value="${itemData ? parseFloat(itemData.total).toFixed(2) : '0.00'}">
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <button type="button" class="text-red-600 hover:text-red-800 delete-row" onclick="deleteRow('${rowId}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" />
                                        </svg>
                                    </button>
                                </td>
                            `;

            tbody.appendChild(newRow);

            applyCurrentColumnVisibility();

            // Populate account dropdown
            const accountSelect = newRow.querySelector('.account-select');
            accounts.forEach(account => {
                const option = document.createElement('option');
                option.value = account.id;
                option.textContent = `${account.account_number} - ${account.name}`;
                if (itemData && itemData.account_id == account.id) {
                    option.selected = true;
                }
                accountSelect.appendChild(option);
            });

            // Populate tax dropdown
            const taxSelect = newRow.querySelector('.tax-select');
            taxes.forEach(tax => {
                const option = document.createElement('option');
                option.value = tax.id;
                option.textContent = `${tax.name} (${tax.tax_rate}%)`;
                if (itemData && itemData.tax_id == tax.id) {
                    option.selected = true;
                }
                taxSelect.appendChild(option);
            });

            // Initialize select2
            $(newRow).find('.select2').select2({
                width: '100%'
            });

            // Add event listeners for calculations
            const quantityInput = newRow.querySelector('.quantity-input');
            const kgInput = newRow.querySelector('.kg-input');
            const priceInput = newRow.querySelector('.price-input');
            const taxSelectCalc = newRow.querySelector('.tax-select');
            const discountInput = newRow.querySelector('.discount-input');

            [quantityInput, kgInput, priceInput, taxSelectCalc, discountInput].forEach(element => {
                element.addEventListener('input', calculateRowTotal);
                element.addEventListener('change', calculateRowTotal);
            });

            // For select2, we also need to listen for change via jQuery
            $(taxSelectCalc).on('change', calculateRowTotal);

            setupRowLogic(newRow);
            setupAccountDefaultTax(newRow);

            // Re-apply locking if from existing data
            if (itemData) {
                if (parseFloat(itemData.quantity) > 0) {
                    quantityInput.dispatchEvent(new Event('input'));
                } else if (parseFloat(itemData.kg) > 0) {
                    kgInput.dispatchEvent(new Event('input'));
                }
            }

            calculateRowTotal.call(newRow);
        }

        // Delete row
        function deleteRow(rowId) {
            const tbody = document.getElementById('invoice-table-body');
            const row = document.getElementById(rowId);

            if (tbody.children.length > 1) {
                tbody.removeChild(row);
                calculateTotals();
            } else {
                Notification.error('You need at least one row in the table.');
            }
        }

        // Calculate row total
        function calculateRowTotal() {
            const row = this.closest('tr');
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const kg = parseFloat(row.querySelector('.kg-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
            const taxSelect = row.querySelector('.tax-select');
            const totalInput = row.querySelector('.total-input');

            // Calculate subtotal based on either qty or kg (whichever is greater than 0)
            let subtotal = 0;
            if (kg > 0) {
                subtotal = kg * price;
            } else if (quantity > 0) {
                subtotal = quantity * price;
            }

            // Calculate discount amount (percentage based)
            let discountAmount = 0;
            if (discount > 0) {
                discountAmount = (subtotal * discount) / 100;
            }

            // Calculate amount after discount
            let amountAfterDiscount = subtotal - discountAmount;

            // Calculate tax if selected (tax is applied before discount - Gross Method)
            let taxAmount = 0;
            if (taxSelect.value) {
                const selectedTax = taxes.find(tax => tax.id == taxSelect.value);
                if (selectedTax) {
                    taxAmount = (subtotal * selectedTax.tax_rate) / 100;
                }
            }

            // Calculate final total
            const total = amountAfterDiscount + taxAmount;
            totalInput.value = total.toFixed(2);

            calculateTotals();
        }

        // Calculate all totals
        function calculateTotals() {
            let subTotal = 0;
            let taxTotal = 0;
            let discountTotal = 0;
            let grandTotal = 0;

            document.querySelectorAll('#invoice-table-body tr').forEach(row => {
                const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
                const kg = parseFloat(row.querySelector('.kg-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
                const taxSelect = row.querySelector('.tax-select');

                let rowSubtotal = 0;
                if (kg > 0) {
                    rowSubtotal = kg * price;
                } else if (quantity > 0) {
                    rowSubtotal = quantity * price;
                }
                subTotal += rowSubtotal;

                // Calculate discount for this row
                let rowDiscount = 0;
                if (discount > 0) {
                    rowDiscount = (rowSubtotal * discount) / 100;
                }
                discountTotal += rowDiscount;

                // Calculate amount after discount
                const amountAfterDiscount = rowSubtotal - rowDiscount;

                // Calculate tax for this row (tax is applied before discount - Gross Method)
                if (taxSelect.value) {
                    const selectedTax = taxes.find(tax => tax.id == taxSelect.value);
                    if (selectedTax) {
                        taxTotal += (amountAfterDiscount * selectedTax.tax_rate) / 100;
                    }
                }
            });

            grandTotal = subTotal - discountTotal + taxTotal;

            // Update display
            document.getElementById('sub-total').textContent = subTotal.toFixed(2);
            document.getElementById('tax-total').textContent = taxTotal.toFixed(2);
            document.getElementById('discount-total').textContent = discountTotal.toFixed(2);
            document.getElementById('grand-total').textContent = grandTotal.toFixed(2);

            calculateRemainingAmount();
        }

        function setupRowLogic(row) {
            const qtyInput = row.querySelector('.quantity-input');
            const kgInput = row.querySelector('.kg-input');

            // When Qty is entered, lock Kg
            qtyInput.addEventListener('input', function() {
                const qtyValue = parseFloat(this.value) || 0;
                if (qtyValue > 0) {
                    kgInput.value = '0';
                    kgInput.readOnly = true;
                    kgInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                } else {
                    kgInput.readOnly = false;
                    kgInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
            });

            // When Kg is entered, lock Qty
            kgInput.addEventListener('input', function() {
                const kgValue = parseFloat(this.value) || 0;
                if (kgValue > 0) {
                    qtyInput.value = '0';
                    qtyInput.readOnly = true;
                    qtyInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                } else {
                    qtyInput.readOnly = false;
                    qtyInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
            });
        }

        // Calculate remaining amount
        function calculateRemainingAmount() {
            const grandTotal = parseFloat(document.getElementById('grand-total').textContent) || 0;
            const paidAmountInput = document.getElementById('paid_amount');
            const paidAmount = parseFloat(paidAmountInput.value) || 0;

            if (paidAmount > grandTotal) {
                paidAmountInput.value = grandTotal.toFixed(2);
                Notification.error('Paid amount cannot be greater than the total amount!');
            }

            const remainingAmount = grandTotal - (parseFloat(paidAmountInput.value) || 0);
            document.getElementById('remaining_amount').value = remainingAmount.toFixed(2);
        }

        function setupPaymentValidation() {
            const paidAmountInput = document.getElementById('paid_amount');
            const paymentTypeSelect = document.getElementById('payment_type');
            const paymentTypeAsterisk = document.getElementById('payment_type_asterisk');
            const paymentTypeError = document.getElementById('payment_type_error');

            function togglePaymentTypeRequired() {
                const paidAmount = parseFloat(paidAmountInput.value) || 0;
                const paymentType = $(paymentTypeSelect).val();

                if (paidAmount > 0) {
                    paymentTypeSelect.setAttribute('required', 'required');
                    paymentTypeAsterisk.classList.remove('hidden');

                    // ✅ Only show error if nothing selected
                    if (!paymentType || paymentType === "") {
                        paymentTypeError.classList.remove('hidden');
                    } else {
                        paymentTypeError.classList.add('hidden');
                    }
                } else {
                    paymentTypeSelect.removeAttribute('required');
                    paymentTypeAsterisk.classList.add('hidden');
                    paymentTypeError.classList.add('hidden');
                }
            }

            window.togglePaymentTypeRequired = togglePaymentTypeRequired;

            paidAmountInput.addEventListener('blur', function() {
                calculateRemainingAmount();
                togglePaymentTypeRequired();
            });

            paymentTypeSelect.addEventListener('change', togglePaymentTypeRequired);
            $(paymentTypeSelect).on('select2:select', togglePaymentTypeRequired);
            $(paymentTypeSelect).on('select2:unselect', togglePaymentTypeRequired);

            togglePaymentTypeRequired();
        }

        // Load customer sales
        function loadCustomerSales(customerId) {
            if (!customerId) {
                document.getElementById('sale_id').innerHTML = '<option value="">Select Sales</option>';
                return;
            }

            showLoading();
            const baseUrl =
                '{{ route('account.sale.credit-notes.get-customer-sales', ['customerId' => 'CUSTOMER_ID_PLACEHOLDER']) }}';
            const finalUrl = baseUrl.replace('CUSTOMER_ID_PLACEHOLDER', customerId);

            fetch(finalUrl)
                .then(response => response.json())
                .then(sales => {
                    const saleSelect = document.getElementById('sale_id');
                    saleSelect.innerHTML = '<option value="">Select Sales</option>';

                    sales.forEach(sale => {
                        const option = document.createElement('option');
                        option.value = sale.id;
                        option.textContent =
                            `${sale.unique_no} - ${sale.invoice_no} (${parseFloat(sale.total).toFixed(2)})`;
                        if (sale.id == '{{ $creditNote->sale_id }}') option.selected = true;
                        saleSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading sales:', error);
                    Notification.error('Error loading sales data');
                })
                .finally(() => {
                    hideLoading();
                });
        }

        // Load sale details
        function loadSaleDetails(saleId) {
            showLoading();
            fetch(`/credit-notes/get-sale-details/${saleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate header fields
                        const sale = data.sale;
                        if (sale) {
                            if (sale.account_id) {
                                $('select[name="payment_type"]').val(sale.account_id).trigger('change');
                            }
                            if (sale.currency_id) {
                                $('#currency_id').val(sale.currency_id).trigger('change');
                            }
                            if (sale.total_after_discount) {
                                $('#paid_amount').val(parseFloat(sale.total_after_discount).toFixed(2));
                            }
                        }

                        populateItemsFromSale(data.items);
                    } else {
                        Notification.error('Error loading sale details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading sale details:', error);
                    Notification.error('Error loading sale details');
                })
                .finally(() => {
                    hideLoading();
                });
        }

        // Populate items from sale
        function populateItemsFromSale(saleItems) {
            if (!saleItems || !Array.isArray(saleItems)) {
                console.error('Invalid sale items data:', saleItems);
                Notification.error('Invalid sale items data received');
                return;
            }

            // Clear existing rows
            const tbody = document.getElementById('invoice-table-body');
            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            rowCounter = 0;

            // Add rows for each sale item
            saleItems.forEach((item, index) => {
                addNewRow({
                    item: item.item,
                    description: item.description,
                    hs_code: item.hs_code,
                    quantity: item.quantity,
                    kg: item.kg,
                    price: item.price,
                    account_id: item.account_id,
                    tax_id: item.tax_id,
                    discount: item.discount || 0
                });
            });

            calculateTotals();
        }

        // Show loading overlay
        function showLoading() {
            document.getElementById('page-loader').classList.remove('hidden');
        }

        // Hide loading overlay
        function hideLoading() {
            document.getElementById('page-loader').classList.add('hidden');
        }

        // Column visibility functions
        function toggleColumnVisibility(column, show) {
            const cells = document.querySelectorAll(`.column-${column}`);
            cells.forEach(cell => cell.style.display = show ? '' : 'none');
            const checkbox = document.querySelector(`.column-toggle[data-column="${column}"]`);
            if (checkbox) checkbox.checked = show;
        }

        function saveColumnVisibility() {
            const visibility = {};
            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                visibility[checkbox.dataset.column] = checkbox.checked;
            });
            localStorage.setItem('creditNoteColumnVisibility', JSON.stringify(visibility));
        }

        function applyCurrentColumnVisibility() {
            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                toggleColumnVisibility(checkbox.dataset.column, checkbox.checked);
            });
        }

        function loadColumnVisibility() {
            const savedVisibility = localStorage.getItem('creditNoteColumnVisibility');
            if (savedVisibility) {
                const visibility = JSON.parse(savedVisibility);
                for (const column in visibility) {
                    toggleColumnVisibility(column, visibility[column]);
                }
            } else {
                // Default visibility: Hide HS Code, Kg, and Discount
                ['hs-code', 'kg', 'discount'].forEach(col => {
                    toggleColumnVisibility(col, false);
                });
            }

            // Set up column toggle event listeners
            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleColumnVisibility(this.dataset.column, this.checked);
                    saveColumnVisibility();
                });
            });
        }
    </script>
@endpush
