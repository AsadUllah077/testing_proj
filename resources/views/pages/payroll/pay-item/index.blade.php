@extends('layouts.auth')

@section('title', 'Payroll Settings - Pay Items')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-3">
                <h2 class="text-xl font-normal">Pay Items</h2>
                <div class="flex items-center space-x-3">
                    <form method="GET" class="flex items-end space-x-4" id="filter-form">

                        <!-- Search -->
                        <div class="flex flex-col">
                            <label for="search" class="text-xs text-gray-500 mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Search name..."
                                class="border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2 w-32">
                        </div>

                        <!-- Type -->
                        <div class="flex flex-col">
                            <label for="type" class="text-xs text-gray-500 mb-1">Type</label>
                            <select name="type"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2 w-32">
                                <option value="">All Types</option>
                                <option value="earning" {{ request('type') === 'earning' ? 'selected' : '' }}>Earning
                                </option>
                                <option value="deduction" {{ request('type') === 'deduction' ? 'selected' : '' }}>Deduction
                                </option>
                                <option value="reimbursement" {{ request('type') === 'reimbursement' ? 'selected' : '' }}>
                                    Reimbursement
                                </option>
                            </select>
                        </div>

                        <!-- Default Page Component -->
                        <div>
                            <x-default-page />
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-2 items-end">
                            <button type="button" id="search-btn"
                                class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                <span id="search-text">Apply Filters</span>
                                <span id="search-loader" class="hidden ml-2">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>

                            <button type="button" id="reset-btn"
                                class="px-4 py-2 text-xs bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">
                                RESET
                            </button>
                        </div>

                    </form>
                    <button onclick="openModal('create')"
                        class="mt-6 px-4 py-2 bg-[#0078C8] text-white rounded text-xs hover:bg-[#006bb0]">
                        ADD NEW PAY ITEM
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div id="pay-item-table">
                @include('pages.payroll.partials.pay-item-table')
            </div>

        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 id="modalTitle" class="text-lg font-medium mb-4">Create Pay Item</h3>
            <form id="itemForm">
                @csrf
                <input type="hidden" id="itemId" name="itemId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Type</label>
                            <select name="type" id="type" required
                                class="select2 mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                                <option value="earning">Earning</option>
                                <option value="deduction">Deduction</option>
                                <option value="reimbursement">Reimbursement</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Calculation Type</label>
                            <select name="calculation_type" id="calculation_type" required
                                onchange="toggleCalculationFields()"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>
                    </div>

                    <div id="percentageFields" class="hidden space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Percentage (%)</label>
                            <input type="number" name="percentage_value" id="percentage_value" step="0.01"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Calculate Based On</label>
                            <select name="base_item_id" id="base_item_id"
                                class="select2 mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                                <option value="">Select Item</option>
                                @foreach ($payItems as $pi)
                                    @if ($pi->calculation_type === 'fixed')
                                        <option value="{{ $pi->id }}">{{ $pi->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Linked Account</label>
                        <select name="account_id" id="account_id" required
                            class="select2 mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="">Select Account</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Statutory Body (for Compliance Monitoring)</label>
                        <select name="statutory_body_id" id="statutory_body_id"
                            class="select2 mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="">None (General Item)</option>
                            @foreach ($statutoryBodies as $body)
                                <option value="{{ $body->id }}">{{ $body->name }} ({{ $body->code }})</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">Linking to a body enables automated deadline monitoring and penalty risk alerts.</p>
                    </div>

                    <label class="flex items-start gap-3 rounded-md border border-gray-200 p-3 bg-gray-50">
                        <input type="checkbox" name="is_taxable" id="is_taxable" value="1" checked
                            class="mt-0.5 rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                        <span>
                            <span class="block text-xs font-medium text-gray-700">Subject to Income Tax</span>
                            <span class="block text-[10px] text-gray-400 mt-1">When unchecked, this earning is excluded from payroll income tax calculation.</span>
                        </span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">CANCEL</button>
                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-[#006bb0]">SAVE</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const modal = document.getElementById('modal');
        const form = document.getElementById('itemForm');

        function toggleCalculationFields() {
            const type = document.getElementById('calculation_type').value;
            const fields = document.getElementById('percentageFields');
            if (type === 'percentage') {
                fields.classList.remove('hidden');
            } else {
                fields.classList.add('hidden');
            }
        }

        function openModal(mode, data = null) {
            document.getElementById('modalTitle').innerText = mode === 'create' ? 'Create Pay Item' : 'Edit Pay Item';
            document.getElementById('itemId').value = data ? data.id : '';
            document.getElementById('name').value = data ? data.name : '';
            document.getElementById('calculation_type').value = data ? data.calculation_type : 'fixed';
            document.getElementById('percentage_value').value = data ? data.percentage_value : '';
            document.getElementById('base_item_id').value = data ? data.base_item_id : '';
            document.getElementById('is_taxable').checked = data ? !!data.is_taxable : true;
            toggleCalculationFields();

            $('#type').val(data ? data.type : 'earning').trigger('change');
            $('#base_item_id').val(data ? data.base_item_id : '').trigger('change');
            $('#account_id').val(data ? data.account_id : '').trigger('change');
            $('#statutory_body_id').val(data ? data.statutory_body_id : '').trigger('change');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function editItem(item) {
            openModal('edit', item);
        }

        async function deleteItem(id) {
            if (!confirm('Are you sure you want to delete this pay item?')) return;
            const res = await fetch(`/payroll/pay-items/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message);
            }
        }

        form.onsubmit = async (e) => {
            e.preventDefault();
            const id = document.getElementById('itemId').value;
            const url = id ? `/payroll/pay-items/${id}` : '/payroll/pay-items';
            const method = id ? 'PUT' : 'POST';

            const formData = new FormData(form);
            const jsonData = {};
            formData.forEach((value, key) => {
                jsonData[key] = value
            });

            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(jsonData)
            });

            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(Array.isArray(data.message) ? data.message.join('\n') : data.message);
            }
        }
    </script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#modal')
            });
        });
    </script>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('payroll.pay-items.index') }}',
            tableSelector: '#pay-item-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
