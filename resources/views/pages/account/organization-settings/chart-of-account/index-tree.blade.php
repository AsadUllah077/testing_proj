@extends('layouts.auth')

@section('title', 'Chart of Accounts')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3">
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
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Chart of Accounts</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-2 relative ">
                            <x-columns :columns="[
                                'account_no' => 'Account No',
                                'name' => 'Name',
                                'parent_account' => 'Parent Account',
                                'status' => 'Status',
                            ]" name="account_chart_of_account_columns" />

                            <!-- Add New Button -->
                            <a href="{{ route('account.organization-settings.account.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0]">
                                ADD NEW
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 my-4">
                        <div class="col-span-full flex justify-end">
                            <select
                                class="w-28 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                <option value="">10</option>
                                <option value="1">20</option>
                                <option value="2">30</option>
                            </select>
                        </div>
                    </div>


                    <div class=" bg-white rounded-md border border-[#C0BFBF]">

                        <!-- Filters -->
                        <form method="GET" class="bg-white p-4 rounded-lg shadow mb-5 border border-gray-200">

                            <div class="grid md:grid-cols-4 gap-4">

                                <div>
                                    <label class="text-xs text-gray-500">Account No</label>
                                    <input type="text" name="account_no" value="{{ request('account_no') }}"
                                        class="w-full border-gray-300 rounded p-2 text-sm" />
                                </div>

                                <div>
                                    <label class="text-xs text-gray-500">Name</label>
                                    <input type="text" name="name" value="{{ request('name') }}"
                                        class="w-full border-gray-300 rounded p-2 text-sm" />
                                </div>

                                <div>
                                    <label class="text-xs text-gray-500">Parent Account</label>
                                    <select name="parent_id" class="w-full border-gray-300 rounded p-2 text-sm">
                                        <option value="">-- Any --</option>
                                        @foreach ($parents as $p)
                                            <option value="{{ $p->id }}"
                                                {{ request('parent_id') == $p->id ? 'selected' : '' }}>
                                                {{ $p->account_number }} - {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs text-gray-500">Per Page</label>
                                    <select name="per_page" class="w-full border-gray-300 rounded p-2 text-sm">
                                        <option {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                        <option {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                                        <option {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 flex gap-3">
                                <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Search</button>
                                <a href="{{ route('account.organization-settings.account.index') }}"
                                    class="bg-gray-300 px-4 py-2 rounded text-sm">Reset</a>
                            </div>

                        </form>

                        <!-- Summary Boxes -->
                        <div class="grid md:grid-cols-3 gap-4 mb-5">
                            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                                <p class="text-xs text-gray-600">Total Accounts</p>
                                <p class="text-2xl font-bold text-blue-700">{{ $totalAccounts }}</p>
                            </div>

                            <div class="bg-green-50 border border-green-200 p-4 rounded-lg">
                                <p class="text-xs text-gray-600">Total Initial Balance</p>
                                <p class="text-2xl font-bold text-green-700">
                                    {{ number_format($totalInitialBalance, 2) }}
                                </p>
                            </div>

                            <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg">
                                <p class="text-xs text-gray-600">Root Accounts</p>
                                <p class="text-2xl font-bold text-gray-700">{{ $rootAccounts }}</p>
                            </div>
                        </div>

                        <x-acccount.coa.tree :accounts="$accounts" />
                    </div>

                </div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />
    <x-history-model />

@endsection

@push('scripts')
    <!-- Dropdown Script -->
    <script>
        // Dropdown button handlers
        document.querySelectorAll('.dropdown-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const menu = this.nextElementSibling;
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                // Toggle current dropdown
                menu.classList.toggle('hidden');
            });
        });


        // Close all dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Export dropdown toggle
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            // Close all other dropdowns
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        }

        // Close export dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });
    </script>
@endpush
