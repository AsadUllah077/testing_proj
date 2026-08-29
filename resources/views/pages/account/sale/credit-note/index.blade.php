@extends('layouts.auth')

@section('title', 'Credit Note')

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
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Credit Note</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 relative ">
                            <x-columns :columns="[
                                'cn_date' => 'Date',
                                'cn_reference_no' => 'Reference No',
                                'cn_invoice_no' => 'Invoice No',
                                'cn_customer_name' => 'Customer Name',
                                'cn_description' => 'Description',
                                'cn_total_amount' => 'Total Amount',
                                'cn_paid_amount' => 'Refunded Amount',
                                'cn_allocated_amount' => 'Allocated Amount',
                                'cn_remaining_amount' => 'Available Credit',
                                'cn_created_by' => 'Created by',
                                'cn_updated_by' => 'Updated by',
                            ]" name="account_credit_note_columns" />

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
                                            <button onclick="startExport('pdf','credit-note')" data-url=""
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
                                            <button onclick="startExport('xlsx','credit-note')"
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

                            <!-- Add New Button -->
                            <a href="{{ route('account.sale.credit-notes.create') }}"
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
                                        <input type="text" name="search" value="{{ request('search') }}"
                                            placeholder="Search invoice no, ref no..."
                                            class="  w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
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
                        <div id="credit-note-table">
                            @include('pages.account.sale.credit-note.partials.table')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />
    <x-history-model />
    <x-acccount.remaining-amount-credit-note />
    <x-export-column-modal />

@endsection

@push('scripts')
    <script>
        // Get modal element
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');
        let selectedCreditNoteId = null;

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
                selectedCreditNoteId = e.target.closest('.delete-btn').getAttribute('data-id');
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
                selectedCreditNoteId = null;
            });
        }

        // Confirm delete
        if (confirmDelete) {
            confirmDelete.addEventListener('click', () => {
                if (selectedCreditNoteId) {
                    deleteCreditNote(selectedCreditNoteId);
                }
            });
        }

        // Delete credit note via AJAX
        function deleteCreditNote(id) {
            fetch(`{{ url('account/sale/credit-notes/delete') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the credit note.');
                })
                .finally(() => {
                    deleteModal.classList.add('hidden');
                    selectedCreditNoteId = null;
                });
        }

        // Close modal when clicking outside
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                    selectedCreditNoteId = null;
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

        // History modal function
        function openHistoryModal(creditNoteId) {
            // Implement history modal functionality here
            alert('History for credit note ID: ' + creditNoteId);
        }
    </script>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.sale.credit-notes.index') }}',
            tableSelector: '#credit-note-table',
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
