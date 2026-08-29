@extends('layouts.auth')

@section('title', 'Edit Attendance Record')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                    <h2 class="text-stone-950 text-xl font-normal">Edit Attendance Record</h2>
                    <p class="text-gray-600 text-sm mt-1">Update employee attendance information</p>
                </div>

                <!-- Form Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <form method="POST" action_url="{{ route('hrm.attendance.update', $attendance->id) }}"
                        back_url="{{ get_index_url('hrm.attendance.index') }}" class="p-6" id="formSubmit">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Employee Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                                <select name="employee_id" required
                                    class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    <option value="">Select Employee</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ old('employee_id', $attendance->employee_id) == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->full_name }} - {{ $employee->department->department ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Attendance Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Attendance Date *</label>
                                <div class="relative">
                                    <input class="date" type="date" name="attendance_date" id="attendance_date"
                                        value="{{ $attendance->attendance_date ? \Carbon\Carbon::parse($attendance->attendance_date)->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d') }}"
                                        required
                                        class="date w-full py-2.5 pl-10 pr-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                @error('attendance_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Format: YYYY-MM-DD (e.g., 2024-12-24)</p>
                            </div>

                            <!-- Check In Time -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Check In Time</label>
                                <input type="time" name="check_in"
                                    value="{{ old('check_in', $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '') }}"
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                @error('check_in')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Check Out Time -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Check Out Time</label>
                                <input type="time" name="check_out"
                                    value="{{ old('check_out', $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '') }}"
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                @error('check_out')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Status Summary -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Current Status</h3>
                            @php
                                $status = 'Absent';
                                $statusColor = 'bg-gray-100 text-gray-800';
                                $iconColor = 'text-gray-500';
                                $delay = null;

                                if ($attendance->check_in) {
                                    $shiftStartTime = $attendance->start_time ?? '09:00:00';
                                    $shiftEndTime = $attendance->end_time ?? $shiftStartTime;
                                    $checkIn = \Carbon\Carbon::parse($attendance->check_in);
                                    $checkInMinutes = $checkIn->hour * 60 + $checkIn->minute;
                                    $sParts = explode(':', $shiftStartTime);
                                    $eParts = explode(':', $shiftEndTime);
                                    $startMinutes =
                                        (isset($sParts[0]) ? (int) $sParts[0] : 0) * 60 +
                                        (isset($sParts[1]) ? (int) $sParts[1] : 0);
                                    $endMinutes =
                                        (isset($eParts[0]) ? (int) $eParts[0] : 0) * 60 +
                                        (isset($eParts[1]) ? (int) $eParts[1] : 0);
                                    $overnight = $endMinutes < $startMinutes;
                                    if ($overnight && $checkInMinutes < $startMinutes) {
                                        $checkInMinutes += 1440;
                                    }
                                    $graceMinutes = $startMinutes + 15;
                                    if ($checkInMinutes > $graceMinutes) {
                                        $status = 'Late';
                                        $statusColor = 'bg-yellow-100 text-yellow-800';
                                        $iconColor = 'text-yellow-500';
                                        $delay = $checkInMinutes - $graceMinutes;
                                    } else {
                                        $status = 'Present';
                                        $statusColor = 'bg-green-100 text-green-800';
                                        $iconColor = 'text-green-500';
                                    }
                                }
                            @endphp
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-full {{ $statusColor }}">
                                    @if ($status == 'Present')
                                        <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @elseif($status == 'Late')
                                        <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <p
                                        class="text-lg font-semibold {{ strtolower($status) == 'present' ? 'text-green-800' : (strtolower($status) == 'late' ? 'text-yellow-800' : 'text-gray-800') }}">
                                        {{ $status }}
                                    </p>
                                    @if ($delay)
                                        <p class="text-sm text-gray-600">Delayed by {{ $delay }} minutes</p>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-2">Working Type *</label>
                                <div class="flex items-center space-x-6">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="working_type" value="on-site"
                                            class="form-radio text-[#0078C8] focus:ring-[#0078C8]"
                                            {{ old('working_type', $attendance->working_type) == 'on-site' || old('working_type', $attendance->working_type) == 'onsite' ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">On-site</span>
                                    </label>

                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="working_type" value="remote"
                                            class="form-radio text-[#0078C8] focus:ring-[#0078C8]"
                                            {{ old('working_type', $attendance->working_type) == 'remote' ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">Remote</span>
                                    </label>
                                </div>
                                <div id="working_type_error" class="text-red-500 text-xs mt-1"></div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                            <a href="{{ get_index_url('hrm.attendance.index') }}"
                                class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">
                                Update Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script>
        $(document).ready(function() {

            // Initialize datepicker with DD/MM/YYYY format
            // $('#attendance_date').datepicker({
            //     dateFormat: 'dd/mm/yy',
            //     changeMonth: true,
            //     changeYear: true,
            //     yearRange: 'c-10:c+10',
            //     showButtonPanel: true,
            //     currentText: "Today",
            //     closeText: "Close",
            //     prevText: "Prev",
            //     nextText: "Next",
            //     onSelect: function(dateText, inst) {
            //         // Format validation
            //         const dateRegex = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/;
            //         if (!dateRegex.test(dateText)) {
            //             $(this).val('');
            //             alert('Please enter date in DD/MM/YYYY format');
            //         }
            //     }
            // });

            // // Add date format mask
            // $('#attendance_date').on('input', function() {
            //     let value = $(this).val().replace(/\D/g, '');

            //     if (value.length > 2) {
            //         value = value.substring(0, 2) + '/' + value.substring(2);
            //     }
            //     if (value.length > 5) {
            //         value = value.substring(0, 5) + '/' + value.substring(5, 9);
            //     }

            //     $(this).val(value);
            // });

            // // Form validation for date format
            // $('#attendanceForm').on('submit', function(e) {
            //     const dateInput = $('#attendance_date').val();
            //     const dateRegex = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/;

            //     if (!dateRegex.test(dateInput)) {
            //         e.preventDefault();
            //         alert('Please enter a valid date in DD/MM/YYYY format');
            //         $('#attendance_date').focus();
            //         return false;
            //     }

            //     // Convert date to YYYY-MM-DD for backend
            //     const parts = dateInput.split('/');
            //     const formattedDate = parts[2] + '-' + parts[1] + '-' + parts[0];

            //     // Create a hidden input with formatted date
            //     $('<input>').attr({
            //         type: 'hidden',
            //         name: 'formatted_date',
            //         value: formattedDate
            //     }).appendTo(this);

            //     return true;
            // });
        });
    </script>
@endpush
