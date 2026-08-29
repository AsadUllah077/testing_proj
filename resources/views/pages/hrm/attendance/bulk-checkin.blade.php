{{-- resources/views/attendance/bulk-checkin.blade.php --}}
@extends('layouts.auth')

@section('title', 'Bulk Check-in')

@section('content')
<div class="mt-4">
    <div class="mx-auto px-3 max-w-4xl">
        <div class="overflow-hidden">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <h2 class="text-stone-950 text-xl font-normal">Bulk Check-in</h2>
                <p class="text-gray-600 text-sm mt-1">Check-in multiple employees at once</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <form method="POST" action="{{ route('attendance.bulk.checkin') }}" class="p-6">
                    @csrf

                    <div class="space-y-6">
                        <!-- Date Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Attendance Date *</label>
                            <input type="date" name="attendance_date" value="{{ date('Y-m-d') }}" required
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all date">
                        </div>

                        <!-- Check-in Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Check-in Time *</label>
                            <input type="time" name="check_in_time" value="{{ date('H:i') }}" required
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <!-- Employee Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Employees *</label>
                            <select name="employee_ids[]" multiple required
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->full_name }} ({{ $employee->employee_id ?? 'N/A' }}) - {{ $employee->department->department ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                        <a href="{{ route('attendance.index') }}"
                            class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">
                            Bulk Check-in
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select Employees",
            allowClear: true
        });
    });
</script>
@endpush
