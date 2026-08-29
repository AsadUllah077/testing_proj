@extends('layouts.auth')

@section('title', 'Leave Details')

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
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-black">Leave Details</h2>
                            <a href="{{ get_index_url('hrm.leave.index') }}"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Back to List
                            </a>
                        </div>
                    </div>

                    <!-- Details Card -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
                        <div class="space-y-6">
                            <!-- Employee Leave Balance Overview -->
                            <div>
                                <h3 class="text-md font-medium text-gray-800 mb-4 border-b pb-2">Employee Leave Balance
                                    Summary ({{ \Carbon\Carbon::parse($leaveRecord->start_date)->year }})</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                    @php
                                        $employeeId = $leaveRecord->employee_id;
                                        $year = \Carbon\Carbon::parse($leaveRecord->start_date)->year;
                                        $leaveTypes = \App\Models\LeaveType::where(
                                            'company_id',
                                            Auth::user()->company_id,
                                        )->get();

                                        // Get all leave balances for this employee and year
                                        $leaveBalances = \App\Models\LeaveBalance::where('employee_id', $employeeId)
                                            ->where('year', $year)
                                            ->with('leaveType')
                                            ->get()
                                            ->keyBy('leave_type_id');
                                    @endphp

                                    @foreach ($leaveTypes as $leaveType)
                                        @php
                                            $balance = $leaveBalances->get($leaveType->id);
                                            $totalLeaves = $balance ? $balance->total_leaves : $leaveType->count ?? 0;
                                            $usedLeaves = $balance ? $balance->used_leaves : 0;
                                            $remainingLeaves = $balance ? $balance->remaining_leaves : $totalLeaves;

                                            // Calculate percentage used
                                            $usedPercentage = $totalLeaves > 0 ? ($usedLeaves / $totalLeaves) * 100 : 0;

                                            // Set color based on usage
                                            if ($usedPercentage >= 90) {
                                                $colorClass = 'bg-red-50 border-red-200';
                                                $textColor = 'text-red-700';
                                                $progressColor = 'bg-red-500';
                                            } elseif ($usedPercentage >= 70) {
                                                $colorClass = 'bg-amber-50 border-amber-200';
                                                $textColor = 'text-amber-700';
                                                $progressColor = 'bg-amber-500';
                                            } elseif ($usedPercentage >= 50) {
                                                $colorClass = 'bg-yellow-50 border-yellow-200';
                                                $textColor = 'text-yellow-700';
                                                $progressColor = 'bg-yellow-500';
                                            } else {
                                                $colorClass = 'bg-green-50 border-green-200';
                                                $textColor = 'text-green-700';
                                                $progressColor = 'bg-green-500';
                                            }
                                        @endphp

                                        <div class="{{ $colorClass }} border rounded-lg p-4">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <h4 class="text-sm font-medium {{ $textColor }}">
                                                        {{ $leaveType->name }}</h4>
                                                    <p class="text-xs text-gray-500">{{ $leaveType->count ?? 0 }} days
                                                        {{ $leaveType->per_label ?? 'per year' }}</p>
                                                </div>
                                                @if (!$balance)
                                                    <span
                                                        class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Default</span>
                                                @endif
                                            </div>

                                            <div class="mb-2">
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span class="text-gray-600">Used: {{ $usedLeaves }} days</span>
                                                    <span class="{{ $textColor }} font-medium">Remaining:
                                                        {{ $remainingLeaves }} days</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full {{ $progressColor }}"
                                                        style="width: {{ min($usedPercentage, 100) }}%"></div>
                                                </div>
                                            </div>

                                            <div class="text-center">
                                                <div class="text-lg font-bold {{ $textColor }}">
                                                    {{ $remainingLeaves }}/{{ $totalLeaves }}</div>
                                                <div class="text-xs text-gray-500">Days Available</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Current Leave Information -->
                            <div>
                                <h3 class="text-md font-medium text-gray-800 mb-4 border-b pb-2">Current Leave Information
                                </h3>
                                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm text-gray-500 mb-1">Employee</label>
                                        <p class="font-medium text-gray-800">{{ $leaveRecord->employee->full_name }}</p>
                                        <p class="text-sm text-gray-500">{{ $leaveRecord->employee->employee_id }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-500 mb-1">Leave Type</label>
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            {{ $leaveRecord->leaveType->name }}
                                            @php
                                                $currentBalance = $leaveBalances->get($leaveRecord->leave_type_id);
                                                if ($currentBalance) {
                                                    echo ' (' . $currentBalance->remaining_leaves . ' days remaining)';
                                                }
                                            @endphp
                                        </span>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-500 mb-1">Status</label>
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'approved' => 'bg-green-100 text-green-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'manually_adjusted' => 'bg-purple-100 text-purple-800',
                                            ];
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$leaveRecord->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst(str_replace('_', ' ', $leaveRecord->status)) }}
                                        </span>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-500 mb-1">Start Date</label>
                                        <p class="font-medium text-gray-800">
                                            {{ $leaveRecord->start_date->format('d M, Y') }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-500 mb-1">End Date</label>
                                        <p class="font-medium text-gray-800">{{ $leaveRecord->end_date->format('d M, Y') }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-500 mb-1">Total Days</label>
                                        <p class="font-medium text-gray-800">{{ $totalDays }} days</p>
                                    </div>

                                    @if ($leaveRecord->half)
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Half Day</label>
                                            <p class="font-medium text-gray-800">
                                                {{ $leaveRecord->half === 'first_half' ? 'First Half' : 'Second Half' }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($leaveRecord->early_leave_time)
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Early Leave Time</label>
                                            <p class="font-medium text-gray-800">
                                                {{ date('h:i A', strtotime($leaveRecord->early_leave_time)) }}
                                            </p>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-sm text-gray-500 mb-1">Created By</label>
                                        <p class="font-medium text-gray-800">
                                            {{ $leaveRecord->createdBy?->first_name . ' ' . $leaveRecord->createdBy?->last_name ?? 'System' }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ $leaveRecord->created_at->format('d M, Y h:i A') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Adjustment Info -->
                            @if ($leaveRecord->adjustment_reason)
                                <div>
                                    <h3 class="text-md font-medium text-gray-800 mb-4 border-b pb-2">Manual Reason</h3>
                                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <div>
                                                {{-- <h4 class="font-medium text-amber-800 mb-1">Manual Adjustment Applied</h4> --}}
                                                <p class="text-amber-700 text-sm">{{ $leaveRecord->adjustment_reason }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Supporting Document -->
                            @if ($leaveRecord->file_upload)
                                <div>
                                    <h3 class="text-md font-medium text-gray-800 mb-4 border-b pb-2">Supporting Document
                                    </h3>
                                    <div class="flex items-center gap-3">
                                        <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <div>
                                            <a href="{{ Storage::url($leaveRecord->file_upload) }}" target="_blank"
                                                class="text-[#0078C8] hover:underline font-medium">
                                                View Document
                                            </a>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Uploaded on {{ $leaveRecord->created_at->format('d M, Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Action Buttons -->
                            <div class="flex justify-end gap-2 pt-6 border-t border-gray-100">
                                <a href="{{ get_index_url('hrm.leave.index') }}"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                    BACK
                                </a>
                                @if (auth()->user()->can('edit-leave'))
                                    <a href="{{ route('hrm.leave.edit', $leaveRecord->id) }}"
                                        class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                        EDIT
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
