@extends('layouts.auth')

@section('title')
    Dashboard
@endsection

@section('content')
@php
    $user = auth()->user();

    $moduleCards = [
        [
            'name' => 'Home',
            'subtitle' => 'Company workspace',
            'description' => 'Company dashboard, organization setup, and core workspace controls.',
            'route' => 'home.dashboard',
            'permissions' => ['home.dashboard.view'],
            'accent' => 'bg-blue-500',
            'ring' => 'group-hover:ring-blue-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'home.dashboard.view'],
                ['label' => 'Companies', 'permission' => 'home.companies.view'],
            ],
        ],
        [
            'name' => 'Users',
            'subtitle' => 'Access control',
            'description' => 'Manage system users, roles, permission lists, and role assignments.',
            'route' => 'users.dashboard',
            'permissions' => ['users.dashboard.view', 'users.user.view', 'users.role.view', 'users.permission.view'],
            'accent' => 'bg-pink-500',
            'ring' => 'group-hover:ring-pink-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'users.dashboard.view'],
                ['label' => 'Users', 'permission' => 'users.user.view'],
                ['label' => 'Roles', 'permission' => 'users.role.view'],
                ['label' => 'Permissions', 'permission' => 'users.permission.view'],
            ],
        ],
        [
            'name' => 'Product',
            'subtitle' => 'Product operations',
            'description' => 'Product overview with inventory, warehouse, and logistics entry points.',
            'route' => 'products.dashboard',
            'permissions' => ['product.dashboard.view', 'product.view'],
            'accent' => 'bg-emerald-500',
            'ring' => 'group-hover:ring-emerald-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
            'features' => [
                ['label' => 'Dashboard'],
                ['label' => 'Warehouse', 'permission' => 'warehouse.dashboard.view'],
                ['label' => 'Logistics', 'permission' => 'product.dashboard.view'],
            ],
        ],
        [
            'name' => 'Warehouse',
            'subtitle' => 'Inventory flow',
            'description' => 'Track products, locations, parts, technicians, gate passes, and scrap.',
            'route' => 'warehouse.dashboard',
            'permissions' => ['warehouse.dashboard.view'],
            'accent' => 'bg-cyan-500',
            'ring' => 'group-hover:ring-cyan-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'warehouse.dashboard.view'],
                ['label' => 'Products', 'permission' => 'warehouse.products.view'],
                ['label' => 'Parts', 'permission' => 'warehouse.parts.view'],
                ['label' => 'Gate Pass', 'permission' => 'warehouse.gate-pass.view'],
                ['label' => 'Scrap', 'permission' => 'warehouse.scrap.view'],
            ],
        ],
        [
            'name' => 'Logistics',
            'subtitle' => 'Freight and trade',
            'description' => 'Bookings, shipments, LC finance, customs declarations, suppliers, and carriers.',
            'route' => 'logistics.dashboard',
            'permissions' => ['logistics.dashboard.view', 'product.dashboard.view'],
            'accent' => 'bg-sky-600',
            'ring' => 'group-hover:ring-sky-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10h10zm0 0h2m-2 0H9m6 0h4v-5l-3-3h-3"/></svg>',
            'features' => [
                ['label' => 'Bookings'],
                ['label' => 'LC Finance'],
                ['label' => 'Custom & GD'],
                ['label' => 'Shipments'],
                ['label' => 'Carriers'],
            ],
        ],
        [
            'name' => 'Accounting',
            'subtitle' => 'Finance control',
            'description' => 'Sales, bills, purchases, journals, reporting, budgets, and reconciliations.',
            'route' => 'account.dashboard',
            'permissions' => ['account.dashboard.view'],
            'accent' => 'bg-orange-500',
            'ring' => 'group-hover:ring-orange-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'account.dashboard.view'],
                ['label' => 'Sales', 'permission' => 'account.sale.view'],
                ['label' => 'Bills', 'permission' => 'account.bill-invoice.view'],
                ['label' => 'Purchasing', 'permission' => 'account.purchase-order.view'],
                ['label' => 'Reports', 'permission' => 'account.reports.view'],
            ],
        ],
        [
            'name' => 'Projects',
            'subtitle' => 'Delivery tracking',
            'description' => 'Project dashboards, project records, clients, teams, and task progress.',
            'route' => 'project.dashboard',
            'permissions' => ['project.dashboard.view', 'project.view'],
            'accent' => 'bg-violet-500',
            'ring' => 'group-hover:ring-violet-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'project.dashboard.view'],
                ['label' => 'Projects', 'permission' => 'project.projects.view'],
                ['label' => 'Clients', 'permission' => 'project.clients.view'],
                ['label' => 'Teams', 'permission' => 'project.teams.view'],
            ],
        ],
        [
            'name' => 'Tickets',
            'subtitle' => 'Support desk',
            'description' => 'Issue logging, ticket assignment, progress tracking, and comment history.',
            'route' => 'ticket.dashboard',
            'permissions' => ['ticket.dashboard.view'],
            'accent' => 'bg-rose-500',
            'ring' => 'group-hover:ring-rose-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'ticket.dashboard.view'],
                ['label' => 'All Tickets', 'permission' => 'ticket.tickets.view'],
                ['label' => 'My Tickets', 'permission' => 'ticket.tickets.assigned'],
                ['label' => 'Create', 'permission' => 'ticket.tickets.create'],
            ],
        ],
        [
            'name' => 'HRM',
            'subtitle' => 'People operations',
            'description' => 'Employee records, attendance, leave, departments, recruitment, and surveys.',
            'route' => 'hrm.index',
            'permissions' => ['hrm.dashboard.view'],
            'accent' => 'bg-teal-500',
            'ring' => 'group-hover:ring-teal-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'hrm.dashboard.view'],
                ['label' => 'Employees', 'permission' => 'hrm.employee.view'],
                ['label' => 'Attendance', 'permission' => 'hrm.attendance.view'],
                ['label' => 'Leave', 'permission' => 'hrm.leave.view'],
            ],
        ],
        [
            'name' => 'Payroll',
            'subtitle' => 'Pay and compliance',
            'description' => 'Pay runs, pay items, employee compensation, tax brackets, liabilities, and reports.',
            'route' => 'payroll.dashboard',
            'permissions' => ['payroll.dashboard.view'],
            'accent' => 'bg-amber-500',
            'ring' => 'group-hover:ring-amber-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'payroll.dashboard.view'],
                ['label' => 'Pay Runs', 'permission' => 'payroll.pay-run.view'],
                ['label' => 'Employee Pay', 'permission' => 'payroll.employee-pay.view'],
                ['label' => 'Liabilities', 'permission' => 'payroll.liability.view'],
                ['label' => 'Reports', 'permission' => 'payroll.reports.view'],
            ],
        ],
        [
            'name' => 'QMS',
            'subtitle' => 'Quality management',
            'description' => 'Quality documents, SOPs, policies, trainings, CAPA, and approvals.',
            'route' => 'qms.index',
            'permissions' => ['qms.dashboard.view'],
            'accent' => 'bg-indigo-500',
            'ring' => 'group-hover:ring-indigo-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'qms.dashboard.view'],
                ['label' => 'Documents', 'permission' => 'qms.document.view'],
                ['label' => 'SOPs', 'permission' => 'qms.sop.view'],
                ['label' => 'Policies', 'permission' => 'qms.policy.view'],
                ['label' => 'Training', 'permission' => 'qms.training.view'],
            ],
        ],
        [
            'name' => 'Mass Mailer',
            'subtitle' => 'Campaign delivery',
            'description' => 'Email contacts, templates, tags, sender setup, and campaign dispatch.',
            'route' => 'mass-mailer.dashboard',
            'permissions' => ['mailer.view'],
            'accent' => 'bg-fuchsia-500',
            'ring' => 'group-hover:ring-fuchsia-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
            'features' => [
                ['label' => 'Dashboard', 'permission' => 'mailer.view'],
                ['label' => 'Contacts', 'permission' => 'mailer.contacts.view'],
                ['label' => 'Templates', 'permission' => 'mailer.templates.view'],
                ['label' => 'Send Email', 'permission' => 'mailer.send'],
            ],
        ],
        [
            'name' => 'Tasks',
            'subtitle' => 'Work management',
            'description' => 'Task lists, ownership, due dates, deliverables, status updates, and calendar work.',
            'route' => 'tasks.index',
            'permissions' => ['tasks.view'],
            'accent' => 'bg-lime-600',
            'ring' => 'group-hover:ring-lime-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
            'features' => [
                ['label' => 'Tasks', 'permission' => 'tasks.view'],
                ['label' => 'Create', 'permission' => 'tasks.create'],
                ['label' => 'Edit', 'permission' => 'tasks.edit'],
                ['label' => 'Delete', 'permission' => 'tasks.delete'],
            ],
        ],
        [
            'name' => 'Activity Logs',
            'subtitle' => 'Audit trail',
            'description' => 'Review system activity, user actions, and operational audit history.',
            'route' => 'activity.logs.index',
            'permissions' => ['activity.logs.view'],
            'accent' => 'bg-slate-600',
            'ring' => 'group-hover:ring-slate-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'features' => [
                ['label' => 'Logs', 'permission' => 'activity.logs.view'],
            ],
        ],
        [
            'name' => 'Financial AI',
            'subtitle' => 'Assistant',
            'description' => 'Ask finance questions with company context across accounting and payroll data.',
            'route' => 'ai.assistant.index',
            'permissions' => [],
            'requires_company' => true,
            'accent' => 'bg-purple-600',
            'ring' => 'group-hover:ring-purple-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
            'features' => [
                ['label' => 'Assistant'],
                ['label' => 'Suggestions'],
                ['label' => 'Company Context'],
            ],
        ],
        [
            'name' => 'Document Ingestion',
            'subtitle' => 'AI documents',
            'description' => 'Upload and confirm documents for AI-assisted transaction and record workflows.',
            'route' => 'ai.document.ingestion.index',
            'permissions' => [],
            'requires_company' => true,
            'accent' => 'bg-stone-600',
            'ring' => 'group-hover:ring-stone-100',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h6M5 3h10l4 4v14H5V3z"/></svg>',
            'features' => [
                ['label' => 'Uploads'],
                ['label' => 'Review'],
                ['label' => 'Confirm'],
            ],
        ],
    ];

    $canOpenModule = function (array $module) use ($user) {
        if (! $user) {
            return false;
        }

        if (! empty($module['requires_company']) && empty($user->company_id)) {
            return false;
        }

        $permissions = $module['permissions'] ?? [];

        if (! empty($permissions) && $user->hasAnyPermission($permissions)) {
            return true;
        }

        return empty($permissions) && ! empty($module['requires_company']);
    };

    $visibleModules = collect($moduleCards)
        ->filter(fn ($module) => \Illuminate\Support\Facades\Route::has($module['route']) && $canOpenModule($module))
        ->map(function ($module) use ($user) {
            $features = collect($module['features'] ?? [])
                ->filter(fn ($feature) => empty($feature['permission']) || $user->hasPermission($feature['permission']))
                ->values();

            if ($features->isEmpty()) {
                $features = collect([['label' => 'Dashboard']]);
            }

            $module['url'] = route($module['route']);
            $module['visible_features'] = $features;

            return $module;
        })
        ->values();
@endphp

<div class="p-3 sm:p-6">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Modules</h1>
            <p class="mt-1 text-sm text-gray-500">Welcome back, {{ auth()->user()->name ?? auth()->user()->username }}.</p>
        </div>
        <div class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 ">
            <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
            <span>{{ $visibleModules->count() }} {{ \Illuminate\Support\Str::plural('module', $visibleModules->count()) }} available</span>
        </div>
    </div>

    @if ($visibleModules->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center">
            <h2 class="text-base font-semibold text-gray-900">No modules available</h2>
            <p class="mt-1 text-sm text-gray-500">Your account does not have module access assigned yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach ($visibleModules as $module)
                @php
                    $featureCount = $module['visible_features']->count();
                    $previewFeatures = $module['visible_features']->take(4);
                    $remainingFeatures = max($featureCount - $previewFeatures->count(), 0);
                @endphp

                <a href="{{ $module['url'] }}"
                    class="group flex h-full min-h-[250px] flex-col rounded-lg border border-gray-200 bg-white p-5  transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:ring-offset-2">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="{{ $module['accent'] }} {{ $module['ring'] }} flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg text-white ring-8 ring-transparent transition">
                                {!! $module['icon'] !!}
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate text-base font-semibold text-gray-900">{{ $module['name'] }}</h2>
                                <p class="truncate text-xs font-medium uppercase tracking-wide text-gray-400">{{ $module['subtitle'] }}</p>
                            </div>
                        </div>
                        <svg class="mt-1 h-5 w-5 flex-shrink-0 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>

                    <p class="mt-4 flex-1 text-sm leading-6 text-gray-600">{{ $module['description'] }}</p>

                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ $featureCount }}</p>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    {{ \Illuminate\Support\Str::plural('area', $featureCount) }}
                                </p>
                            </div>
                            <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">Enabled</span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($previewFeatures as $feature)
                                <span class="rounded-md bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600">
                                    {{ $feature['label'] }}
                                </span>
                            @endforeach

                            @if ($remainingFeatures > 0)
                                <span class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">
                                    +{{ $remainingFeatures }} more
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
