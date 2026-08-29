@extends('layouts.auth')

@section('title', 'Create Leave')

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

                    <!-- Header -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6 mb-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-black">Manual Leave Adjustment</h2>
                            <a href="{{ get_index_url('hrm.leave.index') }}"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Back
                            </a>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6">
                        <form class="space-y-8" method="post" action_url="{{ route('hrm.leave.store') }}"
                            back_url="{{ get_index_url('hrm.leave.index') }}" enctype="multipart/form-data" id="formSubmit">
                            @csrf

                            <!-- Employee Selection -->
                            <div>
                                <h3 class="text-md font-medium text-gray-800 mb-4">1. Select Employee</h3>
                                <div class="grid md:grid-cols-1 lg:grid-cols-3 gap-6">
                                    <!-- Employee Selection Column -->
                                    <div class="lg:col-span-1">
                                        <label class="block text-sm text-gray-600 mb-1">Employee *</label>
                                        <select name="employee_id" id="employee_id" required
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                            <option value="">Select Employee</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}"
                                                    {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                                    {{ $employee->full_name }} - {{ $employee->department->department ?? 'N/A' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div id="employee_id_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <!-- Employee Leave Balance Box -->
                                    <div class="lg:col-span-2">
                                        <label class="block text-sm text-gray-600 mb-1">Employee Leave </label>
                                        <div id="employee_balance_box"
                                            class="hidden bg-white border border-gray-200 rounded-lg p-4 -sm">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-sm font-medium text-gray-700">Available Leave </h4>
                                                <span id="balance_year" class="text-xs text-gray-500"></span>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                                <!-- Annual Leave -->
                                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-xs font-medium text-blue-700">Annual</span>
                                                        <svg class="w-4 h-4 text-blue-500" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <div class="text-center">
                                                        <div id="annual_balance" class="text-lg font-bold text-blue-700">-
                                                        </div>
                                                        <div class="text-xs text-blue-600">days remaining</div>
                                                    </div>
                                                </div>

                                                <!-- Sick Leave -->
                                                <div class="bg-green-50 border border-green-100 rounded-lg p-3">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-xs font-medium text-green-700">Sick</span>
                                                        <svg class="w-4 h-4 text-green-500" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <div class="text-center">
                                                        <div id="sick_balance" class="text-lg font-bold text-green-700">-
                                                        </div>
                                                        <div class="text-xs text-green-600">days remaining</div>
                                                    </div>
                                                </div>

                                                <!-- Casual Leave -->
                                                <div class="bg-purple-50 border border-purple-100 rounded-lg p-3">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-xs font-medium text-purple-700">Casual</span>
                                                        <svg class="w-4 h-4 text-purple-500" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                    </div>
                                                    <div class="text-center">
                                                        <div id="casual_balance" class="text-lg font-bold text-purple-700">-
                                                        </div>
                                                        <div class="text-xs text-purple-600">days remaining</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Loading State -->
                                            <div id="balance_loading" class="hidden text-center py-4">
                                                <div class="inline-flex items-center">
                                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-[#0078C8]"
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                    <span class="text-sm text-gray-600">Loading leave balances...</span>
                                                </div>
                                            </div>

                                            <!-- Error State -->
                                            <div id="balance_error" class="hidden text-center py-4">
                                                <div class="inline-flex items-center text-amber-600">
                                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="text-sm">Unable to load leave balances</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- No Employee Selected -->
                                        <div id="no_employee_selected"
                                            class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                                            <svg class="w-10 h-10 text-gray-400 mx-auto mb-3" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <p class="text-sm text-gray-500">Select an employee to view their leave balance
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Leave Details -->
                            <div>
                                <h3 class="text-md font-medium text-gray-800 mb-4">2. Leave Details</h3>
                                <div class="grid md:grid-cols-4 gap-6 mb-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Leave Type *</label>
                                        <select name="leave_type_id" id="leave_type_id" required
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                            <option value="">Select Leave Type</option>
                                            @foreach ($leaveTypes as $leaveType)
                                                <option value="{{ $leaveType->id }}"
                                                    {{ old('leave_type_id') == $leaveType->id ? 'selected' : '' }}>
                                                    {{ $leaveType->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div id="leave_type_id_error" class="text-red-500 text-xs mt-1"></div>
                                        {{-- <div id="balance_info" class="text-sm text-gray-500 mt-1 hidden"></div> --}}
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Start Date *</label>
                                        <input type="date" name="start_date" id="start_date"
                                            value="{{ old('start_date') }}" required
                                            class="date w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <div id="start_date_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">End Date *</label>
                                        <input type="date" name="end_date" id="end_date"
                                            value="{{ old('end_date') }}" required
                                            class="date w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <div id="end_date_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div class="flex justify-start items-center gap-4 mt-5">
                                        <label class=" text-sm text-gray-600 mb-1">Duration</label>
                                        <div id="duration_info" class="text-sm text-gray-600 ">
                                            <span id="calculated_days"
                                                class="px-2 py-1 bg-blue-50 text-blue-700 rounded ">0 days</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Half Day & Early Leave Options -->
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center space-x-4">
                                            <label></label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="half" value="first_half"
                                                    class="form-radio text-[#0078C8]"
                                                    {{ old('half') == 'first_half' ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm">First Half</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="half" value="second_half"
                                                    class="form-radio text-[#0078C8]"
                                                    {{ old('half') == 'second_half' ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm">Second Half</span>
                                            </label>
                                        </div>
                                    </div>


                                </div>
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Early Leave Time</label>
                                        <input type="time" name="early_leave_time" id="early_leave_time"
                                            value="{{ old('early_leave_time') }}"
                                            class="w-[300] border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <div id="early_leave_time_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Adjustment Section -->
                            <div>
                                <h3 class="text-md font-medium text-gray-800 mb-4">3. Justification</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Reason / Remarks</label>
                                        <textarea name="adjustment_reason" id="adjustment_reason" rows="3"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            placeholder="Please provide a reason for this manual entry (e.g. System correction, Late submission)...">{{ old('adjustment_reason') }}</textarea>
                                        <div id="adjustment_reason_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Supporting Document
                                            (Optional)</label>
                                        <input type="file" name="file_upload" id="file_upload"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <div class="text-xs text-gray-500 mt-1">Max: 5MB. Allowed: PDF, JPG, PNG, DOC</div>
                                        <div id="file_upload_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                                <a href="{{ route('hrm.leave.index') }}"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                    CANCEL
                                </a>
                                <button type="submit" id="submitBtn"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                    SAVE
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Store leave type mappings for common leave types
            const leaveTypeMappings = {
                'annual': ['annual', 'vacation', 'annual leave', 'vacation leave'],
                'sick': ['sick', 'sick leave', 'medical', 'medical leave'],
                'casual': ['casual', 'casual leave', 'personal', 'personal leave']
            };

            // Store leave type categories (you'll need to add data-category to options)
            let leaveTypeCategories = {};

            // Initialize categories from options
            $('#leave_type_id option').each(function() {
                const id = $(this).val();
                const text = $(this).text().toLowerCase();
                let category = 'full_day'; // default

                // Determine category based on leave type name
                if (text.includes('half') || text.includes('half day') || text.includes('half-day')) {
                    category = 'half_day';
                } else if (text.includes('early') || text.includes('early leave')) {
                    category = 'early_leave';
                }

                leaveTypeCategories[id] = category;
            });

            // Function to update UI based on selected leave type
            function updateLeaveTypeUI() {
                const leaveTypeId = $('#leave_type_id').val();
                const category = leaveTypeCategories[leaveTypeId] || 'full_day';

                // Get the leave day options container
                const leaveDayOptions = $('input[name="half"]').closest('div').closest('div').closest('div');
                const earlyLeaveTime = $('#early_leave_time').closest('div').closest('div');

                // Reset all fields first
                $('input[name="half"]').prop('checked', false);
                $('#early_leave_time').val('');

                // Hide both sections initially
                leaveDayOptions.addClass('hidden');
                earlyLeaveTime.addClass('hidden');

                // Show appropriate section based on category
                switch (category) {
                    case 'half_day':
                        // Show half day options, hide early leave
                        leaveDayOptions.removeClass('hidden');
                        earlyLeaveTime.addClass('hidden');

                        // Set first half as default if nothing is selected
                        if (!$('input[name="half"]:checked').val()) {
                            $('input[name="half"][value="first_half"]').prop('checked', true);
                        }
                        break;

                    case 'early_leave':
                        // Show early leave time, hide half day options
                        leaveDayOptions.addClass('hidden');
                        earlyLeaveTime.removeClass('hidden');

                        // Set current time as default if empty
                        if (!$('#early_leave_time').val()) {
                            const now = new Date();
                            const timeString = now.getHours().toString().padStart(2, '0') + ':' +
                                now.getMinutes().toString().padStart(2, '0');
                            $('#early_leave_time').val(timeString);
                        }
                        break;

                    default:
                        // For full_day and other types, hide both
                        leaveDayOptions.addClass('hidden');
                        earlyLeaveTime.addClass('hidden');
                        break;
                }

                // Recalculate days after updating options
                calculateDays();
            }

            // Function to find leave type by name
            function findLeaveTypeIdByName(name) {
                const searchName = name.toLowerCase().trim();

                // Find matching leave type from options
                let foundId = null;
                $('#leave_type_id option').each(function() {
                    const optionName = $(this).text().toLowerCase().trim();
                    if (optionName === searchName || optionName.includes(searchName)) {
                        foundId = $(this).val();
                        return false; // Break the loop
                    }
                });

                return foundId;
            }

            // Function to get leave type ID for a category
            async function getLeaveTypeIdForCategory(category) {
                const searchTerms = leaveTypeMappings[category];

                for (const term of searchTerms) {
                    const id = findLeaveTypeIdByName(term);
                    if (id) return id;
                }

                // If not found by name, try to get from backend
                try {
                    const response = await $.ajax({
                        url: '{{ route('hrm.leave.get-leave-type-by-category') }}',
                        method: 'GET',
                        data: {
                            category: category
                        }
                    });
                    return response.id || null;
                } catch (error) {
                    console.error('Error fetching leave type:', error);
                    return null;
                }
            }

            // Calculate days on date change
            function calculateDays() {
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                const halfDay = $('input[name="half"]:checked').val();
                const leaveTypeId = $('#leave_type_id').val();
                const category = leaveTypeCategories[leaveTypeId] || 'full_day';

                if (!startDate || !endDate) {
                    $('#calculated_days').text('0 days');
                    return;
                }

                // For early leave, it's always 0.5 days for single day
                if (category === 'early_leave') {
                    if (startDate !== endDate) {
                        $('#calculated_days').html(
                            '<span class="text-red-500">Early leave is for single day only</span>');
                        return;
                    }
                    $('#calculated_days').text('0.5 days');
                    return;
                }

                // For half day and single day
                if (category === 'half_day' && startDate === endDate) {
                    $('#calculated_days').text('0.5 days');
                    return;
                }

                $.ajax({
                    url: '{{ route('hrm.leave.calculate-days') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        start_date: startDate,
                        end_date: endDate,
                        half: halfDay,
                        employee_id: $('#employee_id').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#calculated_days').text(response.total_days + ' days');
                        }
                    }
                });
            }

            // Get specific leave balance
            async function getLeaveBalance(employeeId, leaveTypeId, year) {
                if (!employeeId || !leaveTypeId) return null;

                try {
                    const response = await $.ajax({
                        url: '{{ route('hrm.leave.get-balance') }}',
                        method: 'GET',
                        data: {
                            employee_id: employeeId,
                            leave_type_id: leaveTypeId,
                            year: year
                        }
                    });

                    return response.success ? response.data : null;
                } catch (error) {
                    console.error('Error fetching leave balance:', error);
                    return null;
                }
            }

            // Load all employee leave balances
            async function loadEmployeeBalances() {
                const employeeId = $('#employee_id').val();
                const startDate = $('#start_date').val();

                if (!employeeId) {
                    // No employee selected
                    $('#employee_balance_box').addClass('hidden');
                    $('#no_employee_selected').removeClass('hidden');
                    return;
                }

                // Show loading state
                $('#no_employee_selected').addClass('hidden');
                $('#employee_balance_box').removeClass('hidden');
                $('#balance_loading').removeClass('hidden');
                $('#balance_error').addClass('hidden');

                // Hide balance cards temporarily
                $('.bg-blue-50, .bg-green-50, .bg-purple-50').addClass('opacity-50');

                // Get year from start date or current year
                const year = startDate ? new Date(startDate).getFullYear() : new Date().getFullYear();
                $('#balance_year').text(year);

                try {
                    // Get leave type IDs for each category
                    const [annualId, sickId, casualId] = await Promise.all([
                        getLeaveTypeIdForCategory('annual'),
                        getLeaveTypeIdForCategory('sick'),
                        getLeaveTypeIdForCategory('casual')
                    ]);

                    // Get balances for each leave type
                    const [annualBalance, sickBalance, casualBalance] = await Promise.all([
                        annualId ? getLeaveBalance(employeeId, annualId, year) : Promise.resolve(null),
                        sickId ? getLeaveBalance(employeeId, sickId, year) : Promise.resolve(null),
                        casualId ? getLeaveBalance(employeeId, casualId, year) : Promise.resolve(null)
                    ]);

                    // Update UI with balances
                    if (annualBalance) {
                        $('#annual_balance').text(annualBalance.remaining_leaves || '0');
                    } else {
                        $('#annual_balance').text('-');
                    }

                    if (sickBalance) {
                        $('#sick_balance').text(sickBalance.remaining_leaves || '0');
                    } else {
                        $('#sick_balance').text('-');
                    }

                    if (casualBalance) {
                        $('#casual_balance').text(casualBalance.remaining_leaves || '0');
                    } else {
                        $('#casual_balance').text('-');
                    }

                    // Hide loading, show success
                    $('#balance_loading').addClass('hidden');
                    $('.bg-blue-50, .bg-green-50, .bg-purple-50').removeClass('opacity-50');

                } catch (error) {
                    // Show error state
                    $('#balance_loading').addClass('hidden');
                    $('#balance_error').removeClass('hidden');
                    console.error('Error loading employee balances:', error);
                }
            }

            // Event Listeners
            $('#start_date, #end_date').on('change', function() {
                calculateDays();
                loadEmployeeBalances();
            });

            $('input[name="half"]').on('change', calculateDays);

            $('#early_leave_time').on('change', calculateDays);

            $('#employee_id').on('change', function() {
                loadEmployeeBalances();
            });

            $('#leave_type_id').on('change', function() {
                updateLeaveTypeUI();
                loadEmployeeBalances();
            });

            // Initialize on page load if employee is pre-selected
            const initialEmployee = $('#employee_id').val();
            if (initialEmployee) {
                loadEmployeeBalances();
            }

            // Initialize UI based on initial leave type selection
            updateLeaveTypeUI();

        });
    </script>
@endpush
