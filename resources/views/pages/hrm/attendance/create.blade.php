@extends('layouts.auth')

@section('title', 'Add Attendance Record')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 mb-4 flex justify-between items-center ">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Add Attendance Record</h2>
                        <p class="text-gray-600 text-sm mt-1">Record employee attendance for a specific date</p>
                    </div>

                    <a href="{{ route('hrm.attendance.index') }}"
                        class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">
                        Back
                    </a>
                </div>
                <!-- Import/Export Section -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-3">Bulk Import Attendance</h3>
                    <p class="text-sm text-gray-600 mb-4">Import multiple attendance records from an Excel file.</p>

                    <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                        <!-- Download Template -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-50 rounded-lg">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Download Template</h4>
                                    <p class="text-sm text-gray-600">Download Excel template with proper format</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('hrm.attendance.download-template') }}"
                                    class="px-4 py-2 bg-[#0078C8] text-white rounded text-sm font-medium hover:bg-[#006bb0] transition-colors inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Download Excel Template
                                </a>
                            </div>
                        </div>

                        <!-- Upload File -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-green-50 rounded-lg">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Upload Excel File</h4>
                                    <p class="text-sm text-gray-600">Upload filled template to import records</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <form method="POST" action="{{ route('hrm.attendance.import') }}"
                                    enctype="multipart/form-data" id="importForm">
                                    @csrf
                                    <div class="flex items-center gap-3">
                                        <input type="file" name="import_file" id="import_file" accept=".xlsx,.xls,.csv"
                                            class="hidden" onchange="document.getElementById('importForm').submit()">
                                        <label for="import_file"
                                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] rounded text-sm font-medium hover:bg-[#0078C8] hover:text-white transition-colors cursor-pointer inline-flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            Choose File
                                        </label>
                                        <span id="fileName" class="text-sm text-gray-500">No file chosen</span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- File Format Info -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium text-gray-800 mb-2">File Format Requirements:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>File must be in Excel format (.xlsx, .xls) or CSV</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>First row must contain headers: <code
                                        class="bg-gray-100 px-1 rounded">employee_name</code>, <code
                                        class="bg-gray-100 px-1 rounded">attendance_date</code>, <code
                                        class="bg-gray-100 px-1 rounded">check_in</code>, <code
                                        class="bg-gray-100 px-1 rounded">check_out</code></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><code class="bg-gray-100 px-1 rounded">attendance_date</code> format:
                                    <strong>DD/MM/YYYY</strong> (e.g., 24/12/2024)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><code class="bg-gray-100 px-1 rounded">check_in</code> and <code
                                        class="bg-gray-100 px-1 rounded">check_out</code> format: HH:MM (24-hour format,
                                    e.g., 09:00, 17:30)</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Divider -->
                <div class="flex items-center my-6">
                    <div class="flex-grow border-t border-gray-300"></div>
                    <span class="mx-4 text-sm text-gray-500 font-medium">OR</span>
                    <div class="flex-grow border-t border-gray-300"></div>
                </div>

                <!-- Manual Entry Form Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-800 mb-1">Manual Entry</h3>
                        <p class="text-sm text-gray-600">Add attendance record for a single employee</p>
                    </div>

                    <form method="POST" action_url="{{ route('hrm.attendance.store') }}"
                        back_url="{{ route('hrm.attendance.index') }}" id="formSubmit" class="p-6"
                        id="attendanceForm">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Employee Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                                <select name="employee_id" required
                                    class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    <option value="">Select Employee</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->full_name }} -
                                            {{ $employee->department->department ?? 'N/A' }}
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
                                        value="{{ old('attendance_date', Carbon\Carbon::now()->format('Y-m-d')) }}"
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
                                <input type="time" name="check_in" value="{{ old('check_in') }}"
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                @error('check_in')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Check Out Time -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Check Out Time</label>
                                <input type="time" name="check_out" value="{{ old('check_out') }}"
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                @error('check_out')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-2">Working Type *</label>
                                <div class="flex items-center space-x-6">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="working_type" value="on-site"
                                            class="form-radio text-[#0078C8] focus:ring-[#0078C8]"
                                            {{ old('working_type', 'on-site') == 'on-site' ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">On-site</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="working_type" value="remote"
                                            class="form-radio text-[#0078C8] focus:ring-[#0078C8]"
                                            {{ old('working_type') == 'remote' ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">Remote</span>
                                    </label>
                                </div>
                                <div id="working_type_error" class="text-red-500 text-xs mt-1"></div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                            <a href="{{ route('hrm.attendance.index') }}"
                                class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">
                                Save Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        code {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.85em;
        }

        /* Date picker styling */
        .ui-datepicker {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .ui-datepicker-header {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.5rem;
            border-radius: 0.375rem 0.375rem 0 0;
        }

        .ui-datepicker-calendar th {
            color: #6b7280;
            font-weight: 500;
            padding: 0.25rem;
        }

        .ui-datepicker-calendar td {
            padding: 0.25rem;
        }

        .ui-datepicker-calendar td a {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: block;
            text-align: center;
        }

        .ui-datepicker-calendar td a:hover {
            background: #3b82f6;
            color: white;
        }

        .ui-datepicker-current-day a {
            background: #0078C8;
            color: white;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            // $('.select2').select2({
            //     placeholder: "Select Employee",
            //     allowClear: true
            // });

            // File input display
            const fileInput = document.getElementById('import_file');
            const fileName = document.getElementById('fileName');

            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    fileName.textContent = this.files[0].name;
                } else {
                    fileName.textContent = 'No file chosen';
                }
            });

            // Show file selection dialog on label click
            document.querySelector('label[for="import_file"]').addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });

        });
    </script>
@endpush
