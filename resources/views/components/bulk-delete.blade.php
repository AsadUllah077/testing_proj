@props(['url' => null])

<div id="bulkActionsBar" class="hidden">
    <button onclick="showBulkDeleteModal()"
        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-all duration-200 flex items-center gap-2">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                clip-rule="evenodd" />
        </svg>
        Delete Selected (<span id="selectedCount">0</span>)
    </button>
</div>

<!-- Bulk Delete Form -->
<form id="bulkDeleteForm" action="{{ $url ? route($url) : '#' }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="ids" id="bulkDeleteIds">
</form>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" class="hidden fixed inset-0 bg-gray-900/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <!-- Warning Icon -->
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <!-- Modal Title -->
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirm Bulk Delete</h3>

            <!-- Modal Content -->
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete
                    <span id="modalSelectedCount" class="font-bold text-red-600">0</span>
                    selected item(s)?
                </p>
                <p class="text-xs text-red-600 font-medium mt-2">
                    This action cannot be undone!
                </p>
            </div>

            <!-- Modal Actions -->
            <div class="flex gap-3 px-4 py-3 mt-4">
                <button id="cancelBulkDelete" type="button"
                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-all duration-200">
                    Cancel
                </button>
                <button id="confirmBulkDelete" type="button"
                    class="flex-1 px-4 py-2 bg-red-600 text-white text-base font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-200">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Bulk selection functions
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.customer-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
            const count = checkboxes.length;
            const bulkBar = document.getElementById('bulkActionsBar');
            const selectAll = document.getElementById('selectAll');

            if (count > 0) {
                bulkBar.classList.remove('hidden');
                document.getElementById('selectedCount').textContent = count;
            } else {
                bulkBar.classList.add('hidden');
            }

            const allCheckboxes = document.querySelectorAll('.customer-checkbox');
            selectAll.checked = count === allCheckboxes.length && count > 0;
        }

        // Show bulk delete modal
        function showBulkDeleteModal() {
            const checkboxes = document.querySelectorAll('.customer-checkbox:checked');

            if (checkboxes.length === 0) {
                Notification.error('Please select at least one item to delete.');
                return;
            }

            // Update modal count
            document.getElementById('modalSelectedCount').textContent = checkboxes.length;

            // Show modal
            document.getElementById('bulkDeleteModal').classList.remove('hidden');
        }

        // Confirm bulk delete
        document.getElementById('confirmBulkDelete')?.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value).join(',');

            document.getElementById('bulkDeleteIds').value = ids;
            document.getElementById('bulkDeleteModal').classList.add('hidden');
            document.getElementById('bulkDeleteForm').submit();
        });

        // Cancel bulk delete
        document.getElementById('cancelBulkDelete')?.addEventListener('click', function() {
            document.getElementById('bulkDeleteModal').classList.add('hidden');
        });

        // Close modal when clicking outside
        document.getElementById('bulkDeleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('bulkDeleteModal')?.classList.add('hidden');
            }
        });
    </script>
@endpush
