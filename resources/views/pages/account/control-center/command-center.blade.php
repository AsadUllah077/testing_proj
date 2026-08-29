@extends('layouts.auth')
@section('title', 'Accounting Command Center')

@section('content')
    <div class="w-full min-w-0 max-w-full p-3 sm:p-4 space-y-4 overflow-x-hidden">
        <div class="bg-white border border-gray-200 rounded-[5px]">
            <div class="px-4 py-4 sm:px-5 grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end gap-4">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Accounting Command Center</h1>
                    <p class="text-xs text-gray-500 mt-1">{{ $health['start']->format('d M Y') }} to {{ $health['end']->format('d M Y') }}</p>
                </div>
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-[minmax(140px,1fr)_minmax(140px,1fr)_40px] gap-2 min-w-0">
                    <label class="min-w-0"><span class="block mb-1 text-xs text-gray-500">From</span><input type="date" name="start_date" value="{{ $health['start']->toDateString() }}" class="date block w-full h-9 border border-gray-300 rounded-[3px] px-3 text-sm"></label>
                    <label class="min-w-0"><span class="block mb-1 text-xs text-gray-500">To</span><input type="date" name="end_date" value="{{ $health['end']->toDateString() }}" class="date block w-full h-9 border border-gray-300 rounded-[3px] px-3 text-sm"></label>
                    <button class="h-9 w-10 self-end bg-[#0078C8] text-white rounded-[3px] text-sm hover:bg-[#006bb0]" title="Apply date range" aria-label="Apply date range"><i class="fa-solid fa-filter"></i></button>
                </form>
            </div>
            @include('pages.account.control-center.partials.tabs')
        </div>

        @if(session('success'))
            <div class="border border-emerald-200 bg-emerald-50 text-emerald-800 rounded-[5px] px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="border border-red-200 bg-red-50 text-red-800 rounded-[5px] px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-3">
            <div class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500">Health Score</p><p class="text-xl font-bold {{ $health['score'] >= 80 ? 'text-emerald-600' : ($health['score'] >= 60 ? 'text-amber-600' : 'text-red-600') }}">{{ $health['score'] }}%</p>
            </div>
            @foreach ([
                ['Sales', $metrics['sales'], true], ['Purchases', $metrics['purchases'], true],
                ['Receivables', $metrics['receivables'], true], ['Payables', $metrics['payables'], true],
                ['Bank Lines', $metrics['unreconciled'], false], ['Open Payroll', $metrics['open_payroll'], false]
            ] as [$label, $value, $money])
                <div class="bg-white border border-gray-200 rounded-[5px] p-4 min-w-0">
                    <p class="text-xs text-gray-500 truncate">{{ $label }}</p>
                    <p class="text-lg font-bold text-gray-800 break-words">{{ $money ? (currency()['symbol'] ?? '') . ' ' . numberFormat($value) : number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-800">Attention Queue</h2>
                    <div class="flex items-center gap-3">
                        @if(collect($health['checks'])->whereIn('key', ['sale_balance_mismatch', 'bill_balance_mismatch'])->sum('count') > 0)
                            <form method="POST" action="{{ route('account.dashboard.sync-document-balances') }}" onsubmit="return confirm('Repair legacy document totals and synchronize paid and remaining balances from the ledger?')">
                                @csrf
                                <button class="text-xs text-emerald-700 hover:underline" title="Sync document balances"><i class="fa-solid fa-arrows-rotate mr-1"></i>Sync balances</button>
                            </form>
                        @endif
                        <a href="{{ route('account.balance-sync.index') }}" class="text-xs text-gray-600 hover:text-[#0078C8]">History</a>
                        <a href="{{ route('account.health-center.index') }}" class="text-xs text-[#0078C8]">View all</a>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse (collect($health['checks'])->whereIn('status', ['critical', 'warning'])->take(8) as $check)
                        <a href="{{ $check['url'] ?: route('account.health-center.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 focus:outline-none focus:bg-blue-50">
                            <span class="shrink-0 w-2 h-2 rounded-full {{ $check['status'] === 'critical' ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-gray-800">{{ $check['label'] }}</span>
                                    <span class="inline-flex min-w-5 h-5 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold {{ $check['status'] === 'critical' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">{{ $check['count'] }}</span>
                                </span>
                                <span class="block text-xs text-gray-500 mt-0.5">{{ $check['message'] }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-[#0078C8] hidden sm:inline">Review</span>
                            <i class="fa-solid fa-chevron-right shrink-0 text-gray-300"></i>
                        </a>
                    @empty
                        <p class="p-6 text-sm text-emerald-700">No current exceptions.</p>
                    @endforelse
                </div>
            </section>

            <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200"><h2 class="text-sm font-semibold text-gray-800">Recent Ledger Activity</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-4 py-2 text-left">Date</th><th class="px-4 py-2 text-left">Reference</th><th class="px-4 py-2 text-left">Account</th><th class="px-4 py-2 text-right">Amount</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentPostings as $posting)
                                <tr><td class="px-4 py-2">{{ optional($posting->date)->format('d M') }}</td><td class="px-4 py-2"><a class="text-[#0078C8]" href="{{ route('account.document-trace.index', ['reference' => $posting->unique_no]) }}">{{ $posting->unique_no }}</a></td><td class="px-4 py-2">{{ $posting->account?->name }}</td><td class="px-4 py-2 text-right">{{ numberFormat(max($posting->debit, $posting->credit)) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No postings in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection
