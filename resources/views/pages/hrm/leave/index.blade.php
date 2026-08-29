@extends('layouts.auth')

@section('title', 'Leave Management')

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
                        <h2 class="text-stone-950 text-xl  md:mb-0 mb-2">Leave Management</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative">
                            <div></div>
                            <div></div>

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
                                            <button onclick="startExport('pdf','leave-department-wise')" data-url=""
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
                                            <button onclick="startExport('xlsx','leave-department-wise')"
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

                            <!-- Add this to your leave records index view -->
                            {{-- <div class="mb-3">
                                <button type="button" class="btn btn-success" id="exportLeaveBtn">
                                    <i class="bi bi-file-excel"></i> Export to Excel
                                </button>

                                <button type="button" class="btn btn-primary" id="exportLeaveDepartmentWiseBtn">
                                    <i class="bi bi-folder"></i> Export Department-wise
                                </button>
                            </div>

                            <script>
                                // Function to get current filter values
                                function getFilterParams() {
                                    return new URLSearchParams({
                                        q: document.querySelector('[name="q"]')?.value || '',
                                        employee_id: document.querySelector('[name="employee_id"]')?.value || '',
                                        leave_type_id: document.querySelector('[name="leave_type_id"]')?.value || '',
                                        status: document.querySelector('[name="status"]')?.value || '',
                                        from_date: document.querySelector('[name="from_date"]')?.value || '',
                                        to_date: document.querySelector('[name="to_date"]')?.value || '',
                                        is_manual: document.querySelector('[name="is_manual"]')?.checked ? '1' : '',
                                    });
                                }

                                // Export all leave records
                                document.getElementById('exportLeaveBtn').addEventListener('click', function() {
                                    const params = getFilterParams();
                                    window.location.href = '{{ route('hrm.leave.export') }}?' + params.toString();
                                });

                                // Export department-wise
                                document.getElementById('exportLeaveDepartmentWiseBtn').addEventListener('click', function() {
                                    const params = getFilterParams();
                                    window.location.href = '{{ route('hrm.leave.export.department-wise') }}?' + params.toString();
                                });
                            </script> --}}

                            <!-- Add New Button -->
                            <a href="{{ route('hrm.leave.create') }}"
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
                            <form method="GET" id="filter-form"
                                class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-8 gap-4 items-end">
                                @if (request()->boolean('overlap'))
                                    <input type="hidden" name="overlap" value="1">
                                @endif

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
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                                    <select name="status" id="status"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm">
                                        <option value="">All Status</option>
                                        @foreach ($statusOptions as $status)
                                            <option value="{{ $status }}"
                                                {{ request('status') == $status ? 'selected' : '' }}>
                                                {{ $status }}
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
                            @include('pages.hrm.leave.partials.table')
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

        // Approve/Reject functions
        function approveLeave(id) {
            if (confirm('Are you sure you want to approve this leave?')) {
                updateLeaveStatus(id, 'approved');
            }
        }

        function rejectLeave(id) {
            if (confirm('Are you sure you want to reject this leave?')) {
                const remarks = prompt('Please provide a reason for rejection:', '');
                if (remarks !== null) {
                    updateLeaveStatus(id, 'rejected', remarks);
                }
            }
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
            url: '{{ route('hrm.leave.index') }}',
            tableSelector: '#leave-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        })
    </script>
@endpush
