@extends('layouts.auth')

@section('title', 'General Ledger')

@push('styles')
    @include('pages.account.reports.partials._xero-report-styles')
    <style>
        .invoice-no {
            color: #0078C8;
            cursor: pointer;
            transition: color 0.15s;
        }

        .invoice-no:hover {
            color: #005a9e;
            text-decoration: underline;
        }
    </style>
@endpush

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.reports') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden ">
                    <!-- Header -->
                    <div x-data="{}"
                        class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">General Ledger</h2>
                        <div class="flex items-center gap-2 relative ">

                            <x-update-button />

                            <!-- Report Options Button -->
                            <button @click="$dispatch('open-report-filter')"
                                class="px-4 py-1 bg-white border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center">
                                <span class="material-symbols-outlined text-xs font-normal">filter_list</span>
                            </button>

                            <x-columns :columns="[
                                'date' => 'Date',
                                'journal_no' => 'Journal No',
                                'description' => 'Description',
                                'debit' => 'Debit',
                                'credit' => 'Credit',
                                'running_balance' => 'Running Balance',
                                'gross' => 'Gross',
                                'tax' => 'Tax',
                            ]" name="account_report_ledger_columns" />

                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                    EXPORT ▼
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li>
                                            <button type="button"
                                                class="block px-4 py-2 w-full text-left hover:bg-gray-100"
                                                onclick="openExportModal('ledger-report')">
                                                <i class="far fa-file-pdf"></i> PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button"
                                                class="block px-4 py-2 w-full text-left hover:bg-gray-100"
                                                onclick="openExportModal('ledger-report'); setTimeout(() => { document.querySelector('input[name=format][value=excel]').click(); }, 100);">
                                                <i class="fas fa-file-csv"></i> Excel
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <a href="{{ route('account.report.audit-trail') }}"
                                class="px-2 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0]">
                                Audit Trail
                            </a>
                        </div>
                    </div>

                    <div class="bg-white p-8 my-5 mx-auto shadow-sm rounded-lg border border-gray-100">
                        <div class="space-y-8">
                            <!-- Header + Toggle (one line) -->
                            <div class="flex items-center justify-between flex-wrap gap-4 relative">
                                <!-- Report Info -->
                                <div class="xero-report-header" style="text-align: left; margin: 0;">
                                    <div class="xero-company-name">
                                        {{ loggedCompanyId()['company_name'] ?? 'Company Name' }}
                                    </div>
                                    <h1 class="xero-report-title">General Ledger</h1>
                                    <p class="xero-report-subtitle">
                                        For the period
                                        <span class="font-medium text-gray-700">
                                            {{ dateParse($from) }} - {{ dateParse($to) }}
                                        </span>
                                    </p>
                                </div>

                                <!-- Toggle Buttons -->
                                <div class="flex gap-2">
                                    <button type="button"
                                        onclick="document.dispatchEvent(new CustomEvent('ledger:expand-all'))"
                                        class="xero-toggle-btn">
                                        Expand All
                                    </button>
                                    <button type="button"
                                        onclick="document.dispatchEvent(new CustomEvent('ledger:collapse-all'))"
                                        class="xero-toggle-btn">
                                        Collapse All
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Modal -->
                            <div>
                                <x-report-filter-modal title="General Ledger Options">
                                    <x-fiscal-year-select />

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">From</label>
                                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                                            data-filter
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">To</label>
                                        <input type="date" name="to_date" value="{{ request('to_date') }}" data-filter
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    </div>

                                    {{-- <div>
                                        <label class="block text-xs text-gray-500 mb-1">Account Head</label>
                                        <select name="head_account_id" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- Select Head --</option>
                                            @foreach ($parentAccount as $parent)
                                                <option value="{{ $parent->id }}"
                                                    {{ request('head_account_id') == $parent->id ? 'selected' : '' }}>
                                                    {{ $parent->account_number }} - {{ $parent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Account</label>
                                        <select name="account_id" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- Select Account --</option>
                                            @foreach ($childAccount as $child)
                                                <option value="{{ $child->id }}"
                                                    {{ request('account_id') == $child->id ? 'selected' : '' }}>
                                                    {{ $child->account_number }} - {{ $child->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div> --}}

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Accounts</label>
                                        <select name="account_id" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- Select Account --</option>
                                            @foreach ($allChartAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_number }} - {{ $account->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Date Range</label>
                                        <select name="date_range" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- Select Date Range --</option>
                                            @foreach (config('default.date_ranges') as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ request('date_range') == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Per Page</label>
                                        <select name="per_page"
                                            class="select2 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="1000">All</option>
                                            @foreach (config('default.per_page') as $value)
                                                <option value="{{ $value }}"
                                                    {{ request('per_page') == $value ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </x-report-filter-modal>
                            </div>

                            <!-- Table -->
                            <div id="ledger-table">
                                @include('pages.account.reports.partials.ledger-table')
                            </div>

                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

    <x-export-column-modal />
@endsection
@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <!-- Dropdown Script -->
    <script>
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

        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.report.ledger') }}',
            tableSelector: '#ledger-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        /*
            document.getElementById('remove-filter-btn').addEventListener('click', function() {
                const form = document.getElementById('filter-form');
                const headSelect = form.querySelector('select[name="head_account_id"]');
                const allAccountsCheckbox = form.querySelector('input[name="all_accounts"]');

                if (headSelect) {
                    headSelect.value = '';
                    $(headSelect).trigger('change');
                }
                if (allAccountsCheckbox) {
                    allAccountsCheckbox.checked = false;
                }

                // Trigger search
                document.getElementById('search-btn').click();
            });
        */
    </script>

    <script>
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.invoice-no');
            if (!target) return;
            const invoiceNo = target.dataset.invoiceNo;
            if (!invoiceNo) return;

            const url = '{{ route('account.transaction-to-invoice', ':invoiceNo') }}'
                .replace(':invoiceNo', invoiceNo);

            window.open(url, '_blank');

        });
    </script>
@endpush
