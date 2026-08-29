@extends('layouts.auth')

@section('title', 'Edit Department')

@section('content')
    <div class="mt-4">
         <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.organization') as $module)
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
                            <h2 class="text-lg font-semibold text-black">Edit Department</h2>
                            <a href="{{ route('hrm.department.index') }}"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Back
                            </a>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
                        <form class="space-y-8" method="post"
                            action_url="{{ route('hrm.department.update', $department->id) }}"
                            back_url="{{ route('hrm.department.index') }}" id="formSubmit">
                            @csrf
                            @method('PUT')

                            <!-- ================= BASIC DETAILS ================= -->
                            <div>
                                <div class="grid md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Department Name *</label>
                                        <input type="text" name="department" id="department"
                                            value="{{ old('department', $department->department) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            required />
                                        <div id="department_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Head Name *</label>
                                        <input type="text" name="head" id="department"
                                            value="{{ old('head', $department->head) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            required />
                                        <div id="head_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    {{-- <div>
                                        <label class="block text-sm text-gray-600 mb-1">Select Company</label>
                                        <select name="company_id" id="company_id"
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                            <option value="">Select Company</option>
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}"
                                                    {{ $department->company_id == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div id="company_id_error" class="text-red-500 text-xs mt-1"></div>
                                    </div> --}}
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                                <a href="{{ route('hrm.department.index') }}"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                    CANCEL
                                </a>
                                <button type="submit" id="submitBtn"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                    UPDATE
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%'
            });

            // Form submission
            $('#departmentForm').on('submit', function(e) {
                e.preventDefault();

                // Reset errors
                $('.text-red-500').text('');

                // Get form data
                let formData = new FormData(this);
                let submitBtn = $('#submitBtn');
                let originalText = submitBtn.text();

                // Show loading
                submitBtn.prop('disabled', true).text('Updating...');

                // Submit via AJAX
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showToast('success', response.message);

                            // Redirect to index page after 1.5 seconds
                            setTimeout(function() {
                                window.location.href =
                                    "{{ route('hrm.department.index') }}";
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).text(originalText);

                        if (xhr.status === 422) {
                            // Validation errors
                            let errors = xhr.responseJSON.errors;
                            for (let field in errors) {
                                $('#' + field + '_error').text(errors[field][0]);
                            }
                            showToast('error', 'Please fix the errors in the form.');
                        } else {
                            showToast('error', xhr.responseJSON?.message ||
                                'An error occurred. Please try again.');
                        }
                    }
                });
            });
        });

        function showToast(type, message) {
            const toast = $('#toast');
            const toastContent = $('#toast-content');

            // Set color based on type
            if (type === 'success') {
                toast.css('border-left', '4px solid #10B981');
            } else {
                toast.css('border-left', '4px solid #EF4444');
            }

            // Set message and show
            toastContent.text(message);
            toast.removeClass('hidden').addClass('flex');

            // Auto hide after 5 seconds
            setTimeout(() => {
                toast.addClass('hidden').removeClass('flex');
            }, 5000);
        }
    </script>
@endpush
