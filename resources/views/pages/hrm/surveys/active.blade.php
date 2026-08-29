@extends('layouts.auth')

@section('title', 'Active Surveys')

@section('content')
<div class="mt-4">
    <div class="mx-auto px-4 max-w-7xl">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Active Surveys</h1>
                <p class="text-sm text-gray-500 mt-1">Surveys currently open for participation</p>
            </div>
            @if(auth()->user()->hasPermission('hrm.surveys.create'))
            <a href="{{ route('hrm.surveys.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors ">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Survey
            </a>
            @endif
        </div>

        {{-- Table – reuse partial for consistency --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden ">
            @include('pages.hrm.surveys.partials.table', ['surveys' => $surveys])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Dropdown toggle for three-dot menu
    document.addEventListener('click', function(e) {
        // Hide all menus first
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        const btn = e.target.closest('.dropdown-btn');
        if (btn) {
            btn.nextElementSibling.classList.remove('hidden');
            e.stopPropagation();
        }
    });
</script>
@endpush
