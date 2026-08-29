@extends('layouts.auth')
@section('title') {{ $fixedAsset->asset_number }} @endsection
@section('content')
<div class="p-4 mx-auto">

    <div class="flex items-start justify-between mb-5 bg-white rounded-xl p-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('account.fixed-asset.index') }}" class="text-gray-400 hover:text-gray-600 text-xl">&larr;</a>
            <div>
                <h1 class="text-lg font-bold text-gray-800">{{ $fixedAsset->name }}</h1>
                <p class="text-xs text-gray-500 mt-0.5">{{ $fixedAsset->asset_number }} · {{ $fixedAsset->category }}</p>
            </div>
        </div>
        <a href="{{ route('account.fixed-asset.edit', $fixedAsset) }}"
            class="bg-[#0078C8] text-white text-sm font-semibold px-4 py-2 rounded-[3px]">Edit</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">

            {{-- Value Summary --}}
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Purchase Cost</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">{{ number_format($fixedAsset->purchase_cost, 2) }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Acc. Depreciation</p>
                    <p class="text-xl font-bold text-amber-600 mt-1">{{ number_format($fixedAsset->accumulated_depreciation, 2) }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Net Book Value</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ number_format($fixedAsset->current_book_value, 2) }}</p>
                </div>
            </div>

            {{-- Progress bar --}}
            @php
                $pct = $fixedAsset->purchase_cost > 0
                    ? min(100, round(($fixedAsset->accumulated_depreciation / $fixedAsset->purchase_cost) * 100, 1))
                    : 0;
            @endphp
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <div class="flex justify-between text-xs text-gray-500 mb-2">
                    <span>Depreciation Progress</span>
                    <span>{{ $pct }}% depreciated — {{ $fixedAsset->monthsInService() }} months in service of {{ $fixedAsset->useful_life_months }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="h-3 rounded-full {{ $pct >= 100 ? 'bg-blue-500' : 'bg-emerald-500' }}"
                        style="width: {{ $pct }}%"></div>
                </div>
            </div>

            {{-- Depreciation Schedule --}}
            <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase">Depreciation Schedule</h3>
                </div>
                <div class="overflow-y-auto max-h-72">
                    <table class="w-full text-xs">
                        <thead class="sticky top-0 bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500">Month</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-500">Depreciation</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-500">Book Value</th>
                                <th class="px-4 py-2 text-center font-semibold text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($schedule as $row)
                            <tr class="{{ $row['done'] ? 'bg-green-50/40' : '' }}">
                                <td class="px-4 py-1.5 text-gray-700">{{ $row['month'] }}</td>
                                <td class="px-4 py-1.5 text-right text-gray-700">{{ number_format($row['dep'], 2) }}</td>
                                <td class="px-4 py-1.5 text-right font-medium text-gray-800">{{ number_format($row['book_value'], 2) }}</td>
                                <td class="px-4 py-1.5 text-center">
                                    @if ($row['done'])
                                        <span class="text-emerald-500 text-xs">✓ Posted</span>
                                    @else
                                        <span class="text-gray-300 text-xs">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-4 text-sm">
            <div class="bg-white border border-gray-200 rounded-[5px] p-4 space-y-3">
                @foreach ([
                    ['Status', ucfirst(str_replace('_', ' ', $fixedAsset->status))],
                    ['Method', $fixedAsset->depreciation_method === 'declining_balance' ? 'Double-Declining' : 'Straight-Line'],
                    ['Residual Value', number_format($fixedAsset->residual_value, 2)],
                    ['Useful Life', $fixedAsset->useful_life_months . ' months'],
                    ['Monthly Dep.', number_format($fixedAsset->monthlyDepreciation(), 2)],
                    ['Purchase Date', $fixedAsset->purchase_date->format('d M Y')],
                    ['In Service', $fixedAsset->in_service_date->format('d M Y')],
                    ['Last Dep. Run', $fixedAsset->last_depreciation_date?->format('d M Y') ?? '—'],
                ] as [$label, $val])
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">{{ $label }}</p>
                    <p class="text-gray-700 mt-0.5">{{ $val }}</p>
                </div>
                @endforeach
            </div>

            <div class="bg-white border border-gray-200 rounded-[5px] p-4 space-y-2 text-xs text-gray-600">
                <p class="font-semibold text-gray-500 uppercase">Account Mapping</p>
                <p><span class="text-gray-400">Asset:</span> {{ $fixedAsset->assetAccount?->name ?? '—' }}</p>
                <p><span class="text-gray-400">Dep. Expense:</span> {{ $fixedAsset->depreciationExpenseAccount?->name ?? '—' }}</p>
                <p><span class="text-gray-400">Acc. Dep.:</span> {{ $fixedAsset->accumulatedDepreciationAccount?->name ?? '—' }}</p>
            </div>
        </div>
    </div>

</div>
@endsection
