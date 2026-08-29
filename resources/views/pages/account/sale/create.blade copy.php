@extends('layouts.auth')

@section('title', 'Create Sale')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="p-2">
            <div class="mx-auto ">
                <div class="bg-white border border-gray-300 rounded-md">
                    <!-- Header -->
                    <div class="p-5 border-b border-gray-200 flex rounded-md justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Create Customer Invoice</h2>
                        <a href="{{ route('account.sale-invoice.index') }}"
                            class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all hover:shadow-lg">
                            ← BACK
                        </a>
                    </div>
                </div>
                <div class="bg-white border border-gray-300 rounded-lg my-4">
                    <!-- Form -->
                    <form action_url="{{ route('account.sale-invoice.store') }}"
                        back_url="{{ route('account.sale-invoice.index') }}" method="POST" enctype="multipart/form-data"
                        id="formSubmit" class="p-6">
                        @csrf
                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

                            <x-acccount.create-customer :customers="$customers" />

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Invoice No <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="invoice_no" required placeholder="Enter invoice number"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="date" required value="{{ date('Y-m-d') }}"
                                    class="date w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ref No</label>
                                <input type="text" name="ref_no" placeholder="Reference number"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Attachment</label>
                                <input type="file" name="attachment[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                    class="w-full h-10 border border-gray-300 outline-none rounded-md text-sm file:mr-3 file:py-2 file:px-4 file:border-0 file:bg-[#0078C8] file:text-white file:text-xs file:font-medium file:rounded-md file:cursor-pointer hover:file:bg-[#006bb0] transition-all"
                                    multiple>
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6">
                            <div class="overflow-x-auto">
                                <table id="invoice-table" class="w-full text-sm border-collapse">
                                    <thead class="border-b-2 border-gray-300 text-gray-700">
                                        <tr>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-24 column-item font-semibold">
                                                Item</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left column-description font-semibold">
                                                Description</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-center w-20 column-hs-code font-semibold">
                                                HS Code</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-center w-20 column-qty font-semibold">
                                                Qty</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-center w-20 column-kg font-semibold">
                                                Kg</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-right w-24 column-price font-semibold">
                                                Price</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-32 column-account font-semibold">
                                                Account</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-right w-20 column-tax font-semibold">
                                                Tax</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-right w-28 column-total font-semibold">
                                                Total</th>
                                            <th class="px-2 py-3 w-12 text-center font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-table-body" class="bg-white">
                                        <!-- Rows will be added dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Buttons and Totals -->
                        <div class="grid grid-cols-1 md:grid-cols-2 mb-6 gap-4">
                            <div>
                                <div class="flex gap-3">
                                    <button type="button" id="add-new-line"
                                        class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all hover:shadow-lg flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        ADD NEW LINE
                                    </button>

                                    <!-- Column Dropdown -->
                                    <div class="relative">
                                        <button type="button" onclick="toggleDropdown('columnDropdown')"
                                            class="px-5 py-2 border-2 border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#0078C8] hover:text-white transition-all flex items-center gap-2">

                                            COLUMNS ▼
                                        </button>
                                        <div id="columnDropdown"
                                            class="hidden absolute left-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-20 overflow-hidden">
                                            <div class="py-2">
                                                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                                    <span class="text-xs font-semibold text-gray-600 uppercase">Show/Hide
                                                        Columns</span>
                                                </div>
                                                <div class="max-h-80 overflow-y-auto">
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="item" checked>
                                                        <span class="text-sm text-gray-700">Item</span>
                                                    </label>
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="description" checked>
                                                        <span class="text-sm text-gray-700">Description</span>
                                                    </label>
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="hs-code" checked>
                                                        <span class="text-sm text-gray-700">HS Code</span>
                                                    </label>
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="qty" checked>
                                                        <span class="text-sm text-gray-700">Qty</span>
                                                    </label>
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="kg" checked>
                                                        <span class="text-sm text-gray-700">Kg</span>
                                                    </label>
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="price" checked>
                                                        <span class="text-sm text-gray-700">Price</span>
                                                    </label>
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="account" checked>
                                                        <span class="text-sm text-gray-700">Account</span>
                                                    </label>
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="tax" checked>
                                                        <span class="text-sm text-gray-700">Tax</span>
                                                    </label>
                                                    <label
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="total" checked>
                                                        <span class="text-sm text-gray-700">Total</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div>
                                        <label for="note"
                                            class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                                        <textarea placeholder="Enter Note" name="note" rows="4"
                                            class="w-full px-2 border border-gray-300 py-2 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="border-gray-200 rounded-lg p-4 w-full">
                                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                                    <div class="text-left text-gray-600 font-medium">Sub Total:</div>
                                    <div id="subtotal" class="text-left font-semibold text-gray-800">0.00</div>
                                    <div class="text-left text-gray-600 font-medium">Tax:</div>
                                    <div id="tax-total" class="text-left font-semibold text-gray-800">0.00</div>
                                    <div class="text-left text-[#0078C8] font-semibold border-t border-gray-300 pt-2">
                                        Total:</div>
                                    <div id="grand-total"
                                        class="text-left font-bold text-[#0078C8] border-t border-gray-300 pt-2 text-lg">
                                        0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">Payment Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type of Payment</label>
                                    <x-acccount.coa.coa-select name="payment_type" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Currency <span
                                            class="text-red-500">*</span></label>
                                    <select name="currency_id" required
                                        class="select2 w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all bg-white">
                                        <option value="">-- Select Currency --</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}">{{ $currency->code }} -
                                                {{ $currency->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                                    <input type="number" name="paid_amount" id="paid_amount" step="0.01"
                                        placeholder="0.00"
                                        class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Remaining</label>
                                    <input type="number" id="remaining" name="remaining" value="0.00" step="0.01"
                                        placeholder="0.00" readonly
                                        class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex justify-end gap-3">
                            <button type="reset"
                                class="px-6 py-2 border-2 border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-100 transition-all">
                                RESET
                            </button>
                            <button type="submit"
                                class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                SAVE & APPROVE
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let rowIndex = 0;
        const accounts = @json($accounts);

        // Function to toggle dropdown visibility
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('columnDropdown');
            const button = document.querySelector('button[onclick="toggleDropdown(\'columnDropdown\')"]');

            if (dropdown && !dropdown.contains(event.target) && button && !button.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Initialize the table with one row
        document.addEventListener('DOMContentLoaded', function() {
            addNewRow();
            loadColumnVisibility();

            document.getElementById('add-new-line').addEventListener('click', addNewRow);

            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleColumnVisibility(this.dataset.column, this.checked);
                    saveColumnVisibility();
                });
            });

            // Setup paid amount validation
            setupPaymentValidation();
        });

        // Function to setup payment amount validation
        function setupPaymentValidation() {
            const paidAmountInput = document.getElementById('paid_amount');

            paidAmountInput.addEventListener('input', function() {
                const grandTotal = parseFloat(document.getElementById('grand-total').textContent) || 0;
                const paidAmount = parseFloat(this.value) || 0;

                if (paidAmount > grandTotal) {
                    this.value = grandTotal.toFixed(2);
                    showErrorNotification('Paid amount cannot be greater than the total amount!');
                }

                updateTotals();
            });

            // Also validate on blur
            paidAmountInput.addEventListener('blur', function() {
                const grandTotal = parseFloat(document.getElementById('grand-total').textContent) || 0;
                const paidAmount = parseFloat(this.value) || 0;

                if (paidAmount > grandTotal) {
                    this.value = grandTotal.toFixed(2);
                    updateTotals();
                }
            });
        }

        // Function to setup mutual exclusivity and calculation for a row
        function setupRowLogic(row) {
            const qtyInput = row.querySelector('.row-qty');
            const kgInput = row.querySelector('.row-kg');
            const priceInput = row.querySelector('.row-price');
            const totalInput = row.querySelector('.row-total');

            // Function to calculate row total
            function calculateRowTotal() {
                const qty = parseFloat(qtyInput.value) || 0;
                const kg = parseFloat(kgInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;

                // Calculate total based on either qty or kg (whichever is greater than 0)
                let total = 0;
                if (kg > 0) {
                    total = price;
                } else if (qty > 0) {
                    total = qty * price;
                }

                totalInput.value = total.toFixed(2);
                updateTotals();
            }

            // When Qty is entered, lock Kg
            qtyInput.addEventListener('input', function() {
                const qtyValue = parseFloat(this.value) || 0;

                if (qtyValue > 0) {
                    kgInput.value = '0';
                    kgInput.readonly = true;
                    kgInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                } else {
                    kgInput.readonly = false;
                    kgInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                calculateRowTotal();
            });

            // When Kg is entered, lock Qty
            kgInput.addEventListener('input', function() {
                const kgValue = parseFloat(this.value) || 0;

                if (kgValue > 0) {
                    qtyInput.value = '0';
                    qtyInput.readonly = true;
                    qtyInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                } else {
                    qtyInput.readonly = false;
                    qtyInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                calculateRowTotal();
            });

            // Price changes also trigger calculation
            priceInput.addEventListener('input', calculateRowTotal);

            // Add focus event to clear the locked field's value when trying to edit
            qtyInput.addEventListener('focus', function() {
                if (this.readonly) {
                    return;
                }

                const kgValue = parseFloat(kgInput.value) || 0;
                if (kgValue > 0) {
                    const confirmSwitch = confirm('Switching to Quantity will reset Kg value. Continue?');
                    if (confirmSwitch) {
                        kgInput.value = '0';
                        kgInput.readonly = false;
                        kgInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                        this.readonly = false;
                        this.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    } else {
                        this.blur();
                    }
                }
            });

            kgInput.addEventListener('focus', function() {
                if (this.readonly) {
                    return;
                }

                const qtyValue = parseFloat(qtyInput.value) || 0;
                if (qtyValue > 0) {
                    const confirmSwitch = confirm('Switching to Kg will reset Quantity value. Continue?');
                    if (confirmSwitch) {
                        qtyInput.value = '0';
                        qtyInput.readonly = false;
                        qtyInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                        this.readonly = false;
                        this.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    } else {
                        this.blur();
                    }
                }
            });
        }

        // Function to add a new row
        function addNewRow() {
            const tbody = document.getElementById('invoice-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'border-b border-gray-200 hover:bg-blue-50 transition-colors';
            newRow.dataset.rowIndex = rowIndex;

            newRow.innerHTML = `
            <td class="border-r border-gray-300 px-2 py-2 column-item">
                <input type="text" name="rows[${rowIndex}][item]" placeholder="Item name"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 column-description">
                <input type="text" name="rows[${rowIndex}][description]" placeholder="Description"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-hs-code">
                <input type="text" name="rows[${rowIndex}][hs_code]" placeholder="HS Code"
                    class="w-full h-9 px-2 border border-gray-200 outline-none rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-qty">
                <input type="number" name="rows[${rowIndex}][quantity]" min="0" step="1" value="1" placeholder="0"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-qty">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-kg">
                <input type="number" name="rows[${rowIndex}][kg]" min="0" step="0.01" value="0" placeholder="0"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-kg">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-price">
                <input type="number" name="rows[${rowIndex}][price]" min="0" step="0.01" value="0" placeholder="0.00"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-right focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-price">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 column-account">
                <select name="rows[${rowIndex}][account_id]"
                    class="select2 w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                    ${generateAccountOptions()}
                </select>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-tax">
                <select name="rows[${rowIndex}][tax_id]"
                    class="select2 w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                    <option value="">-- No Tax --</option>
                    @foreach ($taxes as $tax)
                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                    @endforeach
                </select>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-total">
                <input type="number" name="rows[${rowIndex}][total]" readonly value="0.00" placeholder="0.00"
                    class="w-full h-9 px-2 border border-gray-200 outline-none rounded-md text-sm text-right bg-gray-50 font-semibold text-[#0078C8] row-total">
            </td>
            <td class="px-2 py-2 text-center">
                <button type="button" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-md transition-all delete-row" title="Delete Row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" />
                    </svg>
                </button>
            </td>
        `;

            tbody.appendChild(newRow);
            rowIndex++;

            // Initialize select2
            $(newRow).find('.select2').select2({
                width: '100%'
            });

            // Setup row logic for mutual exclusivity and calculations
            setupRowLogic(newRow);

            // Delete row handler
            newRow.querySelector('.delete-row').addEventListener('click', function() {
                if (tbody.children.length > 1) {
                    newRow.remove();
                    updateTotals();
                } else {
                    showErrorNotification('You need at least one row in the table.');
                }
            });
        }

        // Update all totals
        function updateTotals() {
            let subtotal = 0;
            let taxTotal = 0;

            document.querySelectorAll('#invoice-table-body tr').forEach(row => {
                const total = parseFloat(row.querySelector('.row-total')?.value) || 0;
                subtotal += total;
                // Tax will be calculated on backend based on tax_id
            });

            const grandTotal = subtotal + taxTotal;
            const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;

            // Ensure paid amount doesn't exceed grand total
            const validPaidAmount = Math.min(paidAmount, grandTotal);
            const remainingAmount = grandTotal - validPaidAmount;

            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('tax-total').textContent = taxTotal.toFixed(2);
            document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
            document.getElementById('remaining').value = remainingAmount.toFixed(2);
        }

        // Function to toggle column visibility
        function toggleColumnVisibility(column, show) {
            const elements = document.querySelectorAll(`.column-${column}`);
            elements.forEach(el => {
                el.style.display = show ? '' : 'none';
            });

            const checkbox = document.querySelector(`.column-toggle[data-column="${column}"]`);
            if (checkbox) {
                checkbox.checked = show;
            }
        }

        // Function to save column visibility to localStorage
        function saveColumnVisibility() {
            const visibility = {};
            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                visibility[checkbox.dataset.column] = checkbox.checked;
            });
            localStorage.setItem('saleColumnVisibility', JSON.stringify(visibility));
        }

        // Function to load column visibility from localStorage
        function loadColumnVisibility() {
            const savedVisibility = localStorage.getItem('saleColumnVisibility');
            if (savedVisibility) {
                const visibility = JSON.parse(savedVisibility);
                for (const column in visibility) {
                    toggleColumnVisibility(column, visibility[column]);
                }
            }
        }
    </script>
@endpush
