@extends('layouts.auth')

@section('title', 'Create Bill')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.bill') as $module)
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
                        <h2 class="text-xl font-semibold text-gray-800">Create Bill</h2>
                        <a href="{{ route('account.bill-invoice.index') }}"
                            class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all hover:shadow-lg">
                            ← BACK
                        </a>
                    </div>
                </div>
                <div class="bg-white border border-gray-300 rounded-lg my-4">
                    <!-- Form -->
                    <form action_url="{{ route('account.bill-invoice.store') }}"
                        back_url="{{ route('account.bill-invoice.index') }}" method="POST" enctype="multipart/form-data"
                        id="formSubmit" class="p-6">
                        @csrf

                        <!-- AI Invoice Extraction -->
                        <div id="extract-section" class="mb-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-100 rounded-full p-2">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">Extract from Invoice</p>
                                        <p class="text-xs text-gray-500">Upload an invoice image or PDF to auto-fill the form</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="file" id="extract-file" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                                    <button type="button" id="extract-btn"
                                        onclick="document.getElementById('extract-file').click()"
                                        class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#0078C8] transition-all flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Upload & Extract
                                    </button>
                                </div>
                            </div>
                            <div id="extract-status" class="hidden mt-3">
                                <div id="extract-loading" class="flex items-center gap-2 text-sm text-blue-700">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Extracting invoice data... This may take a few seconds.
                                </div>
                                <div id="extract-success" class="hidden text-sm text-green-700 font-medium">
                                    Invoice data extracted. Please review the auto-filled fields below.
                                </div>
                                <div id="extract-error" class="hidden text-sm text-red-600 font-medium"></div>
                                <div id="extract-vendor-warning" class="hidden text-sm text-yellow-700 mt-1">
                                    Vendor not found automatically. Please select the vendor manually.
                                </div>
                            </div>
                        </div>

                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                            <x-acccount.create-vendor :vendors="$vendors" />
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

                        @include('components.duplicate-warning', [
                            'duplicateCheckType' => 'bill',
                            'duplicateCheckUrl'  => route('account.check-duplicates'),
                        ])

                        @include('pages.account.bill.partials.advanced-fields')

                        <!-- Table Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6 bg-gray-50">
                            <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                                <table id="invoice-table" class="w-full text-sm border-collapse min-w-[1000px] table-fixed">
                                    <thead class="border-b-2 border-gray-300 text-gray-700">
                                        <tr>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-24 column-item font-semibold">
                                                Item</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-64 column-description font-semibold">
                                                Description</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-center w-20 column-hs-code font-semibold"
                                                style="display: none">
                                                HS Code</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-center w-20 column-qty font-semibold">
                                                Qty</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-center w-20 column-kg font-semibold"
                                                style="display: none">
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
                                            <th class="border-r border-gray-300 px-3 py-3 text-right w-24 column-discount font-semibold"
                                                style="display: none">
                                                Discount %</th>
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

                                    <button type="button" onclick="openTaxRateModal('create')"
                                        class="px-5 py-2 border-2 border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#0078C8] hover:text-white transition-all flex items-center gap-2">
                                        ADD TAX RATE
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
                                                            data-column="hs-code">
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
                                                            data-column="kg">
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
                                                        class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                        <input type="checkbox"
                                                            class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                            data-column="discount">
                                                        <span class="text-sm text-gray-700">Discount</span>
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
                                    <div class="text-left text-gray-600 font-medium">Discount:</div>
                                    <div id="discount-total" class="text-left font-semibold text-gray-800">0.00</div>
                                    <div class="text-left text-gray-600 font-medium">Tax:</div>
                                    <div id="tax-total" class="text-left font-semibold text-gray-800">0.00</div>
                                    <div class="text-left text-gray-600 font-medium">Withholding:</div>
                                    <div id="withholding-total" class="text-left font-semibold text-gray-800">0.00</div>
                                    <div class="text-left text-[#0078C8] font-semibold border-t border-gray-300 pt-2">
                                        Total:</div>
                                    <div id="grand-total"
                                        class="text-left font-bold text-[#0078C8] border-t border-gray-300 pt-2 text-lg">
                                        0.00</div>
                                    <div class="text-left text-gray-600 font-medium">Base Total ({{ currency('code') }}):</div>
                                    <div id="base-currency-total" class="text-left font-semibold text-gray-800">0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">Payment Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type of Payment <span
                                            id="payment_type_asterisk" class="text-red-500 hidden">*</span></label>
                                    <x-chart-account-bank-select name="payment_type" id="payment_type"
                                        :selected="old('payment_type')" />
                                    <span class="text-red-500 text-sm hidden" id="payment_type_error">Please select a
                                        payment type</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Currency <span
                                            class="text-red-500">*</span></label>
                                    <select name="currency_id" id="currency_id" required
                                        class="select2 w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all bg-white">
                                        <option value="">-- Select Currency --</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}"
                                                {{ $currency->code == 'PKR' ? 'selected' : '' }}>{{ $currency->code }} -
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
                        <div class="flex justify-end items-center gap-4">

                            <!-- Checkbox -->
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="send_email" value="1"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Email vendor after approval</span>
                            </label>

                            <!-- Reset Button -->
                            <button type="reset"
                                class="px-6 py-2 border-2 border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-100 transition-all">
                                RESET
                            </button>

                            <!-- Submit Button -->
                            <button type="submit" id="submit-btn"
                                class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all hover:shadow-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                SAVE DRAFT
                            </button>

                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-acccount.tax_rate.create-tax-rate />
@endsection

@push('scripts')
    <script>
        let rowIndex = 0;
        const accounts = @json($accounts);
        const taxes = @json($taxes);
        let activeTaxSelect = null;

        @include('pages.account.partials.account-tax-default-script')

        function taxOptionLabel(taxRate) {
            return `${taxRate.name} (${parseFloat(taxRate.tax_rate || 0)}%)`;
        }

        function appendTaxRateOption(select, taxRate) {
            if (!select || select.querySelector(`option[value="${taxRate.id}"]`)) {
                return;
            }

            const option = new Option(taxOptionLabel(taxRate), taxRate.id, false, false);
            option.setAttribute('data-rate', taxRate.tax_rate);
            select.add(option);
        }

        window.handleTaxRateSaved = function(taxRate) {
            if (!taxRate) {
                return false;
            }

            taxes.push(taxRate);

            document.querySelectorAll('.tax-select').forEach(select => {
                appendTaxRateOption(select, taxRate);
            });

            const targetSelect = activeTaxSelect || document.querySelector('.tax-select');
            if (targetSelect) {
                $(targetSelect).val(String(taxRate.id)).trigger('change');
            }

            Notification.success('Tax rate added to the bill.');
            return false;
        };

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
            document.getElementById('withholding_tax')?.addEventListener('input', updateTotals);
        });

        // Function to setup payment amount validation
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

            paidAmountInput.addEventListener('input', function() {
                const grandTotal = parseFloat(document.getElementById('grand-total').textContent) || 0;
                const paidAmount = parseFloat(this.value) || 0;

                if (paidAmount > grandTotal) {
                    this.value = grandTotal.toFixed(2);
                    Notification.error('Paid amount cannot be greater than the total amount!');
                }

                togglePaymentTypeRequired();
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
                togglePaymentTypeRequired();
            });
            paymentTypeSelect.addEventListener('change', togglePaymentTypeRequired);
            $(paymentTypeSelect).on('select2:select', togglePaymentTypeRequired);
            $(paymentTypeSelect).on('select2:unselect', togglePaymentTypeRequired);
            togglePaymentTypeRequired();
        }

        // Function to setup row calculations including discount
        function setupRowLogic(row) {
            const qtyInput = row.querySelector('.row-qty');
            const kgInput = row.querySelector('.row-kg');
            const priceInput = row.querySelector('.row-price');
            const discountInput = row.querySelector('.row-discount');
            const taxSelect = row.querySelector('.tax-select');
            const totalInput = row.querySelector('.row-total');

            taxSelect.addEventListener('focus', function() {
                activeTaxSelect = taxSelect;
            });

            // Function to calculate row total with discount and tax
            function calculateRowTotal() {
                const qty = parseFloat(qtyInput.value) || 0;
                const kg = parseFloat(kgInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const discountPercent = parseFloat(discountInput.value) || 0;

                // Validate discount percentage
                if (discountPercent < 0 || discountPercent > 100) {
                    Notification.error('Discount percentage must be between 0 and 100');
                    discountInput.value = Math.min(Math.max(discountPercent, 0), 100);
                    return;
                }

                // Calculate subtotal based on either qty or kg (whichever is greater than 0)
                let subtotal = 0;
                if (kg > 0) {
                    subtotal = kg * price; // Corrected: Multiply KG by price
                } else if (qty > 0) {
                    subtotal = qty * price;
                }

                // Calculate discount amount
                const discountAmount = (subtotal * discountPercent) / 100;
                const amountAfterDiscount = subtotal - discountAmount;

                // Get tax rate from the selected option's data-rate attribute
                let taxRate = 0;
                if (taxSelect.value) {
                    const selectedOption = taxSelect.options[taxSelect.selectedIndex];
                    taxRate = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
                }

                // Calculate tax (tax is applied before discount - Gross Method)
                const taxAmount = (amountAfterDiscount * taxRate) / 100;

                // Calculate final total
                const total = amountAfterDiscount + taxAmount;

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

            // Price and discount changes trigger calculation
            priceInput.addEventListener('input', calculateRowTotal);
            discountInput.addEventListener('input', calculateRowTotal);

            // Handle tax select change (for both native and select2)
            taxSelect.addEventListener('change', calculateRowTotal);

            // For select2, also listen to select2 events
            $(taxSelect).on('select2:open select2:select', function() {
                activeTaxSelect = taxSelect;
                calculateRowTotal();
            });
            $(taxSelect).on('select2:unselect', calculateRowTotal);

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
            <td class="border-r border-gray-300 px-2 py-2 text-center column-hs-code" style="display: none">
                <input type="text" name="rows[${rowIndex}][hs_code]" placeholder="HS Code"
                    class="w-full h-9 px-2 border border-gray-200 outline-none rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-qty">
                <input type="number" name="rows[${rowIndex}][quantity]" min="0" step="1" value="1" placeholder="0"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-qty">
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-kg" style="display: none">
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
                    <option value="">-- Select Account --</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-tax">
                <select name="rows[${rowIndex}][tax_id]"
                    class="select2 w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all tax-select">
                    <option value="">-- No Tax --</option>
                    @foreach ($taxes as $tax)
                        <option value="{{ $tax->id }}" data-rate="{{ $tax->tax_rate }}">{{ $tax->name }} ({{ $tax->tax_rate }}%)</option>
                    @endforeach
                </select>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-discount" style="display: none">
                <input type="number" name="rows[${rowIndex}][discount_percentage]" min="0" max="100" step="0.01" value="0" placeholder="0.00"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-right focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-discount">
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
            taxes.forEach(taxRate => {
                appendTaxRateOption(newRow.querySelector('.tax-select'), taxRate);
            });

            $(newRow).find('.select2').select2({
                width: '100%'
            });

            // Setup row logic for calculations including discount
            setupRowLogic(newRow);
            setupAccountDefaultTax(newRow);

            // AI account suggestions
            if (typeof window.attachAccountSuggestion === 'function') {
                window.attachAccountSuggestion(newRow);
            }

            // Apply current column visibility
            applyCurrentColumnVisibility();

            // Delete row handler
            newRow.querySelector('.delete-row').addEventListener('click', function() {
                if (tbody.children.length > 1) {
                    newRow.remove();
                    updateTotals();
                } else {
                    Notification.error('You need at least one row in the table.');
                }
            });
        }

        function updateBaseCurrencyTotal() {
            const grandTotalElement = document.getElementById('grand-total');
            const exchangeRateInput = document.getElementById('exchange_rate');
            const baseTotalElement = document.getElementById('base-currency-total');

            if (!grandTotalElement || !exchangeRateInput || !baseTotalElement) {
                return;
            }

            const grandTotal = parseFloat(grandTotalElement.textContent.replace(/,/g, '')) || 0;
            const exchangeRate = parseFloat(exchangeRateInput.value) || 0;
            baseTotalElement.textContent = (grandTotal * exchangeRate).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 4
            });
        }

        // Update all totals
        function updateTotals() {
            let subtotal = 0;
            let discountTotal = 0;
            let taxTotal = 0;
            let grandTotal = 0;

            document.querySelectorAll('#invoice-table-body tr').forEach(row => {
                const qty = parseFloat(row.querySelector('.row-qty')?.value) || 0;
                const kg = parseFloat(row.querySelector('.row-kg')?.value) || 0;
                const price = parseFloat(row.querySelector('.row-price')?.value) || 0;
                const discountPercent = parseFloat(row.querySelector('.row-discount')?.value) || 0;
                const taxSelect = row.querySelector('.tax-select');
                // Calculate row subtotal
                let rowSubtotal = 0;
                if (kg > 0) {
                    rowSubtotal = kg * price; // Corrected: Multiply KG by price
                } else if (qty > 0) {
                    rowSubtotal = qty * price;
                }

                subtotal += rowSubtotal;

                // Calculate row discount
                const rowDiscount = (rowSubtotal * discountPercent) / 100;
                discountTotal += rowDiscount;

                // Calculate row tax
                let taxRate = 0;
                if (taxSelect && taxSelect.value) {
                    const selectedOption = taxSelect.options[taxSelect.selectedIndex];
                    taxRate = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
                }

                const amountAfterDiscount = rowSubtotal - rowDiscount;
                const rowTax = (amountAfterDiscount * taxRate) / 100;
                taxTotal += rowTax;

                const calculatedRowTotal = amountAfterDiscount + rowTax;
                const rowTotalInput = row.querySelector('.row-total');
                if (rowTotalInput) {
                    rowTotalInput.value = calculatedRowTotal.toFixed(2);
                }
                grandTotal += calculatedRowTotal;
            });

            const withholding = Math.max(parseFloat(document.getElementById('withholding_tax')?.value) || 0, 0);
            const payableTotal = Math.max(grandTotal - withholding, 0);
            const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;

            // Ensure paid amount doesn't exceed grand total
            const validPaidAmount = Math.min(paidAmount, payableTotal);
            const remainingAmount = payableTotal - validPaidAmount;

            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('discount-total').textContent = discountTotal.toFixed(2);
            document.getElementById('tax-total').textContent = taxTotal.toFixed(2);
            document.getElementById('withholding-total').textContent = withholding.toFixed(2);
            document.getElementById('grand-total').textContent = payableTotal.toFixed(2);
            updateBaseCurrencyTotal();
            document.getElementById('remaining').value = remainingAmount.toFixed(2);

            // Update paid amount if it exceeds grand total
            if (paidAmount > payableTotal) {
                document.getElementById('paid_amount').value = payableTotal.toFixed(2);
            }
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

        // Apply all column visibility settings based on checkbox states
        function applyCurrentColumnVisibility() {
            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                toggleColumnVisibility(checkbox.dataset.column, checkbox.checked);
            });
        }

        // Function to save column visibility to localStorage
        function saveColumnVisibility() {
            const visibility = {};
            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                visibility[checkbox.dataset.column] = checkbox.checked;
            });
            localStorage.setItem('billColumnVisibility', JSON.stringify(visibility));
        }

        // Function to load column visibility from localStorage
        function loadColumnVisibility() {
            const savedVisibility = localStorage.getItem('billColumnVisibility');
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
        }

        // ─── AI Invoice Extraction ───────────────────────────────────
        (function() {
            const extractFileInput = document.getElementById('extract-file');
            const extractBtn       = document.getElementById('extract-btn');
            const statusDiv        = document.getElementById('extract-status');
            const loadingDiv       = document.getElementById('extract-loading');
            const successDiv       = document.getElementById('extract-success');
            const errorDiv         = document.getElementById('extract-error');
            const vendorWarningDiv = document.getElementById('extract-vendor-warning');

            if (!extractFileInput) return;

            extractFileInput.addEventListener('change', async function() {
                const file = this.files[0];
                if (!file) return;

                // Show loading state
                statusDiv.classList.remove('hidden');
                loadingDiv.classList.remove('hidden');
                successDiv.classList.add('hidden');
                errorDiv.classList.add('hidden');
                vendorWarningDiv.classList.add('hidden');
                extractBtn.disabled = true;
                extractBtn.classList.add('opacity-50', 'cursor-not-allowed');

                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch('{{ route("account.bill-invoice.extract") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    });

                    const result = await response.json();

                    loadingDiv.classList.add('hidden');

                    if (result.success && result.data) {
                        successDiv.classList.remove('hidden');
                        fillFormFromExtraction(result.data);

                        if (!result.data.vendor_id) {
                            vendorWarningDiv.classList.remove('hidden');
                            if (result.data.vendor_name) {
                                vendorWarningDiv.textContent =
                                    `Vendor "${result.data.vendor_name}" not found in your records. Please select manually.`;
                            }
                        }
                    } else {
                        errorDiv.textContent = result.message || 'Extraction failed. Please enter the bill manually.';
                        errorDiv.classList.remove('hidden');
                    }
                } catch (err) {
                    loadingDiv.classList.add('hidden');
                    errorDiv.textContent = 'Network error during extraction. Please try again.';
                    errorDiv.classList.remove('hidden');
                } finally {
                    extractBtn.disabled = false;
                    extractBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    // Reset file input so the same file can be re-uploaded
                    extractFileInput.value = '';
                }
            });

            function fillFormFromExtraction(data) {
                // --- Header fields ---
                if (data.vendor_id) {
                    const vendorSelect = document.querySelector('select[name="vendor_id"]');
                    if (vendorSelect) {
                        $(vendorSelect).val(data.vendor_id).trigger('change');
                        highlightField(vendorSelect);
                    }
                }

                if (data.invoice_no) {
                    const field = document.querySelector('input[name="invoice_no"]');
                    if (field) { field.value = data.invoice_no; highlightField(field); }
                }

                if (data.date) {
                    const field = document.querySelector('input[name="date"]');
                    if (field) { field.value = data.date; highlightField(field); }
                }

                if (data.ref_no) {
                    const field = document.querySelector('input[name="ref_no"]');
                    if (field) { field.value = data.ref_no; highlightField(field); }
                }

                if (data.note) {
                    const field = document.querySelector('textarea[name="note"]');
                    if (field) { field.value = data.note; highlightField(field); }
                }

                // --- Line items ---
                if (data.rows && data.rows.length > 0) {
                    // Clear existing rows
                    const tbody = document.getElementById('invoice-table-body');
                    tbody.innerHTML = '';
                    rowIndex = 0;

                    data.rows.forEach((item, idx) => {
                        addNewRow();
                        const row = tbody.querySelectorAll('tr')[idx];
                        if (!row) return;

                        // Description
                        const descInput = row.querySelector('input[name$="[description]"]');
                        if (descInput && item.description) {
                            descInput.value = item.description;
                            highlightField(descInput);
                        }

                        // Quantity
                        const qtyInput = row.querySelector('.row-qty');
                        if (qtyInput && item.quantity) {
                            qtyInput.value = item.quantity;
                        }

                        // Price
                        const priceInput = row.querySelector('.row-price');
                        if (priceInput && item.price) {
                            priceInput.value = parseFloat(item.price).toFixed(2);
                            highlightField(priceInput);
                        }

                        // Tax
                        if (item.tax_id) {
                            const taxSelect = row.querySelector('.tax-select');
                            if (taxSelect) {
                                $(taxSelect).val(item.tax_id).trigger('change');
                                highlightField(taxSelect.closest('td'));
                            }
                        }

                        // Trigger row recalculation
                        const qtyEvent = new Event('input', { bubbles: true });
                        if (qtyInput) qtyInput.dispatchEvent(qtyEvent);
                    });

                    updateTotals();
                }
            }

            function highlightField(el) {
                if (!el) return;
                el.classList.add('ring-2', 'ring-blue-300', 'border-blue-400');
                setTimeout(() => {
                    el.classList.remove('ring-2', 'ring-blue-300', 'border-blue-400');
                }, 5000);
            }
        })();
    </script>
    @include('components.account-suggestion')
@endpush
