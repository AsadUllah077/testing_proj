@extends('layouts.auth')

@section('title', 'Sale Transaction')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['route'] . '*') }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden ">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Sale Transaction</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-2 relative ">
                            <x-columns :columns="[
                                'date' => 'Date',
                                'invoice_no' => 'Invoice No',
                                'reference_no' => 'Reference No',
                                'source' => 'Source',
                                'description' => 'Description',
                                'debit' => 'Debit',
                                'credit' => 'Credit',
                                'running_balance' => 'Running Balance',
                                'gross' => 'Gross',
                            ]" name="account_sale_report_columns" />

                            <!-- Export Dropdown -->
                            <div class="relative w-full">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                    EXPORT ▼
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li><a href="#"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">PDF</a></li>
                                        <li><a href="#"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Excel</a></li>
                                    </ul>
                                </div>
                            </div>
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
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex flex-wrap items-end gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">From</label>
                                    <input type="date"
                                        class="w-40 py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none date">
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">To</label>
                                    <input type="date"
                                        class="w-40 py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none date">
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Invoice No</label>
                                    <input type="text"
                                        class="w-40 py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none"
                                        placeholder="invoice no">
                                </div>



                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Search</label>
                                    <input type="text"
                                        class="w-40 py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none"
                                        placeholder="search">
                                </div>


                                <div class="flex gap-2 items-end mt-1">
                                    <button
                                        class=" px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">SEARCH</button>
                                    <button
                                        class=" px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto w-full p-4">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Date</th>
                                        <th class="px-4 py-3 text-left">Invoice No</th>
                                        <th class="px-4 py-3 text-left">Reference No</th>
                                        <th class="px-4 py-3 text-left">Source</th>
                                        <th class="px-4 py-3 text-left">Description</th>
                                        <th class="px-4 py-3 text-left">Debit</th>
                                        <th class="px-4 py-3 text-left">Credit</th>
                                        <th class="px-4 py-3 text-left">Running Balance</th>
                                        <th class="px-4 py-3 text-left">Gross</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr class="hover:bg-gray-50 border-b">
                                        <td class="px-4 py-2">24/02/2024</td>
                                        <td class="px-4 py-2">REF-001</td>
                                        <td class="px-4 py-2">John Doe</td>
                                        <td class="px-4 py-2">Product A</td>
                                        <td class="px-4 py-2">Sale Revenue</td>
                                        <td class="px-4 py-2">2.5</td>
                                        <td class="px-4 py-2">5</td>
                                        <td class="px-4 py-2 text-left">1000</td>
                                        <td class="px-4 py-2 text-left">
                                            5000
                                        </td>
                                    </tr>
                                    <!-- Repeat for other rows with delete-btn class -->
                                    <tr class="hover:bg-gray-50 ">
                                        <td class="px-4 py-2">24/02/2024</td>
                                        <td class="px-4 py-2">REF-002</td>
                                        <td class="px-4 py-2">Jane Smith</td>
                                        <td class="px-4 py-2">Product B</td>
                                        <td class="px-4 py-2">Sale Revenue</td>
                                        <td class="px-4 py-2">3.0</td>
                                        <td class="px-4 py-2">3</td>
                                        <td class="px-4 py-2 text-left">1500</td>
                                        <td class="px-4 py-2 text-left">
                                            4500
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />

@endsection
@push('scripts')
    <!-- Dropdown Script -->
    <script>
        // Get modal element
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');

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

        // Delete button click handler - fixed to work with all delete buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-btn')) {
                e.preventDefault();
                e.stopPropagation();
                // Show modal
                deleteModal.classList.remove('hidden');
                // Close all dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Cancel delete
        if (cancelDelete) {
            cancelDelete.addEventListener('click', () => {
                deleteModal.classList.add('hidden');
            });
        }

        // Confirm delete
        if (confirmDelete) {
            confirmDelete.addEventListener('click', () => {
                alert('Item deleted successfully!');
                deleteModal.classList.add('hidden');
            });
        }

        // Close modal when clicking outside
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                }
            });
        }

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
