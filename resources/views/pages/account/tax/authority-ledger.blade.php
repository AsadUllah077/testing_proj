@extends('layouts.auth')

@section('title', 'Tax Authority Ledger')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-2">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Tax Authority Ledger</h2>
                    <p class="text-xs text-gray-500 mt-1">Comprehensive overview of all tax transactions and balances.</p>
                </div>
                <div class="mt-3 md:mt-0 flex gap-3">
                    <div class="relative">
                        <button onclick="toggleDropdown('exportDropdown')"
                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-xs font-normal rounded hover:bg-[#0078C8] hover:text-white transition-all duration-200 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            EXPORT
                        </button>
                        <div id="exportDropdown"
                            class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                            <button type="button"
                                class="export-btn block w-full text-left px-4 py-2 hover:bg-[#0078C8] hover:text-white"
                                onclick="return false;" data-export-type="tax-authority-ledger" data-export-format="pdf">
                                <i class="far fa-file-pdf mr-2"></i> PDF
                            </button>
                            <button type="button"
                                class="export-btn block w-full text-left px-4 py-2 hover:bg-[#0078C8] hover:text-white"
                                onclick="return false;" data-export-type="tax-authority-ledger" data-export-format="xlsx">
                                <i class="fas fa-file-excel mr-2"></i> Excel
                            </button>
                        </div>
                    </div>
                    <a href="{{ route('account.tax-periods.index') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-normal rounded hover:bg-gray-50 transition-all duration-200">
                        BACK TO PERIODS
                    </a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="mb-6 p-4 bg-white rounded-lg border border-black/10">
                <form class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end" id="filter-form">

                    <div class="md:col-span-2">
                        <label for="name" class="block text-xs font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" id="name" value="{{ request('name') }}"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                            placeholder="Filter by Name">
                    </div>

                    <div class="md:col-span-2">
                        <label for="type" class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                        <select name="type" id="type"
                            class="select2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                            <option value="">All Types</option>
                            <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="quarterly" {{ request('type') == 'quarterly' ? 'selected' : '' }}>Quarterly
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="status" class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status"
                            class="select2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                            <option value="">All Statuses</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="settled" {{ request('status') == 'settled' ? 'selected' : '' }}>Settled</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="start_date" class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                            class="date block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                    </div>

                    <div class="md:col-span-2">
                        <label for="end_date" class="block text-xs font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                            class="date block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                    </div>

                    <div class="md:col-span-1">
                        <x-default-page />
                    </div>

                    <div class="md:col-span-1 flex gap-2">
                        <button type="button" id="search-btn"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[#0078C8] hover:bg-[#0078C8]/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span id="search-text">Search</span>
                            <span id="search-loader" class="hidden ml-2">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                        <button type="button" id="reset-btn"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            X
                        </button>
                    </div>
                </form>
            </div>

            <!-- Content Wrapper -->
            <div id="authority-ledger-content">
                @include('pages.account.tax.periods.partials.authority-ledger-table')
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Function to toggle dropdown visibility
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
            }
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // Initialize ReportFilter for AJAX filtering
        ReportFilter.init({
            url: '{{ route('account.tax-periods.authority-ledger') }}',
            summarySelector: '#authority-ledger-summary',
            tableSelector: '#authority-ledger-content',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        // Handle tax authority ledger export buttons
        document.addEventListener('DOMContentLoaded', function() {
            const exportButtons = document.querySelectorAll('.export-btn');

            exportButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const exportType = this.getAttribute('data-export-type');
                    const exportFormat = this.getAttribute('data-export-format');

                    // Close dropdown
                    document.getElementById('exportDropdown').classList.add('hidden');

                    // Show notification
                    showNotification();
                    resetNotificationProgress();

                    // Get current filter values
                    const name = document.getElementById('name').value;
                    const type = document.getElementById('type').value;
                    const status = document.getElementById('status').value;
                    const start_date = document.getElementById('start_date').value;
                    const end_date = document.getElementById('end_date').value;

                    // Make AJAX request to start export
                    fetch('{{ route('export') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                format: exportFormat,
                                type: exportType,
                                name: name,
                                type_filter: type, // named differently to avoid confusion with export type
                                status: status,
                                start_date: start_date,
                                end_date: end_date
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                currentJobId = data.job_id;
                                localStorage.setItem('pendingExportJobId', currentJobId);
                                checkExportProgress();
                            } else {
                                showNotificationError(data.message);
                            }
                        })
                        .catch(error => {
                            showNotificationError('Failed to start export: ' + error.message);
                        });
                });
            });
        });
    </script>
@endsection
