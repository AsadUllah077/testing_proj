@props(['columns' => null, 'name' => null, 'title' => 'COLUMN'])

@if ($columns)
    <div class="relative">
        <button onclick="toggleColumnFilter()"
            class="px-4 py-2 border bg-[#F6F9FC] w-full border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
            COLUMN ▼
        </button>
        <div id="columnFilterMenu"
            class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg p-3 z-50">
            @foreach ($columns as $key => $label)
                <label class="block text-sm mb-1">
                    <input type="checkbox" class="col-toggle" data-col="{{ $key }}">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
@endif

<script>
    const columns = [
        @foreach ($columns as $key => $label)
            "{{ $key }}",
        @endforeach
    ];

    function toggleColumnFilter() {
        document.getElementById('columnFilterMenu').classList.toggle('hidden');
    }

    function applyColumnVisibility() {
        const visibleColumns = JSON.parse(localStorage.getItem('{{ $name }}')) || columns;
        document.querySelectorAll('.col-toggle').forEach(cb => cb.checked = visibleColumns.includes(cb.dataset.col));
        columns.forEach(col => {
            const show = visibleColumns.includes(col);
            document.querySelectorAll(`[data-col="${col}"]`).forEach(el => el.style.display = show ? '' :
                'none');
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        applyColumnVisibility();
        document.querySelectorAll('.col-toggle').forEach(cb => {
            cb.addEventListener('change', () => {
                const visibleColumns = Array.from(document.querySelectorAll(
                    '.col-toggle:checked')).map(c => c.dataset.col);
                localStorage.setItem('{{ $name }}', JSON.stringify(visibleColumns));
                applyColumnVisibility();
            });
        });
    });
</script>
