@extends('layouts.auth')
@section('title', 'Month-End Adjustments')

@section('content')
    <div class="p-4 space-y-4">
        <div class="bg-white border border-gray-200 rounded-[5px]">
            <div class="px-5 py-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div><h1 class="text-lg font-bold text-gray-800">Month-End Adjustments</h1>@if($period)<p class="text-xs text-gray-500 mt-1">{{ $period->name }}</p>@endif</div>
                <div class="flex gap-2"><form method="GET"><select name="period_id" onchange="this.form.submit()" class="select2 min-w-52"><option value="">Select period</option>@foreach($periods as $item)<option value="{{ $item->id }}" @selected($period?->id === $item->id)>{{ $item->name }}</option>@endforeach</select></form><a href="{{ route('account.month-end-adjustments.create', ['period_id' => $period?->id]) }}" class="h-10 px-4 inline-flex items-center bg-[#0078C8] text-white rounded-[3px] text-sm"><i class="fa-solid fa-plus mr-2"></i>New</a></div>
            </div>
            @include('pages.account.control-center.partials.tabs')
        </div>

        @if($depreciationSuggestions->isNotEmpty())
            <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200"><h2 class="text-sm font-semibold text-gray-800">Depreciation Due</h2></div>
                <div class="divide-y divide-gray-100">
                    @foreach($depreciationSuggestions as $asset)
                        <div class="px-4 py-3 flex items-center gap-3"><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-800">{{ $asset->asset_number }} - {{ $asset->name }}</p><p class="text-xs text-gray-500">Book value {{ numberFormat($asset->current_book_value) }}</p></div><p class="text-sm font-semibold text-gray-800">{{ numberFormat($asset->suggested_amount) }}</p><a href="{{ route('account.month-end-adjustments.create', ['period_id' => $period->id, 'fixed_asset_id' => $asset->id]) }}" class="h-8 px-3 inline-flex items-center border border-[#0078C8] text-[#0078C8] rounded-[3px] text-xs">Prepare</a></div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-4 py-3 text-left">Reference</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Narration</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-center">Status</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-gray-100">
                @forelse($adjustments as $adjustment)<tr><td class="px-4 py-3 font-medium text-[#0078C8]">{{ $adjustment->unique_no }}</td><td class="px-4 py-3">{{ $adjustment->date->format('d M Y') }}</td><td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $adjustment->type) }}</td><td class="px-4 py-3 text-gray-600">{{ $adjustment->narration }}</td><td class="px-4 py-3 text-right font-semibold">{{ numberFormat($adjustment->total_amount) }}</td><td class="px-4 py-3 text-center"><span class="px-2 py-1 text-xs rounded-full {{ $adjustment->status === 'posted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ ucfirst($adjustment->status) }}</span></td><td class="px-4 py-3 text-right"><a href="{{ route('account.month-end-adjustments.show', $adjustment) }}" class="w-8 h-8 inline-flex items-center justify-center border border-gray-300 rounded-[3px]" title="View"><i class="fa-solid fa-eye text-xs"></i></a></td></tr>
                @empty<tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No adjustments for this period.</td></tr>@endforelse
            </tbody></table></div><div class="p-3 border-t">{{ $adjustments->links() }}</div>
        </section>
    </div>
@endsection
