@extends('layouts.auth')

@section('title', 'Task Management')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h2 class="text-stone-950 text-2xl">Task Management</h2>
                    </div>
                    <div class="flex flex-wrap gap-3">


                        <!-- Export Dropdown -->
                        <div class="relative">
                            <button onclick="toggleDropdown('exportDropdown')"
                                class="px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                EXPORT ▼
                            </button>
                            <div id="exportDropdown"
                                class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                <ul class="py-1">
                                    <li>
                                        <a href="#"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                PDF Report
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Excel Report
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Create New Task Button -->
                        <a href="{{ route('tasks.create') }}"
                            class="px-4 py-2.5 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] text-sm font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            CREATE NEW TASK
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total Tasks Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Tasks</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $totalTasks }}</h3>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-gray-500">All tasks assigned</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- In Progress Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">In Progress</p>
                            <h3 class="text-3xl font-bold text-yellow-600 mt-2">{{ $inProgressTasks }}</h3>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-gray-500">Tasks being worked on</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Completed Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Completed</p>
                            <h3 class="text-3xl font-bold text-green-600 mt-2">{{ $completedTasks }}</h3>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-gray-500">Successfully completed</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Overdue Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Overdue</p>
                            <h3 class="text-3xl font-bold text-red-600 mt-2">{{ $overdueTasks }}</h3>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-gray-500">Past deadline tasks</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Per-page selector --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 my-4">
                <div class="col-span-full flex justify-end">
                    <form method="GET" id="perPageForm">
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" name="priority" value="{{ request('priority') }}">
                        <input type="hidden" name="category" value="{{ request('category') }}">
                        <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
                        <input type="hidden" name="from_date" value="{{ request('from_date') }}">
                        <input type="hidden" name="to_date" value="{{ request('to_date') }}">

                        <select name="per_page"
                            class="select2 w-28 bg-white py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-0 focus:focus:outline-none"
                            onchange="document.getElementById('perPageForm').submit();">
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

            <!-- Main Content Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Filter Toggle Button -->
                <div class="flex items-center justify-end p-5">
                    <button onclick="toggleFilters()"
                        class="px-4 py-2.5 border border-[#0078C8] bg-white text-[#0078C8] rounded-lg hover:bg-[#0078C8] text-sm font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </button>
                </div>
                <div id="filtersSection"
                    class="hidden p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Row 1 -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-2">Department</label>
                            <select name="department_id"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                <option value="">All Departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                        {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->department }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                            <select name="status"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                <option value="">All Status</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ request('status') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-2">Priority</label>
                            <select name="priority"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                <option value="">All Priorities</option>
                                @foreach ($priorityOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ request('priority') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-2">Category</label>
                            <select name="category"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                <option value="">All Categories</option>
                                @foreach ($categoryOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ request('category') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Row 2 -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-2">Assigned To</label>
                            <select name="employee_id"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                <option value="">All Employees</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}"
                                        {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}"
                                class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}"
                                class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                            <input type="text" name="q" placeholder="Search by title or description..."
                                value="{{ request('q') }}"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                        </div>

                        <!-- Row 3: Action Buttons -->
                        <div class="col-span-full flex gap-2 mt-2">
                            <button type="submit"
                                class="px-6 py-2.5 border-2 border-[#0078C8] text-[#0078C8] rounded-lg hover:bg-[#0078C8] hover:text-white font-medium text-sm transition-colors">
                                SEARCH
                            </button>
                            <a href="{{ route('tasks.index') }}"
                                class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium text-sm transition-colors">
                                RESET
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Bulk Actions Bar -->
                <div id="bulkActionsBar" class="hidden bg-blue-50 border-b border-blue-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm font-medium text-blue-800" id="selectedCount">0</span>
                                <span class="text-sm text-blue-600">tasks selected</span>
                            </div>

                            <!-- Bulk Actions Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('bulkActionsDropdown')"
                                    class="px-4 py-2 bg-white border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 text-sm font-medium flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                    BULK ACTIONS
                                </button>
                                <div id="bulkActionsDropdown"
                                    class="hidden absolute left-0 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                    <ul class="py-1">
                                        <li>
                                            <button type="button" onclick="bulkUpdateStatus('in_progress')"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Mark as In Progress
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" onclick="bulkUpdateStatus('completed')"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                                Mark as Completed
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" onclick="bulkUpdateStatus('on_hold')"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Put on Hold
                                            </button>
                                        </li>
                                        <li class="border-t border-gray-100">
                                            <button type="button" onclick="bulkDelete()"
                                                class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Delete Selected
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <button onclick="clearSelection()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Clear Selection
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll()"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Task Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Deadline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($tasks as $task)
                                <tr id="row-{{ $task->id }}" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" value="{{ $task->id }}"
                                            class="task-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                            onchange="updateSelection()">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $task->title }}</div>
                                                <div class="text-xs text-gray-500 truncate max-w-xs">
                                                    {{ Str::limit($task->description, 50) }}</div>
                                                <div class="flex items-center gap-1 mt-1">
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                        {{ $categoryOptions[$task->category] ?? $task->category }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">
                                            {{ $task->department->department ?? 'N/A' }}</div>
                                    </td>

                                    <td class="px-6 py-4">
                                        @if ($task->assignedEmployees->count() > 0)
                                            <div class="flex flex-col gap-1">
                                                @foreach ($task->assignedEmployees->take(2) as $employee)
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium">
                                                            {{ substr($employee->full_name, 0, 1) }}
                                                        </div>
                                                        <span
                                                            class="text-sm text-gray-700">{{ $employee->full_name }}</span>
                                                    </div>
                                                @endforeach
                                                @if ($task->assignedEmployees->count() > 2)
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        +{{ $task->assignedEmployees->count() - 2 }} more
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400 italic">Not assigned</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $priorityColors = [
                                                'high' => 'bg-red-100 text-red-800',
                                                'medium' => 'bg-yellow-100 text-yellow-800',
                                                'low' => 'bg-green-100 text-green-800',
                                            ];
                                            $priorityColor =
                                                $priorityColors[$task->priority] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $priorityColor }}">
                                            {{ $priorityOptions[$task->priority] ?? $task->priority }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'in_progress' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'overdue' => 'bg-red-100 text-red-800',
                                                'on_hold' => 'bg-purple-100 text-purple-800',
                                                'cancelled' => 'bg-gray-100 text-gray-800',
                                                'draft' => 'bg-gray-100 text-gray-800',
                                            ];
                                            $statusColor = $statusColors[$task->status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                                            {{ $statusOptions[$task->status] ?? $task->status }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $task->deadline ? $task->deadline->format('d M, Y') : 'No deadline' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            @if ($task->deadline)
                                                @if ($task->deadline->isPast() && !in_array($task->status, ['completed', 'cancelled']))
                                                    <span class="text-red-600">Overdue</span>
                                                @else
                                                    {{ $task->deadline->diffForHumans() }}
                                                @endif
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center relative">
                                        <button
                                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-2 rounded-lg hover:bg-gray-100 transition-colors"
                                            data-id="{{ $task->id }}">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>

                                        <div id="dropdown-{{ $task->id }}"
                                            class="dropdown-menu hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 z-20 overflow-hidden">
                                            <a href="{{ route('tasks.show', $task->id) }}"
                                                class="w-full text-left px-4 py-3 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-700 transition-colors border-b border-gray-100">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    <path fill-rule="evenodd"
                                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                View Details
                                            </a>

                                            <a href="{{ route('tasks.edit', $task->id) }}"
                                                class="w-full text-left px-4 py-3 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-700 transition-colors border-b border-gray-100">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                                Edit Task
                                            </a>



                                            <button type="button"
                                                class="delete-btn w-full text-left px-4 py-3 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-700 transition-colors"
                                                data-url="{{ route('tasks.destroy', $task->id) }}"
                                                data-row="#row-{{ $task->id }}" onclick="confirmDelete(this)">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Delete Task
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <p class="text-lg font-medium text-gray-600 mb-2">No tasks found</p>
                                            <p class="text-sm text-gray-500">Create your first task to get started</p>
                                            <a href="{{ route('tasks.create') }}"
                                                class="mt-4 px-4 py-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] text-sm font-medium">
                                                Create New Task
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($tasks->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        {{ $tasks->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    <!-- Delete confirmation modal -->
    <x-delete-confirmation />

    <!-- Bulk Delete Confirmation Modal -->
    <div id="bulkDeleteModal" class="fixed inset-0 bg-black/50 hidden z-[99999] flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Delete Selected Tasks</h3>
                </div>
                <p class="text-sm text-gray-600 mb-6">
                    Are you sure you want to delete <span id="deleteCount" class="font-semibold">0</span> selected
                    task(s)? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeBulkDeleteModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmBulkDelete()"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                        Delete Tasks
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Filter toggle functionality
        function toggleFilters() {
            const filtersSection = document.getElementById('filtersSection');
            if (filtersSection.classList.contains('hidden')) {
                filtersSection.classList.remove('hidden');
            } else {
                filtersSection.classList.add('hidden');
            }
        }

        // Show filters if any filter is active
        document.addEventListener('DOMContentLoaded', function() {
            const hasActiveFilters = @json(request('q') ||
                    request('department_id') ||
                    request('status') ||
                    request('priority') ||
                    request('category') ||
                    request('employee_id') ||
                    request('from_date') ||
                    request('to_date'));

            if (hasActiveFilters) {
                document.getElementById('filtersSection').classList.remove('hidden');
            }
        });

        // Bulk selection variables
        let selectedTasks = new Set();
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // ------ Bulk Selection Functions ------
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.task-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
                if (selectAll.checked) {
                    selectedTasks.add(checkbox.value);
                } else {
                    selectedTasks.delete(checkbox.value);
                }
            });

            updateBulkActionsBar();
        }

        function updateSelection() {
            const checkboxes = document.querySelectorAll('.task-checkbox');
            selectedTasks.clear();

            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedTasks.add(checkbox.value);
                }
            });

            // Update "Select All" checkbox state
            const selectAll = document.getElementById('selectAll');
            selectAll.checked = selectedTasks.size > 0 && selectedTasks.size === checkboxes.length;
            selectAll.indeterminate = selectedTasks.size > 0 && selectedTasks.size < checkboxes.length;

            updateBulkActionsBar();
        }

        function updateBulkActionsBar() {
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            const selectedCount = document.getElementById('selectedCount');

            if (selectedTasks.size > 0) {
                bulkActionsBar.classList.remove('hidden');
                selectedCount.textContent = selectedTasks.size;
            } else {
                bulkActionsBar.classList.add('hidden');
            }
        }

        function clearSelection() {
            selectedTasks.clear();
            document.querySelectorAll('.task-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateBulkActionsBar();
        }

        // ------ Bulk Actions Functions ------
        function bulkUpdateStatus(status) {
            if (selectedTasks.size === 0) {
                showToast('Please select at least one task', 'error');
                return;
            }

            const taskIds = Array.from(selectedTasks);
            const statusText = {
                'in_progress': 'In Progress',
                'completed': 'Completed',
                'on_hold': 'On Hold'
            } [status] || status;

            if (confirm(`Are you sure you want to mark ${selectedTasks.size} task(s) as ${statusText}?`)) {
                fetch('{{ route('tasks.bulk-update') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            task_ids: taskIds,
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message || `Successfully updated ${selectedTasks.size} task(s)`, 'success');
                            clearSelection();
                            // Reload page after 1 second
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showToast(data.message || 'Failed to update tasks', 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Network error. Please try again.', 'error');
                    });
            }
        }

        function bulkDelete() {
            if (selectedTasks.size === 0) {
                showToast('Please select at least one task', 'error');
                return;
            }

            document.getElementById('deleteCount').textContent = selectedTasks.size;
            document.getElementById('bulkDeleteModal').classList.remove('hidden');
        }

        function closeBulkDeleteModal() {
            document.getElementById('bulkDeleteModal').classList.add('hidden');
        }

        function confirmBulkDelete() {
            const taskIds = Array.from(selectedTasks);

            fetch('{{ route('tasks.bulk-delete') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        task_ids: taskIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || `Successfully deleted ${selectedTasks.size} task(s)`, 'success');

                        // Remove deleted rows from table
                        taskIds.forEach(taskId => {
                            const row = document.getElementById(`row-${taskId}`);
                            if (row) row.remove();
                        });

                        // Update statistics if needed
                        if (data.stats) {
                            document.querySelectorAll('[id$="Tasks"]').forEach(el => {
                                const match = el.id.match(/(\w+)Tasks/);
                                if (match && data.stats[match[1]]) {
                                    el.textContent = data.stats[match[1]];
                                }
                            });
                        }

                        clearSelection();
                        closeBulkDeleteModal();

                        // Check if table is now empty
                        if (document.querySelectorAll('.task-checkbox').length === 0) {
                            // Show empty state
                        }
                    } else {
                        showToast(data.message || 'Failed to delete tasks', 'error');
                    }
                })
                .catch(error => {
                    showToast('Network error. Please try again.', 'error');
                });
        }

        // ------ Existing Functions (keep these) ------
        document.querySelectorAll('.dropdown-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const taskId = this.getAttribute('data-id');
                const menu = document.getElementById('dropdown-' + taskId);

                // Hide all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m.id !== 'dropdown-' + taskId) m.classList.add('hidden');
                });

                // Toggle current dropdown
                menu.classList.toggle('hidden');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // ------ Export dropdown ------
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        // ------ Toast helpers ------
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toast-content');
            toast.classList.remove('hidden');
            content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    ${type === 'success'
                        ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                        : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;
            setTimeout(hideToast, 3000);
        }

        function hideToast() {
            document.getElementById('toast').classList.add('hidden');
        }
        window.hideToast = hideToast;

        // ------ Delete via modal + AJAX ------
        let deleteTarget = {
            url: null,
            rowSel: null
        };
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');

        function confirmDelete(btnEl) {
            deleteTarget = {
                url: btnEl.dataset.url,
                rowSel: btnEl.dataset.row
            };
            deleteModal?.classList.remove('hidden');
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
        window.confirmDelete = confirmDelete;

        cancelDelete?.addEventListener('click', () => {
            deleteModal?.classList.add('hidden');
            deleteTarget = {
                url: null,
                rowSel: null
            };
        });

        // AJAX DELETE
        confirmDeleteBtn?.addEventListener('click', async () => {
            if (!deleteTarget.url) return;

            try {
                const res = await fetch(deleteTarget.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await res.json();

                if (res.ok && data?.success) {
                    // remove row
                    if (deleteTarget.rowSel) {
                        const row = document.querySelector(deleteTarget.rowSel);
                        row?.parentNode?.removeChild(row);
                    }
                    showToast(data.message || 'Task deleted successfully!', 'success');
                } else {
                    const msg = data?.message || 'Failed to delete task.';
                    showToast(msg, 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                deleteModal?.classList.add('hidden');
                deleteTarget = {
                    url: null,
                    rowSel: null
                };
            }
        });

        // Close modal when clicking outside
        deleteModal?.addEventListener('click', (e) => {
            if (e.target === deleteModal) {
                deleteModal.classList.add('hidden');
                deleteTarget = {
                    url: null,
                    rowSel: null
                };
            }
        });

        // Update task status
        function updateTaskStatus(taskId, status) {
            const confirmMessage = status === 'completed' ?
                'Are you sure you want to mark this task as completed?' :
                'Are you sure you want to put this task on hold?';

            if (confirm(confirmMessage)) {
                fetch(`/tasks/${taskId}/update-status`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            // Reload page after 1 second
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Network error. Please try again.', 'error');
                    });
            }
        }

        // Check for success/error messages in session
        @if (session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif

        @if (session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif

        // Close bulk delete modal when clicking outside
        document.getElementById('bulkDeleteModal')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('bulkDeleteModal')) {
                closeBulkDeleteModal();
            }
        });
    </script>
@endpush
