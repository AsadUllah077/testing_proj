@extends('layouts.auth')

@section('title', 'Employee Payroll History')

@section('content')
<div class="mb-8 font-sans">
    <div class="mx-auto px-3">
        <div class="p-6 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-6 shadow-sm">
            <div>
                <h2 class="text-2xl font-light text-gray-800">Employee Payroll History</h2>
                <p class="text-sm text-gray-500 mt-1">Month by month gross, tax, net, payslip and journal links</p>
            </div>
        </div>

        @include('pages.payroll.reports.partials.tabs')

        <div class="bg-white rounded-lg border border-black/10 shadow-sm p-5 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Employee</label>
                    <select name="employee_id" class="select2 border-gray-300 rounded text-sm p-2 border w-80" onchange="this.form.submit()">
                        @foreach($employees as $item)
                            <option value="{{ $item->id }}" {{ (int) $employeeId === (int) $item->id ? 'selected' : '' }}>
                                {{ $item->full_name }} @if($item->employee_id) ({{ $item->employee_id }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Rows</label>
                    <select name="per_page" class="select2 border-gray-300 rounded text-sm p-2 border w-28" onchange="this.form.submit()">
                        @foreach([12, 24, 36, 60] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 12) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        @if($employee)
            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
                <div class="p-5 border-b bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-700">{{ $employee->full_name }}</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-left text-[10px] text-gray-400 uppercase tracking-wider bg-gray-50">
                        <tr>
                            <th class="px-5 py-3">Month</th>
                            <th class="px-5 py-3">Pay Run</th>
                            <th class="px-5 py-3 text-right">Gross</th>
                            <th class="px-5 py-3 text-right">Tax</th>
                            <th class="px-5 py-3 text-right">Net</th>
                            <th class="px-5 py-3 text-right">Payslip</th>
                            <th class="px-5 py-3 text-right">Journal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($history as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-800">{{ \Carbon\Carbon::parse($row->period_end)->format('M Y') }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $row->unique_no }}</td>
                                <td class="px-5 py-3 text-right">{{ currency()?->symbol }} {{ numberFormat($row->gross_earnings) }}</td>
                                <td class="px-5 py-3 text-right text-red-500">{{ currency()?->symbol }} {{ numberFormat($row->tax_amount) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-[#0078C8]">{{ currency()?->symbol }} {{ numberFormat($row->net_pay) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a class="text-[#0078C8] font-semibold hover:underline" href="{{ route('payroll.payslips.pdf', [$row->pay_run_id, $row->id]) }}">PDF</a>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($row->unique_no)
                                        <a class="text-[#0078C8] font-semibold hover:underline" href="{{ route('payroll.pay-runs.view-transaction', $row->unique_no) }}?employee_id={{ $employee->id }}">Journal</a>
                                    @else
                                        <span class="text-gray-400">Draft</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-gray-400">No posted payroll history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($history instanceof \Illuminate\Pagination\LengthAwarePaginator && $history->hasPages())
                    <div class="p-3 border-t"><x-data-table-paginatior :paginator="$history" /></div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
