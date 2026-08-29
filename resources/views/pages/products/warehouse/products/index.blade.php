@extends('layouts.auth')

@php
    $productsPageTitle = match (request('status')) {
    'sold' => 'Sold Products',
    'issued' => 'Issued Products',
    'hold' => 'Hold Products',
    default => 'Products List',
    };
@endphp

@section('title', $productsPageTitle)

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">
                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <h2 class="text-stone-950 text-xl font-normal">
                            {{ $productsPageTitle }}
                        </h2>
                        @php
                            $hasActiveFilters = request()->hasAny([
                                'q',
                                'category',
                                'location',
                                'status',
                                'assigned_to',
                                'work_flow_stage',
                                'disposition',
                                'date_from',
                                'date_to',
                                'manual_status',
                                'updated_today',
                                'disposition_group',
                            ]);
                        @endphp
                        <div class="flex flex-wrap items-center gap-2">

                            <!-- Column Chooser -->
                            <x-columns :columns="[
                                'unique_id' => 'Unique ID',
                                'category' => 'Category',
                                'title' => 'Title',
                                'location' => 'Location',
                                'assigned_to' => 'Assigned To',
                                'disposition' => 'Disposition',
                                'work_flow_stage' => 'Work Flow Stage',
                                // 'specification' => 'Specification',
                                'last_action' => 'Last Action',
                            ]" name="product_columns" />

                            @if(auth()->user()->hasPermission('warehouse.products.export-pdf') || auth()->user()->hasPermission('warehouse.products.export-excel'))
                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="dropdown-btn px-4 py-1.5 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-semibold tracking-wide transition-colors">
                                    EXPORT ▼
                                </button>
                                <div id="exportDropdown"
                                    class="dropdown-menu hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm">
                                        <li>
                                            <button onclick="startWarehouseProductsExport('pdf')"
                                                class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                                </svg>
                                                PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="startWarehouseProductsExport('xlsx')"
                                                class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                Excel
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            @endif

                            <!-- Add Product Manually -->
                            @if(auth()->user()->hasPermission('warehouse.products.create'))
                            <a href="{{ route('warehouse.products.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2 whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                ADD PRODUCT
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Bulk Actions + Per-page toolbar -->
                    <div class="flex flex-wrap justify-between items-center gap-3 my-4 bg-white rounded-xl border border-gray-200 px-4 py-3">
                        <!-- Bulk Actions -->
                        <div class="flex flex-wrap items-center gap-2">
                            @if(auth()->user()->hasPermission('warehouse.products.bulk-delete'))
                            <button type="button" id="bulkDeleteBtn"
                                class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-red-50 hover:border-red-400 hover:text-red-600 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                disabled>
                                Delete Selected
                            </button>
                            @endif
                            @if(auth()->user()->hasPermission('warehouse.gate-pass.create'))
                                <button type="button" id="addToGatePassBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:border-[#0078C8] hover:text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled>
                                    Add to Gate Pass
                                </button>
                            @endif
                            @if(auth()->user()->hasPermission('warehouse.products.bulk-assign-tech'))
                                <button type="button" id="assignToTechBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:border-[#0078C8] hover:text-white transition-colors" title="Assign to technician">
                                    Assign To Tech
                                </button>
                            @endif
                            @if(auth()->user()->hasPermission('warehouse.products.bulk-status-change'))
                                <button type="button" id="statusChangeBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:border-[#0078C8] hover:text-white transition-colors">
                                    Change Status
                                </button>
                            @endif
                            @if(auth()->user()->hasPermission('warehouse.products.edit'))
                                <button type="button" id="bulkTitleBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:border-[#0078C8] hover:text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled>
                                    Edit Selected
                                </button>
                                <button type="button" id="bulkSpecificationsBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:border-[#0078C8] hover:text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled>
                                    Add Specifications
                                </button>
                                <button type="button" id="downloadBulkUpdateTemplateBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-green-50 hover:border-green-500 hover:text-green-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled>
                                    Download Excel Template
                                </button>
                                <button type="button" id="uploadBulkUpdateBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-green-50 hover:border-green-500 hover:text-green-700 transition-colors">
                                    Upload Excel
                                </button>
                            @endif
                            @if(auth()->user()->hasPermission('warehouse.products.bulk-move-location'))
                                <button type="button" id="moveLocationBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:border-[#0078C8] hover:text-white transition-colors">
                                    Move Location
                                </button>
                            @endif
                            @if(auth()->user()->hasPermission('warehouse.products.show'))
                                <button type="button" id="bulkPrintSpecificationsBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-green-50 hover:border-green-500 hover:text-green-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled>
                                    Print Specifications
                                </button>
                            @endif
                            @if(auth()->user()->hasPermission('warehouse.products.bulk-add-part'))
                                <button type="button" id="addPartBtn"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:border-[#0078C8] hover:text-white transition-colors">
                                    Add Part
                                </button>
                            @endif
                        </div>

                        <!-- Barcode selection -->
                        <div class="flex min-w-[280px] flex-1 flex-col gap-1 sm:max-w-md">
                            {{-- <label for


                            --}}
                            <div class="flex gap-2">
                                <input type="text" id="productBarcodeInput" autocomplete="off"
                                    autocapitalize="characters" spellcheck="false" autofocus
                                    placeholder="Scan barcode or enter product ID"
                                    class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs outline-none focus:border-transparent focus:ring-2 focus:ring-[#0078C8]">
                                <button type="button" id="selectScannedProductBtn"
                                    class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-[#0078C8] px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-[#006bb0] disabled:cursor-not-allowed disabled:opacity-50">
                                    Select
                                </button>
                            </div>
                            <p id="productBarcodeStatus" class="hidden text-xs" role="status" aria-live="polite"></p>
                        </div>

                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <!-- Filter Toggle Button (top-right corner) -->
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

                                <div class="flex-1 min-w-[160px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <input type="text" name="q" placeholder="Search by ID, DPN/SN, title..."
                                        value="{{ request('q') }}"
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Category</label>
                                    <select name="category"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Categories</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ request('category') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Location</label>
                                    <select name="location"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Locations</option>
                                        @foreach ($locations as $location)
                                            <option value="{{ $location->id }}"
                                                {{ request('location') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Assigned To</label>
                                    <select name="assigned_to"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All</option>
                                        <option value="Assigned" {{ request('assigned_to') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="Unassigned" {{ request('assigned_to') == 'Unassigned' ? 'selected' : '' }}>Unassigned</option>
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Work Flow Stage</label>
                                    <select name="work_flow_stage"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Stages</option>
                                        @foreach ($workflowStages as $stage)
                                            <option value="{{ $stage->id }}"
                                                {{ request('work_flow_stage') == $stage->id ? 'selected' : '' }}>
                                                {{ $stage->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Disposition</label>
                                    <select name="disposition"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Dispositions</option>
                                        @foreach ($dispositions as $disposition)
                                            <option value="{{ $disposition->id }}"
                                                {{ request('disposition') == $disposition->id ? 'selected' : '' }}>
                                                {{ $disposition->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[110px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                                    <select name="status"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Status</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active (Ready for Sale)</option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive (Not Ready for Sale)</option>
                                        <option value="hold" {{ request('status') == 'hold' ? 'selected' : '' }}>Hold</option>
                                        <option value="issued" {{ request('status') == 'issued' ? 'selected' : '' }}>Issued</option>
                                        <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Sold</option>
                                        <option value="scrap" {{ request('status') == 'scrap' ? 'selected' : '' }}>Scrap</option>
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>

                                <div class="flex-1 min-w-[110px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Per Page</label>
                                    <select name="per_page"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        @foreach ([10, 20, 30, 50, 100, 200, 500, 1000] as $size)
                                            <option value="{{ $size }}"
                                                {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                                                {{ $size }} / page
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <x-filter-button />
                            </form>
                        </div>

                        <!-- Selected Count Bar -->
                        <div class="px-6 py-3 bg-blue-50 border-b border-blue-200 hidden" id="selectedBar">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-blue-800">
                                        <span id="selectedCount">0</span> products selected
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if(auth()->user()->hasPermission('warehouse.products.export-pdf'))
                                        <button type="button" onclick="startWarehouseProductsExport('pdf')"
                                            class="px-3 py-1.5 bg-white border border-[#0078C8] text-[#0078C8] rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:text-white transition-colors">
                                            Export PDF
                                        </button>
                                    @endif
                                    @if(auth()->user()->hasPermission('warehouse.products.export-excel'))
                                        <button type="button" onclick="startWarehouseProductsExport('xlsx')"
                                            class="px-3 py-1.5 bg-white border border-[#0078C8] text-[#0078C8] rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:text-white transition-colors">
                                            Export Excel
                                        </button>
                                    @endif
                                    @if(auth()->user()->hasPermission('warehouse.gate-pass.create'))
                                        <button type="button" id="selectedBarAddToGatePassBtn"
                                            class="px-3 py-1.5 bg-white border border-[#0078C8] text-[#0078C8] rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                            disabled>
                                            Add to Gate Pass
                                        </button>
                                    @endif
                                    @if(auth()->user()->hasPermission('warehouse.products.edit'))
                                        <button type="button" id="selectedBarBulkTitleBtn"
                                            class="px-3 py-1.5 bg-white border border-[#0078C8] text-[#0078C8] rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:text-white transition-colors">
                                            Edit Selected
                                        </button>
                                        <button type="button" id="selectedBarBulkSpecificationsBtn"
                                            class="px-3 py-1.5 bg-white border border-[#0078C8] text-[#0078C8] rounded-lg text-xs font-medium hover:bg-[#0078C8] hover:text-white transition-colors">
                                            Add Specifications
                                        </button>
                                    @endif
                                    @if(auth()->user()->hasPermission('warehouse.products.show'))
                                        <button type="button" id="selectedBarPrintSpecificationsBtn"
                                            class="px-3 py-1.5 bg-white border border-green-600 text-green-700 rounded-lg text-xs font-medium hover:bg-green-600 hover:text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                            disabled>
                                            Print Specifications
                                        </button>
                                    @endif
                                    <button type="button" id="clearSelection"
                                        class="text-xs text-blue-600 hover:text-blue-800">
                                        Clear Selection
                                    </button>
                                </div>
                            </div>
                            <div id="selectedProductsList"
                                class="mt-3 flex max-h-28 flex-wrap gap-2 overflow-y-auto border-t border-blue-200 pt-3"
                                aria-label="Selected products"></div>
                        </div>

                        <!-- Table -->
                        <div id="product-table">
                            @include('pages.products.warehouse.products.partials.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    {{-- Export Column Selection Modal --}}
    <div id="productExportColumnsModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog"
        aria-modal="true" aria-labelledby="productExportColumnsTitle">
        <div class="flex min-h-screen items-center justify-center p-4">
            <button type="button" class="fixed inset-0 bg-black/50" onclick="hideProductExportColumnsModal()"
                aria-label="Close export columns"></button>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-xl bg-white text-left shadow-xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 id="productExportColumnsTitle" class="text-lg font-semibold text-gray-900">Select Export Columns</h3>
                            <span id="productExportFormatBadge"
                                class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-[#0078C8]"></span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Only checked columns will be included. Specifications contain RAM and SSD only.</p>
                    </div>
                    <button type="button" onclick="hideProductExportColumnsModal()"
                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Available columns</p>
                        <div class="flex gap-3 text-xs font-medium">
                            <button type="button" onclick="setAllProductExportColumns(true)" class="text-[#0078C8] hover:underline">Select all</button>
                            <button type="button" onclick="setAllProductExportColumns(false)" class="text-gray-500 hover:text-gray-800 hover:underline">Clear all</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach(\App\Exports\WarehouseProductExport::AVAILABLE_COLUMNS as $column => $heading)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 hover:border-blue-200 hover:bg-blue-50/50">
                                <input type="checkbox" value="{{ $column }}" data-product-export-column checked
                                    class="h-4 w-4 rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                                <span class="text-sm text-gray-700">{{ $heading }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p id="productExportColumnsError" class="mt-3 hidden text-sm text-red-600">Select at least one column to export.</p>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" onclick="hideProductExportColumnsModal()"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" onclick="confirmWarehouseProductsExport()"
                        class="rounded-lg bg-[#0078C8] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#006bb0]">Export Selected Columns</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Bulk Update Import Modal (separate from the existing export) --}}
    <div id="productBulkUpdateModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog"
        aria-modal="true" aria-labelledby="productBulkUpdateTitle">
        <div class="flex min-h-screen items-center justify-center p-4">
            <button type="button" class="fixed inset-0 bg-black/50" onclick="hideProductBulkUpdateModal()"
                aria-label="Close bulk product update"></button>
            <div class="relative w-full max-w-xl overflow-hidden rounded-xl bg-white text-left shadow-xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <h3 id="productBulkUpdateTitle" class="text-lg font-semibold text-gray-900">Upload Product Updates</h3>
                        <p class="mt-1 text-sm text-gray-500">Upload the completed template. Blank cells keep their current values.</p>
                    </div>
                    <button type="button" onclick="hideProductBulkUpdateModal()"
                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="productBulkUpdateForm" enctype="multipart/form-data">
                    <div class="space-y-4 px-6 py-5">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            Keep the <strong>Unique ID</strong> and headings unchanged. Use the reference sheet for valid IDs/names, or <code>__CLEAR__</code> to clear an optional field.
                        </div>
                        <div>
                            <label for="productBulkUpdateFile" class="mb-1.5 block text-sm font-medium text-gray-700">Excel file</label>
                            <input id="productBulkUpdateFile" name="file" type="file" required accept=".xlsx,.xls,.csv"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-[#0078C8] hover:file:bg-blue-100">
                        </div>
                        <div id="productBulkUpdateResult" class="hidden max-h-52 overflow-y-auto rounded-lg border px-4 py-3 text-sm"></div>
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                        <button type="button" onclick="hideProductBulkUpdateModal()"
                            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" id="submitProductBulkUpdateBtn"
                            class="rounded-lg bg-[#0078C8] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#006bb0] disabled:cursor-not-allowed disabled:opacity-60">Upload and Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Status Change Modal --}}
    <div id="statusChangeModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity ">
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Bulk Status Change</h3>
                            <p class="text-sm text-gray-500 mt-1">Change status for <span id="statusModalCount">0</span>
                                selected products</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Product Status</label>
                                <select id="bulkProductStatus"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select status</option>
                                    <option value="Active">Ready for Sale</option>
                                    <option value="Inactive">Not Ready for Sale</option>
                                    <option value="Hold">Hold</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Disposition</label>
                                <select id="statusDisposition"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select disposition</option>
                                    @foreach ($dispositions as $disposition)
                                        <option value="{{ $disposition->id }}">{{ $disposition->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Workflow Stage</label>
                                <select id="statusWorkflow"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select workflow stage</option>
                                    @foreach ($workflowStages as $stage)
                                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Location</label>
                                <select id="statusLocation"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select location</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirmStatusChange"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-sm font-medium text-white hover:bg-[#0078C8] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto">
                            UPDATE
                        </button>
                        <button type="button" onclick="hideStatusChangeModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto">
                            CLOSE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Product Edit Modal --}}
    <div id="bulkTitleModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity">
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Bulk Edit Products</h3>
                            <p class="text-sm text-gray-500 mt-1">Update <span id="bulkTitleModalCount">0</span>
                                selected products. Leave fields blank to keep their current values.</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Title</label>
                                <input type="text" id="bulkTitleInput" maxlength="255" placeholder="Keep current titles"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Workflow Stage</label>
                                <select id="bulkEditWorkflow" class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Keep current workflow stages</option>
                                    @foreach ($workflowStages as $stage)
                                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Disposition</label>
                                <select id="bulkEditDisposition" class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Keep current dispositions</option>
                                    @foreach ($dispositions as $disposition)
                                        <option value="{{ $disposition->id }}">{{ $disposition->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Location</label>
                                <select id="bulkEditLocation" class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Keep current locations</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                <select id="bulkEditStatus" class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Keep current statuses</option>
                                    <option value="Active">Ready for Sale</option>
                                    <option value="Inactive">Not Ready for Sale</option>
                                    <option value="Hold">Hold</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirmBulkTitle"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-sm font-medium text-white hover:bg-[#006bb0] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto">
                            UPDATE PRODUCTS
                        </button>
                        <button type="button" onclick="hideBulkTitleModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto">
                            CLOSE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Specifications Modal --}}
    <div id="bulkSpecificationsModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity">
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Add Bulk Specifications</h3>
                            <p class="text-sm text-gray-500 mt-1">Add the same specifications to <span id="bulkSpecificationsModalCount">0</span>
                                selected products. Existing specifications are kept; matching keys are updated.</p>
                        </div>

                        <div id="bulkSpecificationsList" class="space-y-3"></div>

                        <button type="button" id="addBulkSpecificationRow"
                            class="mt-4 px-3 py-2 border border-[#0078C8] text-[#0078C8] rounded-md text-xs font-medium hover:bg-blue-50 transition-colors">
                            + ADD ANOTHER SPECIFICATION
                        </button>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirmBulkSpecifications"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-sm font-medium text-white hover:bg-[#006bb0] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto">
                            UPDATE SPECIFICATIONS
                        </button>
                        <button type="button" onclick="hideBulkSpecificationsModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto">
                            CLOSE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Part Modal --}}
    {{-- Add Part Modal --}}
    <div id="addPartModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity">
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Add Part</h3>
                            <p class="text-sm text-gray-500 mt-1">Add part to <span id="addPartModalCount">0</span>
                                selected products</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Select Part</label>
                                <select id="part_id" name="part_id"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select a Part</option>
                                    @foreach ($parts as $part)
                                        <option value="{{ $part->id }}">{{ $part->name }} @if ($part->sku)
                                                ({{ $part->sku }})
                                            @endif
                                            - Qty: {{ $part->quantity }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                <textarea id="partNotes" name="notes" rows="3" placeholder="Enter any notes about this part..."
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirmAddPart"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-sm font-medium text-white hover:bg-[#006bb0] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto">
                            ADD PART
                        </button>
                        <button type="button" onclick="hideAddPartModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto">
                            CLOSE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Move Location Modal --}}
    {{-- Move Location Modal --}}
    <div id="moveLocationModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity">
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Move Location</h3>
                            <p class="text-sm text-gray-500 mt-1">Move <span id="moveModalCount">0</span> selected
                                products to new location</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Select Location</label>
                                <select id="newLocation" name="newLocation"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select location</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}
                                            @if ($location->code)
                                                ({{ $location->code }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Select the new warehouse location for the products
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirmMoveLocation"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-sm font-medium text-white hover:bg-[#006bb0] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto">
                            MOVE
                        </button>
                        <button type="button" onclick="hideMoveLocationModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto">
                            CLOSE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Assign to Tech Modal --}}
    <div id="assignTechModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity">
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Assign To Technician</h3>
                            <p class="text-sm text-gray-500 mt-1">Assign <span id="assignModalCount">0</span> selected
                                products</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Assign To</label>
                                <select id="technician"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select technician</option>
                                    @foreach ($technicians as $tech)
                                        <option value="{{ $tech->id }}">{{ $tech->full_name }} - {{ $tech->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="assignExtraFields">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Disposition</label>
                                <select id="assignDisposition"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select disposition</option>
                                    @foreach ($dispositions as $disposition)
                                        <option value="{{ $disposition->id }}">{{ $disposition->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Work Flow Stage</label>
                                <select id="techWorkflow"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <option value="">Select workflow stage</option>
                                    @foreach ($workflowStages as $stage)
                                        <option value="{{ $stage->id }}" data-name="{{ strtolower($stage->name) }}">{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Task Type</label>
                                <input type="text" id="taskType" placeholder="Enter task type..."
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Remarks</label>
                                <textarea id="techRemarks" rows="3" placeholder="Enter remarks"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></textarea>
                            </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirmAssignTech"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-sm font-medium text-white hover:bg-[#0078C8] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto">
                            ASSIGN
                        </button>
                        <button type="button" onclick="hideAssignTechModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto">
                            CLOSE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QR Code Modal (for manual products) --}}
    <div id="productQrModal" class="fixed inset-0 hidden overflow-y-auto" style="z-index:9999;" aria-modal="true" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="hideProductQrModal()"></div>
            <div class="relative inline-block bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-md">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">QR Code</h3>
                        <button onclick="hideProductQrModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="productQrSpinner" class="flex justify-center items-center py-10 hidden">
                        <svg class="animate-spin h-8 w-8 text-[#0078C8]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </div>
                    <div id="productQrContent" class="hidden flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg">
                        <div id="productQrImageWrap" class="mb-3 p-4 bg-white rounded-lg shadow">
                            <!-- QR image rendered here -->
                        </div>
                        <p id="productQrUniqueId" class="text-sm font-mono text-gray-700 bg-gray-100 px-3 py-1 rounded"></p>
                    </div>
                    <div id="productQrError" class="hidden text-center text-red-600 text-sm py-4"></div>
                    <div class="mt-5 flex justify-center gap-3">
                        <button id="productQrDownloadBtn" onclick="downloadProductQr()"
                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-2 transition-colors disabled:opacity-40" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download
                        </button>
                        <button id="productQrPrintBtn" onclick="printProductQrFromModal()"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 flex items-center gap-2 transition-colors disabled:opacity-40" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z" />
                            </svg>
                            Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal component --}}
    <x-delete-confirmation />
@endsection

@push('styles')
    <style>
        .dropdown-menu {
            transition: all 0.2s;
            z-index: 50;
        }

        .select2-container--default .select2-selection--single {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            height: 42px;
            padding: 5px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: 8px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // ========== FILTERS TOGGLE ==========
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

        // ========== CSRF TOKEN ==========
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // ========== DOM ELEMENTS ==========
        let checkAll = document.getElementById('checkAll');
        let rowChecks = document.querySelectorAll('.rowCheck');
        const selectedBar = document.getElementById('selectedBar');
        const selectedCountSpan = document.getElementById('selectedCount');
        const selectedProductsList = document.getElementById('selectedProductsList');
        const clearSelectionBtn = document.getElementById('clearSelection');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const addToGatePassBtn = document.getElementById('addToGatePassBtn');
        const selectedBarAddToGatePassBtn = document.getElementById('selectedBarAddToGatePassBtn');
        const bulkTitleBtn = document.getElementById('bulkTitleBtn');
        const selectedBarBulkTitleBtn = document.getElementById('selectedBarBulkTitleBtn');
        const bulkSpecificationsBtn = document.getElementById('bulkSpecificationsBtn');
        const selectedBarBulkSpecificationsBtn = document.getElementById('selectedBarBulkSpecificationsBtn');
        const downloadBulkUpdateTemplateBtn = document.getElementById('downloadBulkUpdateTemplateBtn');
        const uploadBulkUpdateBtn = document.getElementById('uploadBulkUpdateBtn');
        const bulkPrintSpecificationsBtn = document.getElementById('bulkPrintSpecificationsBtn');
        const selectedBarPrintSpecificationsBtn = document.getElementById('selectedBarPrintSpecificationsBtn');
        const selectedProducts = new Map();
        const productSelectionStorageKey = 'warehouse_product_selection_{{ auth()->id() }}_{{ auth()->user()->company_id ?? 'none' }}';
        const productBarcodeInput = document.getElementById('productBarcodeInput');
        const selectScannedProductBtn = document.getElementById('selectScannedProductBtn');
        const productBarcodeStatus = document.getElementById('productBarcodeStatus');

        // ========== BULK CHECKBOX FUNCTIONS ==========
        function initializeCheckboxes() {
            checkAll = document.getElementById('checkAll');
            rowChecks = document.querySelectorAll('.rowCheck');

            if (checkAll) {
                checkAll.removeEventListener('change', handleCheckAll);
                checkAll.addEventListener('change', handleCheckAll);
            }

            rowChecks.forEach(checkbox => {
                checkbox.removeEventListener('change', handleRowCheck);
                checkbox.addEventListener('change', handleRowCheck);
            });

            applySelectedStateToVisibleRows();
        }

        function handleCheckAll(e) {
            const isChecked = e.target.checked;
            document.querySelectorAll('.rowCheck').forEach(chk => chk.checked = isChecked);
            syncSelectionFromVisibleRows();
            persistProductSelection();
            updateSelectedCount();
        }

        function handleRowCheck(e) {
            syncSelectionFromCheckbox(e.target);
            persistProductSelection();
            updateSelectAllCheckbox();
            updateSelectedCount();
        }

        function normalizeProductId(id) {
            return id ? String(id) : '';
        }

        function getProductMetaFromCheckbox(checkbox) {
            const id = normalizeProductId(checkbox?.value);
            const row = checkbox?.closest('tr');

            return {
                id,
                status: row?.dataset.status || '',
                assigned: row?.dataset.assigned === '1',
                manual: row?.dataset.manual === '1',
                uniqueId: row?.dataset.uniqueId || '',
                title: row?.dataset.title || '',
            };
        }

        function persistProductSelection() {
            try {
                sessionStorage.setItem(productSelectionStorageKey, JSON.stringify(getSelectedEntries()));
            } catch (error) {
                console.warn('Unable to save the product selection.', error);
            }
        }

        function restoreProductSelection() {
            try {
                const savedSelection = JSON.parse(sessionStorage.getItem(productSelectionStorageKey) || '[]');
                if (!Array.isArray(savedSelection)) return;

                savedSelection.forEach(product => {
                    const id = normalizeProductId(product?.id);
                    if (!id) return;

                    selectedProducts.set(id, {
                        id,
                        status: product.status || '',
                        assigned: Boolean(product.assigned),
                        manual: Boolean(product.manual),
                        uniqueId: product.uniqueId || '',
                        title: product.title || '',
                    });
                });
            } catch (error) {
                sessionStorage.removeItem(productSelectionStorageKey);
            }
        }

        function syncSelectionFromCheckbox(checkbox) {
            const meta = getProductMetaFromCheckbox(checkbox);
            if (!meta.id) return;

            if (checkbox.checked) {
                selectedProducts.set(meta.id, meta);
            } else {
                selectedProducts.delete(meta.id);
            }
        }

        function syncSelectionFromVisibleRows() {
            document.querySelectorAll('.rowCheck').forEach(syncSelectionFromCheckbox);
        }

        function applySelectedStateToVisibleRows() {
            document.querySelectorAll('.rowCheck').forEach(checkbox => {
                const id = normalizeProductId(checkbox.value);
                const isSelected = selectedProducts.has(id);
                checkbox.checked = isSelected;

                if (isSelected) {
                    selectedProducts.set(id, getProductMetaFromCheckbox(checkbox));
                }
            });
            updateSelectAllCheckbox();
            persistProductSelection();
        }

        function getSelectedEntries() {
            return Array.from(selectedProducts.values());
        }

        function clearProductSelection() {
            selectedProducts.clear();
            try {
                sessionStorage.removeItem(productSelectionStorageKey);
            } catch (error) {
                console.warn('Unable to clear the saved product selection.', error);
            }
            document.querySelectorAll('.rowCheck').forEach(chk => chk.checked = false);
            if (checkAll) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }
            updateSelectedCount();
        }

        window.getWarehouseProductSelectedIds = function() {
            return Array.from(selectedProducts.keys());
        };

        function updateSelectAllCheckbox() {
            const checkboxes = document.querySelectorAll('.rowCheck');
            const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(checkbox => checkbox.checked);
            const someChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

            if (checkAll) {
                checkAll.checked = allChecked;
                checkAll.indeterminate = someChecked && !allChecked;
            }
        }

        function isRestrictedStatus(status) {
            return status && ['used', 'sold'].includes(status.toLowerCase());
        }

        function renderSelectedProductsList(selectedEntries) {
            if (!selectedProductsList) return;

            selectedProductsList.replaceChildren();

            selectedEntries.forEach(product => {
                const chip = document.createElement('span');
                chip.className = 'inline-flex max-w-full items-center gap-1.5 rounded-full border border-blue-200 bg-white px-2.5 py-1 text-xs text-blue-800';

                const label = document.createElement('span');
                label.className = 'max-w-xs truncate';
                const identifier = product.uniqueId || `#${product.id}`;
                label.textContent = product.title ? `${identifier} — ${product.title}` : identifier;
                label.title = label.textContent;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.dataset.removeSelectedProduct = product.id;
                removeButton.className = 'flex h-4 w-4 flex-none items-center justify-center rounded-full text-blue-500 hover:bg-blue-100 hover:text-red-600';
                removeButton.setAttribute('aria-label', `Remove ${identifier} from selection`);
                removeButton.textContent = '×';

                chip.append(label, removeButton);
                selectedProductsList.appendChild(chip);
            });
        }

        function updateSelectedCount() {
            const selectedEntries = getSelectedEntries();
            const selectedCount = selectedEntries.length;

            let restrictedCount = 0;
            selectedEntries.forEach(product => {
                if (isRestrictedStatus(product.status)) {
                    restrictedCount++;
                }
            });

            const validCount = selectedCount - restrictedCount;

            renderSelectedProductsList(selectedEntries);

            if (selectedCountSpan) {
                if (restrictedCount > 0) {
                    selectedCountSpan.innerHTML =
                        `${selectedCount} (<span class="text-orange-600">${restrictedCount} used/sold</span>)`;
                } else {
                    selectedCountSpan.textContent = selectedCount;
                }
            }

            if (selectedBar) {
                if (selectedCount > 0) {
                    selectedBar.classList.remove('hidden');
                } else {
                    selectedBar.classList.add('hidden');
                }
            }

            const modalCountElements = [
                'statusModalCount',
                'addPartModalCount',
                'moveModalCount',
                'assignModalCount'
            ];

            modalCountElements.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = validCount;
            });

            const bulkTitleCount = document.getElementById('bulkTitleModalCount');
            if (bulkTitleCount) {
                bulkTitleCount.textContent = selectedCount;
            }

            const bulkSpecificationsCount = document.getElementById('bulkSpecificationsModalCount');
            if (bulkSpecificationsCount) {
                bulkSpecificationsCount.textContent = selectedCount;
            }

            if (bulkDeleteBtn) {
                if (validCount > 0) {
                    bulkDeleteBtn.removeAttribute('disabled');
                } else {
                    bulkDeleteBtn.setAttribute('disabled', 'disabled');
                }
            }

            [addToGatePassBtn, selectedBarAddToGatePassBtn].forEach(button => {
                if (!button) return;

                if (validCount > 0) {
                    button.removeAttribute('disabled');
                } else {
                    button.setAttribute('disabled', 'disabled');
                }
            });

            if (bulkTitleBtn) {
                if (selectedCount > 0) {
                    bulkTitleBtn.removeAttribute('disabled');
                } else {
                    bulkTitleBtn.setAttribute('disabled', 'disabled');
                }
            }

            if (bulkSpecificationsBtn) {
                if (selectedCount > 0) {
                    bulkSpecificationsBtn.removeAttribute('disabled');
                } else {
                    bulkSpecificationsBtn.setAttribute('disabled', 'disabled');
                }
            }

            if (downloadBulkUpdateTemplateBtn) {
                if (selectedCount > 0) {
                    downloadBulkUpdateTemplateBtn.removeAttribute('disabled');
                } else {
                    downloadBulkUpdateTemplateBtn.setAttribute('disabled', 'disabled');
                }
            }

            [bulkPrintSpecificationsBtn, selectedBarPrintSpecificationsBtn].forEach(button => {
                if (!button) return;

                if (selectedCount > 0) {
                    button.removeAttribute('disabled');
                } else {
                    button.setAttribute('disabled', 'disabled');
                }
            });

            // Update assign button text based on whether selected products are already assigned
            const assignBtn = document.getElementById('assignToTechBtn');
            if (assignBtn) {
                const anyAssigned = selectedEntries.some(product => product.assigned);
                assignBtn.textContent = anyAssigned ? 'Change Assignee' : 'Assign To Tech';
            }

            // Show/hide bulk action buttons based on whether all selected are manual products
            const allManual = selectedCount > 0 && selectedEntries.every(product => product.manual);
            const extraBtns = ['statusChangeBtn', 'moveLocationBtn', 'addPartBtn'];
            extraBtns.forEach(id => {
                const btn = document.getElementById(id);
                if (btn) btn.style.display = (selectedCount > 0 && allManual) ? 'none' : '';
            });
        }

        // ========== BARCODE BULK SELECTION ==========
        function normalizeScannedProductId(rawValue) {
            let value = String(rawValue || '').trim();
            if (!value) return '';

            try {
                const scannedUrl = new URL(value);
                const segments = scannedUrl.pathname.split('/').filter(Boolean);
                value = segments.length > 0 ? segments[segments.length - 1] : '';
            } catch (error) {
                value = value.split(/[?#]/, 1)[0];
                const segments = value.split('/').filter(Boolean);
                value = segments.length > 0 ? segments[segments.length - 1] : value;
            }

            try {
                return decodeURIComponent(value).trim();
            } catch (error) {
                return value.trim();
            }
        }

        function showProductBarcodeStatus(type, message) {
            if (!productBarcodeStatus) return;

            const colorClasses = {
                success: ['text-emerald-700'],
                error: ['text-red-600'],
                info: ['text-blue-700'],
            };

            productBarcodeStatus.className = 'text-xs';
            productBarcodeStatus.classList.add(...(colorClasses[type] || colorClasses.info));
            productBarcodeStatus.textContent = message;
        }

        function markVisibleScannedProduct(productId) {
            const checkbox = Array.from(document.querySelectorAll('.rowCheck')).find(
                item => normalizeProductId(item.value) === normalizeProductId(productId)
            );
            if (!checkbox) return;

            checkbox.checked = true;
            const row = checkbox.closest('tr');
            row?.classList.add('bg-blue-50');
            window.setTimeout(() => row?.classList.remove('bg-blue-50'), 1500);
        }

        async function selectScannedProduct() {
            if (selectScannedProductBtn?.disabled) return;

            const uniqueId = normalizeScannedProductId(productBarcodeInput?.value);

            if (!uniqueId) {
                showProductBarcodeStatus('error', 'Scan a barcode or enter a product ID.');
                productBarcodeInput?.focus();
                return;
            }

            const existingProduct = getSelectedEntries().find(
                product => String(product.uniqueId || '').toLowerCase() === uniqueId.toLowerCase()
            );
            if (existingProduct) {
                markVisibleScannedProduct(existingProduct.id);
                showProductBarcodeStatus('info', `${existingProduct.uniqueId} is already selected.`);
                productBarcodeInput?.select();
                return;
            }

            selectScannedProductBtn.disabled = true;
            selectScannedProductBtn.textContent = 'Checking...';
            showProductBarcodeStatus('info', `Checking ${uniqueId}...`);

            try {
                const lookupUrl = new URL(@json(route('warehouse.products.scan-selection')), window.location.origin);
                lookupUrl.searchParams.set('unique_id', uniqueId);

                const response = await fetch(lookupUrl.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (!response.ok || !data.success || !data.product) {
                    throw new Error(data.message || 'Unable to find this product.');
                }

                const product = {
                    id: normalizeProductId(data.product.id),
                    status: data.product.status || '',
                    assigned: Boolean(data.product.assigned),
                    manual: Boolean(data.product.manual),
                    uniqueId: data.product.uniqueId || uniqueId,
                    title: data.product.title || '',
                };

                selectedProducts.set(product.id, product);
                markVisibleScannedProduct(product.id);
                persistProductSelection();
                updateSelectAllCheckbox();
                updateSelectedCount();

                showProductBarcodeStatus('success', `${product.uniqueId} selected successfully.`);
                productBarcodeInput.value = '';
            } catch (error) {
                console.error('Product barcode lookup failed:', error);
                showProductBarcodeStatus('error', error.message || 'Failed to select the scanned product.');
                productBarcodeInput?.select();
            } finally {
                selectScannedProductBtn.disabled = false;
                selectScannedProductBtn.textContent = 'Select';
                productBarcodeInput?.focus();
            }
        }

        selectScannedProductBtn?.addEventListener('click', selectScannedProduct);
        productBarcodeInput?.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                selectScannedProduct();
            }
        });

        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function() {
                clearProductSelection();
            });
        }

        selectedProductsList?.addEventListener('click', function(e) {
            const removeButton = e.target.closest('[data-remove-selected-product]');
            if (!removeButton) return;

            const productId = normalizeProductId(removeButton.dataset.removeSelectedProduct);
            selectedProducts.delete(productId);
            document.querySelectorAll('.rowCheck').forEach(checkbox => {
                if (normalizeProductId(checkbox.value) === productId) {
                    checkbox.checked = false;
                }
            });
            persistProductSelection();
            updateSelectAllCheckbox();
            updateSelectedCount();
        });

        function getSelectedIds(filterUsed = false) {
            return getSelectedEntries()
                .filter(product => {
                    return !filterUsed || !isRestrictedStatus(product.status);
                })
                .map(product => product.id)
                .filter(id => id);
        }

        function printSelectedSpecifications() {
            const selectedIds = getSelectedIds(false);

            if (selectedIds.length === 0) {
                Notification.error('Please select products to print specifications.');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('warehouse.products.bulk-print-specifications') }}';
            form.target = '_blank';
            form.style.display = 'none';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken || '';
            form.appendChild(tokenInput);

            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'product_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            form.remove();
        }

        bulkPrintSpecificationsBtn?.addEventListener('click', printSelectedSpecifications);
        selectedBarPrintSpecificationsBtn?.addEventListener('click', printSelectedSpecifications);

        function downloadProductBulkUpdateTemplate() {
            const selectedIds = getSelectedIds(false);
            if (selectedIds.length === 0) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('warehouse.products.bulk-update.template') }}';
            form.style.display = 'none';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken || '';
            form.appendChild(tokenInput);

            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'product_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            form.remove();
        }

        window.showProductBulkUpdateModal = function() {
            const modal = document.getElementById('productBulkUpdateModal');
            const result = document.getElementById('productBulkUpdateResult');
            result?.classList.add('hidden');
            if (result) result.replaceChildren();
            modal?.classList.remove('hidden');
        };

        window.hideProductBulkUpdateModal = function() {
            document.getElementById('productBulkUpdateModal')?.classList.add('hidden');
        };

        downloadBulkUpdateTemplateBtn?.addEventListener('click', downloadProductBulkUpdateTemplate);
        uploadBulkUpdateBtn?.addEventListener('click', window.showProductBulkUpdateModal);

        document.getElementById('productBulkUpdateForm')?.addEventListener('submit', async function(event) {
            event.preventDefault();

            const submitButton = document.getElementById('submitProductBulkUpdateBtn');
            const result = document.getElementById('productBulkUpdateResult');
            const fileInput = document.getElementById('productBulkUpdateFile');
            const originalText = submitButton.textContent;

            result.className = 'hidden max-h-52 overflow-y-auto rounded-lg border px-4 py-3 text-sm';
            result.replaceChildren();
            submitButton.disabled = true;
            submitButton.textContent = 'Updating...';

            try {
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);

                const response = await fetch('{{ route('warehouse.products.bulk-update.import') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });
                const data = await response.json();

                result.classList.remove('hidden');
                if (!response.ok || !data.success) {
                    result.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
                    const message = document.createElement('p');
                    message.className = 'font-medium';
                    message.textContent = data.message || 'The import could not be completed.';
                    result.appendChild(message);

                    const errors = Array.isArray(data.errors) ? data.errors : Object.values(data.errors || {}).flat();
                    if (errors.length) {
                        const list = document.createElement('ul');
                        list.className = 'mt-2 list-disc space-y-1 pl-5';
                        errors.forEach(error => {
                            const item = document.createElement('li');
                            item.textContent = error;
                            list.appendChild(item);
                        });
                        result.appendChild(list);
                    }
                    return;
                }

                result.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
                result.textContent = data.message;
                fileInput.value = '';
                clearProductSelection();

                setTimeout(() => window.location.reload(), 1200);
            } catch (error) {
                result.classList.remove('hidden');
                result.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
                result.textContent = 'The upload failed. Please check your connection and try again.';
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });

        function removeSelectedIds(ids) {
            ids.forEach(id => selectedProducts.delete(normalizeProductId(id)));
            persistProductSelection();
        }

        function addSelectedIdsToExportForm(filterForm) {
            if (!filterForm) return;

            filterForm.querySelectorAll('[data-selected-export-ids="1"]').forEach(input => input.remove());

            const selectedIds = getSelectedIds(false);
            if (selectedIds.length === 0) return;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'ids';
            hidden.value = selectedIds.join(',');
            hidden.dataset.selectedExportIds = '1';
            filterForm.appendChild(hidden);
        }

        function cleanupExportFormSelection(filterForm) {
            if (!filterForm) return;
            filterForm.querySelectorAll('[data-selected-export-ids="1"]').forEach(input => input.remove());
        }

        function getSelectedProductsByRestriction() {
            const restrictedProductIds = [];
            const validProductIds = [];

            getSelectedEntries().forEach(product => {
                if (isRestrictedStatus(product.status)) {
                    restrictedProductIds.push(product.id);
                } else {
                    validProductIds.push(product.id);
                }
            });

            return { restrictedProductIds, validProductIds };
        }

        function openGatePassWithSelectedProducts() {
            const selectedIds = getSelectedIds(true);

            if (selectedIds.length === 0) {
                alert('Please select at least one available product.');
                return;
            }

            const gatePassCreateUrl = new URL(@json(route('warehouse.gate-pass.create')));
            gatePassCreateUrl.searchParams.set('product_ids', selectedIds.join(','));
            window.location.href = gatePassCreateUrl.toString();
        }

        addToGatePassBtn?.addEventListener('click', openGatePassWithSelectedProducts);
        selectedBarAddToGatePassBtn?.addEventListener('click', openGatePassWithSelectedProducts);

        let pendingWarehouseProductsExportFormat = null;

        function startWarehouseProductsExport(format) {
            pendingWarehouseProductsExportFormat = format;
            document.getElementById('productExportFormatBadge').textContent = format === 'xlsx' ? 'EXCEL' : 'PDF';
            document.getElementById('productExportColumnsError').classList.add('hidden');
            document.getElementById('productExportColumnsModal').classList.remove('hidden');
            document.getElementById('exportDropdown')?.classList.add('hidden');
        }

        function hideProductExportColumnsModal() {
            document.getElementById('productExportColumnsModal').classList.add('hidden');
            pendingWarehouseProductsExportFormat = null;
        }
        window.hideProductExportColumnsModal = hideProductExportColumnsModal;

        function setAllProductExportColumns(checked) {
            document.querySelectorAll('[data-product-export-column]').forEach(column => {
                column.checked = checked;
            });
            if (checked) document.getElementById('productExportColumnsError').classList.add('hidden');
        }
        window.setAllProductExportColumns = setAllProductExportColumns;

        function confirmWarehouseProductsExport() {
            const columns = Array.from(document.querySelectorAll('[data-product-export-column]:checked'))
                .map(column => column.value);

            if (columns.length === 0) {
                document.getElementById('productExportColumnsError').classList.remove('hidden');
                return;
            }

            const format = pendingWarehouseProductsExportFormat;
            hideProductExportColumnsModal();
            runWarehouseProductsExport(format, columns);
        }
        window.confirmWarehouseProductsExport = confirmWarehouseProductsExport;

        function runWarehouseProductsExport(format, columns) {
            const filterForm = document.getElementById('filter-form');

            if (filterForm) {
                filterForm.querySelectorAll('[data-export-url-filter="1"]').forEach(input => input.remove());
                filterForm.querySelectorAll('[data-export-columns="1"]').forEach(input => input.remove());
                cleanupExportFormSelection(filterForm);

                const ignoredParams = new Set(['page', 'per_page']);
                const urlParams = new URLSearchParams(window.location.search);

                urlParams.forEach((value, key) => {
                    if (!value || ignoredParams.has(key)) return;

                    const hasFormField = Array.from(filterForm.elements).some(field => field.name === key);
                    if (hasFormField) return;

                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = key;
                    hidden.value = value;
                    hidden.dataset.exportUrlFilter = '1';
                    filterForm.appendChild(hidden);
                });

                addSelectedIdsToExportForm(filterForm);

                const exportColumns = document.createElement('input');
                exportColumns.type = 'hidden';
                exportColumns.name = 'export_columns';
                exportColumns.value = columns.join(',');
                exportColumns.dataset.exportColumns = '1';
                filterForm.appendChild(exportColumns);
            }

            startExport(format, 'warehouse-products');
            cleanupExportFormSelection(filterForm);
            filterForm?.querySelectorAll('[data-export-columns="1"]').forEach(input => input.remove());
        }

        // ========== DROPDOWN FUNCTIONS ==========
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative.w-full')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;

                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });

                menu.classList.toggle('hidden');
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });


        // ========== DELETE FUNCTIONS ==========
        let deleteTarget = {
            url: null,
            rowSel: null,
            productStatus: null
        };
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');

        window.confirmDelete = function(btnEl) {
            const productStatus = btnEl.dataset.status;

            if (isRestrictedStatus(productStatus)) {
                Notification.error(`Cannot delete products with status "${productStatus}"`);
                return;
            }

            deleteTarget = {
                url: btnEl.dataset.url,
                rowSel: btnEl.dataset.row,
                productStatus: productStatus
            };
            deleteModal?.classList.remove('hidden');
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        };

        if (cancelDelete) {
            cancelDelete.addEventListener('click', () => {
                deleteModal?.classList.add('hidden');
                deleteTarget = {
                    url: null,
                    rowSel: null,
                    productStatus: null
                };
            });
        }

        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', async () => {
                if (!deleteTarget.url) return;

                if (isRestrictedStatus(deleteTarget.productStatus)) {
                    Notification.error(`Cannot delete products with status "${deleteTarget.productStatus}"`);
                    deleteModal?.classList.add('hidden');
                    return;
                }

                try {
                    const res = await fetch(deleteTarget.url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await res.json();

                    if (res.ok && data?.success) {
                        if (deleteTarget.rowSel) {
                            const row = document.querySelector(deleteTarget.rowSel);
                            if (row?.dataset.id) {
                                selectedProducts.delete(normalizeProductId(row.dataset.id));
                                persistProductSelection();
                            }
                            row?.remove();
                        }
                        Notification.success(data.message || 'Product deleted successfully!');
                        updateSelectAllCheckbox();
                        updateSelectedCount();
                    } else {
                        Notification.error(data?.message || 'Failed to delete product.');
                    }
                } catch (e) {
                    Notification.error('Network error. Please try again.');
                } finally {
                    deleteModal?.classList.add('hidden');
                    deleteTarget = {
                        url: null,
                        rowSel: null,
                        productStatus: null
                    };
                }
            });
        }

        // ========== BULK DELETE ==========
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                const selectedIds = getSelectedIds();

                if (selectedIds.length === 0) {
                    Notification.error('Please select products to delete');
                    return;
                }

                const { restrictedProductIds, validProductIds } = getSelectedProductsByRestriction();

                if (validProductIds.length === 0) {
                    Notification.error('Cannot delete products with status "Used" or "Sold"');
                    return;
                }

                let confirmMessage = `Are you sure you want to delete ${validProductIds.length} product(s)?`;
                if (restrictedProductIds.length > 0) {
                    confirmMessage = `${restrictedProductIds.length} product(s) with status "Used"/"Sold" will be skipped. ` +
                        confirmMessage;
                }

                if (!confirm(confirmMessage)) {
                    return;
                }

                fetch('{{ route('warehouse.products.bulk-delete') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            ids: validProductIds
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let message = data.message ||
                                `Successfully deleted ${validProductIds.length} product(s)`;
                            if (restrictedProductIds.length > 0) {
                                message +=
                                    `. ${restrictedProductIds.length} product(s) with status "Used"/"Sold" were skipped.`;
                            }
                            Notification.success(message);

                            validProductIds.forEach(id => {
                                const row = document.querySelector(`tr[data-id="${id}"]`);
                                if (row) row.remove();
                            });
                            removeSelectedIds(validProductIds);

                            updateSelectAllCheckbox();
                            updateSelectedCount();

                            if (document.querySelectorAll('.rowCheck').length === 0) {
                                location.reload();
                            }
                        } else {
                            Notification.error(data.message || 'Failed to delete products');
                        }
                    })
                    .catch(error => {
                        Notification.error('Delete failed: ' + error.message);
                    });
            });
        }

        // ========== BULK PRODUCT EDIT ==========
        function openBulkTitleModal() {
            const selectedIds = getSelectedIds(false);

            if (selectedIds.length === 0) {
                Notification.error('Please select products to update');
                return;
            }

            const input = document.getElementById('bulkTitleInput');
            if (input) input.value = '';
            $('#bulkEditWorkflow, #bulkEditDisposition, #bulkEditLocation, #bulkEditStatus').val('').trigger('change');

            document.getElementById('bulkTitleModal')?.classList.remove('hidden');
            setTimeout(() => input?.focus(), 50);
        }

        window.openBulkTitleModal = openBulkTitleModal;

        window.hideBulkTitleModal = function() {
            document.getElementById('bulkTitleModal')?.classList.add('hidden');
            const input = document.getElementById('bulkTitleInput');
            if (input) input.value = '';
            $('#bulkEditWorkflow, #bulkEditDisposition, #bulkEditLocation, #bulkEditStatus').val('').trigger('change');
        };

        bulkTitleBtn?.addEventListener('click', openBulkTitleModal);
        selectedBarBulkTitleBtn?.addEventListener('click', openBulkTitleModal);

        document.getElementById('bulkTitleInput')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('confirmBulkTitle')?.click();
            }
        });

        document.getElementById('confirmBulkTitle')?.addEventListener('click', function() {
            const input = document.getElementById('bulkTitleInput');
            const title = (input?.value || '').trim();
            const workflowStageId = document.getElementById('bulkEditWorkflow')?.value || '';
            const dispositionId = document.getElementById('bulkEditDisposition')?.value || '';
            const locationId = document.getElementById('bulkEditLocation')?.value || '';
            const status = document.getElementById('bulkEditStatus')?.value || '';
            const selectedIds = getSelectedIds(false);

            if (selectedIds.length === 0) {
                Notification.error('Please select products to update');
                return;
            }

            if (!title && !workflowStageId && !dispositionId && !locationId && !status) {
                Notification.error('Please enter or select at least one field to update');
                return;
            }

            const btn = document.getElementById('confirmBulkTitle');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'UPDATING...';

            fetch('{{ route('warehouse.products.bulk-update-title') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        ids: selectedIds,
                        title: title,
                        work_flow_stage_id: workflowStageId,
                        disposition_id: dispositionId,
                        warehouse_location_id: locationId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message ||
                            `Updated ${selectedIds.length} product(s)`);
                        hideBulkTitleModal();
                        clearProductSelection();

                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notification.error(data.message || 'Failed to update products');
                    }
                })
                .catch(error => {
                    Notification.error('Error updating products: ' + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });

        // ========== BULK SPECIFICATIONS UPDATE ==========
        function addBulkSpecificationRow(key = '', value = '') {
            const list = document.getElementById('bulkSpecificationsList');
            if (!list) return;

            const row = document.createElement('div');
            row.className = 'bulk-specification-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 items-center';

            const keyInput = document.createElement('input');
            keyInput.type = 'text';
            keyInput.maxLength = 255;
            keyInput.placeholder = 'Specification key (e.g., Color)';
            keyInput.value = key;
            keyInput.className = 'bulk-specification-key w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none';

            const valueInput = document.createElement('input');
            valueInput.type = 'text';
            valueInput.maxLength = 1000;
            valueInput.placeholder = 'Specification value (e.g., Black)';
            valueInput.value = value;
            valueInput.className = 'bulk-specification-value w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none';

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.textContent = 'REMOVE';
            removeButton.className = 'px-3 py-2 text-xs font-medium text-red-600 border border-red-200 rounded-md hover:bg-red-50 transition-colors';
            removeButton.addEventListener('click', function() {
                if (list.querySelectorAll('.bulk-specification-row').length === 1) {
                    keyInput.value = '';
                    valueInput.value = '';
                    keyInput.focus();
                    return;
                }
                row.remove();
            });

            valueInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addBulkSpecificationRow();
                    list.querySelector('.bulk-specification-row:last-child .bulk-specification-key')?.focus();
                }
            });

            row.append(keyInput, valueInput, removeButton);
            list.appendChild(row);
        }

        function openBulkSpecificationsModal() {
            const selectedIds = getSelectedIds(false);
            if (selectedIds.length === 0) {
                Notification.error('Please select products to update');
                return;
            }

            const list = document.getElementById('bulkSpecificationsList');
            if (list) list.innerHTML = '';
            addBulkSpecificationRow();
            document.getElementById('bulkSpecificationsModal')?.classList.remove('hidden');
            setTimeout(() => list?.querySelector('.bulk-specification-key')?.focus(), 50);
        }

        window.hideBulkSpecificationsModal = function() {
            document.getElementById('bulkSpecificationsModal')?.classList.add('hidden');
            const list = document.getElementById('bulkSpecificationsList');
            if (list) list.innerHTML = '';
        };

        bulkSpecificationsBtn?.addEventListener('click', openBulkSpecificationsModal);
        selectedBarBulkSpecificationsBtn?.addEventListener('click', openBulkSpecificationsModal);
        document.getElementById('addBulkSpecificationRow')?.addEventListener('click', function() {
            addBulkSpecificationRow();
            document.querySelector('#bulkSpecificationsList .bulk-specification-row:last-child .bulk-specification-key')?.focus();
        });

        document.getElementById('confirmBulkSpecifications')?.addEventListener('click', function() {
            const selectedIds = getSelectedIds(false);
            const specifications = [];
            const keys = new Set();
            let hasIncompleteRow = false;
            let hasDuplicateKey = false;

            document.querySelectorAll('#bulkSpecificationsList .bulk-specification-row').forEach(row => {
                const key = (row.querySelector('.bulk-specification-key')?.value || '').trim();
                const value = (row.querySelector('.bulk-specification-value')?.value || '').trim();

                if (!key && !value) return;
                if (!key || !value) {
                    hasIncompleteRow = true;
                    return;
                }

                const normalizedKey = key.toLowerCase();
                if (keys.has(normalizedKey)) {
                    hasDuplicateKey = true;
                    return;
                }

                keys.add(normalizedKey);
                specifications.push({ key, value });
            });

            if (selectedIds.length === 0) {
                Notification.error('Please select products to update');
                return;
            }
            if (hasIncompleteRow) {
                Notification.error('Please enter both a key and value for every specification');
                return;
            }
            if (hasDuplicateKey) {
                Notification.error('Each specification key can only be added once');
                return;
            }
            if (specifications.length === 0) {
                Notification.error('Please add at least one specification');
                return;
            }

            const btn = document.getElementById('confirmBulkSpecifications');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'UPDATING...';

            fetch('{{ route('warehouse.products.bulk-update-specifications') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        ids: selectedIds,
                        specifications: specifications
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message ||
                            `Updated specifications for ${selectedIds.length} product(s)`);
                        hideBulkSpecificationsModal();
                        clearProductSelection();

                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notification.error(data.message || 'Failed to update specifications');
                    }
                })
                .catch(error => {
                    Notification.error('Error updating specifications: ' + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });

        // ========== MODAL FUNCTIONS ==========

        // Status Change Modal
        document.getElementById('statusChangeBtn')?.addEventListener('click', function() {
            const validIds = getSelectedIds(true);
            if (validIds.length === 0) {
                Notification.error('No eligible products selected (used products cannot be modified)');
                return;
            }
            document.getElementById('statusChangeModal').classList.remove('hidden');
        });

        window.hideStatusChangeModal = function() {
            document.getElementById('statusChangeModal').classList.add('hidden');
            $('#bulkProductStatus').val('').trigger('change');
        };

        document.getElementById('confirmStatusChange')?.addEventListener('click', function() {
            const productStatus = document.getElementById('bulkProductStatus').value;
            const disposition = document.getElementById('statusDisposition').value;
            const workflow = document.getElementById('statusWorkflow').value;
            const location = document.getElementById('statusLocation').value;
            const selectedIds = getSelectedIds(true);

            if (!productStatus && !disposition && !workflow && !location) {
                Notification.error('Please select at least one field to update');
                return;
            }

            if (selectedIds.length === 0) {
                Notification.error('No eligible products selected');
                return;
            }

            const btn = document.getElementById('confirmStatusChange');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'UPDATING...';

            fetch('{{ route('warehouse.products.bulk-status-change') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        ids: selectedIds,
                        status: productStatus,
                        disposition_id: disposition,
                        work_flow_stage_id: workflow,
                        location: location
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message || 'Status updated successfully');
                        hideStatusChangeModal();

                        $('#bulkProductStatus, #statusDisposition, #statusWorkflow, #statusLocation').val('').trigger('change');

                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notification.error(data.message || 'Failed to update status');
                    }
                })
                .catch(error => {
                    Notification.error('Error updating status: ' + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });

        // Add Part Modal
        // Add Part Modal
        document.getElementById('addPartBtn')?.addEventListener('click', function() {
            const validIds = getSelectedIds(true);
            if (validIds.length === 0) {
                Notification.error('No eligible products selected (used products cannot be modified)');
                return;
            }

            // Reset the form
            $('#part_id').val('').trigger('change');
            document.getElementById('partNotes').value = '';

            document.getElementById('addPartModal').classList.remove('hidden');
        });

        window.hideAddPartModal = function() {
            document.getElementById('addPartModal').classList.add('hidden');
            // Reset form when closing
            $('#part_id').val('').trigger('change');
            document.getElementById('partNotes').value = '';
        };

        document.getElementById('confirmAddPart')?.addEventListener('click', function() {
            const partId = document.getElementById('part_id').value;
            const notes = document.getElementById('partNotes').value; // Changed from partDescription to partNotes
            const selectedIds = getSelectedIds(true);

            if (!partId) {
                Notification.error('Please select a part');
                return;
            }

            if (selectedIds.length === 0) {
                Notification.error('No eligible products selected');
                return;
            }

            const btn = document.getElementById('confirmAddPart');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'ADDING...';

            fetch('{{ route('warehouse.products.bulk-add-part') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        ids: selectedIds,
                        part_id: partId,
                        notes: notes // Changed from description to notes
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message || `Added part to ${selectedIds.length} products`);
                        hideAddPartModal();

                        // Clear selection
                        clearProductSelection();

                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Notification.error(data.message || 'Failed to add part');
                    }
                })
                .catch(error => {
                    Notification.error('Error adding part: ' + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });

        // Move Location Modal
        // Move Location Modal
        document.getElementById('moveLocationBtn')?.addEventListener('click', function() {
            const validIds = getSelectedIds(true);
            if (validIds.length === 0) {
                Notification.error('No eligible products selected (used products cannot be modified)');
                return;
            }

            // Reset the select
            $('#newLocation').val('').trigger('change');

            document.getElementById('moveLocationModal').classList.remove('hidden');
        });

        window.hideMoveLocationModal = function() {
            document.getElementById('moveLocationModal').classList.add('hidden');
            // Reset select when closing
            $('#newLocation').val('').trigger('change');
        };

        document.getElementById('confirmMoveLocation')?.addEventListener('click', function() {
            const newLocation = document.getElementById('newLocation').value;
            const selectedIds = getSelectedIds(true);

            if (!newLocation) {
                Notification.error('Please select a location');
                return;
            }

            if (selectedIds.length === 0) {
                Notification.error('No eligible products selected');
                return;
            }

            const btn = document.getElementById('confirmMoveLocation');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'MOVING...';

            fetch('{{ route('warehouse.products.bulk-move-location') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        ids: selectedIds,
                        location: newLocation
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message ||
                            `Successfully moved ${selectedIds.length} products`);
                        hideMoveLocationModal();

                        // Clear selection
                        clearProductSelection();

                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Notification.error(data.message || 'Failed to move products');
                    }
                })
                .catch(error => {
                    Notification.error('Error moving products: ' + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });

        // Assign to Tech Modal
        document.getElementById('assignToTechBtn')?.addEventListener('click', function() {
            const validIds = getSelectedIds(true);
            if (validIds.length === 0) {
                Notification.error('No eligible products selected (used products cannot be modified)');
                return;
            }
            // Check if all selected products are manual
            const allManual = getSelectedEntries().every(product => product.manual);
            const extraFields = document.getElementById('assignExtraFields');
            const taskTypeInput = document.getElementById('taskType');
            if (allManual) {
                if (extraFields) extraFields.style.display = 'none';
                if (taskTypeInput) taskTypeInput.value = 'general';
            } else {
                if (extraFields) extraFields.style.display = '';
            }
            document.getElementById('assignTechModal').classList.remove('hidden');
        });


        document.getElementById('confirmAssignTech')?.addEventListener('click', function() {
            const technician = document.getElementById('technician').value;
            const disposition = document.getElementById('assignDisposition').value;
            const workflow = document.getElementById('techWorkflow').value;
            const taskType = document.getElementById('taskType').value;
            const remarks = document.getElementById('techRemarks').value;
            const selectedIds = getSelectedIds(true);

            if (!technician) {
                Notification.error('Please select a technician');
                return;
            }

            if (selectedIds.length === 0) {
                Notification.error('No eligible products selected');
                return;
            }

            const btn = document.getElementById('confirmAssignTech');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'ASSIGNING...';

            fetch('{{ route('warehouse.products.bulk-assign-tech') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        ids: selectedIds,
                        technician_id: technician,
                        disposition_id: disposition,
                        work_flow_stage_id: workflow,
                        task_type: taskType,
                        remarks: remarks
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message ||
                            `Assigned ${selectedIds.length} products to technician`);
                        hideAssignTechModal();

                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notification.error(data.message || 'Failed to assign to technician');
                    }
                })
                .catch(error => {
                    Notification.error('Error assigning to technician: ' + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });


        // ========== CLOSE MODALS ON OUTSIDE CLICK ==========
        [deleteModal,
            document.getElementById('statusChangeModal'),
            document.getElementById('bulkTitleModal'),
            document.getElementById('bulkSpecificationsModal'),
            document.getElementById('addPartModal'),
            document.getElementById('moveLocationModal'),
            document.getElementById('assignTechModal')
        ].forEach(modal => {
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    if (modal.id === 'statusChangeModal') {
                        $('#bulkProductStatus, #statusDisposition, #statusWorkflow, #statusLocation').val('').trigger('change');
                    }
                    if (modal.id === 'bulkTitleModal') {
                        const bulkTitleInput = document.getElementById('bulkTitleInput');
                        if (bulkTitleInput) bulkTitleInput.value = '';
                        $('#bulkEditWorkflow, #bulkEditDisposition, #bulkEditLocation, #bulkEditStatus').val('').trigger('change');
                    }
                    if (modal.id === 'bulkSpecificationsModal') {
                        const bulkSpecificationsList = document.getElementById('bulkSpecificationsList');
                        if (bulkSpecificationsList) bulkSpecificationsList.innerHTML = '';
                    }
                    if (modal === deleteModal) {
                        deleteTarget = {
                            url: null,
                            rowSel: null,
                            productStatus: null
                        };
                    }
                }
            });
        });



        // ========== INITIALIZE SELECT2 ==========
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select option';
                }
            });

            // ── Auto-derive task_type from workflow stage name ──
            // Uses same keyword mapping as TechnicianController $viewMap:
            //   visual → visual-inspection view
            //   functional → functional-testing view
            //   anything else → general-task view
            const taskTypeLabels = {
                visual:      'Visual Inspection',
                functional:  'Functional Testing',
                calibration: 'Calibration',
                repair:      'Repair',
                quality:     'Quality Check',
                general:     'General',
            };

            function detectTaskType(stageName) {
                const n = (stageName || '').toLowerCase();
                if (n.includes('visual'))       return 'visual';
                if (n.includes('functional'))   return 'functional';
                if (n.includes('calibration'))  return 'calibration';
                if (n.includes('repair'))       return 'repair';
                if (n.includes('quality'))      return 'quality';
                if (n)                          return 'general';
                return '';
            }

            $('#techWorkflow').on('change', function() {
                const selected = $(this).find('option:selected');
                const stageName = selected.data('name') || '';
                const type = detectTaskType(stageName);
                const taskTypeInput = document.getElementById('taskType');

                if (type) {
                    taskTypeInput.value = taskTypeLabels[type] || type;
                } else {
                    taskTypeInput.value = '';
                }
            });
        });

        window.hideAssignTechModal = function() {
            document.getElementById('assignTechModal').classList.add('hidden');
            $('#technician, #assignDisposition, #techWorkflow').val('').trigger('change');
            document.getElementById('taskType').value = '';
            document.getElementById('techRemarks').value = '';
            const extraFields = document.getElementById('assignExtraFields');
            if (extraFields) extraFields.style.display = '';
        };

        // ========== INITIALIZE REPORT FILTER ==========
        document.addEventListener('DOMContentLoaded', function() {
            restoreProductSelection();
            initializeCheckboxes();
            updateSelectedCount();

            ReportFilter.init({
                url: '{{ route('warehouse.products.index') }}',
                tableSelector: '#product-table',
                paginationSelector: '#pagination-wrapper',
                filterContainer: document.getElementById('filter-form'),
                debounceDelay: 250
            });

            document.getElementById('filter-form')?.addEventListener('submit', function(e) {
                e.preventDefault();
                ReportFilter.filter();
            });
        });

        // Re-initialize checkboxes after every AJAX table update (filter / pagination)
        document.addEventListener('tableUpdated', function() {
            initializeCheckboxes();
            updateSelectedCount();
        });

        // Dropdown Logic
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const isHidden = dropdown.classList.contains('hidden');

            // Hide all other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(el => {
                el.classList.add('hidden');
            });

            if (isHidden) {
                dropdown.classList.remove('hidden');
            }
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });
    </script>

    <script>
        // ========== PRODUCT QR CODE MODAL ==========
        let _qrImageDataUrl = '';
        let _qrUniqueId    = '';
        let _qrProductId   = '';

        function printProductQrCode(productId, uniqueId) {
            // Close any open row dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));

            // Reset modal state
            _qrImageDataUrl = '';
            _qrUniqueId    = uniqueId;
            _qrProductId   = productId;
            document.getElementById('productQrImageWrap').innerHTML = '';
            document.getElementById('productQrUniqueId').textContent = '';
            document.getElementById('productQrContent').classList.add('hidden');
            document.getElementById('productQrError').classList.add('hidden');
            document.getElementById('productQrError').textContent = '';
            document.getElementById('productQrSpinner').classList.remove('hidden');
            document.getElementById('productQrDownloadBtn').disabled = true;
            document.getElementById('productQrPrintBtn').disabled    = true;
            document.getElementById('productQrModal').classList.remove('hidden');

            fetch(`/warehouse/products/${productId}/qrcode`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('productQrSpinner').classList.add('hidden');
                if (data.success) {
                    _qrImageDataUrl = data.image;
                    _qrUniqueId    = data.unique_id;
                    document.getElementById('productQrImageWrap').innerHTML =
                        `<img src="${data.image}" alt="${data.unique_id}" style="max-width:220px;max-height:220px;display:block;margin:auto;">`;
                    document.getElementById('productQrUniqueId').textContent = data.unique_id;
                    document.getElementById('productQrContent').classList.remove('hidden');
                    document.getElementById('productQrDownloadBtn').disabled = false;
                    document.getElementById('productQrPrintBtn').disabled    = false;
                } else {
                    document.getElementById('productQrError').textContent = data.message || 'Failed to generate QR code.';
                    document.getElementById('productQrError').classList.remove('hidden');
                }
            })
            .catch(err => {
                document.getElementById('productQrSpinner').classList.add('hidden');
                document.getElementById('productQrError').textContent = 'Error: ' + err.message;
                document.getElementById('productQrError').classList.remove('hidden');
            });
        }
        window.printProductQrCode = printProductQrCode;

        function hideProductQrModal() {
            document.getElementById('productQrModal').classList.add('hidden');
        }
        window.hideProductQrModal = hideProductQrModal;

        function downloadProductQr() {
            if (!_qrImageDataUrl) return;
            const link = document.createElement('a');
            link.href     = _qrImageDataUrl;
            link.download = `qrcode-${_qrUniqueId}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        window.downloadProductQr = downloadProductQr;

        function printProductQrFromModal() {
            if (!_qrProductId) return;
            // Open the dedicated print page — same settings panel as the barcode print page
            window.open(`/warehouse/products/${_qrProductId}/print-qrcode`, '_blank');
            hideProductQrModal();
        }
        window.printProductQrFromModal = printProductQrFromModal;
    </script>
@endpush
