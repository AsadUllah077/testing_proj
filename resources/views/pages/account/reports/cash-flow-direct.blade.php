@extends('layouts.auth')

@section('title', 'Direct Cash Flow')

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
            <div class="action-buttons no-print">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-1">Cash Flow Statement (Direct Method)</h2>
                        @php
                            $currentFiscalYear = \App\Models\AccountingPeriod::findPeriodForDate(now()->format('Y-m-d'));
                            $thisYear  = now()->year;
                            $cfdYsStart = $currentFiscalYear ? $currentFiscalYear->start_date->format('Y-m-d') : $thisYear . '-01-01';
                            $cfdYsEnd   = $currentFiscalYear ? $currentFiscalYear->end_date->format('Y-m-d') : $thisYear . '-12-31';
                            $cfdQStart  = now()->startOfQuarter()->toDateString();
                            $cfdQEnd    = now()->endOfQuarter()->toDateString();
                            $cfdMStart  = now()->startOfMonth()->toDateString();
                            $cfdMEnd    = now()->endOfMonth()->toDateString();
                        @endphp
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="text-xs text-gray-400 font-semibold uppercase">Compare:</span>
                            <a href="{{ request()->fullUrlWithQuery(['from_date' => $cfdYsStart, 'to_date' => $cfdYsEnd, 'compare_periods' => 1, 'period_type' => 'fiscal_year', 'date_range' => '']) }}"
                               title="Current fiscal year vs prior fiscal year"
                               class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriods == 1 && $periodType == 'fiscal_year') ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior FY
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['from_date' => $cfdQStart, 'to_date' => $cfdQEnd, 'compare_periods' => 1, 'period_type' => 'quarter', 'date_range' => '']) }}"
                               title="Current quarter vs prior quarter"
                               class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriods == 1 && $periodType == 'quarter') ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior Quarter
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['from_date' => $cfdMStart, 'to_date' => $cfdMEnd, 'compare_periods' => 1, 'period_type' => 'month', 'date_range' => '']) }}"
                               title="Current month vs prior month"
                               class="text-xs px-2.5 py-1 rounded border {{ ($comparePeriods == 1 && $periodType == 'month') ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior Month
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['from_date' => $cfdYsStart, 'to_date' => $cfdYsEnd, 'compare_periods' => 3, 'period_type' => 'fiscal_year', 'date_range' => '']) }}"
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
                    <div class="flex items-center space-x-2 relative no-print">

                        <x-update-button />

                        <!-- Report Options Button -->
                        <button x-data @click="$dispatch('open-report-filter')"
                            class="px-4 py-2 bg-white border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center">
                            <span class="material-symbols-outlined text-xs font-normal mr-1">filter_list</span>
                        </button>

                        <button
                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center"
                            onclick="window.print()">
                            <i class="fas fa-print mr-1"></i> Print
                        </button>

                        <div class="relative inline-block text-left" x-data="{ open: false }">
                            <button @click="open = !open"
                                class="px-4 py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center">
                                EXPORT <i class="fas fa-chevron-down ml-2 text-[10px]"></i>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                class="origin-top-right absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                                <div class="py-1">
                                    <button type="button"
                                        class="export-btn block px-4 py-2 text-sm text-gray-700 hover:bg-[#0078C8] hover:text-white w-full text-left"
                                        data-export-url="{{ route('account.report.cash-flow-direct.pdf', request()->all()) }}"
                                        data-export-type="pdf">
                                        <i class="far fa-file-pdf mr-2"></i> PDF
                                    </button>
                                    <button type="button"
                                        class="export-btn block px-4 py-2 text-sm text-gray-700 hover:bg-[#0078C8] hover:text-white w-full text-left"
                                        data-export-url="{{ route('account.report.cash-flow-direct.csv', request()->all()) }}"
                                        data-export-type="csv">
                                        <i class="fas fa-file-csv mr-2"></i> CSV
                                    </button>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('account.report.audit-trail') }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0]">
                            Audit Trail
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 my-5 mx-auto shadow-sm rounded-lg border border-gray-100">
                <!-- Header + Summary (one line) -->
                <div class="flex items-center justify-between flex-wrap gap-4 relative">
                    <!-- Report Info -->
                    <div class="xero-report-header" style="text-align: left; margin: 0;">
                        <p class="xero-company-name">{{ loggedCompanyId()['company_name'] }}</p>
                        <h1 class="xero-report-title">Cash Flow Statement</h1>
                        <p class="xero-report-subtitle">
                            {{ dateParse($startDate) }} to {{ dateParse($endDate) }}
                            (Direct Method)
                        </p>
                    </div>

                    <!-- Summary -->
                    <div id="cash-flow-summary" class="xero-summary" style="margin: 0; padding: 0; border: none;">
                        <div class="xero-summary-item">
                            <p class="xero-summary-label">Opening Cash Balance</p>
                            <p data-summary-key="opening_balance" class="xero-summary-value">
                                {{ numberFormat($cashFlow['data'][0]['opening_balance']) }}
                            </p>
                        </div>
                        <div class="xero-summary-item">
                            <p class="xero-summary-label">Net Cash Movement</p>
                            <p data-summary-key="net_cash_movement" class="xero-summary-value">
                                {{ numberFormat($cashFlow['data'][0]['net_cash_movement']) }}
                            </p>
                        </div>
                        <div class="xero-summary-item">
                            <p class="xero-summary-label">Closing Cash Balance</p>
                            <p data-summary-key="closing_balance" class="xero-summary-value">
                                {{ numberFormat($cashFlow['data'][0]['closing_balance']) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filter Form Modal -->
                <div>
                    <x-report-filter-modal title="Direct Cash Flow Options">
                        <form method="GET" id="filter-form"
                            class="no-print grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <x-fiscal-year-select />

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">From</label>
                                <input type="date" name="from_date" value="{{ request('from_date') }}" data-filter
                                    class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8] outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">To</label>
                                <input type="date" name="to_date" value="{{ request('to_date') }}" data-filter
                                    class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8] outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Date Range</label>
                                <select name="date_range" data-filter
                                    class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8] outline-none transition-all">
                                    <option value="">-- Select Date Range --</option>
                                    @foreach (config('default.date_ranges') as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ request('date_range') == $value ? 'selected' : '' }}>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Compare With</label>
                                <select name="compare_periods" id="compare_periods_select" data-filter
                                    class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8] outline-none transition-all">
                                    <option value="0">None</option>
                                    @for ($i = 1; $i <= 11; $i++)
                                        <option value="{{ $i }}"
                                            {{ request('compare_periods', $comparePeriods) == $i ? 'selected' : '' }}>
                                            {{ $i }} Period{{ $i > 1 ? 's' : '' }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Period Type</label>
                                <select name="period_type" id="period_type_select" data-filter
                                    class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8] outline-none transition-all">
                                    <option value="month"
                                        {{ request('period_type', $periodType) == 'month' ? 'selected' : '' }}>
                                        Month</option>
                                    <option value="quarter"
                                        {{ request('period_type', $periodType) == 'quarter' ? 'selected' : '' }}>
                                        Quarter</option>
                                    <option value="year"
                                        {{ request('period_type', $periodType) == 'year' ? 'selected' : '' }}>
                                        Year
                                    </option>
                                    <option value="fiscal_year"
                                        {{ request('period_type', $periodType) == 'fiscal_year' ? 'selected' : '' }}>
                                        Fiscal Year
                                    </option>
                                    <option value="custom_date"
                                        {{ request('period_type', $periodType) == 'custom_date' ? 'selected' : '' }}>
                                        Custom Date
                                    </option>
                                </select>
                            </div>

                            <div id="custom_compare_dates" style="display: {{ request('period_type', $periodType) == 'custom_date' ? 'grid' : 'none' }}" class="md:col-span-2 grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                <div>
                                    <label class="block text-xs text-stone-500 mb-1">Compare From:</label>
                                    <input type="date" name="compare_from_date" value="{{ request('compare_from_date') }}" data-filter
                                        class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8] outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs text-stone-500 mb-1">Compare To:</label>
                                    <input type="date" name="compare_to_date" value="{{ request('compare_to_date') }}" data-filter
                                        class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8] outline-none transition-all">
                                </div>
                            </div>

                            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 py-2 border-t mt-2">
                                <label class="flex items-center text-xs font-medium text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="show_variance_amount" value="1" data-filter
                                        {{ request('show_variance_amount', $showVarianceAmount) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-[#0078C8] mr-2">
                                    Variance Amount
                                </label>
                                <label class="flex items-center text-xs font-medium text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="show_variance_percentage" value="1" data-filter
                                        {{ request('show_variance_percentage', $showVariancePercentage) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-[#0078C8] mr-2">
                                    Variance %
                                </label>
                            </div>
                        </form>
                    </x-report-filter-modal>
                </div>

                <div id="cash-flow-table">
                    @include('pages.account.reports.partials.cash-flow-direct-table')
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
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

        ReportFilter.init({
            url: '{{ route('account.report.cash-flow-direct') }}',
            summarySelector: '#cash-flow-summary',
            tableSelector: '#cash-flow-table',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
