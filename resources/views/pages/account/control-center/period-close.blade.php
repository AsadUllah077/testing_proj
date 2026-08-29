@extends('layouts.auth')
@section('title', 'Period Close')

@section('content')
    <div class="p-4 space-y-4">
        <div class="bg-white border border-gray-200 rounded-[5px]">
            <div class="px-5 py-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div><h1 class="text-lg font-bold text-gray-800">Period Close</h1>@if($period)<p class="text-xs text-gray-500 mt-1">{{ $period->start_date->format('d M Y') }} to {{ $period->end_date->format('d M Y') }}</p>@endif</div>
                <form method="GET"><select name="period_id" onchange="this.form.submit()" class="select2 border border-gray-300 rounded-[3px] min-w-64 text-sm"><option value="">Select period</option>@foreach($periods as $item)<option value="{{ $item->id }}" @selected($period?->id === $item->id)>{{ $item->name }} {{ $item->is_locked ? '(Locked)' : '(Open)' }}</option>@endforeach</select></form>
            </div>
            @include('pages.account.control-center.partials.tabs')
        </div>

        @if (!$period)
            <div class="bg-white border border-gray-200 rounded-[5px] p-8 text-center text-gray-500">No accounting period is available.</div>
        @else
            @php
                $completedCount = $savedTasks->where('is_completed', true)->count();
                $ready = $completedCount === count($taskDefinitions) && ($health['critical'] ?? 1) === 0;
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Checklist</p><p class="text-xl font-bold text-gray-800">{{ $completedCount }}/{{ count($taskDefinitions) }}</p></div>
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Critical Issues</p><p class="text-xl font-bold {{ $health['critical'] ? 'text-red-600' : 'text-emerald-600' }}">{{ $health['critical'] }}</p></div>
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Warnings</p><p class="text-xl font-bold text-amber-600">{{ $health['warnings'] }}</p></div>
                <div class="bg-white border border-gray-200 rounded-[5px] p-4"><p class="text-xs text-gray-500">Status</p><p class="text-xl font-bold {{ $period->is_locked ? 'text-gray-600' : ($ready ? 'text-emerald-600' : 'text-amber-600') }}">{{ $period->is_locked ? 'Locked' : ($ready ? 'Ready' : 'In Progress') }}</p></div>
            </div>

            <section class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="divide-y divide-gray-100">
                    @foreach ($taskDefinitions as $key => $label)
                        @php($task = $savedTasks->get($key))
                        <form method="POST" action="{{ route('account.period-close.task', $period) }}" class="px-4 py-3 flex flex-col lg:flex-row lg:items-center gap-3">
                            @csrf
                            <input type="hidden" name="task_key" value="{{ $key }}">
                            <label class="flex items-center gap-3 lg:w-2/5">
                                <input type="hidden" name="is_completed" value="0"><input type="checkbox" name="is_completed" value="1" @checked($task?->is_completed) @disabled($period->is_locked) class="w-4 h-4 text-[#0078C8]">
                                <span class="text-sm font-medium text-gray-800">{{ $label }}</span>
                            </label>
                            <input name="notes" value="{{ $task?->notes }}" placeholder="Review note" @disabled($period->is_locked) class="h-9 flex-1 border border-gray-300 rounded-[3px] px-3 text-sm">
                            @if(!$period->is_locked)<button class="h-9 w-9 border border-[#0078C8] text-[#0078C8] rounded-[3px]" title="Save task"><i class="fa-solid fa-floppy-disk"></i></button>@endif
                        </form>
                    @endforeach
                </div>
            </section>

            @if(!$period->is_locked)
                <div class="flex justify-end">
                    <form method="POST" action="{{ route('account.period-close.close', $period) }}" onsubmit="return confirm('Lock this accounting period? Posting will be blocked for ordinary users.')">@csrf<button @disabled(!$ready) class="px-5 py-2.5 rounded-[3px] text-sm font-semibold {{ $ready ? 'bg-[#0078C8] text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}"><i class="fa-solid fa-lock mr-2"></i>Lock Period</button></form>
                </div>
            @endif
        @endif
    </div>
@endsection
