@extends('layouts.auth')

@section('title', 'Payroll - Salary Advances')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-3">
                <h2 class="text-xl font-normal">Salary Advances</h2>
                <div class="flex items-center space-x-3">
                    <form method="GET" class="flex items-end space-x-4" id="filter-form">
                        <!-- Search -->
                        <div class="flex flex-col">
                            <label for="search" class="text-xs text-gray-500 mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Search employee or reason..."
                                class="border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2 w-32">
                        </div>

                        <!-- Status -->
                        <div class="flex flex-col">
                            <label for="status" class="text-xs text-gray-500 mb-1">Status</label>
                            <select name="status"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2 w-32">
                                <option value="">All</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved
                                </option>
                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected
                                </option>
                            </select>
                        </div>

                        <!-- Default Page Component -->
                        <div class="flex flex-col">
                            <label class="block text-xs text-gray-500 mb-1">Per Page</label>
                            <select name="per_page"
                                class="bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                @foreach (config('default.per_page') as $value)
                                    <option value="{{ $value }}"
                                        {{ request('per_page') == $value ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
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
                        ADD NEW ADVANCE
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div id="salary-advance-table">
                @include('pages.payroll.partials.salary-advance-table')
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
            <h3 id="modalTitle" class="text-lg font-medium mb-4">Create Salary Advance</h3>
            <form id="itemForm">
                @csrf
                <input type="hidden" id="itemId" name="itemId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Employee</label>
                        <select name="employee_id" id="employee_id" required
                            class="select2 mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="">Select Employee</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Amount</label>
                            <input type="number" name="amount" id="amount" step="0.01" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Advance Date</label>
                            <input type="date" name="advance_date" id="advance_date" required
                                class="date mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Recovery Method</label>
                            <select name="recovery_method" id="recovery_method" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                                <option value="fixed">Fixed installment</option>
                                <option value="percentage">Percentage of gross pay</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Recovery Frequency</label>
                            <select name="recovery_frequency" id="recovery_frequency" required class="mt-1 block w-full border-gray-300 rounded-md border p-2 text-sm">
                                <option value="each_pay_run">Every pay run</option>
                                <option value="monthly">Once per month</option>
                            </select>
                        </div>
                        <div id="installment_amount_group">
                            <label class="block text-xs font-medium text-gray-700">Installment Amount</label>
                            <input type="number" name="installment_amount" id="installment_amount" min="0.01" step="0.01"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                        </div>
                        <div id="recovery_percentage_group" class="hidden">
                            <label class="block text-xs font-medium text-gray-700">Gross Pay Percentage</label>
                            <input type="number" name="recovery_percentage" id="recovery_percentage" min="0.01" max="100" step="0.01"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Maximum Net Deduction %</label>
                            <input type="number" name="max_net_deduction_percentage" id="max_net_deduction_percentage"
                                min="1" max="100" step="0.01" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Recovery Start Date</label>
                            <input type="date" name="recovery_start_date" id="recovery_start_date" required
                                class="date mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Reason</label>
                        <textarea name="reason" id="reason" rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2"></textarea>
                    </div>
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

    <div id="issueModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-medium mb-4">Issue Salary Advance</h3>
            <form id="issueForm">
                @csrf
                <input type="hidden" id="issueAdvanceId">
                <div class="space-y-4">
                    <div class="text-sm text-gray-700">
                        <div id="issueEmployee" class="font-medium"></div>
                        <div id="issueAmount" class="text-gray-500"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Bank Account</label>
                        <select name="bank_account_id" id="issue_bank_account_id" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="">Select bank account</option>
                            @foreach ($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Payment Date</label>
                        <input type="date" name="payment_date" id="issue_payment_date" value="{{ now()->toDateString() }}"
                            required
                            class="date mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Description</label>
                        <input type="text" name="description" id="issue_description"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeIssueModal()"
                        class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">CANCEL</button>
                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-[#006bb0]">ISSUE PAYMENT</button>
                </div>
            </form>
        </div>
    </div>

    <div id="scheduleModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-medium mb-4">Reschedule Recovery</h3>
            <form id="scheduleForm" class="space-y-4">@csrf<input type="hidden" id="scheduleAdvanceId">
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs text-gray-600">Method</label><select name="recovery_method" id="schedule_method" class="w-full border rounded p-2 text-sm"><option value="fixed">Fixed installment</option><option value="percentage">Percentage of gross</option></select></div>
                    <div><label class="block text-xs text-gray-600">Frequency</label><select name="recovery_frequency" id="schedule_frequency" class="w-full border rounded p-2 text-sm"><option value="each_pay_run">Every pay run</option><option value="monthly">Once per month</option></select></div>
                    <div><label class="block text-xs text-gray-600">Installment Amount</label><input type="number" name="installment_amount" id="schedule_installment" min="0.01" step="0.01" class="w-full border rounded p-2 text-sm"></div>
                    <div><label class="block text-xs text-gray-600">Gross Percentage</label><input type="number" name="recovery_percentage" id="schedule_percentage" min="0.01" max="100" step="0.01" class="w-full border rounded p-2 text-sm"></div>
                    <div><label class="block text-xs text-gray-600">Maximum Net Deduction %</label><input type="number" name="max_net_deduction_percentage" id="schedule_cap" min="1" max="100" step="0.01" required class="w-full border rounded p-2 text-sm"></div>
                    <div><label class="block text-xs text-gray-600">Recovery Start Date</label><input type="date" name="recovery_start_date" id="schedule_start" required class="date w-full border rounded p-2 text-sm"></div>
                </div>
                <div class="flex justify-end gap-2"><button type="button" onclick="closeScheduleModal()" class="px-4 py-2 border rounded text-xs">CANCEL</button><button class="px-4 py-2 bg-[#0078C8] text-white rounded text-xs">SAVE SCHEDULE</button></div>
            </form>
        </div>
    </div>

    <div id="settlementModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-medium">Early Advance Settlement</h3><div id="settlementSummary" class="text-sm text-gray-500 mt-1 mb-4"></div>
            <form id="settlementForm" class="space-y-4">@csrf<input type="hidden" id="settlementAdvanceId">
                <div><label class="block text-xs text-gray-600">Received into Bank/Cash Account</label><select name="bank_account_id" required class="w-full border rounded p-2 text-sm">@foreach($bankAccounts as $account)<option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>@endforeach</select></div>
                <div><label class="block text-xs text-gray-600">Receipt Date</label><input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="date w-full border rounded p-2 text-sm"></div>
                <div><label class="block text-xs text-gray-600">Description</label><input name="description" class="w-full border rounded p-2 text-sm"></div>
                <div class="flex justify-end gap-2"><button type="button" onclick="closeSettlementModal()" class="px-4 py-2 border rounded text-xs">CANCEL</button><button class="px-4 py-2 bg-green-600 text-white rounded text-xs">POST SETTLEMENT</button></div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const modal = document.getElementById('modal');
        const form = document.getElementById('itemForm');
        const issueModal = document.getElementById('issueModal');
        const issueForm = document.getElementById('issueForm');

        function setDateValue(input, value) {
            if (input._flatpickr) {
                input._flatpickr.setDate(value || null, true);
            } else {
                input.value = value || '';
            }
        }

        function toggleRecoveryMethod() {
            const isFixed = document.getElementById('recovery_method').value === 'fixed';
            document.getElementById('installment_amount_group').classList.toggle('hidden', !isFixed);
            document.getElementById('recovery_percentage_group').classList.toggle('hidden', isFixed);
            document.getElementById('installment_amount').required = isFixed;
            document.getElementById('recovery_percentage').required = !isFixed;
        }

        function openModal(mode, data = null) {
            document.getElementById('modalTitle').innerText = mode === 'create' ? 'Create Salary Advance' :
                'Edit Salary Advance';
            document.getElementById('itemId').value = data ? data.id : '';
            document.getElementById('employee_id').value = data ? data.employee_id : '';
            document.getElementById('amount').value = data ? data.amount : '';
            const advanceDateInput = document.getElementById('advance_date');

            setDateValue(advanceDateInput, data ? data.advance_date : null);
            document.getElementById('recovery_method').value = data?.recovery_method || 'fixed';
            document.getElementById('recovery_frequency').value = data?.recovery_frequency || 'each_pay_run';
            document.getElementById('installment_amount').value = data?.installment_amount || '';
            document.getElementById('recovery_percentage').value = data?.recovery_percentage || '';
            document.getElementById('max_net_deduction_percentage').value = data?.max_net_deduction_percentage || 50;
            setDateValue(document.getElementById('recovery_start_date'), data?.recovery_start_date || data?.advance_date || null);
            document.getElementById('reason').value = data ? data.reason : '';
            toggleRecoveryMethod();

            $('#employee_id').val(data ? data.employee_id : '').trigger('change');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openIssueModal(id, employeeName, amount) {
            document.getElementById('issueAdvanceId').value = id;
            document.getElementById('issueEmployee').innerText = employeeName;
            document.getElementById('issueAmount').innerText = `Amount: ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('issue_description').value = `Salary advance issued to ${employeeName}`;
            issueModal.classList.remove('hidden');
            issueModal.classList.add('flex');
        }

        function closeIssueModal() {
            issueModal.classList.add('hidden');
            issueModal.classList.remove('flex');
        }

        async function togglePause(id) {
            const res = await fetch(`/payroll/salary-advances/${id}/toggle-pause`, {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});
            const data = await res.json(); alert(data.message); if(data.success) location.reload();
        }

        function openScheduleModal(advance) {
            document.getElementById('scheduleAdvanceId').value = advance.id;
            document.getElementById('schedule_method').value = advance.recovery_method || 'fixed';
            document.getElementById('schedule_frequency').value = advance.recovery_frequency || 'each_pay_run';
            document.getElementById('schedule_installment').value = advance.installment_amount || '';
            document.getElementById('schedule_percentage').value = advance.recovery_percentage || '';
            document.getElementById('schedule_cap').value = advance.max_net_deduction_percentage || 50;
            setDateValue(document.getElementById('schedule_start'), advance.recovery_start_date);
            document.getElementById('scheduleModal').classList.remove('hidden'); document.getElementById('scheduleModal').classList.add('flex');
        }
        function closeScheduleModal(){document.getElementById('scheduleModal').classList.add('hidden');document.getElementById('scheduleModal').classList.remove('flex');}
        function openSettlementModal(id,name,amount){document.getElementById('settlementAdvanceId').value=id;document.getElementById('settlementSummary').innerText=`${name} · Outstanding ${Number(amount).toLocaleString()}`;document.getElementById('settlementModal').classList.remove('hidden');document.getElementById('settlementModal').classList.add('flex');}
        function closeSettlementModal(){document.getElementById('settlementModal').classList.add('hidden');document.getElementById('settlementModal').classList.remove('flex');}

        function editItem(item) {
            openModal('edit', item);
        }

        async function approveItem(id) {
            if (!confirm('Are you sure you want to approve this salary advance?')) return;
            const res = await fetch(`/payroll/salary-advances/${id}/approve`, {
                method: 'POST',
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

        async function rejectItem(id) {
            if (!confirm('Are you sure you want to reject this salary advance?')) return;
            const res = await fetch(`/payroll/salary-advances/${id}/reject`, {
                method: 'POST',
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

        async function deleteItem(id) {
            if (!confirm('Are you sure you want to delete this salary advance?')) return;
            const res = await fetch(`/payroll/salary-advances/${id}`, {
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
            const url = id ? `/payroll/salary-advances/${id}` : '/payroll/salary-advances';
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

        issueForm.onsubmit = async (e) => {
            e.preventDefault();

            const id = document.getElementById('issueAdvanceId').value;
            const formData = new FormData(issueForm);
            const jsonData = {};
            formData.forEach((value, key) => {
                jsonData[key] = value
            });

            const res = await fetch(`/payroll/salary-advances/${id}/issue`, {
                method: 'POST',
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

        document.getElementById('scheduleForm').onsubmit = async (e) => {
            e.preventDefault(); const id=document.getElementById('scheduleAdvanceId').value;
            const res=await fetch(`/payroll/salary-advances/${id}/reschedule`,{method:'PUT',body:new FormData(e.target),headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});
            const data=await res.json(); alert(Array.isArray(data.message)?data.message.join('\n'):data.message); if(data.success)location.reload();
        };
        document.getElementById('settlementForm').onsubmit = async (e) => {
            e.preventDefault(); const id=document.getElementById('settlementAdvanceId').value;
            const res=await fetch(`/payroll/salary-advances/${id}/settle-early`,{method:'POST',body:new FormData(e.target),headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});
            const data=await res.json(); alert(Array.isArray(data.message)?data.message.join('\n'):data.message); if(data.success)location.reload();
        };
    </script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#modal')
            });
            document.getElementById('recovery_method').addEventListener('change', toggleRecoveryMethod);
            toggleRecoveryMethod();
        });
    </script>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('payroll.salary-advances.index') }}',
            tableSelector: '#salary-advance-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
