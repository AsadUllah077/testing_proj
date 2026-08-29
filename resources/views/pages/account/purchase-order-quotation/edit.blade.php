@extends('layouts.auth')

@section('title', 'Requested Quotation')

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
                        <h2 class="text-xl font-semibold text-gray-800">Requested Quotation</h2>
                        <a href="{{ route('account.purchase-order.order.list') }}"
                            class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg">
                            ← BACK
                        </a>
                    </div>
                </div>
                <div class="bg-white border border-gray-300 rounded-lg shadow-sm my-4">
                    <!-- Form -->
                    <form action_url="{{ route('account.purchase-order-quotation.store') }}"
                        back_url="{{ route('account.purchase-order.order.list') }}" method="POST"
                        enctype="multipart/form-data" class="p-6" id="formSubmit">
                        @csrf
                        <input type="hidden" name="unique_no" value="{{ $purchaseOrder->unique_no }}">


                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                            <div x-data="vendorDropdown({{ $purchaseOrder->vendor_id }})" class="relative">

                                <!-- Label -->
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Vendor <span class="text-red-500">*</span>
                                </label>

                                <!-- Selected box -->
                                <div class="bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none cursor-pointer"
                                    @click="toggleDropdown">

                                    <span x-text="selected?.name ?? 'Select vendor'"></span>
                                </div>

                                <!-- DROPDOWN -->
                                <div x-show="open" @scroll="handleScroll" x-transition
                                    class="absolute z-50 w-full bg-white border rounded shadow mt-1 max-h-80 overflow-y-auto">


                                    <!-- SEARCH -->
                                    <div class="p-2">
                                        <input type="text" x-model="search" @input="filterVendors"
                                            placeholder="Search vendors..."
                                            class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                    </div>

                                    <!-- VENDOR LIST -->
                                    <template x-if="filtered.length > 0">
                                        <ul>
                                            <template x-for="c in filtered" :key="c.id">
                                                <li @click="selectVendor(c)"
                                                    class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                                                    :class="{ 'bg-blue-50': selected?.id === c.id }">
                                                    <span x-text="c.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>

                                    <!-- ADD NEW VENDOR OPTION -->
                                    <template x-if="filtered.length === 0 && search.length > 0 && !showForm">
                                        <div class="p-3 text-blue-600 cursor-pointer text-sm" @click="showAddForm()">
                                            ➕ Add "<span x-text="search"></span>" as a new vendor
                                        </div>
                                    </template>

                                    <!-- XERO-STYLE INLINE ADD FORM -->
                                    <div x-show="showForm" x-transition class="p-3">

                                        <div class="mb-2">
                                            <label class="text-xs">Vendor Name</label>
                                            <input type="text" x-model="form.name"
                                                class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all"
                                                :class="errors.name ? 'border-red-500' : ''">
                                            <template x-if="errors.name">
                                                <p class="text-red-600 text-xs mt-1" x-text="errors.name[0]"></p>
                                            </template>
                                        </div>

                                        <div class="mb-2">
                                            <label class="text-xs">Email</label>
                                            <input type="email" x-model="form.email"
                                                class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all"
                                                :class="errors.email ? 'border-red-500' : ''">

                                            <template x-if="errors.email">
                                                <p class="text-red-600 text-xs mt-1" x-text="errors.email[0]"></p>
                                            </template>
                                        </div>

                                        <div class="mb-2">
                                            <label class="text-xs">Phone</label>
                                            <input type="text" x-model="form.phone"
                                                class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all"
                                                :class="errors.phone ? 'border-red-500' : ''">

                                            <template x-if="errors.phone">
                                                <p class="text-red-600 text-xs mt-1" x-text="errors.phone[0]"></p>
                                            </template>
                                        </div>


                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" @click="cancelAdd"
                                                class="px-3 py-1 border rounded text-sm">Cancel</button>

                                            <button type="button" @click="saveCustomer"
                                                class="px-3 py-1 bg-[#0078C8] text-white rounded text-sm">
                                                Save
                                            </button>
                                        </div>

                                    </div>
                                </div>

                                <!-- Hidden input for backend -->
                                <input type="hidden" name="vendor_id" :value="selected?.id ?? ''">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order Date <span
                                        class="text-red-500">*</span></label>
                                <input class="date" type="date" name="order_date" required
                                    value="{{ $purchaseOrder->order_date->format('Y-m-d') }}"
                                    class="date w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Departure Date <span
                                        class="text-red-500">*</span></label>
                                <input class="date" type="date" name="departure_date" required
                                    value="{{ $purchaseOrder->departure_date->format('Y-m-d') }}"
                                    class="date w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order No <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="order_no" required placeholder="Enter order number"
                                    value="{{ $purchaseOrder->order_no }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ref No</label>
                                <input type="text" name="ref_no" placeholder="Reference number"
                                    value="{{ $purchaseOrder->ref_no }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Requested By</label>
                                <input type="text" name="requested_by" placeholder="Requested By"
                                    value="{{ $purchaseOrder->requested_by }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Invoice No</label>
                                <input type="text" name="invoice_no" placeholder="Invoice number"
                                    value="{{ $purchaseOrder->invoice_no }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Currency <span
                                        class="text-red-500">*</span></label>
                                <select name="currency_id" required
                                    class="select2 w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all bg-white">
                                    <option value="">-- Select Currency --</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ $purchaseOrder->currency_id == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->code }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Status <span
                                        class="text-red-500">*</span></label>
                                <select name="status" required
                                    class="select2 w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all bg-white">
                                    <option value="">-- Select Status --</option>
                                    <option value="1">Cancelled</option>
                                    <option value="2">Approve & Delivered</option>

                                </select>
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6 shadow-sm">
                            <div class="overflow-x-auto">
                                <table id="invoice-table" class="w-full text-sm border-collapse">
                                    <thead
                                        class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 text-gray-700">
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
                                                class="border-r border-gray-300 px-3 py-3 text-left w-24 column-tax font-semibold">
                                                Tax</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-right w-20 column-project font-semibold">
                                                Project</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-right w-28 column-total font-semibold">
                                                Total</th>
                                            <th class="px-2 py-3 w-12 text-center font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-table-body" class="bg-white">
                                        <!-- Rows will be added dynamically from existing items -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Buttons and Totals -->
                        <div class="grid grid-cols-1 md:grid-cols-2 mb-6 gap-4">
                            <div class="flex items-start gap-3">
                                <button type="button" id="add-new-line"
                                    class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    ADD NEW LINE
                                </button>

                                <!-- Column Dropdown -->
                                <div class="relative">
                                    {{-- <button onclick="toggleDropdown('columnDropdown')" type="button"
                                        class="px-5 py-2 border-2 border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#0078C8] hover:text-white transition-all flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z" />
                                        </svg>
                                        COLUMNS ▼
                                    </button> --}}
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
                                                    class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100">
                                                    <input type="checkbox"
                                                        class="column-toggle mr-3 w-4 h-4 text-[#0078C8] rounded focus:ring-2 focus:ring-[#0078C8]"
                                                        data-column="project" checked>
                                                    <span class="text-sm text-gray-700">Project</span>
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

                            <div
                                class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-lg p-4 shadow-sm w-full">
                                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                                    <div class="text-left text-gray-600 font-medium">Sub Total:</div>
                                    <div id="subtotal" class="text-left font-semibold text-gray-800">
                                        {{ number_format($purchaseOrder->sub_total, 2) }}</div>
                                    <div class="text-left text-gray-600 font-medium">Tax:</div>
                                    <div id="tax-total" class="text-left font-semibold text-gray-800">
                                        {{ number_format($purchaseOrder->tax, 2) }}</div>
                                    <div class="text-left text-[#0078C8] font-semibold border-t border-gray-300 pt-2">
                                        Total:</div>
                                    <div id="grand-total"
                                        class="text-left font-bold text-[#0078C8] border-t border-gray-300 pt-2 text-lg">
                                        {{ number_format($purchaseOrder->total, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Note Section -->
                        <div class="mb-6">
                            <label for="note" class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                            <textarea placeholder="Enter Note" name="note" rows="4"
                                class="w-full px-2 border border-gray-300 py-2 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">{{ $purchaseOrder->note }}</textarea>
                        </div>

                        <!-- Delivery Section -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">Delivery Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
                                    <textarea name="delivery_address" placeholder="Enter delivery address"
                                        class="w-full rounded-lg border border-gray-300 p-2 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:outline-none"
                                        rows="3">{{ $purchaseOrder->delivery_address }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Delivery Instruction
                                    </label>
                                    <textarea name="delivery_instruction" placeholder="Enter delivery instructions"
                                        class="w-full rounded-lg border border-gray-300 p-2 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:outline-none"
                                        rows="3">{{ $purchaseOrder->delivery_instruction }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex justify-end gap-3">
                            <button type="reset"
                                class="px-6 py-2 border-2 border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-100 transition-all">
                                RESET
                            </button>
                            <button type="submit" id="submit-btn"
                                class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                UPDATE PURCHASE ORDER
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
        let taxRates = @json($taxes);
        let existingItems = @json($purchaseOrder->purchaseOrderItems);

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

        // Initialize the table with existing rows
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, existing items:', existingItems);
            loadExistingItems();
            loadColumnVisibility();

            // Add event listener for add new line button
            document.getElementById('add-new-line').addEventListener('click', function() {
                console.log('Add new line clicked');
                addNewRow();
            });

            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleColumnVisibility(this.dataset.column, this.checked);
                    saveColumnVisibility();
                });
            });

            // Form submission handler
            // document.getElementById('purchase-order-form').addEventListener('submit', function(e) {
            //     e.preventDefault();
            //     submitForm();
            // });

            // Initialize Select2
            if (typeof $ !== 'undefined') {
                $('.select2').select2({
                    width: '100%'
                });
            }
        });

        // Function to load existing items
        function loadExistingItems() {
            const tbody = document.getElementById('invoice-table-body');
            console.log('Loading existing items:', existingItems);

            if (existingItems && existingItems.length > 0) {
                existingItems.forEach((item, index) => {
                    console.log('Adding item:', item);
                    addNewRowWithData(item, index);
                });
            } else {
                console.log('No existing items, adding empty row');
                addNewRow();
            }
            updateTotals();
        }

        // Function to add a new row with existing data
        function addNewRowWithData(item, index) {
            const tbody = document.getElementById('invoice-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'border-b border-gray-200 hover:bg-blue-50 transition-colors';
            newRow.dataset.rowIndex = rowIndex++;

            const quantity = item.quantity ? parseFloat(item.quantity) : 1;
            const price = item.price ? parseFloat(item.price) : 0;
            const total = item.total ? parseFloat(item.total) : 0;
            const kg = item.kg ? parseFloat(item.kg) : 0;

            // Escape special characters for template literals
            const escapeHtml = (text) => {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };

            // Generate account options
            let accountOptions = '';
            @foreach ($accounts as $account)
                accountOptions +=
                    `<option value="{{ $account->id }}" ${item.account_id == {{ $account->id }} ? 'selected' : ''}>{{ $account->name }}</option>`;
            @endforeach

            // Generate tax options
            let taxOptions = '';
            @foreach ($taxes as $tax)
                taxOptions +=
                    `<option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" ${item.tax_id == {{ $tax->id }} ? 'selected' : ''}>{{ $tax->name }} ({{ $tax->rate }}%)</option>`;
            @endforeach

            newRow.innerHTML = `
                <td class="border-r border-gray-300 px-2 py-2 column-item">
                    <input type="text" name="rows[${rowIndex}][item]" placeholder="Item name"
                        value="${escapeHtml(item.item || '')}"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 column-description">
                    <input type="text" name="rows[${rowIndex}][description]" placeholder="Description"
                        value="${escapeHtml(item.description || '')}"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-center column-hs-code">
                    <input type="text" name="rows[${rowIndex}][hs_code]" placeholder="HS Code"
                        value="${escapeHtml(item.hs_code || '')}"
                        class="w-full h-9 px-2 border border-gray-200 outline-none rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-center column-qty">
                    <input type="number" name="rows[${rowIndex}][quantity]" min="0" step="1" value="${quantity}" placeholder="0"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-qty">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-center column-kg">
                    <input type="number" name="rows[${rowIndex}][kg]" min="0" step="0.01" value="${kg}" placeholder="0"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-right column-price">
                    <input type="number" name="rows[${rowIndex}][price]" min="0" step="0.01" value="${price}" placeholder="0.00"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-right focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-price">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 column-account">
                    <select name="rows[${rowIndex}][account_id]" required
                        class="select2 w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                        <option value="">-- Select Account --</option>
                        ${accountOptions}
                    </select>
                </td>
                <td class="border-r border-gray-300 px-2 py-2 column-tax">
                    <select name="rows[${rowIndex}][tax_id]"
                        class="tax-select w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                        <option value="">-- No Tax --</option>
                        ${taxOptions}
                    </select>
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-right column-project">
                    <input type="text" name="rows[${rowIndex}][project]" placeholder="Project"
                        value="${escapeHtml(item.project || '')}"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-right focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-right column-total">
                    <input type="number" name="rows[${rowIndex}][total]" readonly value="${total.toFixed(2)}" placeholder="0.00"
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
            console.log('Row added with index:', rowIndex);

            // Initialize Select2 for the new row
            if (typeof $ !== 'undefined') {
                $('.select2').select2({
                    width: '100%'
                });
            }

            // Add calculation listeners
            const qtyInput = newRow.querySelector('.row-qty');
            const priceInput = newRow.querySelector('.row-price');
            const taxSelect = newRow.querySelector('.tax-select');
            const totalInput = newRow.querySelector('.row-total');

            function calculateRowTotal() {
                const qty = parseFloat(qtyInput.value) || 1;
                const price = parseFloat(priceInput.value) || 0;

                // Calculate subtotal
                const subtotal = qty * price;

                // Calculate tax
                const selectedTax = taxSelect.options[taxSelect.selectedIndex];
                const taxRate = selectedTax ? parseFloat(selectedTax.dataset.rate) || 0 : 0;
                const taxAmount = subtotal * (taxRate / 100);

                // Calculate total
                const total = subtotal + taxAmount;

                totalInput.value = total.toFixed(2);
                updateTotals();
            }

            qtyInput.addEventListener('input', calculateRowTotal);
            priceInput.addEventListener('input', calculateRowTotal);
            taxSelect.addEventListener('change', calculateRowTotal);

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

        // Function to add a new empty row
        function addNewRow() {
            const tbody = document.getElementById('invoice-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'border-b border-gray-200 hover:bg-blue-50 transition-colors';
            newRow.dataset.rowIndex = rowIndex++;

            // Generate account options
            let accountOptions = '';
            @foreach ($accounts as $account)
                accountOptions += `<option value="{{ $account->id }}">{{ $account->name }}</option>`;
            @endforeach

            // Generate tax options
            let taxOptions = '';
            @foreach ($taxes as $tax)
                taxOptions +=
                    `<option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>`;
            @endforeach

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
                    <input type="number" name="rows[${rowIndex}][quantity]" min="0" step="1" value="0" placeholder="0"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-qty">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-center column-kg">
                    <input type="number" name="rows[${rowIndex}][kg]" min="0" step="0.01" value="0" placeholder="0"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-right column-price">
                    <input type="number" name="rows[${rowIndex}][price]" min="0" step="0.01" value="0" placeholder="0.00"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-right focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-price">
                </td>
                <td class="border-r border-gray-300 px-2 py-2 column-account">
                    <select name="rows[${rowIndex}][account_id]" required
                        class="select2 w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                        <option value="">-- Select Account --</option>
                        ${accountOptions}
                    </select>
                </td>
                <td class="border-r border-gray-300 px-2 py-2 column-tax">
                    <select name="rows[${rowIndex}][tax_id]"
                        class="tax-select w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                        <option value="">-- No Tax --</option>
                        ${taxOptions}
                    </select>
                </td>
                <td class="border-r border-gray-300 px-2 py-2 text-right column-project">
                    <input type="text" name="rows[${rowIndex}][project]" placeholder="Project"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-right focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
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
            console.log('Empty row added with index:', rowIndex);

            // Initialize Select2 for the new row
            if (typeof $ !== 'undefined') {
                $('.select2').select2({
                    width: '100%'
                });
            }

            // Add calculation listeners
            const qtyInput = newRow.querySelector('.row-qty');
            const priceInput = newRow.querySelector('.row-price');
            const taxSelect = newRow.querySelector('.tax-select');
            const totalInput = newRow.querySelector('.row-total');

            function calculateRowTotal() {
                const qty = parseFloat(qtyInput.value) || 1;
                const price = parseFloat(priceInput.value) || 0;

                // Calculate subtotal
                const subtotal = qty * price;

                // Calculate tax
                const selectedTax = taxSelect.options[taxSelect.selectedIndex];
                const taxRate = selectedTax ? parseFloat(selectedTax.dataset.rate) || 0 : 0;
                const taxAmount = subtotal * (taxRate / 100);

                // Calculate total
                const total = subtotal + taxAmount;

                totalInput.value = total.toFixed(2);
                updateTotals();
            }

            qtyInput.addEventListener('input', calculateRowTotal);
            priceInput.addEventListener('input', calculateRowTotal);
            taxSelect.addEventListener('change', calculateRowTotal);

            // Delete row handler
            newRow.querySelector('.delete-row').addEventListener('click', function() {
                if (tbody.children.length > 1) {
                    newRow.remove();
                    updateTotals();
                } else {
                    showErrorNotificaiton('You need at least one row in the table.');
                }
            });
        }

        // Update all totals
        function updateTotals() {
            let subtotal = 0;
            let taxTotal = 0;

            document.querySelectorAll('#invoice-table-body tr').forEach(row => {
                const qty = parseFloat(row.querySelector('.row-qty')?.value) || 1;
                const price = parseFloat(row.querySelector('.row-price')?.value) || 0;
                const total = parseFloat(row.querySelector('.row-total')?.value) || 0;

                const rowSubtotal = qty * price;
                const rowTax = total - rowSubtotal;

                subtotal += rowSubtotal;
                taxTotal += rowTax;
            });

            const grandTotal = subtotal + taxTotal;

            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('tax-total').textContent = taxTotal.toFixed(2);
            document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
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
            localStorage.setItem('purchaseOrderColumnVisibility', JSON.stringify(visibility));
        }

        // Function to load column visibility from localStorage
        function loadColumnVisibility() {
            const savedVisibility = localStorage.getItem('purchaseOrderColumnVisibility');
            if (savedVisibility) {
                const visibility = JSON.parse(savedVisibility);
                for (const column in visibility) {
                    toggleColumnVisibility(column, visibility[column]);
                }
            }
        }

        // Form submission
        // function submitForm() {
        //     const submitBtn = document.getElementById('submit-btn');
        //     const originalText = submitBtn.innerHTML;

        //     submitBtn.innerHTML = `
    //     <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
    //         <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    //         <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    //     </svg>
    //     UPDATING...
    // `;
        //     submitBtn.disabled = true;

        //     const formData = new FormData(document.getElementById('purchase-order-form'));

        //     fetch("{{ route('account.purchase-order-quotation.store') }}", {
        //             method: 'POST',
        //             body: formData,
        //             headers: {
        //                 'X-Requested-With': 'XMLHttpRequest',
        //                 'X-CSRF-TOKEN': '{{ csrf_token() }}'
        //             }
        //         })
        //         .then(response => response.json())
        //         .then(data => {
        //             if (data.status === 'success') {
        //                 window.location.href = "{{ route('account.purchase-order.order.list') }}";
        //             } else {
        //                 alert(data.message || 'Error occurred');
        //                 submitBtn.innerHTML = originalText;
        //                 submitBtn.disabled = false;
        //             }
        //         })
        //         .catch(error => {
        //             console.error('Error:', error);
        //             alert('An error occurred while updating the purchase order.');
        //             submitBtn.innerHTML = originalText;
        //             submitBtn.disabled = false;
        //         });
        // }
    </script>

    <script>
        function vendorDropdown(selectedVendorId = null) {
            return {
                open: false,
                search: "",
                showForm: false,
                selected: null,

                vendors: @json($vendors),
                filtered: [],

                form: {
                    name: "",
                    email: "",
                    phone: "",
                },

                errors: {},

                page: 1,
                hasMore: true,
                loading: false,

                init() {
                    // Set selected vendor if editing
                    if (selectedVendorId) {
                        this.selected = this.vendors.find(vendor => vendor.id == selectedVendorId) || null;
                    }
                },

                toggleDropdown() {
                    this.open = !this.open;

                    if (this.open) {
                        this.page = 1;
                        this.hasMore = true;
                        this.filtered = [];
                        this.loadVendors();
                        this.showForm = false;
                        this.search = "";
                    }
                },

                loadVendors() {
                    if (!this.hasMore || this.loading) return;

                    this.loading = true;

                    fetch(`{{ route('account.bill.vendors.paginate') }}?page=${this.page}&q=${this.search}`)
                        .then(res => res.json())
                        .then(data => {
                            if (this.page === 1) {
                                this.filtered = data.data;
                            } else {
                                this.filtered.push(...data.data);
                            }

                            if (data.next_page) {
                                this.page = data.next_page;
                            } else {
                                this.hasMore = false;
                            }

                            this.loading = false;
                        });
                },

                handleScroll(event) {
                    const bottom = event.target.scrollHeight - event.target.scrollTop <= event.target.clientHeight + 50;

                    if (bottom) {
                        this.loadVendors();
                    }
                },

                filterVendors() {
                    this.page = 1;
                    this.hasMore = true;
                    this.filtered = [];
                    this.loadVendors();
                },

                selectVendor(c) {
                    this.selected = c;
                    this.open = false;
                },

                showAddForm() {
                    this.showForm = true;
                    this.form.name = this.search;
                },

                cancelAdd() {
                    this.showForm = false;
                },

                saveCustomer(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.errors = {};
                    fetch("{{ route('account.bill.vendors.store.inline') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(this.form)
                        })
                        .then(async res => {
                            if (res.status === 422) {
                                let data = await res.json();
                                this.errors = data.errors;
                                return;
                            }
                            return res.json();
                        })
                        .then(data => {
                            if (!data || !data.success) return;
                            this.vendors.push(data.vendor);
                            this.selected = data.vendor;
                            this.showForm = false;
                            this.open = false;
                            this.search = "";
                        });
                }
            }
        }
    </script>
@endpush
