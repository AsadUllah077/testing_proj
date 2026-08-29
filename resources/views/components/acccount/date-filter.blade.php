@props(['date_filter'])

<div class="flex justify-end mb-3.5">
    <div class="relative inline-block">
        <form method="GET" class="flex items-center gap-2">
            <div class="relative">
                <select id="{{ $date_filter }}" name="per_date" onchange="toggleCustomDates(this)"
                    class="appearance-none bg-[#F6F9FC] border-2 border-[#0078C8] text-[#0078C8] font-semibold px-4 py-1 pr-10 rounded-[3px] cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <option value="">Select option</option>
                    @foreach (config('default.per_date') as $key => $value)
                        <option value="{{ $value }}" {{ request('per_date') == $value ? 'selected' : '' }}>
                            {{ $key }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[#0078C8]">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                </div>
            </div>

            <div id="custom-dates-{{ $date_filter }}" class="{{ request('per_date') == 'CUSTOM' ? 'flex' : 'hidden' }} items-center gap-2">
                <input type="text" name="start_date" value="{{ request('start_date') }}" placeholder="dd/mm/yyyy"
                    class="date bg-[#F6F9FC] border-2 border-[#0078C8] text-[#0078C8] font-semibold px-2 py-0.5 rounded-[3px] text-sm focus:outline-none">
                <span class="text-[#0078C8] font-bold">to</span>
                <input type="text" name="end_date" value="{{ request('end_date') }}" placeholder="dd/mm/yyyy"
                    class="date bg-[#F6F9FC] border-2 border-[#0078C8] text-[#0078C8] font-semibold px-2 py-0.5 rounded-[3px] text-sm focus:outline-none">
                <button type="submit" class="bg-[#0078C8] text-white px-3 py-1 rounded-[3px] text-sm font-bold hover:bg-blue-700 transition-colors">
                    Go
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        function toggleCustomDates(select) {
            const customDiv = document.getElementById(`custom-dates-${select.id}`);
            if (select.value === 'CUSTOM') {
                customDiv.classList.remove('hidden');
                customDiv.classList.add('flex');
            } else {
                customDiv.classList.remove('flex');
                customDiv.classList.add('hidden');
                select.form.submit();
            }
        }

        function adjustWidth(select) {
            // Create a hidden span to measure text width
            const tempSpan = document.createElement("span");
            tempSpan.style.visibility = "hidden";
            tempSpan.style.whiteSpace = "nowrap";
            tempSpan.style.font = getComputedStyle(select).font;
            tempSpan.textContent = select.options[select.selectedIndex].text;

            document.body.appendChild(tempSpan);
            const newWidth = 160; // padding + arrow space
            document.body.removeChild(tempSpan);

            select.style.width = `${newWidth}px`;
        }

        // Initialize width on load
        var select = document.getElementById("{{ $date_filter }}");
        if (select) {
            adjustWidth(select);
        }
    </script>
@endpush
