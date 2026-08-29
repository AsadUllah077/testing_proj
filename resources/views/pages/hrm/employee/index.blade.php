@extends('layouts.auth')

@section('title', 'Employee List')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">
                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Employee </h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative">
                            <div></div>

                            <!-- Column Chooser -->
                            <x-columns :columns="[
                                'emp_employee_id' => 'Employee ID',
                                'emp_employee' => 'Full Name',
                                'emp_department' => 'Department',
                                'emp_reporting_to' => 'Reporting To',
                                'emp_designation' => 'Designation',
                                'emp_status' => 'Status',
                                'emp_tenure' => 'Tenure',
                            ]" name="employee_columns" />

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
                                            <button onclick="startExport('pdf','employee-department-wise')" data-url=""
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
                                            <button onclick="startExport('xlsx','employee-department-wise')"
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

                            <!-- Add New Button -->
                            <a href="{{ route('hrm.employee.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                ADD NEW
                            </a>
                        </div>
                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-4">
                        <!-- Filters Section -->
                        <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                            <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                    <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                    <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Department</label>
                                    <select name="department_id" id="department_id"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Departments</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->department }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                                    <select name="status" id="status"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Status</option>
                                        <option value="active" {{ request('status', 'active') == 'active' ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                            Inactive</option>
                                        <option value="terminated"
                                            {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                                        <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>
                                            Resigned</option>
                                    </select>
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <input type="text" name="q" placeholder="Search by name, email, or phone..."
                                        value="{{ request('q') }}"
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <x-default-page />
                                </div>
                                <x-filter-button />
                            </form>
                        </div>

                        <!-- Table -->
                        <div id="employee-table">
                            @include('pages.hrm.employee.partials.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    {{-- Status Change Modal --}}
    <div id="statusModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Change Employee Status</h3>
                    <select id="statusSelect" class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="terminated">Terminated</option>
                        <option value="resigned">Resigned</option>
                    </select>
                    <input type="hidden" id="statusEmployeeId">
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmStatusChange"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Update Status
                    </button>
                    <button type="button" onclick="hideStatusModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal component --}}
    <x-delete-confirmation />
@endsection

@push('scripts')
    <script>
        // Bulk check
        const checkAll = document.getElementById('checkAll');
        const rowChecks = document.querySelectorAll('.rowCheck');
        checkAll?.addEventListener('change', function() {
            rowChecks.forEach(chk => chk.checked = this.checked);
        });

        // Dropdown toggle
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

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Export dropdown
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        // Toast helpers
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

        // Delete modal functions
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
                    if (deleteTarget.rowSel) {
                        const row = document.querySelector(deleteTarget.rowSel);
                        row?.parentNode?.removeChild(row);
                    }
                    showToast(data.message || 'Employee deleted successfully!', 'success');
                } else {
                    showToast(data?.message || 'Failed to delete employee.', 'error');
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

        // Status change modal
        let currentEmployeeId = null;

        function changeStatus(employeeId) {
            currentEmployeeId = employeeId;
            document.getElementById('statusEmployeeId').value = employeeId;
            document.getElementById('statusModal').classList.remove('hidden');
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }

        function hideStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
            currentEmployeeId = null;
        }

        document.getElementById('confirmStatusChange').addEventListener('click', async () => {
            if (!currentEmployeeId) return;

            const status = document.getElementById('statusSelect').value;

            try {
                const res = await fetch(`/hrm/employee/${currentEmployeeId}/change-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        status: status
                    })
                });

                const data = await res.json();

                if (res.ok && data?.success) {
                    showToast('Employee status updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data?.message || 'Failed to update status.', 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                hideStatusModal();
            }
        });

        // Close modals when clicking outside
        [deleteModal, document.getElementById('statusModal')].forEach(modal => {
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    if (modal === deleteModal) deleteTarget = {
                        url: null,
                        rowSel: null
                    };
                    if (modal === document.getElementById('statusModal')) currentEmployeeId = null;
                }
            });
        });

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
            url: '{{ route('hrm.employee.index') }}',
            tableSelector: '#employee-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
