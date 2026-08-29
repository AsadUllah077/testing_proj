@extends('layouts.auth')
@section('title', 'Document to Ledger Trace')

@section('content')
    <div class="p-4 space-y-4">
        <div class="bg-white border border-gray-200 rounded-[5px]">
            <div class="px-5 py-4"><h1 class="text-lg font-bold text-gray-800">Document to Ledger Trace</h1></div>
            @include('pages.account.control-center.partials.tabs')
        </div>

        <form method="GET" class="bg-white border border-gray-200 rounded-[5px] p-4 flex flex-col md:flex-row gap-3">
            <select name="type" class="h-10 border border-gray-300 rounded-[3px] px-3 text-sm md:w-52">
                @foreach(['reference' => 'Any reference', 'sale' => 'Sale invoice', 'bill' => 'Bill', 'payroll' => 'Payroll', 'journal' => 'Journal'] as $value => $label)<option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>@endforeach
            </select>
            <input name="reference" value="{{ $reference }}" placeholder="Unique number or invoice number" class="h-10 flex-1 border border-gray-300 rounded-[3px] px-3 text-sm" required>
            <button class="h-10 px-5 bg-[#0078C8] text-white rounded-[3px] text-sm"><i class="fa-solid fa-magnifying-glass mr-2"></i>Trace</button>
        </form>

        @if($reference !== '')
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Reference</p><p class="text-sm font-bold text-gray-800 break-all">{{ $uniqueNo }}</p></div>
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Ledger Rows</p><p class="text-xl font-bold text-gray-800">{{ $transactions->count() }}</p></div>
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Total Debit</p><p class="text-xl font-bold text-gray-800">{{ numberFormat($totals['debit']) }}</p></div>
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Difference</p><p class="text-xl font-bold {{ abs($totals['debit'] - $totals['credit']) <= .01 ? 'text-emerald-600' : 'text-red-600' }}">{{ numberFormat($totals['debit'] - $totals['credit']) }}</p></div>
            </div>

            <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Posting Ref.</th><th class="px-4 py-3 text-left">Account</th><th class="px-4 py-3 text-left">Description</th><th class="px-4 py-3 text-right">Debit</th><th class="px-4 py-3 text-right">Credit</th><th class="px-4 py-3 text-left">Source</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($transactions as $transaction)
                            <tr><td class="px-4 py-3">{{ optional($transaction->date)->format('d M Y') }}</td><td class="px-4 py-3 text-xs text-[#0078C8]">{{ $transaction->unique_no }}</td><td class="px-4 py-3"><span class="text-xs text-gray-400">{{ $transaction->account?->account_number }}</span> {{ $transaction->account?->name }}</td><td class="px-4 py-3 text-gray-600">{{ $transaction->description }}</td><td class="px-4 py-3 text-right">{{ $transaction->debit > 0 ? numberFormat($transaction->debit) : '-' }}</td><td class="px-4 py-3 text-right">{{ $transaction->credit > 0 ? numberFormat($transaction->credit) : '-' }}</td><td class="px-4 py-3 text-xs text-gray-500">{{ $transaction->type }}</td></tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-red-500">No ledger rows found for this reference.</td></tr>
                        @endforelse
                    </tbody>
                    @if($transactions->isNotEmpty())<tfoot class="bg-gray-50 font-bold"><tr><td colspan="4" class="px-4 py-3 text-right">Totals</td><td class="px-4 py-3 text-right">{{ numberFormat($totals['debit']) }}</td><td class="px-4 py-3 text-right">{{ numberFormat($totals['credit']) }}</td><td></td></tr></tfoot>@endif
                </table></div>
            </section>
        @endif
    </div>
@endsection
