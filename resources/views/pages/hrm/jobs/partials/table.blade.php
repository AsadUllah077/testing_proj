<div class="overflow-x-auto w-full">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicants
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted Date
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($jobs as $job)
                <tr id="row-{{ $job->id }}" class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z"
                                        clip-rule="evenodd" />
                                    <path
                                        d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $job->job_title }}</div>
                                <div class="text-xs text-gray-500">{{ $job->job_id }}</div>
                                <div class="flex items-center gap-1 mt-1">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $job->formatted_employment_type }}
                                    </span>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $job->formatted_experience_level }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $job->department->department ?? 'N/A' }}</div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-gray-900">{{ $job->location }}</span>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="text-center">
                                <div class="font-bold text-gray-900 text-lg">{{ $job->candidates_count }}</div>
                                <div class="text-xs text-gray-500">applicants</div>
                            </div>
                            @if ($job->applicants_count > 0)
                                <a href="{{ route('hrm.jobs.show', $job->id) }}#applications"
                                    class="text-xs text-[#0078C8] hover:text-[#006bb0] hover:underline font-medium">
                                    View
                                </a>
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $job->status_color }}">
                            {{ $statusOptions[$job->status] ?? $job->status }}
                        </span>
                    </td>

                    <td class="px-6 py-4">
                        @if ($job->posted_date)
                            <div class="text-sm font-medium text-gray-900">{{ $job->posted_date->format('d M, Y') }}
                            </div>
                            <div class="text-xs text-gray-500">
                                @if ($job->createdBy)
                                    by {{ $job->createdBy->first_name . ' ' . $job->createdBy->last_name }}
                                @endif
                            </div>
                        @else
                            <div class="text-sm text-gray-400 italic">Not posted</div>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-center relative">
                        <button
                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-2 rounded-lg hover:bg-gray-100 transition-colors"
                            data-id="{{ $job->id }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div id="dropdown-{{ $job->id }}"
                            class="dropdown-menu hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 z-20 overflow-hidden">
                            <a href="{{ route('hrm.jobs.show', $job->id) }}"
                                class="w-full text-left px-4 py-3 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-700 transition-colors border-b border-gray-100">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                View Details
                            </a>

                            <a href="{{ route('hrm.jobs.edit', $job->id) }}"
                                class="w-full text-left px-4 py-3 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-700 transition-colors border-b border-gray-100">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit Job
                            </a>

                            @if ($job->status === 'draft')
                                <button type="button" onclick="updateJobStatus({{ $job->id }}, 'open')"
                                    class="w-full text-left px-4 py-3 hover:bg-green-50 flex items-center gap-3 text-sm text-gray-700 hover:text-green-700 transition-colors border-b border-gray-100">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Publish Job
                                </button>
                            @endif

                            @if ($job->status === 'open')
                                <button type="button" onclick="updateJobStatus({{ $job->id }}, 'closed')"
                                    class="w-full text-left px-4 py-3 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-700 transition-colors border-b border-gray-100">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Close Job
                                </button>
                            @endif

                            <a href="{{ route('hrm.jobs.clone', $job->id) }}"
                                class="w-full text-left px-4 py-3 hover:bg-purple-50 flex items-center gap-3 text-sm text-gray-700 hover:text-purple-700 transition-colors border-b border-gray-100">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" />
                                    <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z" />
                                </svg>
                                Clone Job
                            </a>

                            <button type="button"
                                class="delete-btn w-full text-left px-4 py-3 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-700 transition-colors"
                                data-url="{{ route('hrm.jobs.destroy', $job->id) }}"
                                data-row="#row-{{ $job->id }}" onclick="confirmDelete(this)">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Delete Job
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <p class="text-lg font-medium text-gray-600 mb-2">No jobs found</p>
                            <p class="text-sm text-gray-500">Create your first job posting to get started</p>
                            <a href="{{ route('hrm.jobs.create') }}"
                                class="mt-4 px-4 py-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] text-sm font-medium">
                                Create New Job
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<x-data-table-paginatior :paginator="$jobs" />
