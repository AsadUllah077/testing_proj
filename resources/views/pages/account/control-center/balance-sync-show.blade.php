@extends('layouts.auth')
@section('title', 'Balance Sync Details')

@section('content')
    @php
        $details = collect($balanceSyncRun->details ?? []);
        $summaryMetrics = [
            ['Source', ucfirst($balanceSyncRun->source)],
            ['Sales Updated', $balanceSyncRun->sales_updated],
            ['Bills Updated', $balanceSyncRun->bills_updated],
            ['Total Changes', $details->count()],
        ];
    @endphp
    <div class="w-full min-w-0 max-w-full p-3 sm:p-4 space-y-4 overflow-x-hidden">
        <div class="bg-white border border-gray-200 rounded-[5px]">
            <div class="px-4 py-4 sm:px-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a href="{{ route('account.balance-sync.index') }}" class="text-xs text-[#0078C8] hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i>Balance Sync History</a>
                    <h1 class="text-lg font-bold text-gray-800 mt-2">Balance Sync #{{ $balanceSyncRun->id }}</h1>
                    <p class="text-xs text-gray-500 mt-1">{{ $balanceSyncRun->company?->name }} (Company #{{ $balanceSyncRun->company_id }}) &middot; {{ $balanceSyncRun->started_at?->format('d M Y H:i:s') }} by {{ $balanceSyncRun->initiatedBy?->full_name ?: 'System / console' }}</p>
                </div>
                <span class="text-sm font-semibold {{ $balanceSyncRun->status === 'completed' ? 'text-emerald-700' : ($balanceSyncRun->status === 'failed' ? 'text-red-700' : 'text-amber-700') }}">{{ ucfirst($balanceSyncRun->status) }}</span>
            </div>
            @include('pages.account.control-center.partials.tabs')
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($summaryMetrics as [$label, $value])
                <div class="bg-white border border-gray-200 rounded-[5px] p-4 min-w-0"><div class="text-xs text-gray-500">{{ $label }}</div><div class="text-lg font-semibold text-gray-800 mt-1 break-words">{{ $value }}</div></div>
            @endforeach
        </div>

        @if($balanceSyncRun->error_message)
            <div class="border border-red-200 bg-red-50 text-red-800 rounded-[5px] px-4 py-3 text-sm">{{ $balanceSyncRun->error_message }}</div>
        @endif

        <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200"><h2 class="text-sm font-semibold text-gray-800">Changed Documents</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-3">Document</th><th class="px-4 py-3">Reason</th><th class="px-4 py-3 text-right">Final Total</th><th class="px-4 py-3 text-right">Paid</th><th class="px-4 py-3 text-right">Remaining</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Ledger Gross</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($details as $detail)
                            @php
                                $before = $detail['before'] ?? [];
                                $after = $detail['after'] ?? [];
                                $documentRoute = ($detail['document_type'] ?? '') === 'sale'
                                    ? route('account.sale-invoice.edit', $detail['document_id'])
                                    : route('account.bill-invoice.edit', $detail['document_id']);
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3"><a href="{{ $documentRoute }}" class="font-medium text-[#0078C8] hover:underline">{{ $detail['reference'] ?? '#' . $detail['document_id'] }}</a><div class="text-xs text-gray-500 capitalize mt-0.5">{{ $detail['document_type'] ?? '' }} &middot; {{ $detail['document_date'] ?? '-' }}</div></td>
                                <td class="px-4 py-3 max-w-xs text-xs text-gray-600">{{ $detail['reason'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums"><div class="text-xs text-gray-400">{{ numberFormat($before['total_after_discount'] ?? 0) }}</div><div class="font-medium">{{ numberFormat($after['total_after_discount'] ?? 0) }}</div></td>
                                <td class="px-4 py-3 text-right tabular-nums"><div class="text-xs text-gray-400">{{ numberFormat($before['paid_amount'] ?? 0) }}</div><div class="font-medium">{{ numberFormat($after['paid_amount'] ?? 0) }}</div></td>
                                <td class="px-4 py-3 text-right tabular-nums"><div class="text-xs text-gray-400">{{ numberFormat($before['remaining_amount'] ?? 0) }}</div><div class="font-medium">{{ numberFormat($after['remaining_amount'] ?? 0) }}</div></td>
                                <td class="px-4 py-3 capitalize"><div class="text-xs text-gray-400">{{ $before['approval_status'] ?? '-' }}</div><div class="font-medium">{{ $after['approval_status'] ?? '-' }}</div></td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums">{{ numberFormat($detail['ledger']['gross'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">This sync completed without changing any documents.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
