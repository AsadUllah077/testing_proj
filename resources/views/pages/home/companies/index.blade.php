@extends('layouts.auth')

@section('title', 'Company Information')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden  sm:rounded-lg">

                    <!-- Header -->
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-800">Company Information</h2>
                        <div class="flex gap-2">


                            <!-- Column Filter -->
                            <x-columns name="companies" :columns="[
                                'date' => 'Date',
                                'name' => 'Name',
                                'phone' => 'Phone',
                                'mobile' => 'Mobile',
                                'email' => 'Email',
                                'country' => 'Country',
                                'state' => 'State',
                                'currency' => 'Currency',
                                'favicon' => 'Favicon',
                                'logo' => 'Logo',
                                'signature' => 'Signature',
                            ]" />
                            <a href="{{ route('home.companies.create') }}"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[#0078C8] hover:bg-[#0078C8]/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">ADD
                                NEW</a>
                        </div>
                    </div>

                    <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                        <form method="GET" class="flex flex-wrap items-end gap-4" id="filter-form">
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                <input type="date" name="from_date" value="{{ request('from_date') }}"
                                    class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                <input type="date" name="to_date" value="{{ request('to_date') }}"
                                    class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                <input type="text" id="searchInput" name="search" value="{{ request('search') }}"
                                    placeholder="Search..."
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <x-default-page />
                            </div>
                            <x-filter-button />
                        </form>
                    </div>

                    <div id="companies-table">
                        @include('pages.home.companies.table')
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function searchCompanies() {
            const searchValue = document.getElementById('searchInput').value;
            const perPage = document.getElementById('per_page').value;
            window.location.href = `?search=${searchValue}&per_page=${perPage}`;
        }

        function resetSearch() {
            window.location.href = `?`;
        }

        function changePerPage() {
            const perPage = document.getElementById('per_page').value;
            const search = document.getElementById('searchInput').value;
            window.location.href = `?per_page=${perPage}&search=${search}`;
        }

        function toggleMenu(id) {
            const menu = document.getElementById(`menu-${id}`);
            document.querySelectorAll('[id^="menu-"]').forEach(m => {
                if (m.id !== `menu-${id}`) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            if (!event.target.closest('button')) {
                document.querySelectorAll('[id^="menu-"]').forEach(m => m.classList.add('hidden'));
            }
        });

        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => cb.checked = this.checked);
        });
    </script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('home.companies.index') }}',
            tableSelector: '#companies-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
