@extends('layouts.auth')

@section('title', 'Chart of Accounts')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="p-3 mb-2 print:hidden">
            <div class="flex items-center space-x-3">
                @foreach (config('modules.organization_settings') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        
        <div class="mx-auto px-3">
            <div class="bg-white rounded-lg border border-black/10 overflow-hidden shadow-sm">
                <!-- Breadcrumbs & Header Section -->
                <div class="p-5 border-b border-gray-100 print:hidden">
                    <nav class="flex mb-3" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-[#0078C8]">
                                <a href="{{ route('account.organization-settings.index') }}" class="flex items-center">
                                    <span class="material-symbols-outlined text-base mr-1">settings</span>
                                    Settings
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center text-gray-400">
                                    <span class="material-symbols-outlined text-base">chevron_right</span>
                                    <span class="ml-1 text-sm font-semibold text-gray-900">
                                        Chart of Accounts
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h2 class="text-stone-950 text-xl font-normal">
                                Chart of Accounts
                            </h2>
                            <p class="text-sm text-gray-500 font-medium mt-0.5">
                                Manage your chart of accounts to organize and track financial transactions.
                            </p>
                        </div>

                        <!-- Top Action Buttons (Xero Style) -->
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('account.conversion.opening-balances.index') }}" 
                                class="px-4 py-2 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#0062a3] flex items-center gap-1 transition-colors">
                                <span class="fa-solid fa-scale-balanced p-1.5"></span> Opening Balance
                            </a>
                            <a href="{{ route('account.organization-settings.account.create') }}" 
                                class="px-4 py-2 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#0062a3] flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-sm font-bold">add</span> Add Account
                            </a>
                            <a href="{{ route('account.organization-settings.account.create', ['bank' => 1]) }}" 
                                class="px-4 py-2 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#0062a3] flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-sm font-bold">add</span> Add Bank Account
                            </a>
                            <button type="button" onclick="window.print()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 bg-white text-xs font-semibold rounded hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-sm">print</span> Print PDF
                            </button>
                            <button type="button" onclick="openImportModal()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 bg-white text-xs font-semibold rounded hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-sm">upload</span> Import
                            </button>
                            <button type="button" onclick="exportFilteredData()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 bg-white text-xs font-semibold rounded hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-sm">download</span> Export
                            </button>
                            <a href="{{ route('account.organization-settings.account.all.logs') }}" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 bg-white text-xs font-semibold rounded hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-sm">history</span> All Logs
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Xero-style Filter Tabs -->
                <div class="border-b border-gray-200 bg-gray-50/50 print:hidden">
                    <nav class="flex flex-wrap -mb-px text-sm font-medium px-4" aria-label="Tabs">
                        @php
                            $tabs = [
                                'all' => 'All Accounts',
                                'assets' => 'Assets',
                                'liabilities' => 'Liabilities',
                                'equity' => 'Equity',
                                'expenses' => 'Expenses',
                                'cogs' => 'COGS',
                                'revenue' => 'Revenue',
                                'archive' => 'Archive',
                                'deleted_history' => 'Deleted History'
                            ];
                        @endphp
                        @foreach ($tabs as $key => $label)
                            <a href="#" data-tab="{{ $key }}" class="tab-link inline-block p-4 border-b-2 transition-all {{ $currentTab === $key ? 'border-[#0078C8] text-[#0078C8] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </nav>
                </div>

                <!-- Hidden Filter Form & Action/Search Bar -->
                <form method="GET" id="filter-form" class="print:hidden">
                    <input type="hidden" name="tab" id="tab-filter-input" value="{{ $currentTab }}">
                    
                    <div class="flex flex-wrap items-center gap-2 py-3 px-4 bg-gray-50 border-b border-gray-200">
                        @if ($currentTab !== 'deleted_history')
                            <button type="button" id="bulk-delete-btn" disabled class="px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-semibold text-gray-400 cursor-not-allowed hover:bg-red-50 hover:text-red-700 hover:border-red-300 transition-colors">
                                Delete
                            </button>
                            
                            @if ($currentTab === 'archive')
                                <button type="button" id="bulk-restore-btn" disabled class="px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-semibold text-gray-400 cursor-not-allowed hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 transition-colors">
                                    Restore
                                </button>
                            @else
                                <button type="button" id="bulk-archive-btn" disabled class="px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-semibold text-gray-400 cursor-not-allowed hover:bg-orange-50 hover:text-orange-700 hover:border-orange-300 transition-colors">
                                    Archive
                                </button>
                            @endif
                            
                            <!-- Change Tax Rate Dropdown -->
                            <div class="relative inline-block text-left">
                                <button type="button" id="bulk-tax-btn" disabled class="px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-semibold text-gray-400 cursor-not-allowed hover:bg-blue-50 hover:text-[#0078C8] hover:border-[#0078C8]/50 transition-colors inline-flex items-center gap-1">
                                    Change Tax Rate <span class="material-symbols-outlined text-xs">arrow_drop_down</span>
                                </button>
                                <div id="tax-rate-dropdown" class="hidden absolute left-0 mt-1 w-56 bg-white border border-gray-200 rounded shadow-lg z-20 max-h-60 overflow-y-auto">
                                    <div class="py-1">
                                        @foreach ($taxRates as $rate)
                                            <button type="button" onclick="applyBulkTaxRate('{{ $rate->name }}')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 font-medium">
                                                {{ $rate->name }} ({{ number_format($rate->tax_rate, 0) }}%)
                                            </button>
                                        @endforeach
                                        @if ($taxRates->isEmpty())
                                            <button type="button" onclick="applyBulkTaxRate('Tax Exempt (0%)')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 font-medium">
                                                Tax Exempt (0%)
                                            </button>
                                            <button type="button" onclick="applyBulkTaxRate('Tax on Sales (0%)')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 font-medium">
                                                Tax on Sales (0%)
                                            </button>
                                            <button type="button" onclick="applyBulkTaxRate('Tax on Purchases (0%)')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 font-medium">
                                                Tax on Purchases (0%)
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <span id="selected-count-label" class="text-xs text-gray-500 font-semibold ml-2">
                                No accounts selected
                            </span>
                        @endif
                        
                        <!-- Inline search right-aligned -->
                        <div class="ml-auto flex items-center gap-2">
                            <input type="text" name="name" value="{{ request('name') }}" placeholder="Search by name or code..." class="py-1.5 px-3 border border-gray-300 rounded text-xs focus:ring-0 focus:outline-none w-64">
                            <button type="button" id="search-btn" class="px-3.5 py-1.5 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#0062a3] transition-colors">
                                Search
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Table Content -->
                <div id="chart-of-account-table">
                    @include('pages.account.organization-settings.chart-of-account.partial.table')
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto print:hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" onclick="closeImportModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-10" onclick="event.stopPropagation()">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900" id="modal-title">
                                Import Chart of Accounts
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    Download the template to ensure your CSV is formatted correctly.
                                </p>
                                <a href="{{ route('account.organization-settings.account.download-template') }}"
                                    class="text-[#0078C8] text-sm font-semibold hover:underline flex items-center mb-6">
                                    <span class="material-symbols-outlined text-base mr-1">download</span>
                                    Download CSV Template
                                </a>

                                <form id="importForm" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mt-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                                        <input type="file" name="csv_file" accept=".csv"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[#0078C8] hover:file:bg-blue-100 border border-gray-300 rounded-md p-2">
                                    </div>
                                    <div id="importErrors" class="hidden mt-4 p-3 bg-red-50 border border-red-200 rounded text-red-600 text-xs list-disc list-inside">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="button" onclick="submitImport()" id="submitImportBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-base font-medium text-white hover:bg-[#006bb0] focus:outline-none sm:w-auto sm:text-sm">
                        <span id="importBtnText">Import</span>
                        <span id="importBtnLoader" class="hidden ml-2"><i class="fas fa-spinner fa-spin"></i></span>
                    </button>
                    <button type="button" onclick="closeImportModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    @media print {
        header, sidebar, nav, form, .print\:hidden {
            display: none !important;
        }
        body {
            background: white !important;
            color: black !important;
        }
        #chart-of-account-table {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        /* Hide checkbox & actions column when printing */
        th:first-child, td:first-child,
        th:last-child, td:last-child {
            display: none !important;
        }
        /* Style print table */
        table {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        th, td {
            border: 1px solid #ddd !important;
            padding: 8px !important;
        }
    }
    </style>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize ReportFilter
            ReportFilter.init({
                url: '{{ route('account.organization-settings.account.index') }}',
                tableSelector: '#chart-of-account-table',
                paginationSelector: '#pagination-wrapper',
                filterContainer: document.getElementById('filter-form'),
                debounceDelay: 250
            });

            // Tab navigation clicking handler
            $('.tab-link').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                $('#tab-filter-input').val(tab);
                
                $('.tab-link').removeClass('border-[#0078C8] text-[#0078C8] font-semibold')
                              .addClass('border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300');
                $(this).addClass('border-[#0078C8] text-[#0078C8] font-semibold')
                       .removeClass('border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300');
                
                // Hide/show bulk buttons and labels based on tab
                if (tab === 'deleted_history') {
                    $('#bulk-delete-btn, #bulk-archive-btn, #bulk-restore-btn, #bulk-tax-btn, #selected-count-label').addClass('hidden');
                } else {
                    $('#bulk-delete-btn, #bulk-tax-btn, #selected-count-label').removeClass('hidden');
                    if (tab === 'archive') {
                        $('#bulk-restore-btn').removeClass('hidden');
                        $('#bulk-archive-btn').addClass('hidden');
                    } else {
                        $('#bulk-restore-btn').addClass('hidden');
                        $('#bulk-archive-btn').removeClass('hidden');
                    }
                }

                // Trigger reload
                ReportFilter.filter();
            });

            // Table content updated event handler
            document.addEventListener('tableUpdated', function() {
                // Reset select all and selection checkboxes
                $('#selectAll').prop('checked', false);
                $('.account-checkbox').prop('checked', false).trigger('change');
            });

            // Select all checkbox handler
            $(document).on('change', '#selectAll', function() {
                const isChecked = $(this).is(':checked');
                $('.account-checkbox').prop('checked', isChecked).trigger('change');
            });

            // Individual account checkbox selection handler
            $(document).on('change', '.account-checkbox', function() {
                const selected = $('.account-checkbox:checked');
                const count = selected.length;
                
                const bulkDeleteBtn = $('#bulk-delete-btn');
                const bulkArchiveBtn = $('#bulk-archive-btn');
                const bulkRestoreBtn = $('#bulk-restore-btn');
                const bulkTaxBtn = $('#bulk-tax-btn');
                const countLabel = $('#selected-count-label');
                
                if (count > 0) {
                    countLabel.text(count + ' account' + (count > 1 ? 's' : '') + ' selected');
                    
                    let hasSystemAccount = false;
                    selected.each(function() {
                        if ($(this).data('is-system') == '1') {
                            hasSystemAccount = true;
                        }
                    });
                    
                    if (hasSystemAccount) {
                        bulkDeleteBtn.attr('disabled', true).addClass('text-gray-400 cursor-not-allowed').removeClass('text-red-600 hover:bg-red-50 hover:text-red-700 hover:border-red-300');
                        bulkArchiveBtn.attr('disabled', true).addClass('text-gray-400 cursor-not-allowed').removeClass('text-orange-600 hover:bg-orange-50 hover:text-orange-700 hover:border-orange-300');
                    } else {
                        bulkDeleteBtn.removeAttr('disabled').removeClass('text-gray-400 cursor-not-allowed').addClass('text-red-600 hover:bg-red-50 hover:text-red-700 hover:border-red-300');
                        bulkArchiveBtn.removeAttr('disabled').removeClass('text-gray-400 cursor-not-allowed').addClass('text-orange-600 hover:bg-orange-50 hover:text-orange-700 hover:border-orange-300');
                    }
                    
                    bulkRestoreBtn.removeAttr('disabled').removeClass('text-gray-400 cursor-not-allowed').addClass('text-blue-600 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300');
                    bulkTaxBtn.removeAttr('disabled').removeClass('text-gray-400 cursor-not-allowed').addClass('text-[#0078C8] hover:bg-blue-50 hover:border-[#0078C8]/50');
                } else {
                    countLabel.text('No accounts selected');
                    bulkDeleteBtn.attr('disabled', true).addClass('text-gray-400 cursor-not-allowed').removeClass('text-red-600 hover:bg-red-50 hover:text-red-700 hover:border-red-300');
                    bulkArchiveBtn.attr('disabled', true).addClass('text-gray-400 cursor-not-allowed').removeClass('text-orange-600 hover:bg-orange-50 hover:text-orange-700 hover:border-orange-300');
                    bulkRestoreBtn.attr('disabled', true).addClass('text-gray-400 cursor-not-allowed').removeClass('text-blue-600 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300');
                    bulkTaxBtn.attr('disabled', true).addClass('text-gray-400 cursor-not-allowed').removeClass('text-[#0078C8] hover:bg-blue-50 hover:border-[#0078C8]/50');
                    $('#tax-rate-dropdown').addClass('hidden');
                }
            });

            // Action row buttons handlers
            $(document).on('click', '#bulk-delete-btn', function() {
                const selectedIds = $('.account-checkbox:checked').map(function() { return $(this).val(); }).get();
                if (selectedIds.length === 0) return;
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Selected accounts will be permanently deleted! This action is irreversible.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, permanently delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performBulkAction('{{ route('account.organization-settings.account.bulk-delete') }}', { ids: selectedIds });
                    }
                });
            });

            $(document).on('click', '#bulk-archive-btn', function() {
                const selectedIds = $('.account-checkbox:checked').map(function() { return $(this).val(); }).get();
                if (selectedIds.length === 0) return;
                
                Swal.fire({
                    title: 'Archive accounts?',
                    text: "Selected accounts will be soft-deleted and placed in Archive.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f97316',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, archive!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performBulkAction('{{ route('account.organization-settings.account.bulk-archive') }}', { ids: selectedIds });
                    }
                });
            });

            $(document).on('click', '#bulk-restore-btn', function() {
                const selectedIds = $('.account-checkbox:checked').map(function() { return $(this).val(); }).get();
                if (selectedIds.length === 0) return;
                
                Swal.fire({
                    title: 'Restore accounts?',
                    text: "Selected accounts will be restored back to active status.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, restore!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performBulkAction('{{ route('account.organization-settings.account.bulk-restore') }}', { ids: selectedIds });
                    }
                });
            });

            $(document).on('click', '#bulk-tax-btn', function(e) {
                e.stopPropagation();
                $('#tax-rate-dropdown').toggleClass('hidden');
            });
            
            $(document).on('click', function(e) {
                if (!e.target.closest('#bulk-tax-btn') && !e.target.closest('#tax-rate-dropdown')) {
                    $('#tax-rate-dropdown').addClass('hidden');
                }
            });

            // Dropdown button handlers using event delegation
            $(document).on('click', '.dropdown-btn', function(e) {
                e.stopPropagation();
                const menu = $(this).next('.dropdown-menu');
                $('.dropdown-menu').not(menu).addClass('hidden');
                menu.toggleClass('hidden');
            });

            $(document).on('click', function() {
                $('.dropdown-menu').addClass('hidden');
            });
        });

        function applyBulkTaxRate(taxCode) {
            const selectedIds = $('.account-checkbox:checked').map(function() { return $(this).val(); }).get();
            if (selectedIds.length === 0) return;
            
            $('#tax-rate-dropdown').addClass('hidden');
            
            Swal.fire({
                title: 'Set Tax Rate?',
                text: `Set tax rate to "${taxCode}" for selected accounts?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, change tax rate!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkAction('{{ route('account.organization-settings.account.bulk-change-tax-rate') }}', { ids: selectedIds, tax_code: taxCode });
                }
            });
        }

        function archiveSingleAccount(id) {
            Swal.fire({
                title: 'Archive account?',
                text: "This account will be soft-deleted and placed in Archive.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f97316',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, archive!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkAction('{{ route('account.organization-settings.account.bulk-archive') }}', { ids: [id] });
                }
            });
        }

        function restoreSingleAccount(id) {
            Swal.fire({
                title: 'Restore account?',
                text: "This account will be restored back to active status.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, restore!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkAction('{{ route('account.organization-settings.account.bulk-restore') }}', { ids: [id] });
                }
            });
        }

        function performBulkAction(url, data) {
            document.getElementById('page-loader')?.classList.remove('hidden');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        ReportFilter.filter();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'An error occurred'
                    });
                }
            })
            .catch(error => {
                console.error('Bulk action error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred.'
                });
            })
            .finally(() => {
                document.getElementById('page-loader')?.classList.add('hidden');
            });
        }

        // Import Modal Functions
        function openImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
            document.getElementById('importErrors').classList.add('hidden');
            document.getElementById('importErrors').innerHTML = '';
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.add('hidden');
        }

        function submitImport() {
            const form = document.getElementById('importForm');
            const formData = new FormData(form);
            const btn = document.getElementById('submitImportBtn');
            const text = document.getElementById('importBtnText');
            const loader = document.getElementById('importBtnLoader');
            const errorDiv = document.getElementById('importErrors');

            // Reset UI
            errorDiv.classList.add('hidden');
            errorDiv.innerHTML = '';
            btn.disabled = true;
            text.classList.add('hidden');
            loader.classList.remove('hidden');

            fetch('{{ route('account.organization-settings.account.import') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json().then(data => ({
                    status: response.status,
                    body: data
                })))
                .then(result => {
                    if (result.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.body.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else if (result.status === 422) {
                        errorDiv.classList.remove('hidden');
                        if (result.body.errors && Array.isArray(result.body.errors)) {
                            const ul = document.createElement('ul');
                            result.body.errors.forEach(err => {
                                const li = document.createElement('li');
                                li.textContent = err;
                                ul.appendChild(li);
                            });
                            errorDiv.appendChild(ul);
                        } else {
                            errorDiv.textContent = result.body.message || 'Validation failed';
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.body.message || 'An error occurred during import'
                        });
                    }
                })
                .catch(error => {
                    console.error('Import Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred.'
                    });
                })
                .finally(() => {
                    btn.disabled = false;
                    text.classList.remove('hidden');
                    loader.classList.add('hidden');
                });
        }

        function exportFilteredData() {
            const form = document.getElementById('filter-form');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();
            window.location.href = '{{ route('account.organization-settings.account.export') }}?' + params;
        }
    </script>
@endpush
