@extends('layouts.auth')

@section('title', 'Attendance Details')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Attendance Details</h2>
                        <p class="text-gray-600 text-sm mt-1">View employee attendance information</p>
                    </div>
                    <div class="flex gap-2 mt-2 md:mt-0">
                        <a href="{{ route('hrm.attendance.edit', $attendance->id) }}"
                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </a>
                        <a href="{{ get_index_url('hrm.attendance.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-xs font-normal transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back
                        </a>
                    </div>
                </div>

                <!-- Details Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Employee Info Section -->
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center gap-4">
                                @if ($attendance->employee->profile_picture)
                                    <img src="{{ Storage::url($attendance->employee->profile_picture) }}"
                                        alt="{{ $attendance->employee->full_name }}"
                                        class="h-16 w-16 rounded-full object-cover border border-gray-200">
                                @else
                                    <div
                                        class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center border border-gray-200">
                                        <span class="text-xl font-semibold text-blue-600">
                                            {{ substr($attendance->employee->full_name, 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $attendance->employee->full_name }}
                                    </h4>
                                    <p class="text-sm text-gray-600">{{ $attendance->employee->employee_id ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-600">{{ $attendance->employee->email ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Department:</span>
                                    <span class="font-medium text-gray-900">
                                        {{ $attendance->employee->department->department ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Designation:</span>
                                    <span class="font-medium text-gray-900">
                                        {{ $attendance->employee->designation ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Status:</span>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                                        {{ $attendance->employee->status == 'active'
                                                                            ? 'bg-green-100 text-green-800'
                                                                            : ($attendance->employee->status == 'inactive'
                                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                                : ($attendance->employee->status == 'terminated'
                                                                                    ? 'bg-red-100 text-red-800'
                                                                                    : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($attendance->employee->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Details Section -->
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Attendance Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Attendance Date</label>
                                    <p class="text-lg font-semibold text-gray-900">
                                        {{ $attendance->attendance_date->format('F d, Y') }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $attendance->attendance_date->format('l') }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Check In</label>
                                        <p
                                            class="text-lg font-semibold {{ $attendance->check_in ? 'text-gray-900' : 'text-gray-500' }}">
                                            {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') : '---' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Check Out</label>
                                        <p
                                            class="text-lg font-semibold {{ $attendance->check_out ? 'text-gray-900' : 'text-gray-500' }}">
                                            {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') : '---' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <!-- Status Card -->
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Attendance Status</h4>
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
                                                <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @elseif($status == 'Late')
                                                <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor"
                                                    viewBox="0 0 20 20">
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
                                </div>

                                <!-- Work Hours Card -->
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Work Hours</h4>
                                    @php
                                        $displayHours = '0h 00m';

                                        if ($attendance->check_in && $attendance->check_out) {
                                            $checkIn = \Carbon\Carbon::parse($attendance->check_in);
                                            $checkOut = \Carbon\Carbon::parse($attendance->check_out);

                                            // Handle overnight shifts: if checkout is before check-in, add a day
                                            if ($checkOut->lt($checkIn)) {
                                                $checkOut->addDay();
                                            }

                                            // Calculate total minutes worked - NO BREAK DEDUCTION
                                            $totalMinutes = $checkIn->diffInMinutes($checkOut);

                                            // Convert to hours and minutes
                                            $hours = floor($totalMinutes / 60);
                                            $minutes = $totalMinutes % 60;
                                            $displayHours =
                                                $hours . 'h ' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . 'm';
                                        }
                                    @endphp
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ $displayHours }}
                                    </p>
                                    @if ($attendance->check_in && $attendance->check_out)
                                        @php
                                            $checkIn = \Carbon\Carbon::parse($attendance->check_in);
                                            $checkOut = \Carbon\Carbon::parse($attendance->check_out);
                                            if ($checkOut->lt($checkIn)) {
                                                $checkOut->addDay();
                                            }
                                        @endphp
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ $checkIn->format('h:i A') }} - {{ $checkOut->format('h:i A') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Meta Information -->
                    <div class="p-6 bg-gray-50 border-t border-gray-200">
                        <h4 class="text-sm font-bold text-gray-700 mb-3">Record Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Created:</span>
                                <span class="font-medium text-gray-900">
                                    {{ $attendance->created_at->format('M d, Y h:i A') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Last Updated:</span>
                                <span class="font-medium text-gray-900">
                                    {{ $attendance->updated_at->format('M d, Y h:i A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
