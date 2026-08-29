@extends('layouts.employee')

@section('title', 'Payslip Details')

@section('content')
<div class="min-h-screen text-slate-100 font-sans">
    <div class="mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <main class="lg:col-span-2 space-y-6">
                <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-slate-950/20 backdrop-blur-xl">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <a href="{{ route('employee.payslips.index') }}" class="text-sm text-slate-400 hover:text-white transition">← My Payslips</a>
                            <h1 class="mt-3 text-3xl font-semibold text-white">Payslip – {{ $payslip->payRun->period_end->format('M Y') }}</h1>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('employee.payslips.pdf', $payslip->id) }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-400">
                                ↓ Download PDF
                            </a>
                            <a href="{{ route('employee.payslips.doc', $payslip->id) }}" class="inline-flex items-center justify-center rounded-2xl bg-sky-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:bg-sky-400">
                                ↓ Download DOC
                            </a>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-2 gap-5 rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-slate-950/20">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.28em] text-slate-500">Period</p>
                        <p class="text-base text-slate-200 font-medium">{{ $payslip->payRun->period_start->format('M d') }} – {{ $payslip->payRun->period_end->format('M d, Y') }}</p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.28em] text-slate-500">Payment Date</p>
                        <p class="text-base text-slate-200 font-medium">{{ $payslip->payRun->payment_date->format('M d, Y') }}</p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.28em] text-slate-500">Frequency</p>
                        <p class="text-base text-slate-200 font-medium">{{ ucfirst($payslip->payRun->frequency) }}</p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.28em] text-slate-500">Status</p>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $payslip->status === 'paid' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-sky-500/15 text-sky-300' }}">
                            {{ ucfirst($payslip->status) }}
                        </span>
                    </div>
                </section>

                @php
                    $earnings   = $payslip->items->filter(fn($i) => $i->payItem && in_array($i->payItem->type, ['earning', 'reimbursement']));
                    $deductions = $payslip->items->filter(fn($i) => $i->payItem && $i->payItem->type === 'deduction');
                    $sym        = currency()?->symbol ?? '';
                @endphp

                <section class="rounded-3xl border border-slate-800 bg-slate-900/80 shadow-xl shadow-slate-950/20">
                    <div class="flex items-center justify-between rounded-t-3xl bg-slate-950/80 px-6 py-4 border-b border-slate-800">
                        <div>
                            <p class="text-xs uppercase tracking-[0.28em] text-green-400">Earnings</p>
                            <p class="mt-1 text-sm text-slate-400">Your total earnings for this period.</p>
                        </div>
                        <span class="text-sm font-semibold text-slate-200">{{ $sym }} {{ numberFormat($payslip->gross_earnings) }}</span>
                    </div>
                    <div class="divide-y divide-slate-800 px-6 py-4">
                        @foreach($earnings as $item)
                        <div class="flex items-center justify-between py-3 text-sm text-slate-300">
                            <span>{{ $item->description }}</span>
                            <span class="font-medium text-emerald-300">{{ $sym }} {{ numberFormat($item->amount) }}</span>
                        </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-800 bg-slate-900/80 shadow-xl shadow-slate-950/20">
                    <div class="flex items-center justify-between rounded-t-3xl bg-slate-950/80 px-6 py-4 border-b border-slate-800">
                        <div>
                            <p class="text-xs uppercase tracking-[0.28em] text-rose-400">Deductions</p>
                            <p class="mt-1 text-sm text-slate-400">Total deductions withheld from your pay.</p>
                        </div>
                        <span class="text-sm font-semibold text-slate-200">{{ $sym }} {{ numberFormat($payslip->total_deductions) }}</span>
                    </div>
                    <div class="divide-y divide-slate-800 px-6 py-4">
                        @foreach($deductions as $item)
                        <div class="flex items-center justify-between py-3 text-sm text-slate-300">
                            <span>{{ $item->description }}</span>
                            <span class="font-medium text-rose-300">- {{ $sym }} {{ numberFormat($item->amount) }}</span>
                        </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-3xl border border-indigo-500/20 bg-slate-900/80 p-6 shadow-xl shadow-slate-950/20 text-center">
                    <p class="text-xs uppercase tracking-[0.28em] text-indigo-300">Net Pay</p>
                    <p class="mt-3 text-4xl font-semibold text-white">{{ $sym }} {{ numberFormat($payslip->net_pay) }}</p>
                    <p class="mt-2 text-sm text-slate-400">Amount landing in your account after deductions.</p>
                </section>

                @if($payslip->ytd_gross > 0)
                <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-xl shadow-slate-950/20">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs uppercase tracking-[0.28em] text-sky-400">Year-to-Date ({{ $payslip->payRun->period_end->year }})</p>
                        <span class="text-sm text-slate-400">Overview</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                        <div class="rounded-3xl bg-slate-950/80 p-4">
                            <p class="text-xs text-slate-500">YTD Gross</p>
                            <p class="mt-2 text-lg font-semibold text-slate-100">{{ $sym }} {{ numberFormat($payslip->ytd_gross) }}</p>
                        </div>
                        <div class="rounded-3xl bg-slate-950/80 p-4">
                            <p class="text-xs text-slate-500">YTD Deductions</p>
                            <p class="mt-2 text-lg font-semibold text-rose-400">{{ $sym }} {{ numberFormat($payslip->ytd_deductions) }}</p>
                        </div>
                        <div class="rounded-3xl bg-slate-950/80 p-4">
                            <p class="text-xs text-slate-500">YTD Net Pay</p>
                            <p class="mt-2 text-lg font-semibold text-indigo-300">{{ $sym }} {{ numberFormat($payslip->ytd_net) }}</p>
                        </div>
                    </div>
                </section>
                @endif

                @if($leaveBalances->count())
                <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-xl shadow-slate-950/20">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs uppercase tracking-[0.28em] text-emerald-300">Leave Balances</p>
                        <span class="text-sm text-slate-400">Available days</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-{{ min($leaveBalances->count(), 4) }} gap-3">
                        @foreach($leaveBalances as $lb)
                        <div class="rounded-3xl bg-slate-950/80 p-4 text-center">
                            <p class="text-xs uppercase tracking-[0.28em] text-slate-500">{{ $lb->leaveType->name ?? 'Leave' }}</p>
                            <p class="mt-2 text-2xl font-semibold text-emerald-300">{{ number_format($lb->remaining_leaves, 1) }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">of {{ $lb->total_leaves }} days</p>
                        </div>
                        @endforeach
                    </div>
                </section>
                @endif
            </main>

            <aside class="lg:col-span-1">
                <div id="payslip-chat-widget" data-payslip-id="{{ $payslip->id }}" class="sticky top-4 rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-slate-950/20">
                    <h2 class="text-base font-semibold text-white">Payslip Assistant</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-400">Ask any question about this payslip, your earnings, or deductions. The assistant is ready to help.</p>
                </div>
            </aside>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/payslip-chat-widget.js') }}"></script>
@endpush
@endsection
