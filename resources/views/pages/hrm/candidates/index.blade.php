@extends('layouts.auth')

@section('title', 'Candidate Management')

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

        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                <!-- Candidates Header -->
                <div class="bg-white rounded-lg border border-black/10 p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h2 class="text-stone-950 text-2xl ">Candidates</h2>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <!-- Export Button -->
                            <button onclick="toggleDropdown('exportDropdown')"
                                class="px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                EXPORT ▼
                            </button>

                            <!-- Create New Candidate Button -->
                            <a href="{{ route('hrm.candidates.create') }}"
                                class="px-4 py-2.5 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] text-sm font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                ADD CANDIDATE
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Total Candidates Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Candidates</p>
                                <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $totalCandidates }}</h3>
                                <div class="flex items-center mt-2">
                                    <span class="text-xs text-gray-500">All applications</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- New Applications Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">New Applications</p>
                                <h3 class="text-3xl font-bold text-blue-600 mt-2">{{ $newCandidates }}</h3>
                                <div class="flex items-center mt-2">
                                    <span class="text-xs text-gray-500">Require review</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- In Interview Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">In Interview</p>
                                <h3 class="text-3xl font-bold text-purple-600 mt-2">{{ $interviewingCandidates }}</h3>
                                <div class="flex items-center mt-2">
                                    <span class="text-xs text-gray-500">Active interviews</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Filters Section -->
                    <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                        <form method="GET" id="filter-form"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-2">Job</label>
                                <select name="job_id"
                                    class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                    <option value="">All Jobs</option>
                                    @foreach ($jobs as $job)
                                        <option value="{{ $job->id }}"
                                            {{ request('job_id') == $job->id ? 'selected' : '' }}>
                                            {{ $job->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-2">Stage</label>
                                <select name="stage"
                                    class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                    <option value="">All Stages</option>
                                    @foreach (App\Models\HrmCandidate::getStageOptions() as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ request('stage') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-2">Source</label>
                                <select name="source"
                                    class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                    <option value="">All Sources</option>
                                    @foreach (App\Models\HrmCandidate::getSourceOptions() as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ request('source') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                <input type="text" name="q" placeholder="Search by name, email, or phone..."
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
                    <div id="candidate-table">
                        @include('pages.hrm.candidates.partials.table')
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Export Dropdown -->
    <div id="exportDropdown"
        class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
        <ul class="py-1">
            <li>
                <a href="{{ route('hrm.candidates.export') . '?' . http_build_query(request()->except('per_page')) }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </div>
                </a>
            </li>
        </ul>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    <!-- Delete confirmation modal -->
    <x-delete-confirmation />
@endsection

<script src="{{ asset('assets/js/report-filter.js') }}"></script>
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
                    showToast(data.message || 'Candidate deleted successfully!', 'success');
                } else {
                    const msg = data?.message || 'Failed to delete candidate.';
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

        // Check for success/error messages in session
        @if (session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif

        @if (session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif

        ReportFilter.init({
            filterContainer: document.getElementById('filter-form'),
            url: '{{ route('hrm.candidates.index') }}',
            tableSelector: '#candidate-table',
            paginationSelector: '#pagination-wrapper',
            debounceDelay: 250
        })
    </script>
@endpush
