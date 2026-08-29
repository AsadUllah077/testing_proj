<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <input id="checkAllPS" type="checkbox"
                            class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                        <label for="checkAllPS" class="text-xs text-gray-600">Select</label>
                    </div>
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    S.No
                </th>
                <th scope="col" data-col="date" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                </th>
                <th scope="col" data-col="name" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                </th>
                <th scope="col" data-col="working" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Working
                </th>
                <th scope="col" data-col="quantity" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Quantity
                </th>
                <th scope="col" data-col="remarks" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Remarks
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($sheets as $sheet)
                <tr class="hover:bg-gray-50 transition-colors" data-ps-id="{{ $sheet->id }}">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <input type="checkbox" class="rowCheckPS h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded"
                            value="{{ $sheet->id }}">
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $sheets->firstItem() + $loop->index }}
                    </td>
                    <td data-col="date" class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $sheet->date->format('d-m-Y') }}
                    </td>
                    <td data-col="name" class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $sheet->name }}
                    </td>
                    <td data-col="working" class="hidden sm:table-cell px-4 py-3 text-sm text-gray-700 max-w-xs">
                        <div class="truncate" title="{{ $sheet->working }}">{{ $sheet->working }}</div>
                    </td>
                    <td data-col="quantity" class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-700">
                        {{ $sheet->quantity ?? '—' }}
                    </td>
                    <td data-col="remarks" class="hidden sm:table-cell px-4 py-3 text-sm text-gray-600 max-w-xs">
                        <div class="truncate" title="{{ $sheet->remarks }}">{{ $sheet->remarks ?? '—' }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                        <div class="relative">
                            <button class="dropdown-btn flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div class="dropdown-menu hidden absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded shadow-md z-10">
                                @if(auth()->user()->hasPermission('warehouse.progress-sheet.edit'))
                                <a href="{{ route('warehouse.progress-sheet.edit', $sheet->id) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Edit
                                </a>
                                @endif
                                @if(auth()->user()->hasPermission('warehouse.progress-sheet.delete'))
                                <button onclick="deletePS({{ $sheet->id }}, '{{ route('warehouse.progress-sheet.destroy', $sheet->id) }}')"
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    Delete
                                </button>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-14 h-14 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-600 font-medium">No progress sheet entries found</p>
                            <p class="text-gray-400 text-sm mt-1 mb-4">Click ADD to create your first entry</p>
                            <a href="{{ route('warehouse.progress-sheet.create') }}"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors inline-flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                ADD ENTRY
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($sheets->hasPages())
    <div id="pagination-wrapper" class="px-6 py-4 border-t border-gray-200">
        {{ $sheets->withQueryString()->links() }}
    </div>
@endif
