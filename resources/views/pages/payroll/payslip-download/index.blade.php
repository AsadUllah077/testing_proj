@extends('layouts.auth')

@section('title', 'Download Payslip')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
@endpush

@section('content')
    <div class="mb-8 font-sans">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-4">
                <div>
                    <a href="{{ route('payroll.dashboard') }}" class="text-md mb-1 text-[#0078C8] hover:underline"><i
                            class="fa-solid fa-arrow-left mr-2"></i>Back</a>
                    <h2 class="text-2xl font-light text-gray-800">Download Payslip</h2>
                    <p class="text-sm text-gray-500">Download employee payslips individually or in bulk by pay run or month
                        range.</p>
                </div>
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <form method="GET" action="{{ route('payroll.download-payslip.index') }}" id="payslip-download-filter-form"
                    class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="selected_employee_ids" id="selected-payslip-employee-ids-input">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Payslip Pay Run</label>
                        <select name="pay_run_id"
                            class="select2 block min-w-72 border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="" {{ $hasRange ? 'selected' : '' }}>Use month range</option>
                            @forelse($payRuns as $run)
                                <option value="{{ $run->id }}"
                                    {{ !$hasRange && $selectedPayRun?->id === $run->id ? 'selected' : '' }}>
                                    {{ $run->unique_no ?? 'PR-' . str_pad($run->id, 6, '0', STR_PAD_LEFT) }}
                                    ({{ $run->period_start->format('M d') }} - {{ $run->period_end->format('M d, Y') }})
                                    - {{ ucfirst($run->status) }}
                                </option>
                            @empty
                                <option value="">No posted or paid pay runs</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">From Month</label>
                        <input type="text" name="period_from" value="{{ request('period_from') }}"
                            placeholder="Select month"
                            class="month-picker block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm
                   shadow-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">To Month</label>
                        <input type="text" name="period_to" value="{{ request('period_to') }}"
                            placeholder="Select month"
                            class="month-picker block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm
                   shadow-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Employee</label>
                        <input type="search" name="search" value="{{ request('search') }}"
                            placeholder="Name, ID, or email"
                            class="block w-64 border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>

                    <div>
                        <x-default-page />
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                            Apply Filters
                        </button>
                        <a href="{{ route('payroll.download-payslip.index') }}" id="payslip-download-reset-link"
                            class="px-4 py-2 text-xs bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">
                            RESET
                        </a>
                    </div>
                </form>
            </div>

            <div id="bulk-action-bar"
                class="hidden bg-[#0078C8] text-white rounded-lg border border-[#0078C8]/30 p-4 mb-4 items-center justify-between shadow-sm">
                <div class="text-sm font-medium">
                    <span id="selected-count" class="font-bold text-lg">0</span> employee(s) selected
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="downloadSelectedPayslips()"
                        class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm">
                        Download Selected PDFs
                    </button>
                    <button type="button" onclick="emailSelectedPayslips()"
                        class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm">
                        Email Selected
                    </button>
                    <button type="button" onclick="clearSelection()"
                        class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-sm transition">
                        Clear
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-black/10 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 w-10">
                                    <input type="checkbox" id="select-all-payslips"
                                        class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                                </th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                {{-- <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Pay Run</th> --}}
                                {{-- <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Period</th> --}}
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider text-right">Net Pay
                                </th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider text-right">Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($payslips as $payslip)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-4">
                                        <input type="checkbox" value="{{ $payslip->employee_id }}"
                                            data-name="{{ $payslip->employee->full_name }}"
                                            class="payslip-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-800">{{ $payslip->employee->full_name }}</div>
                                        <div class="text-xs text-gray-400">{{ $payslip->employee->employee_id ?? '---' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $payslip->employee->email }}
                                    </td>
                                    {{-- <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ $payslip->payRun->unique_no ?? 'PR-' . str_pad($payslip->pay_run_id, 6, '0', STR_PAD_LEFT) }}
                                    </td> --}}
                                    {{-- <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ $payslip->payRun->period_start->format('M d') }} -
                                        {{ $payslip->payRun->period_end->format('M d, Y') }}
                                    </td> --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-gray-800">
                                        {{ currency()?->symbol }} {{ numberFormat($payslip->net_pay) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <a href="{{ route('payroll.payslips.pdf', [$payslip->pay_run_id, $payslip->id]) }}"
                                            target="_blank"
                                            class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-medium border transition">
                                            PDF
                                        </a>
                                        <button type="button"
                                            onclick="emailPayslip({{ $payslip->pay_run_id }}, {{ $payslip->id }}, @js($payslip->employee->full_name))"
                                            class="inline-flex items-center px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-xs font-medium border border-indigo-200 transition">
                                            Email
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">
                                        No payslips found for the selected pay run or month range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-data-table-paginatior :paginator="$payslips" />
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const selectionStorageKey = 'payroll.payslip-download.selected-employees';
        const selectedEmployees = new Map(JSON.parse(localStorage.getItem(selectionStorageKey) || '[]'));
        const selectedCount = document.getElementById('selected-count');
        const bulkActionBar = document.getElementById('bulk-action-bar');

        function persistSelection() {
            localStorage.setItem(selectionStorageKey, JSON.stringify(Array.from(selectedEmployees.entries())));
            syncSelectedEmployeeIdsInput();
        }

        function syncSelectedEmployeeIdsInput() {
            const input = document.getElementById('selected-payslip-employee-ids-input');
            if (input) input.value = Array.from(selectedEmployees.keys()).join(',');
        }

        function updateBulkBar() {
            selectedCount.textContent = selectedEmployees.size;

            if (selectedEmployees.size > 0) {
                bulkActionBar.classList.remove('hidden');
                bulkActionBar.classList.add('flex');
            } else {
                bulkActionBar.classList.add('hidden');
                bulkActionBar.classList.remove('flex');
            }
        }

        function clearSelection() {
            selectedEmployees.clear();
            persistSelection();
            document.querySelectorAll('.payslip-checkbox').forEach(checkbox => checkbox.checked = false);
            const selectAll = document.getElementById('select-all-payslips');
            if (selectAll) selectAll.checked = false;
            updateBulkBar();
        }

        function restoreVisibleSelection() {
            document.querySelectorAll('.payslip-checkbox').forEach(checkbox => {
                checkbox.checked = selectedEmployees.has(checkbox.value);
            });

            const visibleCheckboxes = Array.from(document.querySelectorAll('.payslip-checkbox'));
            const selectAll = document.getElementById('select-all-payslips');
            if (selectAll) {
                selectAll.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(checkbox => checkbox.checked);
            }
        }

        document.getElementById('select-all-payslips')?.addEventListener('change', function() {
            document.querySelectorAll('.payslip-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;

                if (this.checked) {
                    selectedEmployees.set(checkbox.value, checkbox.dataset.name);
                } else {
                    selectedEmployees.delete(checkbox.value);
                }
            });

            persistSelection();
            updateBulkBar();
        });

        document.querySelectorAll('.payslip-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    selectedEmployees.set(this.value, this.dataset.name);
                } else {
                    selectedEmployees.delete(this.value);
                }

                persistSelection();
                restoreVisibleSelection();
                updateBulkBar();
            });
        });

        document.getElementById('payslip-download-filter-form')?.addEventListener('submit', syncSelectedEmployeeIdsInput);
        document.getElementById('payslip-download-reset-link')?.addEventListener('click', function(event) {
            syncSelectedEmployeeIdsInput();
            if (selectedEmployees.size === 0) return;

            event.preventDefault();
            const url = new URL(this.href);
            url.searchParams.set('selected_employee_ids', Array.from(selectedEmployees.keys()).join(','));
            window.location.href = url.toString();
        });

        function selectedPayslipParams() {
            const payRunId = document.querySelector('select[name="pay_run_id"]')?.value;
            const periodFrom = document.querySelector('input[name="period_from"]')?.value;
            const periodTo = document.querySelector('input[name="period_to"]')?.value;

            if (!payRunId && !periodFrom && !periodTo) {
                alert('Please select a posted or paid pay run, or choose a from/to month range.');
                return null;
            }

            if (selectedEmployees.size === 0) {
                alert('Please select at least one employee.');
                return null;
            }

            const params = new URLSearchParams();
            if (payRunId && !periodFrom && !periodTo) params.append('pay_run_id', payRunId);
            if (periodFrom) params.append('period_from', periodFrom);
            if (periodTo) params.append('period_to', periodTo);
            selectedEmployees.forEach((name, employeeId) => params.append('employee_ids[]', employeeId));

            return params;
        }

        function downloadSelectedPayslips() {
            const params = selectedPayslipParams();
            if (!params) return;

            window.location.href = `{{ route('payroll.download-payslip.bulk-pdf') }}?${params.toString()}`;
        }

        async function emailSelectedPayslips() {
            const params = selectedPayslipParams();
            if (!params) return;

            if (!confirm(`Email payslips for ${selectedEmployees.size} selected employee(s)?`)) return;

            const response = await fetch('{{ route('payroll.download-payslip.bulk-email') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params.toString()
            });

            const data = await response.json();
            alert(data.message || 'Email request completed.');
        }

        async function emailPayslip(payRunId, payslipId, employeeName) {
            if (!confirm(`Email payslip to ${employeeName}?`)) return;

            const response = await fetch(`/payroll/pay-runs/${payRunId}/payslips/${payslipId}/email`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            alert(data.message || 'Email request completed.');
        }

        syncSelectedEmployeeIdsInput();
        restoreVisibleSelection();
        updateBulkBar();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>

    <script>
        flatpickr(".month-picker", {
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y"
                })
            ],
            altInput: true,
            disableMobile: true
        });
    </script>
@endpush
