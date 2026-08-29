<div id="exportModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50 bg-opacity-75 transition-opacity" onclick="closeExportModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <!-- Modal Panel -->
        <div id="exportModalBox"
            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg scale-95">
            <!-- Header -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="exportModalTitle">
                        Export Options
                    </h3>
                    <button type="button" onclick="closeExportModal()"
                        class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-sm text-gray-500">Select the columns you want to export</p>
            </div>

            <!-- Form -->
            <form id="exportForm" method="GET">
                <input type="hidden" id="exportType" name="type" value="">
                <input type="hidden" id="exportId" name="id" value="">

                <div class="px-4 py-5 sm:p-6">
                    <!-- Export Format -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="format" value="pdf" checked
                                    class="form-radio text-[#0078C8]" onclick="updateExportRoute()">
                                <span class="ml-2">PDF</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="format" value="excel" class="form-radio text-[#0078C8]"
                                    onclick="updateExportRoute()">
                                <span class="ml-2">Excel</span>
                            </label>
                        </div>
                    </div>

                    <div id="exportPaperSizeWrapper" class="mb-4 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Paper Size</label>
                        <select id="exportPaperSize"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            @foreach (\App\Support\PdfPaperSize::options() as $key => $label)
                                <option value="{{ $key }}" {{ $key === \App\Support\PdfPaperSize::DEFAULT_INVOICE ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Column Selection -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Select Columns</label>
                            <button type="button" onclick="toggleAllColumns()"
                                class="text-xs text-[#0078C8] hover:underline">
                                Select All
                            </button>
                        </div>
                        <div class="border rounded-md max-h-64 overflow-y-auto" id="columnsContainer">
                            <div class="p-4 text-center text-gray-500">
                                <svg class="animate-spin h-8 w-8 mx-auto text-[#0078C8]"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <p class="mt-2">Loading columns...</p>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Select at least one column to export</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <button type="submit" id="exportBtn"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-[#0078C8] px-4 py-2 text-base font-medium text-white hover:bg-[#006bb3] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Export
                    </button>
                    <button type="button" onclick="closeExportModal()"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentExportType = '';
    let currentExportId = '';
    let availableColumns = [];

    function openExportModal(type, id) {
        currentExportType = type;
        currentExportId = id || '';

        document.getElementById('exportType').value = type;
        document.getElementById('exportId').value = id || '';

        // Update modal title
        let title = 'Export';
        if (type === 'sale') title = 'Export Sale Invoice';
        else if (type === 'bill') title = 'Export Bill';
        else if (type === 'credit-note') title = 'Export Credit Note';
        else if (type === 'debit-note') title = 'Export Debit Note';
        else if (type === 'journal-entry') title = 'Export Journal Entry';
        else if (type === 'journal-report') title = 'Export Journal Report';
        else if (type === 'ledger-report') title = 'Export Ledger Report';
        document.getElementById('exportModalTitle').textContent = title;
        updatePaperSizeVisibility();

        // Show modal first
        const modal = document.getElementById('exportModal');
        const modalBox = document.getElementById('exportModalBox');

        modal.classList.remove('hidden');

        setTimeout(() => {
            modal.classList.add('opacity-100');
            modalBox.classList.remove('scale-95');
            modalBox.classList.add('scale-100');
        }, 10);

        // Fetch columns from database
        fetchColumnsFromDatabase(type);
    }

    function closeExportModal() {
        const modal = document.getElementById('exportModal');
        const modalBox = document.getElementById('exportModalBox');

        modal.classList.remove('opacity-100');
        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    function fetchColumnsFromDatabase(type) {
        const container = document.getElementById('columnsContainer');

        // Show loading
        container.innerHTML = `
            <div class="p-4 text-center text-gray-500">
                <svg class="animate-spin h-8 w-8 mx-auto text-[#0078C8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2">Loading columns...</p>
            </div>
        `;

        // Fetch columns from the API
        let route = '';
        if (type === 'sale') {
            route = '{{ route('account.sale-invoice.export-columns') }}';
        } else if (type === 'bill') {
            route = '{{ route('account.bill-invoice.export-columns') }}';
        } else if (type === 'credit-note') {
            route = '{{ route('account.sale.credit-notes.export-columns') }}';
        } else if (type === 'debit-note') {
            route = '{{ route('account.debit-note.export-columns') }}';
        } else if (type === 'journal-entry') {
            route = '{{ route('account.journal-entries.export-columns') }}';
        } else if (type === 'journal-report') {
            route = '{{ route('account.report.journal.export-columns') }}';
        } else if (type === 'ledger-report') {
            route = '{{ route('account.report.ledger.export-columns') }}';
        }

        fetch(route)
            .then(response => response.json())
            .then(data => {
                availableColumns = data;
                renderColumns();
                updateExportRoute();
            })
            .catch(error => {
                console.error('Error fetching columns:', error);
                container.innerHTML = `
                    <div class="p-4 text-center text-red-500">
                        <p>Error loading columns. Please try again.</p>
                    </div>
                `;
            });
    }

    function renderColumns() {
        const container = document.getElementById('columnsContainer');
        container.innerHTML = '';

        if (availableColumns.length === 0) {
            container.innerHTML = '<div class="p-4 text-center text-gray-500">No columns available</div>';
            return;
        }

        availableColumns.forEach((col, index) => {
            const div = document.createElement('div');
            div.className = 'flex items-center px-3 py-2 hover:bg-gray-50 border-b last:border-b-0';
            div.innerHTML = `
                <input type="checkbox" 
                    id="col_${col.key}" 
                    name="columns[]" 
                    value="${col.key}" 
                    ${col.checked ? 'checked' : ''}
                    class="h-4 w-4 text-[#0078C8] border-gray-300 rounded focus:ring-[#0078C8]"
                    onchange="updateColumnChecked('${col.key}', this.checked)">
                <label for="col_${col.key}" class="ml-3 text-sm text-gray-700 cursor-pointer">${col.label}</label>
            `;
            container.appendChild(div);
        });
    }

    function updateColumnChecked(key, checked) {
        const col = availableColumns.find(c => c.key === key);
        if (col) {
            col.checked = checked;
        }
    }

    function toggleAllColumns() {
        const allChecked = availableColumns.every(col => col.checked);
        availableColumns.forEach(col => col.checked = !allChecked);
        renderColumns();
    }

    function getReportFilterParams() {
        // Collect current filter params from the filter form (for report pages)
        const filterForm = document.getElementById('filter-form');
        if (!filterForm) return '';

        const formData = new FormData(filterForm);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }

        return params.toString();
    }

    function updateExportRoute() {
        const format = document.querySelector('input[name="format"]:checked').value;
        const type = currentExportType;
        const id = currentExportId;
        updatePaperSizeVisibility();

        let route = '';
        if (type === 'sale') {
            route = format === 'pdf' ?
                '{{ route('account.sale-invoice.export-pdf', ':id') }}'.replace(':id', id) :
                '{{ route('account.sale-invoice.export-excel', ':id') }}'.replace(':id', id);
        } else if (type === 'bill') {
            route = format === 'pdf' ?
                '{{ route('account.bill-invoice.export-pdf', ':id') }}'.replace(':id', id) :
                '{{ route('account.bill-invoice.export-excel', ':id') }}'.replace(':id', id);
        } else if (type === 'credit-note') {
            route = format === 'pdf' ?
                '{{ route('account.sale.credit-notes.export-pdf', ':id') }}'.replace(':id', id) :
                '{{ route('account.sale.credit-notes.export-excel', ':id') }}'.replace(':id', id);
        } else if (type === 'debit-note') {
            route = format === 'pdf' ?
                '{{ route('account.debit-note.export-pdf', ':id') }}'.replace(':id', id) :
                '{{ route('account.debit-note.export-excel', ':id') }}'.replace(':id', id);
        } else if (type === 'journal-entry') {
            route = format === 'pdf' ?
                '{{ route('account.journal-entries.export-pdf') }}' :
                '{{ route('account.journal-entries.export-excel') }}';
        } else if (type === 'journal-report') {
            route = format === 'pdf' ?
                '{{ route('account.report.journal.export.pdf') }}' :
                '{{ route('account.report.journal.export.csv') }}';
        } else if (type === 'ledger-report') {
            route = format === 'pdf' ?
                '{{ route('account.report.ledger.export.pdf') }}' :
                '{{ route('account.report.ledger.export.csv') }}';
        }

        document.getElementById('exportForm').action = route;
    }

    function updatePaperSizeVisibility() {
        const format = document.querySelector('input[name="format"]:checked')?.value || 'pdf';
        const showPaperSize = format === 'pdf' && ['sale', 'bill', 'credit-note', 'debit-note'].includes(currentExportType);
        const paperSizeWrapper = document.getElementById('exportPaperSizeWrapper');

        if (paperSizeWrapper) {
            paperSizeWrapper.classList.toggle('hidden', !showPaperSize);
        }

        const paperSizeSelect = document.getElementById('exportPaperSize');
        if (paperSizeSelect && showPaperSize) {
            paperSizeSelect.value = 'long_invoice';
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('exportModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeExportModal();
            }
        }
    });

    // Close modal on backdrop click
    document.getElementById('exportModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeExportModal();
        }
    });

    // Form submission handler
    document.getElementById('exportForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const selectedColumns = availableColumns.filter(col => col.checked);
        if (selectedColumns.length === 0) {
            alert('Please select at least one column to export');
            return;
        }

        const format = document.querySelector('input[name="format"]:checked').value;
        const type = currentExportType;
        const id = currentExportId;

        let route = '';
        if (type === 'sale') {
            route = format === 'pdf' ?
                '{{ route('account.sale-invoice.export-pdf', ':id') }}'.replace(':id', id) :
                '{{ route('account.sale-invoice.export-excel', ':id') }}'.replace(':id', id);
        } else if (type === 'bill') {
            route = format === 'pdf' ?
                '{{ route('account.bill-invoice.export-pdf', ':id') }}'.replace(':id', id) :
                '{{ route('account.bill-invoice.export-excel', ':id') }}'.replace(':id', id);
        } else if (type === 'credit-note') {
            route = format === 'pdf' ?
                '{{ route('account.sale.credit-notes.export-pdf', ':id') }}'.replace(':id', id) :
                '{{ route('account.sale.credit-notes.export-excel', ':id') }}'.replace(':id', id);
        } else if (type === 'debit-note') {
            route = format === 'pdf' ?
                '{{ route('account.debit-note.export-pdf', ':id') }}'.replace(':id', id) :
                '{{ route('account.debit-note.export-excel', ':id') }}'.replace(':id', id);
        } else if (type === 'journal-entry') {
            route = format === 'pdf' ?
                '{{ route('account.journal-entries.export-pdf') }}' :
                '{{ route('account.journal-entries.export-excel') }}';
        } else if (type === 'journal-report') {
            route = format === 'pdf' ?
                '{{ route('account.report.journal.export.pdf') }}' :
                '{{ route('account.report.journal.export.csv') }}';
        } else if (type === 'ledger-report') {
            route = format === 'pdf' ?
                '{{ route('account.report.ledger.export.pdf') }}' :
                '{{ route('account.report.ledger.export.csv') }}';
        }

        // Build URL with selected columns
        const columnsParam = selectedColumns.map(col => col.key).join(',');

        // For report types, include filter params from the page
        let filterParams = '';
        if (type === 'journal-report' || type === 'ledger-report') {
            filterParams = getReportFilterParams();
        }

        let finalUrl = route + '?columns=' + columnsParam;
        if (filterParams) {
            finalUrl += '&' + filterParams;
        }
        if (format === 'pdf' && ['sale', 'bill', 'credit-note', 'debit-note'].includes(type)) {
            finalUrl += '&paper_size=' + encodeURIComponent(document.getElementById('exportPaperSize')?.value || 'long_invoice');
        }

        // Redirect with columns parameter
        window.location.href = finalUrl;

        closeExportModal();
    });
</script>
