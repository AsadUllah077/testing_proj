<form method="GET" action="{{ url()->current() }}" data-update-url-form {{ $attributes->except(['label']) }}>
    @foreach (request()->query() as $name => $value)
        @if (is_array($value))
            @foreach ($value as $item)
                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}" data-update-query>
            @endforeach
        @else
            <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-update-query>
        @endif
    @endforeach

    @if (count(request()->query()) > 0)
        <button type="button" onclick="window.location.href='{{ url()->current() }}'"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-all duration-200 hover:bg-[#0078C8] hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
            Reset
        </button>
    @endif

    <button type="submit"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-all duration-200 hover:border-slate-400 hover:bg-slate-50 hover:shadow focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sky-500" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v6h6M20 20v-6h-6M5.64 18.36A9 9 0 103.51 9m16.98 6A9 9 0 0120.49 15" />
        </svg>

        {{ $label }}
    </button>
</form>

@once
    @push('scripts')
        <script>
            document.addEventListener('submit', function(event) {
                const form = event.target.closest('[data-update-url-form]');

                if (!form) {
                    return;
                }

                form.querySelectorAll('[data-update-query]').forEach((input) => input.remove());

                new URLSearchParams(window.location.search).forEach((value, name) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    input.dataset.updateQuery = '';
                    form.appendChild(input);
                });
            });
        </script>
    @endpush
@endonce
