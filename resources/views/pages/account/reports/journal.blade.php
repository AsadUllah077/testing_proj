@extends('layouts.auth')

@section('title', 'Journal Report')

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

        [x-cloak] {
            display: none !important;
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
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Journal</h2>
                        <div class="flex items-center gap-2 relative ">

                            <x-update-button />

                            <!-- Report Options Button -->
                            <button @click="$dispatch('open-report-filter')"
                                class="px-4 py-1 bg-white border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center">
                                <span class="material-symbols-outlined text-xs font-normal">filter_list</span>
                            </button>

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
                                                onclick="openExportModal('journal-report')">
                                                <i class="far fa-file-pdf"></i> PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button"
                                                class="block px-4 py-2 w-full text-left hover:bg-gray-100"
                                                onclick="openExportModal('journal-report'); setTimeout(() => { document.querySelector('input[name=format][value=excel]').click(); }, 100);">
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

                            <!-- Header + Summary + Toggle (one line) -->
                            <div class="flex items-center justify-between flex-wrap gap-4 relative">
                                <!-- Report Info -->
                                <div class="xero-report-header" style="text-align: left; margin: 0;">
                                    <p class="xero-company-name">{{ loggedCompanyId()['company_name'] ?? 'Company Name' }}</p>
                                    <h1 class="xero-report-title">Journal Report</h1>
                                    <p class="xero-report-subtitle">
                                        For the period {{ dateParse($from) }} - {{ dateParse($to) }}
                                    </p>
                                </div>

                                <!-- Summary -->
                                <div id="journal-summary-wrapper">
                                    <div id="journal-summary" class="xero-summary">
                                        <div class="xero-summary-item">
                                            <p class="xero-summary-label">Total Debit</p>
                                            <p data-summary-key="total_debit" x-data="countUp('{{ $totalDebit }}')"
                                                x-on:update-value="update($event.detail.value)"
                                                class="xero-summary-value"
                                                x-text="displayValue">
                                                {{ numberFormat($totalDebit) }}
                                            </p>
                                        </div>
                                        <div class="xero-summary-item">
                                            <p class="xero-summary-label">Total Credit</p>
                                            <p data-summary-key="total_credit" x-data="countUp('{{ $totalCredit }}')"
                                                x-on:update-value="update($event.detail.value)"
                                                class="xero-summary-value"
                                                x-text="displayValue">
                                                {{ numberFormat($totalCredit) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Toggle Buttons -->
                                <div class="flex gap-2">
                                    <button type="button" class="xero-toggle-btn"
                                        onclick="document.dispatchEvent(new CustomEvent('journal:expand-all'))">
                                        Expand All
                                    </button>
                                    <button type="button" class="xero-toggle-btn"
                                        onclick="document.dispatchEvent(new CustomEvent('journal:collapse-all'))">
                                        Collapse All
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Modal -->
                            <div>
                                <x-report-filter-modal title="Journal Report Options">
                                    <x-fiscal-year-select />

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">From</label>
                                        <input type="date" id="from_date" name="from_date" data-filter
                                            value="{{ request('from_date') }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">To</label>
                                        <input type="date" id="to_date" name="to_date" data-filter
                                            value="{{ request('to_date') }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Account</label>
                                        <x-acccount.coa.coa-select id="account_id" name="account_id"
                                            :selected="request('account_id')" />
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Select Invoice</label>
                                        <select name="invoice_no" data-selected="{{ request('invoice_no') }}"
                                            class="ajax-invoice-select w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Select Type</label>
                                        <select id="type" name="type" data-filter
                                            class="select2 bg-white w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- Select Type --</option>
                                            @foreach (['Sale', 'Bill', 'Credit Note', 'Payroll', 'Liability Payment', 'Journal_Entry', 'Tax Settlement', 'Tax Payment', 'Tax Refund'] as $value)
                                                <option value="{{ $value }}"
                                                    {{ request('type') == $value ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Date Range</label>
                                        <select id="date_range" name="date_range" data-filter
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
                                        <x-default-page />
                                    </div>
                                </x-report-filter-modal>
                            </div>

                            <!-- Table -->
                            <div id="journal-table">
                                @include('pages.account.reports.partials.journal-table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-export-column-modal />
    </div>

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
            url: '{{ route('account.report.journal') }}',
            summarySelector: '#journal-summary',
            tableSelector: '#journal-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
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

        // Initialize AJAX Invoice Select
        $('.ajax-invoice-select').select2({
            placeholder: '-- select invoice --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.invoice.paginate') }}',
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
                        results: data.data.map(invoice => ({
                            id: invoice.invoice_no,
                            text: invoice.invoice_no
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });

        const selectedInvoice = $('.ajax-invoice-select').data('selected');

        if (selectedInvoice) {
            const option = new Option(selectedInvoice, selectedInvoice, true, true);
            $('.ajax-invoice-select').append(option).trigger('change');
        }
    </script>
@endpush
