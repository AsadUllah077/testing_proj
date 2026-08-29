@extends('layouts.auth')

@section('title', 'Accounting Users')

@section('content')
    <x-acccount.loader />

    <div class="min-h-screen bg-gray-50/30 py-8">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-4 rounded-xl mb-4">
                <!-- Breadcrumbs -->
                <nav class="flex mb-8" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-[#0078C8]">
                            <a href="{{ route('account.organization-settings.index') }}" class="flex items-center">
                                <span class="material-symbols-outlined text-base mr-1">settings</span>
                                Settings
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center text-gray-400">
                                <span class="material-symbols-outlined text-base">chevron_right</span>
                                <span class="ml-1 text-sm font-semibold text-gray-900">Accounting Users</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Header Block -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Accounting Users</h1>
                        <p class="text-sm text-gray-500 font-medium mt-1">Manage users with specialized accounting and
                            financial oversight permissions.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Column Config (Custom component if exists, otherwise placeholder) -->
                        <x-columns :columns="[
                            'first_name' => 'First Name',
                            'last_name' => 'Last Name',
                            'username' => 'Username',
                            'email' => 'Email',
                            'phone_number' => 'Phone Number',
                            'designation' => 'Designation',
                            'company' => 'Company',
                            'status' => 'Status',
                        ]" name="user_columns" />

                        <!-- Export Menu -->
                        <div class="relative inline-block text-left" x-data="{ open: false }">
                            <button @click="open = !open"
                                class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-50 transition-all duration-300">
                                <span class="material-symbols-outlined mr-2 text-lg">export_notes</span>
                                EXPORT
                                <span class="material-symbols-outlined ml-1 text-sm transition-transform"
                                    :class="open ? 'rotate-180' : ''">expand_more</span>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-2xl border border-gray-100 z-50 overflow-hidden ring-1 ring-black/5">
                                <div class="py-1">
                                    <a href="{{ route('account.organization-settings.users.export.pdf') }}"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#0078C8] font-semibold transition-colors">
                                        <span class="material-symbols-outlined mr-3 text-red-500">picture_as_pdf</span> PDF
                                        Document
                                    </a>
                                    <a href="#"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#0078C8] font-semibold transition-colors">
                                        <span class="material-symbols-outlined mr-3 text-green-600">border_all</span> Excel
                                        Sheet
                                    </a>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('account.organization-settings.users.create') }}"
                            class="inline-flex items-center px-6 py-2.5 bg-[#0078C8] text-white text-sm font-bold rounded-xl hover:bg-[#006bb0] hover:-translate-y-0.5 transition-all duration-300">
                            <span class="material-symbols-outlined mr-2 text-lg">person_add</span>
                            Create Accounting User
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter & Table Container -->
            <div class="bg-white rounded-[2rem] border border-gray-100 overflow-hidden ring-1 ring-black/[0.02]">

                <!-- Filter Bar -->
                <div class="p-8 bg-gray-50/50 border-b border-gray-100">
                    <form method="GET" class="flex flex-wrap items-end gap-6" id="filter-form">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Time
                                Range</label>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="date" name="from_date" value="{{ request('from_date', $from ?? null) }}"
                                    class="date w-full py-3 px-4 bg-white border border-gray-200 rounded-xl text-sm font-semibold focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all">
                                <input type="date" name="to_date" value="{{ request('to_date', $to ?? null) }}"
                                    class="date w-full py-3 px-4 bg-white border border-gray-200 rounded-xl text-sm font-semibold focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all">
                            </div>
                        </div>

                        <div class="flex-1 min-w-[250px]">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Keyword
                                Search</label>
                            <div class="relative">
                                <span
                                    class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">search</span>
                                <input type="text" name="q" placeholder="Name, email, or role..."
                                    value="{{ request('q', $q ?? null) }}"
                                    class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-semibold focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all">
                            </div>
                        </div>

                        <div class="w-32">
                            <x-default-page />
                        </div>

                        <x-filter-button />
                    </form>
                </div>

                <!-- Content Area -->
                <div id="user-table" class="min-h-[400px]">
                    @include('pages.account.organization-settings.user.table')
                </div>

            </div>
        </div>
    </div>

    {{-- Toast Feedback System --}}
    <div id="toast" class="fixed top-8 right-8 z-[9999] hidden pointer-events-none">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-center gap-4 min-w-[320px] pointer-events-auto transform transition-all duration-500 scale-90 opacity-0"
            id="toast-inner">
            <div id="toast-icon-box" class="w-10 h-10 rounded-xl flex items-center justify-center">
                <span id="toast-icon" class="material-symbols-outlined"></span>
            </div>
            <div class="flex-1">
                <h4 id="toast-title" class="text-sm font-bold text-gray-900"></h4>
                <p id="toast-message" class="text-xs text-gray-500 font-medium"></p>
            </div>
        </div>
    </div>

    {{-- Global Delete Shield --}}
    <x-delete-confirmation />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        #toast.show #toast-inner {
            scale: 1;
            opacity: 1;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // Premium Toast Implementation
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const inner = document.getElementById('toast-inner');
            const iconBox = document.getElementById('toast-icon-box');
            const icon = document.getElementById('toast-icon');
            const title = document.getElementById('toast-title');
            const msg = document.getElementById('toast-message');

            if (type === 'success') {
                iconBox.className = 'w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center';
                icon.textContent = 'check_circle';
                title.textContent = 'Action Successful';
            } else {
                iconBox.className = 'w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center';
                icon.textContent = 'error';
                title.textContent = 'Operation Failed';
            }

            msg.textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('show'), 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.classList.add('hidden'), 500);
            }, 4000);
        }
        window.showToast = showToast;

        // Delete Hook
        let deleteTarget = {
            url: null,
            rowSel: null
        };
        const deleteModal = document.getElementById('deleteModal');

        function confirmDelete(btnEl) {
            deleteTarget = {
                url: btnEl.dataset.url,
                rowSel: btnEl.dataset.row
            };
            deleteModal?.classList.remove('hidden');
        }
        window.confirmDelete = confirmDelete;

        document.getElementById('confirmDelete')?.addEventListener('click', async () => {
            if (!deleteTarget.url) return;
            try {
                const res = await fetch(deleteTarget.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data?.success) {
                    document.querySelector(deleteTarget.rowSel)?.remove();
                    showToast(data.success[0] ?? 'Record removed successfully');
                } else {
                    showToast(data?.error?.[0] || 'Error processing request', 'error');
                }
            } catch (e) {
                showToast('Network synchronization failed', 'error');
            } finally {
                deleteModal?.classList.add('hidden');
            }
        });
    </script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        ReportFilter.init({
            url: '{{ route('account.organization-settings.users') }}',
            tableSelector: '#user-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
