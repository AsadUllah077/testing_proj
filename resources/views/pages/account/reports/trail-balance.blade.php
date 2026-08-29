@extends('layouts.auth')

@section('title', 'Trial Balance')

@push('styles')
    @include('pages.account.reports.partials._xero-report-styles')
    <style>
        .account-name {
            color: #0078C8;
            cursor: pointer;
            transition: color 0.15s;
        }
        .account-name:hover {
            color: #005a9e;
            text-decoration: underline;
        }
        .sub-account {
            padding-left: 35px !important;
            color: #555;
            font-size: 12px;
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
                        <div>
                            <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-1">Trial Balance</h2>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="text-xs text-gray-400 font-semibold uppercase">Compare:</span>
                                <a href="{{ request()->fullUrlWithQuery(['compare_period' => 'previous_fiscal_year', 'compare_count' => 1]) }}"
                                   title="vs prior fiscal year"
                                   class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriod == 'previous_fiscal_year' && $compareCount == 1) ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                    vs Prior FY
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['compare_period' => 'previous_quarter', 'compare_count' => 1]) }}"
                                   title="vs prior quarter"
                                   class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriod == 'previous_quarter' && $compareCount == 1) ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                    vs Prior Quarter
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['compare_period' => 'previous_month', 'compare_count' => 1]) }}"
                                   title="vs prior month"
                                   class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriod == 'previous_month' && $compareCount == 1) ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                    vs Prior Month
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['compare_period' => 'previous_fiscal_year', 'compare_count' => 3]) }}"
                                   title="3 fiscal year comparison"
                                   class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriod == 'previous_fiscal_year' && $compareCount == 3) ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                    3-FY
                                </a>
                                @if ($comparePeriod)
                                <a href="{{ request()->fullUrlWithQuery(['compare_period' => '', 'compare_count' => 0]) }}"
                                   class="text-xs px-2.5 py-1 rounded border border-red-300 text-red-500 hover:bg-red-50">
                                    ✕ Clear
                                </a>
                                @endif
                            </div>
                        </div>
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
                                                data-export-url="{{ route('account.report.trail-balance.pdf') }}"
                                                data-export-type="pdf">
                                                <i class="far fa-file-pdf"></i> PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button"
                                                class="export-btn block px-4 py-2 hover:bg-[#0078C8] hover:text-white w-full text-left"
                                                onclick="return false;"
                                                data-export-url="{{ route('account.report.trail-balance.csv') }}"
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

                        <div class="space-y-6">

                            <!-- Header + Summary + Toggle (one line) -->
                            <div class="flex items-center justify-between flex-wrap gap-4 relative">
                                <!-- Report Info -->
                                <div class="xero-report-header" style="text-align: left; margin: 0;">
                                    <p class="xero-company-name">{{ loggedCompanyId()['company_name'] ?? 'Company Name' }}</p>
                                    <h1 class="xero-report-title">Trial Balance</h1>
                                    <p class="xero-report-subtitle">
                                        For the period
                                        <span class="font-medium text-gray-700">
                                            {{ $from ? dateParse($from) : '---' }}
                                            to {{ $to ? dateParse($to) : '---' }}
                                        </span>
                                        @if ($comparePeriod)
                                            <span class="ml-2 px-2 py-0.5 text-xs text-gray-500 border border-gray-200 rounded">
                                                vs {{ $comparePeriod ? ucfirst(str_replace('_', ' ', $comparePeriod)) : '' }}
                                            </span>
                                        @endif
                                    </p>
                                </div>

                                <!-- Summary -->
                                <div id="trial-balance-summary-wrapper">
                                    <div id="trial-balance-summary" class="flex flex-wrap justify-center gap-8">
                                        <div class="text-center">
                                            <p class="text-[11px] uppercase text-gray-400 tracking-wide">Total Debit</p>
                                            <p data-summary-key="total_debit" x-data="countUp('{{ $allAccountsTotalDebit }}')"
                                                x-on:update-value="update($event.detail.value)"
                                                class="text-lg font-semibold text-[#333] mt-0.5 tabular-nums"
                                                x-text="displayValue">
                                                {{ numberFormat($allAccountsTotalDebit) }}
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-[11px] uppercase text-gray-400 tracking-wide">Total Credit</p>
                                            <p data-summary-key="total_credit" x-data="countUp('{{ $allAccountsTotalCredit }}')"
                                                x-on:update-value="update($event.detail.value)"
                                                class="text-lg font-semibold text-[#333] mt-0.5 tabular-nums"
                                                x-text="displayValue">
                                                {{ numberFormat($allAccountsTotalCredit) }}
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-[11px] uppercase text-gray-400 tracking-wide">Status</p>
                                            <p data-summary-key="status" class="text-lg font-semibold text-[#333] mt-0.5">
                                                {{ $status }} {!! $status == 'Balanced' ? '&#10003;' : '&#10007;' !!}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Toggle Buttons -->
                                <div class="flex gap-2">
                                    <button type="button" class="xero-toggle-btn"
                                        onclick="document.dispatchEvent(new CustomEvent('trial-balance:expand-all'))">
                                        Expand All
                                    </button>
                                    <button type="button" class="xero-toggle-btn"
                                        onclick="document.dispatchEvent(new CustomEvent('trial-balance:collapse-all'))">
                                        Collapse All
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Modal -->
                            <div>
                                <x-report-filter-modal title="Trial Balance Options">
                                    <x-fiscal-year-select />

                                    <input type="hidden" name="compare_period" value="{{ $comparePeriod }}" data-filter>
                                    <input type="hidden" name="compare_count" value="{{ request('compare_count', 0) }}"
                                        data-filter>

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

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Account</label>
                                        <x-acccount.coa.coa-select name="account_id" :selected="request('account_id')" />
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
                                            class="select2 bg-white w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="200">All</option>
                                            @foreach (config('default.per_page') as $value)
                                                <option value="{{ $value }}"
                                                    {{ request('per_page') == $value ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Comparison Options -->
                                    <div class="md:col-span-2 border-t pt-4 mt-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2 text-center">Compare
                                            with:</label>
                                        <div class="flex space-x-2 justify-center">
                                            <select id="compare_count_select" class="w-16 border rounded text-sm p-1">
                                                @for ($i = 1; $i <= 11; $i++)
                                                    <option value="{{ $i }}"
                                                        {{ request('compare_count') == $i ? 'selected' : '' }}>
                                                        {{ $i }}</option>
                                                @endfor
                                            </select>
                                            <select id="compare_period_select" class="border rounded text-sm p-1">
                                                <option value="previous_period"
                                                    {{ $comparePeriod == 'previous_period' ? 'selected' : '' }}>Previous
                                                    Period(s)</option>
                                                <option value="previous_month"
                                                    {{ $comparePeriod == 'previous_month' ? 'selected' : '' }}>Previous
                                                    Month(s)</option>
                                                <option value="previous_quarter"
                                                    {{ $comparePeriod == 'previous_quarter' ? 'selected' : '' }}>Previous
                                                    Quarter(s)</option>
                                                <option value="previous_year"
                                                    {{ $comparePeriod == 'previous_year' ? 'selected' : '' }}>Previous
                                                    Year(s)</option>
                                                <option value="previous_fiscal_year"
                                                    {{ $comparePeriod == 'previous_fiscal_year' ? 'selected' : '' }}>Previous
                                                    Fiscal Year(s)</option>
                                                <option value="custom_date"
                                                    {{ $comparePeriod == 'custom_date' ? 'selected' : '' }}>Custom Date</option>
                                            </select>
                                            <div id="custom_compare_dates" style="display: {{ $comparePeriod == 'custom_date' ? 'flex' : 'none' }}" class="space-x-2">
                                                <input type="date" id="compare_from_date" name="compare_from_date" data-filter class="date border rounded text-sm p-1" value="{{ request('compare_from_date') }}">
                                                <input type="date" id="compare_to_date" name="compare_to_date" data-filter class="date border rounded text-sm p-1" value="{{ request('compare_to_date') }}">
                                            </div>
                                            <button type="button" onclick="applyComparison()"
                                                class="px-4 bg-[#0078C8] text-white text-xs py-2 rounded hover:bg-[#006bb0]">
                                                Apply
                                            </button>
                                            @if ($comparePeriod)
                                                <button type="button" onclick="clearComparison()"
                                                    class="px-4 bg-red-50 text-red-600 text-xs py-2 rounded border border-red-100 hover:bg-red-100">
                                                    Clear
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </x-report-filter-modal>
                            </div>

                            <!-- Page Info -->
                            <div class="border border-gray-100 rounded px-4 py-3 text-sm text-gray-500">
                                <strong class="text-gray-600">Note:</strong> You're viewing page
                                {{ $chartOfAccounts->currentPage() }} of {{ $chartOfAccounts->lastPage() }}.
                                Grand totals are calculated across ALL accounts, not just this page.
                            </div>

                            <!-- Table -->
                            <div id="trial-balance-table">
                                @include('pages.account.reports.partials.trial-balance-table')
                            </div>

                            <!-- Footer -->
                            <div class="text-xs text-gray-500 italic space-y-1">
                                <p>* The initial balance is carried over from the previous period. Year balance = Initial +
                                    (Debit - Credit).</p>
                                <p>* Page totals show only the accounts on this page. Grand totals include ALL accounts.</p>
                                @if ($comparePeriod)
                                    <p>* Comparison data shows activity for the
                                        {{ $comparePeriod ? str_replace('_', ' ', $comparePeriod) : 'selected period' }}.
                                    </p>
                                @endif
                            </div>
                        </div>


                    </div>

                </div>
            </div>
        </div>
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
            document.querySelectorAll('[id$="Dropdown"], [id$="With"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        }

        // Close export dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"], [id$="With"]')) {
                document.querySelectorAll('[id$="Dropdown"], [id$="With"]').forEach(el => el.classList.add(
                    'hidden'));
            }
        });

        // Handle comparison buttons
        function applyComparison() {
            const period = document.getElementById('compare_period_select').value;
            const count = period === 'custom_date' ? 1 : document.getElementById('compare_count_select').value;

            document.querySelector('input[name="compare_count"]').value = count;
            document.querySelector('input[name="compare_period"]').value = period;

            triggerFilter();
        }

        function clearComparison() {
            document.querySelector('input[name="compare_count"]').value = 0;
            document.querySelector('input[name="compare_period"]').value = '';
            
            const fromDateInput = document.getElementById('compare_from_date');
            const toDateInput = document.getElementById('compare_to_date');
            if (fromDateInput) fromDateInput.value = '';
            if (toDateInput) toDateInput.value = '';

            triggerFilter();
        }

        function triggerFilter() {
            // Reset to page 1
            let pageInput = document.querySelector('input[name="page"]');
            if (pageInput) {
                pageInput.value = '1';
            }
            // Close dropdown
            document.getElementById('compareWith').classList.add('hidden');
            // Trigger filter
            ReportFilter.filter();
        }

        // Pre-select values if active
        document.addEventListener('DOMContentLoaded', () => {
            const currentCount = '{{ request('compare_count', 1) }}';
            const currentPeriod = '{{ $comparePeriod ?? 'previous_period' }}';

            if (document.getElementById('compare_count_select')) {
                document.getElementById('compare_count_select').value = currentCount > 0 ? currentCount : 1;
            }
            if (document.getElementById('compare_period_select')) {
                document.getElementById('compare_period_select').value = currentPeriod || 'previous_period';
            }

            const periodSelect = document.getElementById('compare_period_select');
            const customDates = document.getElementById('custom_compare_dates');
            const countSelect = document.getElementById('compare_count_select');

            if (periodSelect && periodSelect.value === 'custom_date') {
                if (customDates) customDates.style.display = 'flex';
                if (countSelect) countSelect.style.display = 'none';
            }

            if (periodSelect) {
                periodSelect.addEventListener('change', function() {
                    if (this.value === 'custom_date') {
                        if (customDates) customDates.style.display = 'flex';
                        if (countSelect) countSelect.style.display = 'none';
                    } else {
                        if (customDates) customDates.style.display = 'none';
                        if (countSelect) countSelect.style.display = 'inline-block';
                    }
                });
            }
        });

        // Account drill-down functionality
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.account-name');
            if (!target) return;

            const accountId = target.dataset.accountId;
            if (!accountId) return;

            const fromDate = document.querySelector('[name="from_date"]')?.value || '{{ $from }}';
            const toDate = document.querySelector('[name="to_date"]')?.value || '{{ $to }}';

            const params = new URLSearchParams({
                account_id: accountId,
                from_date: fromDate,
                to_date: toDate
            });

            const url = '{{ route('account.report.ledger') }}?' + params.toString();

            window.open(url, '_blank');
        });

        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.report.trail-balance') }}',
            summarySelector: '#trial-balance-summary',
            tableSelector: '#trial-balance-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
