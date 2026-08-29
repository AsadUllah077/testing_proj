<div class="overflow-x-auto w-full">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Candidate</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Job Applied</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Contact</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Stage</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Applied Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($candidates as $candidate)
                <tr id="row-{{ $candidate->id }}" class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $candidate->full_name }}</div>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $candidate->job->job_title ?? 'N/A' }}
                        </div>
                        @if ($candidate->source)
                            <div class="text-xs text-gray-500">
                                {{ App\Models\HrmCandidate::getSourceOptions()[$candidate->source] ?? $candidate->source }}
                            </div>
                        @endif
                    </td>

                    <td class="px-6 py-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                                <span class="text-gray-900 text-sm">{{ $candidate->email }}</span>
                            </div>
                            @if ($candidate->phone)
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                    </svg>
                                    <span class="text-gray-900 text-sm">{{ $candidate->phone }}</span>
                                </div>
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $candidate->stage_color }}">
                            {{ App\Models\HrmCandidate::getStageOptions()[$candidate->stage] ?? $candidate->stage }}
                        </span>
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $candidate->applied_date ? $candidate->applied_date->format('d M, Y') : 'N/A' }}
                        </div>
                        <div class="text-xs text-gray-500">
                            @if ($candidate->addedBy)
                                by {{ $candidate->addedBy->first_name . ' ' . $candidate->addedBy->last_name }}
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center relative">
                        <button
                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-2 rounded-lg hover:bg-gray-100 transition-colors"
                            data-id="{{ $candidate->id }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div id="dropdown-{{ $candidate->id }}"
                            class="dropdown-menu hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 z-20 overflow-hidden">
                            <a href="{{ route('hrm.candidates.show', $candidate->id) }}"
                                class="w-full text-left px-4 py-3 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-700 transition-colors border-b border-gray-100">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                View Details
                            </a>

                            <a href="{{ route('hrm.candidates.edit', $candidate->id) }}"
                                class="w-full text-left px-4 py-3 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-700 transition-colors border-b border-gray-100">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit Candidate
                            </a>

                            @if ($candidate->resume_path)
                                <a href="{{ route('hrm.candidates.download-resume', $candidate->id) }}"
                                    class="w-full text-left px-4 py-3 hover:bg-green-50 flex items-center gap-3 text-sm text-gray-700 hover:text-green-700 transition-colors border-b border-gray-100">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Download Resume
                                </a>
                            @endif

                            <button type="button"
                                class="delete-btn w-full text-left px-4 py-3 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-700 transition-colors"
                                data-url="{{ route('hrm.candidates.destroy', $candidate->id) }}"
                                data-row="#row-{{ $candidate->id }}" onclick="confirmDelete(this)">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Delete Candidate
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
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-4.201V8m0 0l3 3-3 3M4 8l3-3 3 3" />
                            </svg>
                            <p class="text-lg font-medium text-gray-600 mb-2">No candidates found</p>
                            <p class="text-sm text-gray-500">Add your first candidate to get started</p>
                            <a href="{{ route('hrm.candidates.create') }}"
                                class="mt-4 px-4 py-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] text-sm font-medium">
                                Add Candidate
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$candidates" />
