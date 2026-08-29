@extends('layouts.auth')

@section('title', 'Tax Periods')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-3">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Tax Periods</h2>
                    <p class="text-xs text-gray-500 mt-1">Manage monthly or quarterly tax accumulation and settlements.</p>
                </div>
                <div class="mt-3 md:mt-0 flex gap-3">
                    <a href="{{ route('account.tax-periods.authority-ledger') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-normal rounded hover:bg-gray-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V12zm0 3h.008v.008H6.75V12z" />
                        </svg> TAX AUTHORITY LEDGER
                    </a>
                    <a href="{{ route('account.tax-periods.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg> CREATE TAX PERIOD
                    </a>
                </div>
            </div>

            <!-- Tax Compliance Summary -->
            @if (isset($compliance))
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="bg-white border {{ $compliance['overdue_count'] > 0 ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] uppercase font-semibold text-gray-500">Overdue Filings</p>
                            <i class="fa-solid fa-triangle-exclamation {{ $compliance['overdue_count'] > 0 ? 'text-red-500' : 'text-gray-300' }}"></i>
                        </div>
                        <p class="mt-2 text-2xl font-bold {{ $compliance['overdue_count'] > 0 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $compliance['overdue_count'] }}
                        </p>
                        @if ($compliance['most_overdue_days'] > 0)
                            <p class="text-[10px] text-red-500 mt-1">Most overdue: {{ $compliance['most_overdue_days'] }} days</p>
                        @else
                            <p class="text-[10px] text-gray-400 mt-1">No overdue periods</p>
                        @endif
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] uppercase font-semibold text-gray-500">Due Soon</p>
                            <i class="fa-regular fa-clock {{ $compliance['due_soon_count'] > 0 ? 'text-amber-500' : 'text-gray-300' }}"></i>
                        </div>
                        <p class="mt-2 text-2xl font-bold {{ $compliance['due_soon_count'] > 0 ? 'text-amber-600' : 'text-gray-800' }}">
                            {{ $compliance['due_soon_count'] }}
                        </p>
                        <p class="text-[10px] text-gray-400 mt-1">Within {{ config('accounting.tax_filing_warning_days', 7) }} days</p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] uppercase font-semibold text-gray-500">Open / Settled</p>
                            <i class="fa-solid fa-folder-open text-[#0078C8]"></i>
                        </div>
                        <p class="mt-2 text-2xl font-bold text-[#0078C8]">{{ $compliance['open_count'] }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">Awaiting payment</p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] uppercase font-semibold text-gray-500">Paid</p>
                            <i class="fa-solid fa-circle-check text-green-500"></i>
                        </div>
                        <p class="mt-2 text-2xl font-bold text-green-600">{{ $compliance['paid_count'] }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">Compliant periods</p>
                    </div>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="mb-4 p-4 bg-white rounded-lg border border-black/10">
                <form class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end" id="filter-form">

                    <div class="md:col-span-2">
                        <label for="name" class="block text-xs font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" id="name" value="{{ request('name') }}"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                            placeholder="Filter by Name">
                    </div>

                    <div class="md:col-span-2">
                        <label for="type" class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                        <select name="type" id="type"
                            class="select2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                            <option value="">All Types</option>
                            <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="quarterly" {{ request('type') == 'quarterly' ? 'selected' : '' }}>Quarterly
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="status" class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status"
                            class="select2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                            <option value="">All Statuses</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="settled" {{ request('status') == 'settled' ? 'selected' : '' }}>Settled</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="start_date" class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                            class="date block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                    </div>

                    <div class="md:col-span-2">
                        <label for="end_date" class="block text-xs font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                            class="date block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                    </div>

                    <div class="md:col-span-1">
                        <x-default-page />
                    </div>

                    <div class="md:col-span-1 flex gap-2">
                        <button type="button" id="search-btn"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[#0078C8] hover:bg-[#0078C8]/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span id="search-text">Search</span>
                            <span id="search-loader" class="hidden ml-2">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                        <button type="button" id="reset-btn"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            X
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                    <x-sortable column="name" label="Name" />
                                </th>
                                <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                    <x-sortable column="type" label="Type" />
                                </th>
                                <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                    <x-sortable column="start_date" label="Start Date" />
                                </th>
                                <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                    <x-sortable column="end_date" label="End Date" />
                                </th>
                                <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                    Filing Due
                                </th>
                                <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                    <x-sortable column="status" label="Status" />
                                </th>
                                <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                    <x-sortable column="net_amount" label="Net Amount" />
                                </th>
                                <th
                                    class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tax-periods-table" class="divide-y divide-gray-200">
                            @include('pages.account.tax.periods.partials.tax-periods-table')
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <x-data-table-paginatior :paginator="$taxPeriods" />
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        ReportFilter.init({
            url: '{{ route('account.tax-periods.index') }}',
            tableSelector: '#tax-periods-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
