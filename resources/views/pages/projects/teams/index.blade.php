@extends('layouts.auth')
@section('title', 'Teams')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
                <h2 class="text-stone-950 text-xl font-normal">Teams</h2>
                <div class="flex items-center gap-2">
                    @if(auth()->user()->hasPermission('project.teams.create'))
                    <a href="{{ route('project.teams.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        NEW TEAM
                    </a>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Table -->
                <div id="teams-table">
                    @include('pages.projects.teams.partials.table')
                </div>
            </div>
        </div>
    </div>

    <x-delete-confirmation />
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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
        deleteTarget = { url: null, rowSel: null };
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
</script>
@endpush
