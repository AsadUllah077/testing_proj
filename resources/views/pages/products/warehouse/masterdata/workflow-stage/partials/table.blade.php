{{-- resources/views/pages/products/warehouse/masterdata/workflow-stage/partials/table.blade.php --}}
<div class="overflow-x-auto">
    <table class="w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input type="checkbox" id="checkAll"
                        class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                </th>
                <th scope="col"
                    class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Created At
                </th>
                <th scope="col"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($stages as $stage)
                <tr data-row="{{ $stage->id }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" value="{{ $stage->id }}"
                            class="rowCheck h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $stage->name }}</div>
                        <div class="text-xs text-gray-400 sm:hidden mt-0.5">{{ $stage->created_at->format('d-m-Y') }}</div>
                    </td>
                    <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $stage->created_at->format('d-m-Y') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="relative">
                            <button
                                class="dropdown-btn flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div
                                class="dropdown-menu hidden absolute  mt-1 w-32 bg-white border border-gray-200 rounded shadow-md z-10">
                                @if(auth()->user()->hasPermission('warehouse.master-data.workflow-stages.edit'))
                                <a href="{{ route('warehouse.master-data.workflow-stages.edit', $stage->id) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                    Edit
                                </a>
                                @endif
                                @if(auth()->user()->hasPermission('warehouse.master-data.workflow-stages.delete'))
                                <button onclick="confirmDelete(this)"
                                    data-url="{{ route('warehouse.master-data.workflow-stages.destroy', $stage->id) }}"
                                    data-row="tr[data-row='{{ $stage->id }}']"
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-900">
                                    Delete
                                </button>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                        No workflow stages found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if ($stages->hasPages())
    <div id="pagination-wrapper" class="px-6 py-4 border-t border-gray-200">
        {{ $stages->withQueryString()->links() }}
    </div>
@endif
