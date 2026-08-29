@extends('layouts.auth')
@section('title')
    Recurring Bills
@endsection

@section('content')
    <x-acccount.loader />
    <div class="p-4">

        {{-- ── Header ──────────────────────────────────────────────────────────── --}}
        <div class="flex justify-between items-center mb-5 bg-white p-5">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Recurring Bills</h1>
                <p class="text-xs text-gray-500 mt-0.5">Automated bill schedules — generated daily at 07:00</p>
            </div>
            <a href="{{ route('account.recurring-bill.create') }}"
                class="bg-[#0078C8] text-white text-sm font-semibold px-4 py-2 rounded-[3px] flex items-center gap-1.5">
                <span class="text-base leading-none">+</span> New Schedule
            </a>
        </div>

        {{-- ── Stats ───────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Active</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Paused</p>
                <p class="text-2xl font-bold text-amber-500 mt-1">{{ $stats['paused'] }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Completed</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['completed'] }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Due Today</p>
                <p class="text-2xl font-bold text-[#0078C8] mt-1">{{ $stats['due_today'] }}</p>
            </div>
        </div>

        {{-- ── Filters ─────────────────────────────────────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-[5px] p-4 mb-4">
            <form method="GET" class="flex flex-wrap gap-3 items-center w-full justify-end" id="filter-form">

                <!-- Search -->
                <div class="flex flex-col">
                    <label for="search" class="block text-xs text-gray-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by title or vendor…"
                        class="w-56 flex-shrink-0 border border-gray-300 rounded-[3px] px-3 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                </div>

                <!-- Status -->
                <div class="flex flex-col">
                    <label for="status" class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status"
                        class="select2 w-auto min-w-[180px] border border-gray-300 rounded-[3px] px-3 py-1.5 text-sm focus:outline-none focus:border-[#0078C8]">
                        <option value="">All statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed
                        </option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled
                        </option>
                    </select>
                </div>

                <div class="flex flex-col">
                    <x-default-page />
                </div>

                <!-- Filter Button -->
                <div class="flex gap-2 items-end mt-5">
                    <button type="button" id="search-btn"
                        class="text-sm px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                        <span id="search-text">SEARCH</span>
                        <span id="search-loader" class="hidden ml-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button>
                    <button type="button" id="reset-btn"
                        class="text-sm px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                </div>
            </form>
        </div>

        {{-- ── Table ───────────────────────────────────────────────────────────── --}}
        <div id="recurring-bill-table">
            @include('pages.account.recurring-bill.table')
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.recurring-bill.index') }}',
            tableSelector: '#recurring-bill-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        $(document).ready(function() {
            $('.select2').select2({
                width: 'auto'
            });
        });
    </script>
@endpush
