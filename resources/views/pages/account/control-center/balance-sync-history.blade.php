@extends('layouts.auth')
@section('title', 'Balance Sync History')

@section('content')
    <div class="w-full min-w-0 max-w-full p-3 sm:p-4 space-y-4 overflow-x-hidden">
        <div class="bg-white border border-gray-200 rounded-[5px]">
            <div class="px-4 py-4 sm:px-5">
                <h1 class="text-lg font-bold text-gray-800">Balance Sync History</h1>
                <p class="text-xs text-gray-500 mt-1">{{ $company->name }} (Company #{{ $company->id }}) &middot; Audit trail of document totals and paid or remaining balances synchronized from the ledger.</p>
            </div>
            @include('pages.account.control-center.partials.tabs')
        </div>

        <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Run</th>
                            <th class="px-4 py-3">Started</th>
                            <th class="px-4 py-3">Initiated By</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3 text-right">Sales</th>
                            <th class="px-4 py-3 text-right">Bills</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($runs as $run)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">#{{ $run->id }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $run->started_at?->format('d M Y H:i:s') }}</td>
                                <td class="px-4 py-3">{{ $run->initiatedBy?->full_name ?: 'System / console' }}</td>
                                <td class="px-4 py-3 capitalize">{{ $run->source }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $run->sales_updated }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $run->bills_updated }}</td>
                                <td class="px-4 py-3"><span class="font-medium {{ $run->status === 'completed' ? 'text-emerald-700' : ($run->status === 'failed' ? 'text-red-700' : 'text-amber-700') }}">{{ ucfirst($run->status) }}</span></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('account.balance-sync.show', $run) }}" class="text-[#0078C8] hover:underline">View details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500">No balance sync runs have been recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3">{{ $runs->links() }}</div>
        </section>
    </div>
@endsection
