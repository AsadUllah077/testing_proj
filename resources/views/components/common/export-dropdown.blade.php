@props(['name'])

<div class="relative inline-block w-full sm:w-auto">
    <button type="button" onclick="toggleDropdown('exportDropdown')"
        class="inline-flex min-h-[42px] w-full items-center justify-center gap-2 rounded-xl border border-[#0078C8]/40 bg-white px-4 py-2 text-xs font-semibold text-[#0078C8] shadow-sm transition hover:border-[#0078C8] hover:bg-[#0078C8] hover:text-white sm:w-auto">
        <i class="fa-solid fa-file-export text-[11px]"></i>
        Export
        <i class="fa-solid fa-chevron-down text-[10px]"></i>
    </button>

    <div id="exportDropdown"
        class="absolute right-0 z-30 mt-2 hidden w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
        <ul class="py-1 text-sm">
            <li>
                <button type="button" onclick="startExport('pdf','{{ $name }}')" data-url=""
                    class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-xs font-medium text-slate-700 transition hover:bg-sky-50 hover:text-[#0078C8]">
                    <i class="fa-solid fa-file-pdf w-4 text-red-500"></i>
                    PDF
                </button>
            </li>
            <li>
                <button type="button" onclick="startExport('xlsx','{{ $name }}')"
                    class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-xs font-medium text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">
                    <i class="fa-solid fa-file-excel w-4 text-emerald-600"></i>
                    Excel
                </button>
            </li>
        </ul>
    </div>
</div>

@push('scripts')
    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }

        window.toggleDropdown = toggleDropdown;

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.relative')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });
    </script>
@endpush
