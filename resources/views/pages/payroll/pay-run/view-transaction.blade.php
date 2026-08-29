@extends('layouts.auth')

@section('title', 'Pay Run Transactions')

@php
    $currencySymbol = currency()?->symbol;
    $isAllTransactions = blank($payRun ?? null);
    $transactionRoute = $isAllTransactions
        ? route('payroll.pay-runs.transactions')
        : route('payroll.pay-runs.view-transaction', $payRun->unique_no);
    $pdfExportRoute = $isAllTransactions
        ? route('payroll.pay-runs.transactions.export-pdf')
        : route('payroll.pay-runs.export-transaction-pdf', $payRun->unique_no);
    $csvExportRoute = $isAllTransactions
        ? route('payroll.pay-runs.transactions.export-csv')
        : route('payroll.pay-runs.export-transaction-csv', $payRun->unique_no);
    $summary = $summary ?? ['employee_count' => 0, 'gross' => 0, 'deductions' => 0, 'net' => 0, 'pay_run_count' => 0];
    $selectedEmployeeIds = collect((array) request('employee_ids', []));

    if (request('employee_id')) {
        $selectedEmployeeIds->push(request('employee_id'));
    }

    $selectedEmployeeIds = $selectedEmployeeIds->map(fn($id) => (string) $id)->unique()->all();
@endphp

@section('content')
    <div class="mb-8 font-sans">
        <div class="mx-auto px-3">
            {{-- HEADER BAR --}}
            <div class="p-6 bg-white rounded-lg border border-black/10 md:flex justify-between items-end mb-6 shadow-sm">
                <div>
                    <a href="{{ get_index_url('payroll.pay-runs.index') }}"
                        class="text-xs text-[#0078C8] hover:underline uppercase font-bold tracking-wider">← Back to List</a>
                    <h2 class="text-3xl font-light text-gray-800 mt-2">
                        {{ $isAllTransactions ? 'All Pay Run Transactions' : 'Transactions: ' . $payRun->unique_no }}
                    </h2>
                    @if($isAllTransactions)
                        <p class="text-sm text-gray-500 mt-1">
                            Posted/Paid Pay Runs: <strong>{{ $payRunCount ?? $summary['pay_run_count'] ?? 0 }}</strong>
                            &bull; Showing posted payroll journal transactions
                        </p>
                    @else
                    <p class="text-sm text-gray-500 mt-1">
                        Pay Run Period: <strong>{{ $payRun->period_start->format('M d') }} –
                        {{ $payRun->period_end->format('M d, Y') }}</strong>
                        &bull; Payment Date: <strong>{{ $payRun->payment_date->format('M d, Y') }}</strong>
                        &bull; Status: <span class="uppercase font-bold text-xs">{{ $payRun->status }}</span>
                    </p>
                    @endif
                    @if(!$isAllTransactions && $payRun->approvedBy)
                        <p class="text-xs text-gray-400 mt-1">
                            Approved by
                            <strong>{{ $payRun->approvedBy->name ?? ($payRun->approvedBy->first_name . ' ' . $payRun->approvedBy->last_name) }}</strong>
                            on {{ $payRun->approved_at->format('M d, Y H:i') }}
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2 mt-4 md:mt-0 items-center">
                    <a href="{{ $pdfExportRoute . '?' . http_build_query(request()->except('page')) }}"
                        class="px-4 py-2 text-[#0078C8] rounded font-bold shadow hover:bg-[#0078C8] hover:text-white transition text-sm">
                        <svg class="w-4 h-4 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export PDF
                    </a>
                    <a href="{{ $csvExportRoute . '?' . http_build_query(request()->except('page')) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white rounded font-bold shadow hover:bg-[#0078C8] transition text-sm">
                        <svg class="w-4 h-4 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    @unless($isAllTransactions)
                        <a href="{{ route('payroll.pay-runs.show', $payRun->id) }}"
                            class="px-4 py-2 bg-gray-700 text-white rounded font-bold shadow hover:bg-gray-800 transition text-sm">
                            View Pay Run Details
                        </a>
                    @endunless
                </div>
            </div>

            {{-- SUMMARY CARDS --}}
            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden mb-8">
                <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Pay Run Summary</h3>
                    <span class="text-xs text-gray-500">{{ $summary['employee_count'] }} Employees Processed</span>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold mb-1">Total Gross Earnings</p>
                        <p class="text-2xl font-light text-gray-800">
                            {{ $currencySymbol }} {{ numberFormat($summary['gross']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold mb-1">Total Deductions & Tax</p>
                        <p class="text-2xl font-light text-red-500">
                            {{ $currencySymbol }} ({{ numberFormat($summary['deductions']) }})</p>
                    </div>
                    <div class="bg-[#0078C8]/5 rounded-xl p-3 border border-[#0078C8]/10">
                        <p class="text-xs text-[#0078C8] uppercase font-bold mb-1">Net Pay to Employees</p>
                        <p class="text-2xl font-semibold text-[#0078C8]">
                            {{ $currencySymbol }} {{ numberFormat($summary['net']) }}</p>
                    </div>
                </div>
            </div>

            {{-- FILTER SECTION --}}
            <div class="bg-white rounded-lg border border-black/10 shadow-sm p-8 mb-8">
                <form action="{{ $transactionRoute }}" method="GET" class="flex flex-col md:flex-row gap-4 items-center w-full">
                    <div class="flex-1 w-full relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee, description, account..." class="pl-10 w-full text-sm border border-gray-500 rounded-md focus:ring-[#0078C8] focus:border-[#0078C8] shadow-sm py-2">
                    </div>
                    <div class="w-full md:w-64">
                        <select name="employee_ids[]" multiple data-placeholder="All Employees" class="select2 w-full text-sm border-gray-200 rounded-md focus:ring-[#0078C8] focus:border-[#0078C8] shadow-sm py-2">
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ in_array((string) $employee->id, $selectedEmployeeIds, true) ? 'selected' : '' }}>
                                    {{ $employee->full_name }}{{ $employee->employee_id ? ' (' . $employee->employee_id . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:w-32">
                        <input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}" placeholder="Min Amount" class="w-full text-sm border-gray-200 rounded-md focus:ring-[#0078C8] focus:border-[#0078C8] shadow-sm py-2">
                    </div>
                    <div class="w-full md:w-32">
                        <input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}" placeholder="Max Amount" class="w-full text-sm border-gray-200 rounded-md focus:ring-[#0078C8] focus:border-[#0078C8] shadow-sm py-2">
                    </div>
                    <div class="w-full md:w-32">
                        <select name="per_page" class="w-full text-sm border-gray-200 rounded-md focus:ring-[#0078C8] focus:border-[#0078C8] shadow-sm py-2" onchange="this.form.submit()">
                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50 Per Page</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Per Page</option>
                            <option value="250" {{ request('per_page') == 250 ? 'selected' : '' }}>250 Per Page</option>
                        </select>
                    </div>
                    <div class="flex gap-2 w-full md:w-auto">
                        <button type="submit" class="px-5 py-2 bg-[#0078C8] text-white text-sm font-bold rounded shadow hover:bg-[#0078C8] transition w-full md:w-auto">
                            Filter
                        </button>
                        @if(request()->hasAny(['search', 'employee_id', 'employee_ids', 'min_amount', 'max_amount']) || request('per_page', 50) != 50)
                            <a href="{{ $transactionRoute }}" class="px-5 py-2 bg-gray-100 text-gray-600 text-sm font-bold rounded shadow-sm hover:bg-gray-200 transition text-center w-full md:w-auto">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- TRANSACTIONS TABLE --}}
            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
                <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Journal Entries</h3>
                    <span class="text-xs text-gray-500">{{ $transactions->total() }} Entries</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-white border-b border-gray-100 text-[10px] text-gray-400 uppercase tracking-wider text-left">
                            <tr>
                                @if($isAllTransactions)
                                    <th class="px-6 py-4 font-bold">Pay Run</th>
                                @endif
                                <th class="px-6 py-4 font-bold">Date</th>
                                <th class="px-6 py-4 font-bold">Account</th>
                                <th class="px-6 py-4 font-bold">Description</th>
                                <th class="px-6 py-4 font-bold">Counterparty</th>
                                <th class="px-6 py-4 font-bold text-right">Debit</th>
                                <th class="px-6 py-4 font-bold text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @php
                                $totalDebit = 0;
                                $totalCredit = 0;
                                $groupedTransactions = $transactions->groupBy(function($txn) {
                                    return $txn->counterparty ?: 'General Ledger';
                                });
                            @endphp
                            @forelse ($groupedTransactions as $counterparty => $txns)
                                @php
                                    $counterpartyDebit = 0;
                                    $counterpartyCredit = 0;
                                @endphp
                                @foreach ($txns as $txn)
                                    @php
                                        $counterpartyDebit += $txn->debit;
                                        $counterpartyCredit += $txn->credit;
                                        $totalDebit += $txn->debit;
                                        $totalCredit += $txn->credit;
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition group">
                                        @if($isAllTransactions)
                                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap font-medium">
                                                {{ $txn->unique_no }}
                                            </td>
                                        @endif
                                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                            {{ $txn->date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-800">{{ $txn->account->name ?? 'Unknown Account' }}</div>
                                            <div class="text-[10px] text-gray-400 font-mono">{{ $txn->account->account_number ?? '' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            {{ $txn->description ?: 'Payroll Journal Entry' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs text-gray-500">{{ $txn->counterparty ?? '-' }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium {{ $txn->debit > 0 ? 'text-gray-800' : 'text-gray-300' }}">
                                            {{ $txn->debit > 0 ? $currencySymbol . ' ' . numberFormat($txn->debit) : '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium {{ $txn->credit > 0 ? 'text-gray-800' : 'text-gray-300' }}">
                                            {{ $txn->credit > 0 ? $currencySymbol . ' ' . numberFormat($txn->credit) : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                                
                                {{-- Counterparty Subtotal Row --}}
                                <tr class="bg-[#0078C8]/5 border-t border-[#0078C8]/10">
                                    <td colspan="{{ $isAllTransactions ? 5 : 4 }}" class="px-6 py-3 text-right text-xs font-bold text-[#0078C8] uppercase tracking-wider">
                                        Total for {{ $counterparty }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold text-[#0078C8]">
                                        {{ $currencySymbol }} {{ numberFormat($counterpartyDebit) }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold text-[#0078C8]">
                                        {{ $currencySymbol }} {{ numberFormat($counterpartyCredit) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAllTransactions ? 7 : 6 }}" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-500">No transactions found for this Pay Run.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($transactions->isNotEmpty())
                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="{{ $isAllTransactions ? 5 : 4 }}" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Totals
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-800 border-double border-b-4 border-gray-300">
                                        {{ $currencySymbol }} {{ numberFormat($totalDebit) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-800 border-double border-b-4 border-gray-300">
                                        {{ $currencySymbol }} {{ numberFormat($totalCredit) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <x-data-table-paginatior :paginator="$transactions"/>
            </div>
        </div>
    </div>
@endsection
