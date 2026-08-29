<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                <th class="px-6 py-4" data-col="survey_name">Survey</th>
                <th class="px-6 py-4" data-col="survey_status">Status</th>
                <th class="px-6 py-4" data-col="survey_period">Period</th>
                <th class="px-6 py-4" data-col="survey_responses">Responses</th>
                <th class="px-6 py-4" data-col="survey_anonymous">Anonymous</th>
                <th class="px-6 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($surveys as $survey)
            <tr class="hover:bg-gray-50/80 transition-colors" id="row-{{ $survey->id }}">
                <td class="px-6 py-4" data-col="survey_name">
                    <div class="font-medium text-gray-900 max-w-xs">{{ $survey->title }}</div>
                    @if($survey->description)
                    <div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ Str::limit($survey->description, 60) }}</div>
                    @endif
                </td>
                <td class="px-6 py-4" data-col="survey_status">
                    @php
                    $statusMap = [
                        'Draft'     => 'bg-amber-100 text-amber-700',
                        'Published' => 'bg-green-100 text-green-700',
                        'Closed'    => 'bg-red-100 text-red-700',
                    ];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusMap[$survey->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $survey->status }}
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-600 text-xs" data-col="survey_period">
                    <div>{{ $survey->start_date?->format('M d, Y') }}</div>
                    <div class="text-gray-400">to {{ $survey->end_date?->format('M d, Y') }}</div>
                </td>
                <td class="px-6 py-4" data-col="survey_responses">
                    <span class="inline-flex items-center gap-1.5 text-gray-700 font-medium">
                        <i class="fa-solid fa-users text-[#0078C8] text-xs"></i>
                        {{ $survey->responses_count ?? 0 }}
                    </span>
                </td>
                <td class="px-6 py-4" data-col="survey_anonymous">
                    @if($survey->is_anonymous)
                        <span class="inline-flex items-center gap-1 text-xs text-purple-600 font-medium">
                            <i class="fa-solid fa-user-secret"></i> Yes
                        </span>
                    @else
                        <span class="text-xs text-gray-400">No</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right relative">
                    <div class="inline-block text-left">
                        <button type="button" class="dropdown-btn p-1.5 hover:bg-gray-100 rounded-lg transition-colors text-gray-500">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute right-6 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg py-1 hidden z-[100]">
                            @if(auth()->user()->hasPermission('hrm.surveys.analytics'))
                            <a href="{{ route('hrm.surveys.analytics', $survey->id) }}" class="flex items-center px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                <i class="fa-solid fa-chart-bar w-4 mr-2 text-gray-400"></i> Analytics
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('hrm.surveys.participate'))
                            <a href="{{ route('hrm.surveys.take', $survey->id) }}" class="flex items-center px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                <i class="fa-solid fa-clipboard-list w-4 mr-2 text-gray-400"></i> Take Survey
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('hrm.surveys.edit'))
                            <a href="{{ route('hrm.surveys.edit', $survey->id) }}" class="flex items-center px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                <i class="fa-solid fa-pen w-4 mr-2 text-gray-400"></i> Edit
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('hrm.surveys.analytics'))
                            <a href="{{ route('hrm.surveys.export', $survey->id) }}" class="flex items-center px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                <i class="fa-solid fa-file-csv w-4 mr-2 text-gray-400"></i> Export CSV
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('hrm.surveys.delete'))
                            <button type="button" onclick="deleteSurvey({{ $survey->id }})" class="flex items-center w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-trash w-4 mr-2 text-red-400"></i> Delete
                            </button>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center gap-3 text-gray-400">
                        <i class="fa-solid fa-square-poll-vertical text-4xl opacity-30"></i>
                        <p class="text-sm">No surveys found.</p>
                        @if(auth()->user()->hasPermission('hrm.surveys.create'))
                        <a href="{{ route('hrm.surveys.create') }}" class="text-[#0078C8] text-xs hover:underline">Create your first survey →</a>
                        @endif
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($surveys instanceof \Illuminate\Contracts\Pagination\Paginator && $surveys->hasPages())
    <div class="px-6 py-4 border-t border-gray-100" id="pagination-wrapper">
        {{ $surveys->links() }}
    </div>
@endif
