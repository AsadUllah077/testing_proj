<nav class="py-4 overflow-y-auto h-[calc(100vh-120px)]">
    {{-- Home Dashboard --}}
    @if (auth()->user()->hasPermission('home.dashboard.view'))
        <a href="{{ route('home.dashboard') }}"
            class="flex items-center space-x-3 px-6 py-3 text-blue-600 bg-blue-50 border-r-4 border-blue-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            <span class="font-medium">Home</span>
        </a>
    @endif

    {{-- Users Management --}}
    @if (auth()->user()->hasAnyPermission(['users.dashboard.view', 'users.user.view', 'users.role.view', 'users.permission.view']))
        <a href="{{ route('users.dashboard') }}"
            class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('users.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
            </svg>
            <span class="font-medium">Users</span>
        </a>
    @endif

    {{-- Product --}}
    @if (auth()->user()->hasPermission('product.dashboard.view') || auth()->user()->isModuleAdmin('product'))
    <a href="{{ route('products.dashboard') }}"
        class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('products.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
        <span class="font-medium">Product</span>
    </a>
    @endif

    {{-- Warehouse --}}
    {{-- @if (auth()->user()->hasPermission('warehouse.dashboard.view') || auth()->user()->isModuleAdmin('warehouse'))
    <a href="{{ route('warehouse.dashboard') }}"
        class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('warehouse.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
        </svg>
        <span class="font-medium">Warehouse</span>
    </a>
    @endif --}}

    {{-- Accounting --}}
    
    @if (auth()->user()->hasAnyPermission([
                'account.dashboard.view',
                'account.sale.view',
                'account.bill-invoice.view',
                'account.purchase-order.view',
                'account.journal-entries.view',
                'account.reports.view',
            ]))
        <a href="{{ route('account.dashboard') }}"
            class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('account.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                </path>
            </svg>
            <span class="font-medium">Accounting</span>
        </a>
    @endif

    {{-- Project --}}
    @if (auth()->user()->hasPermission('project.dashboard.view'))
    <a href="{{ route('project.dashboard') }}"
        class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('project.*') && !request()->routeIs('project.clients.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
        </svg>
        <span class="font-medium">Project</span>
    </a>
    @endif


    @if(auth()->user()->hasPermission('ticket.dashboard.view'))
    <a href="{{ route('ticket.dashboard') }}"
        class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('ticket.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
        </svg>
        <span class="font-medium">Ticket</span>
    </a>
    @endif



    {{-- HRM --}}
    @if (auth()->user()->hasAnyPermission(['hrm.dashboard.view', 'hrm.employee.view', 'hrm.leave.view', 'hrm.attendance.view']))
        <a href="{{ route('hrm.index') }}"
            class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('hrm.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                </path>
            </svg>
            <span class="font-medium">HRM</span>
        </a>
    @endif

    {{-- Payroll --}}
    @if (auth()->user()->hasPermission('payroll.dashboard.view'))
    <a href="{{ route('payroll.dashboard') }}"
        class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('payroll.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
            </path>
        </svg>
        <span class="font-medium">Payroll</span>
    </a>
    @endif

    {{-- QMS --}}
    @if (auth()->user()->hasPermission('qms.dashboard.view'))
        <a href="{{ route('qms.index') }}"
            class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('qms.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            <span class="font-medium">QMS</span>
        </a>
    @endif

    {{-- Mass Mailer --}}
    @if (auth()->user()->hasPermission('mailer.view'))
        <a href="{{ route('mass-mailer.dashboard') }}"
            class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('mass-mailer.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                </path>
            </svg>
            <span class="font-medium">Mass Mailer</span>
        </a>
    @endif

    {{-- Chat --}}
    @if (auth()->user()->hasPermission('chat.view'))
        <a href="#" class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                </path>
            </svg>
            <span class="font-medium">Chat</span>
        </a>
    @endif

    {{-- Activity Logs --}}
    @if (auth()->user()->hasPermission('activity.logs.view'))
        <a href="{{ route('activity.logs.index') }}"
            class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('activity.logs.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                </path>
            </svg>
            <span class="font-medium">Activity Logs</span>
        </a>
    @endif

    @if (auth()->user()->hasPermission('tasks.view'))
    <a href="{{ route('tasks.index') }}"
        class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('tasks.*') ? 'bg-gray-50 border-r-4 border-gray-600' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
            </path>
        </svg>
        <span class="font-medium">Tasks</span>
    </a>
    @endif

    {{-- AI Features --}}
    @if(auth()->user()->company_id)
        <a href="{{ route('ai.assistant.index') }}"
            class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('ai.assistant.*') ? 'bg-indigo-50 border-r-4 border-indigo-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            <span class="font-medium">Financial AI</span>
        </a>
        <a href="{{ route('ai.document.ingestion.index') }}"
            class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50 {{ request()->routeIs('ai.document.ingestion.*') ? 'bg-indigo-50 border-r-4 border-indigo-600' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="font-medium">Doc Ingestion</span>
        </a>
    @endif

    {{-- Logout --}}
    <form method="post" action="{{ route('user.logout') }}"
        class="flex items-center space-x-3 px-6 py-3 text-gray-700 hover:bg-gray-50">
        @csrf
        <button type="submit" class="flex items-center space-x-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                </path>
            </svg>
            <span class="font-medium">Log out</span>
        </button>
    </form>
</nav>
