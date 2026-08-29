@extends('layouts.auth')
@section('title')
    Fixed Asset Register
@endsection

@section('content')
    <x-acccount.loader />
    <div class="p-4">

        {{-- Header --}}
        <div class="flex justify-between items-center mb-5 bg-white p-5">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Fixed Asset Register</h1>
                <p class="text-xs text-gray-500 mt-0.5">Track assets and auto-post monthly depreciation</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('account.fixed-asset.create') }}"
                    class="bg-[#0078C8] text-white text-sm font-semibold px-4 py-2 rounded-[3px] flex items-center gap-1.5">
                    <span class="text-base leading-none">+</span> Add Asset
                </a>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total Assets</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Active</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['active']) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total Cost</p>
                <p class="text-xl font-bold text-gray-700 mt-1">{{ number_format($stats['total_cost'], 2) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Net Book Value</p>
                <p class="text-xl font-bold text-blue-600 mt-1">{{ number_format($stats['total_book_value'], 2) }}</p>
            </div>
        </div>

        {{-- Depreciation command hint --}}
        <div
            class="bg-blue-50 border border-blue-100 rounded-[4px] px-4 py-2.5 mb-4 flex items-center gap-3 text-xs text-blue-700">
            <i class="fa-solid fa-terminal text-blue-400"></i>
            <span>Run monthly depreciation:
                <code class="bg-blue-100 px-1.5 py-0.5 rounded font-mono">php artisan assets:post-depreciation</code>
                &nbsp;|&nbsp; Dry run:
                <code class="bg-blue-100 px-1.5 py-0.5 rounded font-mono">--dry-run</code>
                &nbsp;|&nbsp; Specific month:
                <code class="bg-blue-100 px-1.5 py-0.5 rounded font-mono">--month=2026-03</code>
            </span>
        </div>

        {{-- Filters --}}
        <form method="GET" class="bg-white border border-gray-200 rounded-[5px] p-4 mb-4" id="filter-form">
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search name / asset number…"
                        class="w-full border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status"
                        class="select2 border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="fully_depreciated" @selected(request('status') === 'fully_depreciated')>Fully Depreciated</option>
                        <option value="disposed" @selected(request('status') === 'disposed')>Disposed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Category</label>
                    <select name="category"
                        class="select2 border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-default-page />
                </div>

                <!-- Buttons -->
                <div class="mt-6">
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

        {{-- Table --}}
        <div id="fixed-asset-table">
            @include('pages.account.fixed-asset.table')
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
            url: '{{ route('account.fixed-asset.index') }}',
            tableSelector: '#fixed-asset-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
