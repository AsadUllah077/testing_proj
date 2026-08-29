@extends('layouts.auth')

@section('title', 'Pending Leave Requests')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.leave-management') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl  md:mb-0 mb-2">Pending Leave Requests</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-2 relative">
                            <!-- Export Dropdown -->
                            <div class="relative w-full">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                    EXPORT ▼
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm">
                                        <li>
                                            <button onclick="startExport('pdf','leave-pending')" data-url=""
                                                class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="startExport('xlsx','leave-pending')"
                                                class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Excel
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Refresh Button -->
                            <button onclick="window.location.reload()"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                REFRESH
                            </button>
                        </div>
                    </div>

                    {{-- Tabs for Status --}}
                    <div class="bg-white rounded-lg border border-gray-200 mt-4">
                        <div class="border-b border-gray-200">
                            <nav class="flex space-x-4 px-6" aria-label="Tabs">
                                <a href="{{ route('hrm.leave.pending') }}"
                                    class="tab-link {{ !request('status') ? 'border-[#0078C8] text-[#0078C8]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Pending
                                    <span id="pending-count"
                                        class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ !request('status') ? 'bg-[#0078C8] text-white' : 'bg-gray-100 text-gray-900' }}">
                                        {{ $pendingCount }}
                                    </span>
                                </a>
                                <a href="{{ route('hrm.leave.pending', ['status' => 'approved']) }}"
                                    class="tab-link {{ request('status') == 'approved' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Approved
                                    <span id="approved-count"
                                        class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ request('status') == 'approved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-900' }}">
                                        {{ $approvedCount }}
                                    </span>
                                </a>
                                <a href="{{ route('hrm.leave.pending', ['status' => 'rejected']) }}"
                                    class="tab-link {{ request('status') == 'rejected' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Rejected
                                    <span id="rejected-count"
                                        class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ request('status') == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-900' }}">
                                        {{ $rejectedCount }}
                                    </span>
                                </a>
                                <a href="{{ route('hrm.leave.index') }}"
                                    class="tab-link border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    All Records
                                </a>
                            </nav>
                        </div>
                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-4">
                        <!-- Filters Section -->
                        <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                            <form method="GET" id="filter-form"
                                class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4 items-end">
                                <input type="hidden" name="status"
                                    value="{{ request()->has('status') ? request('status') : 'pending' }}">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Employee</label>
                                    <select name="employee_id" id="employee_id"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm">
                                        <option value="">All Employees</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}"
                                                {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                                {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Leave Type</label>
                                    <select name="leave_type_id" id="leave_type_id"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm">
                                        <option value="">All Types</option>
                                        @foreach ($leaveTypes as $leaveType)
                                            <option value="{{ $leaveType?->id }}"
                                                {{ request('leave_type_id') == $leaveType?->id ? 'selected' : '' }}>
                                                {{ $leaveType?->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                    <input type="date" name="from_date" id="from_date"
                                        value="{{ request('from_date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                    <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <input type="text" name="q" placeholder="Search..." id="search"
                                        value="{{ request('q') }}"
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <x-default-page />
                                </div>

                                <x-filter-button />
                            </form>
                        </div>

                        <!-- Table -->
                        <div id="leave-table">
                            @include('pages.hrm.leave.partials.pending_table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    <!-- Approve/Reject Modal -->
    <div id="actionModal" class="hidden fixed inset-0 bg-gray-900/50 z-[9999] flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"></h3>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4">
                <p id="modalMessage" class="text-sm text-gray-600 mb-4"></p>

                <div>
                    <label for="remarksField" class="block text-sm font-medium text-gray-700 mb-2">
                        <span id="remarksLabel">Remarks</span>
                        <span id="remarksRequired" class="text-red-500 hidden">*</span>
                    </label>
                    <textarea id="remarksField" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"
                        placeholder="Enter remarks here..."></textarea>
                    <p id="remarksError" class="hidden text-xs text-red-600 mt-1">This field is required</p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" id="cancelAction"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="button" id="confirmAction"
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 flex items-center gap-2">
                    <span id="confirmBtnText">Confirm</span>
                    <svg id="confirmBtnLoader" class="hidden animate-spin h-4 w-4 text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <x-delete-confirmation />
@endsection

@push('scripts')
    <script>
        // ------ Dropdown toggle for each row ------
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                // Toggle current dropdown
                menu.classList.toggle('hidden');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // ------ Export dropdown ------
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        // ------ Toast helpers ------
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toast-content');
            toast.classList.remove('hidden');
            content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    ${type === 'success'
                        ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                        : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;
            setTimeout(hideToast, 3000);
        }

        function hideToast() {
            document.getElementById('toast').classList.add('hidden');
        }
        window.hideToast = hideToast;

        // ------ Delete via modal + AJAX ------
        let deleteTarget = {
            url: null,
            rowSel: null
        };
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function confirmDelete(btnEl) {
            deleteTarget = {
                url: btnEl.dataset.url,
                rowSel: btnEl.dataset.row
            };
            deleteModal?.classList.remove('hidden');
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
        window.confirmDelete = confirmDelete;

        cancelDelete?.addEventListener('click', () => {
            deleteModal?.classList.add('hidden');
            deleteTarget = {
                url: null,
                rowSel: null
            };
        });

        // AJAX DELETE
        confirmDeleteBtn?.addEventListener('click', async () => {
            if (!deleteTarget.url) return;

            try {
                const res = await fetch(deleteTarget.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await res.json();

                if (res.ok && data?.success) {
                    // remove row
                    if (deleteTarget.rowSel) {
                        const row = document.querySelector(deleteTarget.rowSel);
                        row?.parentNode?.removeChild(row);
                    }
                    showToast(data.message || 'Leave record deleted successfully!', 'success');

                    // Update counts if provided
                    if (data.counts) {
                        updateStatusCounts(data.counts);
                    }
                } else {
                    const msg = data?.message || 'Failed to delete leave record.';
                    showToast(msg, 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                deleteModal?.classList.add('hidden');
                deleteTarget = {
                    url: null,
                    rowSel: null
                };
            }
        });

        // Close modal when clicking outside
        deleteModal?.addEventListener('click', (e) => {
            if (e.target === deleteModal) {
                deleteModal.classList.add('hidden');
                deleteTarget = {
                    url: null,
                    rowSel: null
                };
            }
        });

        // Modal management for approve/reject
        let currentAction = {
            type: null, // 'approve' or 'reject'
            leaveId: null
        };

        const actionModal = document.getElementById('actionModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const remarksField = document.getElementById('remarksField');
        const remarksLabel = document.getElementById('remarksLabel');
        const remarksRequired = document.getElementById('remarksRequired');
        const remarksError = document.getElementById('remarksError');
        const cancelActionBtn = document.getElementById('cancelAction');
        const confirmActionBtn = document.getElementById('confirmAction');
        const confirmBtnText = document.getElementById('confirmBtnText');
        const confirmBtnLoader = document.getElementById('confirmBtnLoader');

        function openActionModal(type, leaveId) {
            currentAction = {
                type,
                leaveId
            };

            // Reset form and buttons
            remarksField.value = '';
            remarksError.classList.add('hidden');
            confirmActionBtn.disabled = false;
            confirmBtnLoader.classList.add('hidden');
            cancelActionBtn.disabled = false;

            if (type === 'approve') {
                modalTitle.textContent = 'Approve Leave Request';
                modalMessage.textContent = 'Are you sure you want to approve this leave request?';
                remarksLabel.textContent = 'Remarks (Optional)';
                remarksRequired.classList.add('hidden');
                remarksField.placeholder = 'Add optional remarks for approval...';
                confirmBtnText.textContent = 'Approve';
                confirmActionBtn.className =
                    'px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 flex items-center gap-2';
            } else if (type === 'reject') {
                modalTitle.textContent = 'Reject Leave Request';
                modalMessage.textContent = 'Please provide a reason for rejecting this leave request.';
                remarksLabel.textContent = 'Rejection Reason';
                remarksRequired.classList.remove('hidden');
                remarksField.placeholder = 'Enter reason for rejection (required)...';
                confirmBtnText.textContent = 'Reject';
                confirmActionBtn.className =
                    'px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 flex items-center gap-2';
            }

            actionModal.classList.remove('hidden');
            remarksField.focus();
        }

        function closeActionModal(force = false) {
            if (!force && confirmActionBtn.disabled) return; // Don't close while processing unless forced

            actionModal.classList.add('hidden');
            currentAction = {
                type: null,
                leaveId: null
            };
            remarksField.value = '';
            remarksError.classList.add('hidden');

            // Ensure buttons are re-enabled for next time
            confirmActionBtn.disabled = false;
            cancelActionBtn.disabled = false;
            confirmBtnLoader.classList.add('hidden');
        }

        // Cancel button
        cancelActionBtn?.addEventListener('click', closeActionModal);

        // Close modal when clicking outside
        actionModal?.addEventListener('click', (e) => {
            if (e.target === actionModal) {
                closeActionModal();
            }
        });

        // Confirm button - handles both approve and reject
        confirmActionBtn?.addEventListener('click', async () => {
            const remarks = remarksField.value.trim();

            // Validate for reject action
            if (currentAction.type === 'reject' && !remarks) {
                remarksError.classList.remove('hidden');
                remarksField.focus();
                return;
            }

            remarksError.classList.add('hidden');

            // Show loading state
            confirmActionBtn.disabled = true;
            cancelActionBtn.disabled = true;
            confirmBtnLoader.classList.remove('hidden');

            // Close dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));

            try {
                const res = await fetch(`/hrm/leave/${currentAction.leaveId}/update-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        status: currentAction.type === 'approve' ? 'approved' : 'rejected',
                        remarks: remarks || null
                    })
                });
                const data = await res.json();

                if (res.ok && data?.success) {
                    const actionText = currentAction.type === 'approve' ? 'approved' : 'rejected';
                    showToast(data.message ||
                        `Leave ${actionText} successfully! Notification sent to employee.`, 'success');

                    // Remove the row from the table
                    const row = document.getElementById(`row-${currentAction.leaveId}`);
                    row?.parentNode?.removeChild(row);

                    // Update counts if provided
                    if (data.counts) {
                        updateStatusCounts(data.counts);
                    }

                    closeActionModal(true);
                } else {
                    showToast(data?.message || `Failed to ${currentAction.type} leave.`, 'error');
                    // Reset loading state on error
                    confirmActionBtn.disabled = false;
                    cancelActionBtn.disabled = false;
                    confirmBtnLoader.classList.add('hidden');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
                // Reset loading state on error
                confirmActionBtn.disabled = false;
                cancelActionBtn.disabled = false;
                confirmBtnLoader.classList.add('hidden');
            }
        });

        // Approve/Reject functions - now open modal instead of prompt
        function approveLeave(id) {
            openActionModal('approve', id);
        }

        function rejectLeave(id) {
            openActionModal('reject', id);
        }

        window.approveLeave = approveLeave;
        window.rejectLeave = rejectLeave;

        function updateStatusCounts(counts) {
            const pendingEl = document.getElementById('pending-count');
            const approvedEl = document.getElementById('approved-count');
            const rejectedEl = document.getElementById('rejected-count');

            if (pendingEl) pendingEl.textContent = counts.pending;
            if (approvedEl) approvedEl.textContent = counts.approved;
            if (rejectedEl) rejectedEl.textContent = counts.rejected;
        }

        // Check for success/error messages in session
        @if (session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif

        @if (session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif
    </script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        ReportFilter.init({
            url: '{{ route('hrm.leave.pending') }}',
            tableSelector: '#leave-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        })
    </script>
@endpush
