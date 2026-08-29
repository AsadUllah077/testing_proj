@extends('layouts.auth')

@section('title', 'Product Issue')

@section('content')
    <x-acccount.loader />
<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header Bar -->
        <div class="mb-4 bg-white rounded-md border border-gray-200 p-5 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center ">
            <div class="flex flex-wrap items-center gap-4">
                <h2 class="text-gray-800 text-base font-semibold tracking-wide">Product Issue</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                        {{ $issues->total() }} Total
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <x-columns :columns="[
                    'issue_date' => 'Date Issued',
                    'product_id' => 'Product ID',
                    'product_title' => 'Product Title',
                    'reference' => 'Reference',
                    'employee' => 'Employee',
                    'quantity' => 'Qty',
                    'issued_by' => 'Issued By',
                    'status' => 'Status',
                    'returned_on' => 'Returned On',
                ]" name="product_issue_columns" />

                @if(auth()->user()->hasPermission('warehouse.product-issue.export-pdf') || auth()->user()->hasPermission('warehouse.product-issue.export-excel'))
                <!-- Export Dropdown -->
                <div class="relative">
                    <button onclick="toggleDropdown('exportDropdown')" class="dropdown-btn px-4 py-1.5 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-semibold tracking-wide transition-colors">
                        EXPORT ▼
                    </button>
                    <div id="exportDropdown" class="dropdown-menu hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                        <ul class="py-1 text-sm">
                            @if(auth()->user()->hasPermission('warehouse.product-issue.export-pdf'))
                            <li>
                                <button onclick="startExport('pdf','product-issue')"
                                    class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    PDF
                                </button>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('warehouse.product-issue.export-excel'))
                            <li>
                                <button onclick="startExport('xlsx','product-issue')"
                                    class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Excel
                                </button>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
                @endif

                @if(auth()->user()->hasPermission('warehouse.product-issue.create'))
                <a href="{{ route('warehouse.product-issue.create') }}"
                    class="px-4 py-1.5 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#0066a0] transition-colors tracking-wide inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    ISSUE NEW PRODUCT
                </a>
                @endif
            </div>
        </div>

        @php $hasActiveFilters = request()->hasAny(['status', 'category', 'search', 'date_from', 'date_to']); @endphp

        <!-- Table Card -->
        <div class="bg-white rounded-md border border-gray-200 overflow-hidden ">
            <!-- Filter Toggle Button -->
            <div class="flex justify-end px-4 pt-3">
                <button type="button" id="filtersToggleBtn" onclick="toggleFilters()"
                    title="Toggle Filters"
                    class="p-2 rounded-lg transition-colors {{ $hasActiveFilters ? 'bg-[#0078C8] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </button>
            </div>

            <!-- Filters Section -->
            <div id="filtersSection" class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 {{ $hasActiveFilters ? '' : 'hidden' }}">
                <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">

                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Category</label>
                        <select name="category" id="categoryFilter" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                        <select name="status" id="statusFilter" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <option value="All">All Status</option>
                            <option value="Issued" {{ request('status') == 'Issued' ? 'selected' : '' }}>Issued</option>
                            <option value="Returned" {{ request('status') == 'Returned' ? 'selected' : '' }}>Returned</option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Issue Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>

                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Issue Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>

                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Per Page</label>
                        <select name="per_page" id="perPageSelect" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            @foreach ([10, 20, 30, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) request('per_page', 20) === $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                        <div class="flex gap-2">
                            <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Search ID, Product, Reference, Employee..." class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <button type="button" id="search-btn" class="px-4 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors inline-flex items-center gap-1">
                                <span id="search-text"><i class="fas fa-search"></i></span>
                                <span id="search-loader" class="hidden">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                            <button type="button" id="reset-btn" class="px-4 py-2.5 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                <i class="fas fa-redo-alt"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="issue-table">
                @include('pages.products.warehouse.product-issue.partials.table')
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100" id="pagination-wrapper">
                {{ $issues->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        const isHidden = dropdown.classList.contains('hidden');
        
        // Hide all other dropdowns
        document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
            el.classList.add('hidden');
        });
        
        // Toggle the clicked one
        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('[id$="Dropdown"]').forEach(m => m.classList.add('hidden'));
        }
    });

    function returnProduct(id) {
        Swal.fire({
            title: 'Confirm Return',
            text: "Mark this asset as returned? The inventory status will be updated automatically.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0078C8',
            cancelButtonColor: '#9CA3AF',
            confirmButtonText: 'Yes, returned'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/warehouse/product-issue/${id}/return`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong. Please try again.'
                        });
                    }
                });
            }
        })
    }
    function toggleFilters() {
        const section = document.getElementById('filtersSection');
        const btn = document.getElementById('filtersToggleBtn');
        const isHidden = section.classList.toggle('hidden');
        if (isHidden) {
            btn.classList.remove('bg-[#0078C8]', 'text-white');
            btn.classList.add('bg-gray-100', 'text-gray-500', 'hover:bg-gray-200');
        } else {
            btn.classList.remove('bg-gray-100', 'text-gray-500', 'hover:bg-gray-200');
            btn.classList.add('bg-[#0078C8]', 'text-white');
        }
    }

    function ensureReferenceColumnVisible() {
        const columnStorageKey = 'product_issue_columns';
        const migrationKey = 'product_issue_columns_reference_visible';

        try {
            if (localStorage.getItem(migrationKey)) {
                return;
            }

            const savedColumns = JSON.parse(localStorage.getItem(columnStorageKey) || 'null');
            if (Array.isArray(savedColumns) && !savedColumns.includes('reference')) {
                const productTitleIndex = savedColumns.indexOf('product_title');
                const insertAt = productTitleIndex >= 0 ? productTitleIndex + 1 : savedColumns.length;
                savedColumns.splice(insertAt, 0, 'reference');
                localStorage.setItem(columnStorageKey, JSON.stringify(savedColumns));
            }

            localStorage.setItem(migrationKey, '1');
        } catch (error) {
            console.warn('Unable to migrate product issue columns.', error);
        }
    }

    // Initialize Report Filter
    document.addEventListener('DOMContentLoaded', function() {
        ensureReferenceColumnVisible();
        if (typeof applyColumnVisibility === 'function') {
            applyColumnVisibility();
        }

        document.addEventListener('tableUpdated', function() {
            if (typeof applyColumnVisibility === 'function') {
                applyColumnVisibility();
            }
        });

        document.getElementById('statusFilter')?.addEventListener('change', () => ReportFilter.debouncedFilter());
        document.getElementById('categoryFilter')?.addEventListener('change', () => ReportFilter.debouncedFilter());
        document.getElementById('perPageSelect')?.addEventListener('change', () => ReportFilter.debouncedFilter());
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); ReportFilter.debouncedFilter(); }
        });
        document.getElementById('filter-form')?.addEventListener('submit', function(e) { e.preventDefault(); });

        ReportFilter.init({
            url: '{{ route('warehouse.product-issue.index') }}',
            tableSelector: '#issue-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250,
        });
    });
</script>
<script src="{{ asset('assets/js/report-filter.js') }}"></script>
@endpush
