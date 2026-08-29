@extends('layouts.auth')

@section('title', 'Interview Calendar')

@section('content')
<div class="mt-4">
    <div class="p-3 mb-2">
        <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
            @foreach (config('modules.recruitment') as $module)
                <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                    active="{{ Request::routeIs($module['is_active']) }}" />
            @endforeach
        </div>
    </div>
    <div class="">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                <!-- Header with Filters -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg text-black mb-2">Interview Calendar</h2>
                            <p class="text-sm text-gray-600">View all scheduled interviews</p>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Job Filter -->
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Job</label>
                                <select id="jobFilter" class="select2 border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none w-full lg:w-48">
                                    <option value="">All Jobs</option>
                                    @foreach($jobs as $job)
                                        <option value="{{ $job->id }}">{{ $job->job_title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Interviewer Filter -->
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Interviewer</label>
                                <input type="text" id="interviewerFilter" placeholder="Search interviewer..."
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none w-full lg:w-48">
                            </div>

                            <!-- Clear Filters Button -->
                            <div class="mt-5">
                                <button id="clearFilters"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Month Navigation -->
                    <div class="flex items-center justify-between mt-6">
                        <div class="flex items-center gap-2">
                            <button id="prevMonth" class="p-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <h3 id="currentMonth" class="text-md font-semibold text-gray-700 min-w-[180px] text-center">Loading...</h3>
                            <button id="nextMonth" class="p-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Today Button -->
                        <button id="todayBtn" class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            Today
                        </button>
                    </div>

                    <!-- Legend -->

                </div>

                <!-- Calendar -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
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
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-[#0078C8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-600">Loading calendar...</span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div id="calendarEmpty" class="text-center py-8 hidden">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm text-gray-500">No interviews scheduled for selected filters</p>
                        <p class="text-xs text-gray-400 mt-1">Candidates will appear here when they're scheduled for interviews</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Interview Details Modal -->
<div id="interviewDetailsModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Interview Details</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="modalContent" class="overflow-y-auto max-h-[calc(90vh-100px)]">
                <!-- Interview details will be loaded here -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.calendar-day {
    min-height: 140px;
}
.calendar-day.today {
    background-color: #f0f9ff;
    border-color: #bae6fd;
}
.calendar-day.weekend {
    background-color: #f8fafc;
}
.interview-item {
    transition: all 0.2s ease;
}
.interview-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    // Function to get time-based color
    function getTimeBasedColor(interviewTime, location) {
        if (!interviewTime) return 'bg-gray-100 text-gray-800 border-gray-200';

        const hour = parseInt(interviewTime.split(':')[0]);
        let bgClass = 'bg-blue-100 text-blue-800 border-blue-200';

        // Time-based colors
        if (hour < 12) {
            bgClass = 'bg-blue-100 text-blue-800 border-blue-200'; // Morning
        } else if (hour < 17) {
            bgClass = 'bg-green-100 text-green-800 border-green-200'; // Afternoon
        } else {
            bgClass = 'bg-purple-100 text-purple-800 border-purple-200'; // Evening
        }

        // Location-based override
        if (location && location.toLowerCase().includes('virtual')) {
            bgClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
        } else if (location && (location.toLowerCase().includes('onsite') || location.toLowerCase().includes('office'))) {
            bgClass = 'bg-red-100 text-red-800 border-red-200';
        }

        return bgClass;
    }

    // Function to get time-based dot color
    function getTimeBasedDotColor(interviewTime, location) {
        if (!interviewTime) return 'bg-gray-500';

        const hour = parseInt(interviewTime.split(':')[0]);
        let dotClass = 'bg-blue-500';

        if (hour < 12) {
            dotClass = 'bg-blue-500';
        } else if (hour < 17) {
            dotClass = 'bg-green-500';
        } else {
            dotClass = 'bg-purple-500';
        }

        if (location && location.toLowerCase().includes('virtual')) {
            dotClass = 'bg-yellow-500';
        } else if (location && (location.toLowerCase().includes('onsite') || location.toLowerCase().includes('office'))) {
            dotClass = 'bg-red-500';
        }

        return dotClass;
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
        return date.toISOString().split('T')[0];
    }

    // Function to format time
    function formatTime(timeString) {
        if (!timeString) return '';
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const formattedHour = hour % 12 || 12;
        return `${formattedHour}:${minutes} ${ampm}`;
    }

    // Function to shorten name
    function shortenName(fullName) {
        const nameParts = fullName.split(' ');
        if (nameParts.length >= 2) {
            return nameParts[0] + ' ' + nameParts[1].charAt(0) + '.';
        }
        return fullName;
    }

    // Function to load calendar
    function loadCalendar(year, month) {
        $('#calendarGrid').empty();
        $('#calendarLoading').removeClass('hidden');
        $('#calendarEmpty').addClass('hidden');

        const daysInMonth = getDaysInMonth(year, month);
        const firstDay = getFirstDayOfMonth(year, month);
        const jobId = $('#jobFilter').val();
        const interviewer = $('#interviewerFilter').val();

        // Get the month name
        const monthNames = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
        $('#currentMonth').text(`${monthNames[month]} ${year}`);

        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            $('#calendarGrid').append('<div class="calendar-day p-2 border border-gray-100 bg-gray-50 rounded"></div>');
        }

        // Fetch interview data for the month
        $.ajax({
            url: '{{ route("hrm.candidates.calendar-data") }}',
            method: 'GET',
            data: {
                year: year,
                month: month + 1,
                job_id: jobId || '',
                interviewer: interviewer || ''
            },
            success: function(response) {
                $('#calendarLoading').addClass('hidden');

                if (response.success && response.data.length > 0) {
                    const interviewsByDate = {};

                    // Group interviews by date
                    response.data.forEach(interview => {
                        const dateStr = interview.interview_date;

                        if (!interviewsByDate[dateStr]) {
                            interviewsByDate[dateStr] = [];
                        }

                        interviewsByDate[dateStr].push(interview);
                    });

                    // Create calendar cells for each day of the month
                    for (let day = 1; day <= daysInMonth; day++) {
                        const currentDate = new Date(year, month, day);
                        const dateStr = formatDate(currentDate);
                        const isToday = currentDate.toDateString() === new Date().toDateString();
                        const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() === 6;

                        const todayClass = isToday ? 'today' : '';
                        const weekendClass = isWeekend ? 'weekend' : '';

                        let cellHtml = `
                            <div class="calendar-day p-2 border border-gray-200 rounded overflow-y-auto ${todayClass} ${weekendClass}">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-sm font-medium ${isToday ? 'text-blue-600' : (isWeekend ? 'text-red-600' : 'text-gray-700')}">${day}</span>
                                    ${isToday ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Today</span>' : ''}
                                </div>
                                <div class="space-y-1 mt-2" id="interviews-${dateStr}">
                        `;

                        // Add interviews for this date
                        if (interviewsByDate[dateStr]) {
                            // Sort interviews by time
                            const sortedInterviews = interviewsByDate[dateStr].sort((a, b) => {
                                return (a.interview_time || '').localeCompare(b.interview_time || '');
                            });

                            sortedInterviews.forEach((interview, index) => {
                                const shortName = shortenName(interview.candidate.full_name);
                                const jobTitle = interview.job.job_title || 'No Job';
                                const interviewTime = formatTime(interview.interview_time) || 'Time TBD';
                                const bgColorClass = getTimeBasedColor(interview.interview_time, interview.interview_location);
                                const dotColorClass = getTimeBasedDotColor(interview.interview_time, interview.interview_location);

                                cellHtml += `
                                    <div class="cursor-pointer interview-item text-xs p-1.5 rounded border ${bgColorClass} hover:opacity-90"
                                         data-interview-id="${interview.id}"
                                         title="${interview.candidate.full_name} - ${jobTitle} - ${interviewTime}">
                                        <div class="flex items-center justify-between gap-1">
                                            <div class="flex items-center gap-1">
                                                <div class="w-1.5 h-1.5 ${dotColorClass} rounded-full"></div>
                                                <span class="font-medium truncate">${shortName}</span>
                                            </div>
                                            <span class="text-xs opacity-75">${interviewTime.split(' ')[0]}</span>
                                        </div>
                                        <div class="truncate text-xs mt-0.5 opacity-90">${jobTitle}</div>
                                    </div>
                                `;
                            });
                        }

                        cellHtml += '</div></div>';
                        $('#calendarGrid').append(cellHtml);
                    }

                    // Add click handlers to interview items
                    $('.interview-item').on('click', function() {
                        const interviewId = $(this).data('interview-id');
                        showInterviewDetails(interviewId);
                    });

                } else {
                    // No interviews found
                    for (let day = 1; day <= daysInMonth; day++) {
                        const currentDate = new Date(year, month, day);
                        const isToday = currentDate.toDateString() === new Date().toDateString();
                        const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() === 6;

                        const todayClass = isToday ? 'today' : '';
                        const weekendClass = isWeekend ? 'weekend' : '';

                        let cellHtml = `
                            <div class="calendar-day p-2 border border-gray-200 rounded ${todayClass} ${weekendClass}">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-sm font-medium ${isToday ? 'text-blue-600' : (isWeekend ? 'text-red-600' : 'text-gray-700')}">${day}</span>
                                    ${isToday ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Today</span>' : ''}
                                </div>
                                <div class="mt-10 text-center">
                                    <span class="text-xs text-gray-400">No interviews</span>
                                </div>
                            </div>
                        `;
                        $('#calendarGrid').append(cellHtml);
                    }

                    if (response.data.length === 0) {
                        $('#calendarEmpty').removeClass('hidden');
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

    // Function to show interview details
    function showInterviewDetails(interviewId) {
        $.ajax({
            url: '{{ route("hrm.candidates.interview-details") }}',
            method: 'GET',
            data: { id: interviewId },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const interview = data.interview_details;
                    const candidate = data.candidate;
                    const job = data.job;
                    const resume = data.resume;

                    const interviewDate = interview.interview_date ? new Date(interview.interview_date).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    }) : 'Not scheduled';

                    let modalHtml = `
                        <div class="space-y-4">
                            <!-- Candidate Info -->
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-lg font-semibold text-blue-600">
                                        ${candidate.first_name[0]}${candidate.last_name[0]}
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800">${candidate.full_name}</h4>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${candidate.stage === 'interviewing' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'}">
                                            ${candidate.stage.charAt(0).toUpperCase() + candidate.stage.slice(1)}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Job Info -->
                            <div class="bg-gray-50 p-3 rounded-md">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    <h5 class="text-sm font-medium text-gray-700">Job Position</h5>
                                </div>
                                <div class="ml-6">
                                    <p class="text-sm font-medium text-gray-800">${job.job_title}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-600">ID: ${job.job_id}</span>
                                        <span class="text-xs text-gray-600">•</span>
                                        <span class="text-xs text-gray-600">${job.location}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Interview Details -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Interview Date</label>
                                    <p class="text-sm font-medium text-gray-800">${interviewDate}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Interview Time</label>
                                    <p class="text-sm font-medium text-gray-800">${interview.formatted_interview_time || 'Not specified'}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Interviewer</label>
                                    <p class="text-sm font-medium text-gray-800">${interview.interviewer || 'Not assigned'}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Location</label>
                                    <p class="text-sm font-medium text-gray-800">${interview.interview_location || 'Not specified'}</p>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="border-t pt-4">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Contact Information</h5>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                        </svg>
                                        <a href="mailto:${candidate.email}" class="text-sm text-blue-600 hover:underline">${candidate.email}</a>
                                    </div>
                                    ${candidate.phone ? `
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                            </svg>
                                            <a href="tel:${candidate.phone}" class="text-sm text-blue-600 hover:underline">${candidate.phone}</a>
                                        </div>
                                    ` : ''}
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">Applied: ${candidate.applied_date || 'Not specified'}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">Source: ${candidate.source || 'Not specified'}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            ${candidate.notes || interview.interview_notes ? `
                                <div class="border-t pt-4">
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">Notes</h5>
                                    <div class="bg-gray-50 p-3 rounded-md text-sm text-gray-700">
                                        ${candidate.notes || ''}
                                        ${candidate.notes && interview.interview_notes ? '<br><br>' : ''}
                                        ${interview.interview_notes || ''}
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Actions -->
                            <div class="border-t pt-4 flex gap-2">
                                <a href="{{ url('hrm/candidates') }}/${interviewId}"
                                   class="flex-1 text-center px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#0078C8]">
                                    View Full Profile
                                </a>
                                ${resume.url ? `
                                    <a href="${resume.url}" target="_blank"
                                       class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                        View Resume
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    `;

                    $('#modalContent').html(modalHtml);
                    $('#interviewDetailsModal').removeClass('hidden');
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

    $('#jobFilter, #interviewerFilter').on('change', function() {
        loadCalendar(currentYear, currentMonth);
    });

    // Add keyup event for interviewer filter
    $('#interviewerFilter').on('keyup', function() {
        clearTimeout($(this).data('timeout'));
        $(this).data('timeout', setTimeout(function() {
            loadCalendar(currentYear, currentMonth);
        }, 500));
    });

    $('#clearFilters').on('click', function() {
        $('#jobFilter').val('');
        $('#interviewerFilter').val('');
        loadCalendar(currentYear, currentMonth);
    });

    $('#closeModal').on('click', function() {
        $('#interviewDetailsModal').addClass('hidden');
    });

    // Close modal when clicking outside
    $('#interviewDetailsModal').on('click', function(e) {
        if (e.target === this) {
            $(this).addClass('hidden');
        }
    });

    // Initialize calendar
    loadCalendar(currentYear, currentMonth);
});
</script>
@endpush
