@extends('layouts.auth')

@section('title', 'Payroll Analytics')

@section('content')
<div class="mb-8 font-sans">
    <div class="mx-auto px-3">
        <div class="p-6 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-6 shadow-sm">
            <div>
                <h2 class="text-2xl font-light text-gray-800">Payroll Analytics</h2>
                <p class="text-sm text-gray-500 mt-1">Average salary, tax, payroll growth and department movement</p>
            </div>
            <form method="GET">
                <select name="months" class="select2 border-gray-300 rounded text-sm p-2 border w-36" onchange="this.form.submit()">
                    @foreach([3, 6, 12, 24] as $months)
                        <option value="{{ $months }}" {{ (int) $monthsBack === $months ? 'selected' : '' }}>{{ $months }} months</option>
                    @endforeach
                </select>
            </form>
        </div>

        @include('pages.payroll.reports.partials.tabs')

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg border border-black/10 p-5 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-bold">Average Salary</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ currency()?->symbol }} {{ numberFormat($analytics['average_salary']) }}</p>
            </div>
            <div class="bg-white rounded-lg border border-black/10 p-5 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-bold">Highest Salary</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ currency()?->symbol }} {{ numberFormat($analytics['highest_salary']) }}</p>
            </div>
            <div class="bg-white rounded-lg border border-black/10 p-5 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-bold">Lowest Salary</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ currency()?->symbol }} {{ numberFormat($analytics['lowest_salary']) }}</p>
            </div>
            <div class="bg-white rounded-lg border border-black/10 p-5 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-bold">Average Tax</p>
                <p class="text-2xl font-bold text-red-500 mt-1">{{ currency()?->symbol }} {{ numberFormat($analytics['average_tax']) }}</p>
            </div>
            <div class="bg-white rounded-lg border border-black/10 p-5 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-bold">Payroll Growth</p>
                <p class="text-2xl font-bold mt-1 {{ $analytics['payroll_growth'] >= 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ $analytics['payroll_growth'] >= 0 ? '+' : '' }}{{ number_format($analytics['payroll_growth'], 1) }}%
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
                <div class="p-5 border-b bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Monthly Payroll</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-left text-[10px] text-gray-400 uppercase tracking-wider bg-gray-50">
                        <tr>
                            <th class="px-5 py-3">Month</th>
                            <th class="px-5 py-3 text-right">Headcount</th>
                            <th class="px-5 py-3 text-right">Gross</th>
                            <th class="px-5 py-3 text-right">Tax</th>
                            <th class="px-5 py-3 text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($monthly as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-800">{{ $row->period_label }}</td>
                                <td class="px-5 py-3 text-right">{{ number_format($row->headcount) }}</td>
                                <td class="px-5 py-3 text-right">{{ currency()?->symbol }} {{ numberFormat($row->gross) }}</td>
                                <td class="px-5 py-3 text-right text-red-500">{{ currency()?->symbol }} {{ numberFormat($row->tax) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-[#0078C8]">{{ currency()?->symbol }} {{ numberFormat($row->net) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No posted payroll data found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
                <div class="p-5 border-b bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest">Department Growth</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-left text-[10px] text-gray-400 uppercase tracking-wider bg-gray-50">
                        <tr>
                            <th class="px-5 py-3">Department</th>
                            <th class="px-5 py-3 text-right">Current Gross</th>
                            <th class="px-5 py-3 text-right">Previous Gross</th>
                            <th class="px-5 py-3 text-right">Growth</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($departmentGrowth as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-800">{{ $row->department }}</td>
                                <td class="px-5 py-3 text-right">{{ currency()?->symbol }} {{ numberFormat($row->gross) }}</td>
                                <td class="px-5 py-3 text-right text-gray-500">{{ currency()?->symbol }} {{ numberFormat($row->previous_gross) }}</td>
                                <td class="px-5 py-3 text-right font-bold {{ $row->growth >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $row->growth >= 0 ? '+' : '' }}{{ number_format($row->growth, 1) }}%
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">Need at least two posted pay runs for department growth.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
