@extends('layouts.auth')

@section('title', 'Payroll Liabilities by Statutory Body')

@section('content')
    <x-acccount.loader />
    <div class="px-4 py-8 mx-auto">

        <!-- Header -->
        <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-4">
            <div>
                <h2 class="text-2xl font-light text-gray-800">Payroll Liabilities by Statutory Body</h2>
                <p class="text-sm text-gray-500">Aggregated balances per statutory deduction (PAYE, EPF, SOCSO, etc.) across all pay runs.</p>
            </div>
            <a href="{{ route('payroll.liabilities.index') }}"
                class="px-4 py-2 text-xs bg-[#0078C8] text-white rounded hover:bg-[#006bb0]">
                <i class="fa-solid fa-money-bill-wave mr-1"></i> Pay Liabilities
            </a>
        </div>

        <!-- Filters Section -->
        <div class="mb-4 bg-white rounded-[5px] border border-gray-200 overflow-hidden">
            <form method="GET" id="filter-form">
                <div class="p-6 border-gray-200">
                    <div class="flex flex-wrap items-end gap-4">

                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Statutory Body</label>
                            <select name="pay_item_id"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All statutory bodies</option>
                                @foreach ($payItems as $item)
                                    <option value="{{ $item->id }}" {{ (string) $pay_item_id === (string) $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                            <select name="status"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All</option>
                                <option value="overdue"     {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="due_soon"    {{ $status === 'due_soon' ? 'selected' : '' }}>Due Soon</option>
                                <option value="outstanding" {{ $status === 'outstanding' ? 'selected' : '' }}>Outstanding</option>
                                <option value="paid"        {{ $status === 'paid' ? 'selected' : '' }}>Fully Paid</option>
                            </select>
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">From (Period Start)</label>
                            <input type="date" name="date_from" value="{{ $date_from }}"
                                class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">To (Period End)</label>
                            <input type="date" name="date_to" value="{{ $date_to }}"
                                class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <x-default-page />
                        </div>

                        <div class="flex gap-2 items-end mt-1">
                            <button type="button" id="search-btn"
                                class="px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                <span id="search-text">SEARCH</span>
                                <span id="search-loader" class="hidden ml-2">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                            <button type="button" id="reset-btn"
                                class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">
                                RESET
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="bg-white border {{ $totals['overdue_count'] > 0 ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] uppercase font-semibold text-gray-500">Overdue Amount</p>
                    <i class="fa-solid fa-triangle-exclamation {{ $totals['overdue_count'] > 0 ? 'text-red-500' : 'text-gray-300' }}"></i>
                </div>
                <p class="mt-2 text-2xl font-bold {{ $totals['overdue_count'] > 0 ? 'text-red-600' : 'text-gray-800' }}">
                    {{ numberFormat($totals['overdue_amount']) }}
                </p>
                <p class="text-[10px] text-gray-400 mt-1">{{ $totals['overdue_count'] }} item(s)</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] uppercase font-semibold text-gray-500">Total Outstanding</p>
                    <i class="fa-solid fa-clock-rotate-left text-[#0078C8]"></i>
                </div>
                <p class="mt-2 text-2xl font-bold text-[#0078C8]">{{ numberFormat($totals['outstanding']) }}</p>
                <p class="text-[10px] text-gray-400 mt-1">Awaiting remittance</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] uppercase font-semibold text-gray-500">Paid (Filtered Period)</p>
                    <i class="fa-solid fa-circle-check text-green-500"></i>
                </div>
                <p class="mt-2 text-2xl font-bold text-green-600">{{ numberFormat($totals['paid_amount']) }}</p>
                <p class="text-[10px] text-gray-400 mt-1">Already remitted</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] uppercase font-semibold text-gray-500">Statutory Bodies</p>
                    <i class="fa-solid fa-building-columns text-gray-500"></i>
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-800">{{ $totals['body_count'] }}</p>
                <p class="text-[10px] text-gray-400 mt-1">Active deduction types</p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div id="statutory-body-table">
                @include('pages.payroll.liabilities.partials.statutory-body-table')
            </div>
        </div>

        <p class="text-xs text-gray-500 italic mt-3">
            Statutory due dates are calculated as pay-period end + {{ config('accounting.payroll_statutory_grace_days', 15) }} days
            (configurable in <code>config/accounting.php</code>).
        </p>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        ReportFilter.init({
            url: '{{ route('payroll.liabilities.by-statutory-body') }}',
            tableSelector: '#statutory-body-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
