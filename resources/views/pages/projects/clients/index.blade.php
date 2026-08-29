@extends('layouts.auth')
@section('title', 'Clients')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
                <h2 class="text-stone-950 text-xl font-normal">Clients</h2>
                <div class="flex items-center gap-2 flex-wrap">

                    <!-- Column Chooser -->
                    <x-columns :columns="[
                        'name'     => 'Client Name',
                        'type'     => 'Type',
                        'contact'  => 'Contact',
                        'industry' => 'Industry',
                        'location' => 'Location',
                        'status'   => 'Status',
                    ]" name="client_columns" />

                    @if(auth()->user()->hasPermission('project.clients.export-pdf') || auth()->user()->hasPermission('project.clients.export-excel'))
                    <!-- Export Dropdown -->
                    <div class="relative">
                        <button onclick="toggleDropdown('exportClientsDropdown')"
                            class="px-3 py-1.5 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal whitespace-nowrap">
                            EXPORT ▼
                        </button>
                        <div id="exportClientsDropdown"
                            class="hidden absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded shadow-md z-10">
                            <ul class="py-1 text-sm text-gray-700">
                                <li>
                                    <a href="#" onclick="exportClients('pdf')"
                                        class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white text-xs">PDF</a>
                                </li>
                                <li>
                                    <a href="#" onclick="exportClients('excel')"
                                        class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white text-xs">Excel</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @endif

                    @if(auth()->user()->hasPermission('project.clients.create'))
                    <a href="{{ route('project.clients.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        NEW CLIENT
                    </a>
                    @endif
                </div>
            </div>

            @php $hasActiveFilters = request()->hasAny(['q', 'status', 'type']); @endphp

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Filter Toggle -->
                <div class="flex justify-end px-4 pt-3">
                    <button type="button" id="filtersToggleBtn" onclick="toggleFilters()" title="Toggle Filters"
                        class="p-2 rounded-lg transition-colors {{ $hasActiveFilters ? 'bg-[#0078C8] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                    </button>
                </div>

                <!-- Filters -->
                <div id="filtersSection" class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 {{ $hasActiveFilters ? '' : 'hidden' }}">
                    <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                            <input type="text" name="q" placeholder="Name, email or contact..."
                                value="{{ request('q') }}"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div class="flex-1 min-w-[130px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                            <select name="status" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[130px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Type</label>
                            <select name="type" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Types</option>
                                <option value="company" {{ request('type') === 'company' ? 'selected' : '' }}>Company</option>
                                <option value="individual" {{ request('type') === 'individual' ? 'selected' : '' }}>Individual</option>
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <x-default-page />
                        </div>
                        <x-filter-button />
                    </form>
                </div>

                <!-- Table -->
                <div id="clients-table">
                    @include('pages.projects.clients.partials.table')
                </div>
            </div>
        </div>
    </div>

    <x-delete-confirmation />
@endsection

<script src="{{ asset('assets/js/report-filter.js') }}"></script>
@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ========== DROPDOWN ==========
    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
            if (el.id !== id) el.classList.add('hidden');
        });
        dropdown.classList.toggle('hidden');
    }
    window.toggleDropdown = toggleDropdown;

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.relative') && !e.target.closest('[id$="Dropdown"]')) {
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
        }
    });

    // ========== EXPORT ==========
    window.exportClients = function (format) {
        const params = new URLSearchParams(window.location.search);
        const url = format === 'pdf'
            ? '{{ route('project.clients.export.pdf') }}'
            : '{{ route('project.clients.export.excel') }}';
        window.open(`${url}?${params.toString()}`, '_blank');
    };

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

    // Delete
    let deleteTarget = { url: null, rowSel: null };
    const deleteModal = document.getElementById('deleteModal');

    function confirmDelete(btnEl) {
        deleteTarget = { url: btnEl.dataset.url, rowSel: btnEl.dataset.row };
        deleteModal?.classList.remove('hidden');
    }
    window.confirmDelete = confirmDelete;

    document.getElementById('cancelDelete')?.addEventListener('click', () => {
        deleteModal?.classList.add('hidden');
    });

    document.getElementById('confirmDelete')?.addEventListener('click', async () => {
        if (!deleteTarget.url) return;
        try {
            const res = await fetch(deleteTarget.url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data?.success) {
                if (deleteTarget.rowSel) document.querySelector(deleteTarget.rowSel)?.remove();
                Notification.success(data.message || 'Deleted.');
            } else {
                Notification.error(data?.message || 'Delete failed.');
            }
        } catch (e) {
            Notification.error('Network error.');
        } finally {
            deleteModal?.classList.add('hidden');
            deleteTarget = { url: null, rowSel: null };
        }
    });

    deleteModal?.addEventListener('click', e => { if (e.target === deleteModal) deleteModal.classList.add('hidden'); });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.dropdown-btn');
        if (btn) {
            e.stopPropagation();
            const menu = btn.nextElementSibling;
            document.querySelectorAll('.dropdown-menu').forEach(m => { if (m !== menu) m.classList.add('hidden'); });
            menu.classList.toggle('hidden');
        }
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
    });

    ReportFilter.init({
        url: '{{ route('project.clients.index') }}',
        tableSelector: '#clients-table',
        paginationSelector: '#pagination-wrapper',
        filterContainer: document.getElementById('filter-form'),
        debounceDelay: 250
    });
</script>
@endpush
