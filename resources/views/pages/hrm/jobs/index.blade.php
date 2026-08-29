@extends('layouts.auth')

@section('title', 'Job Management')

@section('content')
<x-acccount.loader />
    <div class="mt-4">
         <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.recruitment') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Job Openings Header -->
                    <div class="bg-white rounded-lg border border-black/10 p-6 mb-6">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <h2 class="text-stone-950 text-2xl ">Job Openings</h2>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <!-- Export Dropdown -->
                                <div class="relative">
                                    <button onclick="toggleDropdown('exportDropdown')"
                                        class="px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        EXPORT ▼
                                    </button>
                                    <div id="exportDropdown"
                                        class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                        <ul class="py-1">
                                            <li>
                                                <a href="#"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        PDF Report
                                                    </div>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        Excel Report
                                                    </div>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Create New Job Button -->
                                <a href="{{ route('hrm.jobs.create') }}"
                                    class="px-4 py-2.5 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] text-sm font-medium flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    CREATE NEW JOB
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Total Jobs Card -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Jobs</p>
                                    <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $totalJobs }}</h3>
                                    <div class="flex items-center mt-2">
                                        <span class="text-xs text-gray-500">All job postings</span>
                                    </div>
                                </div>
                                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                        <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Active Jobs Card -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Active Jobs</p>
                                    <h3 class="text-3xl font-bold text-green-600 mt-2">{{ $activeJobs }}</h3>
                                    <div class="flex items-center mt-2">
                                        <span class="text-xs text-gray-500">Currently open positions</span>
                                    </div>
                                </div>
                                <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Total Applicants Card -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Applicants</p>
                                    <h3 class="text-3xl font-bold text-purple-600 mt-2">{{ $totalApplicants }}</h3>
                                    <div class="flex items-center mt-2">
                                        <span class="text-xs text-gray-500">All job applications</span>
                                    </div>
                                </div>
                                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <!-- Filters Section -->
                        <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                            <form method="GET" id="filter-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 items-end">
                                @if (request()->filled('from_date'))
                                    <input type="hidden" name="from_date" value="{{ request('from_date') }}">
                                @endif
                                @if (request()->filled('to_date'))
                                    <input type="hidden" name="to_date" value="{{ request('to_date') }}">
                                @endif

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Department</label>
                                    <select name="department_id"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                        <option value="">All Departments</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->department }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                                    <select name="status"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                        <option value="">All Status</option>
                                        @foreach ($statusOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ request('status') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Employment Type</label>
                                    <select name="employment_type"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                        <option value="">All Types</option>
                                        @foreach ($employmentTypeOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ request('employment_type') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Experience Level</label>
                                    <select name="experience_level"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                        <option value="">All Levels</option>
                                        @foreach ($experienceLevelOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ request('experience_level') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <input type="text" name="q" placeholder="Search by title, ID, or location..."
                                        value="{{ request('q') }}"
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                </div>

                                <div>
                                    <x-default-page />
                                </div>

                              <x-filter-button />
                            </form>
                        </div>

                        <!-- Table -->
                       <div id="job-table">
                        @include('pages.hrm.jobs.partials.table')
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

<script src="{{asset('assets/js/report-filter.js')}}"></script>
    <script>
        // ------ Dropdown toggle for each row ------
 document.addEventListener('click', function (e) {
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
                    showToast(data.message || 'Job deleted successfully!', 'success');
                } else {
                    const msg = data?.message || 'Failed to delete job.';
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

        // Update job status
        function updateJobStatus(jobId, status) {
            const confirmMessage = status === 'open'
                ? 'Are you sure you want to publish this job?'
                : 'Are you sure you want to close this job?';

            if (confirm(confirmMessage)) {
                fetch(`/hrm/jobs/${jobId}/update-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        // Reload page after 1 second
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Network error. Please try again.', 'error');
                });
            }
        }

        // Check for success/error messages in session
        @if(session('success'))
            showToast('{{ session("success") }}', 'success');
        @endif

        @if(session('error'))
            showToast('{{ session("error") }}', 'error');
        @endif

        ReportFilter.init({
            url:'{{ route('hrm.jobs.index') }}',
            tableSelector:'#job-table',
            paginationSelector:'#pagination-wrapper',
            filterContainer:document.getElementById('filter-form'),
            debounceDelay:250
        })
    </script>
@endpush
