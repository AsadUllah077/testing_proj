@extends('layouts.auth')

@section('title', 'Employee Pay Templates')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-3">
                <h2 class="text-xl font-normal">Employee Pay Templates</h2>
                <form method="GET" class="flex items-end space-x-4" id="filter-form">
                    <input type="hidden" name="selected_employee_ids" id="selected-employee-ids-input">

                    <!-- Search Field -->
                    <div class="flex flex-col">
                        <label class="block text-xs text-gray-500 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee..."
                            class="border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] 
                   focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-xs text-gray-500 mb-1">Salary Set</label>
                        <select name="salary_set" class="border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] 
                   focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="1" {{ request('salary_set') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ request('salary_set') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-xs text-gray-500 mb-1">Payslip Pay Run</label>
                        <select name="pay_run_id" class="select2 border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] 
                   focus:border-[#0078C8] sm:text-sm border p-2 min-w-56">
                            @forelse($publishedPayRuns as $run)
                                <option value="{{ $run->id }}" {{ $selectedPayRun?->id === $run->id ? 'selected' : '' }}>
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
                        <x-default-page />
                    </div>

                    <div class="flex gap-2 items-end">
                        <button type="button" id="search-btn"
                            class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] 
                   hover:text-white rounded hover:bg-[#0078C8]">
                            <span id="search-text">Apply Filters</span>
                            <span id="search-loader" class="hidden ml-2">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>

                        <button type="button" id="reset-btn"
                            class="px-4 py-2 text-xs bg-[#0078C8] border border-[#0078C8] 
                   text-white rounded hover:bg-[#0a8de4]">
                            RESET
                        </button>
                    </div>

                </form>
            </div>

            {{-- Bulk Action Bar --}}
            <div id="bulk-action-bar"
                class="hidden bg-gradient-to-r from-[#0078C8] to-[#005a96] text-white rounded-lg border border-[#0078C8]/30 p-4 mb-3 flex items-center justify-between shadow-lg transition-all duration-300">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium">
                        <span id="selected-count" class="font-bold text-lg">0</span> employee(s) selected
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    @if($selectedPayRun)
                        <button type="button" id="bulk-payslip-download-btn" onclick="downloadSelectedPayslips()"
                            class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download Payslip PDFs
                        </button>
                    @endif
                    <button type="button" id="bulk-setup-btn" onclick="openBulkModal()"
                        class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Bulk Setup Pay Template
                    </button>
                    <button type="button" onclick="clearSelection()"
                        class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-sm transition">
                        Clear
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div id="employee-pay-table">
                @include('pages.payroll.partials.employee-pay-table')
            </div>

        </div>
    </div>

    {{-- Bulk Setup Modal --}}
    <div id="bulk-modal-overlay"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity duration-300"
        onclick="closeBulkModal(event)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden"
            onclick="event.stopPropagation()">

            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-[#0078C8] to-[#005a96] p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold">Bulk Pay Template Setup</h3>
                        <p class="text-sm text-white/70 mt-1">Configure pay items for <span id="modal-emp-count"
                                class="font-bold text-white">0</span> selected employees</p>
                    </div>
                    <button onclick="closeBulkModal()" class="text-white/60 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Selected employees preview --}}
                <div class="mt-4 flex flex-wrap gap-2 max-h-32 overflow-y-auto pr-2" id="modal-selected-tags" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.3) transparent;">
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto" style="max-height: 55vh;">

                {{-- Basic Salary Option --}}
                <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-100">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-blue-900">Basic Salary Source</h4>
                            <p class="text-xs text-blue-600">Choose how to set the Basic Salary for each employee</p>
                        </div>
                    </div>
                    <div class="space-y-2 ml-11">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="salary_source" value="auto" checked
                                class="text-[#0078C8] focus:ring-[#0078C8]" onchange="toggleSalarySource()">
                            <span class="text-sm text-gray-700">Use each employee's <strong>existing salary</strong>
                                from HR records</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="salary_source" value="fixed"
                                class="text-[#0078C8] focus:ring-[#0078C8]" onchange="toggleSalarySource()">
                            <span class="text-sm text-gray-700">Set a <strong>fixed amount</strong> for all selected
                                employees</span>
                        </label>
                    </div>
                </div>

                {{-- Pay Items --}}
                @foreach (['earning', 'deduction', 'reimbursement'] as $type)
                    @php $itemsOfType = $payItems->where('type', $type); @endphp
                    @if ($itemsOfType->isNotEmpty())
                        <div class="mb-5">
                            <h4
                                class="text-xs font-bold text-gray-600 uppercase mb-3 bg-gray-50 p-2 border-l-4 
                                {{ $type === 'earning' ? 'border-green-500' : ($type === 'deduction' ? 'border-red-400' : 'border-blue-400') }}">
                                {{ ucfirst($type) }}s
                            </h4>
                            <div class="space-y-3">
                                @foreach ($itemsOfType as $item)
                                    @php
                                        $isBasic = stripos($item->name, 'basic salary') !== false;
                                        $isMedical = stripos($item->name, 'medical allowance') !== false;
                                        $isTax = stripos($item->name, 'income tax') !== false;
                                    @endphp
                                    <div
                                        class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition {{ $isBasic ? 'bg-blue-50/50 border-blue-200' : '' }} {{ $isTax ? 'bg-amber-50/50 border-amber-200' : '' }}">
                                        <div class="flex-1">
                                            <span
                                                class="text-sm font-medium text-gray-800">{{ $item->name }}</span>
                                            @if ($isBasic)
                                                <span
                                                    class="ml-2 text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-full font-bold uppercase">auto-fill</span>
                                            @endif
                                            @if ($isTax)
                                                <span
                                                    class="ml-2 text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-bold uppercase">auto-calculated</span>
                                            @endif
                                            @if (in_array($item->type, ['earning', 'reimbursement'], true))
                                                <span
                                                    class="ml-2 text-[10px] {{ $item->is_taxable ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }} px-1.5 py-0.5 rounded-full font-bold uppercase">
                                                    {{ $item->is_taxable ? 'taxable' : 'tax exempt' }}
                                                </span>
                                            @endif
                                            <p class="text-xs text-gray-400">
                                                {{ $item->account->name ?? 'No Account' }}</p>
                                        </div>
                                        <div class="w-40">
                                            <div class="relative">
                                                <span
                                                    class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500 text-xs">{{ currency()?->symbol }}</span>
                                                <input type="number"
                                                    id="bulk_item_{{ $item->id }}"
                                                    data-item-id="{{ $item->id }}"
                                                    data-is-basic="{{ $isBasic ? '1' : '0' }}"
                                                    data-is-medical="{{ $isMedical ? '1' : '0' }}"
                                                    data-is-tax="{{ $isTax ? '1' : '0' }}"
                                                    step="0.01"
                                                    class="bulk-pay-input block w-full pl-6 pr-3 py-2 border border-gray-300 rounded-md focus:ring-[#0078C8] focus:border-[#0078C8] text-sm {{ $isBasic ? 'bg-blue-50 border-blue-300' : '' }} {{ $isTax ? 'bg-amber-50 border-amber-300' : '' }}"
                                                    placeholder="{{ $isBasic ? 'Auto from HR records' : ($isTax ? 'Auto from tax slabs' : '0.00') }}"
                                                    {{ $isBasic || $isTax ? 'disabled' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Auto Tax Info --}}
                <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <p class="text-xs text-amber-700"><strong>Income Tax</strong> is automatically calculated per employee using the configured FBR tax slabs based only on pay items marked taxable. You don't need to set it manually.</p>
                </div>

                <label class="mt-3 flex items-start gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-lg cursor-pointer">
                    <input type="checkbox" id="bulk_apply_medical_allowance"
                        class="mt-1 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                    <span>
                        <span class="block text-xs font-bold text-emerald-800">Move 10% of gross salary before tax to Medical Allowance</span>
                        <span class="block text-[11px] text-emerald-700 mt-1">For each taxable employee, this uses Basic Salary plus any existing Medical Allowance before tax deductions, moves that employee's own 10%, and recalculates Income Tax.</span>
                    </span>
                </label>

            </div>

            {{-- Modal Footer --}}
            <div class="p-6 border-t bg-gray-50 flex items-center justify-between rounded-b-2xl">
                <p class="text-xs text-gray-400 italic max-w-xs">This will replace all existing pay items for the selected
                    employees.</p>
                <div class="flex gap-3">
                    <button type="button" onclick="closeBulkModal()"
                        class="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="button" id="bulk-save-btn" onclick="submitBulkUpdate()"
                        class="px-6 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-bold hover:bg-[#006bb0] transition shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Apply to All Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('payroll.employee-pay.index') }}',
            tableSelector: '#employee-pay-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        // ========== BULK SELECTION LOGIC ==========
        const employeeSelectionStorageKey = 'payroll.employee-pay.selected-employees';
        let selectedEmployees = new Map(JSON.parse(localStorage.getItem(employeeSelectionStorageKey) || '[]')); // id -> { name, salary }

        function persistSelection() {
            localStorage.setItem(employeeSelectionStorageKey, JSON.stringify(Array.from(selectedEmployees.entries())));
            syncSelectedEmployeeIdsInput();
        }

        function syncSelectedEmployeeIdsInput() {
            const input = document.getElementById('selected-employee-ids-input');
            if (input) input.value = Array.from(selectedEmployees.keys()).join(',');
        }

        function updateBulkBar() {
            const bar = document.getElementById('bulk-action-bar');
            const count = selectedEmployees.size;
            document.getElementById('selected-count').textContent = count;

            if (count > 0) {
                bar.classList.remove('hidden');
                bar.classList.add('flex');
            } else {
                bar.classList.add('hidden');
                bar.classList.remove('flex');
            }
        }

        function clearSelection() {
            selectedEmployees.clear();
            persistSelection();
            document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('select-all-employees');
            if (selectAll) selectAll.checked = false;
            updateBulkBar();
        }

        function restoreVisibleSelection() {
            const checkboxes = Array.from(document.querySelectorAll('.employee-checkbox'));
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectedEmployees.has(checkbox.value);
            });

            const selectAll = document.getElementById('select-all-employees');
            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && checkboxes.every(checkbox => checkbox.checked);
                selectAll.indeterminate = checkboxes.some(checkbox => checkbox.checked) && !selectAll.checked;
            }

            updateBulkBar();
        }

        // Delegate checkbox events (survives AJAX re-renders)
        document.getElementById('employee-pay-table').addEventListener('change', function(e) {
            if (e.target.id === 'select-all-employees') {
                const checked = e.target.checked;
                document.querySelectorAll('.employee-checkbox').forEach(cb => {
                    cb.checked = checked;
                    const id = cb.value;
                    if (checked) {
                        selectedEmployees.set(id, {
                            name: cb.dataset.name,
                            salary: cb.dataset.salary,
                            payslipId: cb.dataset.payslipId
                        });
                    } else {
                        selectedEmployees.delete(id);
                    }
                });
                persistSelection();
                updateBulkBar();
                return;
            }

            if (e.target.classList.contains('employee-checkbox')) {
                const id = e.target.value;
                if (e.target.checked) {
                    selectedEmployees.set(id, {
                        name: e.target.dataset.name,
                        salary: e.target.dataset.salary,
                        payslipId: e.target.dataset.payslipId
                    });
                } else {
                    selectedEmployees.delete(id);
                }
                persistSelection();
                updateBulkBar();
            }
        });

        document.getElementById('filter-form')?.addEventListener('submit', syncSelectedEmployeeIdsInput);
        document.addEventListener('click', function(event) {
            if (event.target.closest('#search-btn')) {
                syncSelectedEmployeeIdsInput();
            }

            if (event.target.closest('#reset-btn')) {
                syncSelectedEmployeeIdsInput();
                if (selectedEmployees.size === 0) return;

                event.preventDefault();
                event.stopImmediatePropagation();
                const url = new URL('{{ route('payroll.employee-pay.index') }}', window.location.origin);
                url.searchParams.set('selected_employee_ids', Array.from(selectedEmployees.keys()).join(','));
                window.location.href = url.toString();
            }
        }, true);
        document.addEventListener('tableUpdated', restoreVisibleSelection);

        function downloadSelectedPayslips() {
            const payRunId = document.querySelector('select[name="pay_run_id"]')?.value;
            const employeeIds = Array.from(selectedEmployees.entries())
                .filter(([, data]) => data.payslipId)
                .map(([id]) => id);

            if (!payRunId) {
                alert('Please select a posted or paid pay run first.');
                return;
            }

            if (employeeIds.length === 0) {
                alert('Please select at least one employee with a payslip in this pay run.');
                return;
            }

            const params = new URLSearchParams();
            params.append('pay_run_id', payRunId);
            employeeIds.forEach(id => params.append('employee_ids[]', id));

            window.location.href = `{{ route('payroll.employee-pay.bulk-payslips') }}?${params.toString()}`;
        }

        syncSelectedEmployeeIdsInput();
        restoreVisibleSelection();

        // ========== MODAL LOGIC ==========
        function openBulkModal() {
            if (selectedEmployees.size === 0) return;

            document.getElementById('modal-emp-count').textContent = selectedEmployees.size;

            // Build employee tags
            const tagsContainer = document.getElementById('modal-selected-tags');
            tagsContainer.innerHTML = '';
            selectedEmployees.forEach((data, id) => {
                const salaryText = data.salary && parseFloat(data.salary) > 0 ?
                    ` — {{ currency()?->symbol }} ${parseFloat(data.salary).toLocaleString()}` : '';
                tagsContainer.innerHTML +=
                    `<span class="inline-flex items-center gap-1 px-2 py-1 bg-white/20 rounded-full text-xs">
                        ${data.name}${salaryText}
                    </span>`;
            });

            document.getElementById('bulk-modal-overlay').classList.remove('hidden');
            document.getElementById('bulk-modal-overlay').classList.add('flex');
        }

        function closeBulkModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('bulk-modal-overlay').classList.add('hidden');
            document.getElementById('bulk-modal-overlay').classList.remove('flex');
        }

        function toggleSalarySource() {
            const isAuto = document.querySelector('input[name="salary_source"][value="auto"]').checked;
            const basicInputs = document.querySelectorAll('.bulk-pay-input[data-is-basic="1"]');

            basicInputs.forEach(input => {
                if (isAuto) {
                    input.disabled = true;
                    input.value = '';
                    input.placeholder = 'Auto from HR records';
                    input.classList.add('bg-blue-50', 'border-blue-300');
                } else {
                    input.disabled = false;
                    input.placeholder = '0.00';
                    input.classList.remove('bg-blue-50', 'border-blue-300');
                }
            });

            applyBulkMedicalAllowancePreview();
        }

        const bulkMedicalAllowanceRate = 0.10;
        const parseBulkMoney = (value) => parseFloat(String(value || '0').replace(/,/g, '')) || 0;

        function highlightBulkPayInput(input) {
            input.classList.add('bg-emerald-50', 'border-emerald-300');
            setTimeout(() => input.classList.remove('bg-emerald-50', 'border-emerald-300'), 2000);
        }

        function applyBulkMedicalAllowancePreview() {
            const checkbox = document.getElementById('bulk_apply_medical_allowance');
            const basicInput = document.querySelector('.bulk-pay-input[data-is-basic="1"]');
            const medicalInput = document.querySelector('.bulk-pay-input[data-is-medical="1"]');
            const isAuto = document.querySelector('input[name="salary_source"][value="auto"]').checked;

            if (!checkbox || !basicInput || !medicalInput) return;

            if (!('medicalOriginalValue' in basicInput.dataset)) {
                basicInput.dataset.medicalOriginalValue = basicInput.value || '';
                basicInput.dataset.medicalOriginalPlaceholder = basicInput.placeholder || '';
            }
            if (!('medicalOriginalValue' in medicalInput.dataset)) {
                medicalInput.dataset.medicalOriginalValue = medicalInput.value || '';
            }

            if (checkbox.checked) {
                if (isAuto) {
                    basicInput.placeholder = 'Auto from HR records minus 10%';
                    medicalInput.value = '';
                    medicalInput.placeholder = 'Auto 10% per employee';
                } else {
                    const originalBasic = parseBulkMoney(basicInput.dataset.medicalOriginalValue);
                    const originalMedical = parseBulkMoney(medicalInput.dataset.medicalOriginalValue);
                    const medicalBasis = originalBasic + originalMedical;
                    if (medicalBasis > 0) {
                        const medicalAllowanceAmount = Math.round((medicalBasis * bulkMedicalAllowanceRate) * 100) / 100;
                        basicInput.value = Math.max(0, medicalBasis - medicalAllowanceAmount).toFixed(2);
                        medicalInput.value = medicalAllowanceAmount.toFixed(2);
                    }
                }
            } else {
                basicInput.value = basicInput.dataset.medicalOriginalValue || '';
                basicInput.placeholder = basicInput.dataset.medicalOriginalPlaceholder || '0.00';
                medicalInput.value = medicalInput.dataset.medicalOriginalValue || '';
                medicalInput.placeholder = '0.00';
                delete basicInput.dataset.medicalOriginalValue;
                delete basicInput.dataset.medicalOriginalPlaceholder;
                delete medicalInput.dataset.medicalOriginalValue;
            }

            highlightBulkPayInput(medicalInput);
            highlightBulkPayInput(basicInput);
        }

        document.getElementById('bulk_apply_medical_allowance')?.addEventListener('change', applyBulkMedicalAllowancePreview);

        async function submitBulkUpdate() {
            const btn = document.getElementById('bulk-save-btn');
            btn.disabled = true;
            btn.innerHTML =
                `<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Saving...`;

            const isAuto = document.querySelector('input[name="salary_source"][value="auto"]').checked;

            // Build pay_items payload (skip tax — auto-calculated server-side)
            const payItems = {};
            document.querySelectorAll('.bulk-pay-input').forEach(input => {
                const itemId = input.dataset.itemId;
                const isBasic = input.dataset.isBasic === '1';
                const isTax = input.dataset.isTax === '1';

                // Skip Income Tax — the backend calculates it automatically
                if (isTax) return;

                if (isBasic) {
                    payItems[itemId] = {
                        amount: isAuto ? 'auto_salary' : (input.value || 0)
                    };
                } else if (input.value && parseFloat(input.value) > 0) {
                    payItems[itemId] = {
                        amount: input.value
                    };
                }
            });

            // Check if at least one item has a value
            const hasValues = Object.values(payItems).some(v => v.amount === 'auto_salary' || parseFloat(v.amount) > 0);
            if (!hasValues) {
                alert('Please enter at least one pay item amount.');
                btn.disabled = false;
                btn.innerHTML =
                    `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Apply to All Selected`;
                return;
            }

            try {
                const res = await fetch('{{ route('payroll.employee-pay.bulk-update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        employee_ids: Array.from(selectedEmployees.keys()),
                        pay_items: payItems,
                        apply_medical_allowance: document.getElementById('bulk_apply_medical_allowance').checked ? 1 : 0
                    })
                });

                const data = await res.json();

                if (data.success) {
                    closeBulkModal();
                    clearSelection();

                    // Show success toast
                    showToast(data.message, 'success');

                    // Refresh table
                    if (typeof ReportFilter !== 'undefined' && ReportFilter.refresh) {
                        ReportFilter.refresh();
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(data.message || 'An error occurred.');
                }
            } catch (err) {
                console.error('Bulk update failed', err);
                alert('An error occurred. Please try again.');
            }

            btn.disabled = false;
            btn.innerHTML =
                `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Apply to All Selected`;
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className =
                `fixed top-6 right-6 z-[60] px-6 py-3 rounded-lg shadow-xl text-white text-sm font-medium transition-all duration-300 transform translate-x-full ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full');
                toast.classList.add('translate-x-0');
            });

            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
@endpush
