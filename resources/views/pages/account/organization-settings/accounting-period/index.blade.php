@extends('layouts.auth')

@section('title', 'Accounting Period')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.organization_settings') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden ">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 mb-3">

                        <!-- Breadcrumb -->
                        <nav class="flex mb-3" aria-label="Breadcrumb">
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
                                        <span class="ml-1 text-sm font-semibold text-gray-900">
                                            Accounting Period
                                        </span>
                                    </div>
                                </li>
                            </ol>
                        </nav>

                        <!-- Header Row -->
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

                            <!-- Title + Description -->
                            <div>
                                <h2 class="text-stone-950 text-xl font-normal">
                                    Accounting Period
                                </h2>
                                <p class="text-sm text-gray-500 font-medium">
                                    Manage your accounting periods for accurate financial tracking.
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">

                                <a href="{{ route('account.organization-settings.accounting-period.create') }}"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0]">
                                    ADD NEW
                                </a>

                                <a href="{{ route('account.organization-settings.accounting-period.all.logs') }}"
                                    class="px-4 py-2 border text-[#0078C8] border-[#0078C8] text-xs font-normal rounded hover:text-white hover:bg-[#0078C8]">
                                    All Logs
                                </a>

                            </div>

                        </div>

                    </div>

                    <div class="bg-white rounded-md mt-5">
                        <!-- Filters -->
                        <form method="GET" id="filter-form">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex flex-wrap items-end gap-4 justify-end">

                                    <div class="flex flex-col">
                                        <label class="block text-xs text-gray-500 mb-1">From</label>
                                        <input type="date" name="from" value="{{ request('from') }}"
                                            class="date w-40 py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 outline-none">
                                    </div>

                                    <div class="flex flex-col">
                                        <label class="block text-xs text-gray-500 mb-1">To</label>
                                        <input type="date" name="to" value="{{ request('to') }}"
                                            class="date w-40 py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 outline-none">
                                    </div>

                                    <div class="flex flex-col">
                                        <select name="status"
                                            class="select2 w-56 py-[9px] px-3 border border-gray-300 rounded text-sm focus:ring-0 outline-none">
                                            <option value="">-- select status --</option>
                                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Open
                                            </option>
                                            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Close
                                            </option>
                                        </select>
                                    </div>

                                    <div class="flex flex-col">
                                        <x-default-page />
                                    </div>

                                    <div class="flex gap-2 items-end mt-1">
                                        <button type="button" id="search-btn"
                                            class=" px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                            <span id="search-text">SEARCH</span>
                                            <span id="search-loader" class="hidden ml-2">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                        <button type="button" id="reset-btn"
                                            class=" px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Table -->
                        <div id="accounting-period-table">
                            @include('pages.account.organization-settings.accounting-period.table')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    @if ($canManageAllowedUsers)
    <!-- Allowed Advisors Modal -->
    <div id="allowedAdvisorsModal"
        class="fixed inset-0 z-[1000] hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-xl overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between border-b bg-gray-50 px-5 py-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Allowed Advisors</h2>
                    <p id="allowedAdvisorsPeriodName" class="mt-0.5 text-xs text-gray-500"></p>
                </div>
                <button type="button" id="closeAllowedAdvisorsModal"
                    class="flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-sm font-bold text-white hover:bg-red-600"
                    aria-label="Close">x</button>
            </div>

            <form id="allowedAdvisorsForm" method="POST">
                @csrf
                @method('PUT')
                <div class="p-5">
                    <label for="allowed_advisor_user_ids" class="mb-2 block text-sm font-medium text-gray-700">
                        Allow these users (Advisors) to make changes when period is locked
                    </label>
                    <select id="allowed_advisor_user_ids" name="user_id[]" multiple
                        class="select2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->full_name }}{{ $user->email ? ' (' . $user->email . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">
                        Select one or multiple users. Clear all selections to remove every advisor exception.
                    </p>
                    <div id="allowedAdvisorsError" class="mt-3 hidden text-sm text-red-600"></div>
                </div>

                <div class="flex justify-end gap-2 border-t bg-gray-50 px-5 py-3">
                    <button type="button" id="cancelAllowedAdvisorsModal"
                        class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button type="submit" id="saveAllowedAdvisors"
                        class="rounded bg-[#0078C8] px-4 py-2 text-sm text-white hover:bg-[#006bb0] disabled:cursor-not-allowed disabled:opacity-60">
                        Save Advisors
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Status Modal -->
    <div id="statusModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center hidden transition-opacity duration-300"
        style="z-index: 1000;">
        <!-- Modal Box -->
        <div id="statusModalBox"
            class="max-w-2xl bg-white rounded-lg shadow-lg w-full mx-4 overflow-hidden transform transition-all duration-300 scale-95">

            <!-- Header -->
            <div class="flex justify-between items-center bg-gray-100 px-5 py-3 border-b">
                <h2 class="text-lg font-semibold text-gray-800" id="headerName"></h2>
                <button onclick="closeStatusModal()"
                    class="text-white bg-red-500 hover:bg-red-600 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold transition-colors">
                    x
                </button>
            </div>
            <!-- Form -->
            <form id="statusForm" method="POST">
                @csrf
                @method('PUT')
                <!-- Table -->
                <div class="p-4 overflow-x-auto max-h-96">
                    <div class="grid md:grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Start Date</label>
                            <div class="relative">
                                <input type="text" name="start_date" id="start_date"
                                    class="date w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                <span
                                    class="absolute right-3 top-2.5 text-gray-400 pointer-events-none material-icons text-base"></span>
                            </div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-1 gap-4 mt-5">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">End Date</label>
                            <div class="relative">
                                <input type="text" name="end_date" id="end_date"
                                    class="date w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                <span
                                    class="absolute right-3 top-2.5 text-gray-400 pointer-events-none material-icons text-base"></span>
                            </div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-1 gap-4 mt-5">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">End Date</label>
                            <div class="relative">
                                <select name="is_locked"
                                    class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                    <option value="">Select an option</option>
                                    <option value="0">Open</option>
                                    <option value="1">Lock</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end px-5 py-3 bg-gray-50 border-t gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8]  text-white rounded-md text-sm transition-colors">
                        Change
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    @if ($canManageAllowedUsers)
    <script>
        (() => {
            const modal = document.getElementById('allowedAdvisorsModal');
            const form = document.getElementById('allowedAdvisorsForm');
            const select = $('#allowed_advisor_user_ids');
            const error = document.getElementById('allowedAdvisorsError');
            const saveButton = document.getElementById('saveAllowedAdvisors');
            let activeButton = null;

            // Keep the Select2 dropdown above and inside the modal overlay.
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            select.select2({
                width: '100%',
                placeholder: 'Select advisor users',
                allowClear: true,
                dropdownParent: $('#allowedAdvisorsModal')
            });

            function closeAllowedAdvisorsModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
                activeButton = null;
            }

            // Delegation keeps row buttons working after AJAX filtering/pagination replaces the table.
            document.addEventListener('click', (event) => {
                const button = event.target.closest('.allowed-advisors-btn');
                if (!button) return;

                activeButton = button;
                form.action = button.dataset.updateUrl;
                document.getElementById('allowedAdvisorsPeriodName').textContent = button.dataset.periodName;
                error.classList.add('hidden');
                error.textContent = '';

                let selectedUserIds = [];
                try {
                    selectedUserIds = JSON.parse(button.dataset.userIds || '[]').map(String);
                } catch (exception) {
                    selectedUserIds = [];
                }

                select.val(selectedUserIds).trigger('change');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            });

            document.getElementById('closeAllowedAdvisorsModal').addEventListener('click', closeAllowedAdvisorsModal);
            document.getElementById('cancelAllowedAdvisorsModal').addEventListener('click', closeAllowedAdvisorsModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeAllowedAdvisorsModal();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeAllowedAdvisorsModal();
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                error.classList.add('hidden');
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new FormData(form)
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        const validationMessage = data.errors
                            ? Object.values(data.errors).flat().join(' ')
                            : data.message;
                        throw new Error(validationMessage || 'Unable to update allowed advisors.');
                    }

                    const userIds = (data.user_ids || []).map(Number);
                    if (activeButton) {
                        activeButton.dataset.userIds = JSON.stringify(userIds);
                        const count = userIds.length;
                        activeButton.querySelector('.advisor-count').textContent =
                            `${count} ${count === 1 ? 'Advisor' : 'Advisors'}`;
                    }

                    closeAllowedAdvisorsModal();
                    Notification.success(data.message);
                } catch (exception) {
                    error.textContent = exception.message;
                    error.classList.remove('hidden');
                } finally {
                    saveButton.disabled = false;
                    saveButton.textContent = 'Save Advisors';
                }
            });
        })();
    </script>
    @endif

    <script>
        // Open Status Modal
        function openStatusModal(id, start_date, end_date, is_locked, update_url) {

            function cleanDate(dateString) {
                if (!dateString) return "";
                return dateString.split(" ")[0]; // 2025-07-01
            }

            function toDMY(dateString) {
                const parts = cleanDate(dateString).split("-");
                return parts[2] + "/" + parts[1] + "/" + parts[0]; // dd/mm/yyyy
            }

            document.getElementById('headerName').innerText = 'Change Status for ' + formatHumanDate(start_date) + ' to ' +
                formatHumanDate(end_date);

            document.getElementById('statusForm').action = update_url;
            $("#start_date").flatpickr().setDate(toDMY(start_date), true, "d/m/Y");
            $("#end_date").flatpickr().setDate(toDMY(end_date), true, "d/m/Y");

            $('select[name="is_locked"]').val(is_locked == "1" ? "1" : "0").trigger("change");

            // Show modal animation
            const modal = document.getElementById('statusModal');
            const modalBox = document.getElementById('statusModalBox');

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
                modalBox.classList.add('scale-100');
                modalBox.classList.remove('scale-95');
            }, 10);

            document.body.style.overflow = 'hidden';
        }


        // Close Stauys Modal
        function closeStatusModal() {
            const modal = document.getElementById('statusModal');
            const modalBox = document.getElementById('statusModalBox');

            modal.classList.remove('opacity-100');
            modalBox.classList.remove('scale-100');
            modalBox.classList.add('scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 250);
        }


        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) closeStatusModal();
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('statusModal');
                if (!modal.classList.contains('hidden')) closeStatusModal();
            }
        });
    </script>

    <!-- Dropdown Script -->
    <script>
        // Dropdown button handlers using event delegation
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

        // Close all dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });
    </script>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.organization-settings.accounting-period.index') }}',
            tableSelector: '#accounting-period-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
