@extends('layouts.auth')

@section('title', 'Leave Calendar')

@section('content')
    <div class="mt-4">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.leave-management') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header with Filters -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6 mb-6">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg  text-black mb-2">Leave Calendar</h2>

                            </div>

                            <!-- Filters -->
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Department Filter -->
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Department</label>
                                    <select id="departmentFilter"
                                        class="select2 border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none w-full lg:w-48">
                                        <option value="">All Departments</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->department }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Leave Type Filter -->
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Leave Type</label>
                                    <select id="leaveTypeFilter"
                                        class="select2 border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none w-full lg:w-48">
                                        <option value="">All Leave Types</option>
                                        @foreach ($leaveTypes as $leaveType)
                                            <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Clear Filters Button -->
                                <div class="mt-5">
                                    <form action="{{ route('hrm.calendar') }}">
                                        <button type="submit"
                                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                            Clear Filters
                                        </button>
                                    </form>



                                </div>
                            </div>
                        </div>

                        <!-- Month Navigation -->
                        <div class="flex items-center justify-between mt-6">
                            <div class="flex items-center gap-2">
                                <button id="prevMonth" class="p-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <h3 id="currentMonth" class="text-md font-semibold text-gray-700 min-w-[180px] text-center">
                                    Loading...</h3>
                                <button id="nextMonth" class="p-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Today Button -->
                            <button id="todayBtn"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Today
                            </button>
                        </div>

                        <!-- Legend -->
                        {{-- <div class="mt-4 flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-xs text-gray-600">Annual Leave</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-xs text-gray-600">Sick Leave</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                            <span class="text-xs text-gray-600">Casual Leave</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                            <span class="text-xs text-gray-600">Remote Work</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-pink-500 rounded-full"></div>
                            <span class="text-xs text-gray-600">Business Trip</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <span class="text-xs text-gray-600">Half Day</span>
                        </div>
                    </div> --}}
                    </div>

                    <!-- Calendar -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6">
                        <!-- Weekday Headers -->
                        <div class="grid grid-cols-7 gap-1 mb-2">
                            <div class="text-center text-sm font-semibold text-gray-500 py-2 bg-red-50">SUN</div>
                            <div class="text-center text-sm font-semibold text-gray-500 py-2">MON</div>
                            <div class="text-center text-sm font-semibold text-gray-500 py-2">TUE</div>
                            <div class="text-center text-sm font-semibold text-gray-500 py-2">WED</div>
                            <div class="text-center text-sm font-semibold text-gray-500 py-2">THU</div>
                            <div class="text-center text-sm font-semibold text-gray-500 py-2">FRI</div>
                            <div class="text-center text-sm font-semibold text-gray-500 py-2 bg-red-50">SAT</div>
                        </div>

                        <!-- Calendar Grid -->
                        <div id="calendarGrid" class="grid grid-cols-7 gap-1">
                            <!-- Calendar will be loaded here via JavaScript -->
                        </div>

                        <!-- Loading State -->
                        <div id="calendarLoading" class="text-center py-8">
                            <div class="inline-flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-[#0078C8]"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="text-sm text-gray-600">Loading calendar...</span>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div id="calendarEmpty" class="text-center py-8 hidden">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm text-gray-500">No leaves found for selected filters</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Leave Details Modal -->
    <div id="leaveDetailsModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Leave Details</h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="modalContent">
                    <!-- Leave details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .calendar-day {
            min-height: 120px;
        }

        .calendar-day.today {
            background-color: #f0f9ff;
            border-color: #bae6fd;
        }

        .calendar-day.weekend {
            background-color: #f8fafc;
        }

        .leave-item {
            transition: all 0.2s ease;
        }

        .leave-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentMonth = new Date().getMonth();
            let currentYear = new Date().getFullYear();

            // Function to get leave type color
            function getLeaveTypeColor(leaveTypeName) {
                const name = leaveTypeName.toLowerCase();

                if (name.includes('annual') || name.includes('vacation')) {
                    return 'bg-blue-100 text-blue-800 border-blue-200';
                } else if (name.includes('sick') || name.includes('medical')) {
                    return 'bg-green-100 text-green-800 border-green-200';
                } else if (name.includes('casual') || name.includes('personal')) {
                    return 'bg-purple-100 text-purple-800 border-purple-200';
                } else if (name.includes('remote') || name.includes('work from home')) {
                    return 'bg-yellow-100 text-yellow-800 border-yellow-200';
                } else if (name.includes('business') || name.includes('trip')) {
                    return 'bg-pink-100 text-pink-800 border-pink-200';
                } else if (name.includes('half') || name.includes('half day')) {
                    return 'bg-orange-100 text-orange-800 border-orange-200';
                } else if (name.includes('conference') || name.includes('training')) {
                    return 'bg-indigo-100 text-indigo-800 border-indigo-200';
                }

                return 'bg-gray-100 text-gray-800 border-gray-200';
            }

            // Function to get leave type dot color
            function getLeaveTypeDotColor(leaveTypeName) {
                const name = leaveTypeName.toLowerCase();

                if (name.includes('annual') || name.includes('vacation')) return 'bg-blue-500';
                if (name.includes('sick') || name.includes('medical')) return 'bg-green-500';
                if (name.includes('casual') || name.includes('personal')) return 'bg-purple-500';
                if (name.includes('remote') || name.includes('work from home')) return 'bg-yellow-500';
                if (name.includes('business') || name.includes('trip')) return 'bg-pink-500';
                if (name.includes('half') || name.includes('half day')) return 'bg-orange-500';
                if (name.includes('conference') || name.includes('training')) return 'bg-indigo-500';

                return 'bg-gray-500';
            }

            // Function to get days in month
            function getDaysInMonth(year, month) {
                return new Date(year, month + 1, 0).getDate();
            }

            // Function to get first day of month
            function getFirstDayOfMonth(year, month) {
                return new Date(year, month, 1).getDay();
            }

            // Function to format date
            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
            // Add this helper function to parse dates without timezone issues:
            function parseLocalDate(dateString) {
                const [year, month, day] = dateString.split('-').map(Number);
                return new Date(year, month - 1, day);
            }

            // Function to load calendar
            // In your calendar.blade.php JavaScript section
            function loadCalendar(year, month) {
                $('#calendarGrid').empty();
                $('#calendarLoading').removeClass('hidden');
                $('#calendarEmpty').addClass('hidden');

                const daysInMonth = getDaysInMonth(year, month);
                const firstDay = getFirstDayOfMonth(year, month);
                const departmentId = $('#departmentFilter').val();
                const leaveTypeId = $('#leaveTypeFilter').val();

                // Get the month name
                const monthNames = [
                    "January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];
                $('#currentMonth').text(`${monthNames[month]} ${year}`);

                // Add empty cells for days before the first day of the month
                for (let i = 0; i < firstDay; i++) {
                    $('#calendarGrid').append(
                        '<div class="calendar-day p-2 border border-gray-100 bg-gray-50 rounded"></div>');
                }

                // Prepare data for AJAX request
                const requestData = {
                    year: year,
                    month: month + 1,
                };

                // Only add department_id if it has a value
                if (departmentId && departmentId.trim() !== '') {
                    requestData.department_id = departmentId;
                }

                // Only add leave_type_id if it has a value
                if (leaveTypeId && leaveTypeId.trim() !== '') {
                    requestData.leave_type_id = leaveTypeId;
                }

                // Fetch leave data for the month
                $.ajax({
                    url: '{{ route('hrm.leave.calendar-data') }}',
                    method: 'GET',
                    data: requestData,
                    success: function(response) {
                        $('#calendarLoading').addClass('hidden');

                        if (response.success && response.data.length > 0) {
                            const leavesByDate = {};

                            // Group leaves by date
                            response.data.forEach(leave => {
                                const startDate = new Date(leave.start_date);
                                const endDate = new Date(leave.end_date);

                                // For each day in the leave period
                                const currentDate = new Date(startDate);
                                while (currentDate <= endDate) {
                                    const dateStr = formatDate(currentDate);

                                    // Check working days
                                    let isWorkingDay = true;
                                    const workFrom = leave.employee.working_day_from;
                                    const workTo = leave.employee.working_day_to;

                                    if (workFrom && workTo) {
                                        const day = currentDate.getDay(); // 0 (Sun) - 6 (Sat)
                                        const days = ['sunday', 'monday', 'tuesday',
                                            'wednesday', 'thursday', 'friday', 'saturday'
                                        ];
                                        const start = days.indexOf(workFrom.toLowerCase());
                                        const end = days.indexOf(workTo.toLowerCase());

                                        if (start !== -1 && end !== -1) {
                                            if (start <= end) {
                                                isWorkingDay = day >= start && day <= end;
                                            } else {
                                                // Wrap around case (e.g. Fri to Mon)
                                                isWorkingDay = day >= start || day <= end;
                                            }
                                        }
                                    } else {
                                        isWorkingDay = true;
                                    }

                                    if (isWorkingDay) {
                                        if (!leavesByDate[dateStr]) {
                                            leavesByDate[dateStr] = [];
                                        }
                                        leavesByDate[dateStr].push(leave);
                                    }
                                    currentDate.setDate(currentDate.getDate() + 1);
                                }
                            });

                            // Create calendar cells for each day of the month
                            for (let day = 1; day <= daysInMonth; day++) {
                                const currentDate = new Date(year, month, day);
                                const dateStr = formatDate(currentDate);
                                const isToday = currentDate.toDateString() === new Date()
                                    .toDateString();
                                const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() ===
                                    6;

                                const todayClass = isToday ? 'today' : '';
                                const weekendClass = isWeekend ? 'weekend' : '';

                                let cellHtml = `
                        <div class="calendar-day p-2 border border-gray-200 rounded overflow-y-auto ${todayClass} ${weekendClass}">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-sm font-medium ${isToday ? 'text-blue-600' : (isWeekend ? 'text-red-600' : 'text-gray-700')}">${day}</span>
                                ${isToday ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Today</span>' : ''}
                            </div>
                            <div class="space-y-1 mt-2" id="leaves-${dateStr}">
                    `;

                                // Add leaves for this date
                                if (leavesByDate[dateStr]) {
                                    leavesByDate[dateStr].forEach((leave, index) => {
                                        // Shorten employee name: "John Doe" -> "John D."
                                        const nameParts = leave.employee.full_name.split(' ');
                                        const shortName = nameParts[0] + ' ' + (nameParts[1] ?
                                            nameParts[1].charAt(0) + '.' : '');

                                        const leaveTypeName = leave.leave_type.name;
                                        const bgColorClass = getLeaveTypeColor(leaveTypeName);
                                        const dotColorClass = getLeaveTypeDotColor(
                                            leaveTypeName);

                                        cellHtml += `
                                <div class="cursor-pointer leave-item text-xs p-1 rounded border ${bgColorClass} hover:opacity-90"
                                     data-leave-id="${leave.id}"
                                     title="${leave.employee.full_name} - ${leaveTypeName}">
                                    <div class="flex items-center gap-1">
                                        <div class="w-1 h-1 ${dotColorClass} rounded-full"></div>
                                        <span class="font-medium truncate">${shortName}</span>
                                    </div>
                                    <div class="truncate text-xs mt-0.5">(${leaveTypeName})</div>
                                </div>
                            `;
                                    });
                                }

                                cellHtml += '</div></div>';
                                $('#calendarGrid').append(cellHtml);
                            }

                            // Add click handlers to leave items
                            $('.leave-item').on('click', function() {
                                const leaveId = $(this).data('leave-id');
                                showLeaveDetails(leaveId);
                            });

                        } else {
                            // No leaves found
                            for (let day = 1; day <= daysInMonth; day++) {
                                const currentDate = new Date(year, month, day);
                                const isToday = currentDate.toDateString() === new Date()
                                    .toDateString();
                                const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() ===
                                    6;

                                const todayClass = isToday ? 'today' : '';
                                const weekendClass = isWeekend ? 'weekend' : '';

                                let cellHtml = `
                        <div class="calendar-day p-2 border border-gray-200 rounded ${todayClass} ${weekendClass}">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-sm font-medium ${isToday ? 'text-blue-600' : (isWeekend ? 'text-red-600' : 'text-gray-700')}">${day}</span>
                                ${isToday ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Today</span>' : ''}
                            </div>
                            <div class="mt-8 text-center">
                                <span class="text-xs text-gray-400">No leaves</span>
                            </div>
                        </div>
                    `;
                                $('#calendarGrid').append(cellHtml);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#calendarLoading').addClass('hidden');
                        $('#calendarEmpty').removeClass('hidden');
                        console.error('Error loading calendar:', error);
                    }
                });
            }

            // Function to show leave details
            function showLeaveDetails(leaveId) {
                $.ajax({
                    url: '{{ route('hrm.leave.details') }}',
                    method: 'GET',
                    data: {
                        id: leaveId
                    },
                    success: function(response) {
                        if (response.success) {
                            const leave = response.data;
                            const startDate = new Date(leave.start_date).toLocaleDateString();
                            const endDate = new Date(leave.end_date).toLocaleDateString();
                            const duration = leave.total_days;
                            const bgColorClass = getLeaveTypeColor(leave.leave_type.name);

                            let modalHtml = `
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                    <span class="text-lg font-semibold text-gray-600">
                                        ${leave.employee.full_name.split(' ').map(n => n[0]).join('')}
                                    </span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">${leave.employee.full_name}</h4>
                                    <p class="text-sm text-gray-600">${leave.employee.department?.department || 'No Department'}</p>
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-1 text-xs rounded border ${bgColorClass}">
                                        ${leave.leave_type.name}
                                    </span>
                                    ${leave.half ? `<span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded border border-orange-200">${leave.half === 'first_half' ? 'First Half' : 'Second Half'}</span>` : ''}
                                    ${leave.early_leave_time ? `<span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded border border-purple-200">Early Leave: ${leave.early_leave_time}</span>` : ''}
                                </div>

                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <label class="text-xs text-gray-500">Start Date</label>
                                        <p class="text-sm font-medium">${startDate}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">End Date</label>
                                        <p class="text-sm font-medium">${endDate}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">Duration</label>
                                        <p class="text-sm font-medium">${duration} day${duration > 1 ? 's' : ''}</p>
                                    </div>
                                     <div>
                                        <label class="text-xs text-gray-500">Status</label>
                                        <p class="mt-1">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium ${
                                                leave.status === 'Approved' ? 'bg-green-100 text-green-800' :
                                                leave.status === 'Rejected' ? 'bg-red-100 text-red-800' :
                                                'bg-yellow-100 text-yellow-800'
                                            }">
                                                ${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}
                                            </span>
                                        </p>
                                    </div>

                                </div>

                                ${leave.adjustment_reason ? `
                                                                <div class="mt-3">
                                                                    <label class="text-xs text-gray-500">Reason / Remarks</label>
                                                                    <p class="text-sm text-gray-700 mt-1">${leave.adjustment_reason}</p>
                                                                </div>
                                                            ` : ''}

                                ${leave.file_upload ? `
                                                                <div class="mt-3">
                                                                    <label class="text-xs text-gray-500">Supporting Document</label>
                                                                    <div class="mt-1">
                                                                        <a href="${leave.file_url}" target="_blank" class="text-sm text-[#0078C8] hover:underline flex items-center gap-1">
                                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                                            </svg>
                                                                            View Document
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            ` : ''}
                            </div>
                        </div>
                    `;

                            $('#modalContent').html(modalHtml);
                            $('#leaveDetailsModal').removeClass('hidden');
                        }
                    }
                });
            }

            // Event Listeners
            $('#prevMonth').on('click', function() {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                loadCalendar(currentYear, currentMonth);
            });

            $('#nextMonth').on('click', function() {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                loadCalendar(currentYear, currentMonth);
            });

            $('#todayBtn').on('click', function() {
                currentMonth = new Date().getMonth();
                currentYear = new Date().getFullYear();
                loadCalendar(currentYear, currentMonth);
            });

            $('#departmentFilter, #leaveTypeFilter').on('change', function() {
                loadCalendar(currentYear, currentMonth);
            });

            $('#clearFilters').on('click', function() {
                $('#departmentFilter').val('');
                $('#leaveTypeFilter').val('');
                loadCalendar(currentYear, currentMonth);
            });

            $('#closeModal').on('click', function() {
                $('#leaveDetailsModal').addClass('hidden');
            });

            // Close modal when clicking outside
            $('#leaveDetailsModal').on('click', function(e) {
                if (e.target === this) {
                    $(this).addClass('hidden');
                }
            });

            // Initialize calendar
            loadCalendar(currentYear, currentMonth);
        });
    </script>
@endpush
