@extends('layouts.auth')

@section('title', 'Edit Job Posting')

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

                <!-- Header -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-black">Edit Job Posting</h2>
                        <a href="{{ route('hrm.jobs.index') }}"
                            class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            Back to Jobs
                        </a>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Update the job details for "{{ $job->job_title }}"</p>
                </div>

                <!-- Form -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
                    <form class="space-y-8" method="post" action_url="{{ route('hrm.jobs.update', $job->id) }}" back_url={{ route('hrm.jobs.index') }}
                          enctype="multipart/form-data" id="formSubmit">
                        @csrf
                        @method('PUT')

                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-md font-medium text-gray-800 mb-4  pb-2"> Job Details</h3>
                            <div class="grid md:grid-cols-1 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Job Title *</label>
                                    <input type="text" name="job_title" id="job_title" required
                                        value="{{ old('job_title', $job->job_title) }}"
                                        placeholder="e.g. Senior Product Designer"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                    <div id="job_title_error" class="text-red-500 text-xs mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Department *</label>
                                    <select name="department_id" id="department_id" required
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" {{ old('department_id', $job->department_id) == $department->id ? 'selected' : '' }}>
                                                {{ $department->department }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="department_id_error" class="text-red-500 text-xs mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Employment Type *</label>
                                    <select name="employment_type" id="employment_type" required
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <option value="">Select Type</option>
                                        @foreach($employmentTypeOptions = App\Models\HrmJob::getEmploymentTypeOptions() as $value => $label)
                                            <option value="{{ $value }}" {{ old('employment_type', $job->employment_type) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="employment_type_error" class="text-red-500 text-xs mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Experience Level *</label>
                                    <select name="experience_level" id="experience_level" required
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <option value="">Select Experience Level</option>
                                        @foreach($experienceLevelOptions = App\Models\HrmJob::getExperienceLevelOptions() as $value => $label)
                                            <option value="{{ $value }}" {{ old('experience_level', $job->experience_level) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="experience_level_error" class="text-red-500 text-xs mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Location *</label>
                                    <input type="text" name="location" id="location" required
                                        value="{{ old('location', $job->location) }}"
                                        placeholder="City, State, or Remote"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                    <div id="location_error" class="text-red-500 text-xs mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <div>
                            <h3 class="text-md font-medium text-gray-800 mb-4  pb-2"> Requirements & Skills</h3>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Job Description *</label>
                                <textarea name="job_description" id="job_description" rows="6" required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                    placeholder="Describe the role overview, responsibilities, and company culture...">{{ old('job_description', $job->job_description) }}</textarea>
                                <div id="job_description_error" class="text-red-500 text-xs mt-1"></div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm text-gray-600 mb-1">Requirements</label>
                                <textarea name="requirements" id="requirements" rows="4"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                    placeholder="List the requirements and qualifications...">{{ old('requirements', $job->requirements) }}</textarea>
                                <div id="requirements_error" class="text-red-500 text-xs mt-1"></div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm text-gray-600 mb-1">Required Skills *</label>
                                <div class="mb-3">
                                    <!-- Skills Tags Container -->
                                    <div id="skills-tags-container" class="flex flex-wrap gap-2 mb-3 p-3 border border-gray-300 rounded-md min-h-[44px]">
                                        <!-- Skills tags will be added here -->
                                        @php
                                            // Get skills from job
                                            $jobSkills = $job->skills ?? [];
                                            if (is_string($jobSkills)) {
                                                $jobSkills = json_decode($jobSkills, true) ?? [];
                                            }
                                            // Normalize skills from old input or job
                                            $normalizedSkills = [];
                                            if (old('skills')) {
                                                foreach(old('skills') as $skill) {
                                                    $normalizedSkill = trim($skill);
                                                    if ($normalizedSkill && !in_array(strtolower($normalizedSkill), array_map('strtolower', $normalizedSkills))) {
                                                        $normalizedSkills[] = $normalizedSkill;
                                                    }
                                                }
                                            } else {
                                                foreach($jobSkills as $skill) {
                                                    $normalizedSkill = trim($skill);
                                                    if ($normalizedSkill && !in_array(strtolower($normalizedSkill), array_map('strtolower', $normalizedSkills))) {
                                                        $normalizedSkills[] = $normalizedSkill;
                                                    }
                                                }
                                            }
                                        @endphp
                                        @foreach($normalizedSkills as $skill)
                                            <div class="skill-tag inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-800 rounded-full text-sm">
                                                {{ $skill }}
                                                <button type="button" class="remove-skill text-blue-600 hover:text-blue-800" data-skill="{{ $skill }}">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Skills Input -->
                                    <div class="relative">
                                        <input type="text"
                                            id="skill-input"
                                            placeholder="Type a skill and press Enter"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            autocomplete="off">
                                        <div class="text-xs text-gray-500 mt-1">Type skill name and press Enter to add. Duplicates (case-insensitive) will be ignored.</div>
                                    </div>

                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" name="skills" id="skills-input" value="{{ old('skills', json_encode($normalizedSkills)) }}">
                                    <div id="skills_error" class="text-red-500 text-xs mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment & Status -->
                        <div>
                            <h3 class="text-md font-medium text-gray-800 mb-4  pb-2"> Assignment & Status</h3>
                            <div class="grid md:grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Hiring Manager</label>
                                    <select name="hiring_manager_id" id="hiring_manager_id"
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <option value="">Assign Manager</option>
                                        @foreach($hiringManagers as $manager)
                                            <option value="{{ $manager->id }}" {{ old('hiring_manager_id', $job->hiring_manager_id) == $manager->id ? 'selected' : '' }}>
                                                {{ $manager->first_name . ' ' . $manager->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="hiring_manager_id_error" class="text-red-500 text-xs mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Status *</label>
                                    <select name="status" id="status" required
                                        class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                        <option value="">Select Status</option>
                                        @foreach($statusOptions = App\Models\HrmJob::getStatusOptions() as $value => $label)
                                            <option value="{{ $value }}" {{ old('status', $job->status) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="status_error" class="text-red-500 text-xs mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                            <a href="{{ route('hrm.jobs.index') }}"
                                class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                CANCEL
                            </a>
                            <button type="submit" id="submitBtn"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                UPDATE JOB POSTING
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
    let skills = [];

    // Load skills from hidden input or job data
    const skillsInput = $('#skills-input');
    if (skillsInput.val()) {
        try {
            const parsedSkills = JSON.parse(skillsInput.val());
            // Normalize skills (case-insensitive unique)
            const normalizedSkills = [];
            parsedSkills.forEach(skill => {
                const trimmedSkill = skill.trim();
                if (trimmedSkill && !normalizedSkills.some(s => s.toLowerCase() === trimmedSkill.toLowerCase())) {
                    normalizedSkills.push(trimmedSkill);
                }
            });
            skills = normalizedSkills;
            renderSkills();
        } catch (e) {
            // If it's not JSON, try to parse as comma-separated
            if (skillsInput.val().trim()) {
                const skillArray = skillsInput.val().split(',').map(s => s.trim()).filter(s => s);
                // Normalize skills (case-insensitive unique)
                const normalizedSkills = [];
                skillArray.forEach(skill => {
                    if (!normalizedSkills.some(s => s.toLowerCase() === skill.toLowerCase())) {
                        normalizedSkills.push(skill);
                    }
                });
                skills = normalizedSkills;
                renderSkills();
            }
        }
    }

    // Normalize skill name (trim and optionally format)
    function normalizeSkill(skill) {
        return skill.trim();
    }

    // Check if skill already exists (case-insensitive)
    function skillExists(skill) {
        const normalizedSkill = normalizeSkill(skill);
        return skills.some(s => s.toLowerCase() === normalizedSkill.toLowerCase());
    }

    // Add skill on Enter key
    $('#skill-input').keydown(function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            addSkill();
        }
    });

    // Add skill on comma
    $('#skill-input').keyup(function(e) {
        if (e.key === ',' || e.keyCode === 188) {
            const value = $(this).val().trim();
            if (value.endsWith(',')) {
                $(this).val(value.slice(0, -1));
                addSkill();
            }
        }
    });

    // Click outside to add skill
    $('#skill-input').blur(function() {
        if ($(this).val().trim()) {
            addSkill();
        }
    });

    // Remove skill when clicking remove button
    $(document).on('click', '.remove-skill', function() {
        const skillToRemove = $(this).data('skill');
        skills = skills.filter(s => s !== skillToRemove);
        renderSkills();
    });



    // Skill management functions
    function addSkill() {
        const input = $('#skill-input');
        const skill = normalizeSkill(input.val());

        if (skill) {
            // Check if skill already exists (case-insensitive)
            if (!skillExists(skill)) {
                skills.push(skill);
                renderSkills();
            } else {
                // Show warning for duplicate
                showToast(`Skill "${skill}" already added`, 'error');
            }
            input.val('');
            input.focus();
        }
    }

    function renderSkills() {
        const container = $('#skills-tags-container');
        container.empty();

        skills.forEach((skill, index) => {
            const skillElement = $(`
                <div class="skill-tag inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-800 rounded-full text-sm">
                    ${skill}
                    <button type="button" class="remove-skill text-blue-600 hover:text-blue-800" data-skill="${skill}">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            `);
            container.append(skillElement);
        });

        // Update hidden input
        $('#skills-input').val(JSON.stringify(skills));
    }
});
</script>
@endpush
