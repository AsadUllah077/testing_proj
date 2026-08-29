@props([
    'title' => '',
    'required' => false,
])

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

    {{-- Section Header --}}
    <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-1 h-5 bg-blue-600 rounded-full"></div>
            <h2 class="text-slate-900 text-base font-bold">{{ $title }}</h2>
        </div>

        @if ($required)
            <span class="px-2 py-0.5 bg-blue-600/10 rounded text-blue-600 text-[10px] font-bold uppercase tracking-wide">
                Required
            </span>
        @endif
    </div>

    {{ $slot }}

</div>
