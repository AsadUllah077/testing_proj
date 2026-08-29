@props(['action', 'summary' => null])
<form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">
    {{ $slot }}
</form>

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        ReportFilter.init({
            url: '{{ $action }}',
            tableSelector: '#custom_table',
            paginationSelector: '#pagination-wrapper',
            summarySelector: '{{ $summary }}',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
