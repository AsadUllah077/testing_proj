@extends('layouts.auth')

@section('title', 'Tax Rate')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">

        <!-- Tabs -->
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap">
                @foreach (config('modules.organization_settings') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">
            <div class="overflow-hidden">

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
                                        Tax Rate
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <!-- Header Row -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

                        <!-- Title + Description -->
                        <div>
                            <h2 class="text-xl font-normal text-stone-950">
                                Tax Rate
                            </h2>
                            <p class="text-sm text-gray-500 font-medium">
                                Manage your tax rates to apply accurate taxation across transactions.
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col md:flex-row md:flex-wrap items-start md:items-center gap-2">

                            <!-- Columns -->
                            <x-columns :columns="[
                                'tax_name' => 'Name',
                                'tax_code' => 'Code',
                                'tax_rate' => 'Rate',
                                'tax_type' => 'Type',
                            ]" name="tax_rates" />

                            <!-- Buttons -->
                            <button onclick="openTaxRateModal('create')"
                                class="px-4 py-2 bg-[#0078C8] text-white rounded text-xs hover:bg-[#006bb0]">
                                ADD NEW
                            </button>

                            <a href="{{ route('account.organization-settings.tax_rate.all.logs') }}"
                                class="px-4 py-2 border text-[#0078C8] border-[#0078C8] text-center rounded text-xs hover:text-white hover:bg-[#0078C8]">
                                All Logs
                            </a>

                        </div>

                    </div>

                </div>

                <div class="bg-white rounded-md border border-black/10 mt-4">
                    <!-- Search -->
                    <form method="GET" id="filter-form" class="p-4 border-b border-gray-200">
                        <div class="flex justify-end gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Search</label>
                                <input type="search" name="search" value="{{ request('search') }}"
                                    class="py-2 px-3 border border-gray-300 rounded focus:ring-0 focus:outline-none text-sm w-60"
                                    placeholder="Search..." />
                            </div>

                            <div class="flex flex-col">
                                <x-default-page />
                            </div>

                            <div class="flex gap-2 items-end">
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
                    </form>

                    <!-- TABLE -->
                    <div id="tax-rate-table">
                        @include('pages.account.organization-settings.tax-rate.table')
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Delete modal -->
    <x-delete-confirmation />

    <!-- Create/Edit modal -->
    <x-acccount.tax_rate.create-tax-rate />

@endsection



@push('scripts')
    <script>
        let taxIdToDelete = null;

        // Load Edit Modal
        async function loadTaxRate(id) {
            const res = await fetch(`/settings/tax-rate/edit/${id}`);
            const data = await res.json();
            if (data.success) {
                openTaxRateModal('edit', data.tax_rate);
            } else {
                data.message.forEach(message => alert(message))
            }
        }

        // Open Delete Modal
        function deleteTaxRate(id) {
            taxIdToDelete = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        // Confirm Delete
        document.getElementById('confirmDelete').addEventListener('click', async () => {
            const res = await fetch(`/settings/tax-rate/delete/${taxIdToDelete}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                data.message.forEach(message => alert(message));
            }
        });

        // Cancel Delete
        document.getElementById('cancelDelete').addEventListener('click', () => {
            document.getElementById('deleteModal').classList.add('hidden');
        });

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
            url: '{{ route('account.organization-settings.tax_rate.index') }}',
            tableSelector: '#tax-rate-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
