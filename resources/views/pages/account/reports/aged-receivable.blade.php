@extends('layouts.auth')

@section('title', 'Aged Receivable')

@push('styles')
    @include('pages.account.reports.partials._xero-report-styles')
    <style>
        .invoice-no { color: #0078C8; cursor: pointer; transition: color 0.15s; }
        .invoice-no:hover { color: #005a9e; text-decoration: underline; }
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
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Aged Receivable</h2>
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
                                                class="export-btn block px-4 py-2 hover:bg-[#0078C8] hover:text-white w-full text-left"
                                                onclick="return false;"
                                                data-export-url="{{ route('account.report.aged-receivable.pdf') }}"
                                                data-export-type="pdf">
                                                <i class="far fa-file-pdf"></i> PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button"
                                                class="export-btn block px-4 py-2 hover:bg-[#0078C8] hover:text-white w-full text-left"
                                                onclick="return false;"
                                                data-export-url="{{ route('account.report.aged-receivable.csv') }}"
                                                data-export-type="csv">
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
                                <div class="xero-report-header report-header" style="text-align: left; margin: 0; border-bottom: none; padding-bottom: 0;">
                                    <h1 class="xero-report-title">Aged Receivables Report</h1>
                                    <div class="xero-company-name">
                                        {{ loggedCompanyId()['company_name'] ?? 'Company Name' }}
                                    </div>
                                    <p class="xero-report-subtitle">
                                        @if ($from_date)
                                            From <span class="font-medium text-gray-700">{{ dateParse($from_date) }}</span>
                                        @endif
                                        As of <span class="font-medium text-gray-700">{{ dateParse($as_of_date) }}</span>
                                    </p>
                                </div>

                                <!-- Summary -->
                                <div id="aged-receivable-summary" class="xero-summary" style="margin: 0; padding: 0; border: none;">
                                    <div class="xero-summary-item">
                                        <p class="xero-summary-label">Total Receivables</p>
                                        <p data-summary-key="grand_total"
                                            x-data="countUp('{{ $grandTotal }}')" x-on:update-value="update($event.detail.value)"
                                            class="xero-summary-value"
                                            x-text="displayValue">
                                            {{ numberFormat($grandTotal) }}</p>
                                    </div>

                                    <div class="xero-summary-item">
                                        <p class="xero-summary-label">Overdue</p>
                                        <p data-summary-key="overdue"
                                            x-data="countUp('{{ $grandTotals[count($grandTotals) - 2] + $grandTotals[count($grandTotals) - 1] }}')" x-on:update-value="update($event.detail.value)"
                                            class="xero-summary-value"
                                            x-text="displayValue">
                                            {{ numberFormat($grandTotals[count($grandTotals) - 2] + $grandTotals[count($grandTotals) - 1]) }}
                                        </p>
                                    </div>

                                    <div class="xero-summary-item">
                                        <p class="xero-summary-label">Current (0-30 Days)</p>
                                        <p data-summary-key="current"
                                            x-data="countUp('{{ $grandTotals[0] }}')" x-on:update-value="update($event.detail.value)"
                                            class="xero-summary-value"
                                            x-text="displayValue">
                                            {{ numberFormat($grandTotals[0]) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Toggle Buttons -->
                                <div class="flex gap-2">
                                    <button type="button"
                                        class="xero-toggle-btn"
                                        onclick="document.dispatchEvent(new CustomEvent('aging:expand-all'))">
                                        Expand All
                                    </button>
                                    <button type="button"
                                        class="xero-toggle-btn"
                                        onclick="document.dispatchEvent(new CustomEvent('aging:collapse-all'))">
                                        Collapse All
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Modal -->
                            <div>
                                <x-report-filter-modal title="Aged Receivable Options">
                                    <x-fiscal-year-select />

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">From Date</label>
                                        <input type="date" name="from_date" value="{{ $from_date }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">As of Date</label>
                                        <input type="date" name="as_of_date" value="{{ $as_of_date }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Ageing Periods</label>
                                        <select name="periods"
                                            class="select2 py-2 px-3 border border-gray-300 rounded-md text-sm w-full">
                                            <option value="4x1" {{ $period == '4x1' ? 'selected' : '' }}>4 Periods
                                                of 1
                                                Month</option>
                                            <option value="5x15" {{ $period == '5x15' ? 'selected' : '' }}>5 Periods
                                                of
                                                15 Days</option>
                                            <option value="6x1" {{ $period == '6x1' ? 'selected' : '' }}>6 Periods
                                                of 1
                                                Month</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Group by</label>
                                        <select name="group_by"
                                            class="select2 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none w-full">
                                            <option value="">Select option...</option>
                                            <option value="unique_no" {{ $groupBy == 'unique_no' ? 'selected' : '' }}>
                                                By
                                                Invoice</option>
                                            <option value="customer_id" {{ $groupBy == 'customer_id' ? 'selected' : '' }}>
                                                By Customer</option>
                                        </select>
                                    </div>

                                    <div>
                                        <x-default-page />
                                    </div>

                                    <div class="flex items-center gap-2 py-4 mt-2 border-t md:col-span-2">
                                        <input type="hidden" name="include_statutory" value="false">
                                        <input type="checkbox" name="include_statutory" id="include_statutory"
                                            value="true" {{ $includeStatutory ? 'checked' : '' }}
                                            class="w-4 h-4 text-[#0078C8] border-gray-300 rounded focus:ring-[#0078C8]">
                                        <label for="include_statutory" class="text-xs text-gray-500 cursor-pointer">Include
                                            Statutory</label>
                                    </div>
                                </x-report-filter-modal>
                            </div>

                            <!-- Table Container -->
                            <div id="aged-receivable-table">
                                @include('pages.account.reports.partials.aged-receivable-table')
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Dropdown Script -->
    <script>
        // Dropdown button handlers
        document.querySelectorAll('.dropdown-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const menu = this.nextElementSibling;
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                // Toggle current dropdown
                menu.classList.toggle('hidden');
            });
        });

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
    </script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.report.aged-receivable') }}',
            summarySelector: '#aged-receivable-summary',
            tableSelector: '#aged-receivable-table',
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
    </script>
@endpush
