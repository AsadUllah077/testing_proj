@extends('layouts.auth')
@section('title', 'Tickets')

@section('content')
    <x-acccount.loader />
    <div class="mt-4 mx-auto px-3 max-w-12xl">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">All Tickets</h1>
                <p class="text-sm text-gray-500 mt-0.5">Track and manage all reported issues</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Column Chooser -->
                <x-columns :columns="[
                    'ticket_number' => 'Ticket #',
                    'title'         => 'Title',
                    'category'      => 'Category',
                    'severity'      => 'Severity',
                    'status'        => 'Status',
                    'assigned_to'   => 'Assigned To',
                    'reported_by'   => 'Reported By',
                    'created_at'    => 'Date',
                ]" name="ticket_columns" />

                <!-- Export Dropdown -->
                <div class="relative">
                    <button onclick="toggleDropdown('exportDropdown')"
                        class="dropdown-btn px-4 py-1.5 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-semibold tracking-wide transition-colors">
                        EXPORT ▼
                    </button>
                    <div id="exportDropdown"
                        class="dropdown-menu hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                        <ul class="py-1 text-sm">
                            <li>
                                <button onclick="startExport('pdf', 'tickets')"
                                    class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                    </svg>
                                    PDF
                                </button>
                            </li>
                            <li>
                                <button onclick="startExport('xlsx', 'tickets')"
                                    class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Excel
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                @if(auth()->user()->isSuperAdmin())
                <button id="bulkAssignBtn"
                    class="hidden px-3 py-2 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0] flex items-center gap-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v10.5m-1.5-10.5h1.5m-1.5 10.5h1.5m-1.5-10.5a1.5 1.5 0 0 0-1.5-1.5h-12a1.5 1.5 0 0 0-1.5 1.5v12a1.5 1.5 0 0 0 1.5 1.5h12a1.5 1.5 0 0 0 1.5-1.5V7.5Z" />
                    </svg>
                    Assign Selected
                </button>
                @endif
                @if(auth()->user()->hasPermission('ticket.tickets.create'))
                <a href="{{ route('ticket.tickets.create') }}"
                    class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0] flex items-center gap-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Log New Issue
                </a>
                @endif
            </div>
        </div>

        @php $hasActiveFilters = request()->hasAny(['q', 'status', 'severity', 'category']); @endphp

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

            <!-- Filter Toggle -->
            <div class="flex justify-end px-4 pt-3">
                <button type="button" id="filtersToggleBtn" onclick="toggleFilters()" title="Toggle Filters"
                    class="p-2 rounded-lg transition-colors {{ $hasActiveFilters ? 'bg-[#0078C8] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </button>
            </div>

            <!-- Filters -->
            <div id="filtersSection" class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 {{ $hasActiveFilters ? '' : 'hidden' }}">
                <form method="GET" id="filterForm" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Ticket # or title..."
                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                        <select name="status"
                            class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <option value="">All Statuses</option>
                            @foreach(['open' => 'Open', 'in-progress' => 'In Progress', 'review' => 'Under Review', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $val => $label)
                                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Severity</label>
                        <select name="severity"
                            class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <option value="">All Severities</option>
                            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                                <option value="{{ $val }}" {{ request('severity') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[130px]">
                        <x-default-page />
                    </div>
                    <x-filter-button />
                </form>
            </div>

            <!-- Table -->
            <div id="tickets-table">
                @include('pages.tickets.partials.table', compact('tickets'))
            </div>

            <div id="pagination-wrapper">
                <x-data-table-paginatior :paginator="$tickets" />
            </div>

        </div>

    </div>

    <!-- Delete Confirm Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-2">Delete Ticket</h3>
            <p class="text-sm text-gray-500 mb-5">Are you sure you want to delete this ticket? This action cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button onclick="document.getElementById('deleteModal').classList.add('hidden')"
                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                <button id="confirmDeleteBtn"
                    class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0]">Delete</button>
            </div>
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin())
    <!-- Assign Modal -->
    <div id="assignModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="font-semibold text-gray-900 mb-4 text-lg">Assign Tickets</h3>
            <form id="assignForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Select Employee</label>
                    <select name="assigned_to" class="select2 w-full" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Issue Category</label>
                    <select name="category" class="select2 w-full" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Due Date</label>
                        <input type="date" name="due_date" class="date w-full py-2 px-3 border border-gray-300 rounded-lg text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Due Time</label>
                        <input type="time" name="due_time" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm" required>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors">Confirm Assignment</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

<script src="{{ asset('assets/js/report-filter.js') }}"></script>
@push('scripts')
<script>
    // Filter toggle
    function toggleFilters() {
        const section = document.getElementById('filtersSection');
        const btn = document.getElementById('filtersToggleBtn');
        const isHidden = section.classList.toggle('hidden');
        if (isHidden) {
            btn.classList.remove('bg-[#0078C8]', 'text-white');
            btn.classList.add('bg-gray-100', 'text-gray-500', 'hover:bg-gray-200');
        } else {
            btn.classList.remove('bg-gray-100', 'text-gray-500', 'hover:bg-gray-200');
            btn.classList.add('bg-[#0078C8]', 'text-white');
        }
    }
    window.toggleFilters = toggleFilters;

    // AJAX filtering + pagination
    ReportFilter.init({
        url:                '{{ route('ticket.tickets.index') }}',
        tableSelector:      '#tickets-table',
        paginationSelector: '#pagination-wrapper',
        filterContainer:    document.getElementById('filterForm'),
        debounceDelay:      250,
    });

    // Delete
    let pendingDeleteId = null;
    const deleteModal   = document.getElementById('deleteModal');
    const csrf          = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function deleteTicket(id) {
        pendingDeleteId = id;
        deleteModal.classList.remove('hidden');
    }
    window.deleteTicket = deleteTicket;

    deleteModal?.addEventListener('click', e => { if (e.target === deleteModal) deleteModal.classList.add('hidden'); });

    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async () => {
        if (!pendingDeleteId) return;
        try {
            const res  = await fetch(`/tickets/${pendingDeleteId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                Notification.success(data.message);
                deleteModal.classList.add('hidden');
                setTimeout(() => window.location.reload(), 800);
            } else {
                Notification.error(data.message);
            }
        } catch {
            Notification.error('Network error.');
        }
    });
    // Bulk selection logic
    const selectAll = document.getElementById('selectAll');
    const checkboxes = () => document.querySelectorAll('.ticket-checkbox');
    const bulkBtn = document.getElementById('bulkAssignBtn');
    const assignModal = document.getElementById('assignModal');

    function updateBulkBtn() {
        const checked = Array.from(checkboxes()).filter(c => c.checked).length;
        if (checked > 0) {
            bulkBtn.classList.remove('hidden');
        } else {
            bulkBtn.classList.add('hidden');
        }
    }

    document.addEventListener('change', e => {
        if (e.target.id === 'selectAll') {
            checkboxes().forEach(c => c.checked = e.target.checked);
            updateBulkBtn();
        }
        if (e.target.classList.contains('ticket-checkbox')) {
            updateBulkBtn();
        }
    });

    bulkBtn?.addEventListener('click', () => {
        assignModal.classList.remove('hidden');
    });

    // Handle Assignment Submit
    document.getElementById('assignForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const ticketIds = Array.from(checkboxes())
            .filter(c => c.checked)
            .map(c => c.value);
        
        ticketIds.forEach(id => formData.append('ticket_ids[]', id));

        try {
            const res = await fetch('{{ route('ticket.tickets.bulk-assign') }}', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Notification.success(data.message);
                assignModal.classList.add('hidden');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                Notification.error(data.message || 'Error occurred');
            }
        } catch (err) {
            Notification.error('Network error');
        }
    });
</script>
@endpush
