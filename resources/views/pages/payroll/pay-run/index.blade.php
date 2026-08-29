@extends('layouts.auth')

@section('title', 'Pay Runs')

@section('content')
    <x-acccount.loader />
    <div class="mb-8 font-sans">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-light text-gray-800">Pay Runs</h2>
                    <p class="text-sm text-gray-500">Manage and process salary for your employees.</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('payroll.pay-runs.transactions') }}"
                        class="px-4 py-2 text-xs border border-[#0078C8] text-[#0078C8] rounded font-medium shadow-sm hover:bg-[#0078C8] hover:text-white transition">
                        VIEW ALL TRANSACTIONS
                    </a>
                    <a href="{{ route('payroll.pay-runs.create') }}"
                        class="px-4 py-2 text-xs bg-[#0078C8] text-white rounded font-medium shadow-sm hover:bg-[#006bb0] transition">
                        NEW PAY RUN
                    </a>
                </div>
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-light text-gray-800">Pay Runs</h2>
                </div>
                <form method="GET" class="flex flex-wrap gap-4 items-end" id="filter-form">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Status</label>
                        <select name="status"
                            class="select2 block w-40 border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">From Date</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="date block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">To Date</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="date block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>

                    <div>
                        <x-default-page />
                    </div>

                    <div class="flex gap-2 items-end mt-1">
                        <button type="button" id="search-btn"
                            class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                            <span id="search-text">Apply Filters</span>
                            <span id="search-loader" class="hidden ml-2">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                        <button type="button" id="reset-btn"
                            class="px-4 py-2 text-xs bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div id="pay-run-table">
                @include('pages.payroll.partials.pay-run-table')
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete the pay run and all associated accounting records!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/payroll/pay-runs/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    });
                }
            });
        }
    </script>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('payroll.pay-runs.index') }}',
            tableSelector: '#pay-run-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
