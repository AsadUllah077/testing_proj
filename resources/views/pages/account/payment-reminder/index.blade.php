@extends('layouts.auth')
@section('title', 'Payment Reminders')

@section('content')
    <div class="p-4">

        {{-- Header --}}
        <div class="flex justify-between items-center mb-4 p-5 bg-white rounded-lg">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Payment Reminders</h1>
                <p class="text-sm text-gray-500">Manage overdue invoice reminders. Scheduler runs daily at 09:00.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('account.sale-invoice.index') }}"
                    class="text-sm text-[#0078C8] hover:underline font-semibold">View All Invoices →</a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">{{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">{{ session('error') }}</div>
        @endif

        {{-- Filter Form --}}
        <form id="filter-form" method="GET" class="mb-5 bg-white p-5 rounded-[5px] border border-gray-200">
            <div class="grid gap-4 md:grid-cols-4 xl:grid-cols-8">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Select Customer</label>
                    <select name="customer_id"
                        class="ajax-customer-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        @if (request('customer_id'))
                            <option value="{{ request('customer_id') }}" selected>
                                {{ \App\Models\Customer::find(request('customer_id'))->name ?? '' }}</option>
                        @endif
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Invoice No</label>
                    <input type="text" name="invoice_no" value="{{ request('invoice_no') }}"
                        placeholder="Enter Invoice No"
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Invoice From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Invoice To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Min Amount</label>
                    <input type="number" step="0.01" min="0" name="min_amount" value="{{ request('min_amount') }}"
                        placeholder="0.00"
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Max Amount</label>
                    <input type="number" step="0.01" min="0" name="max_amount" value="{{ request('max_amount') }}"
                        placeholder="0.00"
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <x-default-page />
                </div>
                <div class="flex gap-2 items-end mt-1">
                    <button type="button" id="search-btn"
                        class="px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                        <span id="search-text">SEARCH</span>
                        <span id="search-loader" class="hidden ml-2"><i class="fas fa-spinner fa-spin"></i></span>
                    </button>
                    <button type="button" id="reset-btn"
                        class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                </div>
            </div>
        </form>

        <div id="payment-reminder-content">
            @include('pages.account.payment-reminder._content')
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        ReportFilter.init({
            url: '{{ route('account.payment-reminders.index') }}',
            tableSelector: '#payment-reminder-content',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        $('.ajax-customer-select').select2({
            placeholder: '-- select customer --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.sale.customers.paginate') }}',
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
                        results: data.data.map(customer => ({
                            id: customer.id,
                            text: customer.name
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });
        // Intercept bucket pagination links (p1/p2/p3/p4 params) via AJAX
        document.addEventListener('click', function(e) {
            const link = e.target.closest('#payment-reminder-content a[href]');
            if (!link) return;

            const url = new URL(link.href);
            const isBucketPage = ['p1', 'p2', 'p3', 'p4'].some(p => url.searchParams.has(p));
            if (!isBucketPage) return;

            e.preventDefault();

            // Merge filter params with bucket page params
            const filterParams = ReportFilter.buildQueryParams();
            url.searchParams.forEach((val, key) => filterParams.set(key, val));

            ReportFilter.filter(filterParams.toString());
        });
    </script>
@endpush
