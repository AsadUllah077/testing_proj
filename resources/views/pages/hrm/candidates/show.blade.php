@extends('layouts.auth')

@section('title', 'Candidate Details')

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
            <div class="mx-auto bg-white border border-gray-200 rounded-md p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl text-gray-900">{{ $candidate->full_name }}</h1>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm {{ $candidate->stage_color }}">
                                    {{ App\Models\HrmCandidate::getStageOptions()[$candidate->stage] }}
                                </span>
                                @if($candidate->stage == 'interviewing' && $candidate->interview_date)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $candidate->interview_date->format('M d, Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('hrm.candidates.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                            Back to Candidates
                        </a>
                        <a href="{{ route('hrm.candidates.edit', $candidate->id) }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#0078C8]">
                            Edit Candidate
                        </a>
                    </div>
                </div>

                <!-- Action Dropdown -->
                <div id="actionDropdown" class="hidden mt-3 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                    <ul class="py-1">
                        <li>
                            <a href="{{ route('hrm.candidates.download-resume', $candidate->id) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    Download Resume
                                </div>
                            </a>
                        </li>
                        <li>
                            <button type="button" onclick="updateStageModal()"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    Update Stage
                                </div>
                            </button>
                        </li>
                        <li>
                            <button type="button" onclick="confirmDelete()"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Delete Candidate
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Candidate Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Personal Information Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Full Name</h4>
                                <p class="text-gray-900">{{ $candidate->full_name }}</p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Email Address</h4>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                    </svg>
                                    <a href="mailto:{{ $candidate->email }}" class="text-[#0078C8] hover:underline">{{ $candidate->email }}</a>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Phone Number</h4>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                    </svg>
                                    <p class="text-gray-900">{{ $candidate->phone ?: 'Not provided' }}</p>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Current Role</h4>
                                <p class="text-gray-900">{{ $candidate->current_role ?: 'Not specified' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Interview Details Card (Only for interviewing stage) -->
                    @if($candidate->stage == 'interviewing')
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Interview Details</h3>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-purple-100 text-purple-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    Interview Scheduled
                                </span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-1">Interview Date</h4>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-gray-900">
                                            {{ $candidate->interview_date ? $candidate->interview_date->format('F d, Y') : 'Not scheduled' }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-1">Interview Time</h4>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-gray-900">
                                            {{ $candidate->formatted_interview_time ?: 'Not specified' }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-1">Interview Location</h4>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-gray-900">
                                            {{ $candidate->interview_location ?: 'Not specified' }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-1">Interviewer</h4>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-gray-900">
                                            {{ $candidate->interviewer ?: 'Not assigned' }}
                                        </p>
                                    </div>
                                </div>
                                @if($candidate->interview_notes)
                                    <div class="md:col-span-2">
                                        <h4 class="text-sm font-medium text-gray-500 mb-1">Interview Notes</h4>
                                        <div class="bg-gray-50 p-4 rounded-md">
                                            <p class="text-gray-700 whitespace-pre-line">{{ $candidate->interview_notes }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Interview Reminder -->
                            @if($candidate->interview_date && $candidate->interview_date->isFuture())
                                @php
                                    $daysUntilInterview = $candidate->interview_date->diffInDays(now());
                                @endphp
                                <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-md">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm font-medium text-blue-700">
                                            @if($daysUntilInterview == 0)
                                                Interview is today at {{ $candidate->formatted_interview_time }}
                                            @elseif($daysUntilInterview == 1)
                                                Interview is tomorrow at {{ $candidate->formatted_interview_time }}
                                            @else
                                                Interview in {{ $daysUntilInterview }} days
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Application Details Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2">Application Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Job Applied For</h4>
                                @if($candidate->job)
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                            <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                                        </svg>
                                        <a href="{{ route('hrm.jobs.show', $candidate->job->id) }}" class="text-[#0078C8] hover:underline">
                                            {{ $candidate->job->job_title }}
                                        </a>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        ID: {{ $candidate->job->job_id }} • {{ $candidate->job->location }}
                                    </div>
                                @else
                                    <p class="text-gray-500 italic">No job specified</p>
                                @endif
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Application Source</h4>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-gray-900">{{ $candidate->source ? App\Models\HrmCandidate::getSourceOptions()[$candidate->source] : 'Not specified' }}</p>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Applied Date</h4>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-gray-900">{{ $candidate->applied_date ? $candidate->applied_date->format('F d, Y') : 'Not specified' }}</p>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Added By</h4>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-gray-900">{{ $candidate->addedBy->full_name ?? 'Unknown' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resume & Notes Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2">Documents & Notes</h3>

                        <!-- Resume -->
                        @if($candidate->resume_path)
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-3">Resume</h4>
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $candidate->resume_original_name }}</p>
                                                <p class="text-xs text-gray-500">Uploaded {{ $candidate->created_at->format('M d, Y') }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('hrm.candidates.download-resume', $candidate->id) }}"
                                            class="px-3 py-1.5 bg-[#0078C8] text-white text-sm rounded-md hover:bg-[#006bb0]">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-3">Resume</h4>
                                <div class="p-4 bg-gray-50 rounded-lg text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No resume uploaded</p>
                                </div>
                            </div>
                        @endif

                        <!-- Cover Letter -->
                        @if($candidate->cover_letter)
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-3">Cover Letter</h4>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <p class="text-gray-700 whitespace-pre-line">{{ $candidate->cover_letter }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Notes -->
                        @if($candidate->notes)
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-3">Additional Notes</h4>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <p class="text-gray-700 whitespace-pre-line">{{ $candidate->notes }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Column - Timeline & Actions -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2">Candidate Status</h3>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Current Stage</h4>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $candidate->stage_color }}">
                                        {{ App\Models\HrmCandidate::getStageOptions()[$candidate->stage] }}
                                    </span>
                                    @if($candidate->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Quick Stage Update -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Update Stage</h4>
                                <select id="quickStageUpdate" onchange="updateCandidateStage(this.value)"
                                    class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                    <option value="">Select new stage</option>
                                    @foreach(App\Models\HrmCandidate::getStageOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ $candidate->stage == $value ? 'selected disabled' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Rating -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Rating</h4>
                                @if($candidate->rating)
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($candidate->rating))
                                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @elseif($i - 0.5 <= $candidate->rating)
                                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 1l2.4 7.2h7.6l-6 4.8 2.4 7.2-6-4.8-6 4.8 2.4-7.2-6-4.8h7.6z" />
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endif
                                        @endfor
                                        <span class="ml-2 text-sm font-medium text-gray-900">{{ number_format($candidate->rating, 1) }}/5.0</span>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 italic">Not rated yet</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2">Activity Timeline</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Candidate Added</p>
                                    <p class="text-xs text-gray-500">By {{ $candidate->addedBy->full_name ?? 'System' }}</p>
                                    <p class="text-xs text-gray-400">{{ $candidate->created_at->format('M d, Y • h:i A') }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Application Submitted</p>
                                    <p class="text-xs text-gray-500">For {{ $candidate->job->job_title ?? 'Position' }}</p>
                                    <p class="text-xs text-gray-400">{{ $candidate->applied_date ? $candidate->applied_date->format('M d, Y') : 'Date not specified' }}</p>
                                </div>
                            </div>

                            @if($candidate->stage == 'interviewing' && $candidate->interview_date)
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Interview Scheduled</p>
                                        <p class="text-xs text-gray-500">{{ $candidate->interviewer ? 'With ' . $candidate->interviewer : '' }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ $candidate->interview_date->format('M d, Y') }}
                                            @if($candidate->formatted_interview_time)
                                                • {{ $candidate->formatted_interview_time }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif

                            @if($candidate->updated_at->gt($candidate->created_at))
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Last Updated</p>
                                        <p class="text-xs text-gray-500">Profile information was modified</p>
                                        <p class="text-xs text-gray-400">{{ $candidate->updated_at->format('M d, Y • h:i A') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- System Information Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2">System Information</h3>
                        <div class="space-y-3">
                            <div>
                                <h4 class="text-xs font-medium text-gray-500 mb-1">Candidate ID</h4>
                                <p class="text-sm text-gray-900">#{{ str_pad($candidate->id, 6, '0', STR_PAD_LEFT) }}</p>
                            </div>
                            <div>
                                <h4 class="text-xs font-medium text-gray-500 mb-1">Created On</h4>
                                <p class="text-sm text-gray-900">{{ $candidate->created_at->format('F d, Y • h:i A') }}</p>
                            </div>
                            <div>
                                <h4 class="text-xs font-medium text-gray-500 mb-1">Last Updated</h4>
                                <p class="text-sm text-gray-900">{{ $candidate->updated_at->format('F d, Y • h:i A') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2">Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="{{ route('hrm.candidates.edit', $candidate->id) }}"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-[#0078C8] hover:bg-blue-50 rounded-md transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                                Edit Candidate
                            </a>
                            @if($candidate->resume_path)
                                <a href="{{ route('hrm.candidates.download-resume', $candidate->id) }}"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-green-600 hover:bg-green-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    Download Resume
                                </a>
                            @endif
                            @if($candidate->stage == 'interviewing')
                                <a href="{{ route('hrm.candidates.calendar') }}?month={{ $candidate->interview_date ? $candidate->interview_date->format('m') : date('m') }}&year={{ $candidate->interview_date ? $candidate->interview_date->format('Y') : date('Y') }}"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-purple-600 hover:bg-purple-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    View in Calendar
                                </a>
                            @endif
                            <button onclick="updateStageModal()"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-yellow-600 hover:bg-yellow-50 rounded-md transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/>
                                </svg>
                                Update Stage
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"
    class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>

<!-- Update Stage Modal -->
<div id="updateStageModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Candidate Stage</h3>
            <p class="text-sm text-gray-600 mb-6">Select the new stage for {{ $candidate->full_name }}.</p>

            <select id="stageSelect" class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm mb-6">
                <option value="">Select Stage</option>
                @foreach(App\Models\HrmCandidate::getStageOptions() as $value => $label)
                    <option value="{{ $value }}" {{ $candidate->stage == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="hideModal('updateStageModal')"
                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" onclick="updateStage()"
                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                    Update Stage
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.338 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Delete Candidate</h3>
                <p class="mt-2 text-sm text-gray-600">Are you sure you want to delete {{ $candidate->full_name }}? This action cannot be undone.</p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="hideModal('deleteModal')"
                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Cancel
                </button>
                <form id="deleteForm" action="{{ route('hrm.candidates.destroy', $candidate->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                        Delete Candidate
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Toggle dropdown
    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        dropdown.classList.toggle('hidden');
    }

    // Show modal
    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    // Hide modal
    function hideModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Show update stage modal
    window.updateStageModal = function() {
        showModal('updateStageModal');
        toggleDropdown('actionDropdown');
    }

    // Update stage via quick select
    window.updateCandidateStage = function(stage) {
        if (!stage) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`{{ route('hrm.candidates.update-stage', $candidate->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ stage: stage })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(data.message || 'Error updating stage', 'error');
            }
        })
        .catch(error => {
            showToast('Network error. Please try again.', 'error');
        });
    }

    // Update stage via modal
    window.updateStage = function() {
        const stage = document.getElementById('stageSelect').value;
        if (!stage) {
            showToast('Please select a stage', 'error');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`{{ route('hrm.candidates.update-stage', $candidate->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ stage: stage })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                hideModal('updateStageModal');
                showToast(data.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(data.message || 'Error updating stage', 'error');
            }
        })
        .catch(error => {
            showToast('Network error. Please try again.', 'error');
        });
    }

    // Confirm delete
    window.confirmDelete = function() {
        showModal('deleteModal');
        toggleDropdown('actionDropdown');
    }

    // Toast function
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

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#actionDropdown') && !e.target.closest('[onclick*="toggleDropdown"]')) {
            document.getElementById('actionDropdown').classList.add('hidden');
        }
    });

    // Check for success/error messages in session
    @if(session('success'))
        showToast('{{ session("success") }}', 'success');
    @endif

    @if(session('error'))
        showToast('{{ session("error") }}', 'error');
    @endif
</script>
@endpush
