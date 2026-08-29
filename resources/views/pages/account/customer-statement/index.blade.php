@extends('layouts.auth')
@section('title', 'Customer Statements')

@section('content')
    <x-acccount.loader />
    <div class="p-4">
        <div class="flex justify-between items-center mb-4 p-5 bg-white rounded-lg border border-black/10 md:flex">
            <h1 class="text-lg font-bold text-gray-800">Customer Statements</h1>
            <form method="POST" action="{{ route('account.customer-statements.bulk-email') }}"
                class="flex items-center gap-2">
                @csrf
                <input id="customer-statement-as-of" type="date" name="as_of" value="{{ now()->toDateString() }}"
                    class="date border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                <button type="submit"
                    onclick="return confirm('Send statements to all customers with outstanding balances?')"
                    class="w-full bg-[#0078C8] text-white text-sm font-semibold px-4 py-1.5 rounded-[3px] hover:bg-[#0585db]">
                    Bulk Email All
                </button>
            </form>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">{{ session('error') }}</div>
        @endif

        {{-- ── Aged Receivable Card Summary ─────────────────────────────────────── --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
            <div class="bg-white border border-gray-200 rounded-[5px] p-3 border-t-[3px] border-t-[#0078C8]">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Current</p>
                <p class="text-lg font-bold text-gray-800 mt-1">{{ numberFormat($aging['current']) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-3 border-t-[3px] border-t-amber-300">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">1 – 30 Days</p>
                <p class="text-lg font-bold text-red-700 mt-1">{{ numberFormat($aging['b1']) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-3 border-t-[3px] border-t-amber-500">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">31 – 60 Days</p>
                <p class="text-lg font-bold text-red-700 mt-1">{{ numberFormat($aging['b2']) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-3 border-t-[3px] border-t-red-500">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">61 – 90 Days</p>
                <p class="text-lg font-bold text-red-700 mt-1">{{ numberFormat($aging['b3']) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-3 border-t-[3px] border-t-red-700">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">90+ Days</p>
                <p class="text-lg font-bold text-red-700 mt-1">{{ numberFormat($aging['b4']) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-[5px] border border-gray-200 overflow-hidden">
            <!-- Filters Section -->
            <form method="GET" id="filter-form">
                <input type="hidden" name="selected_customer_ids" id="selected-customer-statement-ids-input">
                <div class="p-6 border-gray-200">
                    <div class="flex flex-wrap items-end gap-4">

                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Select Customer</label>
                            <select name="customer_id"
                                class="ajax-customer-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </select>
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Min</label>
                            <input type="number" name="min_amount" value="{{ request('min_amount') }}"
                                placeholder="Enter outstanding balance min"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Max</label>
                            <input type="number" name="max_amount" value="{{ request('max_amount') }}"
                                placeholder="Enter outstanding balance max"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <x-default-page />
                        </div>

                        <div class="flex gap-2 items-end mt-1">
                            <button type="button" id="search-btn"
                                class=" px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                <span id="search-text">SEARCH</span>
                                <span id="search-loader" class="hidden ml-2">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                            <button type="button" id="reset-btn"
                                class=" px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                        </div>
                    </div>
                </div>
            </form>
            <div id="customer-statement-bulk-bar"
                class="hidden bg-[#0078C8] text-white border border-[#0078C8]/30 px-4 py-3 flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm font-medium">
                        <span id="customer-statement-selected-count" class="font-bold text-lg">0</span> customer(s) selected
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="downloadSelectedCustomerStatements('pdf')"
                            class="px-4 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition">
                            Bulk PDF
                        </button>
                        <button type="button" onclick="downloadSelectedCustomerStatements('zip')"
                            class="px-4 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition">
                            Separate PDFs ZIP
                        </button>
                        <button type="button" onclick="emailSelectedCustomerStatements()"
                            class="px-4 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition">
                            Email Selected
                        </button>
                        <button type="button" onclick="clearCustomerStatementSelection()"
                            class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-sm transition">
                            Clear
                        </button>
                    </div>
                </div>
                <div id="customer-statement-selected-list" class="flex flex-wrap gap-2"></div>
            </div>
            <div id="customer-statement-table">
                @include('pages.account.customer-statement.table')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.customer-statements.index') }}',
            tableSelector: '#customer-statement-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        // Initialize AJAX Customer Select
        $('.ajax-customer-select').select2({
            placeholder: '-- select customer --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.sale.customers.paginate') }}',
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
                        results: data.data.map(customer => ({
                            id: customer.id,
                            text: customer.name
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });

        const customerStatementSelectionKey = 'account.customer-statements.selected';
        const selectedCustomerStatements = new Map(JSON.parse(localStorage.getItem(customerStatementSelectionKey) || '[]'));
        const customerStatementBulkBar = document.getElementById('customer-statement-bulk-bar');
        const customerStatementSelectedCount = document.getElementById('customer-statement-selected-count');
        const customerStatementSelectedList = document.getElementById('customer-statement-selected-list');
        const customerStatementTable = document.getElementById('customer-statement-table');

        function persistCustomerStatementSelection() {
            localStorage.setItem(customerStatementSelectionKey, JSON.stringify(Array.from(selectedCustomerStatements.entries())));
            syncSelectedCustomerStatementIdsInput();
        }

        function syncSelectedCustomerStatementIdsInput() {
            const input = document.getElementById('selected-customer-statement-ids-input');
            if (input) input.value = Array.from(selectedCustomerStatements.keys()).join(',');
        }

        function renderSelectedCustomerStatementList() {
            customerStatementSelectedList.innerHTML = '';

            selectedCustomerStatements.forEach((label, customerId) => {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-2 rounded bg-white/15 border border-white/25 px-3 py-1 text-xs font-semibold';

                const text = document.createElement('span');
                text.textContent = label || `Customer #${customerId}`;

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'customer-statement-selected-remove text-white/80 hover:text-white';
                button.dataset.customerId = customerId;
                button.textContent = 'x';

                chip.appendChild(text);
                chip.appendChild(button);
                customerStatementSelectedList.appendChild(chip);
            });
        }

        function updateCustomerStatementBulkBar() {
            customerStatementSelectedCount.textContent = selectedCustomerStatements.size;
            customerStatementBulkBar.classList.toggle('hidden', selectedCustomerStatements.size === 0);
            customerStatementBulkBar.classList.toggle('flex', selectedCustomerStatements.size > 0);
            syncSelectedCustomerStatementIdsInput();
            renderSelectedCustomerStatementList();
        }

        function restoreCustomerStatementSelection() {
            document.querySelectorAll('.customer-statement-checkbox').forEach(checkbox => {
                checkbox.checked = selectedCustomerStatements.has(checkbox.value);
            });

            const visibleCheckboxes = Array.from(document.querySelectorAll('.customer-statement-checkbox'));
            const selectAll = document.getElementById('select-all-customer-statements');
            if (selectAll) {
                selectAll.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(checkbox => checkbox.checked);
            }
        }

        function clearCustomerStatementSelection() {
            selectedCustomerStatements.clear();
            persistCustomerStatementSelection();
            restoreCustomerStatementSelection();
            updateCustomerStatementBulkBar();
        }

        function downloadSelectedCustomerStatements(mode = 'zip') {
            if (selectedCustomerStatements.size === 0) {
                alert('Please select at least one customer statement.');
                return;
            }

            const params = new URLSearchParams();
            selectedCustomerStatements.forEach((label, customerId) => params.append('customer_ids[]', customerId));
            params.set('as_of', document.getElementById('customer-statement-as-of')?.value || '{{ now()->toDateString() }}');

            const downloadUrl = mode === 'pdf'
                ? `{{ route('account.customer-statements.bulk-pdf') }}`
                : `{{ route('account.customer-statements.separate-pdfs') }}`;

            window.location.href = `${downloadUrl}?${params.toString()}`;
        }

        function emailSelectedCustomerStatements() {
            if (selectedCustomerStatements.size === 0) {
                alert('Please select at least one customer statement.');
                return;
            }

            if (!confirm('Send statements to the selected customers?')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ route('account.customer-statements.selected-email') }}`;

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = `{{ csrf_token() }}`;
            form.appendChild(tokenInput);

            const asOfInput = document.createElement('input');
            asOfInput.type = 'hidden';
            asOfInput.name = 'as_of';
            asOfInput.value = document.getElementById('customer-statement-as-of')?.value || '{{ now()->toDateString() }}';
            form.appendChild(asOfInput);

            selectedCustomerStatements.forEach((label, customerId) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'customer_ids[]';
                input.value = customerId;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }

        document.addEventListener('change', function(event) {
            if (event.target?.id === 'select-all-customer-statements') {
                document.querySelectorAll('.customer-statement-checkbox').forEach(checkbox => {
                    checkbox.checked = event.target.checked;
                    if (event.target.checked) {
                        selectedCustomerStatements.set(checkbox.value, checkbox.dataset.label);
                    } else {
                        selectedCustomerStatements.delete(checkbox.value);
                    }
                });
                persistCustomerStatementSelection();
                updateCustomerStatementBulkBar();
            }

            if (event.target?.classList?.contains('customer-statement-checkbox')) {
                if (event.target.checked) {
                    selectedCustomerStatements.set(event.target.value, event.target.dataset.label);
                } else {
                    selectedCustomerStatements.delete(event.target.value);
                }
                persistCustomerStatementSelection();
                restoreCustomerStatementSelection();
                updateCustomerStatementBulkBar();
            }
        });

        document.addEventListener('click', function(event) {
            if (event.target?.classList?.contains('customer-statement-selected-remove')) {
                selectedCustomerStatements.delete(event.target.dataset.customerId);
                persistCustomerStatementSelection();
                restoreCustomerStatementSelection();
                updateCustomerStatementBulkBar();
            }
        });

        if (customerStatementTable) {
            new MutationObserver(() => {
                restoreCustomerStatementSelection();
                updateCustomerStatementBulkBar();
            }).observe(customerStatementTable, { childList: true, subtree: true });
        }

        restoreCustomerStatementSelection();
        updateCustomerStatementBulkBar();
        syncSelectedCustomerStatementIdsInput();
    </script>
@endpush
