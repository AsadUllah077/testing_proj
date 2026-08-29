@extends('layouts.auth')
@section('title', 'Adjustment '.$monthEndAdjustment->unique_no)

@section('content')
    <div class="p-4 space-y-4">
        <div class="bg-white border border-gray-200 rounded-[5px] px-5 py-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div><h1 class="text-lg font-bold text-gray-800">{{ $monthEndAdjustment->unique_no }}</h1><p class="text-xs text-gray-500 mt-1">{{ $monthEndAdjustment->date->format('d M Y') }} | {{ ucfirst(str_replace('_', ' ', $monthEndAdjustment->type)) }}</p></div>
            <div class="flex gap-2"><a href="{{ route('account.month-end-adjustments.index', ['period_id' => $monthEndAdjustment->accounting_period_id]) }}" class="w-9 h-9 inline-flex items-center justify-center border border-gray-300 rounded-[3px]" title="Back"><i class="fa-solid fa-arrow-left"></i></a>@if($monthEndAdjustment->status === 'draft')<form method="POST" action="{{ route('account.month-end-adjustments.post', $monthEndAdjustment) }}" onsubmit="return confirm('Post this balanced adjustment to the general ledger?')">@csrf<button class="h-9 px-4 bg-[#0078C8] text-white rounded-[3px] text-sm"><i class="fa-solid fa-check mr-2"></i>Post</button></form>@else<a href="{{ route('account.document-trace.index', ['type' => 'journal', 'reference' => $monthEndAdjustment->unique_no]) }}" class="h-9 px-4 inline-flex items-center border border-[#0078C8] text-[#0078C8] rounded-[3px] text-sm"><i class="fa-solid fa-route mr-2"></i>Trace</a>@endif</div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Status</p><p class="text-lg font-bold {{ $monthEndAdjustment->status === 'posted' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($monthEndAdjustment->status) }}</p></div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Period</p><p class="text-sm font-bold text-gray-800">{{ $monthEndAdjustment->period?->name }}</p></div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Total</p><p class="text-lg font-bold text-gray-800">{{ numberFormat($monthEndAdjustment->total_amount) }}</p></div>
            <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Asset</p><p class="text-sm font-bold text-gray-800">{{ $monthEndAdjustment->fixedAsset?->name ?: '-' }}</p></div>
        </div>

        <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200"><p class="text-sm font-semibold text-gray-800">{{ $monthEndAdjustment->narration }}</p></div>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-4 py-3 text-left">Account</th><th class="px-4 py-3 text-left">Description</th><th class="px-4 py-3 text-right">Debit</th><th class="px-4 py-3 text-right">Credit</th></tr></thead><tbody class="divide-y divide-gray-100">
                @foreach($monthEndAdjustment->lines as $line)<tr><td class="px-4 py-3"><span class="text-xs text-gray-400">{{ $accounts->get($line['account_id'])?->account_number }}</span> {{ $accounts->get($line['account_id'])?->name }}</td><td class="px-4 py-3 text-gray-600">{{ $line['description'] }}</td><td class="px-4 py-3 text-right">{{ $line['debit'] > 0 ? numberFormat($line['debit']) : '-' }}</td><td class="px-4 py-3 text-right">{{ $line['credit'] > 0 ? numberFormat($line['credit']) : '-' }}</td></tr>@endforeach
            </tbody><tfoot class="bg-gray-50 font-bold"><tr><td colspan="2" class="px-4 py-3 text-right">Totals</td><td class="px-4 py-3 text-right">{{ numberFormat(collect($monthEndAdjustment->lines)->sum('debit')) }}</td><td class="px-4 py-3 text-right">{{ numberFormat(collect($monthEndAdjustment->lines)->sum('credit')) }}</td></tr></tfoot></table></div>
        </section>

        @if($monthEndAdjustment->status === 'draft')<div class="flex justify-end"><form method="POST" action="{{ route('account.month-end-adjustments.destroy', $monthEndAdjustment) }}" onsubmit="return confirm('Delete this draft adjustment?')">@csrf @method('DELETE')<button class="h-9 px-4 border border-red-200 text-red-600 rounded-[3px] text-sm"><i class="fa-solid fa-trash mr-2"></i>Delete Draft</button></form></div>@endif
    </div>
@endsection
