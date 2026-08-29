{{-- resources/views/pages/products/warehouse/technicians/partials/table.blade.php --}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="flex items-center">
                        <input id="checkAll" type="checkbox"
                            class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                        <label for="checkAll" class="ml-2 text-xs text-gray-600">Select</label>
                    </div>
                </th>
                <th scope="col" data-col="unique_id"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Unique ID
                </th>
                <th scope="col" data-col="category"
                    class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Category
                </th>
                <th scope="col" data-col="location"
                    class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Location
                </th>
                <th scope="col" data-col="task_type"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Task Type
                </th>
                <th scope="col" data-col="technician"
                    class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Technician
                </th>
                <th scope="col" data-col="status"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" data-col="assigned_date"
                    class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Assigned Date
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($assignments as $assignment)
                @php
                    $product = $assignment->product;
                    $status = $assignment->completed_at
                        ? 'COMPLETE'
                        : ($assignment->started_at
                            ? 'IN PROGRESS'
                            : 'PENDING');

                    $statusClass = match ($status) {
                        'COMPLETE' => 'bg-green-100 text-green-800',
                        'IN PROGRESS' => 'bg-blue-100 text-blue-800',
                        'PENDING' => 'bg-yellow-100 text-yellow-800',
                        default => 'bg-gray-100 text-gray-800',
                    };

                    $taskTypeLower = strtolower(trim($assignment->task_type ?? ''));
                    $stageName = strtolower(trim($product->workFlowStage->name ?? ''));
                    if (str_contains($taskTypeLower, 'functional') || str_contains($stageName, 'functional')) {
                        $urlType = 'functional';
                    } elseif (str_contains($taskTypeLower, 'visual') || str_contains($stageName, 'visual')) {
                        $urlType = 'visual';
                    } elseif (str_contains($taskTypeLower, 'calibration')) {
                        $urlType = 'calibration';
                    } elseif (str_contains($taskTypeLower, 'repair')) {
                        $urlType = 'repair';
                    } elseif (str_contains($taskTypeLower, 'quality')) {
                        $urlType = 'quality';
                    } else {
                        $urlType = 'general';
                    }
                    $taskTypeClass = match(true) {
                        str_contains($taskTypeLower, 'visual')      => 'bg-purple-100 text-purple-800',
                        str_contains($taskTypeLower, 'functional')  => 'bg-indigo-100 text-indigo-800',
                        str_contains($taskTypeLower, 'calibration') => 'bg-orange-100 text-orange-800',
                        str_contains($taskTypeLower, 'repair')      => 'bg-red-100 text-red-800',
                        str_contains($taskTypeLower, 'quality')     => 'bg-teal-100 text-teal-800',
                        default                                     => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition-colors" data-assignment-id="{{ $assignment->id }}">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <input type="checkbox"
                            class="rowCheck h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded"
                            value="{{ $assignment->id }}">
                    </td>
                    <td data-col="unique_id" class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900">
                        <a href="{{ route('warehouse.products.show', $product->id) }}" class="hover:text-[#0078C8]">
                            {{ $product->unique_id }}
                        </a>
                    </td>
                    <td data-col="category" class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $product->category->name ?? 'N/A' }}
                    </td>
                    <td data-col="location" class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $product->location ?? 'N/A' }}
                    </td>
                    <td data-col="task_type" class="px-4 py-3 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $taskTypeClass }}">
                            {{ $assignment->task_type }}
                        </span>
                    </td>
                    <td data-col="technician" class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm">
                        @if ($assignment->technician)
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                <i class="fas fa-user mr-1"></i>{{ $assignment->technician->full_name }}
                            </span>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td data-col="status" class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                            {{ $status }}
                        </span>
                        @if ($assignment->priority && $status == 'PENDING')
                            <span
                                class="ml-1 px-2 py-0.5 text-xs rounded-full
                            {{ $assignment->priority == 'urgent'
                                ? 'bg-red-100 text-red-800'
                                : ($assignment->priority == 'high'
                                    ? 'bg-orange-100 text-orange-800'
                                    : ($assignment->priority == 'medium'
                                        ? 'bg-yellow-100 text-yellow-800'
                                        : 'bg-green-100 text-green-800')) }}">
                                {{ ucfirst($assignment->priority) }}
                            </span>
                        @endif
                    </td>
                    <td data-col="assigned_date" class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $assignment->assigned_at ? $assignment->assigned_at->format('M d, Y') : 'N/A' }}
                        @if ($assignment->due_date)
                            <div
                                class="text-xs {{ now() > $assignment->due_date ? 'text-red-600' : 'text-gray-500' }}">
                                Due: {{ $assignment->due_date->format('M d') }}
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                        <div class="relative inline-block">
                            <button
                                class="dropdown-btn inline-flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none">
                                <span class="sr-only">Actions</span>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div
                                class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded shadow-lg z-10">
                                <div class="py-1">
                                    <a href="{{ route('warehouse.technicians.show', $assignment->id) }}"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-info-circle mr-2 text-blue-600"></i>View Detail
                                    </a>

                                    <div class="border-t border-gray-100 my-1"></div>

                                    @if ($status == 'PENDING')
                                        @if(auth()->user()->hasPermission('warehouse.technicians.start-task'))
                                        <button onclick="startTask({{ $assignment->id }}, '{{ addslashes($assignment->task_type ?? '') }}')"
                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-play mr-2 text-green-600"></i>Start Task
                                        </button>
                                        @endif
                                    @endif

                                    @if ($status == 'IN PROGRESS')
                                        @if(auth()->user()->hasPermission('warehouse.technicians.submit-result'))
                                        <a href="{{ route('warehouse.technicians.submit.result', ['id' => $assignment->id, 'type' => $urlType]) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-check-circle mr-2 text-blue-600"></i>Submit Result
                                        </a>
                                        @endif
                                    @endif

                                    @if ($status == 'COMPLETE')
                                        @if(auth()->user()->hasPermission('warehouse.technicians.view-result'))
                                        <a href="{{ route('warehouse.technicians.view.result', ['id' => $assignment->id]) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-eye mr-2 text-green-600"></i>View Result
                                        </a>
                                        @endif
                                        @if(auth()->user()->hasPermission('warehouse.technicians.submit-result'))
                                        <a href="{{ route('warehouse.technicians.submit.result', ['id' => $assignment->id, 'type' => $urlType, 'edit' => 1]) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-edit mr-2 text-indigo-600"></i>Edit Result
                                        </a>
                                        @endif
                                    @endif

                                    {{-- <a href="{{ route('warehouse.products.show', $product->id) }}"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-info-circle mr-2 text-blue-600"></i>View Product
                                    </a> --}}

                                    <div class="border-t border-gray-100 my-1"></div>

                                    @if ($status == 'COMPLETE' && ($assignment->result ?? '') == 'failed')
                                        @if(auth()->user()->hasPermission('warehouse.technicians.retry-task'))
                                        <button onclick="retryTask({{ $assignment->id }})"
                                            class="w-full text-left px-4 py-2 text-sm text-orange-600 hover:bg-gray-100">
                                            <i class="fas fa-redo mr-2"></i>Retry Task
                                        </button>
                                        @endif
                                    @endif

                                    @if(auth()->user()->hasPermission('warehouse.technicians.delete'))
                                    <button onclick="confirmDelete(this)"
                                        data-url="{{ route('warehouse.technicians.destroy', $assignment->id) }}"
                                        data-row="tr[data-assignment-id='{{ $assignment->id }}']"
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                        <i class="fas fa-trash mr-2"></i>Delete
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                            <p class="text-gray-600 font-medium text-lg">No assignments found</p>
                            <p class="text-gray-500 text-sm mt-1 mb-4">Click the BULK ASSIGN button to create new
                                assignments</p>
                            {{-- <button type="button" id="emptyBulkAssignBtn"
                                class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors inline-flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Bulk Assign
                            </button> --}}
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if (isset($assignments) && $assignments->hasPages())
    <div id="pagination-wrapper" class="px-4 py-3 border-t border-gray-200">
        {{ $assignments->withQueryString()->links() }}
    </div>
@endif

<script>
    // Re-initialize checkboxes after table reload
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initializeCheckboxes === 'function') {
            initializeCheckboxes();
            updateSelectedCount();
        }
    });

    // Function for starting a task
    function startTask(assignmentId, taskType) {
        if (confirm('Start this task?')) {
            fetch(`/warehouse/technicians/start-task/${assignmentId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const t = (taskType || '').toLowerCase();
                        let type = 'general';
                        if (t.includes('visual'))       type = 'visual';
                        else if (t.includes('functional')) type = 'functional';
                        else if (t.includes('calibration')) type = 'calibration';
                        else if (t.includes('repair'))  type = 'repair';
                        else if (t.includes('quality')) type = 'quality';

                        window.location.href = `/warehouse/technicians/submit-result/${assignmentId}/${type}`;
                    } else {
                        showToast(data.message || 'Failed to start task', 'error');
                    }
                })
                .catch(error => {
                    showToast('Network error. Please try again.', 'error');
                });
        }
    }

    // Function for retrying a failed task
    function retryTask(assignmentId) {
        if (confirm('Retry this task?')) {
            fetch(`/warehouse/technicians/retry-task/${assignmentId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Task retry initiated', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast(data.message || 'Failed to retry task', 'error');
                    }
                })
                .catch(error => {
                    showToast('Network error. Please try again.', 'error');
                });
        }
    }

    // Empty state bulk assign button
    document.getElementById('emptyBulkAssignBtn')?.addEventListener('click', function() {
        document.getElementById('bulkAssignBtn').click();
    });
</script>
