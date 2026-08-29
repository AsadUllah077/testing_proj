@extends('layouts.auth')
@section('title', 'Vendor Statements')

@section('content')
    <x-acccount.loader />
    <div class="p-4">
        <div class="flex justify-between items-center mb-4 p-5 bg-white rounded-lg border border-black/10 md:flex">
            <h1 class="text-lg font-bold text-gray-800">Vendor Statements</h1>
            <form method="POST" action="{{ route('account.vendor-statements.bulk-email') }}" class="flex items-center gap-2">
                @csrf
                <input id="vendor-statement-as-of" type="date" name="as_of" value="{{ now()->toDateString() }}"
                    class="date border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                <button type="submit" onclick="return confirm('Send statements to all vendors with outstanding balances?')"
                    class="w-full bg-[#0078C8] text-white text-sm font-semibold px-4 py-1.5 rounded-[3px] hover:bg-[#078ae2]">
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

        {{-- ── Aged Payable Card Summary ────────────────────────────────────────── --}}
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
                <input type="hidden" name="selected_vendor_ids" id="selected-vendor-statement-ids-input">
                <div class="p-6 border-gray-200">
                    <div class="flex flex-wrap items-end gap-4">

                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Select Vendor</label>
                            <select name="vendor_id"
                                class="ajax-vendor-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
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
            <div id="vendor-statement-bulk-bar"
                class="hidden bg-[#0078C8] text-white border border-[#0078C8]/30 px-4 py-3 flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm font-medium">
                        <span id="vendor-statement-selected-count" class="font-bold text-lg">0</span> vendor(s) selected
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="downloadSelectedVendorStatements('pdf')"
                            class="px-4 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition">
                            Bulk PDF
                        </button>
                        <button type="button" onclick="downloadSelectedVendorStatements('zip')"
                            class="px-4 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition">
                            Separate PDFs ZIP
                        </button>
                        <button type="button" onclick="emailSelectedVendorStatements()"
                            class="px-4 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition">
                            Email Selected
                        </button>
                        <button type="button" onclick="clearVendorStatementSelection()"
                            class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-sm transition">
                            Clear
                        </button>
                    </div>
                </div>
                <div id="vendor-statement-selected-list" class="flex flex-wrap gap-2"></div>
            </div>
            <div id="vendor-statement-table">
                @include('pages.account.vendor-statement.table')
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.vendor-statements.index') }}',
            tableSelector: '#vendor-statement-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        // Initialize AJAX Vendor Select
        $('.ajax-vendor-select').select2({
            placeholder: '-- select vendor --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.bill.vendors.paginate') }}',
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
                        results: data.data.map(vendor => ({
                            id: vendor.id,
                            text: vendor.name
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });

        const vendorStatementSelectionKey = 'account.vendor-statements.selected';
        const selectedVendorStatements = new Map(JSON.parse(localStorage.getItem(vendorStatementSelectionKey) || '[]'));
        const vendorStatementBulkBar = document.getElementById('vendor-statement-bulk-bar');
        const vendorStatementSelectedCount = document.getElementById('vendor-statement-selected-count');
        const vendorStatementSelectedList = document.getElementById('vendor-statement-selected-list');
        const vendorStatementTable = document.getElementById('vendor-statement-table');

        function persistVendorStatementSelection() {
            localStorage.setItem(vendorStatementSelectionKey, JSON.stringify(Array.from(selectedVendorStatements.entries())));
            syncSelectedVendorStatementIdsInput();
        }

        function syncSelectedVendorStatementIdsInput() {
            const input = document.getElementById('selected-vendor-statement-ids-input');
            if (input) input.value = Array.from(selectedVendorStatements.keys()).join(',');
        }

        function renderSelectedVendorStatementList() {
            vendorStatementSelectedList.innerHTML = '';

            selectedVendorStatements.forEach((label, vendorId) => {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-2 rounded bg-white/15 border border-white/25 px-3 py-1 text-xs font-semibold';

                const text = document.createElement('span');
                text.textContent = label || `Vendor #${vendorId}`;

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'vendor-statement-selected-remove text-white/80 hover:text-white';
                button.dataset.vendorId = vendorId;
                button.textContent = 'x';

                chip.appendChild(text);
                chip.appendChild(button);
                vendorStatementSelectedList.appendChild(chip);
            });
        }

        function updateVendorStatementBulkBar() {
            vendorStatementSelectedCount.textContent = selectedVendorStatements.size;
            vendorStatementBulkBar.classList.toggle('hidden', selectedVendorStatements.size === 0);
            vendorStatementBulkBar.classList.toggle('flex', selectedVendorStatements.size > 0);
            syncSelectedVendorStatementIdsInput();
            renderSelectedVendorStatementList();
        }

        function restoreVendorStatementSelection() {
            document.querySelectorAll('.vendor-statement-checkbox').forEach(checkbox => {
                checkbox.checked = selectedVendorStatements.has(checkbox.value);
            });

            const visibleCheckboxes = Array.from(document.querySelectorAll('.vendor-statement-checkbox'));
            const selectAll = document.getElementById('select-all-vendor-statements');
            if (selectAll) {
                selectAll.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(checkbox => checkbox.checked);
            }
        }

        function clearVendorStatementSelection() {
            selectedVendorStatements.clear();
            persistVendorStatementSelection();
            restoreVendorStatementSelection();
            updateVendorStatementBulkBar();
        }

        function downloadSelectedVendorStatements(mode = 'zip') {
            if (selectedVendorStatements.size === 0) {
                alert('Please select at least one vendor statement.');
                return;
            }

            const params = new URLSearchParams();
            selectedVendorStatements.forEach((label, vendorId) => params.append('vendor_ids[]', vendorId));
            params.set('as_of', document.getElementById('vendor-statement-as-of')?.value || '{{ now()->toDateString() }}');

            const downloadUrl = mode === 'pdf'
                ? `{{ route('account.vendor-statements.bulk-pdf') }}`
                : `{{ route('account.vendor-statements.separate-pdfs') }}`;

            window.location.href = `${downloadUrl}?${params.toString()}`;
        }

        function emailSelectedVendorStatements() {
            if (selectedVendorStatements.size === 0) {
                alert('Please select at least one vendor statement.');
                return;
            }

            if (!confirm('Send statements to the selected vendors?')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ route('account.vendor-statements.selected-email') }}`;

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = `{{ csrf_token() }}`;
            form.appendChild(tokenInput);

            const asOfInput = document.createElement('input');
            asOfInput.type = 'hidden';
            asOfInput.name = 'as_of';
            asOfInput.value = document.getElementById('vendor-statement-as-of')?.value || '{{ now()->toDateString() }}';
            form.appendChild(asOfInput);

            selectedVendorStatements.forEach((label, vendorId) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'vendor_ids[]';
                input.value = vendorId;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }

        document.addEventListener('change', function(event) {
            if (event.target?.id === 'select-all-vendor-statements') {
                document.querySelectorAll('.vendor-statement-checkbox').forEach(checkbox => {
                    checkbox.checked = event.target.checked;
                    if (event.target.checked) {
                        selectedVendorStatements.set(checkbox.value, checkbox.dataset.label);
                    } else {
                        selectedVendorStatements.delete(checkbox.value);
                    }
                });
                persistVendorStatementSelection();
                updateVendorStatementBulkBar();
            }

            if (event.target?.classList?.contains('vendor-statement-checkbox')) {
                if (event.target.checked) {
                    selectedVendorStatements.set(event.target.value, event.target.dataset.label);
                } else {
                    selectedVendorStatements.delete(event.target.value);
                }
                persistVendorStatementSelection();
                restoreVendorStatementSelection();
                updateVendorStatementBulkBar();
            }
        });

        document.addEventListener('click', function(event) {
            if (event.target?.classList?.contains('vendor-statement-selected-remove')) {
                selectedVendorStatements.delete(event.target.dataset.vendorId);
                persistVendorStatementSelection();
                restoreVendorStatementSelection();
                updateVendorStatementBulkBar();
            }
        });

        if (vendorStatementTable) {
            new MutationObserver(() => {
                restoreVendorStatementSelection();
                updateVendorStatementBulkBar();
            }).observe(vendorStatementTable, { childList: true, subtree: true });
        }

        restoreVendorStatementSelection();
        updateVendorStatementBulkBar();
        syncSelectedVendorStatementIdsInput();
    </script>
@endpush
