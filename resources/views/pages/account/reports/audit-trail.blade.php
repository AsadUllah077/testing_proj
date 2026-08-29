@extends('layouts.auth')

@section('title', 'Audit Trail')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div x-data="{}"
                        class="mt-5 p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Audit Trail</h2>
                        <div class="flex items-center gap-2 relative">
                            <!-- Report Options Button -->
                            <button @click="$dispatch('open-report-filter')"
                                class="px-4 py-1 bg-white border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center">
                                <span class="material-symbols-outlined text-xs font-normal">filter_list</span>
                            </button>
                            <a href="javascript:void(0)" onclick="window.history.back(); return false;"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                <- Back
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-md border border-[#C0BFBF] my-4">
                        <div class="rounded-xl shadow-sm p-4 space-y-8">

                            <!-- Header -->
                            <div class="border-b border-gray-100 pb-4">
                                <div class="flex items-start justify-between gap-6">
                                    <div>
                                        <h1 class="text-2xl font-semibold text-gray-800">Audit Trail</h1>
                                        <div class="text-gray-600 text-sm mt-1 mb-1">
                                            {{ loggedCompanyId()['company_name'] ?? 'Company Name' }}
                                        </div>
                                        <p class="text-sm text-gray-500">
                                            Track all accounting transactions and changes
                                        </p>
                                    </div>
                                </div>

                                <!-- Filter Modal -->
                                <x-report-filter-modal title="Audit Trail Options">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Source</label>
                                        <select name="source" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="all"
                                                {{ request('source', 'all') == 'all' ? 'selected' : '' }}>All Activity
                                            </option>
                                            <option value="transactions"
                                                {{ request('source') == 'transactions' ? 'selected' : '' }}>Transaction
                                                Changes</option>
                                            <option value="reports" {{ request('source') == 'reports' ? 'selected' : '' }}>
                                                Report Access & Exports</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">User</label>
                                        <select name="user_id" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- All Users --</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}"
                                                    {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->first_name }} {{ $user->last_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Transaction Type</label>
                                        <select name="model_type" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- All Types --</option>
                                            @foreach ($modelLabels as $class => $label)
                                                <option value="{{ $class }}"
                                                    {{ request('model_type') == $class ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Report Name</label>
                                        <select name="report_name" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- All Reports --</option>
                                            @foreach ($reportNames as $name)
                                                <option value="{{ $name }}"
                                                    {{ request('report_name') == $name ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Action</label>
                                        <select name="action" data-filter
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                            <option value="">-- All Actions --</option>
                                            <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>
                                                Created</option>
                                            <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>
                                                Updated</option>
                                            <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>
                                                Deleted</option>
                                            <option value="view" {{ request('action') == 'view' ? 'selected' : '' }}>
                                                Viewed Report</option>
                                            <option value="filter" {{ request('action') == 'filter' ? 'selected' : '' }}>
                                                Filtered Report</option>
                                            <option value="export" {{ request('action') == 'export' ? 'selected' : '' }}>
                                                Exported Report</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">From</label>
                                        <input type="date" name="from_date" data-filter
                                            value="{{ request('from_date') }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">To</label>
                                        <input type="date" name="to_date" data-filter value="{{ request('to_date') }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
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
                                        <x-default-page />
                                    </div>
                                </x-report-filter-modal>
                            </div>

                            <!-- Summary -->
                            <div id="audit-summary-wrapper" class="w-full min-h-[56px]">
                                <div id="audit-summary" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 w-full">
                                        <p class="text-xs uppercase text-gray-600">Total Records</p>
                                        <p data-summary-key="total_logs"
                                            class="text-2xl font-semibold text-blue-700 mt-1 tabular-nums">
                                            {{ number_format($logs->total()) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="overflow-x-auto">
                                <div id="audit-trail-table">
                                    @include('pages.account.reports.partials.audit-trail-table')
                                </div>
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
    <script>
        ReportFilter.init({
            url: '{{ route('account.report.audit-trail') }}',
            summarySelector: '#audit-summary',
            tableSelector: '#audit-trail-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
