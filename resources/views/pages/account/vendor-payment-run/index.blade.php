@extends('layouts.auth')
@section('title', 'Vendor Payment Runs')

@section('content')
    <x-acccount.loader />
    <div class="p-4">
        <div class="flex justify-between items-center mb-4 bg-white rounded-lg p-5">
            <h1 class="text-lg font-bold text-gray-800">Vendor Payment Runs</h1>
            <a href="{{ route('account.vendor-payment-run.create') }}"
                class="bg-[#0078C8] text-white text-sm font-semibold px-4 py-1.5 rounded-[3px] hover:bg-[#0a86d8]">
                + New Payment Run
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">{{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-[5px] border border-gray-200 overflow-hidden">
            <!-- Filters Section -->
            <form method="GET" id="filter-form">
                <div class="p-6 border-gray-200">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}"
                                class=" date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}"
                                class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Reference No</label>
                            <input type="text" name="reference_no" value="{{ request('reference_no') }}"
                                placeholder="Enter Reference No"
                                class="  w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Select Vendor</label>
                            <select name="vendor_id"
                                class="ajax-vendor-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </select>
                        </div>


                        <div class="flex-1 min-w-[150px]">
                            <x-default-page />
                        </div>

                        <div class="flex gap-2 items-end mt-1">
                            <button type="button" id="search-btn"
                                class=" px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                <span id="search-text">SEARCH</span>
                                <span id="search-loader" class="hidden ml-2">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                            <button type="button" id="reset-btn"
                                class=" px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                        </div>
                    </div>
                </div>
            </form>
            <!-- Table -->
            <div id="vendor-payment-table">
                @include('pages.account.vendor-payment-run.table')
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.vendor-payment-run.index') }}',
            tableSelector: '#vendor-payment-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        // Initialize AJAX Vendor Select
        $('.ajax-vendor-select').select2({
            placeholder: '-- select vendor --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.bill.vendors.paginate') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data.map(vendor => ({
                            id: vendor.id,
                            text: vendor.name
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });
    </script>
@endpush
