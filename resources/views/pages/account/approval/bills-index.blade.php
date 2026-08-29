@extends('layouts.auth')
@section('title', 'Bill Approvals')

@section('content')
    <x-acccount.loader />
    <div class="p-4">
        <div class="bg-white p-4 rounded-lg mb-4">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-lg font-bold text-gray-800">Bill Approvals</h1>
                <a href="{{ route('account.approvals.sales.index') }}"
                    class="text-sm text-[#0078C8] hover:underline font-semibold">Switch to Invoices →</a>
            </div>

            {{-- Status Tabs --}}
            <div class="flex gap-2 mb-4">
                @foreach (['draft' => 'Draft', 'submitted' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}"
                        class="px-4 py-1.5 rounded-[3px] text-sm font-semibold border
                  {{ $status === $key ? 'bg-[#0078C8] text-white border-[#0078C8]' : 'bg-white text-gray-600 border-gray-300 hover:border-[#0078C8] hover:text-[#0078C8]' }}">
                        {{ $label }}
                        @if (isset($counts[$key]))
                            <span class="ml-1 bg-white/30 text-xs px-1.5 py-0.5 rounded-full">{{ $counts[$key] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-[5px] border border-gray-200 overflow-hidden">
            <!-- Filters Section -->
            <form method="GET" id="filter-form">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="selected_bill_ids" id="selected-bill-ids-input">
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
                            <label class="block text-xs font-medium text-gray-600 mb-2">Select Vendor</label>
                            <select name="vendor_id"
                                class="ajax-vendor-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            </select>
                        </div>

                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Invoice No</label>
                            <input type="text" name="invoice_no" value="{{ request('invoice_no') }}"
                                placeholder="Enter invoice no"
                                class="  w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>

                        <div class="flex-1 min-w-[150px]">
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

            @if($status === 'submitted' || $status === 'draft')
            <!-- Bulk Actions -->
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                            Select All
                        </label>
                        <span id="selected-count" class="text-sm text-gray-500">0 selected</span>
                    </div>
                    <div class="flex gap-2">
                        @if($status === 'draft')
                        <button type="button" id="bulk-submit-btn"
                            class="px-4 py-2 text-sm bg-amber-500 text-white rounded hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            Submit Selected
                        </button>
                        @elseif($status === 'submitted')
                        <button type="button" id="bulk-approve-btn"
                            class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            Approve Selected
                        </button>
                        <button type="button" id="bulk-reject-btn"
                            class="px-4 py-2 text-sm bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            Reject Selected
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Table -->
            <div id="bill-approve-table">
                @include('pages.account.approval.bill-table')
            </div>
        </div>

    </div>

    {{-- Reject Modal --}}
    <div id="rejectModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-base font-bold text-gray-800 mb-4">Reject Bill</h2>
            <form id="rejectForm" method="POST">
                @csrf
                <label class="block text-sm font-semibold text-gray-600 mb-1">Rejection Reason <span
                        class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]"
                    placeholder="Explain why this bill is rejected…"></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 text-sm border border-gray-300 rounded text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-red-500 text-white rounded hover:bg-red-600">Confirm
                        Reject</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Reject Modal --}}
    <div id="bulkRejectModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-base font-bold text-gray-800 mb-4">Reject Selected Bills</h2>
            <form id="bulkRejectForm" method="POST" action="{{ route('account.approvals.bills.bulk-reject') }}">
                @csrf
                <label class="block text-sm font-semibold text-gray-600 mb-1">Rejection Reason <span
                        class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-[#0078C8]"
                    placeholder="Explain why these bills are rejected…"></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="closeBulkRejectModal()"
                        class="px-4 py-2 text-sm border border-gray-300 rounded text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-red-500 text-white rounded hover:bg-red-600">Confirm
                        Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id, url) {
            document.getElementById('rejectForm').action = url;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        function closeBulkRejectModal() {
            document.getElementById('bulkRejectModal').classList.add('hidden');
        }

        // Bulk selection functionality
        @if($status === 'submitted' || $status === 'draft')
        const billApprovalSelectionStorageKey = 'account.approvals.bills.selected-bills';
        const selectedBillApprovals = new Map(JSON.parse(localStorage.getItem(billApprovalSelectionStorageKey) || '[]'));

        function persistBillApprovalSelection() {
            localStorage.setItem(billApprovalSelectionStorageKey, JSON.stringify(Array.from(selectedBillApprovals.entries())));
            syncSelectedBillIdsInput();
        }

        function syncSelectedBillIdsInput() {
            const input = document.getElementById('selected-bill-ids-input');
            if (input) input.value = Array.from(selectedBillApprovals.keys()).join(',');
        }

        function initializeBulkSelection() {
            const selectAllCheckbox = document.getElementById('select-all');
            if (!selectAllCheckbox) return;
            
            const checkboxes = document.querySelectorAll('input[name="bill_ids[]"]');
            const selectedCount = document.getElementById('selected-count');
            @if($status === 'draft')
            const bulkSubmitBtn = document.getElementById('bulk-submit-btn');
            @elseif($status === 'submitted')
            const bulkApproveBtn = document.getElementById('bulk-approve-btn');
            const bulkRejectBtn = document.getElementById('bulk-reject-btn');
            @endif

            function updateSelectedCount() {
                const selected = selectedBillApprovals.size;
                selectedCount.textContent = `${selected} selected`;
                @if($status === 'draft')
                bulkSubmitBtn.disabled = selected === 0;
                @elseif($status === 'submitted')
                bulkApproveBtn.disabled = selected === 0;
                bulkRejectBtn.disabled = selected === 0;
                @endif
            }

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectedBillApprovals.has(checkbox.value);
            });
            selectAllCheckbox.checked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
            selectAllCheckbox.indeterminate = Array.from(checkboxes).some(cb => cb.checked) && !selectAllCheckbox.checked;
            updateSelectedCount();

            selectAllCheckbox.removeEventListener('change', selectAllChangeHandler);
            selectAllCheckbox.addEventListener('change', selectAllChangeHandler);

            function selectAllChangeHandler() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    if (this.checked) {
                        selectedBillApprovals.set(checkbox.value, checkbox.value);
                    } else {
                        selectedBillApprovals.delete(checkbox.value);
                    }
                });
                persistBillApprovalSelection();
                updateSelectedCount();
            }

            checkboxes.forEach(checkbox => {
                checkbox.removeEventListener('change', checkboxChangeHandler);
                checkbox.addEventListener('change', checkboxChangeHandler);
            });

            function checkboxChangeHandler() {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                const someChecked = Array.from(checkboxes).some(cb => cb.checked);
                if (this.checked) {
                    selectedBillApprovals.set(this.value, this.value);
                } else {
                    selectedBillApprovals.delete(this.value);
                }
                persistBillApprovalSelection();
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
                updateSelectedCount();
            }

            @if($status === 'draft')
            bulkSubmitBtn.addEventListener('click', function() {
                const selectedIds = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedIds.length === 0) return;

                if (confirm(`Are you sure you want to submit ${selectedIds.length} bill(s) for approval?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('account.approvals.bills.bulk-submit') }}';

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                }
            });
            @elseif($status === 'submitted')
            bulkApproveBtn.addEventListener('click', function() {
                const selectedIds = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedIds.length === 0) return;

                if (confirm(`Are you sure you want to approve ${selectedIds.length} bill(s)?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('account.approvals.bills.bulk-approve') }}';

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                }
            });

            bulkRejectBtn.addEventListener('click', function() {
                const selectedIds = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedIds.length === 0) return;

                // Store selected IDs in data attribute for the form to use
                document.getElementById('bulkRejectForm').dataset.selectedIds = JSON.stringify(selectedIds);
                document.getElementById('bulkRejectModal').classList.remove('hidden');
            });

            // Handle bulk reject form submission
            document.getElementById('bulkRejectForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const selectedIds = JSON.parse(this.dataset.selectedIds || '[]');
                const reason = this.querySelector('textarea[name="reason"]').value;

                if (!reason.trim()) {
                    alert('Please enter a rejection reason');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.action;

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'reason';
                reasonInput.value = reason;
                form.appendChild(reasonInput);

                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            });
            @endif
        }

        document.addEventListener('DOMContentLoaded', initializeBulkSelection);
        document.addEventListener('tableUpdated', initializeBulkSelection);
        document.addEventListener('click', function(event) {
            if (event.target.closest('#search-btn')) {
                syncSelectedBillIdsInput();
            }

            if (event.target.closest('#reset-btn')) {
                syncSelectedBillIdsInput();
                if (selectedBillApprovals.size === 0) return;

                event.preventDefault();
                event.stopImmediatePropagation();
                const url = new URL('{{ route('account.approvals.bills.index') }}', window.location.origin);
                url.searchParams.set('status', '{{ $status }}');
                url.searchParams.set('selected_bill_ids', Array.from(selectedBillApprovals.keys()).join(','));
                window.location.href = url.toString();
            }
        }, true);
        syncSelectedBillIdsInput();
        @endif
    </script>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.approvals.bills.index') }}',
            tableSelector: '#bill-approve-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        // Initialize AJAX Vendor Select
        $('.ajax-vendor-select').select2({
            placeholder: '-- select vend --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.bill.vendors.paginate') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data.map(customer => ({
                            id: customer.id,
                            text: customer.name
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });
    </script>
@endpush
