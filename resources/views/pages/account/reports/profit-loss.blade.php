@extends('layouts.auth')

@section('title', 'Profit and loss')

@push('styles')
    @include('pages.account.reports.partials._xero-report-styles')
@endpush

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0 no-print">
                @foreach (config('modules.reports') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">
            <div class="no-print">
                <div x-data="{}"
                    class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-1">Profit & Loss Statement</h2>
                        {{-- ── Comparative Quick-Access Buttons ─────────────────── --}}
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="text-xs text-gray-400 font-semibold uppercase">Compare:</span>
                            @php
                                $currentFiscalYear = \App\Models\AccountingPeriod::findPeriodForDate(now()->format('Y-m-d'));
                                $thisYear  = now()->year;
                                $ysStart   = $currentFiscalYear ? $currentFiscalYear->start_date->format('Y-m-d') : $thisYear . '-01-01';
                                $ysEnd     = $currentFiscalYear ? $currentFiscalYear->end_date->format('Y-m-d') : $thisYear . '-12-31';
                                $qStart    = now()->startOfQuarter()->toDateString();
                                $qEnd      = now()->endOfQuarter()->toDateString();
                                $mStart    = now()->startOfMonth()->toDateString();
                                $mEnd      = now()->endOfMonth()->toDateString();
                            @endphp
                            <a href="{{ request()->fullUrlWithQuery(['from_date' => $ysStart, 'to_date' => $ysEnd, 'compare_periods' => 1, 'period_type' => 'fiscal_year', 'date_range' => '']) }}"
                               title="Current fiscal year vs prior fiscal year"
                               class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriods == 1 && $periodType == 'fiscal_year') ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior FY
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['from_date' => $qStart, 'to_date' => $qEnd, 'compare_periods' => 1, 'period_type' => 'quarter', 'date_range' => '']) }}"
                               title="Current quarter vs prior quarter"
                               class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriods == 1 && $periodType == 'quarter') ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior Quarter
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['from_date' => $mStart, 'to_date' => $mEnd, 'compare_periods' => 1, 'period_type' => 'month', 'date_range' => '']) }}"
                               title="Current month vs prior month"
                               class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriods == 1 && $periodType == 'month') ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior Month
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['from_date' => $ysStart, 'to_date' => $ysEnd, 'compare_periods' => 3, 'period_type' => 'fiscal_year', 'date_range' => '']) }}"
                               title="3 fiscal year comparison"
                               class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriods == 3 && $periodType == 'fiscal_year') ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                3-FY
                            </a>
                            @if ($comparePeriods > 0)
                            <a href="{{ request()->fullUrlWithQuery(['compare_periods' => 0]) }}"
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

                        <button
                            class="px-4 py-2 bg-white border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal"
                            onclick="window.print()">
                            <i class="fas fa-print"></i> PRINT
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
                                            data-export-url="{{ route('account.report.profit-loss.pdf') }}"
                                            data-export-type="pdf">
                                            <i class="far fa-file-pdf"></i> PDF
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button"
                                            class="export-btn block px-4 py-2 hover:bg-[#0078C8] hover:text-white w-full text-left"
                                            onclick="return false;"
                                            data-export-url="{{ route('account.report.profit-loss.csv') }}"
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
            </div>
            <div class="bg-white p-8 my-5 mx-auto shadow-sm rounded-lg border border-gray-100">
                <!-- Header + Summary + Toggle (one line) -->
                <div class="flex items-center justify-between flex-wrap gap-4 relative">
                    <!-- Report Info -->
                    <div class="xero-report-header" style="text-align: left; margin: 0;">
                        <div class="xero-company-name">{{ loggedCompanyId()['company_name'] ?? 'Company Name' }}</div>
                        <h1 class="xero-report-title">Profit & Loss Statement</h1>
                        <div class="xero-report-subtitle">
                            {{ dateParse($startDate) }} to
                            {{ dateParse($endDate) }}
                        </div>
                    </div>

                    <!-- Summary -->
                    <div id="profit-loss-summary" class="xero-summary" style="margin: 0; padding: 0; border: none;">
                        <div class="xero-summary-item">
                            <p class="xero-summary-label">Total Revenue</p>
                            <p data-summary-key="total_revenue" x-data="countUp('{{ $profitLoss['total_revenue']['current'] }}')"
                                x-on:update-value="update($event.detail.value)"
                                class="xero-summary-value" x-text="displayValue">
                                {{ numberFormat($profitLoss['total_revenue']['current']) }}
                            </p>
                        </div>

                        <div class="xero-summary-item">
                            <p class="xero-summary-label">Total Expenses</p>
                            <p data-summary-key="total_expenses" x-data="countUp('{{ $profitLoss['total_expenses']['current'] }}')"
                                x-on:update-value="update($event.detail.value)"
                                class="xero-summary-value" x-text="displayValue">
                                {{ numberFormat($profitLoss['total_expenses']['current']) }}
                            </p>
                        </div>

                        <div class="xero-summary-item">
                            <p class="xero-summary-label">Net Profit</p>
                            <p data-summary-key="net_profit" x-data="countUp('{{ $profitLoss['net_profit']['current'] }}')"
                                x-on:update-value="update($event.detail.value)"
                                class="xero-summary-value" x-text="displayValue">
                                {{ numberFormat($profitLoss['net_profit']['current']) }}
                            </p>
                        </div>
                    </div>

                    <!-- Toggle Buttons -->
                    <div class="flex gap-2">
                        <button type="button" class="xero-toggle-btn"
                            onclick="document.dispatchEvent(new CustomEvent('profit-loss:expand-all'))">
                            Expand All
                        </button>
                        <button type="button" class="xero-toggle-btn"
                            onclick="document.dispatchEvent(new CustomEvent('profit-loss:collapse-all'))">
                            Collapse All
                        </button>
                    </div>
                </div>

                <!-- Filter Modal -->
                <div>
                    <x-report-filter-modal title="Profit & Loss Options">
                        <x-fiscal-year-select />

                        <div>
                            <label class="block text-xs text-stone-500 mb-1">Date Range</label>
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
                            <label class="block text-xs text-stone-500 mb-1">From:</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" data-filter
                                class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs text-stone-500 mb-1">To:</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" data-filter
                                class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs text-stone-500 mb-1">Compare with:</label>
                            <select name="compare_periods" id="compare_periods_select" data-filter
                                class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                <option value="0" {{ $comparePeriods == 0 ? 'selected' : '' }}>None</option>
                                @for ($i = 1; $i <= 11; $i++)
                                    <option value="{{ $i }}" {{ $comparePeriods == $i ? 'selected' : '' }}>
                                        {{ $i }} previous {{ Str::plural($periodType, $i) }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-stone-500 mb-1">Period type:</label>
                            <select name="period_type" id="period_type_select" data-filter
                                class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                <option value="month" {{ $periodType == 'month' ? 'selected' : '' }}>Month</option>
                                <option value="quarter" {{ $periodType == 'quarter' ? 'selected' : '' }}>Quarter
                                </option>
                                <option value="year" {{ $periodType == 'year' ? 'selected' : '' }}>Year</option>
                                <option value="fiscal_year" {{ $periodType == 'fiscal_year' ? 'selected' : '' }}>Fiscal Year</option>
                                <option value="custom_date" {{ $periodType == 'custom_date' ? 'selected' : '' }}>Custom Date</option>
                            </select>
                        </div>

                        <div id="custom_compare_dates" style="display: {{ $periodType == 'custom_date' ? 'grid' : 'none' }}" class="md:col-span-2 grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <div>
                                <label class="block text-xs text-stone-500 mb-1">Compare From:</label>
                                <input type="date" name="compare_from_date" value="{{ request('compare_from_date') }}" data-filter
                                    class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-stone-500 mb-1">Compare To:</label>
                                <input type="date" name="compare_to_date" value="{{ request('compare_to_date') }}" data-filter
                                    class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                            </div>
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 py-2 border-t mt-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="show_variance_amount" value="1" data-filter
                                    {{ request('show_variance_amount') ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-[#0078C8] rounded border-gray-300 focus:ring-[#0078C8]">
                                <span class="ml-2 text-sm text-gray-600">Variance Amount</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="show_variance_percentage" value="1" data-filter
                                    {{ request('show_variance_percentage') ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-[#0078C8] rounded border-gray-300 focus:ring-[#0078C8]">
                                <span class="ml-2 text-sm text-gray-600">Variance %</span>
                            </label>
                        </div>
                    </x-report-filter-modal>
                </div>

                @include('components.financial-narrative', ['narrative' => $narrative ?? []])

                <!-- Profit & Loss Table -->
                <div id="profit-loss-table">
                    @include('pages.account.reports.partials.profit-loss-table')
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
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

        // Account drill-down functionality
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.account-name');
            if (!target) return;

            const accountId = target.dataset.accountId;
            if (!accountId) return;

            const fromDate = document.querySelector('[name="from_date"]')?.value || '{{ $startDate }}';
            const toDate = document.querySelector('[name="to_date"]')?.value || '{{ $endDate }}';

            const params = new URLSearchParams({
                account_id: accountId,
                from_date: fromDate,
                to_date: toDate
            });

            const url = '{{ route('account.report.ledger') }}?' + params.toString();

            window.open(url, '_blank');
        });

        $(document).ready(function() {
            function toggleCustomDate() {
                var periodType = $('#period_type_select').val();
                if (periodType === 'custom_date') {
                    $('#custom_compare_dates').css('display', 'grid');
                    $('#compare_periods_select').closest('div').hide();
                } else {
                    $('#custom_compare_dates').hide();
                    $('#compare_periods_select').closest('div').show();
                }
            }

            $('#period_type_select').on('change', toggleCustomDate);
            toggleCustomDate(); // Initial run
        });

        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.report.profit-loss') }}',
            summarySelector: '#profit-loss-summary',
            tableSelector: '#profit-loss-table',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
