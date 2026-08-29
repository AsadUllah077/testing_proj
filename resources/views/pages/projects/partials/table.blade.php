<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th data-col="name"     class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Project</th>
                <th data-col="client"   class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Client</th>
                <th data-col="team"     class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Team</th>
                <th data-col="manager"  class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Manager</th>
                <th data-col="timeline" class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Timeline</th>
                <th data-col="priority" class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Priority</th>
                <th data-col="status"   class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th data-col="progress" class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Progress</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($projects as $project)
            <tr class="hover:bg-gray-50 transition-colors" data-row="{{ $project->id }}">
                <td data-col="name" class="px-4 py-3">
                    <a href="{{ route('project.projects.show', $project) }}"
                        class="font-medium text-[#0078C8] hover:underline">{{ $project->name }}</a>
                    <div class="text-xs text-gray-400 mt-0.5">
                        {{ $project->tasks_count ?? $project->tasks->count() }} tasks
                    </div>
                </td>
                <td data-col="client"   class="px-4 py-3 text-gray-700 hidden sm:table-cell">{{ $project->clientRecord?->name ?? $project->client ?? '—' }}</td>
                <td data-col="team"     class="px-4 py-3 text-gray-700 hidden md:table-cell">
                    @if($project->teams->isNotEmpty())
                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-medium">{{ $project->teams->first()->name }}</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td data-col="manager"  class="px-4 py-3 text-gray-700 hidden md:table-cell">{{ $project->manager?->full_name ?? '—' }}</td>
                <td data-col="timeline" class="px-4 py-3 text-gray-500 text-xs hidden md:table-cell">
                    @if($project->start_date)
                        {{ $project->start_date->format('d M Y') }}
                        @if($project->end_date) — {{ $project->end_date->format('d M Y') }} @endif
                    @else
                        —
                    @endif
                </td>
                <td data-col="priority" class="px-4 py-3 hidden md:table-cell">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $project->priority_color }}">
                        {{ ucfirst($project->priority) }}
                    </span>
                </td>
                <td data-col="status" class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $project->status_color }}">
                        {{ ucfirst(str_replace('-', ' ', $project->status)) }}
                    </span>
                </td>
                <td data-col="progress" class="px-4 py-3 hidden sm:table-cell">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-1.5 min-w-[60px]">
                            <div class="bg-[#0078C8] h-1.5 rounded-full" style="width: {{ $project->progress }}%"></div>
                        </div>
                        <span class="text-xs text-gray-500 w-8 text-right">{{ $project->progress }}%</span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="relative">
                        <button class="dropdown-btn p-1.5 rounded hover:bg-gray-100 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                            </svg>
                        </button>
                        <div class="dropdown-menu hidden absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                            <a href="{{ route('project.projects.show', $project) }}"
                                class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                View
                            </a>
                            @if(auth()->user()->hasPermission('project.projects.edit'))
                            <a href="{{ route('project.projects.edit', $project) }}"
                                class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                </svg>
                                Edit
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('project.projects.delete'))
                            <button onclick="confirmDelete(this)"
                                data-url="{{ route('project.projects.destroy', $project) }}"
                                data-row="tr[data-row='{{ $project->id }}']"
                                class="flex items-center gap-2 w-full px-4 py-2 text-xs text-red-600 hover:bg-red-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Delete
                            </button>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-12 text-center text-gray-400 text-sm">
                    No projects found.
                    @if(auth()->user()->hasPermission('project.projects.create'))
                        <a href="{{ route('project.projects.create') }}" class="text-[#0078C8] hover:underline ml-1">Create one now</a>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div id="pagination-wrapper" class="px-4 py-3 border-t border-gray-200">
    {{ $projects->links() }}
</div>
