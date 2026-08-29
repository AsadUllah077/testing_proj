<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                <th class="px-6 py-4" data-col="resign_employee">Employee</th>
                <th class="px-6 py-4" data-col="resign_date">Resign Date</th>
                <th class="px-6 py-4" data-col="resign_last_day">Last Working Day</th>
                <th class="px-6 py-4" data-col="resign_title">Title</th>
                <th class="px-6 py-4" data-col="resign_description">Description</th>
                <th class="px-6 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($resignations as $resignation)
                <tr class="hover:bg-gray-50/80 transition-colors" id="row-{{ $resignation->id }}">
                    <td class="px-6 py-4" data-col="resign_employee">
                        <div class="font-medium text-gray-900">{{ $resignation->employee->full_name ?? 'N/A' }}</div>
                        <div class="text-xs text-gray-500">{{ $resignation->employee->email ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4 text-gray-600" data-col="resign_date">
                        {{ $resignation->resign_date ? $resignation->resign_date->format('M d, Y') : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-gray-600 font-medium" data-col="resign_last_day">
                        {{ $resignation->last_working_day ? $resignation->last_working_day->format('M d, Y') : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-gray-800 font-medium" data-col="resign_title">
                        {{ $resignation->title }}
                    </td>
                    <td class="px-6 py-4 text-gray-500 max-w-xs truncate" data-col="resign_description">
                        {{ $resignation->description ?? 'No description' }}
                    </td>
                    <td class="px-6 py-4 text-right relative">
                        <div class="inline-block text-left">
                            <button type="button" class="dropdown-btn p-1.5 hover:bg-gray-100 rounded-lg transition-colors text-gray-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div class="dropdown-menu absolute right-4 mt-1 w-36 bg-white border border-gray-200 rounded-lg shadow-lg py-1 hidden z-[100]">
                                @if (auth()->user()->hasPermission('hrm.resignations.edit'))
                                    <a href="{{ route('hrm.resignations.edit', $resignation->id) }}" class="flex items-center px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </a>
                                @endif
                                @if (auth()->user()->hasPermission('hrm.resignations.delete'))
                                    <button type="button" onclick="confirmDeleteResignation({{ $resignation->id }})" class="flex items-center w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-red-50">
                                        <svg class="w-4 h-4 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                        No resignations found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-6 border-t border-gray-100 flex items-center justify-between" id="pagination-wrapper">
    {{ $resignations->links() }}
</div>
