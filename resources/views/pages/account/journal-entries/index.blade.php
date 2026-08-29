@extends('layouts.auth')

@section('title', 'Journal Entries')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">
                    <!-- Header -->
                    <div
                        class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-3">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Journal Entries</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative">
                            <div></div>
                            <x-columns :columns="[
                                'j_date' => 'Date',
                                'j_unique_no' => 'Reference No',
                                'j_narration' => 'Narration',
                                'j_item' => 'Item',
                                'j_description' => 'Description',
                                'j_account' => 'Account',
                                'j_debit' => 'Debit',
                                'j_credit' => 'Credit',
                            ]" name="journal_entries" />

                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-5 py-1.5 border border-[#0078C8] text-[#0078C8] rounded-lg hover:bg-[#0078C8] hover:text-white text-sm font-medium transition-all duration-200 flex items-center gap-2">
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
                                            <button onclick="openExportModal('journal-entry', '')" data-url=""
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
                                            <button onclick="openExportModal('journal-entry', '')"
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
                            <a href="{{ route('account.journal-entries.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>ADD NEW
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-md border border-[#C0BFBF] mt-2">
                        <!-- Filters -->
                        <form method="GET" id="filter-form">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex flex-wrap items-end gap-4 justify-end">

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Search</label>
                                        <input type="search" name="search" placeholder="Search by any" data-filter
                                            value="{{ request('search') }}"
                                            class="w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">From Date</label>
                                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                                            data-filter
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">To Date</label>
                                        <input type="date" name="end_date" value="{{ request('end_date') }}" data-filter
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Chart of Account</label>
                                        <x-acccount.coa.coa-select name="coa" :selected="request('coa')" data-filter />
                                    </div>

                                    <div class="flex-1">
                                        <x-default-page />
                                    </div>

                                    <x-filter-button />

                                </div>
                            </div>
                        </form>

                        <!-- Table -->
                        <div id="journal-entries-table">
                            @include('pages.account.journal-entries.table')
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />
    <x-export-column-modal />
@endsection

@push('scripts')
    <script>
        // Get modal element
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');
        let currentDeleteForm = null;

        // Select all checkbox
        document.getElementById('select-all')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

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

                currentDeleteForm = e.target.closest('.delete-form');
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
                currentDeleteForm = null;
            });
        }

        // Confirm delete
        if (confirmDelete) {
            confirmDelete.addEventListener('click', () => {
                if (currentDeleteForm) {
                    currentDeleteForm.submit();
                }
                deleteModal.classList.add('hidden');
                currentDeleteForm = null;
            });
        }

        // Close modal when clicking outside
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                    currentDeleteForm = null;
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

        // Row grouping highlight
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#journal-entries-table-body tr');
            let currentUniqueNo = '';

            rows.forEach(row => {
                const uniqueNo = row.dataset.uniqueNo;
                if (uniqueNo !== currentUniqueNo) {
                    row.classList.add('border-t-2', 'border-gray-300');
                    currentUniqueNo = uniqueNo;
                }
            });
        });

        // Quick filter by reference number
        function filterByReference(refNo) {
            const searchInput = document.querySelector('input[name="search"]');
            searchInput.value = refNo;
            document.getElementById('filter-form').submit();
        }

        // Quick view totals
        function showEntryTotals(uniqueNo) {
            const rows = document.querySelectorAll(`tr[data-unique-no="${uniqueNo}"]`);
            let totalDebit = 0;
            let totalCredit = 0;

            rows.forEach(row => {
                const debit = parseFloat(row.querySelector('td:nth-child(8)').textContent.replace(/,/g, '')) || 0;
                const credit = parseFloat(row.querySelector('td:nth-child(9)').textContent.replace(/,/g, '')) || 0;
                totalDebit += debit;
                totalCredit += credit;
            });

            alert(`Entry ${uniqueNo} Totals:\nDebit: ${totalDebit.toFixed(2)}\nCredit: ${totalCredit.toFixed(2)}`);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.querySelector('input[name="search"]').focus();
            }

            // Escape to close dropdowns
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Auto-refresh page every 30 seconds (optional)
        // setInterval(() => {
        //     window.location.reload();
        // }, 30000);

        // Print functionality
        function printJournalEntries() {
            const printContent = document.querySelector('.bg-white.rounded-md').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Journal Entries Report</title>
                            <style>
                                body { font-family: Arial, sans-serif; margin: 20px; }
                                table { width: 100%; border-collapse: collapse; }
                                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                                th { background-color: #f8f9fa; }
                                .text-green-600 { color: #059669; }
                                .text-red-600 { color: #dc2626; }
                                .text-right { text-align: right; }
                            </style>
                        </head>
                        <body>
                            <h2>Journal Entries Report</h2>
                            <p>Generated on: ${new Date().toLocaleString()}</p>
                            ${printContent}
                        </body>
                        </html>
                    `;

            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }

        // Add print button event listener if you add a print button
        // document.getElementById('print-btn')?.addEventListener('click', printJournalEntries);
    </script>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.journal-entries.index') }}',
            tableSelector: '#journal-entries-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush

<style>
    /* Additional styling for better visual hierarchy */
    tr[data-unique-no] {
        transition: background-color 0.2s ease;
    }

    tr[data-unique-no]:hover {
        background-color: #f9fafb;
    }

    .dropdown-menu {
        animation: fadeIn 0.2s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Color coding for debit/credit */
    .debit-positive {
        background-color: #f0fdf4;
    }

    .credit-positive {
        background-color: #fef2f2;
    }
</style>
