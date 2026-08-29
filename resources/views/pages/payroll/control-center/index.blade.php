@extends('layouts.auth')

@section('title', 'Payroll Control Center')

@section('content')
<x-acccount.loader />
<div class="mx-auto px-3 pb-8 space-y-4">
    <div class="bg-white border border-black/10 rounded-md p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-medium">Payroll Control Center</h1>
            <p class="text-sm text-gray-500 mt-1">Review calculation, employee, accounting, and approval controls before posting.</p>
        </div>
        @if($selectedPayRun)
            <form method="POST" action="{{ route('payroll.control-center.run', $selectedPayRun) }}">
                @csrf
                <button class="px-4 py-2 bg-[#0078C8] text-white rounded text-xs hover:bg-[#006bb0]">
                    <i class="fa-solid fa-shield-check mr-1"></i> RUN PREFLIGHT
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="border border-green-200 bg-green-50 text-green-800 rounded p-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="border border-red-200 bg-red-50 text-red-800 rounded p-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-[360px_minmax(0,1fr)] gap-4 items-start">
        <div class="bg-white border border-black/10 rounded-md overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50 text-sm font-medium">Pay Runs</div>
            <div class="divide-y max-h-[720px] overflow-y-auto">
                @forelse($payRuns as $run)
                    <a href="{{ route('payroll.control-center.index', ['pay_run_id' => $run->id]) }}"
                        class="block px-4 py-3 {{ $selectedPayRun?->id === $run->id ? 'bg-blue-50 border-l-4 border-[#0078C8]' : 'hover:bg-gray-50' }}">
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="font-medium">{{ $run->unique_no ?: 'Draft #' . $run->id }}</span>
                            <span class="capitalize text-gray-500">{{ $run->status }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ $run->period_start->format('Y-m-d') }} to {{ $run->period_end->format('Y-m-d') }} · {{ $run->payslips_count }} employees</div>
                        <div class="text-xs mt-1 capitalize">Preflight: {{ $run->preflight_status ?: 'pending' }}</div>
                    </a>
                @empty
                    <div class="p-4 text-sm text-gray-500">No pay runs found.</div>
                @endforelse
            </div>
            <div class="p-3">{{ $payRuns->withQueryString()->links() }}</div>
        </div>

        @if($selectedPayRun && $result)
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    @php
                        $metrics = [
                            ['Status', ucfirst($result['status']), $result['status'] === 'failed' ? 'text-red-600' : ($result['status'] === 'review' ? 'text-amber-600' : 'text-green-600')],
                            ['Blockers', $result['summary']['blockers'], 'text-red-600'],
                            ['Warnings', $result['summary']['warnings'], 'text-amber-600'],
                            ['Journal Debit', number_format($result['journal']['debit'], 2), 'text-gray-900'],
                            ['Journal Credit', number_format($result['journal']['credit'], 2), 'text-gray-900'],
                        ];
                    @endphp
                    @foreach($metrics as [$label, $value, $color])
                        <div class="bg-white border border-black/10 rounded-md p-4">
                            <div class="text-xs text-gray-500">{{ $label }}</div>
                            <div class="text-lg font-semibold mt-1 break-words {{ $color }}">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white border border-black/10 rounded-md overflow-hidden">
                    <div class="px-4 py-3 border-b flex justify-between items-center">
                        <div>
                            <div class="font-medium">{{ $selectedPayRun->unique_no ?: 'Pay Run #' . $selectedPayRun->id }}</div>
                            <div class="text-xs text-gray-500">{{ ucfirst($selectedPayRun->run_type) }} · {{ ucfirst($selectedPayRun->approval_status) }} approval</div>
                        </div>
                        <a href="{{ route('payroll.pay-runs.show', $selectedPayRun->id) }}" class="text-sm text-[#0078C8] hover:underline">Open pay run</a>
                    </div>
                    @php($makerCheckerBlocked = collect($result['checks'])->contains(fn ($check) => $check['code'] === 'MAKER_CHECKER' && $check['severity'] === 'blocker'))
                    @if($makerCheckerBlocked)
                        <div class="px-4 py-3 border-b border-blue-200 bg-blue-50 text-sm text-blue-900">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-user-shield mt-0.5 text-[#0078C8]"></i>
                                <div>
                                    <div class="font-medium">A second payroll user must approve this pay run</div>
                                    <p class="text-xs mt-1 text-blue-800">
                                        {{ $selectedPayRun->createdBy?->name ?: 'The current user' }} created this pay run and cannot approve the same run. Sign in as another user with Payroll Pay Run Approve permission, open this pay run, and approve it. No accounting data needs correction.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($result['summary']['unreviewed_warnings'] > 1)
                        <form method="POST" action="{{ route('payroll.control-center.override-all', $selectedPayRun) }}" class="p-3 border-b bg-amber-50 flex flex-wrap items-center gap-2">
                            @csrf
                            <span class="text-xs text-amber-800">Review all {{ $result['summary']['unreviewed_warnings'] }} warnings:</span>
                            <input name="reason" required minlength="15" placeholder="Shared documented review reason" class="flex-1 min-w-64 border rounded px-2 py-1 text-xs">
                            <button class="border border-amber-600 text-amber-700 rounded px-3 py-1 text-xs">REVIEW ALL WARNINGS</button>
                        </form>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                <tr><th class="px-4 py-3">Control</th><th class="px-4 py-3">Result</th><th class="px-4 py-3">Finding</th><th class="px-4 py-3">Review</th></tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($result['checks'] as $check)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $check['group'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs font-medium {{ $check['severity'] === 'blocker' ? 'text-red-600' : ($check['severity'] === 'warning' ? 'text-amber-600' : 'text-green-600') }}">
                                                {{ $check['overridden'] ? 'Reviewed' : ucfirst($check['severity']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 min-w-[280px]">{{ $check['message'] }}
                                            @if($check['override_reason'])<div class="text-xs text-gray-500 mt-1">Reason: {{ $check['override_reason'] }}</div>@endif
                                        </td>
                                        <td class="px-4 py-3 min-w-[260px]">
                                            @if($check['severity'] === 'warning' && !$check['overridden'])
                                                <form method="POST" action="{{ route('payroll.control-center.override', $selectedPayRun) }}" class="flex gap-2">
                                                    @csrf
                                                    <input type="hidden" name="check_code" value="{{ $check['code'] }}">
                                                    <input name="reason" required minlength="10" placeholder="Review reason" class="w-full border rounded px-2 py-1 text-xs">
                                                    <button class="border border-[#0078C8] text-[#0078C8] rounded px-2 text-xs">REVIEW</button>
                                                </form>
                                            @else
                                                @if($check['code'] === 'MAKER_CHECKER' && $check['severity'] === 'blocker')
                                                    <span class="text-xs text-blue-700">Another authorized user must approve</span>
                                                @else
                                                    <span class="text-xs text-gray-400">{{ $check['severity'] === 'blocker' ? 'Correct source data' : 'No action' }}</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white border border-black/10 rounded-md p-10 text-center text-gray-500">Select a pay run to inspect its controls and journal preview.</div>
        @endif
    </div>
</div>
@endsection
