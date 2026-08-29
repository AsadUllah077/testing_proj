@extends('layouts.auth')

@section('title', 'Attendance Overview')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header with Metrics -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-2">Attendance Overview</h1>
                    <p class="text-gray-600 text-sm">Manage daily employee check-ins and generate reports.</p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                    <!-- Total Employees -->
                    <div class="bg-white rounded-lg border border-gray-100 p-4  cursor-pointer hover:shadow-md transition-shadow duration-200"
                        onclick="filterByStatus('all')">
                        <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1">Total Employees</p>
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $totalEmployees }}</h3>
                            <span class="text-[10px] bg-green-50 text-green-700 px-1.5 py-0.5 rounded-full font-medium">
                                +2%
                            </span>
                        </div>
                    </div>

                    <!-- Present Today -->
                    <div class="bg-white rounded-lg border border-gray-100 p-4  cursor-pointer hover:shadow-md transition-shadow duration-200 {{ request('status') == 'present' ? 'ring-2 ring-blue-500' : '' }}"
                        onclick="filterByStatus('present')">
                        <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1">Present Today</p>
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $presentToday }}</h3>
                            <span class="text-[10px] text-gray-500 font-medium">{{ $attendanceRate }}% Rate</span>
                        </div>
                    </div>

                    <!-- Absent Today -->
                    <div class="bg-white rounded-lg border border-gray-100 p-4  cursor-pointer hover:shadow-md transition-shadow duration-200 {{ request('status') == 'absent' ? 'ring-2 ring-blue-500' : '' }}"
                        onclick="filterByStatus('absent')">
                        <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1">Absent Today</p>
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $absentToday }}</h3>
                            <span class="text-[10px] bg-red-50 text-red-700 px-1.5 py-0.5 rounded-full font-medium">
                                High
                            </span>
                        </div>
                    </div>

                    <!-- Late Arrivals -->
                    <div class="bg-white rounded-lg border border-gray-100 p-4  cursor-pointer hover:shadow-md transition-shadow duration-200 {{ request('status') == 'late' ? 'ring-2 ring-blue-500' : '' }}"
                        onclick="filterByStatus('late')">
                        <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1">Late Arrivals</p>
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $lateToday }}</h3>
                            <span class="text-[10px] text-gray-500 font-medium">Avg 15m</span>
                        </div>
                    </div>

                    <!-- Total Leave Records -->
                    <div class="bg-white rounded-lg border border-gray-100 p-4  cursor-pointer hover:shadow-md transition-shadow duration-200 border-l-4 border-blue-500"
                        onclick="window.location.href='{{ route('hrm.leave.index') }}'">
                        <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1">Total Leave Records</p>
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $totalLeaves }}</h3>
                            <span class="text-[10px] bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded-full font-medium">
                                View all
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Header with Buttons -->
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Attendance Records</h2>
                        @if (request('status'))
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-sm text-gray-600">Filtered by:</span>
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full
                                                {{ request('status') == 'present'
                                                    ? 'bg-green-100 text-green-800'
                                                    : (request('status') == 'absent'
                                                        ? 'bg-red-100 text-red-800'
                                                        : 'bg-orange-100 text-orange-800') }}">
                                    {{ ucfirst(request('status')) }}
                                </span>
                                <button onclick="clearFilter()" class="text-xs text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative">
                        <div></div>

                        <!-- Column Chooser -->
                        <x-columns :columns="[
                            'at_id' => 'S.No',
                            'at_employee_id' => 'Employee',
                            'at_attendance_date' => 'Date',
                            'at_department_id' => 'Department',
                            'at_check_in' => 'Check In',
                            'at_check_out' => 'Check Out',
                            'at_total_work_hours' => 'Work Hours',
                            'at_status' => 'Status',
                        ]" name="attendance_columns" />

                        <!-- Export Button (opens modal) -->
                        <div class="relative w-full">
                            <button onclick="document.getElementById('exportModal').classList.remove('hidden')"
                                class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                EXPORT
                            </button>
                        </div>

                        <!-- Add this to your attendance index view -->
                        {{-- <div class="mb-3">
                            <button type="button" class="btn btn-success" id="exportBtn">
                                <i class="bi bi-file-excel"></i> Export to Excel
                            </button>

                            <button type="button" class="btn btn-primary" id="exportDepartmentWiseBtn">
                                <i class="bi bi-folder"></i> Export Department-wise
                            </button>
                        </div>

                        <script>
                            document.getElementById('exportBtn').addEventListener('click', function() {
                                const params = new URLSearchParams({
                                    from_date: document.querySelector('[name="from_date"]')?.value || '',
                                    to_date: document.querySelector('[name="to_date"]')?.value || '',
                                    department_id: document.querySelector('[name="department_id"]')?.value || '',
                                    status: document.querySelector('[name="status"]')?.value || '',
                                    q: document.querySelector('[name="q"]')?.value || '',
                                });

                                window.location.href = '{{ route('hrm.attendance.export1') }}?' + params.toString();
                            });

                            document.getElementById('exportDepartmentWiseBtn').addEventListener('click', function() {
                                const params = new URLSearchParams({
                                    from_date: document.querySelector('[name="from_date"]')?.value || '',
                                    to_date: document.querySelector('[name="to_date"]')?.value || '',
                                    department_id: document.querySelector('[name="department_id"]')?.value || '',
                                    status: document.querySelector('[name="status"]')?.value || '',
                                    q: document.querySelector('[name="q"]')?.value || '',
                                });

                                window.location.href = '{{ route('hrm.attendance.export.department-wise') }}?' + params.toString();
                            });
                        </script> --}}



                        <!-- Add New Button -->
                        <a href="{{ route('hrm.attendance.create') }}"
                            class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            ADD NEW
                        </a>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden">
                    <!-- Filters Section -->
                    <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                        <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                <input type="date" name="from_date" value="{{ request('from_date') }}" id="from_date"
                                    class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                <input type="date" name="to_date" value="{{ request('to_date') }}" id="to_date"
                                    class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">Department</label>
                                <select name="department_id" id="department_id"
                                    class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    <option value="">All Departments</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}"
                                            {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->department }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                                <select name="status" id="statusFilter"
                                    class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    <option value="">All Status</option>
                                    <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present
                                    </option>
                                    <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent
                                    </option>
                                    <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late
                                    </option>
                                </select>
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                <input type="text" name="q" id="search-q"
                                    placeholder="Search by name or employee ID..." value="{{ request('q') }}"
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <x-default-page />
                            </div>
                            <x-filter-button />
                        </form>
                    </div>

                    <!-- Bulk company transfer -->
                    <div class="px-6 py-4 bg-blue-50/60 border-b border-blue-100 flex flex-col lg:flex-row lg:items-end gap-3">
                        <div class="flex-1 min-w-[220px]">
                            <label for="attendance-target-company" class="block text-xs font-medium text-gray-700 mb-2">
                                Transfer selected records to
                            </label>
                            <select id="attendance-target-company"
                                class="w-full py-2.5 px-4 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                                <option value="">Select another company</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500">
                                The destination employee is matched using the source employee's email address.
                            </p>
                        </div>
                        <div class="flex items-center justify-between lg:justify-start gap-3">
                            <span id="attendance-selected-count" class="text-sm font-medium text-gray-600">0 selected</span>
                            <button type="button" id="attendance-transfer-button" disabled
                                class="px-4 py-2.5 bg-[#0078C8] text-white text-xs font-semibold rounded-lg hover:bg-[#006bb0] disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
                                TRANSFER ATTENDANCE
                            </button>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div id="attendance-table">
                        @include('pages.hrm.attendance.partials.table')
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

    {{-- Delete confirmation modal component --}}
    <x-delete-confirmation />

    {{-- Export Options Modal --}}
    <div id="exportModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-[#0078C8] to-[#006bb0] px-6 py-4 flex items-center justify-between">
                <h3 class="text-white font-semibold text-lg">Export Attendance</h3>
                <button onclick="closeExportModal()" class="text-white/80 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-5">
                <!-- Export Mode -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Export Mode</label>
                    <div class="space-y-2">
                        <label
                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-[#0078C8] hover:bg-blue-50/50 cursor-pointer transition-all group">
                            <input type="radio" name="export_mode" value="group" checked
                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]" onchange="toggleExportSections()">
                            <div>
                                <span class="text-sm font-medium text-gray-800 group-hover:text-[#0078C8]">Group</span>
                                <p class="text-xs text-gray-500">Grouped by department (current behavior)</p>
                            </div>
                        </label>
                        <label
                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-[#0078C8] hover:bg-blue-50/50 cursor-pointer transition-all group">
                            <input type="radio" name="export_mode" value="by-name"
                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]" onchange="toggleExportSections()">
                            <div>
                                <span class="text-sm font-medium text-gray-800 group-hover:text-[#0078C8]">By Name</span>
                                <p class="text-xs text-gray-500">Sorted alphabetically by employee name</p>
                            </div>
                        </label>
                        <label
                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-[#0078C8] hover:bg-blue-50/50 cursor-pointer transition-all group">
                            <input type="radio" name="export_mode" value="individual"
                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]" onchange="toggleExportSections()">
                            <div>
                                <span
                                    class="text-sm font-medium text-gray-800 group-hover:text-[#0078C8]">Individual</span>
                                <p class="text-xs text-gray-500">Export attendance for a specific employee</p>
                            </div>
                        </label>
                        <label
                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-[#0078C8] hover:bg-blue-50/50 cursor-pointer transition-all group">
                            <input type="radio" name="export_mode" value="monthly-summary"
                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]" onchange="toggleExportSections()">
                            <div>
                                <span class="text-sm font-medium text-gray-800 group-hover:text-[#0078C8]">Monthly
                                    Summary</span>
                                <p class="text-xs text-gray-500">Summary export (Present, Absent, Leaves) for multiple
                                    months
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Individual Employee Search (hidden by default) -->
                <div id="individualSearchSection" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Employee</label>
                    <div class="relative">
                        <input type="text" id="employeeSearchInput" placeholder="Type employee name..."
                            autocomplete="off"
                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        <input type="hidden" id="selectedEmployeeId" value="">
                        <!-- Search Results Dropdown -->
                        <div id="employeeSearchResults"
                            class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 max-h-48 overflow-y-auto">
                        </div>
                    </div>
                    <p id="selectedEmployeeName" class="text-xs text-green-600 mt-1 hidden">
                        <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        Selected: <span id="selectedEmployeeLabel"></span>
                    </p>
                </div>
                <!-- Monthly Duration Selection -->
                <div id="monthlyDurationSection" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Duration</label>
                    <select id="exportDuration"
                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        <option value="">Select Duration</option>
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}">{{ $i }} {{ $i == 1 ? 'Month' : 'Months' }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Format Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Format</label>
                    <div class="flex gap-3">
                        <label
                            class="flex-1 flex items-center justify-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-[#0078C8] hover:bg-blue-50/50 cursor-pointer transition-all group">
                            <input type="radio" name="export_format" value="pdf" checked
                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium text-gray-800 group-hover:text-[#0078C8]">PDF</span>
                        </label>
                        <label
                            class="flex-1 flex items-center justify-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-[#0078C8] hover:bg-blue-50/50 cursor-pointer transition-all group">
                            <input type="radio" name="export_format" value="xlsx"
                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium text-gray-800 group-hover:text-[#0078C8]">Excel</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <button onclick="closeExportModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button onclick="submitExport()"
                    class="px-5 py-2 text-sm font-medium text-white bg-[#0078C8] rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                </button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .grid-cols-9 {
            grid-template-columns: repeat(9, minmax(0, 1fr));
        }

        .grid-cols-8 {
            grid-template-columns: repeat(8, minmax(0, 1fr));
        }

        .clickable-card {
            transition: all 0.2s ease;
        }

        .clickable-card:hover {
            transform: translateY(-2px);
        }

        .active-filter {
            border-color: #0078C8;
            background-color: #f0f9ff;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Export dropdown
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        // Toast helpers
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

        // Export to PDF
        function exportToPDF() {
            // Implement PDF export logic here
            showToast('PDF export feature coming soon!', 'success');
        }

        // Filter by status when clicking on cards
        function filterByStatus(status) {
            const url = new URL(window.location.href);

            if (status === 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', status);
            }

            // Remove page parameter to start from page 1
            url.searchParams.delete('page');

            window.location.href = url.toString();
        }

        // Clear filter
        function clearFilter() {
            const url = new URL(window.location.href);
            url.searchParams.delete('status');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        // Update status dropdown when clicking cards
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('statusFilter');
            const currentStatus = '{{ request('status') }}';

            if (currentStatus) {
                if (statusFilter) {
                    statusFilter.value = currentStatus;
                    // Trigger Select2 update if using Select2
                    if ($.fn.select2 && statusFilter.classList.contains('select2-hidden-accessible')) {
                        $(statusFilter).val(currentStatus).trigger('change');
                    }
                }
            }
        });

        // Dropdown toggle for action buttons
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                // Toggle current dropdown
                menu.classList.toggle('hidden');
            }
        });


        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Delete modal functions
        let deleteTarget = {
            url: null,
            rowSel: null
        };
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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
                    if (deleteTarget.rowSel) {
                        const row = document.querySelector(deleteTarget.rowSel);
                        row?.parentNode?.removeChild(row);
                    }
                    showToast(data.message || 'Attendance record deleted successfully!', 'success');
                } else {
                    showToast(data?.message || 'Failed to delete attendance record.', 'error');
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

        // Close modals when clicking outside
        [deleteModal].forEach(modal => {
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    if (modal === deleteModal) deleteTarget = {
                        url: null,
                        rowSel: null
                    };
                }
            });
        });

        // Bulk attendance company transfer
        const attendanceTable = document.getElementById('attendance-table');
        const attendanceTargetCompany = document.getElementById('attendance-target-company');
        const attendanceTransferButton = document.getElementById('attendance-transfer-button');
        const attendanceSelectedCount = document.getElementById('attendance-selected-count');
        let attendanceTransferInProgress = false;

        function selectedAttendanceIds() {
            return Array.from(document.querySelectorAll('#attendance-table .attendance-row-checkbox:checked:not(:disabled)'))
                .map(checkbox => Number(checkbox.value));
        }

        function updateAttendanceTransferState() {
            const rowCheckboxes = Array.from(document.querySelectorAll('#attendance-table .attendance-row-checkbox:not(:disabled)'));
            const selectedCount = rowCheckboxes.filter(checkbox => checkbox.checked).length;
            const selectAll = document.getElementById('attendance-select-all');

            attendanceSelectedCount.textContent = `${selectedCount} selected`;
            attendanceTransferButton.disabled = attendanceTransferInProgress || selectedCount === 0 || !attendanceTargetCompany.value;

            if (selectAll) {
                selectAll.disabled = rowCheckboxes.length === 0;
                selectAll.checked = rowCheckboxes.length > 0 && selectedCount === rowCheckboxes.length;
                selectAll.indeterminate = selectedCount > 0 && selectedCount < rowCheckboxes.length;
            }
        }

        document.addEventListener('change', event => {
            if (event.target.id === 'attendance-select-all') {
                document.querySelectorAll('#attendance-table .attendance-row-checkbox:not(:disabled)').forEach(checkbox => {
                    checkbox.checked = event.target.checked;
                });
                updateAttendanceTransferState();
            }

            if (event.target.classList.contains('attendance-row-checkbox')) {
                updateAttendanceTransferState();
            }
        });

        attendanceTargetCompany?.addEventListener('change', updateAttendanceTransferState);
        new MutationObserver(updateAttendanceTransferState).observe(attendanceTable, {
            childList: true,
            subtree: true
        });

        attendanceTransferButton?.addEventListener('click', async () => {
            const attendanceIds = selectedAttendanceIds();
            const companyId = Number(attendanceTargetCompany.value);
            const companyName = attendanceTargetCompany.options[attendanceTargetCompany.selectedIndex]?.textContent?.trim();

            if (attendanceIds.length === 0 || !companyId) {
                showToast('Select attendance records and a destination company first.', 'error');
                return;
            }

            if (!window.confirm(`Transfer ${attendanceIds.length} attendance record(s) to ${companyName}?`)) {
                return;
            }

            attendanceTransferInProgress = true;
            attendanceTransferButton.textContent = 'TRANSFERRING...';
            updateAttendanceTransferState();

            try {
                const response = await fetch('{{ route('hrm.attendance.bulk-transfer-company') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        attendance_ids: attendanceIds,
                        company_id: companyId
                    })
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Attendance records could not be transferred.');
                }

                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                showToast(error.message || 'Network error. Please try again.', 'error');
                attendanceTransferInProgress = false;
                attendanceTransferButton.textContent = 'TRANSFER ATTENDANCE';
                updateAttendanceTransferState();
            }
        });

        updateAttendanceTransferState();

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[id$="Dropdown"]') && !e.target.closest('button')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        @if (session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif

        @if (session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif
    </script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        ReportFilter.init({
            url: '{{ route('hrm.attendance.index') }}',
            tableSelector: '#attendance-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
    <script>
        // ========== Export Modal Logic ==========

        let employeeSearchTimeout = null;

        function closeExportModal() {
            document.getElementById('exportModal').classList.add('hidden');
            resetExportModal();
        }

        function resetExportModal() {
            document.querySelector('input[name="export_mode"][value="group"]').checked = true;
            document.querySelector('input[name="export_format"][value="pdf"]').checked = true;
            document.getElementById('individualSearchSection').classList.add('hidden');
            document.getElementById('monthlyDurationSection').classList.add('hidden');
            document.getElementById('employeeSearchInput').value = '';
            document.getElementById('selectedEmployeeId').value = '';
            document.getElementById('selectedEmployeeName').classList.add('hidden');
            document.getElementById('employeeSearchResults').classList.add('hidden');
            document.getElementById('exportDuration').value = '1';
        }

        function toggleExportSections() {
            const mode = document.querySelector('input[name="export_mode"]:checked').value;
            const individualSection = document.getElementById('individualSearchSection');
            const monthlySection = document.getElementById('monthlyDurationSection');

            // Toggle Individual Section
            if (mode === 'individual') {
                individualSection.classList.remove('hidden');
            } else {
                individualSection.classList.add('hidden');
                document.getElementById('employeeSearchInput').value = '';
                document.getElementById('selectedEmployeeId').value = '';
                document.getElementById('selectedEmployeeName').classList.add('hidden');
            }

            // Toggle Monthly Section
            if (mode === 'monthly-summary') {
                monthlySection.classList.remove('hidden');
            } else {
                monthlySection.classList.add('hidden');
            }
        }

        // Employee search typeahead
        document.getElementById('employeeSearchInput').addEventListener('input', function() {
            clearTimeout(employeeSearchTimeout);
            const query = this.value.trim();
            const resultsDiv = document.getElementById('employeeSearchResults');

            if (query.length < 1) {
                resultsDiv.classList.add('hidden');
                return;
            }

            employeeSearchTimeout = setTimeout(() => {
                fetch('{{ route('hrm.attendance.search-employees') }}?q=' + encodeURIComponent(query), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(employees => {
                        if (employees.length === 0) {
                            resultsDiv.innerHTML =
                                '<div class="px-4 py-3 text-sm text-gray-500">No employees found</div>';
                        } else {
                            resultsDiv.innerHTML = employees.map(emp =>
                                `<div class="px-4 py-2.5 text-sm text-gray-700 hover:bg-[#0078C8] hover:text-white cursor-pointer transition-colors"
                                  onclick="selectEmployee(${emp.id}, '${emp.full_name.replace(/'/g, "\\'")}')">
                                <span class="font-medium">${emp.full_name}</span> <span class="font-medium">(${emp.email})</span>
                                ${emp.employee_card_id ? '<span class="text-xs opacity-75 ml-1">(' + emp.employee_card_id + ')</span>' : ''}
                            </div>`
                            ).join('');
                        }
                        resultsDiv.classList.remove('hidden');
                    })
                    .catch(() => {
                        resultsDiv.innerHTML =
                            '<div class="px-4 py-3 text-sm text-red-500">Search failed</div>';
                        resultsDiv.classList.remove('hidden');
                    });
            }, 300);
        });

        function selectEmployee(id, name) {
            document.getElementById('selectedEmployeeId').value = id;
            document.getElementById('employeeSearchInput').value = name;
            document.getElementById('employeeSearchResults').classList.add('hidden');
            document.getElementById('selectedEmployeeName').classList.remove('hidden');
            document.getElementById('selectedEmployeeLabel').textContent = name;
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#individualSearchSection')) {
                document.getElementById('employeeSearchResults').classList.add('hidden');
            }
        });

        // Close modal when clicking backdrop
        document.getElementById('exportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeExportModal();
            }
        });

        function submitExport() {
            const mode = document.querySelector('input[name="export_mode"]:checked').value;
            const format = document.querySelector('input[name="export_format"]:checked').value;

            // Map mode to export type
            let exportType;
            if (mode === 'group') {
                exportType = 'attendance-department-wise';
            } else if (mode === 'by-name') {
                exportType = 'attendance-by-name';
            } else if (mode === 'individual') {
                const empId = document.getElementById('selectedEmployeeId').value;
                if (!empId) {
                    alert('Please select an employee first.');
                    return;
                }
                exportType = 'attendance-individual';
            } else if (mode === 'monthly-summary') {
                exportType = 'attendance-monthly-summary';
            }

            // Call the global startExport with our type
            startExport(format, exportType);

            // Close modal after starting export to ensure data is captured
            closeExportModal();
        }

        // Live Work Hours Updater
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(() => {
                document.querySelectorAll('.live-work-hours').forEach(el => {
                    const checkInIso = el.getAttribute('data-checkin-time');
                    if (checkInIso) {
                        const checkInDate = new Date(checkInIso);
                        const now = new Date();
                        let diffInSeconds = Math.floor((now - checkInDate) / 1000);
                        
                        if (diffInSeconds < 0) diffInSeconds = 0; // prevent negative if clocks differ slightly

                        const hours = Math.floor(diffInSeconds / 3600);
                        const minutes = Math.floor((diffInSeconds % 3600) / 60);
                        const seconds = diffInSeconds % 60;
                        
                        el.textContent = `${hours}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
                    }
                });
            }, 1000);
        });
    </script>
@endpush
