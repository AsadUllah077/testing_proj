@props(['column', 'label'])

@php
    // Current sorting field & direction
    $currentSort = request('sort_by');
    $currentDir = request('sort_dir', 'asc');

    // Determine next direction
    $nextDir = $currentSort === $column && $currentDir === 'asc' ? 'desc' : 'asc';

    // Build URL with all current query params
    $query = request()->all();
    $query['sort_by'] = $column;
    $query['sort_dir'] = $nextDir;

    $url = request()->fullUrlWithQuery($query);

    // Icon logic
    $icon = '';
    if ($currentSort === $column) {
        $icon = $currentDir === 'asc' ? '↑' : '↓';
    }
@endphp

<a href="{{ $url }}" class="flex items-center gap-1 cursor-pointer">
    <span>{{ $label }}</span>
    @if ($icon)
        <span class="text-lg text-[#0078C8]">{{ $icon }}</span>
    @endif
</a>
