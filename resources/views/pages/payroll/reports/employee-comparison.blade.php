@extends('layouts.auth')

@section('title', 'Employee Payroll Comparison')

@section('content')
<div class="mb-8 font-sans">
    <div class="mx-auto px-3">
        <div class="p-6 bg-white rounded-lg border border-black/10 mb-6 shadow-sm">
            <h2 class="text-2xl font-light text-gray-800">Employee Comparison Report</h2>
            <p class="text-sm text-gray-500 mt-1">Current month versus previous month by employee</p>
        </div>

        @include('pages.payroll.reports.partials.tabs')

        <div class="bg-white rounded-lg border border-black/10 shadow-sm p-5 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Current Pay Run</label>
                    <select name="pay_run_id" class="select2 border-gray-300 rounded text-sm p-2 border w-64" onchange="this.form.submit()">
                        @foreach($payRuns as $payRun)
                            <option value="{{ $payRun->id }}" {{ (int) $currentRunId === (int) $payRun->id ? 'selected' : '' }}>
                                {{ $payRun->period_end->format('M Y') }} ({{ $payRun->unique_no }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Compare With</label>
                    <select name="prev_pay_run_id" class="select2 border-gray-300 rounded text-sm p-2 border w-64" onchange="this.form.submit()">
                        <option value="">None</option>
                        @foreach($payRuns as $payRun)
                            @if((int) $payRun->id !== (int) $currentRunId)
                                <option value="{{ $payRun->id }}" {{ (int) $previousRunId === (int) $payRun->id ? 'selected' : '' }}>
                                    {{ $payRun->period_end->format('M Y') }} ({{ $payRun->unique_no }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="border-gray-300 rounded text-sm p-2 border w-56">
                </div>
                <button class="px-4 py-2 bg-[#0078C8] text-white rounded text-sm font-semibold">Apply</button>
            </form>
        </div>

        <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-left text-[10px] text-gray-400 uppercase tracking-wider bg-gray-50">
                    <tr>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3 text-right">Gross</th>
                        <th class="px-5 py-3 text-right">Tax</th>
                        <th class="px-5 py-3 text-right">Net</th>
                        <th class="px-5 py-3 text-right">Previous Net</th>
                        <th class="px-5 py-3 text-right">Difference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($comparison as $row)
                        @php $difference = (float) $row->net_pay - (float) $row->previous_net; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-800">{{ $row->full_name }}</div>
                                <div class="text-xs text-gray-400">{{ $row->employee_code }}</div>
                            </td>
                            <td class="px-5 py-3 text-right">{{ currency()?->symbol }} {{ numberFormat($row->gross_earnings) }}</td>
                            <td class="px-5 py-3 text-right text-red-500">{{ currency()?->symbol }} {{ numberFormat($row->tax_amount) }}</td>
                            <td class="px-5 py-3 text-right font-bold text-[#0078C8]">{{ currency()?->symbol }} {{ numberFormat($row->net_pay) }}</td>
                            <td class="px-5 py-3 text-right text-gray-500">{{ currency()?->symbol }} {{ numberFormat($row->previous_net) }}</td>
                            <td class="px-5 py-3 text-right font-bold {{ $difference >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $difference >= 0 ? '+' : '-' }}{{ currency()?->symbol }} {{ numberFormat(abs($difference)) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No comparison data found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($comparison instanceof \Illuminate\Pagination\LengthAwarePaginator && $comparison->hasPages())
                <div class="p-3 border-t"><x-data-table-paginatior :paginator="$comparison" /></div>
            @endif
        </div>
    </div>
</div>
@endsection
