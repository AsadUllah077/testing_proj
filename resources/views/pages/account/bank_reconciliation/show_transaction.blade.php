@extends('layouts.auth')

@section('title', 'Transaction Details - ' . $unique_no)

@section('content')
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full mx-auto">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                        {{ $detail['source_module'] }}
                    </span>
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ ($detail['status'] ?? '') === 'Reconciled' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $detail['status'] }}
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl text-slate-900 font-bold">{{ $unique_no }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ $detail['source_document'] ?: 'Bank transaction audit trail' }}</p>
            </div>
            <a href="javascript:history.back()"
                class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm font-bold hover:bg-slate-50">
                Back
            </a>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <section class="bg-white rounded-lg border border-slate-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-bold text-slate-900">General Information</h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-md border border-slate-200 p-4">
                            <div class="text-xs uppercase font-bold text-slate-500">Source Module</div>
                            <div class="mt-1 font-bold text-slate-900">{{ $detail['source_module'] }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-md border border-slate-200 p-4">
                            <div class="text-xs uppercase font-bold text-slate-500">Source Document</div>
                            <div class="mt-1 font-bold text-slate-900">{{ $detail['source_document'] ?: '-' }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-md border border-slate-200 p-4">
                            <div class="text-xs uppercase font-bold text-slate-500">Payment Date</div>
                            <div class="mt-1 font-bold text-slate-900">{{ $detail['payment_date'] ?: '-' }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-md border border-slate-200 p-4">
                            <div class="text-xs uppercase font-bold text-slate-500">Amount</div>
                            <div class="mt-1 font-black text-slate-900">{{ number_format($detail['amount'], 2) }}</div>
                        </div>
                    </div>
                </section>

                @if ($line)
                    <section class="bg-white rounded-lg border border-slate-200 shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-200">
                            <h2 class="text-lg font-bold text-slate-900">Matched Statement</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs uppercase font-bold text-slate-500">Statement Date</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $line->date?->format('d M Y') }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase font-bold text-slate-500">Statement Reference</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $line->reference ?: $line->unique_no }}
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-xs uppercase font-bold text-slate-500">Statement Description</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $line->description ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase font-bold text-slate-500">Payee</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $line->payee ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase font-bold text-slate-500">Statement Amount</div>
                                <div class="mt-1 font-black text-slate-900">{{ number_format((float) $line->amount, 2) }}
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                <section class="bg-white rounded-lg border border-slate-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-900">Accounting Entry</h2>
                        <span class="text-xs text-slate-500">Debit / Credit</span>
                    </div>
                    @if ($transactions->isEmpty())
                        <div class="p-8 text-center text-slate-500">No ledger transactions associated with this record yet.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3 text-left">Date</th>
                                        <th class="px-5 py-3 text-left">Account</th>
                                        <th class="px-5 py-3 text-left">Description</th>
                                        <th class="px-5 py-3 text-right">Debit</th>
                                        <th class="px-5 py-3 text-right">Credit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($transactions as $txn)
                                        <tr>
                                            <td class="px-5 py-3 whitespace-nowrap">{{ $txn->date?->format('d M Y') }}</td>
                                            <td class="px-5 py-3">
                                                <div class="font-bold text-slate-900">{{ $txn->account->name ?? '-' }}
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    {{ $txn->account->account_number ?? '' }}</div>
                                            </td>
                                            <td class="px-5 py-3 text-slate-700">{{ $txn->description }}</td>
                                            <td class="px-5 py-3 text-right font-semibold">
                                                {{ $txn->debit > 0 ? number_format((float) $txn->debit, 2) : '-' }}</td>
                                            <td class="px-5 py-3 text-right font-semibold">
                                                {{ $txn->credit > 0 ? number_format((float) $txn->credit, 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-slate-50 border-t border-slate-200">
                                    <tr>
                                        <td colspan="3" class="px-5 py-3 text-right font-bold">Totals</td>
                                        <td class="px-5 py-3 text-right font-black">
                                            {{ number_format((float) $transactions->sum('debit'), 2) }}</td>
                                        <td class="px-5 py-3 text-right font-black">
                                            {{ number_format((float) $transactions->sum('credit'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <aside class="space-y-6">
                <section class="bg-white rounded-lg border border-slate-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-bold text-slate-900">Audit Trail</h2>
                    </div>
                    <ol class="p-5 space-y-4">
                        @foreach ($detail['timeline'] as $step)
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full bg-[#0078C8]"></span>
                                <div>
                                    <div class="text-sm font-bold text-slate-900">{{ $step['label'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $step['value'] }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section class="bg-white rounded-lg border border-slate-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-bold text-slate-900">Related Documents</h2>
                    </div>
                    <div class="p-5 flex flex-col gap-2">
                        @if ($detail['source_url'])
                            <a href="{{ $detail['source_url'] }}"
                                class="px-4 py-2 rounded-md bg-[#0078C8] text-white text-sm font-bold text-center hover:bg-[#005a96]">
                                View Source Document
                            </a>
                        @endif
                        <a href="{{ route('account.report.ledger', ['search' => $unique_no]) }}"
                            class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm font-bold text-center hover:bg-slate-50">
                            View Related Ledger
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
