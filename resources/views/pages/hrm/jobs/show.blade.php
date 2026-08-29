@extends('layouts.auth')

@section('title', 'Job Details - ' . $job->job_title)

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
                        <div>
                            <h2 class="text-lg font-semibold text-black">{{ $job->job_title }}</h2>
                            <div class="flex items-center gap-4 mt-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">{{ $job->job_id }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">{{ $job->location }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php
                                        $statusOptions = App\Models\HrmJob::getStatusOptions();
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $job->status_color }}">
                                        {{ $statusOptions[$job->status] ?? $job->status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('hrm.jobs.index') }}"
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                Back to Jobs
                            </a>
                            <a href="{{ route('hrm.jobs.edit', $job->id) }}"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Edit Job
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column - Job Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Job Details Card -->
                        <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                            <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Job Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Job Title</label>
                                    <p class="font-medium text-gray-800">{{ $job->job_title }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Job ID</label>
                                    <p class="font-medium text-gray-800">{{ $job->job_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Department</label>
                                    <p class="font-medium text-gray-800">{{ $job->department->department ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Employment Type</label>
                                    <p class="font-medium text-gray-800">{{ $job->formatted_employment_type }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Experience Level</label>
                                    <p class="font-medium text-gray-800">{{ $job->formatted_experience_level }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Location</label>
                                    <p class="font-medium text-gray-800">{{ $job->location }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Status</label>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $job->status_color }}">
                                        {{ $statusOptions[$job->status] ?? $job->status }}
                                    </span>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Posted Date</label>
                                    <p class="font-medium text-gray-800">
                                        @if($job->posted_date)
                                            {{ $job->posted_date->format('d M, Y') }}
                                        @else
                                            <span class="text-gray-400">Not posted</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Closing Date</label>
                                    <p class="font-medium text-gray-800">
                                        @if($job->closing_date)
                                            {{ $job->closing_date->format('d M, Y') }}
                                        @else
                                            <span class="text-gray-400">Not specified</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm text-gray-500 mb-1">Hiring Manager</label>
                                    <p class="font-medium text-gray-800">
                                        @if($job->hiringManager)
                                            {{ $job->hiringManager->first_name . ' ' . $job->hiringManager->last_name }}
                                        @else
                                            <span class="text-gray-400">Not assigned</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Job Description Card -->
                        <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                            <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Job Description</h3>
                            <div class="prose prose-sm max-w-none">
                                {!! nl2br(e($job->job_description)) !!}
                            </div>
                        </div>

                        <!-- Requirements Card -->
                        @if($job->requirements)
                        <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                            <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Requirements & Qualifications</h3>
                            <div class="prose prose-sm max-w-none">
                                {!! nl2br(e($job->requirements)) !!}
                            </div>
                        </div>
                        @endif

                        <!-- Skills Card -->
                        <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                            <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Required Skills</h3>
                            <div class="flex flex-wrap gap-2">
                                @php
                                    $jobSkills = $job->skills ?? [];
                                    if (is_string($jobSkills)) {
                                        $jobSkills = json_decode($jobSkills, true) ?? [];
                                    }
                                @endphp
                                @forelse($jobSkills as $skill)
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm bg-blue-100 text-blue-800">
                                        {{ $skill }}
                                    </span>
                                @empty
                                    <p class="text-gray-500 text-sm">No skills specified</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Sidebar -->
                    <div class="space-y-6">
                        <!-- Statistics Card -->
                        <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                            <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Statistics</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Total Candidates</label>
                                    <div class="flex items-center justify-between">
                                        <p class="text-2xl font-bold text-gray-800">{{ $job->candidates->count() }}</p>
                                        <a href="#candidates" class="text-sm text-[#0078C8] hover:underline">View All</a>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">New</label>
                                        <p class="text-lg font-semibold text-blue-600">{{ $applicationStats['applied'] }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Screening</label>
                                        <p class="text-lg font-semibold text-yellow-600">{{ $applicationStats['reviewed'] }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Interviewing</label>
                                        <p class="text-lg font-semibold text-purple-600">{{ $applicationStats['interviewed'] }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Offer</label>
                                        @php
                                            $offerCount = $job->candidates->where('stage', 'offer')->count();
                                        @endphp
                                        <p class="text-lg font-semibold text-green-600">{{ $offerCount }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Hired</label>
                                        <p class="text-lg font-semibold text-emerald-600">{{ $applicationStats['hired'] }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Rejected</label>
                                        <p class="text-lg font-semibold text-red-600">{{ $applicationStats['rejected'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline Card -->
                        <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                            <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Timeline</h3>
                            <div class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Created</p>
                                        <p class="text-xs text-gray-500">{{ $job->created_at->format('d M, Y h:i A') }}</p>
                                        <p class="text-xs text-gray-500">
                                            by {{ $job->createdBy->first_name . ' ' . $job->createdBy->last_name ?? 'System' }}
                                        </p>
                                    </div>
                                </div>
                                @if($job->posted_date)
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Published</p>
                                        <p class="text-xs text-gray-500">{{ $job->posted_date->format('d M, Y') }}</p>
                                    </div>
                                </div>
                                @endif
                                @if($job->updated_at && $job->updated_at->gt($job->created_at))
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Last Updated</p>
                                        <p class="text-xs text-gray-500">{{ $job->updated_at->format('d M, Y h:i A') }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Quick Actions Card -->
                        <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                            <h3 class="text-md font-medium text-gray-800 mb-4 pb-2">Quick Actions</h3>
                            <div class="space-y-2">
                                <a href="{{ route('hrm.jobs.edit', $job->id) }}"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-[#0078C8] hover:bg-blue-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                    Edit Job Details
                                </a>
                                @if($job->status === 'draft')
                                <button onclick="updateJobStatus({{ $job->id }}, 'open')"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-green-600 hover:bg-green-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Publish Job
                                </button>
                                @endif
                                @if($job->status === 'open')
                                <button onclick="updateJobStatus({{ $job->id }}, 'closed')"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Close Job
                                </button>
                                @endif
                                <a href="{{ route('hrm.jobs.clone', $job->id) }}"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-purple-600 hover:bg-purple-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z"/>
                                        <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z"/>
                                    </svg>
                                    Clone Job
                                </a>
                                <button onclick="confirmDeleteJob({{ $job->id }})"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Delete Job
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Candidates Section -->
                <div id="candidates" class="mt-6">
                    <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-md font-medium text-gray-800">Candidates ({{ $job->candidates->count() }})</h3>
                            <a href="{{ route('hrm.candidates.index') }}?job_id={{ $job->id }}"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                View All Candidates
                            </a>
                        </div>

                        @if($job->candidates->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Candidate</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applied Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stage</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($job->candidates->take(5) as $candidate)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-medium text-gray-800">{{ $candidate->first_name }} {{ $candidate->last_name }}</div>
                                                            <div class="text-xs text-gray-500">{{ $candidate->current_role ?? 'Not specified' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="text-sm">
                                                        @if($candidate->applied_date)
                                                            {{ $candidate->applied_date->format('d M, Y') }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </div>
                                                    @if($candidate->addedBy)
                                                        <div class="text-xs text-gray-500">by {{ $candidate->addedBy->first_name }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $candidate->stage_color }}">
                                                        {{ App\Models\HrmCandidate::getStageOptions()[$candidate->stage] ?? $candidate->stage }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="space-y-1">
                                                        <div class="flex items-center gap-1">
                                                            <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                                            </svg>
                                                            <span class="text-xs text-gray-600 truncate max-w-[150px]">{{ $candidate->email }}</span>
                                                        </div>
                                                        @if($candidate->phone)
                                                            <div class="flex items-center gap-1">
                                                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                                                </svg>
                                                                <span class="text-xs text-gray-600">{{ $candidate->phone }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <a href="{{ route('hrm.candidates.show', $candidate->id) }}"
                                                            class="px-3 py-1 text-xs text-[#0078C8] hover:bg-blue-50 rounded transition-colors">
                                                            View
                                                        </a>
                                                        <a href="{{ route('hrm.candidates.edit', $candidate->id) }}"
                                                            class="px-3 py-1 text-xs text-gray-600 hover:bg-gray-100 rounded transition-colors">
                                                            Edit
                                                        </a>
                                                        @if($candidate->resume_path)
                                                            <a href="{{ route('hrm.candidates.download-resume', $candidate->id) }}"
                                                                class="px-3 py-1 text-xs text-green-600 hover:bg-green-50 rounded transition-colors">
                                                                Resume
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($job->candidates->count() > 5)
                                <div class="mt-4 text-center">
                                    <a href="{{ route('hrm.candidates.index') }}?job_id={{ $job->id }}"
                                        class="text-sm text-[#0078C8] hover:underline">
                                        Show all {{ $job->candidates->count() }} candidates
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-8">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-4.201V8m0 0l3 3-3 3M4 8l3-3 3 3"/>
                                </svg>
                                <p class="text-gray-500">No candidates yet</p>
                                <p class="text-sm text-gray-400 mt-1">Candidates will appear here when they apply for this job</p>
                                <div class="mt-4">
                                    <a href="{{ route('hrm.candidates.create') }}?job_id={{ $job->id }}"
                                        class="inline-flex items-center px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add Candidate
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>

<!-- Delete confirmation modal -->
<div id="deleteJobModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Job</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to delete this job posting? This action cannot be undone.
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="cancelDeleteJob"
                        class="px-4 py-2 bg-gray-100 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-200 mr-2">
                        Cancel
                    </button>
                    <button id="confirmDeleteJob"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentJobId = null;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Toast functions
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        toast.classList.remove('hidden');
        content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    ${type === 'success'
                        ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                        : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;
        setTimeout(hideToast, 3000);
    }

    function hideToast() {
        document.getElementById('toast').classList.add('hidden');
    }

    // Update job status
    function updateJobStatus(jobId, status) {
        const confirmMessage = status === 'open'
            ? 'Are you sure you want to publish this job?'
            : 'Are you sure you want to close this job?';

        if (confirm(confirmMessage)) {
            fetch(`/hrm/jobs/${jobId}/update-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Reload page after 1 second
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Network error. Please try again.', 'error');
            });
        }
    }

    // Delete job confirmation
    function confirmDeleteJob(jobId) {
        currentJobId = jobId;
        document.getElementById('deleteJobModal').classList.remove('hidden');
    }

    // Cancel delete
    document.getElementById('cancelDeleteJob')?.addEventListener('click', function() {
        document.getElementById('deleteJobModal').classList.add('hidden');
        currentJobId = null;
    });

    // Confirm delete
    document.getElementById('confirmDeleteJob')?.addEventListener('click', function() {
        if (!currentJobId) return;

        fetch(`/hrm/jobs/${currentJobId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Job deleted successfully!', 'success');
                // Redirect to jobs list after 1 second
                setTimeout(() => {
                    window.location.href = '{{ route('hrm.jobs.index') }}';
                }, 1000);
            } else {
                showToast(data.message || 'Error deleting job', 'error');
            }
        })
        .catch(error => {
            showToast('Network error. Please try again.', 'error');
        })
        .finally(() => {
            document.getElementById('deleteJobModal').classList.add('hidden');
            currentJobId = null;
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('deleteJobModal');
        if (event.target === modal) {
            modal.classList.add('hidden');
            currentJobId = null;
        }
    });
</script>
@endpush
