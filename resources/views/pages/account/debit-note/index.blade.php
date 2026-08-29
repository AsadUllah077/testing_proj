@extends('layouts.auth')
@section('title')
    Debit Notes
@endsection

@section('content')
    <x-acccount.loader />
    <div class="p-4">

        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.bill') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['route'] . '*') }}" />
                @endforeach
            </div>
        </div>

        {{-- ── Header ──────────────────────────────────────────────────────────── --}}
        <div class="flex justify-between items-center mb-5 bg-white p-5">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Debit Notes</h1>
                <p class="text-xs text-gray-500 mt-0.5">Purchase returns & supplier credit notes</p>
            </div>
            <a href="{{ route('account.debit-note.create') }}"
                class="bg-[#0078C8] text-white text-sm font-semibold px-4 py-2 rounded-[3px] flex items-center gap-1.5">
                <span class="text-base leading-none">+</span> New Debit Note
            </a>
        </div>

        {{-- ── Stats ───────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-5">
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total Notes</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">This Month</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['this_month']) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total Value</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['total_value'], 2) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Allocated</p>
                <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($stats['allocated_value'], 2) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Refunded</p>
                <p class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($stats['refunded_value'], 2) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Available Credit</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['available_value'], 2) }}</p>
            </div>
        </div>

        {{-- ── Filters ─────────────────────────────────────────────────────────── --}}
        <form method="GET" class="bg-white border border-gray-200 rounded-md p-4 mb-4" id="filter-form">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">

                <!-- Search -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by ref / vendor..."
                        class="w-full h-9 border border-gray-300 rounded text-sm px-3 focus:outline-none focus:border-[#0078C8]">
                </div>

                <!-- Vendor -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Vendor</label>
                    <select name="vendor_id" id="vendorSelect"
                        class="w-full h-9 border border-gray-300 rounded text-sm px-3 focus:outline-none focus:border-[#0078C8]">
                        <option value="">— Select vendor —</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="date w-full h-9 border border-gray-300 rounded text-sm px-3 focus:outline-none focus:border-[#0078C8]">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="date w-full h-9 border border-gray-300 rounded text-sm px-3 focus:outline-none focus:border-[#0078C8]">
                </div>

                <!-- Per Page -->
                <div> <x-default-page /> </div>

                <!-- Buttons -->
                <div class="flex gap-2">
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

            </div>
        </form>

        {{-- ── Table ───────────────────────────────────────────────────────────── --}}
        <div id="debit-note-table">
            @include('pages.account.debit-note.table')
        </div>

    </div>

    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
            class="fixed bottom-4 right-4 bg-emerald-600 text-white text-sm px-4 py-2.5 rounded-[4px] shadow-lg z-50">
            {{ session('success') }}
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.debit-note.index') }}',
            tableSelector: '#debit-note-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        $(document).ready(function() {
            $('#vendorSelect').select2({
                width: '100%',
                placeholder: '— Select vendor —',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route('account.bill.vendors.paginate') }}',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term ?? '',
                        page: params.page ?? 1
                    }),
                    processResults(data, params) {
                        params.page = params.page ?? 1;
                        return {
                            results: data.data.map(v => ({
                                id: v.id,
                                text: v.name
                            })),
                            pagination: {
                                more: !!data.next_page
                            }
                        };
                    },
                    cache: true
                }
            });
        });
    </script>
@endpush
