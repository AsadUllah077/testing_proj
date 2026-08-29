@extends('layouts.auth')
@section('title') Budget vs Actual @endsection

@section('content')
<div class="p-4 mx-auto">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-5 bg-white p-5">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Budget vs Actual</h1>
            <p class="text-xs text-gray-500 mt-0.5">Compare budgeted targets against actual transactions</p>
        </div>
        <a href="{{ route('account.budget.index') }}{{ request('period_id') ? '?period_id='.request('period_id') : '' }}"
            class="bg-[#0078C8] text-white text-sm font-semibold px-4 py-2 rounded-[3px] flex items-center gap-1.5">
            <i class="fa-solid fa-pen text-xs"></i> Edit Budgets
        </a>
    </div>

    {{-- Period Selector --}}
    <form method="GET" class="bg-white border border-gray-200 rounded-[5px] p-4 mb-4 flex items-center gap-3">
        <label class="text-sm font-medium text-gray-600">Period:</label>
        <select name="period_id" onchange="this.form.submit()"
            class="select2 border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
            @foreach ($periods as $p)
                <option value="{{ $p->id }}" @selected($period?->id == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        @if ($period)
            <span class="text-xs text-gray-500 w-[250px]">
                {{ $period->start_date->format('d M Y') }} — {{ $period->end_date->format('d M Y') }}
            </span>
        @endif
    </form>

    @if ($rows->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-[4px] p-4 text-sm text-amber-700">
            No budget data found for this period.
            <a href="{{ route('account.budget.index', ['period_id' => $period?->id]) }}" class="underline font-medium">Set budgets first →</a>
        </div>
    @else

    {{-- Summary Cards --}}
    <div class="grid grid-cols-3 gap-4 mb-5">
        <div class="bg-white border border-gray-200 rounded-[5px] p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Total Budgeted</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totals['budget'], 2) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-[5px] p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Total Actual</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totals['actual'], 2) }}</p>
        </div>
        <div class="border rounded-[5px] p-4 {{ $totals['variance'] >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }}">
            <p class="text-xs uppercase font-semibold tracking-wide {{ $totals['variance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                Total Variance
            </p>
            <p class="text-2xl font-bold mt-1 {{ $totals['variance'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                {{ $totals['variance'] >= 0 ? '+' : '' }}{{ number_format($totals['variance'], 2) }}
            </p>
        </div>
    </div>

    {{-- AI Budget Insights --}}
    @if (!empty($insights))
        @php
            $healthColors = [
                'excellent'  => 'bg-emerald-50 border-emerald-200',
                'good'       => 'bg-emerald-50 border-emerald-200',
                'caution'    => 'bg-yellow-50 border-yellow-200',
                'over_budget'=> 'bg-red-50 border-red-200',
                'no_budget'  => 'bg-gray-50 border-gray-200',
            ];
            $healthLabels = [
                'excellent'   => 'Excellent',
                'good'        => 'On Track',
                'caution'     => 'Caution',
                'over_budget' => 'Over Budget',
                'no_budget'   => 'No Budgets',
            ];
            $healthTextColors = [
                'excellent'   => 'text-emerald-700',
                'good'        => 'text-emerald-600',
                'caution'     => 'text-yellow-700',
                'over_budget' => 'text-red-700',
                'no_budget'   => 'text-gray-500',
            ];
            $insightTypeColors = [
                'success' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                'warning' => 'text-yellow-800 bg-yellow-50 border-yellow-200',
                'danger'  => 'text-red-700 bg-red-50 border-red-200',
                'info'    => 'text-blue-700 bg-blue-50 border-blue-200',
            ];
            $insightIcons = [
                'check'          => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'alert'          => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z',
                'trending_up'    => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                'trending_down'  => 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6',
                'pie_chart'      => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
                'compare_arrows' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                'warning'        => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'info'           => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ];
        @endphp

        <div class="mb-5 border rounded-[5px] {{ $healthColors[$health] ?? $healthColors['no_budget'] }} p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="bg-white/80 rounded-full p-1.5">
                        <svg class="w-4 h-4 {{ $healthTextColors[$health] ?? 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-wide {{ $healthTextColors[$health] ?? 'text-gray-500' }}">
                        Budget Insights
                    </p>
                </div>
                <span class="text-xs font-bold uppercase px-2 py-0.5 rounded {{ $healthColors[$health] ?? '' }} {{ $healthTextColors[$health] ?? '' }}">
                    {{ $healthLabels[$health] ?? 'Unknown' }}
                </span>
            </div>

            <div class="space-y-2">
                @foreach ($insights as $insight)
                    <div class="flex items-start gap-2 border rounded px-3 py-2 text-sm {{ $insightTypeColors[$insight['type']] ?? $insightTypeColors['info'] }}">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $insightIcons[$insight['icon']] ?? $insightIcons['info'] }}" />
                        </svg>
                        <span>{{ $insight['message'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Report Table --}}
    <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Account</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Budget</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actual</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Variance</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Used %</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-36">Progress</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $currentCategory = null; @endphp
                @foreach ($rows as $row)
                    @if ($currentCategory !== $row['account']->category)
                        @php $currentCategory = $row['account']->category; @endphp
                        <tr class="bg-gray-50">
                            <td colspan="7" class="px-4 py-2 text-xs font-bold text-gray-500 uppercase tracking-wide">
                                {{ $currentCategory }}
                            </td>
                        </tr>
                    @endif
                    <tr class="{{ $row['over_budget'] ? 'bg-red-50/40' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-3 text-gray-800 font-medium">{{ $row['account']->name }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $row['account']->category }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            {{ $row['budget'] > 0 ? number_format($row['budget'], 2) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">
                            {{ number_format($row['actual'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold
                            {{ $row['variance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $row['variance'] >= 0 ? '+' : '' }}{{ number_format($row['variance'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-xs">
                            @if ($row['pct'] !== null)
                                <span class="font-semibold {{ $row['pct'] > 100 ? 'text-red-600' : 'text-gray-700' }}">
                                    {{ $row['pct'] }}%
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($row['budget'] > 0 && $row['actual'] >= 0)
                                @php $pct = min(100, $row['pct'] ?? 0); @endphp
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ ($row['pct'] ?? 0) > 100 ? 'bg-red-500' : (($row['pct'] ?? 0) > 80 ? 'bg-amber-400' : 'bg-emerald-500') }}"
                                        style="width: {{ $pct }}%"></div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-gray-300 bg-gray-50">
                <tr>
                    <td colspan="2" class="px-4 py-3 font-bold text-gray-700">Total</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800">{{ number_format($totals['budget'], 2) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800">{{ number_format($totals['actual'], 2) }}</td>
                    <td class="px-4 py-3 text-right font-bold {{ $totals['variance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $totals['variance'] >= 0 ? '+' : '' }}{{ number_format($totals['variance'], 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @endif
</div>
@endsection
