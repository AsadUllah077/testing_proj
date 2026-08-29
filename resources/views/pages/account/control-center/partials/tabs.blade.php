<div class="bg-white border-b border-gray-200 px-4 overflow-x-auto">
    <nav class="flex min-w-max" aria-label="Accounting controls">
        @foreach ([
            ['account.control-center.index', 'Command Center', 'fa-gauge-high'],
            ['account.health-center.index', 'Health Center', 'fa-heart-pulse'],
            ['account.period-close.index', 'Period Close', 'fa-lock'],
            ['account.document-trace.index', 'Document Trace', 'fa-route'],
            ['account.balance-sync.index', 'Balance Sync', 'fa-arrows-rotate'],
            ['account.month-end-adjustments.index', 'Adjustments', 'fa-sliders'],
            ['account.bank-rules.index', 'Bank Rules', 'fa-wand-magic-sparkles'],
        ] as [$routeName, $label, $icon])
            @php
                $activePattern = str_ends_with($routeName, '.index')
                    ? substr($routeName, 0, -6) . '*'
                    : $routeName;
            @endphp
            <a href="{{ route($routeName) }}"
                class="px-4 py-3 text-sm border-b-2 whitespace-nowrap {{ request()->routeIs($activePattern) ? 'border-[#0078C8] text-[#0078C8] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                <i class="fa-solid {{ $icon }} mr-2"></i>{{ $label }}
            </a>
        @endforeach
    </nav>
</div>
