@extends('layouts.auth')

@section('title', 'List Of Purchase Order')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.purchase-order') as $module)
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
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">
                            Purchase Orders Approved
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 items-center">

                            <div></div>
                            <div class="relative w-full">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                    Select Status ▼
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li><a href="#" class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Approved</a>
                                        </li>
                                        <li><a href="#"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Canceled</a></li>
                                                  <li><a href="#"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Fully Paid</a></li>
                                                  <li><a href="#"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Partially Paid</a></li>
                                    </ul>
                                </div>
                            </div>
                            <!-- Select All Checkbox (centered vertically) -->
                            <div class="flex justify-center items-center">
                                <input type="checkbox" id="selectAll" class="rounded mr-2">
                                <label for="selectAll" class="text-sm text-gray-700">Select All To Delete</label>
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
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center ">

                        <!-- Search and Reset -->
                        <div></div>
                        <div class=" flex space-x-2">
                            <input type="text" placeholder="FO-2"
                                class="px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                            <button
                                class="px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">SEARCH</button>
                            <button
                                class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                        </div>

                    </div>





                    <!-- Bill Card -->
                    <div class="w-64 border border-gray-300 rounded-md overflow-hidden bg-white shadow-sm mt-5">
                        <!-- Header -->
                        <div class="bg-[#0078C8] flex justify-between items-center px-3 py-1.5">
                            <span class="text-white text-xs font-semibold">Bill No : PO-1</span>
                            <span class="text-red-500 text-xs font-bold cursor-pointer leading-none">x</span>
                        </div>

                        <!-- Body -->
                        <div class="p-3 text-sm text-gray-800">
                            <p><span class="font-semibold">Requested by:</span> Aizaz</p>
                            <p><span class="font-semibold">Total Amount:</span> 425000.00</p>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-between items-center border-t border-gray-200 px-3 py-2">
                            <button
                                class="px-3 py-1 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#006bb0] transition">
                                VIEW
                            </button>
                            <input type="checkbox" class="w-4 h-4 cursor-pointer">
                        </div>
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
        // Get modal element
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');

        // Dropdown button handlers
        document.querySelectorAll('.dropdown-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
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
        document.addEventListener('click', function (e) {
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
        window.addEventListener('click', function (e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('table input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
@endpush
