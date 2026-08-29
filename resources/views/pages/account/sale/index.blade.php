@extends('layouts.auth')

@section('title', 'Customer Invoice')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['route'] . '*') }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden ">

                    <!-- Header -->
                    <div
                        class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-3">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Customer Invoice</h2>
                        <div class="grid grid-cols-1 md:grid-cols-5 lg:grid-cols-5 gap-2 relative ">
                            <x-columns :columns="[
                                'in_date' => 'Date',
                                'in_reference_no' => 'Reference No',
                                'in_invoice_no' => 'Invoice No',
                                'in_customer_name' => 'Customer Name',
                                'in_description' => 'Description',
                                'in_total_amount' => 'Total Amount',
                                'in_total_after_discount' => 'Total After Discount / Tax',
                                'in_remaining_amount' => 'Remaining Amount',
                                'in_created_by' => 'Created by',
                                'in_updated_by' => 'Updated by',
                                'in_status' => 'Status',
                            ]" name="account_sale_invoice_columns" />

                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-5 py-1.5 border rounded border-[#0078C8] text-[#0078C8] hover:bg-[#0078C8] hover:text-white text-sm font-medium transition-all duration-200 flex items-center gap-2">
                                    Export
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                    <ul class="py-1 text-sm">
                                        <li>
                                            <button onclick="startExport('pdf','sale')" data-url=""
                                                class="w-full text-left px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="startExport('xlsx','sale')"
                                                class="w-full text-left px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Excel
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Insert CSV Button -->
                            <button onclick="openImportModal()"
                                class="px-4 text-center py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center justify-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                IMPORT CSV
                            </button>

                            <!-- Sale Approvals Link -->
                            <a href="{{ route('account.approvals.sales.index') }}"
                                class="px-4 text-center py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4 mr-1">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg> APPROVALS
                            </a>

                            <!-- Add New Button -->
                            <a href="{{ route('account.sale-invoice.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4 mr-1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg> ADD NEW
                            </a>
                        </div>
                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <!-- Filters Section -->
                        <form method="GET" id="filter-form">
                            <div class="p-6 border-gray-200">
                                <div class="flex flex-wrap items-end gap-4">
                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                                            class=" date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    </div>

                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    </div>

                                    <div class="flex-1 min-w-[200px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Select Customer</label>
                                        <select name="customer_id"
                                            class="ajax-customer-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        </select>
                                    </div>

                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Invoice No</label>
                                        <input type="text" name="invoice_no" value="{{ request('invoice_no') }}"
                                            placeholder="Enter invoice no"
                                            class="  w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    </div>

                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                                        <select name="approval_status"
                                            class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <option value="">All Status</option>
                                            <option value="draft"
                                                {{ request('approval_status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                            <option value="submitted"
                                                {{ request('approval_status') == 'submitted' ? 'selected' : '' }}>Pending
                                                Approval</option>
                                            <option value="approved"
                                                {{ request('approval_status') == 'approved' ? 'selected' : '' }}>Approved
                                            </option>
                                            <option value="rejected"
                                                {{ request('approval_status') == 'rejected' ? 'selected' : '' }}>Rejected
                                            </option>
                                        </select>
                                    </div>

                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Payment</label>
                                        <select name="payment_status" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm">
                                            <option value="">All Payments</option>
                                            @foreach (['unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid', 'overpaid' => 'Overpaid'] as $value => $label)
                                                <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
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

                        <!-- Table -->
                        <div id="sale-table">
                            @include('pages.account.sale.partials.table')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <x-acccount.remaining-amount />

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Import Sales CSV</h3>
                    <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                            required>
                        <p class="text-xs text-gray-500 mt-1">Upload a CSV file with sales data</p>
                    </div>

                    <div class="mb-4">
                        <a href="{{ route('account.sale-invoice.download-template') }}"
                            class="inline-flex items-center px-4 py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            Download Template
                        </a>
                    </div>

                    <div id="importProgress" class="hidden mb-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold inline-block text-[#0078C8]">
                                Importing...
                            </span>
                            <span id="progressPercent" class="text-xs font-semibold inline-block text-[#0078C8]">
                                0%
                            </span>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200">
                            <div id="progressBar" style="width:0%"
                                class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-[#0078C8] transition-all duration-300">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeImportModal()" id="cancelImportBtn"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" id="importSubmitBtn"
                            class="px-4 py-2 bg-[#0078C8] text-white rounded hover:bg-[#006bb0] transition-colors">
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <x-export-column-modal />
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>
    <script>
        // Get modal element
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');

        // Select all checkbox
        // document.getElementById('select-all').addEventListener('change', function() {
        //     const checkboxes = document.querySelectorAll('.sale-checkbox');
        //     checkboxes.forEach(checkbox => {
        //         checkbox.checked = this.checked;
        //     });
        // });

        // Dropdown button handlers
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                // Toggle current dropdown
                menu.classList.toggle('hidden');
            }
        });

        // Delete button click handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-btn')) {
                e.preventDefault();
                e.stopPropagation();
                // Show modal
                deleteModal.classList.remove('hidden');
                // Close all dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Cancel delete
        if (cancelDelete) {
            cancelDelete.addEventListener('click', () => {
                deleteModal.classList.add('hidden');
            });
        }

        // Confirm delete
        if (confirmDelete) {
            confirmDelete.addEventListener('click', () => {
                alert('Item deleted successfully!');
                deleteModal.classList.add('hidden');
            });
        }

        // Close modal when clicking outside
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                }
            });
        }

        // Close all dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Export dropdown toggle
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            // Close all other dropdowns
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        }

        // Close export dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // Import modal functions
        function openImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.add('hidden');
            document.getElementById('importForm').reset();
            document.getElementById('importProgress').classList.add('hidden');
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressPercent').textContent = '0%';
            document.getElementById('importSubmitBtn').disabled = false;
        }

        // Handle import form submission
        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const fileInput = document.getElementById('csv_file');
            const file = fileInput.files[0];
            if (!file) return;

            const submitBtn = document.getElementById('importSubmitBtn');
            const progressDiv = document.getElementById('importProgress');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');

            submitBtn.disabled = true;
            progressDiv.classList.remove('hidden');

            Papa.parse(file, {
                header: true,
                skipEmptyLines: true,
                transformHeader: function(h) {
                    return h.trim().toLowerCase().replace(/ /g, '_');
                },
                complete: async function(results) {
                    const data = results.data;
                    const chunkSize = 1000;
                    const totalChunks = Math.ceil(data.length / chunkSize);

                    let totalProcessed = 0;
                    let totalSkipped = 0;
                    let allErrors = [];

                    for (let i = 0; i < totalChunks; i++) {
                        const chunk = data.slice(i * chunkSize, (i + 1) * chunkSize);

                        try {
                            const response = await fetch(
                                '{{ route('account.sale-invoice.import.chunk') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        rows: chunk
                                    })
                                });

                            const result = await response.json();
                            if (!result.success) {
                                throw new Error(result.message || 'Error processing chunk');
                            }

                            totalProcessed += (result.processed || 0);
                            totalSkipped += (result.skipped || 0);
                            if (result.errors && result.errors.length > 0) {
                                allErrors = allErrors.concat(result.errors);
                            }

                            const percent = Math.round(((i + 1) / totalChunks) * 100);
                            progressBar.style.width = percent + '%';
                            progressPercent.textContent = percent + '%';

                        } catch (error) {
                            console.error('Error:', error);
                            alert('Import failed at chunk ' + (i + 1) + ': ' + error.message);
                            submitBtn.disabled = false;
                            return;
                        }
                    }

                    let summary =
                        `Import Complete!\n\nProcessed: ${totalProcessed}\nSkipped: ${totalSkipped}`;
                    if (allErrors.length > 0) {
                        summary += `\n\nErrors:\n- ${allErrors.slice(0, 10).join('\n- ')}`;
                        if (allErrors.length > 10) {
                            summary += `\n... and ${allErrors.length - 10} more errors.`;
                        }
                    }

                    alert(summary);
                    closeImportModal();
                    location.reload();
                }
            });
        });
    </script>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.sale-invoice.index') }}',
            tableSelector: '#sale-table',
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
    </script>
@endpush
