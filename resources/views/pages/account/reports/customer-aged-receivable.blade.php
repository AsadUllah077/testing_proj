@extends('layouts.auth')

@section('title', 'Customer Aged Receivable')

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
                    <div x-data="{}"
                        class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <div class="md:mb-0 mb-2">
                            <h2 class="text-stone-950 text-xl font-normal">Customer Aged Receivable</h2>
                            <p class="text-xs text-gray-500 mt-1">Trade customer balances only — excludes statutory tax receivables</p>
                        </div>
                        <div class="flex items-center gap-2 relative ">

                            <x-update-button />

                            <button @click="$dispatch('open-report-filter')"
                                class="px-4 py-1 bg-white border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center">
                                <span class="material-symbols-outlined text-xs font-normal">filter_list</span>
                            </button>

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
                                                data-export-url="{{ route('account.report.customer-aged-receivable.pdf') }}"
                                                data-export-type="pdf">
                                                <i class="far fa-file-pdf"></i> PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button"
                                                class="export-btn block px-4 py-2 hover:bg-[#0078C8] hover:text-white w-full text-left"
                                                onclick="return false;"
                                                data-export-url="{{ route('account.report.customer-aged-receivable.csv') }}"
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
                                <div class="xero-report-header relative report-header" style="text-align: left; margin: 0; border-bottom: none; padding-bottom: 0;">
                                    <h1 class="xero-report-title">Customer Aged Receivable</h1>
                                    <div class="xero-company-name">
                                        {{ loggedCompanyId()['company_name'] ?? 'Company Name' }}</div>
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
                                        <p class="xero-summary-label">Total Customer Receivables</p>
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
                                    <button type="button" class="xero-toggle-btn"
                                        onclick="document.dispatchEvent(new CustomEvent('aging:expand-all'))">Expand All</button>
                                    <button type="button" class="xero-toggle-btn"
                                        onclick="document.dispatchEvent(new CustomEvent('aging:collapse-all'))">Collapse All</button>
                                </div>
                            </div>

                            <!-- Filter Modal -->
                            <div>
                                <x-report-filter-modal title="Customer Aged Receivable Options">
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
                                            <option value="4x1" {{ $period == '4x1' ? 'selected' : '' }}>4 Periods of 1 Month</option>
                                            <option value="5x15" {{ $period == '5x15' ? 'selected' : '' }}>5 Periods of 15 Days</option>
                                            <option value="6x1" {{ $period == '6x1' ? 'selected' : '' }}>6 Periods of 1 Month</option>
                                        </select>
                                    </div>

                                    <div>
                                        <x-default-page />
                                    </div>
                                </x-report-filter-modal>
                            </div>

                            {{-- AI Payment Behavior Insights --}}
                            @if (!empty($predictions))
                                @php
                                    $highRisk = collect($predictions)->filter(fn($p) => ($p['risk_label'] ?? '') === 'high');
                                    $medRisk  = collect($predictions)->filter(fn($p) => ($p['risk_label'] ?? '') === 'medium');
                                    $avgDays  = collect($predictions)->filter(fn($p) => $p['avg_days_to_pay'] !== null)->avg('avg_days_to_pay');
                                @endphp
                                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mt-4">
                                    <div class="flex items-start gap-2 mb-3">
                                        <div class="bg-indigo-100 rounded-full p-1.5 mt-0.5 shrink-0">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-indigo-700 uppercase tracking-wide">Payment Behavior Insights</p>
                                            <p class="text-xs text-indigo-600 mt-0.5">Based on historical payment patterns across {{ count($predictions) }} customers</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                                        <div class="bg-white rounded p-2 border border-indigo-100">
                                            <p class="text-lg font-bold text-gray-800">{{ $avgDays ? round($avgDays, 0) : '—' }}</p>
                                            <p class="text-[10px] text-gray-500 uppercase">Avg Days to Pay</p>
                                        </div>
                                        <div class="bg-white rounded p-2 border border-red-100">
                                            <p class="text-lg font-bold text-red-600">{{ $highRisk->count() }}</p>
                                            <p class="text-[10px] text-gray-500 uppercase">High Risk</p>
                                        </div>
                                        <div class="bg-white rounded p-2 border border-yellow-100">
                                            <p class="text-lg font-bold text-yellow-700">{{ $medRisk->count() }}</p>
                                            <p class="text-[10px] text-gray-500 uppercase">Medium Risk</p>
                                        </div>
                                        <div class="bg-white rounded p-2 border border-green-100">
                                            <p class="text-lg font-bold text-green-600">{{ count($predictions) - $highRisk->count() - $medRisk->count() }}</p>
                                            <p class="text-[10px] text-gray-500 uppercase">Low Risk</p>
                                        </div>
                                    </div>
                                    @if ($highRisk->count() > 0)
                                        <div class="mt-3 text-xs text-indigo-800">
                                            <strong>Priority collection:</strong>
                                            {{ $highRisk->keys()->take(5)->implode(', ') }}
                                            @if ($highRisk->count() > 5) and {{ $highRisk->count() - 5 }} more @endif
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div id="aged-receivable-table">
                                @include('pages.account.reports.partials.customer-aged-receivable-table')
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.dropdown-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const menu = this.nextElementSibling;
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                menu.classList.toggle('hidden');
            });
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });
    </script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        ReportFilter.init({
            url: '{{ route('account.report.customer-aged-receivable') }}',
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
