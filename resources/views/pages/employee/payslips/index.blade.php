@extends('layouts.employee')

@section('title', 'My Payslips')

@section('content')
    <div class="min-h-screen text-slate-100 font-sans">
        <div class="mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <section
                        class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-xl shadow-slate-950/20 backdrop-blur-xl">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-sm uppercase tracking-[0.3em] text-indigo-400/80">Payslips</p>
                                <h1 class="mt-2 text-3xl font-semibold text-white">My Payslips</h1>
                                <p class="mt-1 text-sm text-slate-400">{{ $employee->full_name }}</p>
                            </div>
                            <div
                                class="rounded-2xl bg-slate-800/80 px-4 py-3 text-sm text-slate-300 shadow-inner border border-slate-700">
                                <a href="{{ route('employee.tax-certificate') }}" class="text-indigo-300 hover:text-indigo-200">Download Tax Certificate</a>
                            </div>
                        </div>
                    </section>

                    @if(session('success'))<div class="rounded-lg border border-emerald-700 bg-emerald-900/30 p-3 text-sm text-emerald-200">{{ session('success') }}</div>@endif
                    @if($errors->any())<div class="rounded-lg border border-rose-700 bg-rose-900/30 p-3 text-sm text-rose-200">{{ $errors->first() }}</div>@endif

                    <section class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach([
                            ['YTD Gross', $latestPayslip?->ytd_gross ?? 0],
                            ['YTD Deductions', $latestPayslip?->ytd_deductions ?? 0],
                            ['YTD Net', $latestPayslip?->ytd_net ?? 0],
                            ['Advance Balance', $advances->sum('remaining_amount')],
                        ] as [$label, $value])
                            <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                                <div class="text-xs text-slate-500">{{ $label }}</div>
                                <div class="mt-1 text-lg font-semibold text-white">{{ currency()?->symbol }} {{ numberFormat($value) }}</div>
                            </div>
                        @endforeach
                    </section>

                    @if($advances->isNotEmpty())
                        <section class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden">
                            <div class="px-5 py-3 border-b border-slate-800 font-medium">Salary Advance Statement</div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="text-left text-xs text-slate-500"><tr><th class="p-3">Date</th><th class="p-3">Original</th><th class="p-3">Remaining</th><th class="p-3">Recovery</th><th class="p-3">Installments</th></tr></thead>
                                    <tbody class="divide-y divide-slate-800">
                                        @foreach($advances as $advance)
                                            <tr><td class="p-3">{{ $advance->advance_date->format('Y-m-d') }}</td><td class="p-3">{{ numberFormat($advance->amount) }}</td><td class="p-3">{{ numberFormat($advance->remaining_amount) }}</td><td class="p-3">{{ $advance->recovery_method === 'percentage' ? number_format($advance->recovery_percentage,2).'%' : numberFormat($advance->installment_amount) }}</td><td class="p-3">{{ $advance->installments_recovered }} / {{ $advance->installments_total ?: '-' }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endif

                    <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-5">
                        <h2 class="font-medium">Bank Detail Change Request</h2>
                        <form method="POST" action="{{ route('employee.bank-change-request') }}" class="grid grid-cols-1 md:grid-cols-4 gap-2 mt-3">
                            @csrf
                            <input name="bank_name" required value="{{ $employee->bank_name }}" placeholder="Bank name" class="rounded bg-slate-800 border-slate-700 text-sm">
                            <input name="bank_account_number" required value="{{ $employee->bank_account_number }}" placeholder="Account number" class="rounded bg-slate-800 border-slate-700 text-sm">
                            <input name="bank_iban" value="{{ $employee->bank_iban }}" placeholder="IBAN" class="rounded bg-slate-800 border-slate-700 text-sm">
                            <button class="rounded bg-indigo-500 px-3 py-2 text-sm font-medium">Submit for Review</button>
                        </form>
                        @if($bankChangeRequests->isNotEmpty())<div class="mt-3 text-xs text-slate-400">Latest request: <span class="capitalize">{{ $bankChangeRequests->first()->status }}</span> · {{ $bankChangeRequests->first()->created_at->format('Y-m-d') }}</div>@endif
                    </section>

                    @if ($payslips->count())
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                            @foreach ($payslips as $slip)
                                <article
                                    class="group rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-slate-950/30 transition duration-300 hover:-translate-y-1 hover:border-indigo-500/30">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.32em] text-slate-500">
                                                {{ $slip->payRun->period_start->format('M d') }} –
                                                {{ $slip->payRun->period_end->format('M d, Y') }}</p>
                                            <p class="mt-2 text-sm text-slate-300">{{ ucfirst($slip->payRun->frequency) }}
                                            </p>
                                        </div>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $slip->status === 'paid' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-sky-500/15 text-sky-300' }}">
                                            {{ ucfirst($slip->status) }}
                                        </span>
                                    </div>

                                    <div class="mt-6 space-y-3 text-sm">
                                        <div class="flex items-center justify-between text-slate-400">
                                            <span>Gross Earnings</span>
                                            <span class="text-white font-medium">{{ currency()?->symbol }}
                                                {{ numberFormat($slip->gross_earnings) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-slate-400">
                                            <span>Deductions</span>
                                            <span class="text-rose-400">- {{ currency()?->symbol }}
                                                {{ numberFormat($slip->total_deductions) }}</span>
                                        </div>
                                        <div
                                            class="flex items-center justify-between border-t border-slate-800 pt-4 text-base font-semibold text-slate-100">
                                            <span>Net Pay</span>
                                            <span class="text-indigo-300">{{ currency()?->symbol }}
                                                {{ numberFormat($slip->net_pay) }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-6 grid grid-cols-2 gap-3">
                                        <a href="{{ route('employee.payslips.show', $slip->id) }}"
                                            class="inline-flex items-center justify-center rounded-2xl border border-slate-700 bg-slate-800/80 px-3 py-2 text-sm text-slate-200 transition hover:border-indigo-500/40 hover:bg-slate-700/90">
                                            View
                                        </a>
                                        <a href="{{ route('employee.payslips.pdf', $slip->id) }}"
                                            class="inline-flex items-center justify-center rounded-2xl bg-indigo-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                                            ↓ PDF
                                        </a>
                                        <a href="{{ route('employee.payslips.doc', $slip->id) }}"
                                            class="inline-flex items-center justify-center rounded-2xl bg-sky-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-sky-400">
                                            ↓ DOC
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div
                            class="mt-8 rounded-3xl border border-slate-800 bg-slate-900/70 p-4 text-center text-sm text-slate-400 shadow-lg shadow-slate-950/20">
                            {{ $payslips->links() }}
                        </div>
                    @else
                        <div
                            class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/80 p-12 text-center text-slate-300 shadow-xl shadow-slate-950/20">
                            <svg class="mx-auto h-12 w-12 text-slate-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-4 text-lg font-semibold text-slate-100">No payslips yet</p>
                            <p class="mt-2 text-sm text-slate-500">Your payslips will appear here once payroll is processed.
                            </p>
                        </div>
                    @endif
                </div>

                <aside class="lg:col-span-1">
                    <div id="payslip-chat-widget" data-payslip-id=""
                        class="sticky top-4 flex flex-col h-[520px]
                rounded-3xl border border-slate-800/60
                bg-gradient-to-br from-slate-900/90 to-slate-950
                shadow-[0_10px_40px_rgba(0,0,0,0.7)]
                overflow-hidden">

                        <!-- Header -->
                        <div
                            class="flex items-center justify-between px-4 py-3 
                    bg-slate-800 border-b border-slate-700">

                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                                <span class="text-sm font-semibold text-white">
                                    Payslip Assistant
                                </span>
                            </div>

                            <button class="text-slate-400 hover:text-white text-lg">×</button>
                        </div>

                        <!-- Subtitle -->
                        <div class="px-4 py-2 text-xs text-slate-400 border-b border-slate-800">
                            Ask me about your payslip, taxes, deductions, or earnings
                        </div>

                        <!-- Chat Messages -->
                        <div id="chat-messages" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 text-sm scroll-smooth">

                            <!-- AI Message -->
                            <div class="flex gap-2">
                                <div
                                    class="w-7 h-7 rounded-full bg-slate-700 flex items-center justify-center text-slate-300 text-xs">
                                    AI
                                </div>
                                <div class="bg-slate-800/70 px-3 py-2 rounded-xl text-slate-200 max-w-[80%]">
                                    Hi 👋 I'm your payslip assistant. Ask me anything about your earnings, deductions,
                                    taxes, or summaries.
                                </div>
                            </div>

                        </div>

                        <!-- Input -->
                        <div class="p-3 border-t border-slate-800 bg-slate-900">
                            <form id="chat-form" class="flex items-center gap-2">

                                <input id="chat-input" type="text" placeholder="Ask about your payslip..."
                                    class="flex-1 rounded-xl bg-slate-800 border border-slate-700
                              text-sm text-white placeholder-slate-500
                              px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-500/40" />

                                <button type="submit"
                                    class="px-3 py-2 rounded-xl
                               bg-slate-700 hover:bg-slate-600
                               text-white text-sm font-medium transition">
                                    Send
                                </button>
                            </form>
                        </div>

                    </div>
                </aside>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/js/payslip-chat-widget.js') }}"></script>
    @endpush
@endsection
