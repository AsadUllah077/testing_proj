@extends('layouts.auth')

@section('title', 'Surveys')

@section('content')
    <x-acccount.loader />
<div class="mt-4">
    <div class="mx-auto px-4 max-w-7xl">

        {{-- Header --}}
        <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
            <div>
                <h2 class="text-stone-950 text-xl font-normal">Survey Management</h2>
                <p class="text-gray-600 text-sm mt-1">Create, publish, and analyze employee surveys</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative mt-3 md:mt-0">
                <div></div>

                <!-- Column Chooser -->
                <x-columns :columns="[
                    'survey_name'      => 'Survey',
                    'survey_status'    => 'Status',
                    'survey_period'    => 'Period',
                    'survey_responses' => 'Responses',
                    'survey_anonymous' => 'Anonymous',
                ]" name="survey_columns" />

                <!-- Export Dropdown -->
                <div class="relative w-full">
                    <button onclick="toggleDropdown('exportDropdown')"
                        class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                        EXPORT ▼
                    </button>
                    <div id="exportDropdown"
                        class="hidden absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                        <ul class="py-1 text-sm">
                            <li>
                                <button onclick="startExport('pdf','hrm-surveys')"
                                    class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    PDF
                                </button>
                            </li>
                            <li>
                                <button onclick="startExport('xlsx','hrm-surveys')"
                                    class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Excel
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('hrm.surveys.create'))
                    <a href="{{ route('hrm.surveys.create') }}"
                        class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        ADD NEW
                    </a>
                @endif
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @php
            $statCards = [
                ['label' => 'Total',     'value' => $stats['total'],     'color' => 'bg-slate-100 text-slate-700',   'icon' => 'fa-clipboard-list'],
                ['label' => 'Published', 'value' => $stats['published'], 'color' => 'bg-green-100 text-green-700',   'icon' => 'fa-circle-check'],
                ['label' => 'Draft',     'value' => $stats['draft'],     'color' => 'bg-amber-100 text-amber-700',   'icon' => 'fa-pen-to-square'],
                ['label' => 'Closed',    'value' => $stats['closed'],    'color' => 'bg-red-100 text-red-700',       'icon' => 'fa-circle-xmark'],
            ];
            @endphp
            @foreach($statCards as $card)
            <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg {{ $card['color'] }} flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid {{ $card['icon'] }} text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $card['label'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Filter Section --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-4">
            <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                        <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                        <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                        <select name="status" id="statusFilter" class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none select2">
                            <option value="">All Status</option>
                            <option value="Draft"     {{ request('status') == 'Draft'     ? 'selected' : '' }}>Draft</option>
                            <option value="Published" {{ request('status') == 'Published' ? 'selected' : '' }}>Published</option>
                            <option value="Closed"    {{ request('status') == 'Closed'    ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                        <input type="text" name="q" placeholder="Search by title..."
                            value="{{ request('q') }}"
                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div class="flex flex-col">
                        <x-default-page />
                    </div>
                    <x-filter-button />
                </form>
            </div>

            {{-- Table Container --}}
            <div id="survey-table">
                @include('pages.hrm.surveys.partials.table', ['surveys' => $surveys])
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Export dropdown toggle
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
        if (el.id !== id) el.classList.add('hidden');
    });
    dropdown.classList.toggle('hidden');
}
window.toggleDropdown = toggleDropdown;

function deleteSurvey(id) {
    Swal.fire({
        title: 'Delete Survey?',
        text: 'All questions and responses will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/hrm/surveys/${id}/destroy`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(resp) {
                    if (resp.success) {
                        Swal.fire('Deleted!', resp.message, 'success').then(() => {
                           document.querySelector(`#row-${id}`)?.remove();
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Could not delete survey.', 'error');
                }
            });
        }
    });
}

// Dropdown toggle for rows
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.dropdown-btn');
    if (btn) {
        e.stopPropagation();
        const menu = btn.nextElementSibling;
        document.querySelectorAll('.dropdown-menu').forEach(m => {
            if (m !== menu) m.classList.add('hidden');
        });
        menu.classList.toggle('hidden');
        return;
    }
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
});

// Initialize Report Filter
document.addEventListener('DOMContentLoaded', function() {
    ReportFilter.init({
        url: '{{ route('hrm.surveys.index') }}',
        tableSelector: '#survey-table',
        paginationSelector: '#pagination-wrapper',
        filterContainer: document.getElementById('filter-form'),
        debounceDelay: 250,
    });

    document.addEventListener('tableUpdated', () => {
        if (typeof applyColumnVisibility === 'function') applyColumnVisibility();
    });
});
</script>
<script src="{{ asset('assets/js/report-filter.js') }}"></script>
@endpush
@endsection
