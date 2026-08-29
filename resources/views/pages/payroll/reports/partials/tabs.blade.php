@php
    $tabs = [
        ['label' => 'Summary', 'route' => 'payroll.reports.summary', 'active' => 'payroll.reports.summary'],
        ['label' => 'Employee History', 'route' => 'payroll.reports.employee-history', 'active' => 'payroll.reports.employee-history'],
        ['label' => 'Comparison', 'route' => 'payroll.reports.employee-comparison', 'active' => 'payroll.reports.employee-comparison'],
        ['label' => 'Calendar', 'route' => 'payroll.reports.calendar', 'active' => 'payroll.reports.calendar'],
        ['label' => 'Analytics', 'route' => 'payroll.reports.analytics', 'active' => 'payroll.reports.analytics'],
        ['label' => 'Overtime', 'route' => 'payroll.reports.overtime', 'active' => 'payroll.reports.overtime'],
    ];
@endphp

<div class="bg-white rounded-lg border border-black/10 shadow-sm p-2 mb-6 flex flex-wrap gap-2">
    @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="px-4 py-2 rounded text-sm font-semibold {{ Request::routeIs($tab['active']) ? 'bg-[#0078C8] text-white' : 'text-gray-600 hover:bg-blue-50' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
