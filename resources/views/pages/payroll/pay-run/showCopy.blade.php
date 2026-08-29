@extends('layouts.auth')

@section('title', 'Pay Run Details')

@section('content')
    <div class="mb-8 font-sans">
        <div class="mx-auto px-3">

            {{-- HEADER BAR --}}
            <div class="p-6 bg-white rounded-lg border border-black/10 md:flex justify-between items-end mb-6 shadow-sm">
                <div>
                    <a href="{{ route('payroll.pay-runs.index') }}"
                        class="text-xs text-[#0078C8] hover:underline uppercase font-bold tracking-wider">← Back to List</a>
                    <h2 class="text-3xl font-light text-gray-800 mt-2">Pay Run: {{ $payRun->period_start->format('M d') }} –
                        {{ $payRun->period_end->format('M d, Y') }}</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Payment Date: <strong>{{ $payRun->payment_date->format('M d, Y') }}</strong>
                        &bull; Status: <span class="uppercase font-bold text-xs">{{ $payRun->status }}</span>
                        &bull; Approval:
                        @if($payRun->approval_status === 'approved')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">✓ Approved</span>
                        @elseif($payRun->approval_status === 'rejected')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">✗ Rejected</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-yellow-100 text-yellow-700">⏳ Pending Approval</span>
                        @endif
                    </p>
                    @if($payRun->approvedBy)
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $payRun->approval_status === 'approved' ? 'Approved' : 'Reviewed' }} by
                            <strong>{{ $payRun->approvedBy->name ?? ($payRun->approvedBy->first_name . ' ' . $payRun->approvedBy->last_name) }}</strong>
                            on {{ $payRun->approved_at->format('M d, Y H:i') }}
                            @if($payRun->approval_notes)
                                &bull; <em>"{{ $payRun->approval_notes }}"</em>
                            @endif
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2 mt-4 md:mt-0 items-center">
                    @if ($payRun->status === 'draft')
                        @if ($payRun->approval_status !== 'approved')
                            <button onclick="openApproveModal()"
                                class="px-5 py-2 bg-green-600 text-white rounded font-bold shadow hover:bg-green-700 transition text-sm">
                                ✓ Approve
                            </button>
                            <button onclick="openRejectModal()"
                                class="px-5 py-2 bg-red-500 text-white rounded font-bold shadow hover:bg-red-600 transition text-sm">
                                ✗ Reject
                            </button>
                        @else
                            <button onclick="postToAccounting()"
                                class="px-8 py-3 bg-[#0078C8] text-white rounded font-bold shadow hover:bg-[#006bb0] transition">
                                POST TO ACCOUNTING
                            </button>
                        @endif
                    @elseif($payRun->status === 'posted')
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mr-2">
                            <p class="text-xs text-blue-700 font-medium">Posted: {{ $payRun->unique_no }}</p>
                        </div>
                        <a href="{{ route('payroll.pay-runs.bank-file', $payRun->id) }}"
                            class="px-5 py-2 bg-gray-700 text-white rounded font-bold shadow hover:bg-gray-800 transition text-sm">
                            ↓ Bank File
                        </a>
                        <button onclick="openPaymentModal()"
                            class="px-8 py-3 bg-green-600 text-white rounded font-bold shadow hover:bg-green-700 transition uppercase">
                            Record Payment
                        </button>
                    @else
                        <div class="bg-green-50 border-l-4 border-green-400 p-4 mr-2">
                            <p class="text-sm text-green-700 font-bold uppercase">PAID</p>
                            <p class="text-xs text-green-600 mt-1">Fully settled in accounts.</p>
                        </div>
                        <a href="{{ route('payroll.pay-runs.bank-file', $payRun->id) }}"
                            class="px-5 py-2 bg-gray-700 text-white rounded font-bold shadow hover:bg-gray-800 transition text-sm">
                            ↓ Bank File
                        </a>
                    @endif

                    <button onclick="emailAllPayslips()"
                        class="px-5 py-2 bg-indigo-600 text-white rounded font-bold shadow hover:bg-indigo-700 transition text-sm">
                        ✉ Email All
                    </button>
                </div>
            </div>

            {{-- SUMMARY CARDS --}}
            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden mb-8">
                <div class="p-6 border-b bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Summary</h3>
                </div>
                <div class="p-10 grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold mb-1">Total Gross</p>
                        <p class="text-3xl font-light text-gray-800">
                            {{ $currencySymbol }} {{ numberFormat($summary['gross']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold mb-1">Total Deductions</p>
                        <p class="text-3xl font-light text-red-500">
                            {{ $currencySymbol }} ({{ numberFormat($summary['deductions']) }})</p>
                    </div>
                    <div class="md:col-span-2 bg-[#0078C8]/5 rounded-xl p-4 border border-[#0078C8]/10">
                        <p class="text-xs text-[#0078C8] uppercase font-bold mb-1">Net To Pay</p>
                        <p class="text-4xl font-semibold text-[#0078C8]">
                            {{ $currencySymbol }} {{ numberFormat($summary['net']) }}</p>
                    </div>
                </div>
            </div>

            {{-- ANOMALY WARNINGS PANEL --}}
            @if($payRun->status === 'draft' && !empty($anomalies['warnings']))
                @php
                    $criticalCount = $anomalies['summary']['critical'] ?? 0;
                    $warningCount = $anomalies['summary']['warning'] ?? 0;
                    $totalCount = $criticalCount + $warningCount;
                    $employeeWarnings = collect($anomalies['warnings'])->groupBy('employee_id');
                @endphp
                <div x-data="{ expanded: true, filter: 'all' }" class="mb-8 rounded-lg border shadow-sm overflow-hidden {{ $criticalCount > 0 ? 'border-red-200' : 'border-amber-200' }}">
                    {{-- Header --}}
                    <div class="px-6 py-4 flex items-center justify-between cursor-pointer {{ $criticalCount > 0 ? 'bg-red-50' : 'bg-amber-50' }}" @click="expanded = !expanded">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $criticalCount > 0 ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold {{ $criticalCount > 0 ? 'text-red-700' : 'text-amber-700' }} uppercase tracking-wider">
                                    Anomaly Detection — {{ $totalCount }} {{ Str::plural('Issue', $totalCount) }} Found
                                </h3>
                                <p class="text-xs {{ $criticalCount > 0 ? 'text-red-500' : 'text-amber-500' }} mt-0.5">
                                    @if($criticalCount > 0)<span class="font-bold">{{ $criticalCount }} critical</span> · @endif
                                    {{ $warningCount }} {{ Str::plural('warning', $warningCount) }}
                                    · Review before approving
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click.stop="fetch('{{ route('payroll.pay-runs.validate', $payRun->id) }}').then(r=>r.json()).then(d=>{if(d.success)location.reload();else alert(d.message)})"
                                class="text-xs px-3 py-1.5 bg-white border rounded shadow-sm hover:bg-gray-50 font-medium text-gray-600 transition" title="Re-check">
                                ↻ Re-check
                            </button>
                            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div x-show="expanded" x-collapse x-cloak class="bg-white">
                        {{-- Filter tabs --}}
                        <div class="px-6 pt-4 pb-2 flex gap-2 border-b border-gray-100">
                            <button @click="filter='all'" :class="filter==='all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-3 py-1 rounded text-xs font-bold transition">All ({{ $totalCount }})</button>
                            @if($criticalCount > 0)
                            <button @click="filter='critical'" :class="filter==='critical' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600 hover:bg-red-100'" class="px-3 py-1 rounded text-xs font-bold transition">Critical ({{ $criticalCount }})</button>
                            @endif
                            @if($warningCount > 0)
                            <button @click="filter='warning'" :class="filter==='warning' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-600 hover:bg-amber-100'" class="px-3 py-1 rounded text-xs font-bold transition">Warnings ({{ $warningCount }})</button>
                            @endif
                        </div>

                        {{-- Warning rows --}}
                        <div class="divide-y divide-gray-50 max-h-[400px] overflow-y-auto">
                            @foreach($anomalies['warnings'] as $warn)
                            <div x-show="filter === 'all' || filter === '{{ $warn['severity'] }}'" class="px-6 py-3 flex items-start gap-3 hover:bg-gray-50/50 transition">
                                @if($warn['severity'] === 'critical')
                                    <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-xs font-bold">!</span>
                                @else
                                    <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-amber-100 text-amber-500 flex items-center justify-center text-xs font-bold">⚠</span>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-sm font-medium text-gray-800">{{ $warn['employee_name'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $warn['severity'] === 'critical' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">{{ $warn['severity'] }}</span>
                                        <span class="text-[10px] text-gray-400 font-mono">{{ $warn['code'] }}</span>
                                    </div>
                                    <p class="text-xs text-gray-600">{{ $warn['message'] }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-3 bg-gray-50/50 border-t border-gray-100">
                            <p class="text-[10px] text-gray-400 italic">These warnings are advisory only and do not block approval or posting. Thresholds are configurable in Settings.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- EMPLOYEE BREAKDOWN --}}
            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
                <div class="p-6 border-b bg-gray-50">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Employee Breakdown</h3>
                            <span class="text-xs text-gray-500">
                                <span id="employee-match-count">{{ $summary['employee_count'] }}</span> of {{ $summary['employee_count'] }} Employees
                            </span>
                        </div>
                        <form id="employeeFilterForm" class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(220px,1fr)_120px_120px_auto] md:items-end">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Employee</label>
                                <input type="search" name="search" placeholder="Name, ID, or email"
                                    class="w-full border-gray-300 rounded text-sm p-2 border focus:ring-[#0078C8] focus:border-[#0078C8]">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Gross Min</label>
                                <input type="number" name="gross_min" min="0" step="0.01"
                                    class="w-full border-gray-300 rounded text-sm p-2 border focus:ring-[#0078C8] focus:border-[#0078C8]">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Gross Max</label>
                                <input type="number" name="gross_max" min="0" step="0.01"
                                    class="w-full border-gray-300 rounded text-sm p-2 border focus:ring-[#0078C8] focus:border-[#0078C8]">
                            </div>
                            <button type="button" id="clearEmployeeFilter"
                                class="px-4 py-2 border border-gray-300 rounded text-xs font-bold text-gray-600 hover:bg-white transition">
                                Clear
                            </button>
                        </form>
                    </div>
                </div>
                <div id="employeeBreakdownRows" class="divide-y divide-gray-100">
                    @foreach ($payRun->payslips as $slip)
                        @php
                            $slipWarningCount = $warningCounts[$slip->employee_id] ?? ['critical' => 0, 'warning' => 0];
                        @endphp
                        <div x-data="{ open: false }" class="payroll-payslip-row" data-payslip-id="{{ $slip->id }}">
                            <div class="flex items-center justify-between px-6 py-5 hover:bg-gray-50 cursor-pointer transition"
                                @click="open = !open">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3 transition-transform" :class="open ? 'rotate-90' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <div>
                                        <div class="font-medium text-gray-800 text-base flex items-center gap-2">
                                            {{ $slip->employee->full_name }}
                                            @if($slipWarningCount['critical'] > 0)
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100 text-red-600 text-[10px] font-bold" title="{{ $slipWarningCount['critical'] }} critical">{{ $slipWarningCount['critical'] }}</span>
                                            @endif
                                            @if($slipWarningCount['warning'] > 0)
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-600 text-[10px] font-bold" title="{{ $slipWarningCount['warning'] }} warnings">{{ $slipWarningCount['warning'] }}</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400">{{ $slip->employee->employee_id ?? 'N/A' }} •
                                            {{ $slip->employee->designation }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 text-right">
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold">Gross</p>
                                        <p class="font-medium">{{ $currencySymbol }} {{ numberFormat($slip->gross_earnings) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold">Deductions</p>
                                        <p class="text-red-400">{{ $currencySymbol }} ({{ numberFormat($slip->total_deductions) }})</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-[#0078C8] uppercase font-bold">Net Pay</p>
                                        <p class="font-bold text-[#0078C8]">{{ $currencySymbol }} {{ numberFormat($slip->net_pay) }}</p>
                                    </div>
                                    {{-- Action buttons --}}
                                    <div class="flex items-center gap-2" @click.stop>
                                        <a href="{{ route('payroll.payslips.pdf', [$payRun->id, $slip->id]) }}"
                                            target="_blank"
                                            class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded font-medium border transition">
                                            ↓ PDF
                                        </a>
                                        <a href="{{ route('payroll.payslips.doc', [$payRun->id, $slip->id]) }}"
                                            class="text-xs px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded font-medium border border-blue-200 transition">
                                            ↓ DOC
                                        </a>
                                        <button onclick="emailPayslip({{ $slip->id }}, '{{ $slip->employee->full_name }}')"
                                            class="text-xs px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded font-medium border border-indigo-200 transition">
                                            ✉ Email
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="open" x-collapse x-cloak class="bg-gray-50/50 px-10 py-6 border-t border-gray-50">
                                <div class="max-w-3xl mx-auto">
                                    <div class="flex justify-between items-end mb-4">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Payslip Items</h4>
                                        @if ($payRun->status === 'draft')
                                            <button onclick="openAddItemModal({{ $slip->id }})"
                                                class="text-[10px] bg-white border border-gray-200 px-3 py-1 rounded shadow-sm hover:bg-gray-50 text-gray-600 font-bold uppercase transition">
                                                + Add Item
                                            </button>
                                        @endif
                                    </div>
                                    <table class="w-full text-sm">
                                        <thead class="text-left text-[10px] text-gray-400 uppercase tracking-wider">
                                            <tr>
                                                <th class="py-2">Description</th>
                                                <th class="py-2 text-right">Amount</th>
                                                @if ($payRun->status === 'draft')<th class="py-2 w-10"></th>@endif
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($slip->items as $item)
                                                <tr>
                                                    <td class="py-3 text-gray-700">
                                                        {{ $item->description }}
                                                        @if ($item->payItem->calculation_type === 'percentage')
                                                            <span class="text-[10px] text-gray-400 ml-2">({{ $item->payItem->percentage_value }}%
                                                                of {{ $item->payItem->baseItem->name ?? 'Base' }})</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 text-right">
                                                        @if ($payRun->status === 'draft')
                                                            <span class="text-gray-500 text-xs">{{ $currencySymbol }}</span>
                                                            <input type="number" step="0.01" value="{{ $item->amount }}"
                                                                onchange="updateItemAmount({{ $item->id }}, this.value)"
                                                                class="w-24 text-right border-gray-200 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8]">
                                                        @else
                                                            {{ $currencySymbol }} {{ numberFormat($item->amount) }}
                                                        @endif
                                                    </td>
                                                    @if ($payRun->status === 'draft')
                                                        <td class="py-3 text-right">
                                                            <button onclick="removeItem({{ $item->id }})"
                                                                class="text-gray-300 hover:text-red-500 transition">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                    </path>
                                                                </svg>
                                                            </button>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    @if($slip->ytd_gross > 0)
                                        <div class="mt-4 p-3 bg-blue-50 rounded border border-blue-100 text-xs text-blue-700 flex flex-wrap gap-6">
                                            <span><strong>YTD Gross:</strong> {{ $currencySymbol }} {{ numberFormat($slip->ytd_gross) }}</span>
                                            <span><strong>YTD Deductions:</strong> {{ $currencySymbol }} {{ numberFormat($slip->ytd_deductions) }}</span>
                                            <span><strong>YTD Net:</strong> {{ $currencySymbol }} {{ numberFormat($slip->ytd_net) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div id="employeeFilterEmpty" class="hidden px-6 py-10 text-center text-sm text-gray-400">
                        No employees match the current filter.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- APPROVE MODAL --}}
    <div id="approveModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-medium mb-2 text-green-700">Approve Pay Run</h3>
            <p class="text-sm text-gray-500 mb-4">This will allow the pay run to be posted to accounting.</p>
            <form id="approveForm">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Notes (optional)</label>
                    <textarea name="notes" rows="2" placeholder="Approval notes..."
                        class="w-full border-gray-300 rounded text-sm p-2 border focus:ring-green-500 focus:border-green-500"></textarea>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('approveModal')" class="px-4 py-2 border rounded text-sm text-gray-700">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded text-sm font-bold">Approve</button>
                </div>
            </form>
        </div>
    </div>

    {{-- REJECT MODAL --}}
    <div id="rejectModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-medium mb-2 text-red-600">Reject Pay Run</h3>
            <p class="text-sm text-gray-500 mb-4">Please provide a reason for rejection.</p>
            <form id="rejectForm">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Rejection Notes</label>
                    <textarea name="notes" rows="3" placeholder="Reason for rejection..." required
                        class="w-full border-gray-300 rounded text-sm p-2 border focus:ring-red-500 focus:border-red-500"></textarea>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 border rounded text-sm text-gray-700">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-red-500 text-white rounded text-sm font-bold">Reject</button>
                </div>
            </form>
        </div>
    </div>

    @if ($payRun->status === 'draft')
        <div id="addItemModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
                <h3 class="text-lg font-medium mb-4">Add Extra Item</h3>
                <form id="addItemForm">
                    @csrf
                    <input type="hidden" id="modalPayslipId" name="payslipId">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Select Pay Item</label>
                            <select name="pay_item_id" required
                                class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                                @foreach ($payItems as $pi)
                                    <option value="{{ $pi->id }}">{{ $pi->name }} ({{ $pi->type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Amount</label>
                            <input type="number" name="amount" step="0.01" required
                                class="mt-1 block w-full border-gray-300 rounded shadow-sm sm:text-sm border p-2">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="closeAddItemModal()"
                            class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700">CANCEL</button>
                        <button type="submit" class="px-6 py-2 bg-[#0078C8] text-white rounded text-xs font-medium">ADD ITEM</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($payRun->status === 'posted')
        <div id="recordPaymentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-medium mb-4">Record Payroll Payment</h3>
                <form id="recordPaymentForm">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Payment Date</label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                                class="date mt-1 block w-full border-gray-300 rounded shadow-sm sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Bank Account</label>
                            <select name="bank_account_id" required
                                class="select2 mt-1 block w-full border-gray-300 rounded shadow-sm sm:text-sm border p-2">
                                @foreach ($bankAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->account_number }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Reference (Optional)</label>
                            <input type="text" name="reference" placeholder="e.g. Bank Transfer Ref"
                                class="mt-1 block w-full border-gray-300 rounded sm:text-sm border p-2">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="closePaymentModal()"
                            class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700">CANCEL</button>
                        <button type="submit"
                            class="px-6 py-2 bg-green-600 text-white rounded text-xs font-bold uppercase">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div id="payDeductionModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-medium mb-4 text-orange-600">Pay Deduction / Tax</h3>
            <p class="text-xs text-gray-500 mb-4 italic">Settling liability accounts from this pay run.</p>
            <form id="payDeductionForm">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Select Deduction Item</label>
                        <select name="pay_item_id" required class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                            @foreach ($deductionItems as $di)
                                <option value="{{ $di->id }}">{{ $di->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full border-gray-300 rounded text-sm border p-2 date">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Paid from Bank Account</label>
                        <select name="bank_account_id" required class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->account_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Reference (Optional)</label>
                        <input type="text" name="reference" placeholder="e.g. Tax Payment Ref" class="mt-1 block w-full border-gray-300 rounded text-sm border p-2">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeDeductionModal()" class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700">CANCEL</button>
                    <button type="submit" class="px-6 py-2 bg-orange-500 text-white rounded text-xs font-bold uppercase">Record Tax Payment</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function closeModal(id) { const m = document.getElementById(id); m?.classList.add('hidden'); m?.classList.remove('flex'); }
    function openModal(id)  { const m = document.getElementById(id); m?.classList.remove('hidden'); m?.classList.add('flex'); }

    const employeeFilterForm = document.getElementById('employeeFilterForm');
    const employeeRows = Array.from(document.querySelectorAll('.payroll-payslip-row'));
    const employeeMatchCount = document.getElementById('employee-match-count');
    const employeeFilterEmpty = document.getElementById('employeeFilterEmpty');
    const clearEmployeeFilter = document.getElementById('clearEmployeeFilter');
    let employeeFilterTimer = null;
    let employeeFilterAbort = null;

    async function applyEmployeeFilter() {
        if (!employeeFilterForm) return;

        const params = new URLSearchParams(new FormData(employeeFilterForm));
        [...params.entries()].forEach(([key, value]) => {
            if (value === '') params.delete(key);
        });

        employeeFilterAbort?.abort();
        employeeFilterAbort = new AbortController();

        try {
            const res = await fetch(`{{ route('payroll.pay-runs.filter-payslips', $payRun->id) }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: employeeFilterAbort.signal,
            });

            const data = await res.json();
            if (!data.success) return;

            const visibleIds = new Set((data.ids || []).map(String));
            let visible = 0;

            employeeRows.forEach(row => {
                const show = visibleIds.has(row.dataset.payslipId);
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            if (employeeMatchCount) employeeMatchCount.textContent = data.matched ?? visible;
            employeeFilterEmpty?.classList.toggle('hidden', visible > 0);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Employee filter failed', error);
            }
        }
    }

    employeeFilterForm?.addEventListener('input', () => {
        clearTimeout(employeeFilterTimer);
        employeeFilterTimer = setTimeout(applyEmployeeFilter, 250);
    });

    employeeFilterForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        applyEmployeeFilter();
    });

    clearEmployeeFilter?.addEventListener('click', () => {
        employeeFilterForm.reset();
        applyEmployeeFilter();
    });

    function openApproveModal() { openModal('approveModal'); }
    function openRejectModal()  { openModal('rejectModal'); }

    document.getElementById('approveForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('{{ route('payroll.pay-runs.approve', $payRun->id) }}', {
            method: 'POST', body: new FormData(e.target),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (data.success) { alert(data.message); location.reload(); } else alert(data.message);
    });

    document.getElementById('rejectForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('{{ route('payroll.pay-runs.reject', $payRun->id) }}', {
            method: 'POST', body: new FormData(e.target),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (data.success) { alert(data.message); location.reload(); } else alert(data.message);
    });

    async function postToAccounting() {
        if (!confirm('This will lock the pay run and generate accounting journal entries. Proceed?')) return;
        const res = await fetch('{{ route('payroll.pay-runs.post', $payRun->id) }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (data.success) { alert('Posted successfully!'); location.reload(); } else alert(data.message);
    }

    async function emailPayslip(payslipId, name) {
        if (!confirm(`Email payslip to ${name}?`)) return;
        const res = await fetch(`/payroll/pay-runs/{{ $payRun->id }}/payslips/${payslipId}/email`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        alert(data.message);
    }

    async function emailAllPayslips() {
        if (!confirm('Email payslips to all employees in this pay run?')) return;
        const res = await fetch('{{ route('payroll.pay-runs.email-all', $payRun->id) }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        alert(data.message);
    }

    async function updateItemAmount(itemId, amount) {
        const res = await fetch(`/payroll/payslips/items/${itemId}/update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ amount })
        });
        const data = await res.json();
        if (data.success) location.reload(); else alert(data.message);
    }

    async function removeItem(itemId) {
        if (!confirm('Remove this item?')) return;
        const res = await fetch(`/payroll/payslips/items/${itemId}`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (data.success) location.reload();
    }

    const modal = document.getElementById('addItemModal');
    function openAddItemModal(payslipId) {
        document.getElementById('modalPayslipId').value = payslipId;
        modal?.classList.remove('hidden'); modal?.classList.add('flex');
    }
    function closeAddItemModal() { modal?.classList.add('hidden'); modal?.classList.remove('flex'); }

    document.getElementById('addItemForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payslipId = document.getElementById('modalPayslipId').value;
        const res = await fetch(`/payroll/payslips/${payslipId}/items/add`, {
            method: 'POST', body: new FormData(e.target),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (data.success) location.reload(); else alert(data.message);
    });

    function openPaymentModal()  { openModal('recordPaymentModal'); }
    function closePaymentModal() { closeModal('recordPaymentModal'); }

    document.getElementById('recordPaymentForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('{{ route('payroll.pay-runs.pay', $payRun->id) }}', {
            method: 'POST', body: new FormData(e.target),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (data.success) { alert('Payment recorded!'); location.reload(); } else alert(data.message);
    });

    function openDeductionModal()  { openModal('payDeductionModal'); }
    function closeDeductionModal() { closeModal('payDeductionModal'); }

    document.getElementById('payDeductionForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('{{ route('payroll.pay-runs.pay-deduction', $payRun->id) }}', {
            method: 'POST', body: new FormData(e.target),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (data.success) { alert('Deduction payment recorded!'); location.reload(); } else alert(data.message);
    });
</script>
@endpush
