@extends('layouts.auth')

@section('title', 'Edit Candidate')

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

    <div class="mx-auto px-3">
        <div class="overflow-hidden">

            <!-- Header -->
            <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6 mb-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg  text-black">Edit Candidate</h2>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('hrm.candidates.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                            Back to Candidates
                        </a>
                        <a href="{{ route('hrm.candidates.show', $candidate->id) }}"
                            class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            View Details
                        </a>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6">
                <form class="space-y-8" method="post" action_url="{{ route('hrm.candidates.update', $candidate->id) }}"
                      back_url="{{ route('hrm.candidates.index') }}" enctype="multipart/form-data" id="formSubmit">
                    @csrf
                    @method('PUT')

                    <!-- Personal Information -->
                    <div>
                        <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">First Name *</label>
                                <input type="text" name="first_name" id="first_name" required
                                    value="{{ old('first_name', $candidate->first_name) }}"
                                    placeholder="e.g. John"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="first_name_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Last Name *</label>
                                <input type="text" name="last_name" id="last_name" required
                                    value="{{ old('last_name', $candidate->last_name) }}"
                                    placeholder="e.g. Doe"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="last_name_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Email Address *</label>
                                <input type="email" name="email" id="email" required
                                    value="{{ old('email', $candidate->email) }}"
                                    placeholder="e.g. john@example.com"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="email_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Phone Number</label>
                                <input type="text" name="phone" id="phone"
                                    value="{{ old('phone', $candidate->phone) }}"
                                    placeholder="e.g. +1 (555) 123-4567"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="phone_error" class="text-red-500 text-xs mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Application Details -->
                    <div>
                        <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Application Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Job Applied For</label>
                                <select name="hrm_job_id" id="hrm_job_id"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                    <option value="">Select Job</option>
                                    @foreach($jobs as $job)
                                        <option value="{{ $job->id }}" {{ old('hrm_job_id', $candidate->hrm_job_id) == $job->id ? 'selected' : '' }}>
                                            {{ $job->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="hrm_job_id_error" class="text-red-500 text-xs mt-1"></div>
                            </div>



                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Application Source</label>
                                <select name="source" id="source"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                    <option value="">Select Source</option>
                                    @foreach(App\Models\HrmCandidate::getSourceOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('source', $candidate->source) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="source_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Stage *</label>
                                <select name="stage" id="stage" required
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none stage-select">
                                    <option value="">Select Stage</option>
                                    @foreach(App\Models\HrmCandidate::getStageOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('stage', $candidate->stage) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="stage_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Applied Date</label>
                                <input class="date" type="date" name="applied_date" id="applied_date"
                                    value="{{ old('applied_date', $candidate->applied_date ? $candidate->applied_date->format('Y-m-d') : date('Y-m-d')) }}"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="applied_date_error" class="text-red-500 text-xs mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Interview Details Section -->
                    <div id="interviewSection" class="{{ $candidate->stage == 'interviewing' ? '' : 'hidden' }}">
                        <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Interview Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Interview Date</label>
                                <input class="date" type="date" name="interview_date" id="interview_date"
                                    value="{{ old('interview_date', $candidate->interview_date ? $candidate->interview_date: '') }}"
                                    class="date w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="interview_date_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Interview Time</label>
                                <input type="time" name="interview_time" id="interview_time"
                                    value="{{ old('interview_time', $candidate->interview_time) }}"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="interview_time_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Interview Location</label>
                                <input type="text" name="interview_location" id="interview_location"
                                    value="{{ old('interview_location', $candidate->interview_location) }}"
                                    placeholder="e.g. Conference Room A"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="interview_location_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm text-gray-600 mb-1">Interviewer</label>
                                <input type="text" name="interviewer" id="interviewer"
                                    value="{{ old('interviewer', $candidate->interviewer) }}"
                                    placeholder="e.g. John Smith (HR Manager)"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                <div id="interviewer_error" class="text-red-500 text-xs mt-1"></div>
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-sm text-gray-600 mb-1">Interview Notes</label>
                                <textarea name="interview_notes" id="interview_notes" rows="3"
                                    placeholder="Any notes about the interview..."
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">{{ old('interview_notes', $candidate->interview_notes) }}</textarea>
                                <div id="interview_notes_error" class="text-red-500 text-xs mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Resume/CV -->
                    <div>
                        <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Resume / CV</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Upload Resume</label>
                                @if($candidate->resume_path)
                                    <div class="mb-3 p-3 bg-gray-50 rounded-md" id="existingResume">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <svg class="w-6 h-6 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $candidate->resume_original_name }}</p>
                                                    <p class="text-xs text-gray-500">{{ $candidate->resume_size }}</p>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <a href="{{ route('hrm.candidates.download-resume', $candidate->id) }}"
                                                   class="text-sm text-[#0078C8] hover:text-[#006bb0]">
                                                    Download
                                                </a>
                                                <button type="button" onclick="removeResume()"
                                                        class="text-sm text-red-600 hover:text-red-800">
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="remove_resume" id="removeResume" value="0">
                                @endif

                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="resume" class="relative cursor-pointer bg-white rounded-md font-medium text-[#0078C8] hover:text-[#006bb0] focus-within:outline-none">
                                                <span>{{ $candidate->resume_path ? 'Upload new file' : 'Upload a file' }}</span>
                                                <input id="resume" name="resume" type="file" class="sr-only" accept=".pdf,.doc,.docx,.txt">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PDF, DOC, DOCX, TXT up to 10MB</p>
                                        @if($candidate->resume_path)
                                            <p class="text-xs text-blue-600">Uploading a new file will replace the existing resume.</p>
                                        @endif
                                    </div>
                                </div>
                                <div id="resume_error" class="text-red-500 text-xs mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div>
                        <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Additional Notes</h3>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Notes</label>
                            <textarea name="notes" id="notes" rows="4"
                                placeholder="Any additional notes about the candidate..."
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">{{ old('notes', $candidate->notes) }}</textarea>
                            <div id="notes_error" class="text-red-500 text-xs mt-1"></div>
                        </div>
                    </div>



                    <!-- Buttons -->
                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                        <a href="{{ route('hrm.candidates.index') }}"
                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                            CANCEL
                        </a>
                        <button type="submit" id="submitBtn"
                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            UPDATE CANDIDATE
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
    // Show/Hide interview section based on stage selection
    function toggleInterviewSection() {
        const stage = $('#stage').val();
        const interviewSection = $('#interviewSection');

        if (stage === 'interviewing') {
            interviewSection.removeClass('hidden');
            interviewSection.slideDown(300);

            // If interview date is empty, set to today + 2 days
            if (!$('#interview_date').val()) {
                const twoDaysLater = new Date();
                twoDaysLater.setDate(twoDaysLater.getDate() + 2);
                $('#interview_date').val(twoDaysLater.toISOString().split('T')[0]);
            }

            // If interview time is empty, set to 10:00 AM
            if (!$('#interview_time').val()) {
                $('#interview_time').val('10:00');
            }
        } else {
            interviewSection.slideUp(300, function() {
                $(this).addClass('hidden');
            });
        }
    }

    // Initial check
    toggleInterviewSection();

    // Listen for stage changes
    $('#stage').on('change', function() {
        toggleInterviewSection();
    });

    // Remove resume function
    window.removeResume = function() {
        if (confirm('Are you sure you want to remove the resume?')) {
            $('#existingResume').hide();
            $('#removeResume').val('1');
        }
    }

    // Drag and drop file upload
    const dropArea = $('.border-dashed');
    const fileInput = $('#resume');

    dropArea.on('dragover', function(e) {
        e.preventDefault();
        dropArea.addClass('border-[#0078C8] bg-blue-50');
    });

    dropArea.on('dragleave', function(e) {
        e.preventDefault();
        dropArea.removeClass('border-[#0078C8] bg-blue-50');
    });

    dropArea.on('drop', function(e) {
        e.preventDefault();
        dropArea.removeClass('border-[#0078C8] bg-blue-50');

        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput.prop('files', files);

            // Show file name
            const fileName = files[0].name;
            dropArea.find('.text-center').html(`
                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-sm text-gray-600">File selected:</p>
                <p class="text-xs text-gray-500">${fileName}</p>
            `);
        }
    });

    // File input change
    fileInput.on('change', function() {
        if (this.files.length > 0) {
            const fileName = this.files[0].name;
            dropArea.find('.text-center').html(`
                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-sm text-gray-600">File selected:</p>
                <p class="text-xs text-gray-500">${fileName}</p>
            `);
        }
    });


});
</script>
@endpush
