@extends('layouts.auth')

@section('title', 'Payroll Calendar')

@section('content')
<div class="mb-8 font-sans">
    <div class="mx-auto px-3">
        <div class="p-6 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-6 shadow-sm">
            <div>
                <h2 class="text-2xl font-light text-gray-800">Payroll Calendar</h2>
                <p class="text-sm text-gray-500 mt-1">Processing, posting, payment and tax due dates</p>
            </div>
            <form method="GET">
                <input type="month" name="month" value="{{ $calendarDate->format('Y-m') }}" class="border border-gray-300 rounded p-2 text-sm" onchange="this.form.submit()">
            </form>
        </div>

        @include('pages.payroll.reports.partials.tabs')

        <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
            <div class="grid grid-cols-7 bg-gray-50 text-[10px] uppercase tracking-wider text-gray-400 font-bold">
                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                    <div class="px-3 py-3 border-r last:border-r-0">{{ $day }}</div>
                @endforeach
            </div>
            @foreach($weeks as $week)
                <div class="grid grid-cols-7 border-t">
                    @foreach($week as $day)
                        @php $key = $day->toDateString(); @endphp
                        <div class="min-h-[145px] p-3 border-r last:border-r-0 {{ $day->month === $calendarDate->month ? 'bg-white' : 'bg-gray-50 text-gray-400' }}">
                            <div class="text-sm font-semibold mb-2">{{ $day->day }}</div>
                            <div class="space-y-1.5">
                                @foreach($eventsByDate[$key] ?? [] as $event)
                                    <a href="{{ route('payroll.pay-runs.show', $event['pay_run_id']) }}"
                                       class="block rounded border px-2 py-1 text-[11px] leading-tight {{ $event['class'] }}">
                                        <span class="font-bold">{{ $event['label'] }}</span>
                                        <span class="block opacity-80">{{ $event['period'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
