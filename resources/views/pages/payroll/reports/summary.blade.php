@extends('layouts.auth')

@section('title', 'Payroll Summary Report')

@section('content')
<div class="mb-8 font-sans">
    <div class="mx-auto px-3">

        <div class="p-6 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-6 shadow-sm">
            <div>
                <h2 class="text-2xl font-light text-gray-800">Payroll Summary Report</h2>
                <p class="text-sm text-gray-500 mt-1">Department breakdown &amp; period comparison</p>
            </div>
            @if($currentRun)
            <a href="{{ route('payroll.reports.summary.export') }}?pay_run_id={{ $currentRun->id }}"
                class="px-5 py-2 bg-[#0078C8] text-white rounded text-sm font-bold hover:bg-[#0f88d9] transition">
                ↓ Export PDF
            </a>
            @endif
        </div>

        @include('pages.payroll.reports.partials.tabs')

        {{-- Filters --}}
        <div class="bg-white rounded-lg border border-black/10 shadow-sm p-5 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pay Run</label>
                    <select name="pay_run_id" class="select2 border-gray-300 rounded text-sm p-2 border w-64" onchange="this.form.submit()">
                        <option value="">-- Select Pay Run --</option>
                        @foreach($payRuns as $pr)
                            <option value="{{ $pr->id }}" {{ $payRunId == $pr->id ? 'selected' : '' }}>
                                {{ $pr->period_start->format('M d') }} – {{ $pr->period_end->format('M d, Y') }}
                                ({{ ucfirst($pr->status) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Compare With</label>
                    <select name="prev_pay_run_id" class="select2 border-gray-300 rounded text-sm p-2 border w-64" onchange="this.form.submit()">
                        <option value="">-- None --</option>
                        @foreach($payRuns as $pr)
                            @if($pr->id != $payRunId)
                            <option value="{{ $pr->id }}" {{ $prevPayRunId == $pr->id ? 'selected' : '' }}>
                                {{ $pr->period_start->format('M d') }} – {{ $pr->period_end->format('M d, Y') }}
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <select
                        name="per_page"
                        class="select2 w-24 bg-white py-1.5 px-3 rounded text-sm focus:ring-0 focus:outline-none border border-gray-300"
                        onchange="this.form.submit();">
                        @foreach ([10, 20, 30, 50, 100, 200, 500, 1000] as $size)
                            <option value="{{ $size }}"
                                {{ (int) request('per_page', 10) == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    @if (count(request()->query()) > 0)
                        <a href="{{ route('payroll.reports.summary') }}"
                            class="bg-red-600 rounded px-2 py-1 text-white">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        @if($currentRun)
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            @php
                $totalGross = $currentRun->payslips->sum('gross_earnings');
                $totalDed   = $currentRun->payslips->sum('total_deductions');
                $totalNet   = $currentRun->payslips->sum('net_pay');
                $empCount   = $currentRun->payslips->count();
                $prevGross  = $previousRun?->payslips->sum('gross_earnings') ?? 0;
                $prevNet    = $previousRun?->payslips->sum('net_pay') ?? 0;
            @endphp
            <div class="bg-white rounded-lg border border-black/10 p-5 shadow-sm text-center">
                <p class="text-xs text-gray-400 uppercase font-bold">Employees</p>
                <p class="text-3xl font-light text-gray-800 mt-1">{{ $empCount }}</p>
            </div>
            <div class="bg-white rounded-lg border border-black/10 p-5 shadow-sm text-center">
                <p class="text-xs text-gray-400 uppercase font-bold">Total Gross</p>
                <p class="text-2xl font-light text-gray-800 mt-1">{{ currency()?->symbol }} {{ numberFormat($totalGross) }}</p>
                @if($prevGross > 0)
                    @php $diff = (($totalGross - $prevGross) / $prevGross) * 100; @endphp
                    <p class="text-xs mt-1 {{ $diff >= 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $diff >= 0 ? '▲' : '▼' }} {{ abs(round($diff, 1)) }}% vs prev
                    </p>
                @endif
            </div>
            <div class="bg-white rounded-lg border border-black/10 p-5 shadow-sm text-center">
                <p class="text-xs text-gray-400 uppercase font-bold">Total Deductions</p>
                <p class="text-2xl font-light text-red-500 mt-1">{{ currency()?->symbol }} {{ numberFormat($totalDed) }}</p>
            </div>
            <div class="bg-[#0078C8]/5 rounded-lg border border-[#0078C8]/20 p-5 shadow-sm text-center">
                <p class="text-xs text-[#0078C8] uppercase font-bold">Net Pay</p>
                <p class="text-2xl font-semibold text-[#0078C8] mt-1">{{ currency()?->symbol }} {{ numberFormat($totalNet) }}</p>
                @if($prevNet > 0)
                    @php $diffNet = (($totalNet - $prevNet) / $prevNet) * 100; @endphp
                    <p class="text-xs mt-1 {{ $diffNet >= 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $diffNet >= 0 ? '▲' : '▼' }} {{ abs(round($diffNet, 1)) }}% vs prev
                    </p>
                @endif
            </div>
        </div>

        {{-- Department Breakdown --}}
        <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden mb-6">
            <div class="p-5 border-b bg-gray-50">
                <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">By Department</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="text-left text-[10px] text-gray-400 uppercase tracking-wider bg-gray-50">
                    <tr>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Employees</th>
                        <th class="px-5 py-3 text-right">Gross Earnings</th>
                        <th class="px-5 py-3 text-right">Deductions</th>
                        <th class="px-5 py-3 text-right">Net Pay</th>
                        <th class="px-5 py-3 text-right">% of Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($deptBreakdown as $dept => $data)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $dept }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $data['count'] }}</td>
                        <td class="px-5 py-3 text-right">{{ currency()?->symbol }} {{ numberFormat($data['gross']) }}</td>
                        <td class="px-5 py-3 text-right text-red-500">({{ currency()?->symbol }} {{ numberFormat($data['deductions']) }})</td>
                        <td class="px-5 py-3 text-right font-bold text-[#0078C8]">{{ currency()?->symbol }} {{ numberFormat($data['net']) }}</td>
                        <td class="px-5 py-3 text-right text-gray-500">{{ $totalGross > 0 ? round($data['gross'] / $totalGross * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td class="px-5 py-3" colspan="2">TOTAL</td>
                        <td class="px-5 py-3 text-right">{{ currency()?->symbol }} {{ numberFormat($totalGross) }}</td>
                        <td class="px-5 py-3 text-right text-red-500">({{ currency()?->symbol }} {{ numberFormat($totalDed) }})</td>
                        <td class="px-5 py-3 text-right text-[#0078C8]">{{ currency()?->symbol }} {{ numberFormat($totalNet) }}</td>
                        <td class="px-5 py-3 text-right">100%</td>
                    </tr>
                </tfoot>
            </table>
            
            @if ($deptBreakdown instanceof \Illuminate\Pagination\LengthAwarePaginator && $deptBreakdown->hasPages())
                <div class="p-3 bg-white border-t border-gray-200">
                    <x-data-table-paginatior :paginator="$deptBreakdown" />
                </div>
            @endif
        </div>

        {{-- Top Pay Items --}}
        @if($topItems->count())
        <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
            <div class="p-5 border-b bg-gray-50">
                <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Top Pay Items</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="text-left text-[10px] text-gray-400 uppercase tracking-wider bg-gray-50">
                    <tr>
                        <th class="px-5 py-3">Pay Item</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3 text-right">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($topItems as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $item->name }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold
                                {{ $item->type === 'earning' ? 'bg-green-100 text-green-700' : ($item->type === 'deduction' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                                {{ ucfirst($item->type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right font-medium">{{ currency()?->symbol }} {{ numberFormat($item->total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($topItems instanceof \Illuminate\Pagination\LengthAwarePaginator && $topItems->hasPages())
                <div class="p-3 bg-white border-t border-gray-200">
                    <x-data-table-paginatior :paginator="$topItems" />
                </div>
            @endif
        </div>
        @endif

        @else
        <div class="bg-white rounded-lg border border-black/10 p-12 text-center text-gray-400 shadow-sm">
            Select a pay run above to view the summary report.
        </div>
        @endif
    </div>
</div>
@endsection
