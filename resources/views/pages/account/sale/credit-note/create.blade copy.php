@extends('layouts.auth')

@section('title', 'Create Credit Note')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active'] . '*') }}" />
                @endforeach
            </div>
        </div>
        <div class="py-8">
            <div class="mx-auto sm:px-6 lg:px-8">
                <div class="bg-white border border-gray-300 rounded-md">

                    <!-- Header -->
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Create Credit Note</h2>
                        <a href="{{ route('account.sale.credit-notes.index') }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded hover:bg-[#006bb0] transition">
                            BACK
                        </a>
                    </div>

                    <!-- Form -->
                    <form id="formSubmit" back_url="{{ route('account.sale.credit-notes.index') }}"
                        action_url="{{ route('account.sale.credit-notes.store') }}" method="POST" class="p-4">
                        @csrf

                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Ref No</label>
                                <input type="text" name="ref_no"
                                    class="w-full h-9 border border-gray-300 outline-none rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                    placeholder="Enter reference number">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Invoice No</label>
                                <input type="text" name="invoice_no"
                                    class="w-full h-9 border border-gray-300 rounded outline-none text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                    placeholder="Enter invoice number">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Select Customer *</label>
                                <select name="customer_id" id="customer_id" required
                                    class="select2 w-full h-9 border border-gray-300 rounded text-sm outline-none focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                    <option value="">Select Customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Select Sales</label>
                                <select name="sale_id" id="sale_id"
                                    class=" select2 w-full h-9 border border-gray-300 rounded text-sm outline-none focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                    <option value="">Select Sales</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Date *</label>
                                <input type="date" name="date" required
                                    class="w-full h-9 border border-gray-300 outline-none rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                    value="{{ date('Y-m-d') }}">
                            </div>

                        </div>

                        <!-- Table -->
                        <div class="border border-gray-300 rounded mb-4">
                            <table id="invoice-table" class="w-full text-sm border-collapse">
                                <thead class="bg-gray-50 border-b border-gray-300 text-gray-700">
                                    <tr>
                                        <th class="border-r border-gray-300 px-3 py-2 text-left w-24 column-item">Item *
                                        </th>
                                        <th class="border-r border-gray-300 px-3 py-2 text-left column-description">
                                            Description</th>
                                        <th class="border-r border-gray-300 px-3 py-2 text-center w-20 column-hs-code">HS
                                            Code</th>
                                        <th class="border-r border-gray-300 px-3 py-2 text-center w-20 column-qty">Qty *
                                        </th>
                                        <th class="border-r border-gray-300 px-3 py-2 text-center w-20 column-kg">Kg</th>
                                        <th class="border-r border-gray-300 px-3 py-2 text-right w-24 column-price">Price *
                                        </th>
                                        <th class="border-r border-gray-300 px-3 py-2 text-left w-32 column-account">Account
                                        </th>
                                        <th class="border-r border-gray-300 px-3 py-2 text-right w-20 column-tax">Tax</th>
                                        <th class="px-3 py-2 text-right w-28 column-total">Total</th>
                                        <th class="px-2 py-2 w-8 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="invoice-table-body">
                                    <!-- Rows will be added dynamically -->
                                </tbody>
                            </table>
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
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="hs-code"
                                                        checked> HS Code
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
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="kg"
                                                        checked> Kg
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="price"
                                                        checked> Price
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center px-3 py-1 hover:bg-gray-100 cursor-pointer">
                                                    <input type="checkbox" class="column-toggle mr-2" data-column="account"
                                                        checked> Account
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
                                <div class="text-right font-medium" id="sub-total">0.00</div>
                                <div class="text-right">Tax:</div>
                                <div class="text-right font-medium" id="tax-total">0.00</div>
                                <div class="text-right font-semibold">Total:</div>
                                <div class="text-right font-semibold" id="grand-total">0.00</div>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            {{-- <div> --}}
                            {{-- <label class="block text-sm text-gray-600 mb-1">Type of Payment *</label>
                                <select name="payment_type" required
                                    class="w-full h-9 border border-[#0078C8] outline-none rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                    <option value="">Select Payment Type</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="credit_card">Credit Card</option>
                                </select> --}}
                            {{-- </div> --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Type of Payment</label>
                                <x-acccount.coa.coa-select name="payment_type" />
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Select Currency *</label>
                                <select name="currency_id" id="currency_id" required
                                    class="select2 w-full h-9 border border-gray-300 rounded text-sm outline-none focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1">
                                    <option value="">Select Currency</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Paid Amount *</label>
                                <input type="number" name="paid_amount" id="paid_amount" required step="0.01"
                                    min="0"
                                    class="w-full h-9 border border-gray-300 outline-none rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1"
                                    value="0.00">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Remaining Amount</label>
                                <input type="number" id="remaining_amount" name="remaining_amount" value="0.00"
                                    step="0.01" readonly
                                    class="w-full h-9 border border-gray-300 outline-none rounded text-sm bg-gray-100">
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex justify-end">
                            <button type="submit" id="submit-btn"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded hover:bg-[#006bb0] transition flex items-center">
                                <span id="submit-text">SAVE & APPROVE</span>
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
    <div id="loading-overlay" class="hidden fixed inset-0 bg-gray-600/50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#0078C8] mx-auto"></div>
            <p class="text-white text-center mt-4">Loading...</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Global variables
        let accounts = [];
        let taxes = [];
        let rowCounter = 0;

        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            loadDropdownData();
            setupEventListeners();
        });

        // Initialize form
        function initializeForm() {
            addNewRow();
            loadColumnVisibility();
            calculateTotals();
        }

        // Load dropdown data via AJAX
        function loadDropdownData() {
            // Load accounts
            fetch('{{ route('account.sale.credit-notes.get-accounts') }}')
                .then(response => response.json())
                .then(data => {
                    accounts = data;
                });

            // Load taxes
            fetch('{{ route('account.sale.credit-notes.get-taxes') }}')
                .then(response => response.json())
                .then(data => {
                    taxes = data;
                });
        }

        // Setup event listeners
        function setupEventListeners() {
            // Customer change event
            // document.getElementById('customer_id').addEventListener('change', function() {
            //     loadCustomerSales(this.value);
            // });

            $('#customer_id').on('change', function() {
                loadCustomerSales($(this).val());
            });

            // Sale change event
            // document.getElementById('sale_id').addEventListener('change', function() {
            //     if (this.value) {
            //         loadSaleDetails(this.value);
            //     }
            // });
            $('#sale_id').on('change', function() {
                if ($(this).val()) {
                    loadSaleDetails($(this).val());
                }
            });


            // Paid amount change event
            document.getElementById('paid_amount').addEventListener('input', function() {
                calculateRemainingAmount();
            });

            // Add new row button
            document.getElementById('add-new-line').addEventListener('click', addNewRow);

            // Form submission
            // document.getElementById('creditNoteForm').addEventListener('submit', submitForm);

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

        // Add new row to the table
        function addNewRow() {
            const tbody = document.getElementById('invoice-table-body');
            const rowId = `row-${++rowCounter}`;

            const newRow = document.createElement('tr');
            newRow.id = rowId;
            newRow.className = 'border-b border-gray-200';
            newRow.innerHTML = `
            <td class="border-r border-gray-300 px-2 py-2 column-item">
                <input type="text" name="items[${rowCounter}][item]" required
                    class="w-full h-8 outline-none border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 item-input">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 column-description">
                <textarea name="items[${rowCounter}][description]" rows="1"
                    class="w-full h-8 outline-none border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 description-input"></textarea>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-hs-code">
                <input type="text" name="items[${rowCounter}][hs_code]"
                    class="w-full h-8 border border-gray-200 outline-none rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 hs-code-input">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-qty">
                <input type="number" name="items[${rowCounter}][quantity]" required min="1" step="1"
                    class="w-full h-8 outline-none border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 quantity-input" value="1">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-kg">
                <input type="number" name="items[${rowCounter}][kg]" min="0" step="1"
                    class="w-full h-8 outline-none border border-gray-200 rounded text-sm text-center focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 kg-input" value="0">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-price">
                <input type="number" name="items[${rowCounter}][price]" required min="0" step="0.01"
                    class="w-full h-8 outline-none border border-gray-200 rounded text-sm text-right focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 price-input" value="0.00">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 column-account">
                <select name="items[${rowCounter}][account_id]"
                    class=" select2 w-full h-8 outline-none border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 account-select">
                    <option value="">Select Account</option>
                </select>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-tax">
                <select name="items[${rowCounter}][tax_id]"
                    class="select2 w-full h-8 outline-none border border-gray-200 rounded text-sm focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 tax-select">
                    <option value="">No Tax</option>
                </select>
            </td>
            <td class="px-2 py-2 text-right column-total">
                <input type="number" name="items[${rowCounter}][total]" readonly
                    class="w-full h-8 border border-gray-200 outline-none rounded text-sm text-right bg-gray-100 total-input" value="0.00">
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

            // Populate account dropdown
            const accountSelect = newRow.querySelector('.account-select');
            accounts.forEach(account => {
                const option = document.createElement('option');
                option.value = account.id;
                option.textContent = `${account.account_number} - ${account.name}`;
                accountSelect.appendChild(option);
            });

            // Populate tax dropdown
            const taxSelect = newRow.querySelector('.tax-select');
            taxes.forEach(tax => {
                const option = document.createElement('option');
                option.value = tax.id;
                option.textContent = `${tax.name} (${tax.rate}%)`;
                taxSelect.appendChild(option);
            });

            // Add event listeners for calculations
            const quantityInput = newRow.querySelector('.quantity-input');
            const priceInput = newRow.querySelector('.price-input');
            const taxSelectCalc = newRow.querySelector('.tax-select');

            [quantityInput, priceInput, taxSelectCalc].forEach(element => {
                element.addEventListener('input', calculateRowTotal);
                element.addEventListener('change', calculateRowTotal);
            });

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
                showErrorNotification('You need at least one row in the table.');
            }
        }

        // Calculate row total
        function calculateRowTotal() {
            const row = this.closest('tr');
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const taxSelect = row.querySelector('.tax-select');
            const totalInput = row.querySelector('.total-input');

            let subtotal = quantity * price;
            let taxAmount = 0;

            // Calculate tax if selected
            if (taxSelect.value) {
                const selectedTax = taxes.find(tax => tax.id == taxSelect.value);
                if (selectedTax) {
                    taxAmount = (subtotal * selectedTax.tax_rate) / 100;
                }
            }

            // const total = subtotal + taxAmount;
            const total = subtotal;
            totalInput.value = total.toFixed(2);

            calculateTotals();
        }

        // Calculate all totals
        function calculateTotals() {
            let subTotal = 0;
            let taxTotal = 0;
            let grandTotal = 0;

            document.querySelectorAll('#invoice-table-body tr').forEach(row => {
                const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const taxSelect = row.querySelector('.tax-select');

                const rowSubtotal = quantity * price;
                subTotal += rowSubtotal;

                // Calculate tax for this row
                if (taxSelect.value) {
                    const selectedTax = taxes.find(tax => tax.id == taxSelect.value);
                    if (selectedTax) {
                        taxTotal += (rowSubtotal * selectedTax.tax_rate) / 100;
                    }
                }
            });

            grandTotal = subTotal + taxTotal;

            // Update display
            document.getElementById('sub-total').textContent = subTotal.toFixed(2);
            document.getElementById('tax-total').textContent = taxTotal.toFixed(2);
            document.getElementById('grand-total').textContent = grandTotal.toFixed(2);

            calculateRemainingAmount();
        }

        // Calculate remaining amount
        function calculateRemainingAmount() {
            const grandTotal = parseFloat(document.getElementById('grand-total').textContent) || 0;
            const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
            const remainingAmount = grandTotal - paidAmount;

            document.getElementById('remaining_amount').value = remainingAmount.toFixed(2);
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
                        saleSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading sales:', error);
                    showErrorNotification('Error loading sales data');
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
                    console.log(data);
                    if (data.success) {
                        populateItemsFromSale(data.items);
                    } else {
                        showErrorNotification('Error loading sale details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading sale details:', error);
                    showErrorNotification('Error loading sale details');
                })
                .finally(() => {
                    hideLoading();
                });
        }

        // Populate items from sale
        function populateItemsFromSale(saleItems) {
            // console.log('Populating items:', saleItems); // Debug log

            if (!saleItems || !Array.isArray(saleItems)) {
                console.error('Invalid sale items data:', saleItems);
                showErrorNotification('Invalid sale items data received');
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
                // console.log('Processing item:', item, 'Index:', index); // Debug log
                addNewRow();
                const lastRow = tbody.lastChild;

                if (lastRow) {
                    lastRow.querySelector('.item-input').value = item.item || '';
                    lastRow.querySelector('.description-input').value = item.description || '';
                    lastRow.querySelector('.hs-code-input').value = item.hs_code || '';
                    lastRow.querySelector('.quantity-input').value = item.quantity || 1;
                    lastRow.querySelector('.kg-input').value = item.kg || 0;
                    lastRow.querySelector('.price-input').value = item.price || 0;

                    if (item.account_id) {
                        lastRow.querySelector('.account-select').value = item.account_id;
                    }
                    if (item.tax_id) {
                        lastRow.querySelector('.tax-select').value = item.tax_id;
                    }

                    calculateRowTotal.call(lastRow);
                }
            });

            calculateTotals();
            console.log('Finished populating items'); // Debug log
        }

        // Submit form
        // function submitForm(e) {
        //     e.preventDefault();

        //     if (!validateForm()) {
        //         return;
        //     }

        //     const submitBtn = document.getElementById('submit-btn');
        //     const submitText = document.getElementById('submit-text');
        //     const loadingSpinner = document.getElementById('loading-spinner');

        //     // Show loading state
        //     submitBtn.disabled = true;
        //     submitText.textContent = 'Processing...';
        //     loadingSpinner.classList.remove('hidden');

        //     // Prepare form data
        //     const formData = new FormData(document.getElementById('creditNoteForm'));

        //     // Send AJAX request
        //     fetch('{{ route('account.sale.credit-notes.store') }}', {
        //             method: 'POST',
        //             headers: {
        //                 'X-Requested-With': 'XMLHttpRequest',
        //                 'X-CSRF-TOKEN': '{{ csrf_token() }}'
        //             },
        //             body: formData
        //         })
        //         .then(response => response.json())
        //         .then(data => {
        //             if (data.success) {
        //                 alert(data.message);
        //                 if (data.redirect_url) {
        //                     window.location.href = data.redirect_url;
        //                 } else {
        //                     window.location.reload();
        //                 }
        //             } else {
        //                 alert(data.message);
        //                 if (data.errors) {
        //                     displayValidationErrors(data.errors);
        //                 }
        //             }
        //         })
        //         .catch(error => {
        //             console.error('Error:', error);
        //             alert('An error occurred while creating the credit note.');
        //         })
        //         .finally(() => {
        //             // Reset button state
        //             submitBtn.disabled = false;
        //             submitText.textContent = 'SAVE & APPROVE';
        //             loadingSpinner.classList.add('hidden');
        //         });
        // }

        // Validate form
        function validateForm() {
            const customerId = document.getElementById('customer_id').value;
            const date = document.querySelector('input[name="date"]').value;
            const currencyId = document.getElementById('currency_id').value;
            const paymentType = document.querySelector('select[name="payment_type"]').value;
            const paidAmount = document.getElementById('paid_amount').value;

            if (!customerId) {
                showErrorNotification('Please select a customer');
                return false;
            }

            if (!date) {
                showErrorNotification('Please select a date');
                return false;
            }

            if (!currencyId) {
                showErrorNotification('Please select a currency');
                return false;
            }

            if (!paymentType) {
                showErrorNotification('Please select a payment type');
                return false;
            }

            if (!paidAmount || parseFloat(paidAmount) < 0) {
                showErrorNotification('Please enter a valid paid amount');
                return false;
            }

            // Validate items
            const items = document.querySelectorAll('#invoice-table-body tr');
            if (items.length === 0) {
                showErrorNotification('Please add at least one item');
                return false;
            }

            let hasErrors = false;
            items.forEach((row, index) => {
                const item = row.querySelector('.item-input').value;
                const quantity = row.querySelector('.quantity-input').value;
                const price = row.querySelector('.price-input').value;

                if (!item) {
                    showErrorNotification(`Please enter item name for row ${index + 1}`);
                    hasErrors = true;
                }

                if (!quantity || parseFloat(quantity) <= 0) {
                    showErrorNotification(`Please enter a valid quantity for row ${index + 1}`);
                    hasErrors = true;
                }

                if (!price || parseFloat(price) < 0) {
                    showErrorNotification(`Please enter a valid price for row ${index + 1}`);
                    hasErrors = true;
                }
            });

            return !hasErrors;
        }

        // Display validation errors
        function displayValidationErrors(errors) {
            let errorMessage = 'Please fix the following errors:\n';
            for (const field in errors) {
                errorMessage += `- ${errors[field][0]}\n`;
            }
            showErrorNotification(errorMessage);
        }

        // Show loading overlay
        function showLoading() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        }

        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }

        // Column visibility functions (same as before)
        function toggleColumnVisibility(column, show) {
            const cells = document.querySelectorAll(`.column-${column}`);
            cells.forEach(cell => cell.style.display = show ? '' : 'none');
            document.querySelector(`.column-toggle[data-column="${column}"]`).checked = show;
        }

        function saveColumnVisibility() {
            const visibility = {};
            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                visibility[checkbox.dataset.column] = checkbox.checked;
            });
            localStorage.setItem('creditNoteColumnVisibility', JSON.stringify(visibility));
        }

        function loadColumnVisibility() {
            const savedVisibility = localStorage.getItem('creditNoteColumnVisibility');
            if (savedVisibility) {
                const visibility = JSON.parse(savedVisibility);
                for (const column in visibility) {
                    toggleColumnVisibility(column, visibility[column]);
                }
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
