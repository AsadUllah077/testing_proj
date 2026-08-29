@extends('layouts.auth')

@section('title', 'Permission Management')

@section('content')
    <x-acccount.loader />
    <div class="mt-4 px-3">

        <!-- Header -->
        <div class="p-4 sm:p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center mb-0">
            <h2 class="text-stone-950 text-xl font-normal">Permission Management</h2>
            <a href="{{ route('users.permission.create') }}"
                class="w-full sm:w-auto px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                ADD NEW
            </a>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-4">

            <!-- Filters Section -->
            <div class="p-4 sm:p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3" id="filter-form">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">From Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                            class="date w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">To Date</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                            class="date w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Search</label>
                        <input type="text" name="q" placeholder="Search permission..."
                            value="{{ request('q') }}"
                            class="w-full py-2.5 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <x-default-page />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                        <x-filter-button />
                    </div>
                </form>
            </div>

            <div id="permission-table">
                @include('pages.users.permission.table')
            </div>

        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    {{-- Delete confirmation modal component --}}
    <x-delete-confirmation />
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                menu.classList.toggle('hidden');
            }
        });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

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

        let deleteTarget = { url: null, rowSel: null };
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function confirmDelete(btnEl) {
            deleteTarget = { url: btnEl.dataset.url, rowSel: btnEl.dataset.row };
            deleteModal?.classList.remove('hidden');
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
        window.confirmDelete = confirmDelete;

        cancelDelete?.addEventListener('click', () => {
            deleteModal?.classList.add('hidden');
            deleteTarget = { url: null, rowSel: null };
        });

        confirmDeleteBtn?.addEventListener('click', async () => {
            if (!deleteTarget.url) return;
            try {
                const res = await fetch(deleteTarget.url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                if (res.ok && data?.success) {
                    if (deleteTarget.rowSel) {
                        const row = document.querySelector(deleteTarget.rowSel);
                        row?.parentNode?.removeChild(row);
                    }
                    showToast(data.message || 'Permission deleted successfully!', 'success');
                } else {
                    showToast(data?.message || 'Failed to delete permission.', 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                deleteModal?.classList.add('hidden');
                deleteTarget = { url: null, rowSel: null };
            }
        });

        deleteModal?.addEventListener('click', (e) => {
            if (e.target === deleteModal) {
                deleteModal.classList.add('hidden');
                deleteTarget = { url: null, rowSel: null };
            }
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
            url: '{{ route('users.permission.index') }}',
            tableSelector: '#permission-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
