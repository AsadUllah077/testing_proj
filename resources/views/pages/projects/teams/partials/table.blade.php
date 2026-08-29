<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Team Name</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Project</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($teams as $team)
            <tr class="hover:bg-gray-50 transition-colors" data-row="{{ $team->id }}">
                <td class="px-4 py-3">
                    <span class="font-medium text-gray-900">{{ $team->name }}</span>
                </td>
                <td class="px-4 py-3">
                    @if($team->project)
                        <span class="text-sm text-gray-700">{{ $team->project->name }}</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $team->created_at->format('M d, Y') }}</td>
                <td class="px-4 py-3">
                    <div class="relative">
                        <button class="dropdown-btn p-1.5 rounded hover:bg-gray-100 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                            </svg>
                        </button>
                        <div class="dropdown-menu hidden absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                            @if(auth()->user()->hasPermission('project.teams.edit'))
                            <a href="{{ route('project.teams.edit', $team) }}"
                                class="flex items-center gap-2 w-full px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                </svg>
                                Edit
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('project.teams.delete'))
                            <button onclick="confirmDelete(this)"
                                data-url="{{ route('project.teams.destroy', $team) }}"
                                data-row="tr[data-row='{{ $team->id }}']"
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
                <td colspan="4" class="px-4 py-12 text-center text-gray-400 text-sm">
                    No teams found.
                    @if(auth()->user()->hasPermission('project.teams.create'))
                        <a href="{{ route('project.teams.create') }}" class="text-[#0078C8] hover:underline ml-1">Create one now</a>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div id="pagination-wrapper" class="px-4 py-3 border-t border-gray-200">
    {{ $teams->links() }}
</div>
