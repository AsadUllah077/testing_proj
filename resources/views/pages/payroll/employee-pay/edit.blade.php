@extends('layouts.auth')

@section('title', 'Pay Template - ' . $employee->full_name)

@section('content')
    <div class="mb-8 font-sans">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-light text-gray-800">{{ $employee->full_name }}</h2>
                    <p class="text-sm text-gray-500">{{ $employee->employee_id ?? 'N/A' }} • {{ $employee->designation }}</p>
                </div>
                <a href="{{ get_index_url('payroll.employee-pay.index') }}"
                    class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4] hover:underline">←
                    Back to List</a>
            </div>

            <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-normal mb-4 border-b pb-2">Recurring Pay Items</h3>
                    <p class="text-sm text-gray-600 mb-6">Assign earnings and deductions that appear on every payslip for
                        this employee.</p>

                    <form id="templateForm">
                        @csrf
                        <div class="space-y-6">
                            @foreach (['earning', 'deduction', 'reimbursement'] as $type)
                                <div>
                                    <h4
                                        class="text-sm font-semibold text-gray-700 uppercase mb-3 bg-gray-50 p-2 border-l-4 border-[#0078C8]">
                                        {{ $type }}s</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @php $itemsOfType = $payItems->where('type', $type); @endphp
                                        @foreach ($itemsOfType as $item)
                                            @php
                                                $existing = $structures->where('pay_item_id', $item->id)->first();
                                                $isBasic = stripos($item->name, 'basic salary') !== false;
                                                $isMedical = stripos($item->name, 'medical allowance') !== false;
                                            @endphp
                                            <div
                                                class="flex items-center justify-between p-3 border rounded hover:bg-gray-50 transition">
                                                <div class="flex-1">
                                                    <span
                                                        class="text-sm font-medium text-gray-800">{{ $item->name }}</span>
                                                    @if (in_array($item->type, ['earning', 'reimbursement'], true))
                                                        <span
                                                            class="ml-2 text-[10px] {{ $item->is_taxable ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }} px-1.5 py-0.5 rounded-full font-bold uppercase">
                                                            {{ $item->is_taxable ? 'taxable' : 'tax exempt' }}
                                                        </span>
                                                    @endif
                                                    <p class="text-xs text-gray-400">
                                                        {{ $item->account->name ?? 'No Account' }}</p>
                                                </div>
                                                <div class="w-32">
                                                    <div class="relative">
                                                        <span
                                                            class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500 text-xs">{{ currency()?->symbol }}</span>
                                                        <input type="number" name="pay_items[{{ $item->id }}][amount]"
                                                            value="{{ $existing->amount ?? ($isBasic ? $employee->salary : '') }}" step="0.01"
                                                            data-is-basic="{{ $isBasic ? '1' : '0' }}"
                                                            data-is-medical="{{ $isMedical ? '1' : '0' }}"
                                                            class="block w-full pl-6 pr-3 py-2 border border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm"
                                                            placeholder="0.00">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if ($itemsOfType->isEmpty())
                                            <p class="text-xs text-gray-400 italic col-span-2">No {{ $type }} items
                                                defined.</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Hourly Rate for Overtime --}}
                        <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded">
                            <h4 class="text-sm font-bold text-amber-800 mb-3 uppercase tracking-wider">Overtime Settings</h4>
                            <p class="text-xs text-amber-600 mb-3">Set an hourly rate to enable overtime calculation during pay run generation.</p>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-amber-700 uppercase mb-1">Hourly Rate</label>
                                        <div class="relative w-32">
                                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500 text-xs">{{ currency()?->symbol }}</span>
                                            <input type="number" id="otHourlyRate" name="hourly_rate" step="0.01" min="0"
                                                value="{{ $structures->first()?->hourly_rate ?? '' }}"
                                                class="block w-full pl-6 pr-3 py-2 border border-amber-300 rounded focus:ring-amber-500 focus:border-amber-500 text-sm"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="pt-4">
                                        <span class="text-xs text-amber-600 font-bold">× 1.5 ×</span>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-amber-700 uppercase mb-1">Test Hours</label>
                                        <input type="number" id="otTestHours" step="0.5" min="0"
                                            class="block w-32 px-3 py-2 border border-amber-300 rounded focus:ring-amber-500 focus:border-amber-500 text-sm"
                                            placeholder="e.g. 10">
                                    </div>
                                    <div class="pt-4">
                                        <button type="button" id="applyOtBtn" 
                                            class="px-3 py-2 bg-amber-600 text-white text-xs font-bold rounded hover:bg-amber-700 transition">
                                            APPLY TO EARNINGS
                                        </button>
                                    </div>
                                </div>
                                <p class="text-[10px] text-amber-500 mt-2 italic">Calculated Amount: {{ currency()?->symbol }} <span id="otCalculatedPreview">0.00</span></p>
                            </div>
                        </div>

                        {{-- Bank Details --}}
                        <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded">
                            <h4 class="text-sm font-bold text-gray-700 mb-3 uppercase tracking-wider">Bank Details (for Bank File Export)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Bank Name</label>
                                    <input type="text" name="bank_name" value="{{ $employee->bank_name ?? '' }}"
                                        class="block w-full border border-gray-300 rounded py-2 px-3 text-sm focus:ring-[#0078C8] focus:border-[#0078C8]"
                                        placeholder="e.g. HBL">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Account Number</label>
                                    <input type="text" name="bank_account_number" value="{{ $employee->bank_account_number ?? '' }}"
                                        class="block w-full border border-gray-300 rounded py-2 px-3 text-sm focus:ring-[#0078C8] focus:border-[#0078C8]"
                                        placeholder="e.g. 01234567890">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">IBAN</label>
                                    <input type="text" name="bank_iban" value="{{ $employee->bank_iban ?? '' }}"
                                        class="block w-full border border-gray-300 rounded py-2 px-3 text-sm focus:ring-[#0078C8] focus:border-[#0078C8]"
                                        placeholder="e.g. PK36SCBL0000001123456702">
                                </div>
                            </div>
                        </div>

                        {{-- Employee Portal Password --}}
                        <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded">
                            <h4 class="text-sm font-bold text-purple-700 mb-1 uppercase tracking-wider">Employee Self-Service Portal</h4>
                            <p class="text-xs text-purple-500 mb-3">Set a password so the employee can log in at <code>/my/login</code> to view their payslips.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">New Portal Password</label>
                                    <input type="password" name="portal_password"
                                        class="block w-full border border-gray-300 rounded py-2 px-3 text-sm focus:ring-purple-500 focus:border-purple-500"
                                        placeholder="Leave blank to keep existing">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Confirm Password</label>
                                    <input type="password" name="portal_password_confirmation"
                                        class="block w-full border border-gray-300 rounded py-2 px-3 text-sm focus:ring-purple-500 focus:border-purple-500"
                                        placeholder="Repeat password">
                                </div>
                            </div>
                        </div>

                        {{-- TAX INSIGHTS & OPTIMIZATION --}}
                        <div class="mt-8 border-t pt-8">
                            <div class="flex items-center gap-2 mb-6">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-normal text-gray-800">Tax Insights & Optimization</h3>
                                    <p class="text-sm text-gray-500">Automated advice to reduce tax liability within legal limits.</p>
                                </div>
                            </div>

                            <label class="mb-6 flex items-start gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-lg cursor-pointer">
                                <input type="checkbox" name="apply_medical_allowance" value="1"
                                    class="mt-1 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                                <span>
                                    <span class="block text-sm font-bold text-emerald-800">Move 10% of gross salary before tax to Medical Allowance</span>
                                    <span class="block text-xs text-emerald-700 mt-1">This uses Basic Salary plus any existing Medical Allowance before tax deductions. It then deducts that 10% from Basic Salary and recalculates Income Tax.</span>
                                </span>
                            </label>

                            <div id="taxInsightContainer">
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    {{-- Projection Card --}}
                                    <div class="lg:col-span-1 bg-indigo-50 rounded-xl border border-indigo-100 p-6">
                                        <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-widest mb-4">Annual Projection</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <p class="text-[10px] text-indigo-400 uppercase font-bold">Estimated Annual Tax</p>
                                                <p class="text-3xl font-light text-indigo-900">{{ currency()?->symbol }} <span id="projectedAnnualTax">{{ numberFormat($taxInsights['projection']['annual_tax']) }}</span></p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4 pt-2 border-t border-indigo-100">
                                                <div>
                                                    <p class="text-[10px] text-indigo-400 uppercase font-bold">Monthly Tax</p>
                                                    <p class="text-sm font-semibold text-indigo-800">{{ currency()?->symbol }} <span id="projectedMonthlyTax">{{ numberFormat($taxInsights['projection']['monthly_tax']) }}</span></p>
                                                </div>
                                                <div>
                                                    <p class="text-[10px] text-indigo-400 uppercase font-bold">Effective Rate</p>
                                                    <p class="text-sm font-semibold text-indigo-800"><span id="effectiveRate">{{ $taxInsights['projection']['effective_rate'] }}</span>%</p>
                                                </div>
                                            </div>
                                            <div class="pt-4 mt-2 bg-white/50 rounded-lg p-3 border border-indigo-100">
                                                <p class="text-[10px] text-indigo-400 uppercase font-bold mb-1">Current Slab</p>
                                                <p class="text-xs font-bold text-indigo-900" id="currentBracket">{{ $taxInsights['projection']['current_bracket_label'] }}</p>
                                                @if($taxInsights['projection']['headroom'] > 0)
                                                    <p class="text-[10px] text-indigo-500 mt-2">
                                                        {{ currency()?->symbol }}{{ numberFormat($taxInsights['projection']['headroom']) }} headroom until next slab ({{ currency()?->symbol }}{{ numberFormat($taxInsights['projection']['next_slab_at']) }})
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Suggestions --}}
                                    <div class="lg:col-span-2 space-y-4">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest px-1">Legal Optimization Suggestions</h4>
                                        <div id="suggestionsList" class="space-y-3">
                                            @forelse($taxInsights['suggestions'] as $suggestion)
                                                <div class="bg-white border rounded-lg p-4 flex gap-4 hover:shadow-sm transition">
                                                    <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center 
                                                        {{ $suggestion['priority'] === 'high' ? 'bg-red-50 text-red-600' : ($suggestion['priority'] === 'medium' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600') }}">
                                                        @if($suggestion['type'] === 'benefit')
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        @elseif($suggestion['type'] === 'marginal_rate')
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        @else
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        @endif
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex justify-between items-start">
                                                            <h5 class="text-sm font-bold text-gray-800">{{ $suggestion['title'] }}</h5>
                                                            @if($suggestion['potential_saving'])
                                                                <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">SAVE {{ currency()?->symbol }}{{ numberFormat($suggestion['potential_saving']) }}/yr</span>
                                                            @endif
                                                        </div>
                                                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $suggestion['description'] }}</p>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="p-8 text-center bg-gray-50 rounded-lg border border-dashed">
                                                    <p class="text-sm text-gray-400">Current structure is tax-efficient or below taxable threshold.</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-6 p-4 bg-gray-50 rounded text-[10px] text-gray-400 border border-black/5 italic">
                                    Disclaimer: These suggestions are automated estimates based on current tax brackets. They do not constitute professional tax advice. Always consult with a qualified tax advisor before making structural changes to payroll.
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t flex justify-end">
                            <button type="submit"
                                class="px-8 py-3 bg-[#0078C8] text-white rounded font-medium shadow-sm hover:bg-[#006bb0] transition">
                                SAVE PAY TEMPLATE
                            </button>
                        </div>
                    </form>
                    </div>
                </div>

                {{-- Right Column: AI Insights & Benchmarking --}}
                <div class="lg:col-span-1">
                    @include('pages.payroll.employee-pay.partials.benchmarking-widget')
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('templateForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerText = 'SAVING...';

            const formData = new FormData(e.target);
            const res = await fetch('{{ route('payroll.employee-pay.update', $employee->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await res.json();
            if (data.success) {
                alert('Template saved successfully!');
                window.location.href = @js(get_index_url('payroll.employee-pay.index'));
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerText = 'SAVE PAY TEMPLATE';
            }
        }

        // --- TAX OPTIMIZATION REAL-TIME REFRESH ---
        let debounceTimer;
        const medicalAllowanceRate = 0.10;
        const parseMoney = (value) => parseFloat(String(value || '0').replace(/,/g, '')) || 0;

        const highlightPayInput = (input, classes = ['bg-emerald-50', 'border-emerald-300']) => {
            input.classList.add(...classes);
            setTimeout(() => input.classList.remove(...classes), 2000);
        };

        let refreshTaxOptimization = async () => {
            const formData = new FormData(document.getElementById('templateForm'));
            const params = new URLSearchParams(formData);
            
            try {
                const res = await fetch('{{ route('payroll.employee-pay.tax-optimize', $employee->id) }}?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                
                if (result.success) {
                    const data = result.data;
                    const proj = data.projection;
                    
                    // Update Projection Card
                    document.getElementById('projectedAnnualTax').innerText = new Intl.NumberFormat().format(proj.annual_tax);
                    document.getElementById('projectedMonthlyTax').innerText = new Intl.NumberFormat().format(proj.monthly_tax);
                    document.getElementById('effectiveRate').innerText = proj.effective_rate;
                    document.getElementById('currentBracket').innerText = proj.current_bracket_label;

                    // Store IDs for other features
                    window.currentTaxItemId = data.tax_item_id;
                    window.currentOvertimeItemId = data.overtime_item_id;

                    // Sync with Template Form Tax Field
                    if (data.tax_item_id) {
                        const taxInput = document.querySelector(`input[name="pay_items[${data.tax_item_id}][amount]"]`);
                        if (taxInput) {
                            // Only update if it's different to avoid jarring UX
                            if (taxInput.value === '' || parseFloat(taxInput.value || 0) !== parseFloat(proj.monthly_tax)) {
                                taxInput.value = proj.monthly_tax;
                                // Visual feedback on the input
                                taxInput.classList.add('bg-green-50', 'border-green-300');
                                setTimeout(() => taxInput.classList.remove('bg-green-50', 'border-green-300'), 2000);
                            }
                        }
                    }
                    
                    // Update Suggestions List
                    const list = document.getElementById('suggestionsList');
                    if (data.suggestions.length === 0) {
                        list.innerHTML = `<div class="p-8 text-center bg-gray-50 rounded-lg border border-dashed">
                                            <p class="text-sm text-gray-400">Current structure is tax-efficient or below taxable threshold.</p>
                                          </div>`;
                    } else {
                        list.innerHTML = data.suggestions.map(s => {
                            const priorityClasses = s.priority === 'high' ? 'bg-red-50 text-red-600' : (s.priority === 'medium' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600');
                            let icon = '';
                            if (s.type === 'benefit') icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                            else if (s.type === 'marginal_rate') icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                            else icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                            
                            const savingBadge = s.potential_saving ? `<span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">SAVE {{ currency()?->symbol }}${new Intl.NumberFormat().format(s.potential_saving)}/yr</span>` : '';

                            return `<div class="bg-white border rounded-lg p-4 flex gap-4 hover:shadow-sm transition">
                                        <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center ${priorityClasses}">
                                            ${icon}
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <h5 class="text-sm font-bold text-gray-800">${s.title}</h5>
                                                ${savingBadge}
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">${s.description}</p>
                                        </div>
                                    </div>`;
                        }).join('');
                    }
                }
                return result;
            } catch (err) {
                console.error('Tax optimization refresh failed', err);
                return null;
            }
        };

        const applyMedicalAllowancePreview = () => {
            const checkbox = document.querySelector('input[name="apply_medical_allowance"]');
            const basicInput = document.querySelector('input[data-is-basic="1"]');
            const medicalInput = document.querySelector('input[data-is-medical="1"]');

            if (!checkbox || !basicInput || !medicalInput) return;

            if (!('medicalOriginalValue' in basicInput.dataset)) {
                basicInput.dataset.medicalOriginalValue = basicInput.value || '';
            }
            if (!('medicalOriginalValue' in medicalInput.dataset)) {
                medicalInput.dataset.medicalOriginalValue = medicalInput.value || '';
            }

            if (checkbox.checked) {
                const currentMonthlyTax = parseMoney(document.getElementById('projectedMonthlyTax')?.innerText);
                const originalBasic = parseMoney(basicInput.dataset.medicalOriginalValue);
                const originalMedical = parseMoney(medicalInput.dataset.medicalOriginalValue);

                if (currentMonthlyTax <= 0) {
                    checkbox.checked = false;
                    alert('Medical Allowance move applies only when monthly income tax is greater than zero.');
                    return;
                }

                const medicalBasis = originalBasic + originalMedical;
                const medicalAllowanceAmount = Math.round((medicalBasis * medicalAllowanceRate) * 100) / 100;
                basicInput.value = (medicalBasis - medicalAllowanceAmount).toFixed(2);
                medicalInput.value = medicalAllowanceAmount.toFixed(2);
            } else {
                basicInput.value = basicInput.dataset.medicalOriginalValue || '';
                medicalInput.value = medicalInput.dataset.medicalOriginalValue || '';
                delete basicInput.dataset.medicalOriginalValue;
                delete medicalInput.dataset.medicalOriginalValue;
            }

            highlightPayInput(basicInput);
            highlightPayInput(medicalInput);
            refreshTaxOptimization();
        };

        // --- OVERTIME PREVIEW & APPLY ---
        const updateOtPreview = () => {
            const rate = parseFloat(document.getElementById('otHourlyRate').value || 0);
            const hours = parseFloat(document.getElementById('otTestHours').value || 0);
            const total = rate * 1.5 * hours;
            document.getElementById('otCalculatedPreview').innerText = new Intl.NumberFormat().format(total);
            return total;
        };

        document.getElementById('otHourlyRate').addEventListener('input', updateOtPreview);
        document.getElementById('otTestHours').addEventListener('input', updateOtPreview);

        document.getElementById('applyOtBtn').addEventListener('click', () => {
            const total = updateOtPreview();
            if (window.currentOvertimeItemId) {
                const otInput = document.querySelector(`input[name="pay_items[${window.currentOvertimeItemId}][amount]"]`);
                if (otInput) {
                    otInput.value = total.toFixed(2);
                    otInput.classList.add('bg-amber-50', 'border-amber-300');
                    setTimeout(() => otInput.classList.remove('bg-amber-50', 'border-amber-300'), 2000);
                    // Refresh tax since taxable earnings changed
                    refreshTaxOptimization();
                }
            } else {
                alert('Overtime pay item not found for this company.');
            }
        });

        // Attach listeners to all amount inputs
        document.querySelectorAll('input[name^="pay_items"]').forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(refreshTaxOptimization, 600);
            });
        });

        document.querySelector('input[name="apply_medical_allowance"]')?.addEventListener('change', applyMedicalAllowancePreview);

        // Initialize on load to sync if current data is stale
        window.addEventListener('load', () => {
            refreshTaxOptimization();
        });

        // --- BENCHMARKING REAL-TIME UPDATE ---
        const updateBenchmarkingUI = (benchmarks) => {
            if (!benchmarks) return;

            // Update Comp-Ratio
            const ratio = benchmarks.comp_ratio ? benchmarks.comp_ratio.toFixed(1) + '%' : 'N/A';
            document.getElementById('compRatioDisplay').innerText = ratio;
            
            // Update Percentile
            document.getElementById('internalPercentileDisplay').innerText = Math.round(benchmarks.internal_percentile) + 'th';
            
            // Update Gauge Needle
            const needle = document.getElementById('compIndicator');
            if (needle) {
                const leftPos = Math.min(100, Math.max(0, benchmarks.comp_ratio || 50));
                needle.style.left = leftPos + '%';
            }

            // Update Stats
            if (benchmarks.market_benchmark) {
                document.getElementById('marketMedianDisplay').innerText = '{{ currency()?->symbol }} ' + new Intl.NumberFormat().format(benchmarks.market_benchmark.median_salary);
            }
            document.getElementById('internalAvgDisplay').innerText = '{{ currency()?->symbol }} ' + new Intl.NumberFormat().format(benchmarks.internal_stats.avg);

            // Update Flight Risk
            const risk = benchmarks.flight_risk;
            const container = document.getElementById('flightRiskContainer');
            const levelDisplay = document.getElementById('riskLevelDisplay');
            const reasonsList = document.getElementById('riskReasonsList');

            if (container && levelDisplay && reasonsList) {
                // Update Colors
                const isHigh = risk.level === 'Critical' || risk.level === 'High';
                container.className = `p-4 rounded-2xl border ${isHigh ? 'bg-red-50 border-red-100' : 'bg-green-50 border-green-100'}`;
                levelDisplay.className = `text-sm font-black ${isHigh ? 'text-red-800' : 'text-green-800'}`;
                levelDisplay.innerText = risk.level + ' Risk';

                // Update Reasons
                if (risk.reasons.length === 0) {
                    reasonsList.innerHTML = `<li class="text-[10px] font-medium text-green-700 italic">Compensation is competitive with market and peers.</li>`;
                } else {
                    reasonsList.innerHTML = risk.reasons.map(r => `
                        <li class="text-[10px] font-medium flex items-center gap-2 ${isHigh ? 'text-red-700' : 'text-green-700'}">
                            <span class="w-1 h-1 rounded-full bg-current"></span>
                            ${r}
                        </li>
                    `).join('');
                }
            }
        };

        // Hook into existing refreshTaxOptimization
        const originalRefreshTax = refreshTaxOptimization;
        refreshTaxOptimization = async () => {
            const result = await originalRefreshTax();
            if (result && result.success && result.benchmarks) {
                // Update Benchmarking UI (New logic)
                if (typeof updateBenchmarkingUI === 'function') {
                    updateBenchmarkingUI(result.benchmarks);
                }
            }
        };
    </script>
@endpush
