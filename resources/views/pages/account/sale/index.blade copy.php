@extends('layouts.auth')

@section('title', 'Customer Invoice')

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
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Customer Invoice</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative ">
                            <div></div>
                            <x-columns :columns="[
                                'in_date' => 'Date',
                                'in_reference_no' => 'Reference No',
                                'in_invoice_no' => 'Invoice No',
                                'in_customer_name' => 'Customer Name',
                                'in_description' => 'Description',
                                'in_total_amount' => 'Total Amount',
                                'in_remaining_amount' => 'Remaining Amount',
                                'in_created_by' => 'Created by',
                                'in_updated_by' => 'Updated by',
                            ]" name="account_sale_invoice_columns" />

                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-5 py-1.5 border border-[#0078C8] text-[#0078C8] rounded-lg hover:bg-[#0078C8] hover:text-white text-sm font-medium transition-all duration-200 flex items-center gap-2">
                                    Export
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                    <ul class="py-1 text-sm">
                                        <li>
                                            <button onclick="startExport('pdf','sale')" data-url=""
                                                class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="startExport('xlsx','sale')"
                                                class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Excel
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Insert CSV Button -->
                            {{-- <a href=""
                                class="px-4 text-center py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal flex items-center justify-center">
                                INSERT CSV
                            </a> --}}

                            <!-- Add New Button -->
                            <a href="{{ route('account.sale-invoice.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4 mr-1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg> ADD NEW
                            </a>
                        </div>
                    </div>

                    <!-- Records Per Page -->
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 my-4">
                        <div class="col-span-full flex justify-end">
                            <form method="GET" action="{{ route('account.sale-invoice.index') }}"
                                class="flex items-center">
                                <select name="per_page" onchange="this.form.submit()"
                                    class="select2 w-20 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    @foreach (config('default.per_page') as $value)
                                        <option value="{{ $value }}"
                                            {{ request('per_page') == $value ? 'selected' : '' }}>{{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <!-- Filters Section -->
                        <form method="GET" action="{{ route('account.sale-invoice.index') }}">
                            <div class="p-6 border-gray-200">
                                <div class="flex flex-wrap items-end gap-4">
                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                                            class=" date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    </div>

                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    </div>

                                    <div class="flex-1 min-w-[200px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Select Customer</label>
                                        <select name="customer_id"
                                            class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <option value="">-- select customer --</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}"
                                                    {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Invoice No</label>
                                        <input type="text" name="invoice_no" value="{{ request('invoice_no') }}"
                                            placeholder="Enter invoice no"
                                            class="  w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    </div>

                                    <div class="flex gap-3">
                                        <button type="submit"
                                            class="px-6 py-2.5 border-1 border-[#0078C8] text-[#0078C8] rounded-lg hover:bg-[#0078C8] hover:text-white transition-all duration-200 font-medium text-sm">
                                            SEARCH
                                        </button>
                                        <a href="{{ route('account.sale-invoice.index') }}"
                                            class="px-6 py-2.5 bg-[#0078C8] border-1 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm flex items-center">
                                            RESET
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Table -->
                        <div class="overflow-x-auto w-full p-4">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        {{-- <th class="px-4 py-3 text-left"><input type="checkbox" id="select-all"
                                                data-col="in_remaining_amount"></th> --}}
                                        <th class="px-4 py-3 text-left" data-col="in_date"><x-sortable column="date"
                                                label="Date" /></th>
                                        <th class="px-4 py-3 text-left" data-col="in_reference_no"><x-sortable
                                                column="reference_no" label="Reference No" /></th>
                                        <th class="px-4 py-3 text-left" data-col="in_invoice_no"><x-sortable
                                                column="invoice_no" label="Invoice No" /></th>
                                        <th class="px-4 py-3 text-left" data-col="in_customer_name"><x-sortable
                                                column="customer_name" label="Customer Name" /></th>
                                        <th class="px-4 py-3 text-left" data-col="in_description"><x-sortable
                                                column="description" label="Description" /></th>
                                        <th class="px-4 py-3 text-left" data-col="in_total_amount"><x-sortable
                                                column="total_amount" label="Total Amount" /></th>
                                        <th class="px-4 py-3 text-left" data-col="in_remaining_amount"><x-sortable
                                                column="remaining_amount" label="Remaining Amount" /></th>
                                        <th class="px-4 py-3 text-left" data-col="in_created_by"><x-sortable
                                                column="created_by" label="Created by" /></th>
                                        <th class="px-4 py-3 text-left" data-col="in_updated_by"><x-sortable
                                                column="updated_by" label="Updated by" /></th>
                                        <th class="px-4 py-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($sales as $sale)
                                        <tr class="hover:bg-gray-50 border-b">
                                            {{-- <td class="px-4 py-2"><input type="checkbox" class="sale-checkbox"
                                                    value="{{ $sale->id }}"></td> --}}
                                            <td class="px-4 py-2" data-col="in_date">{{ dateFormat($sale->date) }}
                                            </td>
                                            <td class="px-4 py-2" data-col="in_reference_no">{{ $sale->ref_no ?? 'N/A' }}
                                            </td>
                                            <td class="px-4 py-2" data-col="in_invoice_no">{{ $sale->invoice_no }}</td>
                                            <td class="px-4 py-2" data-col="in_customer_name">
                                                {{ $sale->customer->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-2" data-col="in_description">
                                                {{ Str::limit($sale->note, 30) ?: 'N/A' }}</td>
                                            <td class="px-4 py-2 text-left" data-col="in_total_amount">
                                                {{ numberFormat($sale->total) }}</td>

                                            <td class="px-4 py-2 text-center" data-col="in_remaining_amount">
                                                @if ($sale->credit_note_status == 1)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-600 text-white">
                                                        CRN Applied
                                                    </span>
                                                @elseif($sale->remaining_amount > 0)
                                                    <button
                                                        onclick="openPaymentModal({{ $sale->id }}, '{{ $sale->invoice_no }}', '{{ $sale->customer->name ?? 'N/A' }}', {{ $sale->remaining_amount }})"
                                                        class="bg-[#B1AFAF] hover:bg-[#0078C8] px-3 py-1 text-white rounded-full text-xs transition-colors cursor-pointer"
                                                        title="Click to record payment">
                                                        {{ number_format($sale->remaining_amount, 2) }}
                                                    </button>
                                                @else
                                                    <span
                                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#0078C8] text-white">
                                                        Paid
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-2 text-left" data-col="in_created_by">
                                                {{ $sale->createdBy->fullName ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-left" data-col="in_updated_by">
                                                {{ $sale->updatedBy->fullName ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 text-center relative">
                                                <button
                                                    class="text-gray-400 hover:text-gray-600 dropdown-btn p-1 rounded hover:bg-gray-100 transition-colors">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                    </svg>
                                                </button>
                                                <div
                                                    class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg border border-gray-200 z-20 overflow-hidden">
                                                    <form action="{{ route('account.sale-invoice.destroy', $sale->id) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class=" w-full text-left px-4 py-2.5 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-600 transition-colors"
                                                            onclick="return confirm('Are you sure you want to delete this sale invoice?')">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                    @if ($sale->credit_note_status != 1)
                                                        <a href="{{ route('account.sale-invoice.edit', $sale->id) }}"
                                                            class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path
                                                                    d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                            </svg>
                                                            Edit
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('account.sale-invoice.show', $sale->id) }}"
                                                        class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                            <path fill-rule="evenodd"
                                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        Details
                                                    </a>
                                                    <a href="{{ route('account.sale-invoice.audit-logs', $sale->id) }}"
                                                        class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        History
                                                    </a>

                                                    <a href="{{ route('account.sale-invoice.export-pdf', $sale->id) }}"
                                                        class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        Export PDF
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                                No sale invoices found.
                                            </td>
                                        </tr>
                                    @endforelse

                                    <!-- Total Row -->
                                    @if ($sales->count() > 0)
                                        <tr class="font-semibold">
                                            <td colspan="5"
                                                class="px-6 py-4 text-[#0078C8] text-right text-sm uppercase tracking-wide">
                                                Total:
                                            </td>
                                            <td class="px-3 py-4 items-center text-[#0078C8] text-sm font-bold">
                                                {{ numberFormat($sales->sum('total')) }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#0078C8] text-white">
                                                    {{ numberFormat($sales->sum('remaining_amount')) }}
                                                </span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if ($sales->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200">
                                {{ $sales->links() }}
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
    <x-acccount.remaining-amount />
@endsection

@push('scripts')
    <script>
        // Get modal element
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');

        // Select all checkbox
        // document.getElementById('select-all').addEventListener('change', function() {
        //     const checkboxes = document.querySelectorAll('.sale-checkbox');
        //     checkboxes.forEach(checkbox => {
        //         checkbox.checked = this.checked;
        //     });
        // });

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

        // Delete button click handler
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
