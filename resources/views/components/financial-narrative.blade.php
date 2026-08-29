{{--
    Financial Narrative Component
    Usage: @include('components.financial-narrative', ['narrative' => $narrative])
    Expects $narrative as an array of strings (3-5 insight sentences).
--}}
@if (!empty($narrative))
    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 my-4">
        <div class="flex items-start gap-2 mb-2">
            <div class="bg-indigo-100 rounded-full p-1.5 mt-0.5 shrink-0">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="text-xs font-bold text-indigo-700 uppercase tracking-wide">Executive Summary</p>
        </div>
        <div class="pl-8 space-y-1.5">
            @foreach ($narrative as $sentence)
                <p class="text-sm text-indigo-900 leading-relaxed">{{ $sentence }}</p>
            @endforeach
        </div>
    </div>
@endif
