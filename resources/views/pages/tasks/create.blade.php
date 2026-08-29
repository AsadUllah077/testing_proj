@extends('layouts.auth')

@section('title', 'Create Task')

@section('content')
<div class="mt-4">
    <div class="mx-auto px-3">
        <!-- Header -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <h2 class="text-lg font-medium text-gray-800">Add & Manage Task</h2>
                </div>
                <a href="{{ route('tasks.index') }}"
                    class="px-4 py-2 bg-[#0078C8] hover:bg-[#0078C8] text-white text-sm font-medium rounded-md transition duration-200 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back
                </a>
            </div>
        </div>

        <!-- Form Container -->
        <form method="post" action="{{ route('tasks.store') }}" id="formSubmit" enctype="multipart/form-data"
              action_url="{{ route('tasks.store') }}" back_url="{{ route('tasks.index') }}">
            @csrf

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Basic Info Card -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-5 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-sm font-medium">1</span>
                                </div>
                                <h3 class="text-md font-medium text-gray-800">Basic Info</h3>
                            </div>
                        </div>
                        <div class="p-5 space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Task Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" id="title" required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none transition duration-200"
                                    placeholder="e.g. Q4 Financial Audit & Report Generation">
                                <div id="title_error" class="text-red-500 text-xs mt-2"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" id="description" rows="4"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none transition duration-200 resize-none"
                                    placeholder="Describe the task requirements, expected outcomes, and useful links..."></textarea>
                                <div id="description_error" class="text-red-500 text-xs mt-2"></div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
                                    <select name="category" id="category" required
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none appearance-none bg-white">
                                        <option value="">Select Category</option>
                                        @foreach($categoryOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div id="category_error" class="text-red-500 text-xs mt-2"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Task Type</label>
                                    <select name="task_type" id="task_type"
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none appearance-none bg-white">
                                        <option value="">Select Task Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="team">Team</option>
                                        <option value="project">Project</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Managerial Oversight Card -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-5 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-sm font-medium">2</span>
                                </div>
                                <h3 class="text-md font-medium text-gray-800">Managerial Oversight</h3>
                            </div>
                        </div>
                        <div class="p-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Initial Manager Remarks</label>
                                <textarea name="manager_remarks" id="manager_remarks" rows="3"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none transition duration-200 resize-none"
                                    placeholder="Add remarks or additional instructions..."></textarea>
                                <div id="manager_remarks_error" class="text-red-500 text-xs mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Assignment Card -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-5 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-sm font-medium">3</span>
                                </div>
                                <h3 class="text-md font-medium text-gray-800">Assignment</h3>
                            </div>
                        </div>
                        <div class="p-5 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                    <select name="department_id" id="department_id"
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none appearance-none bg-white">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->department }}</option>
                                        @endforeach
                                    </select>
                                    <div id="department_id_error" class="text-red-500 text-xs mt-2"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Members</label>
                                    <select name="assigned_employees[]" id="assigned_employees" multiple
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none appearance-none bg-white min-h-[46px]">
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <div id="assigned_employees_error" class="text-red-500 text-xs mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline & Priority Card -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-5 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-sm font-medium">4</span>
                                </div>
                                <h3 class="text-md font-medium text-gray-800">Timeline & Priority</h3>
                            </div>
                        </div>
                        <div class="p-5 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Deadline <span class="text-red-500">*</span></label>
                                    <input type="datetime-local" name="deadline" id="deadline" required
                                        class="date w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none transition duration-200 appearance-none bg-white">
                                    <div id="deadline_error" class="text-red-500 text-xs mt-2"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level <span class="text-red-500">*</span></label>
                                    <select name="priority" id="priority" required
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none appearance-none bg-white">
                                        <option value="">Select Priority</option>
                                        @foreach($priorityOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div id="priority_error" class="text-red-500 text-xs mt-2"></div>
                                </div>
                            </div>

                            <!-- Status Field Row -->
                            <div class="grid grid-cols-1 gap-5 pt-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                                    <select name="status" id="status" required
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 focus:outline-none appearance-none bg-white">
                                        <option value="">Select Status</option>
                                        <option value="draft" selected>Draft</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="review">Review</option>
                                        <option value="completed">Completed</option>
                                        <option value="on_hold">On Hold</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <div id="status_error" class="text-red-500 text-xs mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>
            </div>

            <!-- Action Buttons Card -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-5">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Note:</span> Fields marked with <span class="text-red-500">*</span> are required
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('tasks.index') }}"
                                class="px-6 py-3 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition duration-200">
                                Cancel
                            </a>
                            <button type="submit" id="submitBtn"
                                class="px-6 py-3 bg-[#0078C8] hover:bg-[#0078C8] text-white text-sm font-medium rounded-lg transition duration-200 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Save & Assign Task
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-4">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>
@endsection

@push('styles')
<style>
    /* Custom select styling */
    select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.25em 1.25em;
        padding-right: 2.5rem;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* For multiple select */
    select[multiple] {
        background-image: none;
        min-height: 120px;
    }

    /* Checkbox toggle styling */
    input:checked + div {
        background-color: #0078C8;
    }

    input:checked + div:after {
        transform: translateX(1.5rem);
    }

    /* Select2 custom styling */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        min-height: 46px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #0078C8;
        box-shadow: 0 0 0 2px rgba(72, 169, 248, 0.2);
        outline: none;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 46px;
        padding-left: 16px;
        padding-right: 30px;
    }

    .select2-container--default .select2-selection--multiple {
        min-height: 46px;
        padding: 4px 8px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #e0f2fe;
        border: 1px solid #bae6fd;
        border-radius: 0.25rem;
        padding: 2px 8px;
        margin-top: 4px;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 46px;
        right: 8px;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize all select2 elements
    $('.select2').select2({
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || 'Select an option';
        },
        allowClear: true
    });

    // Set default deadline to tomorrow at 9:00 AM
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    const year = tomorrow.getFullYear();
    const month = String(tomorrow.getMonth() + 1).padStart(2, '0'); // Fixed month calculation
    const day = String(tomorrow.getDate()).padStart(2, '0');

    $('#deadline').val(`${year}-${month}-${day}T09:00`);

    // Remove any duplicate custom dropdown arrows that might conflict
    $('select.select2').each(function() {
        $(this).next('.select2-container').find('.select2-selection__arrow').remove();
    });

    // Form validation and submission
    $(document).on('submit', '#formSubmit', function(e) {
        e.preventDefault();

        // Clear previous errors
        $('.text-red-500').text('');

        let isValid = true;
        const formData = new FormData(this);

        // Basic validation
        if (!$('#title').val().trim()) {
            $('#title_error').text('Task title is required');
            isValid = false;
        }

        if (!$('#category').val()) {
            $('#category_error').text('Category is required');
            isValid = false;
        }

        if (!$('#deadline').val()) {
            $('#deadline_error').text('Deadline is required');
            isValid = false;
        }

        if (!$('#priority').val()) {
            $('#priority_error').text('Priority level is required');
            isValid = false;
        }

        if (!$('#status').val()) {
            $('#status_error').text('Status is required');
            isValid = false;
        }

        if (!isValid) {
            showToast('Please fill all required fields', 'error');
            return false;
        }


    });
});


</script>
@endpush
