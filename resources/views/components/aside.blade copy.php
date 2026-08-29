<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <!-- User Profile -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
            <div
                class="w-12 h-12 bg-gradient-to-br from-amber-600 to-amber-800 rounded-full flex items-center justify-center">
                @php
                    $name = trim(Auth::user()->fullName ?? '');
                    $parts = preg_split('/\s+/', $name);

                    $initials = collect($parts)->filter()->map(fn($p) => strtoupper(mb_substr($p, 0, 1)))->implode('');
                @endphp
                <span class="text-white font-semibold text-lg">{{ $initials }}</span>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">{{ Auth::user()->username }} </h3>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    @if (Request::routeIs('dashboard') ||  Request::routeIs('activity.logs.index'))
        <x-sidebar />
    @endif

    @if (Request::routeIs('users.*'))
        @php
            $sub_modules = [
                [
                    'name' => 'Dashboard',
                    'url' => route('users.dashboard'),
                    'is_active' => Request::routeIs('users.dashboard')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
                  [
                    'name' => 'Permission',
                    'url' => route('users.permission.index'),
                    'is_active' => Request::routeIs('users.permission.*')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
                 [
                    'name' => 'Role',
                    'url' => route('users.role.index'),
                    'is_active' => Request::routeIs('users.role.*')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],

                [
                    'name' => 'User',
                    'url' => route('users.user.index'),
                    'is_active' => Request::routeIs('users.user.*')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],

            ];
        @endphp
        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif
    @if (Request::routeIs('home.*'))
        @php
            $sub_modules = [
                [
                    'name' => 'Dashboard',
                    'url' => route('home.dashboard'),
                    'is_active' => Request::routeIs('home.dashboard')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
                [
                    'name' => 'Company',
                    'url' => route('home.companies.index'),
                    'is_active' => Request::routeIs('home.companies.*')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
            ];
        @endphp
        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif
    @if (Request::routeIs('account.*'))
        @php
            $sub_modules = [
                [
                    'name' => 'Dashboard',
                    'url' => route('account.dashboard'),
                    'is_active' => Request::routeIs('account.dashboard')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-gauge-high w-5 h-5 mr-2 "></i>',
                ],
                [
                    'name' => 'Sale Invoice',
                    'url' => route('account.sale-invoice.index'),
                    'is_active' =>
                        Request::routeIs('account.sale-invoice.*') ||
                        Request::routeIs('account.sale.sale-transaction.*') ||
                        Request::routeIs('account.sale.credit-notes.*') ||
                        Request::routeIs('account.sale.customers.*')
                            ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice-dollar w-5 h-5 mr-2 "></i>',
                ],
                [
                    'name' => 'Bill',
                    'url' => route('account.bill-invoice.index'),
                    'is_active' =>
                        Request::routeIs('account.bill-invoice.*') ||
                        Request::routeIs('account.bill.vendors.*') ||
                        Request::routeIs('account.bill-report.index')
                            ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice w-5 h-5 mr-2 "></i>',
                ],
                [
                    'name' => 'Purchase Order',
                    'url' => route('account.purchase-order.index'),
                    'is_active' =>
                        Request::routeIs('account.purchase-order.*') ||
                        Request::routeIs('account.purchase-order.index') ||
                        Request::routeIs('account.purchase-order.order.list') ||
                        Request::routeIs('account.purchase-order-quotation.good-recieve') ||
                        Request::routeIs('account.purchase-order.reports')
                            ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-cart-shopping w-5 h-5 mr-2 "></i>',
                ],
                [
                    'name' => 'Journal Entries',
                    'url' => route('account.journal-entries.index'),
                    'is_active' => Request::routeIs('account.journal-entries.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-book-open w-5 h-5 mr-2 "></i>',
                ],
                [
                    'name' => 'Reports',
                    'url' => route('account.report.journal'),
                    'is_active' => Request::routeIs('account.report.*')
                        ? 'text-[#0078C8] border-r-4 border-'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-chart-line w-5 h-5 mr-2 "></i>',
                ],
                [
                    'name' => 'Organization Settings',
                    'url' => route('account.organization-settings.index'),
                    'is_active' => Request::routeIs('account.organization-settings.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-gear w-5 h-5 mr-2 "></i>',
                ],
                [
                    'name' => 'Kpi',
                    'url' => route('account.kpi'),
                    'is_active' => Request::routeIs('account.kpi')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-chart-pie w-5 h-5 mr-2 "></i>',
                ],
            ];
        @endphp

        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif


    @if (Request::routeIs('qms.*'))
        @php
            $sub_modules = [
                [
                    'name' => 'Dashboard',
                    'url' => route('qms.index'),
                    'is_active' => Request::routeIs('qms.index')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
                [
                    'name' => 'Document Management',
                    'url' => route('qms.documentmanagment.index'),
                    'is_active' => Request::routeIs('qms.documentmanagment.index')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],

            ];
        @endphp
        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif

    @if (Request::routeIs('hrm.*'))
        @php
            $sub_modules = [
                [
                    'name' => 'Dashboard',
                    'url' => route('hrm.index'),
                    'is_active' => Request::routeIs('hrm.index')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
                [
                    'name' => 'Organization',
                    'url' => route('hrm.employee-type.index'),
                    'is_active' => Request::routeIs('hrm.employee-type.index')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
                [
                    'name' => 'Employees',
                    'url' => route('hrm.employee.index'),
                    'is_active' => Request::routeIs('hrm.employee.index')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
                  [
                    'name' => 'Attendance',
                    'url' => route('hrm.attendance.index'),
                    'is_active' => Request::routeIs('hrm.attendance.index')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],
                 [
                    'name' => 'Leaves',
                    'url' => route('hrm.calendar'),
                    'is_active' => Request::routeIs('hrm.calendar')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ],

            ];
        @endphp
        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif
</aside>
