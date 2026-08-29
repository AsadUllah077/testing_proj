@extends('layouts.auth')

@section('title', 'Tax Brackets (FBR)')

@section('content')
    <div class="mb-8 font-sans">
        <div class="mx-auto px-3">

            <div class="p-6 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-6 shadow-sm">
                <div>
                    <h2 class="text-2xl font-light text-gray-800">Income Tax Brackets</h2>
                    <p class="text-sm text-gray-500 mt-1">Pakistan FBR salaried income tax slabs</p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Fiscal Year Filter --}}
                    <select id="yearFilter" onchange="changeYear(this.value)"
                        class="border-gray-300 rounded text-sm p-2 border focus:ring-[#0078C8] focus:border-[#0078C8]">
                        @foreach ($fiscalYears as $yr)
                            <option value="{{ $yr->name }}" {{ $yr->name == $fiscalYear ? 'selected' : '' }}>
                                {{ $yr->name }}
                            </option>
                        @endforeach
                    </select>

                    <button onclick="seedFBR()"
                        class="px-4 py-2 bg-white border border-[#0078C8] text-[#0078C8] rounded text-sm font-bold hover:bg-[#0078C8] hover:text-white transition">
                        Load FBR {{ $fiscalYear }}
                    </button>

                    <button onclick="downloadTaxBracketTemplate()"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded text-sm font-bold hover:bg-gray-50 transition">
                        Template
                    </button>

                    <button onclick="openImportModal()"
                        class="px-4 py-2 bg-white border border-green-600 text-green-700 rounded text-sm font-bold hover:bg-green-600 hover:text-white transition">
                        Import
                    </button>

                    <button onclick="openModal('addModal')"
                        class="px-4 py-2 bg-[#0078C8] text-white rounded text-sm font-bold hover:bg-[#006bb0] transition">
                        + Add Slab
                    </button>
                </div>
            </div>

            {{-- Tax Calculator --}}
            <div class="bg-white rounded-lg border border-black/10 shadow-sm p-6 mb-6">

                <div class="flex justify-between items-start gap-6 flex-wrap">

                    <!-- Left Side -->
                    <div>
                        <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest mb-4">
                            Quick Tax Calculator
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Monthly Gross Salary (PKR)
                                </label>

                                <div class="flex gap-3">
                                    <input type="number" id="calcSalary" placeholder="e.g. 150000"
                                        class="border-gray-300 rounded text-sm p-2 border focus:ring-[#0078C8] w-full">

                                    <button onclick="calculateTax()"
                                        class="px-4 py-2 bg-[#0078C8] text-white rounded text-sm font-bold hover:bg-[#006bb0] transition whitespace-nowrap">
                                        Calculate
                                    </button>
                                </div>

                                <label class="mt-3 flex items-start gap-2 text-xs text-gray-700 cursor-pointer">
                                    <input type="checkbox" id="calcApplyMedicalAllowance"
                                        class="mt-0.5 rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                                    <span>Apply 10% Medical Allowance before tax when salary is taxable.</span>
                                </label>
                            </div>

                            <div id="calcResult"
                                class="hidden text-sm text-gray-700 bg-gray-50 rounded p-4 border border-gray-200">
                            </div>
                        </div>
                    </div>

                    <!-- Right Side -->
                    <div>
                        <form method="GET" id="perPageForm">
                            <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                            <select name="per_page"
                                class="select2 w-24 bg-white py-1.5 px-3 rounded text-sm focus:ring-0 focus:outline-none border border-gray-300"
                                onchange="document.getElementById('perPageForm').submit();">

                                @foreach ([10, 20, 30, 50, 100, 200, 500, 1000] as $size)
                                    <option value="{{ $size }}"
                                        {{ (int) request('per_page', 10) == $size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                </div>

            </div>

            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
                <div class="p-5 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">
                        Slabs — {{ $fiscalYear }}
                        @if (request()->filled('per_page') || request()->filled('fiscal_year'))
                            <a href="{{ route('payroll.tax-brackets.index') }}"
                                class="bg-red-600 rounded px-2 py-1 text-white">Reset</a>
                        @endif
                    </h3>
                    <span class="text-xs text-gray-400">{{ $brackets->count() }} slabs</span>
                </div>
                <div id="bracketsTable">
                    @include('pages.payroll.tax-brackets.partials.table', compact('brackets'))
                </div>
            </div>
        </div>
    </div>

    {{-- IMPORT MODAL --}}
    <div id="importModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-medium mb-2">Import Tax Brackets</h3>
            <p class="text-xs text-gray-500 mb-4">
                Upload CSV, XLS, or XLSX with columns: min_income, max_income, rate.
                The fiscal year is taken from the selected year above, and slab order follows the file row order.
                Base tax is calculated automatically from the previous slabs.
            </p>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="fiscal_year" id="importFiscalYear" value="{{ $fiscalYear }}">

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">File</label>
                        <input type="file" name="import_file" id="importFile" required accept=".csv,.txt,.xls,.xlsx"
                            class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                    </div>

                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="replace_existing" value="1"
                            class="mt-1 rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                        <span>Replace existing slabs for the selected fiscal year before importing.</span>
                    </label>

                    <div id="importErrors"
                        class="hidden p-3 bg-red-50 border border-red-200 rounded text-red-700 text-xs"></div>
                </div>

                <div class="mt-5 flex justify-between gap-3">
                    <button type="button" onclick="downloadTaxBracketTemplate()"
                        class="px-4 py-2 border rounded text-sm text-gray-700">Download Template</button>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeModal('importModal')"
                            class="px-4 py-2 border rounded text-sm text-gray-700">Cancel</button>
                        <button type="submit" id="importSubmitBtn"
                            class="px-6 py-2 bg-green-600 text-white rounded text-sm font-bold">Import</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ADD MODAL --}}
    <div id="addModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-medium mb-4">Add Tax Slab</h3>
            <form id="addForm">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Fiscal Year</label>
                        <select name="fiscal_year" required
                            class="select2 mt-1 block w-full border-gray-300 rounded text-sm border p-2 focus:ring-[#0078C8] focus:border-[#0078C8]">
                            <option value="">Select Year</option>
                            @foreach ($fiscalYears as $yr)
                                <option value="{{ $yr->name }}">{{ $yr->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Min Income (PKR)</label>
                            <input type="number" name="min_income" required min="0" step="1"
                                class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Max Income (blank = unlimited)</label>
                            <input type="number" name="max_income" min="0" step="1"
                                class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Rate (% over min)</label>
                        <input type="number" name="rate" required min="0" max="100" step="0.01"
                            class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                        <p class="mt-1 text-xs text-gray-500">Base tax is calculated automatically after saving.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Sort Order</label>
                        <input type="number" name="sort_order" required min="0"
                            value="{{ $brackets->count() + 1 }}"
                            class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('addModal')"
                        class="px-4 py-2 border rounded text-sm text-gray-700">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-[#0078C8] text-white rounded text-sm font-bold">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-medium mb-4">Edit Tax Slab</h3>
            <form id="editForm">
                @csrf @method('PUT')
                <input type="hidden" id="editId">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Fiscal Year</label>
                        <select name="fiscal_year" id="editYear" required
                            class="mt-1 block w-full border-gray-300 rounded text-sm border p-2 focus:ring-[#0078C8] focus:border-[#0078C8]">
                            <option value="">Select Year</option>
                            @foreach ($fiscalYears as $yr)
                                <option value="{{ $yr->name }}">{{ $yr->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Min Income</label>
                            <input type="number" name="min_income" id="editMin" required min="0"
                                class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Max Income</label>
                            <input type="number" name="max_income" id="editMax" min="0"
                                class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Rate (%)</label>
                        <input type="number" name="rate" id="editRate" required min="0" max="100"
                            step="0.01" class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                        <p class="mt-1 text-xs text-gray-500">Current base tax: <span id="editBaseText">0</span>. It will be recalculated after update.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Sort Order</label>
                        <input type="number" name="sort_order" id="editSort" required
                            class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('editModal')"
                        class="px-4 py-2 border rounded text-sm text-gray-700">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-[#0078C8] text-white rounded text-sm font-bold">Update</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openModal(id) {
            const m = document.getElementById(id);
            m?.classList.remove('hidden');
            m?.classList.add('flex');
        }

        function closeModal(id) {
            const m = document.getElementById(id);
            m?.classList.add('hidden');
            m?.classList.remove('flex');
        }

        function changeYear(year) {
            window.location.href = `{{ route('payroll.tax-brackets.index') }}?fiscal_year=${year}`;
        }

        function selectedFiscalYear() {
            return document.getElementById('yearFilter')?.value || '{{ $fiscalYear }}';
        }

        function downloadTaxBracketTemplate() {
            window.location.href = `{{ route('payroll.tax-brackets.template') }}?fiscal_year=${encodeURIComponent(selectedFiscalYear())}`;
        }

        function openImportModal() {
            document.getElementById('importFiscalYear').value = selectedFiscalYear();
            document.getElementById('importErrors').classList.add('hidden');
            document.getElementById('importErrors').innerHTML = '';
            openModal('importModal');
        }

        function showImportErrors(errors, fallbackMessage) {
            const box = document.getElementById('importErrors');
            let list = [];

            if (Array.isArray(errors)) {
                list = errors;
            } else if (errors && typeof errors === 'object') {
                list = Object.values(errors).flat();
            }

            if (!list.length && fallbackMessage) {
                list = [fallbackMessage];
            }

            box.innerHTML = list.map(error => `<div>${error}</div>`).join('');
            box.classList.remove('hidden');
        }

        async function seedFBR() {
            const year = document.getElementById('yearFilter')?.value || '{{ $fiscalYear }}';
            if (!confirm(`Load FBR ${year} tax slabs? This will replace existing slabs for this year.`)) return;
            const res = await fetch('{{ route('payroll.tax-brackets.seed-fbr') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fiscal_year: year
                })
            });
            const data = await res.json();
            alert(data.message);
            if (data.success) location.reload();
        }

        document.getElementById('addForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch('{{ route('payroll.tax-brackets.store') }}', {
                method: 'POST',
                body: new FormData(e.target),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Tax bracket added successfully!');
                closeModal('addModal');
                location.reload();
            } else alert(data.message);
        });

        document.getElementById('importForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('importSubmitBtn');
            const errorsBox = document.getElementById('importErrors');
            const fd = new FormData(e.target);

            fd.set('fiscal_year', selectedFiscalYear());
            errorsBox.classList.add('hidden');
            errorsBox.innerHTML = '';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Importing...';

            try {
                const res = await fetch('{{ route('payroll.tax-brackets.import') }}', {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    alert(data.message || 'Tax brackets imported successfully.');
                    closeModal('importModal');
                    location.reload();
                    return;
                }

                showImportErrors(data.errors, data.message || 'Import failed.');
            } catch (error) {
                showImportErrors([], 'Import failed. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Import';
            }
        });

        function editBracket(id, min, max, base, rate, sort) {
            document.getElementById('editId').value = id;
            document.getElementById('editYear').value = '{{ $fiscalYear }}';
            document.getElementById('editMin').value = min;
            document.getElementById('editMax').value = max || '';
            document.getElementById('editBaseText').innerText = Number(base || 0).toLocaleString();
            document.getElementById('editRate').value = rate;
            document.getElementById('editSort').value = sort;
            openModal('editModal');
        }

        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('editId').value;
            const fd = new FormData(e.target);
            fd.append('_method', 'PUT');
            const res = await fetch(`/payroll/tax-brackets/${id}`, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Tax bracket updated successfully!');
                closeModal('editModal');
                location.reload();
            } else alert(data.message);
        });

        async function deleteBracket(id) {
            if (!confirm('Delete this tax slab?')) return;
            const res = await fetch(`/payroll/tax-brackets/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Tax bracket deleted successfully!');
                location.reload();
            } else alert(data.message);
        }

        async function calculateTax() {
            const salary = parseFloat(document.getElementById('calcSalary').value);
            const applyMedicalAllowance = document.getElementById('calcApplyMedicalAllowance')?.checked;

            if (!salary || salary <= 0) {
                alert('Enter a valid salary.');
                return;
            }

            // Tax brackets from Laravel
            const paginatedBrackets = @json($brackets);
            const brackets = Array.isArray(paginatedBrackets) ?
                paginatedBrackets :
                paginatedBrackets.data;

            const calculateFromMonthly = (monthlySalary) => {
                const annualSalary = monthlySalary * 12;
                const slab = brackets.find(b => {
                    const min = Number(b.min_income);
                    const max = b.max_income ? Number(b.max_income) : Infinity;

                    return annualSalary > min && annualSalary <= max;
                }) || brackets[brackets.length - 1];

                let annualTaxAmount = 0;

                if (slab) {
                    annualTaxAmount =
                        parseFloat(slab.base_tax) +
                        ((annualSalary - parseFloat(slab.min_income)) * parseFloat(slab.rate) / 100);
                }

                return {
                    annualSalary,
                    annualTaxAmount: Math.max(0, annualTaxAmount),
                    monthlyTaxAmount: Math.max(0, annualTaxAmount / 12),
                    slab
                };
            };

            const originalTax = calculateFromMonthly(salary);
            const medicalAllowance = applyMedicalAllowance && originalTax.monthlyTaxAmount > 0
                ? Math.round((salary * 0.10) * 100) / 100
                : 0;
            const taxableMonthlySalary = salary - medicalAllowance;
            const calculatedTax = calculateFromMonthly(taxableMonthlySalary);

            const annual = salary * 12;
            const annualTax = calculatedTax.annualTaxAmount;
            const monthlyTax = calculatedTax.monthlyTaxAmount;
            const monthlyNetSalary = salary - monthlyTax;
            const annualNetSalary = annual - annualTax;
            const effectiveRate = annual > 0 ?
                ((annualTax / annual) * 100).toFixed(2) :
                '0.00';

            const slab = calculatedTax.slab;
            const slabNumber = slab ? slab.sort_order : 'N/A';

            const slabDescription = slab ?
                `You fall under Slab #${slabNumber} (${slab.rate}% tax rate)` :
                'No matching tax slab found.';

            const result = document.getElementById('calcResult');
            result.classList.remove('hidden');

            result.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">
                            Monthly Summary
                        </h4>

                        <div><strong>Gross Salary:</strong> PKR ${salary.toLocaleString()}</div>
                        ${applyMedicalAllowance ? `<div><strong>Medical Allowance:</strong> PKR ${medicalAllowance.toLocaleString()}</div>` : ''}
                        ${applyMedicalAllowance ? `<div><strong>Taxable Salary:</strong> PKR ${taxableMonthlySalary.toLocaleString()}</div>` : ''}
                        <div><strong>Tax:</strong> PKR ${monthlyTax.toLocaleString()}</div>
                        <div class="text-green-600 font-semibold">
                            <strong>Net Salary:</strong> PKR ${monthlyNetSalary.toLocaleString()}
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">
                            Annual Summary
                        </h4>

                        <div><strong>Gross Salary:</strong> PKR ${annual.toLocaleString()}</div>
                        ${applyMedicalAllowance ? `<div><strong>Medical Allowance:</strong> PKR ${(medicalAllowance * 12).toLocaleString()}</div>` : ''}
                        ${applyMedicalAllowance ? `<div><strong>Taxable Salary:</strong> PKR ${(taxableMonthlySalary * 12).toLocaleString()}</div>` : ''}
                        <div><strong>Tax:</strong> PKR ${annualTax.toLocaleString()}</div>
                        <div class="text-green-600 font-semibold">
                            <strong>Net Salary:</strong> PKR ${annualNetSalary.toLocaleString()}
                        </div>

                        <div class="mt-2">
                            <strong>Effective Tax Rate:</strong> ${effectiveRate}%
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-semibold">
                            ${slabDescription}
                        </span>
                    </div>

                </div>
                `;
        }
    </script>
@endpush
