@extends('layouts.auth')
@section('title', 'Accounting Health Center')

@section('content')
    <div class="w-full min-w-0 max-w-full p-3 sm:p-4 space-y-4 overflow-x-hidden">
        <div class="bg-white border border-gray-200 rounded-[5px]">
            <div class="px-4 py-4 sm:px-5 grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end gap-4">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Accounting Health Center</h1>
                    <p class="text-xs text-gray-500 mt-1">{{ $health['start']->format('d M Y') }} to {{ $health['end']->format('d M Y') }}</p>
                </div>
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-[160px_160px_40px] gap-2 w-full sm:w-auto min-w-0">
                    <label class="min-w-0"><span class="block mb-1 text-xs text-gray-500">From</span><input type="date" name="start_date" value="{{ $health['start']->toDateString() }}" class="date block w-full h-9 border border-gray-300 rounded-[3px] px-3 text-sm"></label>
                    <label class="min-w-0"><span class="block mb-1 text-xs text-gray-500">To</span><input type="date" name="end_date" value="{{ $health['end']->toDateString() }}" class="date block w-full h-9 border border-gray-300 rounded-[3px] px-3 text-sm"></label>
                    <button class="h-9 w-10 self-end bg-[#0078C8] text-white rounded-[3px] hover:bg-[#006bb0]" title="Run health checks" aria-label="Run health checks"><i class="fa-solid fa-rotate"></i></button>
                </form>
            </div>
            @include('pages.account.control-center.partials.tabs')
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ([['Score', $health['score'].'%', 'text-[#0078C8]'], ['Critical', $health['critical'], 'text-red-600'], ['Warnings', $health['warnings'], 'text-amber-600'], ['Passed', $health['passed'], 'text-emerald-600']] as [$label, $value, $color])
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">{{ $label }}</p><p class="text-2xl font-bold {{ $color }}">{{ $value }}</p></div>
            @endforeach
        </div>

        <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
            <div class="divide-y divide-gray-100">
                @foreach ($health['checks'] as $check)
                    <div class="px-4 py-3 flex flex-wrap sm:flex-nowrap items-start gap-3">
                        <span class="shrink-0 mt-0.5 w-7 h-7 flex items-center justify-center rounded-full {{ $check['status'] === 'pass' ? 'bg-emerald-50 text-emerald-600' : ($check['status'] === 'critical' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600') }}">
                            <i class="fa-solid {{ $check['status'] === 'pass' ? 'fa-check' : 'fa-exclamation' }} text-xs"></i>
                        </span>
                        <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-gray-800">{{ $check['label'] }}</p><p class="text-xs text-gray-500 mt-0.5">{{ $check['message'] }}</p></div>
                        @if ($check['url'])<a href="{{ $check['url'] }}" class="shrink-0 ml-10 sm:ml-0 text-xs text-[#0078C8] whitespace-nowrap hover:underline">Open <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i></a>@endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
