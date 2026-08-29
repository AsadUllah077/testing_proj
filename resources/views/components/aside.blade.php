<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <!-- User Profile -->
    <div class="p-6 border-b border-gray-200">
        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-edit-profile'))"
            class="flex items-center space-x-3 w-full text-left group cursor-pointer rounded-lg hover:bg-gray-50 transition-colors p-1 -m-1">
            @php
                $name = trim(Auth::user()->fullName ?? '');
                $parts = preg_split('/\s+/', $name);
                $initials = collect($parts)->filter()->map(fn($p) => strtoupper(mb_substr($p, 0, 1)))->implode('');
                $profilePic = Auth::user()->profile_pic ?? null;
                $firstName = Auth::user()->first_name ?? '';
                $lastName = Auth::user()->last_name ?? '';
                $displayName = trim($firstName . ' ' . $lastName) ?: Auth::user()->username;
            @endphp
            <div
                class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0 ring-2 ring-transparent group-hover:ring-[#0078C8] transition-all">
                @if ($profilePic && file_exists(public_path($profilePic)))
                    <img src="{{ asset($profilePic) }}" alt="{{ Auth::user()->username }}"
                        class="w-full h-full object-cover">
                @else
                    <div
                        class="w-full h-full bg-gradient-to-br from-amber-600 to-amber-800 flex items-center justify-center">
                        <span class="text-white font-semibold text-lg">{{ $initials }}</span>
                    </div>
                @endif
            </div>
            <div class="min-w-0">
                <h3 class="font-semibold text-gray-900 truncate group-hover:text-[#0078C8] transition-colors">
                    {{ $displayName }}</h3>
                {{-- <p class="text-xs text-gray-400 truncate">Edit Profile</p> --}}
            </div>
        </button>
    </div>

    <!-- Navigation Menu -->
    @if (Request::routeIs('dashboard') || Request::routeIs('activity.logs.index'))
        <x-sidebar />
    @endif

    @if (Request::routeIs('users.*'))
        @php
            $sub_modules = [];

            // Dashboard
            if (auth()->user()->hasPermission('users.dashboard.view')) {
                $sub_modules[] = [
                    'name' => 'Dashboard',
                    'url' => route('users.dashboard'),
                    'is_active' => Request::routeIs('users.dashboard')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ];
            }

            // Permission
            if (auth()->user()->hasPermission('users.permission.view')) {
                $sub_modules[] = [
                    'name' => 'Permission',
                    'url' => route('users.permission.index'),
                    'is_active' => Request::routeIs('users.permission.*')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ];
            }

            // Role
            if (auth()->user()->hasPermission('users.role.view')) {
                $sub_modules[] = [
                    'name' => 'Role',
                    'url' => route('users.role.index'),
                    'is_active' => Request::routeIs('users.role.*')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ];
            }

            // User
            if (auth()->user()->hasPermission('users.user.view')) {
                $sub_modules[] = [
                    'name' => 'User',
                    'url' => route('users.user.index'),
                    'is_active' => Request::routeIs('users.user.*')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ];
            }
        @endphp

        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('home.*'))
        @php
            $sub_modules = [];

            // Home Dashboard
            if (auth()->user()->hasPermission('home.dashboard.view')) {
                $sub_modules[] = [
                    'name' => 'Dashboard',
                    'url' => route('home.dashboard'),
                    'is_active' => Request::routeIs('home.dashboard')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ];
            }

            // Company
            if (auth()->user()->hasPermission('home.companies.view')) {
                $sub_modules[] = [
                    'name' => 'Company',
                    'url' => route('home.companies.index'),
                    'is_active' => Request::routeIs('home.companies.*')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ];
            }
        @endphp

        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('account.*'))
        @php
            $sub_modules = [];

            // Dashboard
            if (auth()->user()->hasPermission('account.dashboard.view')) {
                $sub_modules[] = [
                    'name' => 'Dashboard',
                    'url' => route('account.dashboard'),
                    'is_active' => Request::routeIs('account.dashboard')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-gauge-high w-5 h-5 mr-2 "></i>',
                ];
            }

            // Sale Invoice
            if (auth()->user()->hasPermission('account.sale.view')) {
                $sub_modules[] = [
                    'name' => 'Sale Invoice',
                    'url' => route('account.sale-invoice.index'),
                    'is_active' =>
                        Request::routeIs('account.sale-invoice.*') ||
                        Request::routeIs('account.sale.sale-transaction.*') ||
                        Request::routeIs('account.sale.customers.*')
                            ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice-dollar w-5 h-5 mr-2 "></i>',
                ];
            }

            // Bill
            if (auth()->user()->hasPermission('account.bill-invoice.view')) {
                $sub_modules[] = [
                    'name' => 'Bill',
                    'url' => route('account.bill-invoice.index'),
                    'is_active' =>
                        Request::routeIs('account.bill-invoice.*') ||
                        Request::routeIs('account.bill.vendors.*') ||
                        Request::routeIs('account.bill-report.index')
                            ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice w-5 h-5 mr-2 "></i>',
                ];
            }

            // Purchase Order
            if (auth()->user()->hasPermission('account.purchase-order.view')) {
                $sub_modules[] = [
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
                ];
            }

            // Journal Entries
            if (auth()->user()->hasPermission('account.journal-entries.view')) {
                $sub_modules[] = [
                    'name' => 'Journal Entries',
                    'url' => route('account.journal-entries.index'),
                    'is_active' => Request::routeIs('account.journal-entries.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-book-open w-5 h-5 mr-2 "></i>',
                ];
            }

            // Reports
            if (auth()->user()->hasPermission('account.reports.view')) {
                $sub_modules[] = [
                    'name' => 'Reports',
                    'url' => route('account.report.journal'),
                    'is_active' => Request::routeIs('account.report.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-chart-line w-5 h-5 mr-2 "></i>',
                ];
            }

            // Conversion
            $conversionChildren = [];

            if (auth()->user()->hasPermission('account.opening-balance-conversion.view')) {
                $conversionChildren[] = [
                    'name' => 'Opening Balances',
                    'url' => route('account.conversion.opening-balances.index'),
                    'is_active' => Request::routeIs('account.conversion.opening-balances.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-scale-balanced w-4 h-4 mr-2"></i>',
                ];
            }

            if (!empty($conversionChildren)) {
                $sub_modules[] = [
                    'type' => 'group',
                    'name' => 'Conversion',
                    'icon' => '<i class="fa-solid fa-right-left w-5 h-5 mr-2"></i>',
                    'children' => $conversionChildren,
                ];
            }

            // Liability Payments
            /*if (auth()->user()->hasPermission('account.journal-entries.view')) {
                // reusing this permission for now
                $sub_modules[] = [
                    'name' => 'Liabilities',
                    'url' => route('account.liabilities.index'),
                    'is_active' => Request::routeIs('account.liabilities.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice-dollar w-5 h-5 mr-2 "></i>',
                ];
            }*/

            // Payment Allocation — allocate payments across all approved invoices/bills
            /* $paymentChildren = [];

            if (auth()->user()->hasPermission('account.sale.view')) {
                $paymentChildren[] = [
                    'name' => 'Customer Invoices',
                    'url' => route('account.invoice-allocation.sales'),
                    'is_active' => Request::routeIs('account.invoice-allocation.sales*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice-dollar w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.bill-invoice.view')) {
                $paymentChildren[] = [
                    'name' => 'Vendor Invoices',
                    'url' => route('account.invoice-allocation.bills'),
                    'is_active' => Request::routeIs('account.invoice-allocation.bills*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice w-4 h-4 mr-2"></i>',
                ];
            }

            if (!empty($paymentChildren)) {
                $sub_modules[] = [
                    'type' => 'group',
                    'name' => 'Payment Allocation',
                    'icon' => '<i class="fa-solid fa-money-bill-transfer w-5 h-5 mr-2"></i>',
                    'children' => $paymentChildren,
                ];
            } */

            // Finance Tools (grouped dropdown)
            $financeChildren = [];

            if (auth()->user()->hasPermission('account.sale.view')) {
                $financeChildren[] = [
                    'name' => 'Recurring Invoices',
                    'url' => route('account.recurring-invoice.index'),
                    'is_active' => Request::routeIs('account.recurring-invoice.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice-dollar w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.bill-invoice.view')) {
                $financeChildren[] = [
                    'name' => 'Recurring Bills',
                    'url' => route('account.recurring-bill.index'),
                    'is_active' => Request::routeIs('account.recurring-bill.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.sale.view')) {
                $financeChildren[] = [
                    'name' => 'Credit Notes',
                    'url' => route('account.sale.credit-notes.index'),
                    'is_active' => Request::routeIs('account.sale.credit-notes.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-circle-plus w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.bill-invoice.view')) {
                $financeChildren[] = [
                    'name' => 'Debit Notes',
                    'url' => route('account.debit-note.index'),
                    'is_active' => Request::routeIs('account.debit-note.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-circle-minus w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.reports.view')) {
                $financeChildren[] = [
                    'name' => 'Budget',
                    'url' => route('account.budget.index'),
                    'is_active' => Request::routeIs('account.budget.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-wallet w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.journal-entries.view')) {
                $financeChildren[] = [
                    'name' => 'Fixed Assets',
                    'url' => route('account.fixed-asset.index'),
                    'is_active' => Request::routeIs('account.fixed-asset.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-building w-4 h-4 mr-2"></i>',
                ];
            }

            /* if (auth()->user()->hasPermission('account.reports.view')) {
                $sub_modules[] = [
                    'name'      => 'Aged Reports',
                    'url'       => route('account.report.aged-receivable'),
                    'is_active' => Request::routeIs('account.report.aged-*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]' : 'hover:bg-blue-50',
                    'icon'      => '<i class="fa-solid fa-hourglass-half w-4 h-4 mr-2"></i>',
                ];
            } */

            if (auth()->user()->hasPermission('account.reports.view')) {
                $financeChildren[] = [
                    'name' => 'Cash Flow Forecast',
                    'url' => route('account.cash-flow-forecast'),
                    'is_active' => Request::routeIs('account.cash-flow-forecast')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-water w-4 h-4 mr-2"></i>',
                ];
            }

            // Invoice Approvals
            if (
                auth()->user()->hasPermission('account.sale.view') ||
                auth()->user()->hasPermission('account.bill-invoice.view')
            ) {
                $financeChildren[] = [
                    'name' => 'Invoice Approvals',
                    'url' => route('account.approvals.sales.index'),
                    'is_active' => Request::routeIs('account.approvals.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-check-double w-5 h-5 mr-2 "></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.sale.view')) {
                $financeChildren[] = [
                    'name' => 'Payment Reminders',
                    'url' => route('account.payment-reminders.index'),
                    'is_active' => Request::routeIs('account.payment-reminders.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-bell w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.sale.view')) {
                $financeChildren[] = [
                    'name' => 'Customer Receipts',
                    'url' => route('account.partial-payment.index'),
                    'is_active' => Request::routeIs('account.partial-payment.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-hand-holding-dollar w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.sale.view')) {
                $financeChildren[] = [
                    'name' => 'Customer Statements',
                    'url' => route('account.customer-statements.index'),
                    'is_active' => Request::routeIs('account.customer-statements.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.bill-invoice.view')) {
                $financeChildren[] = [
                    'name' => 'Vendor Statements',
                    'url' => route('account.vendor-statements.index'),
                    'is_active' => Request::routeIs('account.vendor-statements.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.bill-invoice.view')) {
                $financeChildren[] = [
                    'name' => 'Vendor Payment Runs',
                    'url' => route('account.vendor-payment-run.index'),
                    'is_active' => Request::routeIs('account.vendor-payment-run.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-money-check-dollar w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.sale.view')) {
                $financeChildren[] = [
                    'name' => 'Invoice Processing',
                    'url' => route('account.invoice-allocation.sales'),
                    'is_active' => Request::routeIs('account.invoice-allocation*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-money-bill-transfer w-5 h-5 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('account.journal-entries.view')) {
                $financeChildren[] = [
                    'name' => 'Tax Settlement',
                    'url' => route('account.tax-periods.index'),
                    'is_active' => Request::routeIs('account.tax-periods.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-building-columns w-4 h-4 mr-2"></i>',
                ];
            }

            // Liability Payments
            if (auth()->user()->hasPermission('account.journal-entries.view')) {
                // reusing this permission for now
                $financeChildren[] = [
                    'name' => 'Liabilities',
                    'url' => route('account.liabilities.index'),
                    'is_active' => Request::routeIs('account.liabilities.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-invoice-dollar w-5 h-5 mr-2 "></i>',
                ];
            }

            if (!empty($financeChildren)) {
                $sub_modules[] = [
                    'type' => 'group',
                    'name' => 'Finance Tools',
                    'icon' => '<i class="fa-solid fa-toolbox w-5 h-5 mr-2"></i>',
                    'children' => $financeChildren,
                ];
            }

            // Organization Settings
            if (auth()->user()->hasPermission('account.organization-settings.view')) {
                $sub_modules[] = [
                    'name' => 'Organization Settings',
                    'url' => route('account.organization-settings.index'),
                    'is_active' => Request::routeIs('account.organization-settings.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-gear w-5 h-5 mr-2 "></i>',
                ];
            } elseif (strcasecmp((string) auth()->user()->role?->name, 'Accountant Manager') === 0) {
                $sub_modules[] = [
                    'name' => 'Accounting Team',
                    'url' => route('account.organization-settings.users'),
                    'is_active' => Request::routeIs('account.organization-settings.users*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-user-shield w-5 h-5 mr-2 "></i>',
                ];
            }

            // Control Center
            if (auth()->user()->hasPermission('account.reports.view')) {
                $sub_modules[] = [
                    'name' => 'Control Center',
                    'url' => route('account.control-center.index'),
                    'is_active' => Request::routeIs([
                        'account.control-center.*',
                        'account.health-center.*',
                        'account.period-close.*',
                        'account.document-trace.*',
                        'account.month-end-adjustments.*',
                        'account.bank-rules.*',
                    ])
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-shield-halved w-5 h-5 mr-2"></i>',
                ];
            }

            // KPI
            if (auth()->user()->hasPermission('account.kpi.view')) {
                $sub_modules[] = [
                    'name' => 'Kpi',
                    'url' => route('account.kpi'),
                    'is_active' => Request::routeIs('account.kpi')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-chart-pie w-5 h-5 mr-2 "></i>',
                ];
            }
        @endphp

        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('qms.*'))
        @php
            $sub_modules = [];

            // QMS Dashboard
            if (auth()->user()->hasPermission('qms.dashboard.view')) {
                $sub_modules[] = [
                    'name' => 'Dashboard',
                    'url' => route('qms.index'),
                    'is_active' => Request::routeIs('qms.index')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ];
            }

            // Document Management
            if (auth()->user()->hasPermission('qms.document.view')) {
                $sub_modules[] = [
                    'name' => 'Document Management',
                    'url' => route('qms.documentmanagement.index'),
                    'is_active' => Request::routeIs('qms.documentmanagement.index')
                        ? 'text-blue-600  border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '',
                ];
            }

            // Quality Policies
            $sub_modules[] = [
                'name' => 'Quality Policies',
                'url' => route('qms.policy.index'),
                'is_active' => Request::routeIs('qms.policy.*')
                    ? 'text-blue-600  border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '',
            ];

            // SOP Management
            $sub_modules[] = [
                'name' => 'SOP Management',
                'url' => route('qms.sop.index'),
                'is_active' => Request::routeIs('qms.sop.*')
                    ? 'text-blue-600  border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '',
            ];

            // Training Management
            $sub_modules[] = [
                'name' => 'Training',
                'url' => route('qms.training.index'),
                'is_active' => Request::routeIs('qms.training.*')
                    ? 'text-blue-600  border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '',
            ];

            // CAPA System
            $sub_modules[] = [
                'name' => 'CAPA System',
                'url' => route('qms.capa.index'),
                'is_active' => Request::routeIs('qms.capa.*')
                    ? 'text-blue-600  border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '',
            ];

            // Management Review
            $sub_modules[] = [
                'name' => 'Management Review',
                'url' => route('qms.management-review.index'),
                'is_active' => Request::routeIs('qms.management-review.*')
                    ? 'text-blue-600  border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '',
            ];
        @endphp

        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('products.*'))
        @php
            $sub_modules = [];

            // QMS Dashboard
            $sub_modules[] = [
                'name' => 'Dashboard',
                'url' => route('products.dashboard'),
                'is_active' => Request::routeIs('products.dashboard')
                    ? 'text-blue-600  border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '',
            ];

            $sub_modules[] = [
                'name' => 'Warehouse',
                'url' => route('warehouse.dashboard'),
                'is_active' => Request::routeIs('warehouse.dashboard')
                    ? 'text-blue-600  border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '',
            ];

            $sub_modules[] = [
                'name' => 'Logistics',
                'url' => route('logistics.dashboard'),
                'is_active' => Request::routeIs('logistics.dashboard')
                    ? 'text-blue-600  border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '',
            ];

        @endphp

        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('warehouse.*'))
        @php
            $sub_modules = [];

            // Dashboard
            if (auth()->user()->hasPermission('warehouse.dashboard.view')) {
                $sub_modules[] = [
                    'name' => 'Dashboard',
                    'url' => route('warehouse.dashboard'),
                    'is_active' => Request::routeIs('warehouse.dashboard')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-chart-pie w-5 h-5 mr-2"></i>',
                ];
            }

            // Master Data
            if (auth()->user()->hasPermission('warehouse.master-data.view')) {
                $sub_modules[] = [
                    'name' => 'Master Data',
                    'url' => route('warehouse.master-data'),
                    'is_active' => Request::routeIs('warehouse.master-data*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-database w-5 h-5 mr-2"></i>',
                ];
            }
            // Generate Barcode
            if (auth()->user()->hasPermission('warehouse.barcode.view')) {
                $sub_modules[] = [
                    'name' => 'Generate Barcode',
                    'url' => route('warehouse.barcode'),
                    'is_active' => Request::routeIs('warehouse.barcode')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-barcode w-5 h-5 mr-2"></i>',
                ];
            }
            // Products
            if (auth()->user()->hasPermission('warehouse.products.view')) {
                $sub_modules[] = [
                    'name' => 'Products',
                    'url' => route('warehouse.products.index'),
                    'is_active' =>
                        Request::routeIs('warehouse.products*') &&
                        !Request::routeIs('warehouse.products.reports.*') &&
                        !in_array(request('status'), ['sold', 'hold'])
                            ? 'text-blue-600 border-r-4 border-blue-600'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-box w-5 h-5 mr-2"></i>',
                ];

                $sub_modules[] = [
                    'name' => 'Product Reports',
                    'url' => route('warehouse.products.reports.index'),
                    'is_active' => Request::routeIs('warehouse.products.reports.*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-chart-column w-5 h-5 mr-2"></i>',
                ];
            }

            // Product Issue
            if (auth()->user()->hasPermission('warehouse.product-issue.view')) {
                $sub_modules[] = [
                    'name' => 'Product Issue',
                    'url' => route('warehouse.product-issue.index'),
                    'is_active' => Request::routeIs('warehouse.product-issue*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-export w-5 h-5 mr-2"></i>',
                ];
            }

            // Sold Products
            if (auth()->user()->hasPermission('warehouse.products.view')) {
                $sub_modules[] = [
                    'name' => 'Sold Products',
                    'url' => route('warehouse.products.index', ['status' => 'sold']),
                    'is_active' =>
                        Request::routeIs('warehouse.products.index') && request('status') === 'sold'
                            ? 'text-blue-600 border-r-4 border-blue-600'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-cart-shopping w-5 h-5 mr-2"></i>',
                ];
            }

            // Hold Products
            if (auth()->user()->hasPermission('warehouse.products.view')) {
                $sub_modules[] = [
                    'name' => 'Hold Products',
                    'url' => route('warehouse.products.index', ['status' => 'hold']),
                    'is_active' =>
                        Request::routeIs('warehouse.products.index') && request('status') === 'hold'
                            ? 'text-blue-600 border-r-4 border-blue-600'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-pause w-5 h-5 mr-2"></i>',
                ];
            }

            // Technicians
            if (auth()->user()->hasPermission('warehouse.technicians.view')) {
                $sub_modules[] = [
                    'name' => 'Technicians',
                    'url' => route('warehouse.technicians.index'),
                    'is_active' => Request::routeIs('warehouse.technicians*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-user-gear w-5 h-5 mr-2"></i>',
                ];
            }

            // Parts
            if (auth()->user()->hasPermission('warehouse.parts.view')) {
                $sub_modules[] = [
                    'name' => 'Parts',
                    'url' => route('warehouse.parts.index'),
                    'is_active' => Request::routeIs('warehouse.parts*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-cogs w-5 h-5 mr-2"></i>',
                ];
            }

            // Gate Pass
            if (auth()->user()->hasPermission('warehouse.gate-pass.view')) {
                $sub_modules[] = [
                    'name' => 'Gate Pass',
                    'url' => route('warehouse.gate-pass.index'),
                    'is_active' => Request::routeIs('warehouse.gate-pass*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-clipboard-check w-5 h-5 mr-2"></i>',
                ];
            }

            // Scrap
            if (auth()->user()->hasPermission('warehouse.scrap.view')) {
                $sub_modules[] = [
                    'name' => 'Scrap',
                    'url' => route('warehouse.scrap.index'),
                    'is_active' => Request::routeIs('warehouse.scrap*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-trash-alt w-5 h-5 mr-2"></i>',
                ];
            }

            // Progress Sheet
            if (auth()->user()->hasPermission('warehouse.progress-sheet.view')) {
                $sub_modules[] = [
                    'name' => 'Progress Sheet',
                    'url' => route('warehouse.progress-sheet.index'),
                    'is_active' => Request::routeIs('warehouse.progress-sheet*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-table-list w-5 h-5 mr-2"></i>',
                ];
            }

            // ── Scan Stations ──────────────────────────────────────────────
            if (auth()->user()->hasPermission('warehouse.scan.view')) {
                $scanGroup = [];

                $scanGroup[] = [
                    'name' => 'Scan — Location A',
                    'url' => route('warehouse.scan.location-a'),
                    'is_active' => Request::routeIs('warehouse.scan.location-a')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-barcode w-4 h-4 mr-2 text-yellow-500"></i>',
                ];

                $scanGroup[] = [
                    'name' => 'Scan — Location B',
                    'url' => route('warehouse.scan.location-b'),
                    'is_active' => Request::routeIs('warehouse.scan.location-b')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-barcode w-4 h-4 mr-2 text-green-500"></i>',
                ];

                $scanGroup[] = [
                    'name' => 'Scan Logs',
                    'url' => route('warehouse.scan.logs'),
                    'is_active' => Request::routeIs('warehouse.scan.logs')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-list-check w-4 h-4 mr-2"></i>',
                ];

                $sub_modules[] = [
                    'type' => 'group',
                    'name' => 'Scan Tracking',
                    'icon' => '<i class="fa-solid fa-qrcode w-5 h-5 mr-2"></i>',
                    'children' => $scanGroup,
                ];
            }

            // Activity Logs
            if (auth()->user()->hasPermission('warehouse.activity-logs.view') || auth()->user()->isSuperAdmin()) {
                $sub_modules[] = [
                    'name' => 'Activity Logs',
                    'url' => route('warehouse.activity-logs.index'),
                    'is_active' => Request::routeIs('warehouse.activity-logs.*')
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-clock-rotate-left w-5 h-5 mr-2"></i>',
                ];
            }
        @endphp


        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('logistics.*'))
        @php
            $sub_modules = [];

            // Dashboard
            $sub_modules[] = [
                'name' => 'Dashboard',
                'url' => route('logistics.dashboard'),
                'is_active' => Request::routeIs('logistics.dashboard')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-chart-pie w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Manage Booking',
                'url' => route('logistics.manage-booking.v2.index'),
                'is_active' => Request::routeIs('logistics.manage-booking.v2.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-chart-pie w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'My Finance ',
                'url' => route('logistics.lc.index'),
                'is_active' => Request::routeIs('logistics.lc.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-chart-pie w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Custom & GD',
                'url' => route('logistics.gd.index'),
                'is_active' => Request::routeIs('logistics.gd.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-chart-pie w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Analytics',
                'url' => route('logistics.analytics.index'),
                'is_active' => Request::routeIs('logistics.analytics.index')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-chart-pie w-5 h-5 mr-2"></i>',
            ];
            // Suppliers
            $sub_modules[] = [
                'name' => 'Suppliers',
                'url' => route('logistics.suppliers.v2.index'),
                'is_active' => Request::routeIs('logistics.suppliers.v2.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-truck w-5 h-5 mr-2"></i>',
            ];
            // Carriers
            $sub_modules[] = [
                'name' => 'Carriers',
                'url' => route('logistics.carriers.v2.index'),
                'is_active' => Request::routeIs('logistics.carriers.v2.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-truck-fast w-5 h-5 mr-2"></i>',
            ];

            // Entities (Importers / Exporters)
            $sub_modules[] = [
                'name' => 'Entities',
                'url' => route('logistics.entities.index'),
                'is_active' => Request::routeIs('logistics.entities.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-users w-5 h-5 mr-2"></i>',
            ];

            // Purchase Orders
            $sub_modules[] = [
                'name' => 'Purchase Orders',
                'url' => route('logistics.purchase-orders.index'),
                'is_active' => Request::routeIs('logistics.purchase-orders.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-file-invoice-dollar w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Document',
                'url' => route('logistics.documents.index'),
                'is_active' => Request::routeIs('logistics.documents.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-file-lines w-5 h-5 mr-2"></i>',
            ];

            // Shipments
            $sub_modules[] = [
                'name' => 'Shipments',
                'url' => route('logistics.shipments.index'),
                'is_active' => Request::routeIs('logistics.shipments.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-truck-fast w-5 h-5 mr-2"></i>',
            ];

            // Maersk API Integration
            $sub_modules[] = [
                'name' => 'Maersk API',
                'url' => route('logistics.maersk.index'),
                'is_active' => Request::routeIs('logistics.maersk.*')
                    ? 'text-blue-600 border-r-4 border-blue-600'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-ship w-5 h-5 mr-2"></i>',
            ];
        @endphp

        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('hrm.*'))
        @php
            $sub_modules = [];

            // HRM Dashboard
            if (auth()->user()->hasPermission('hrm.dashboard.view')) {
                $sub_modules[] = [
                    'name' => 'Dashboard',
                    'url' => route('hrm.index'),
                    'is_active' => Request::routeIs('hrm.index')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-gauge-high w-5 h-5 mr-2"></i>',
                ];
            }

            // Organization (Employee Type)
            if (auth()->user()->hasPermission('hrm.employee-type.view')) {
                $sub_modules[] = [
                    'name' => 'Organization',
                    'url' => route('hrm.employee-type.index'),
                    'is_active' => Request::routeIs('hrm.employee-type.index')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-sitemap w-5 h-5 mr-2"></i>',
                ];
            }

            // Employees
            if (auth()->user()->hasPermission('hrm.employee.view')) {
                $sub_modules[] = [
                    'name' => 'Employees',
                    'url' => route('hrm.employee.index'),
                    'is_active' => Request::routeIs('hrm.employee.index')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-users-gear w-5 h-5 mr-2"></i>',
                ];
            }

            // Attendance
            if (auth()->user()->hasPermission('hrm.attendance.view')) {
                $sub_modules[] = [
                    'name' => 'Attendance',
                    'url' => route('hrm.attendance.index'),
                    'is_active' => Request::routeIs('hrm.attendance.index')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-clipboard-user w-5 h-5 mr-2"></i>',
                ];
            }

            // Leaves
            if (auth()->user()->hasPermission('hrm.leave.view')) {
                $sub_modules[] = [
                    'name' => 'Leaves',
                    'url' => route('hrm.calendar'),
                    'is_active' => Request::routeIs('hrm.calendar')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-regular fa-calendar-days w-5 h-5 mr-2"></i>',
                ];
            }
            $sub_modules[] = [
                'name' => 'Recruitment',
                'url' => route('hrm.recruitment.dashboard'),
                'is_active' => Request::routeIs('hrm.recruitment.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-user-plus w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Announcements',
                'url' => route('hrm.announcements.index'),
                'is_active' => Request::routeIs('hrm.announcements.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-bullhorn w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Policies',
                'url' => route('hrm.policies.index'),
                'is_active' => Request::routeIs('hrm.policies.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-scale-balanced w-5 h-5 mr-2"></i>',
            ];

            $offboardingChildren = [];

            if (auth()->user()->hasPermission('hrm.warnings.view')) {
                $offboardingChildren[] = [
                    'name' => 'Warnings',
                    'url' => route('hrm.warnings.index'),
                    'is_active' => Request::routeIs('hrm.warnings.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-triangle-exclamation w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('hrm.resignations.view')) {
                $offboardingChildren[] = [
                    'name' => 'Resignations',
                    'url' => route('hrm.resignations.index'),
                    'is_active' => Request::routeIs('hrm.resignations.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-signature w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('hrm.complaints.view')) {
                $offboardingChildren[] = [
                    'name' => 'Complaints',
                    'url' => route('hrm.complaints.index'),
                    'is_active' => Request::routeIs('hrm.complaints.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-circle-exclamation w-4 h-4 mr-2"></i>',
                ];
            }

            if (!empty($offboardingChildren)) {
                $sub_modules[] = [
                    'type' => 'group',
                    'name' => 'Offboarding',
                    'icon' => '<i class="fa-solid fa-user-xmark w-5 h-5 mr-2"></i>',
                    'children' => $offboardingChildren,
                ];
            }

            $surveyChildren = [];

            if (auth()->user()->hasPermission('hrm.surveys.view')) {
                $surveyChildren[] = [
                    'name' => 'Manage Surveys',
                    'url' => route('hrm.surveys.index'),
                    'is_active' =>
                        Request::routeIs('hrm.surveys.index') ||
                        Request::routeIs('hrm.surveys.create') ||
                        Request::routeIs('hrm.surveys.edit') ||
                        Request::routeIs('hrm.surveys.analytics')
                            ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-list-check w-4 h-4 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('hrm.surveys.participate')) {
                $surveyChildren[] = [
                    'name' => 'Active Surveys',
                    'url' => route('hrm.surveys.active'),
                    'is_active' =>
                        Request::routeIs('hrm.surveys.active') || Request::routeIs('hrm.surveys.take')
                            ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-clipboard-question w-4 h-4 mr-2"></i>',
                ];
            }

            if (!empty($surveyChildren)) {
                $sub_modules[] = [
                    'type' => 'group',
                    'name' => 'Surveys',
                    'icon' => '<i class="fa-solid fa-square-poll-vertical w-5 h-5 mr-2"></i>',
                    'children' => $surveyChildren,
                ];
            }
            if (auth()->user()->hasPermission('hrm.reports.view')) {
                $sub_modules[] = [
                    'name' => 'Reports',
                    'url' => route('hrm.reports.index'),
                    'is_active' => Request::routeIs('hrm.reports.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-chart-column w-5 h-5 mr-2"></i>',
                ];
            }
            // Activity Logs
            if (auth()->user()->hasPermission('hrm.activity-logs.view') || auth()->user()->isSuperAdmin()) {
                $sub_modules[] = [
                    'name' => 'Activity Logs',
                    'url' => route('hrm.activity-logs.index'),
                    'is_active' => Request::routeIs('hrm.activity-logs.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-clock-rotate-left w-5 h-5 mr-2"></i>',
                ];
            }

        @endphp


        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('payroll.*'))
        @php
            $sub_modules = [];

            $sub_modules[] = [
                'name' => 'Dashboard',
                'url' => route('payroll.dashboard'),
                'is_active' => Request::routeIs('payroll.dashboard')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-gauge-high w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Pay Runs',
                'url' => route('payroll.pay-runs.index'),
                'is_active' => Request::routeIs('payroll.pay-runs.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-receipt w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Payroll Control Center',
                'url' => route('payroll.control-center.index'),
                'is_active' => Request::routeIs('payroll.control-center.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-shield-halved w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Payroll Operations',
                'url' => route('payroll.operations.index'),
                'is_active' => Request::routeIs('payroll.operations.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-sliders w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Employee Pay',
                'url' => route('payroll.employee-pay.index'),
                'is_active' => Request::routeIs('payroll.employee-pay.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-user-tie w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Pay Items',
                'url' => route('payroll.pay-items.index'),
                'is_active' => Request::routeIs('payroll.pay-items.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-list-check w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Payroll Liabilities',
                'url' => route('payroll.liabilities.index'),
                'is_active' => Request::routeIs('payroll.liabilities.index')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-file-invoice-dollar w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Liabilities Report',
                'url' => route('payroll.liabilities.report'),
                'is_active' => Request::routeIs('payroll.liabilities.report')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-chart-bar w-5 h-5 mr-2"></i>',
            ];

            // Payment Summary by Statutory Body (PAYE / EPF / SOCSO etc.)
            $sub_modules[] = [
                'name' => 'By Statutory Body',
                'url' => route('payroll.liabilities.by-statutory-body'),
                'is_active' => Request::routeIs('payroll.liabilities.by-statutory-body')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-building-columns w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Tax Brackets',
                'url' => route('payroll.tax-brackets.index'),
                'is_active' => Request::routeIs('payroll.tax-brackets.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-percent w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Payroll Report',
                'url' => route('payroll.reports.summary'),
                'is_active' => Request::routeIs('payroll.reports.summary') || Request::routeIs('payroll.reports.overtime')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-chart-column w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Employee History',
                'url' => route('payroll.reports.employee-history'),
                'is_active' => Request::routeIs('payroll.reports.employee-history')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-user-clock w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Payroll Calendar',
                'url' => route('payroll.reports.calendar'),
                'is_active' => Request::routeIs('payroll.reports.calendar')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-calendar-days w-5 h-5 mr-2"></i>',
            ];

            $sub_modules[] = [
                'name' => 'Payroll Analytics',
                'url' => route('payroll.reports.analytics'),
                'is_active' => Request::routeIs('payroll.reports.analytics')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-chart-line w-5 h-5 mr-2"></i>',
            ];

            if (auth()->user()->hasPermission('payroll.organization-settings.users.view')) {
                $sub_modules[] = [
                    'name' => 'Payroll Users',
                    'url' => route('payroll.organization-settings.users.index'),
                    'is_active' => Request::routeIs('payroll.organization-settings.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-user-shield w-5 h-5 mr-2"></i>',
                ];
            }
        @endphp

        @if (count($sub_modules) > 0)
            <x-sub-sidebar :sub_modules="$sub_modules" />
        @endif
    @endif

    @if (Request::routeIs('mass-mailer.*'))
        @php
            $sub_modules = [
                [
                    'name' => 'Dashboard',
                    'url' => route('mass-mailer.dashboard'),
                    'is_active' => Request::routeIs('mass-mailer.dashboard')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-gauge-high w-5 h-5 mr-2"></i>',
                ],
                [
                    'name' => 'Tags',
                    'url' => route('mass-mailer.tags.index'),
                    'is_active' => Request::routeIs('mass-mailer.tags.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-tags w-5 h-5 mr-2"></i>',
                ],
                [
                    'name' => 'Contacts',
                    'url' => route('mass-mailer.contacts.index'),
                    'is_active' => Request::routeIs('mass-mailer.contacts.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-users w-5 h-5 mr-2"></i>',
                ],
                [
                    'name' => 'Templates',
                    'url' => route('mass-mailer.templates.index'),
                    'is_active' => Request::routeIs('mass-mailer.templates.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-file-lines w-5 h-5 mr-2"></i>',
                ],
                [
                    'name' => 'Send Email',
                    'url' => route('mass-mailer.sender.create'),
                    'is_active' => Request::routeIs('mass-mailer.sender.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-paper-plane w-5 h-5 mr-2"></i>',
                ],
            ];
        @endphp
        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif

    @if (Request::routeIs('project.*'))
        @php
            $sub_modules = [];

            // Dashboard
            $sub_modules[] = [
                'name' => 'Dashboard',
                'url' => route('project.dashboard'),
                'is_active' => Request::routeIs('project.dashboard')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-gauge-high w-5 h-5 mr-2"></i>',
            ];

            // Projects List
            if (auth()->user()->hasPermission('project.projects.view')) {
                $sub_modules[] = [
                    'name' => 'Projects',
                    'url' => route('project.projects.index'),
                    'is_active' => Request::routeIs('project.projects.*')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-folder-open w-5 h-5 mr-2"></i>',
                ];
            }

            // Clients
            $sub_modules[] = [
                'name' => 'Clients',
                'url' => route('project.clients.index'),
                'is_active' => Request::routeIs('project.clients.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-users w-5 h-5 mr-2"></i>',
            ];

            // Teams
            $sub_modules[] = [
                'name' => 'Teams',
                'url' => route('project.teams.index'),
                'is_active' => Request::routeIs('project.teams.*')
                    ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                    : 'hover:bg-blue-50',
                'icon' => '<i class="fa-solid fa-people-group w-5 h-5 mr-2"></i>',
            ];
        @endphp
        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif

    @if (Request::routeIs('ticket.*'))
        @php
            $sub_modules = [];

            if (auth()->user()->hasPermission('ticket.dashboard.view')) {
                $sub_modules[] = [
                    'name' => 'Dashboard',
                    'url' => route('ticket.dashboard'),
                    'is_active' => Request::routeIs('ticket.dashboard')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-gauge-high w-5 h-5 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('ticket.tickets.view')) {
                $sub_modules[] = [
                    'name' => 'All Tickets',
                    'url' => route('ticket.tickets.index'),
                    'is_active' =>
                        Request::routeIs('ticket.tickets.index') || Request::routeIs('ticket.tickets.show')
                            ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                            : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-ticket w-5 h-5 mr-2"></i>',
                ];
            }

            if (auth()->user()->employee && auth()->user()->hasPermission('ticket.tickets.assigned')) {
                $sub_modules[] = [
                    'name' => 'My Tickets',
                    'url' => route('ticket.tickets.assigned'),
                    'is_active' => Request::routeIs('ticket.tickets.assigned')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-list-check w-5 h-5 mr-2"></i>',
                ];
            }

            if (auth()->user()->hasPermission('ticket.tickets.create')) {
                $sub_modules[] = [
                    'name' => 'Log New Issue',
                    'url' => route('ticket.tickets.create'),
                    'is_active' => Request::routeIs('ticket.tickets.create')
                        ? 'text-[#0078C8] border-r-4 border-[#0078C8]'
                        : 'hover:bg-blue-50',
                    'icon' => '<i class="fa-solid fa-circle-plus w-5 h-5 mr-2"></i>',
                ];
            }
        @endphp
        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif
    @php
        $sub_modules = [];
    @endphp
    @if (Request::routeIs('tasks.*'))
        <x-sub-sidebar :sub_modules="$sub_modules" />
    @endif
</aside>
