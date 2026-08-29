<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                <th class="px-6 py-4" data-col="comp_title">Title</th>
                <th class="px-6 py-4" data-col="comp_date">Date</th>
                <th class="px-6 py-4" data-col="comp_from">From</th>
                <th class="px-6 py-4" data-col="comp_against">Against</th>
                <th class="px-6 py-4" data-col="comp_attachment">Attachment</th>
                <th class="px-6 py-4" data-col="comp_description">Description</th>
                <th class="px-6 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($complaints as $complaint)
                <tr class="hover:bg-gray-50/80 transition-colors" id="row-{{ $complaint->id }}">
                    <td class="px-6 py-4 text-gray-800 font-medium" data-col="comp_title">
                        {{ $complaint->title }}
                    </td>
                    <td class="px-6 py-4 text-gray-600" data-col="comp_date">
                        {{ $complaint->date ? $complaint->date->format('M d, Y') : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-gray-900" data-col="comp_from">
                        {{ $complaint->fromEmployee->full_name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-gray-900" data-col="comp_against">
                        {{ $complaint->againstEmployee->full_name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4" data-col="comp_attachment">
                        @if($complaint->file_path)
                            <a href="{{ route('hrm.complaints.download', $complaint->id) }}" class="inline-flex items-center text-xs text-[#0078C8] hover:underline font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download
                            </a>
                        @else
                            <span class="text-xs text-gray-400">No file</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500 max-w-xs truncate" data-col="comp_description">
                        {{ $complaint->description ?? 'No description' }}
                    </td>
                    <td class="px-6 py-4 text-right relative">
                        <div class="inline-block text-left">
                            <button type="button" class="dropdown-btn p-1.5 hover:bg-gray-100 rounded-lg transition-colors text-gray-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div class="dropdown-menu absolute right-4 mt-1 w-36 bg-white border border-gray-200 rounded-lg shadow-lg py-1 hidden z-[100]">
                                @if (auth()->user()->hasPermission('hrm.complaints.edit'))
                                    <a href="{{ route('hrm.complaints.edit', $complaint->id) }}" class="flex items-center px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </a>
                                @endif
                                @if (auth()->user()->hasPermission('hrm.complaints.delete'))
                                    <button type="button" onclick="confirmDeleteComplaint({{ $complaint->id }})" class="flex items-center w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-red-50">
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
                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                        No complaints found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-6 border-t border-gray-100 flex items-center justify-between" id="pagination-wrapper">
    {{ $complaints->links() }}
</div>
