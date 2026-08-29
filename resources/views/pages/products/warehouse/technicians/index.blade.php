{{-- resources/views/pages/products/warehouse/technicians/index.blade.php --}}
@extends('layouts.auth')

@section('title', 'Technicians Dashboard')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">
                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <h2 class="text-stone-950 text-xl font-normal">Technicians Dashboard</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3


                    gap-2 relative">
                            <!-- Stats Summary -->
                            <div class="flex items-center gap-2 text-sm">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                    <i class="fas fa-tasks mr-1"></i>{{ $stats['total_tasks'] }} Total
                                </span>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                    <i class="fas fa-clock mr-1"></i>{{ $stats['pending_tasks'] }} Pending
                                </span>
                            </div>

                            <!-- Column Chooser -->
                            <x-columns :columns="[
                                'unique_id' => 'Unique ID',
                                'category' => 'Category',
                                'location' => 'Location',
                                'task_type' => 'Task Type',
                                'status' => 'Status',
                                'technician' => 'Technician',
                                'assigned_date' => 'Assigned Date',
                            ]" name="technician_columns" />

                            @if(auth()->user()->hasPermission('warehouse.technicians.export-pdf') || auth()->user()->hasPermission('warehouse.technicians.export-excel'))
                            <!-- Export Dropdown -->
                            <div class="relative w-full">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                    EXPORT ▼
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li>
                                            <a href="#" onclick="exportData('pdf')"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">PDF</a>
                                        </li>
                                        <li>
                                            <a href="#" onclick="exportData('excel')"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Excel</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            @endif

                            <!-- Bulk Assign Button -->
                            {{-- <button type="button" id="bulkAssignBtn"
                            class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            BULK ASSIGN
                            @if ($stats['pending_tasks'] > 0)
                                <span class="bg-white text-[#0078C8] px-2 py-0.5 rounded-full text-xs">{{ $stats['pending_tasks'] }}</span>
                            @endif
                        </button> --}}
                        </div>
                    </div>

                    <!-- Per-page selector and Bulk Actions -->
                    <div class="flex justify-between items-center my-4">
                        <div></div>
                        <div class="flex items-center gap-4">
                            <!-- Bulk Actions -->
                            <div class="flex items-center gap-2">
                                @if(auth()->user()->hasPermission('warehouse.technicians.bulk-delete'))
                                <button type="button" id="bulkDeleteBtn"
                                    class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                    DELETE SELECTED
                                </button>
                                @endif
                            </div>

                            <!-- Per page selector -->
                            <form method="GET" id="perPageForm" class="flex items-center gap-2">
                                <input type="hidden" name="status" value="{{ request('status') }}">
                                <input type="hidden" name="category" value="{{ request('category') }}">
                                <input type="hidden" name="task_type" value="{{ request('task_type') }}">
                                <input type="hidden" name="search" value="{{ request('search') }}">

                                <select name="per_page" id="perPageSelect"
                                    class="select2 w-28 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    @foreach ([10, 20, 30, 50, 100] as $size)
                                        <option value="{{ $size }}"
                                            {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                                            {{ $size }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>

                    @php $hasActiveFilters = request()->hasAny(['status', 'category', 'task_type', 'search']); @endphp

                    <!-- Workload Overview Panel -->
                    @if($technicians->isNotEmpty())
                    <div class="bg-white rounded-xl border border-gray-200 mb-4 overflow-hidden">
                        <button type="button" id="workloadToggleBtn"
                            onclick="toggleWorkload()"
                            class="w-full flex items-center justify-between px-5 py-3 text-left hover:bg-gray-50 transition-colors">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0078C8]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-800">Workload Overview</span>
                                <span class="text-xs text-gray-400">({{ $technicians->count() }} technicians)</span>
                            </div>
                            <svg id="workloadChevron" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="workloadPanel" class="hidden border-t border-gray-100">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                                        <tr>
                                            <th class="px-5 py-2.5 text-left font-medium">Technician</th>
                                            <th class="px-5 py-2.5 text-center font-medium">Open Tasks</th>
                                            <th class="px-5 py-2.5 text-center font-medium">Overdue</th>
                                            <th class="px-5 py-2.5 text-center font-medium">Avg Completion</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Load</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($technicians as $tech)
                                        @php
                                            $open    = $tech->current_tasks ?? 0;
                                            $overdue = $tech->overdue_tasks ?? 0;
                                            $avgH    = $tech->avg_completion_hours ?? 0;
                                            $loadPct = min(100, $open * 10);
                                            $loadColor = $loadPct >= 70 ? 'bg-red-500' : ($loadPct >= 40 ? 'bg-yellow-400' : 'bg-green-500');
                                        @endphp
                                        <tr class="hover:bg-gray-50/60 transition-colors">
                                            <td class="px-5 py-2.5">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-7 h-7 rounded-full bg-[#0078C8]/10 text-[#0078C8] flex items-center justify-center text-xs font-semibold flex-shrink-0">
                                                        {{ strtoupper(substr($tech->full_name ?? $tech->username ?? '?', 0, 1)) }}
                                                    </div>
                                                    <span class="font-medium text-gray-800 truncate max-w-[160px]">{{ $tech->full_name ?? $tech->username ?? 'Unknown' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-2.5 text-center">
                                                <span class="inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 rounded-full text-xs font-semibold {{ $open > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500' }}">
                                                    {{ $open }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-2.5 text-center">
                                                <span class="inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 rounded-full text-xs font-semibold {{ $overdue > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-400' }}">
                                                    {{ $overdue }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-2.5 text-center text-gray-600 text-xs">
                                                {{ $avgH > 0 ? $avgH . 'h' : '—' }}
                                            </td>
                                            <td class="px-5 py-2.5 w-40">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1 bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                                        <div class="h-1.5 rounded-full {{ $loadColor }} transition-all"
                                                            style="width: {{ $loadPct }}%"></div>
                                                    </div>
                                                    <span class="text-xs text-gray-400 w-8 text-right">{{ $loadPct }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="px-5 py-2 text-xs text-gray-400 border-t border-gray-100">
                                Load bar = open tasks × 10 (capped at 100%). Avg completion time is for tasks with a recorded start and end time.
                            </p>
                        </div>
                    </div>
                    @endif

                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
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
                                <input type="hidden" name="per_page" id="perPageHiddenInput" value="{{ request('per_page', 20) }}">
                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                                    <select name="status" id="statusFilter"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="All" {{ request('status') == 'All' ? 'selected' : '' }}>All Status
                                        </option>
                                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>
                                            Pending</option>
                                        <option value="In Progress"
                                            {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="Complete" {{ request('status') == 'Complete' ? 'selected' : '' }}>
                                            Complete</option>
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Category</label>
                                    <select name="category" id="categoryFilter"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="All" {{ request('category') == 'All' ? 'selected' : '' }}>All
                                            Categories</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat }}"
                                                {{ request('category') == $cat ? 'selected' : '' }}>
                                                {{ $cat }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Task Type</label>
                                    <select name="task_type" id="taskTypeFilter"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="All" {{ request('task_type') == 'All' ? 'selected' : '' }}>All
                                            Task Types</option>
                                        @foreach ($taskTypes as $type)
                                            <option value="{{ $type }}"
                                                {{ request('task_type') == $type ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Technician</label>
                                    <select name="technician_id" id="technicianFilter"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="All" {{ request('technician_id') == 'All' ? 'selected' : '' }}>All Technicians</option>
                                        @foreach ($technicians as $tech)
                                            <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                                                {{ $tech->full_name ?? $tech->username }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Done From</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}" id="dateFromFilter"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>

                                <div class="flex-1 min-w-[130px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Done To</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}" id="dateToFilter"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>

                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <div class="flex gap-2">
                                        <input type="text" name="search" id="searchInput"
                                            placeholder="Search by ID, technician, task..." value="{{ request('search') }}"
                                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <button type="button" id="search-btn"
                                            class="px-4 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors inline-flex items-center gap-1">
                                            <span id="search-text"><i class="fas fa-search"></i></span>
                                            <span id="search-loader" class="hidden">
                                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </span>
                                        </button>
                                        <button type="button" id="reset-btn"
                                            class="px-4 py-2.5 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                            <i class="fas fa-redo-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Selected Count Bar -->
                        <div class="px-6 py-3 bg-blue-50 border-b border-blue-200 hidden" id="selectedBar">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-blue-800">
                                        <span id="selectedCount">0</span> tasks selected
                                    </span>
                                </div>
                                <button type="button" id="clearSelection"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                    Clear Selection
                                </button>
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div id="technician-table">
                            @include('pages.products.warehouse.technicians.partials.table')
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

    {{-- Bulk Assign Modal --}}
    <div id="bulkAssignModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity">
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Bulk Assign Tasks</h3>
                            <p class="text-sm text-gray-500 mt-1">Assign tasks to technician</p>
                        </div>

                        <form id="bulkAssignForm">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Select Technician <span
                                            class="text-red-500">*</span></label>
                                    <select name="technician_id" id="technicianSelect"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                        required>
                                        <option value="">Choose technician...</option>
                                        @foreach ($technicians as $tech)
                                            <option value="{{ $tech->id }}">
                                                {{ $tech->username }} ({{ $tech->email }}) - {{ $tech->current_tasks }}
                                                active tasks
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Task Type <span
                                            class="text-red-500">*</span></label>
                                    <select name="task_type"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                        required>
                                        <option value="">Select task type...</option>
                                        <option value="Visual Inspection">Visual Inspection</option>
                                        <option value="Functional Testing">Functional Testing</option>
                                        <option value="Calibration">Calibration</option>
                                        <option value="Repair">Repair</option>
                                        <option value="Quality Check">Quality Check</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Priority</label>
                                    <select name="priority"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Due Date</label>
                                    <input type="date" name="due_date"
                                        class="date w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                        min="{{ date('Y-m-d') }}">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Instructions/Notes</label>
                                    <textarea name="remarks" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                        placeholder="Add any special instructions..."></textarea>
                                </div>

                                <input type="hidden" name="product_ids" id="selectedProductIds">
                            </div>

                            <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse mt-4 -mx-6 -mb-6">
                                <button type="submit" id="confirmBulkAssign"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto">
                                    ASSIGN TASKS
                                </button>
                                <button type="button" onclick="hideBulkAssignModal()"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto">
                                    CANCEL
                                </button>
                            </div>
                        </form>
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

        .toast-success {
            background-color: #10b981;
            color: white;
        }

        .toast-error {
            background-color: #ef4444;
            color: white;
        }

        .toast-warning {
            background-color: #f59e0b;
            color: white;
        }

        .toast-info {
            background-color: #3b82f6;
            color: white;
        }
    </style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/report-filter.js') }}"></script>
<script>
        // ========== CSRF TOKEN ==========
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // ========== DOM ELEMENTS ==========
        let checkAll = document.getElementById('checkAll');
        let rowChecks = document.querySelectorAll('.rowCheck');
        const selectedBar = document.getElementById('selectedBar');
        const selectedCountSpan = document.getElementById('selectedCount');
        const clearSelectionBtn = document.getElementById('clearSelection');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkCompleteBtn = document.getElementById('bulkCompleteBtn');

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
        }

        function handleCheckAll(e) {
            const isChecked = e.target.checked;
            document.querySelectorAll('.rowCheck').forEach(chk => chk.checked = isChecked);
            updateSelectedCount();
        }

        function handleRowCheck(e) {
            updateSelectAllCheckbox();
            updateSelectedCount();
        }

        function updateSelectAllCheckbox() {
            const checkboxes = document.querySelectorAll('.rowCheck');
            const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(checkbox => checkbox.checked);
            const someChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

            if (checkAll) {
                checkAll.checked = allChecked;
                checkAll.indeterminate = someChecked && !allChecked;
            }
        }

        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.rowCheck:checked').length;

            if (selectedCountSpan) {
                selectedCountSpan.textContent = selectedCount;
            }

            if (selectedBar) {
                if (selectedCount > 0) {
                    selectedBar.classList.remove('hidden');
                } else {
                    selectedBar.classList.add('hidden');
                }
            }

            // Update bulk action buttons
            if (bulkDeleteBtn) {
                if (selectedCount > 0) {
                    bulkDeleteBtn.removeAttribute('disabled');
                } else {
                    bulkDeleteBtn.setAttribute('disabled', 'disabled');
                }
            }

            if (bulkCompleteBtn) {
                if (selectedCount > 0) {
                    bulkCompleteBtn.removeAttribute('disabled');
                } else {
                    bulkCompleteBtn.setAttribute('disabled', 'disabled');
                }
            }
        }

        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.rowCheck:checked'))
                .map(checkbox => checkbox.value)
                .filter(id => id);
        }

        // Clear selection
        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function() {
                document.querySelectorAll('.rowCheck').forEach(chk => chk.checked = false);
                if (checkAll) {
                    checkAll.checked = false;
                    checkAll.indeterminate = false;
                }
                updateSelectedCount();
            });
        }

        // ========== WORKLOAD PANEL TOGGLE ==========
        function toggleWorkload() {
            const panel   = document.getElementById('workloadPanel');
            const chevron = document.getElementById('workloadChevron');
            const hidden  = panel.classList.toggle('hidden');
            chevron.style.transform = hidden ? '' : 'rotate(180deg)';
        }
        window.toggleWorkload = toggleWorkload;

        // ========== FILTER TOGGLE ==========
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
        window.toggleFilters = toggleFilters;

        // ========== DROPDOWN FUNCTIONS ==========
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative.w-full')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        // Action dropdowns
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

        // ========== FILTER FUNCTIONALITY ==========
        document.getElementById('statusFilter')?.addEventListener('change', () => ReportFilter.debouncedFilter());
        document.getElementById('categoryFilter')?.addEventListener('change', () => ReportFilter.debouncedFilter());
        document.getElementById('taskTypeFilter')?.addEventListener('change', () => ReportFilter.debouncedFilter());
        document.getElementById('perPageSelect')?.addEventListener('change', function () {
            document.getElementById('perPageHiddenInput').value = this.value;
            ReportFilter.debouncedFilter();
        });
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); ReportFilter.debouncedFilter(); }
        });
        document.getElementById('filter-form')?.addEventListener('submit', function(e) { e.preventDefault(); });

        ReportFilter.init({
            url: '{{ route('warehouse.technicians.index') }}',
            tableSelector: '#technician-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250,
        });
        window.hideBulkAssignModal = function() {
            document.getElementById('bulkAssignModal').classList.add('hidden');
        };

        // Bulk Assign Form Submit
        document.getElementById('bulkAssignForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const selectedIds = JSON.parse(document.getElementById('selectedProductIds').value || '[]');

            formData.delete('product_ids');
            selectedIds.forEach(id => {
                formData.append('product_ids[]', id);
            });

            const submitBtn = document.getElementById('confirmBulkAssign');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> ASSIGNING...';

            fetch('{{ route('warehouse.technicians.bulk.assign') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message);
                        hideBulkAssignModal();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Notification.error(data.message || 'Failed to assign tasks');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Notification.error('Network error. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        });

        // ========== BULK DELETE ==========
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                const selectedIds = getSelectedIds();

                if (selectedIds.length === 0) {
                    Notification.error('Please select tasks to delete');
                    return;
                }

                if (!confirm(`Are you sure you want to delete ${selectedIds.length} tasks?`)) {
                    return;
                }

                fetch('{{ route('warehouse.technicians.bulk.delete') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            ids: selectedIds
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Notification.success(data.message);
                            selectedIds.forEach(id => {
                                const row = document.querySelector(`tr[data-assignment-id="${id}"]`);
                                if (row) row.remove();
                            });
                            updateSelectedCount();
                        } else {
                            Notification.error(data.message || 'Failed to delete tasks');
                        }
                    })
                    .catch(error => {
                        Notification.error('Delete failed: ' + error.message);
                    });
            });
        }

        // ========== BULK COMPLETE ==========
        if (bulkCompleteBtn) {
            bulkCompleteBtn.addEventListener('click', function() {
                const selectedIds = getSelectedIds();

                if (selectedIds.length === 0) {
                    Notification.error('Please select tasks to mark as complete');
                    return;
                }

                if (!confirm(`Mark ${selectedIds.length} tasks as complete?`)) {
                    return;
                }

                fetch('{{ route('warehouse.technicians.bulk.complete') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            ids: selectedIds
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Notification.success(data.message);
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Notification.error(data.message || 'Failed to complete tasks');
                        }
                    })
                    .catch(error => {
                        Notification.error('Error: ' + error.message);
                    });
            });
        }

        // ========== DELETE CONFIRMATION ==========
        window.confirmDelete = function(button) {
            const url = button.dataset.url;
            const rowSelector = button.dataset.row;

            if (confirm('Are you sure you want to delete this assignment?')) {
                fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Notification.success(data.message);
                            const row = document.querySelector(rowSelector);
                            if (row) row.remove();
                            updateSelectedCount();
                        } else {
                            Notification.error(data.message || 'Failed to delete');
                        }
                    })
                    .catch(error => {
                        Notification.error('Network error. Please try again.');
                    });
            }
        };

        // ========== TASK FUNCTIONS ==========
        window.startTask = function(assignmentId, taskType) {
            if (confirm('Start this task?')) {
                fetch(`/warehouse/technicians/start-task/${assignmentId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const t = (taskType || '').toLowerCase();
                            let type = 'general';
                            if (t.includes('visual'))          type = 'visual';
                            else if (t.includes('functional')) type = 'functional';
                            else if (t.includes('calibration')) type = 'calibration';
                            else if (t.includes('repair'))     type = 'repair';
                            else if (t.includes('quality'))    type = 'quality';

                            window.location.href = `/warehouse/technicians/submit-result/${assignmentId}/${type}`;
                        } else {
                            Notification.error(data.message || 'Failed to start task');
                        }
                    })
                    .catch(error => {
                        Notification.error('Network error. Please try again.');
                    });
            }
        };

        window.retryTask = function(assignmentId) {
            if (confirm('Retry this task?')) {
                fetch(`/warehouse/technicians/retry-task/${assignmentId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Notification.success('Task retry initiated');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            Notification.error(data.message || 'Failed to retry task');
                        }
                    })
                    .catch(error => {
                        Notification.error('Network error. Please try again.');
                    });
            }
        };

        // ========== EXPORT FUNCTIONS ==========
        window.exportData = function(format) {
            const params = new URLSearchParams(window.location.search);
            let url = format === 'pdf' ?
                '{{ route('warehouse.technicians.export.pdf') }}' :
                '{{ route('warehouse.technicians.export.excel') }}';

            url += `?${params.toString()}`;
            window.open(url, '_blank');
            Notification.success(`Exporting data as ${format.toUpperCase()}`);
        };

        // ========== CLOSE MODALS ON OUTSIDE CLICK ==========
        const bulkAssignModal = document.getElementById('bulkAssignModal');
        if (bulkAssignModal) {
            bulkAssignModal.addEventListener('click', (e) => {
                if (e.target === bulkAssignModal) {
                    bulkAssignModal.classList.add('hidden');
                }
            });
        }
        // ========== INITIALIZE SELECT2 ==========
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select option';
                }
            });
        });

        // ========== INITIALIZE CHECKBOXES ==========
        document.addEventListener('DOMContentLoaded', function() {
            initializeCheckboxes();
            updateSelectedCount();
        });

        // Empty state bulk assign button
        document.addEventListener('DOMContentLoaded', function() {
            const emptyBulkAssignBtn = document.getElementById('emptyBulkAssignBtn');
            if (emptyBulkAssignBtn) {
                emptyBulkAssignBtn.addEventListener('click', function() {
                    document.getElementById('bulkAssignBtn').click();
                });
            }
        });
    </script>
@endpush
