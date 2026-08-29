@extends('layouts.auth')

@section('title', 'Scan Logs')

@section('content')
<x-acccount.loader />
<div class="mt-4">
    <div class="mx-auto px-3">

        {{-- Header --}}
        <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center mb-4">
            <div>
                <h2 class="text-stone-950 text-xl font-normal">Scan Logs</h2>
                <p class="text-gray-500 text-sm mt-0.5">All product barcode scan events across all locations</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('warehouse.scan.location-a') }}"
                    class="px-4 py-2 bg-yellow-400 text-yellow-950 text-xs font-semibold rounded-lg hover:bg-yellow-500 transition flex items-center gap-2 ">
                    <i class="fa-solid fa-barcode w-4 h-4"></i>
                    Scan at Photography (A)
                </a>
                <a href="{{ route('warehouse.scan.location-b') }}"
                    class="px-4 py-2 bg-green-500 text-white text-xs font-semibold rounded-lg hover:bg-green-600 transition flex items-center gap-2 ">
                    <i class="fa-solid fa-barcode w-4 h-4"></i>
                    Scan at Warehouse#2 (B)
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-gray-200  p-5 mb-4">
            <form method="GET" id="filterForm" class="flex flex-wrap items-end gap-4">

                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Location</label>
                    <select name="location"
                        class="select2 w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] outline-none">
                        <option value="">All Locations</option>
                        <option value="A" {{ request('location') === 'A' ? 'selected' : '' }}>A — Photography (Yellow)</option>
                        <option value="B" {{ request('location') === 'B' ? 'selected' : '' }}>B — Warehouse#2 (Green)</option>
                    </select>
                </div>

                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Barcode</label>
                    <input type="text" name="barcode" value="{{ request('barcode') }}"
                        placeholder="Search barcode…"
                        class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] outline-none">
                </div>

                <div class="flex-1 min-w-[130px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] outline-none">
                </div>

                <div class="flex-1 min-w-[130px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] outline-none">
                </div>

                <x-filter-button />
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">

            {{-- Summary pills --}}
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex flex-wrap gap-3 items-center">
                <span class="text-sm text-gray-600 font-medium">
                    {{ $logs->total() }} scan{{ $logs->total() !== 1 ? 's' : '' }} found
                </span>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">
                    📷 Photography (A): {{ $logs->getCollection()->where('location', 'A')->count() }} on this page
                </span>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                    🏭 Warehouse#2 (B): {{ $logs->getCollection()->where('location', 'B')->count() }} on this page
                </span>
            </div>

            <div class="overflow-x-auto" id="logs-table">
                @include('pages.products.warehouse.scan.partials._logs_table')
            </div>

            {{-- Pagination --}}
            @if ($logs->hasPages())
                <div class="px-5 py-4 border-t border-gray-100" id="pagination-wrapper">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/report-filter.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        ReportFilter.init({
            url: '{{ route('warehouse.scan.logs') }}',
            tableSelector: '#logs-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filterForm'),
            debounceDelay: 250
        });
    });
</script>
@endpush
