{{-- before ui enhancement like xero have --}}
@extends('layouts.auth')

@section('title', 'Transaction Details - ' . $unique_no)

@section('content')
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">Transaction Details ✨</h1>
                <p class="text-sm text-slate-500 mt-1">ID: {{ $unique_no }}</p>
            </div>

            <!-- Right: Actions -->
            <!-- <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <a href="javascript:history.back()" class="btn px-4 py-2 rounded bg-[#0078C8] text-white hover:bg-[#005e98] transition-all duration-200 hover:text-white border-slate-200 hover:border-slate-300">
                    <span><- Back</span>
                </a>
            </div> -->
        </div>

        <!-- Content -->
        <div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6 mb-8">

            @if ($line)
                <div class="mb-8">
                    <h2 class="text-xl text-slate-800 font-bold mb-4">Bank Statement Line</h2>
                    <div class="bg-slate-50 rounded p-4 border border-slate-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <div class="text-sm text-slate-500 font-medium mb-1">Date</div>
                                <div class="text-slate-800 font-bold">
                                    {{ \Carbon\Carbon::parse($line->date)->format('d M Y') }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500 font-medium mb-1">Payee</div>
                                <div class="text-slate-800 font-bold">{{ $line->payee ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500 font-medium mb-1">Reference</div>
                                <div class="text-slate-800 font-bold">{{ $line->reference ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500 font-medium mb-1">Status</div>
                                <div class="text-slate-800 font-bold">
                                    @if ($line->status === 'Reconciled')
                                        <span
                                            class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-bold">{{ $line->status }}</span>
                                    @elseif($line->status === 'Partially Reconciled')
                                        <span
                                            class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-bold">{{ $line->status }}</span>
                                    @else
                                        <span
                                            class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full text-xs font-bold">{{ $line->status }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-span-2 md:col-span-4 mt-2">
                                <div class="text-sm text-slate-500 font-medium mb-1">Description</div>
                                <div class="text-slate-800">{{ $line->description ?: '-' }}</div>
                            </div>
                            <div class="mt-4">
                                <div class="text-sm text-slate-500 font-medium mb-1">Amount</div>
                                <div class="text-lg text-slate-800 font-black">
                                    {{ $line->amount < 0 ? '-' : '' }}{{ number_format(abs($line->amount), 2) }}
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="text-sm text-slate-500 font-medium mb-1">Remaining Amount</div>
                                <div
                                    class="text-lg {{ abs($line->remaining_amount) > 0 ? 'text-red-600' : 'text-slate-800' }} font-black">
                                    {{ number_format(abs($line->remaining_amount), 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div>
                <h2 class="text-xl text-slate-800 font-bold mb-4">Associated Ledger Transactions</h2>

                @if ($transactions->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-slate-500 text-sm">No ledger transactions associated with this record yet.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full">
                            <thead
                                class="text-xs font-semibold uppercase text-slate-500 bg-slate-50 border-t border-b border-slate-200">
                                <tr>
                                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                                        <div class="font-semibold text-left">Date</div>
                                    </th>
                                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                                        <div class="font-semibold text-left">Account</div>
                                    </th>
                                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                                        <div class="font-semibold text-left">Description</div>
                                    </th>
                                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-right">
                                        <div class="font-semibold text-right">Debit</div>
                                    </th>
                                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-right">
                                        <div class="font-semibold text-right">Credit</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-200">
                                @foreach ($transactions as $txn)
                                    <tr>
                                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                                            <div class="text-slate-800">
                                                {{ \Carbon\Carbon::parse($txn->date)->format('d/m/Y') }}</div>
                                        </td>
                                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                                            <div class="font-medium text-slate-800">{{ $txn->account->name ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">{{ $txn->account->account_number ?? '' }}
                                            </div>
                                        </td>
                                        <td class="px-2 first:pl-5 last:pr-5 py-3">
                                            <div class="text-slate-800">{{ $txn->description }}</div>
                                        </td>
                                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-right">
                                            <div class="text-slate-800 font-medium">
                                                {{ $txn->debit > 0 ? number_format($txn->debit, 2) : '-' }}</div>
                                        </td>
                                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-right">
                                            <div class="text-slate-800 font-medium">
                                                {{ $txn->credit > 0 ? number_format($txn->credit, 2) : '-' }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                                <!-- Totals -->
                                <tr class="bg-slate-50">
                                    <td colspan="3" class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-right">
                                        <div class="font-bold text-slate-800">Totals:</div>
                                    </td>
                                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-right">
                                        <div class="font-bold text-slate-800">
                                            {{ number_format($transactions->sum('debit'), 2) }}</div>
                                    </td>
                                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-right">
                                        <div class="font-bold text-slate-800">
                                            {{ number_format($transactions->sum('credit'), 2) }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
