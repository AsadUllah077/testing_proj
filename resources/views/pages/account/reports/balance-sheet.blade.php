@extends('layouts.auth')

@section('title', 'Balance Sheet')

@push('styles')
    @include('pages.account.reports.partials._xero-report-styles')
    @include('pages.account.reports.partials._xero-balance-sheet-style')
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
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-1">Balance Sheet</h2>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="text-xs text-gray-400 font-semibold uppercase">Compare:</span>
                            <a href="{{ request()->fullUrlWithQuery(['compare_periods' => 1, 'period_type' => 'fiscal_year']) }}"
                                title="vs prior fiscal year end"
                                class="text-xs px-2.5 py-1 rounded border {{ $comparePeriods == 1 && $periodType == 'fiscal_year' ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior FY
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['compare_periods' => 1, 'period_type' => 'quarter']) }}"
                                title="vs end of prior quarter"
                                class="text-xs px-2.5 py-1 rounded border {{ $comparePeriods == 1 && $periodType == 'quarter' ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior Quarter
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['compare_periods' => 1, 'period_type' => 'month']) }}"
                                title="vs end of prior month"
                                class="text-xs px-2.5 py-1 rounded border {{ $comparePeriods == 1 && $periodType == 'month' ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                                vs Prior Month
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['compare_periods' => 3, 'period_type' => 'fiscal_year']) }}"
                                title="3 fiscal year comparison"
                                class="text-xs px-2.5 py-1 rounded border {{ $comparePeriods == 3 && $periodType == 'fiscal_year' ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'border-gray-300 text-gray-600 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
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
                                            data-export-url="{{ route('account.report.balance-sheet.export') }}"
                                            data-export-type="pdf">
                                            <i class="far fa-file-pdf"></i> PDF
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button"
                                            class="export-btn block px-4 py-2 hover:bg-[#0078C8] hover:text-white w-full text-left"
                                            onclick="return false;"
                                            data-export-url="{{ route('account.report.balance-sheet.export') }}"
                                            data-export-type="csv">
                                            <i class="fas fa-file-csv"></i> CSV
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

            <div id="balance-sheet-card" class="bg-white p-8 my-5 mx-auto shadow-sm rounded-lg border border-gray-100">
                <!-- Animated SVG border (Assets = Liabilities + Equity check) -->
                <svg class="balance-border-svg" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                    <rect class="balance-border-rect" x="0" y="0" width="100%" height="100%" rx="8"
                        ry="8" pathLength="100"></rect>
                </svg>

                <!-- Header + Summary + Toggle (one line) -->
                <div class="flex items-center justify-between flex-wrap gap-4 relative">
                    <!-- Report Info -->
                    <div class="report-header" style="text-align: left; margin: 0; border-bottom: none; padding-bottom: 0;">
                        <div class="report-subtitle" style="font-weight: 500; color: #555;">
                            {{ loggedCompanyId()['company_name'] ?? 'Company Name' }}</div>
                        <h1 class="report-title">Balance Sheet</h1>
                        <div class="report-subtitle">As of {{ dateParse($asOfDate) }}</div>
                    </div>

                    <!-- Summary -->
                    <div id="balance-sheet-summary" class="flex flex-wrap justify-center gap-8">
                        <div class="text-center">
                            <p class="text-[11px] uppercase text-gray-400 tracking-wide">Total Assets</p>
                            <p data-summary-key="total_assets"
                                data-raw-value="{{ $balanceSheet['total_assets']['current'] }}" x-data="countUp('{{ $balanceSheet['total_assets']['current'] }}')"
                                x-on:update-value="update($event.detail.value)"
                                class="text-lg font-semibold text-[#333] mt-0.5 tabular-nums" x-text="displayValue">
                                {{ numberFormat($balanceSheet['total_assets']['current']) }}
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-[11px] uppercase text-gray-400 tracking-wide">Total Liabilities</p>
                            <p data-summary-key="total_liabilities"
                                data-raw-value="{{ $balanceSheet['total_liabilities']['current'] }}"
                                x-data="countUp('{{ $balanceSheet['total_liabilities']['current'] }}')" x-on:update-value="update($event.detail.value)"
                                class="text-lg font-semibold text-[#333] mt-0.5 tabular-nums" x-text="displayValue">
                                {{ numberFormat($balanceSheet['total_liabilities']['current']) }}
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-[11px] uppercase text-gray-400 tracking-wide">Net Assets</p>
                            <p data-summary-key="total_equity"
                                data-raw-value="{{ $balanceSheet['total_equity']['current'] }}" x-data="countUp('{{ $balanceSheet['total_equity']['current'] }}')"
                                x-on:update-value="update($event.detail.value)"
                                class="text-lg font-semibold text-[#333] mt-0.5 tabular-nums" x-text="displayValue">
                                {{ numberFormat($balanceSheet['total_equity']['current']) }}
                            </p>
                        </div>
                    </div>

                    <!-- Toggle Buttons -->
                    <div class="flex gap-2">
                        <button type="button"
                            class="px-3 py-1 border border-gray-300 rounded text-xs text-gray-500 hover:text-gray-700 hover:border-gray-400"
                            onclick="document.dispatchEvent(new CustomEvent('balance-sheet:expand-all'))">
                            Expand All
                        </button>
                        <button type="button"
                            class="px-3 py-1 border border-gray-300 rounded text-xs text-gray-500 hover:text-gray-700 hover:border-gray-400"
                            onclick="document.dispatchEvent(new CustomEvent('balance-sheet:collapse-all'))">
                            Collapse All
                        </button>
                    </div>
                </div>

                <!-- Filter Modal -->
                <div>
                    <x-report-filter-modal title="Balance Sheet Options">
                        <x-fiscal-year-select />

                        <div>
                            <label class="block text-xs text-gray-500 mb-1">As of Date:</label>
                            <input type="date" name="as_of_date" value="{{ $asOfDate }}" data-filter
                                class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Compare with:</label>
                            <select name="compare_periods" id="compare_periods_select" data-filter
                                class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                <option value="0" {{ $comparePeriods == 0 ? 'selected' : '' }}>None</option>
                                @for ($i = 1; $i <= 11; $i++)
                                    <option value="{{ $i }}" {{ $comparePeriods == $i ? 'selected' : '' }}>
                                        {{ $i }} {{ $i == 1 ? 'period' : 'periods' }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Period type:</label>
                            <select name="period_type" id="period_type_select" data-filter
                                class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                <option value="month" {{ $periodType == 'month' ? 'selected' : '' }}>Month</option>
                                <option value="quarter" {{ $periodType == 'quarter' ? 'selected' : '' }}>Quarter</option>
                                <option value="year" {{ $periodType == 'year' ? 'selected' : '' }}>Year</option>
                                <option value="fiscal_year" {{ $periodType == 'fiscal_year' ? 'selected' : '' }}>Fiscal
                                    Year</option>
                                <option value="custom_date" {{ $periodType == 'custom_date' ? 'selected' : '' }}>Custom Date</option>
                            </select>
                        </div>

                        <div id="custom_compare_dates" style="display: {{ $periodType == 'custom_date' ? 'block' : 'none' }}" class="md:col-span-2">
                            <label class="block text-xs text-stone-500 mb-1">Compare Date:</label>
                            <input type="date" name="compare_date" value="{{ request('compare_date') }}" data-filter
                                class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 py-2 border-t mt-2">
                            <label class="flex items-center text-xs text-gray-500 cursor-pointer">
                                <input type="checkbox" name="show_zero_balance" data-filter class="mr-2"
                                    {{ request('show_zero_balance') ? 'checked' : '' }}>
                                Show zero balance
                            </label>
                            <label class="flex items-center text-xs text-gray-500 cursor-pointer">
                                <input type="checkbox" name="show_variance_amount" data-filter class="mr-2"
                                    {{ request('show_variance_amount') ? 'checked' : '' }}>
                                Variance (Amount)
                            </label>
                            <label class="flex items-center text-xs text-gray-500 cursor-pointer">
                                <input type="checkbox" name="show_variance_percentage" data-filter class="mr-2"
                                    {{ request('show_variance_percentage') ? 'checked' : '' }}>
                                Variance (%)
                            </label>
                        </div>
                    </x-report-filter-modal>
                </div>

                @include('components.financial-narrative', ['narrative' => $narrative ?? []])

                <!-- Balance Sheet Table -->
                <div id="balance-sheet-table">
                    @include('pages.account.reports.partials.balance-sheet-table')
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $period = \App\Models\AccountingPeriod::findPeriodForDate(now()->format('Y-m-d'));

    $fromDate = $period
        ? $period->start_date->format('Y-m-d')
        : now()->startOfMonth()->format('Y-m-d');

    $toDate = now()->format('Y-m-d');
@endphp

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

        // Account drill-down functionality
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.account-name');
            if (!target) return;

            const accountId = target.dataset.accountId;
            if (!accountId) return;

            const fromDate = document.querySelector('[name="from_date"]')?.value || @json($fromDate);
            const toDate = document.querySelector('[name="to_date"]')?.value || @json($toDate);

            const params = new URLSearchParams({
                account_id: accountId,
                from_date: fromDate,
                to_date: toDate
            });

            // window.location.href =
            const url = '{{ route('account.report.ledger') }}?' + params.toString();

            window.open(url, '_blank');
        });

        // Balance check: Assets = Liabilities + Equity → pulse green/red border
        (function() {
            const card = document.getElementById('balance-sheet-card');
            const summary = document.getElementById('balance-sheet-summary');
            if (!card || !summary) return;

            const readRaw = (key) => {
                const el = summary.querySelector(`[data-summary-key="${key}"]`);
                if (!el) return 0;
                // Prefer data-raw-value; fall back to parsed text
                const raw = el.getAttribute('data-raw-value');
                const val = raw !== null ? raw : (el.textContent || '').replace(/[^\d.-]/g, '');
                const n = parseFloat(val);
                return isNaN(n) ? 0 : n;
            };

            const runCheck = () => {
                const assets = readRaw('total_assets');
                const liabilities = readRaw('total_liabilities');
                const equity = readRaw('total_equity');
                const diff = Math.abs(assets - (liabilities + equity));
                const balanced = diff < 0.01; // tolerate floating point rounding

                card.classList.remove('balance-success', 'balance-error');
                // Force reflow so the animation restarts if the class was just removed
                void card.offsetWidth;
                card.classList.add(balanced ? 'balance-success' : 'balance-error');

                // Clear after the fade-out completes (~5.5s border-fade)
                clearTimeout(card._balanceTimer);
                card._balanceTimer = setTimeout(() => {
                    card.classList.remove('balance-success', 'balance-error');
                }, 5700);
            };

            // Initial check once Alpine has rendered
            window.addEventListener('load', () => setTimeout(runCheck, 300));

            // Re-run whenever the summary is replaced by ReportFilter
            new MutationObserver(() => {
                clearTimeout(window._balanceDebounce);
                window._balanceDebounce = setTimeout(runCheck, 200);
            }).observe(summary, {
                childList: true,
                subtree: true,
                characterData: true
            });
        })();

        $(document).ready(function() {
            function toggleCustomDate() {
                var periodType = $('#period_type_select').val();
                if (periodType === 'custom_date') {
                    $('#custom_compare_dates').css('display', 'block');
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
            url: '{{ route('account.report.balance-sheet') }}',
            summarySelector: '#balance-sheet-summary',
            tableSelector: '#balance-sheet-table',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
