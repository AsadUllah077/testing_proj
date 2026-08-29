@extends('layouts.auth')
@section('title')
    Dashboard
@endsection

@section('content')
<div class="p-4 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Enterprise Overview</h1>
            <p class="text-sm text-gray-500 mt-1">{{ now()->format('l, F j, Y') }} &mdash; Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
            <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>Live Data
        </span>
    </div>

    {{-- ── Row 1: KPI Cards ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Total Revenue --}}
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl p-5 text-white ">
            <div class="flex items-center justify-between mb-3">
                <span class="text-indigo-100 text-xs font-medium uppercase tracking-wider">Total Revenue</span>
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    {{ currency()['symbol'] ?? 'RS' }}
                </div>
            </div>
            <p class="text-2xl font-bold">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($totalSalesAmount) }}</p>
            <p class="text-indigo-200 text-xs mt-1">{{ $totalSalesCount }} invoices total</p>
        </div>

        {{-- Employees --}}
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-xl p-5 text-white ">
            <div class="flex items-center justify-between mb-3">
                <span class="text-emerald-100 text-xs font-medium uppercase tracking-wider">Employees</span>
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold">{{ $totalEmployees }}</p>
            <p class="text-emerald-200 text-xs mt-1">{{ $activeEmployees }} active &bull; {{ $todayAttendance }} checked in today</p>
        </div>

        {{-- Active Projects --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl p-5 text-white ">
            <div class="flex items-center justify-between mb-3">
                <span class="text-purple-100 text-xs font-medium uppercase tracking-wider">Projects</span>
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold">{{ $totalProjects }}</p>
            <p class="text-purple-200 text-xs mt-1">{{ $activeProjects }} active &bull; {{ $completedProjects }} completed</p>
        </div>

        {{-- Open Tickets --}}
        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-xl p-5 text-white ">
            <div class="flex items-center justify-between mb-3">
                <span class="text-rose-100 text-xs font-medium uppercase tracking-wider">Tickets</span>
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold">{{ $totalTickets }}</p>
            <p class="text-rose-200 text-xs mt-1">{{ $openTickets }} open &bull; {{ $inProgressTickets }} in progress</p>
        </div>
    </div>

    {{-- ── Row 2: Charts ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Sales vs Bills Bar Chart (2/3 width) --}}
        <div class="lg:col-span-2 bg-white rounded-xl -sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Revenue Overview</h2>
                    <p class="text-xs text-gray-500">Sales vs Bills — last 6 months</p>
                </div>
                <div class="flex items-center space-x-4 text-xs text-gray-500">
                    <span class="flex items-center"><span class="w-3 h-3 rounded-sm bg-indigo-500 mr-1.5 inline-block"></span>Sales</span>
                    <span class="flex items-center"><span class="w-3 h-3 rounded-sm bg-orange-400 mr-1.5 inline-block"></span>Bills</span>
                </div>
            </div>
            <div class="relative h-56">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        {{-- Employee Status Doughnut (1/3 width) --}}
        <div class="bg-white rounded-xl -sm p-6">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-800">Employee Status</h2>
                <p class="text-xs text-gray-500">Breakdown by status</p>
            </div>
            <div class="relative h-44 flex items-center justify-center">
                <canvas id="employeeChart"></canvas>
                <div class="absolute text-center pointer-events-none">
                    <p class="text-2xl font-bold text-gray-800">{{ $totalEmployees }}</p>
                    <p class="text-xs text-gray-400">Total</p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                <div class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span><span class="text-gray-600">Active <strong>{{ $activeEmployees }}</strong></span></div>
                <div class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-yellow-400 mr-1.5"></span><span class="text-gray-600">Inactive <strong>{{ $inactiveEmployees }}</strong></span></div>
                <div class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-blue-400 mr-1.5"></span><span class="text-gray-600">Resigned <strong>{{ $resignedEmployees }}</strong></span></div>
                <div class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-red-400 mr-1.5"></span><span class="text-gray-600">Terminated <strong>{{ $terminatedEmployees }}</strong></span></div>
            </div>
        </div>
    </div>

    {{-- ── Row 3: Project & Ticket Status + Quick Stats ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Project Status --}}
        <div class="bg-white rounded-xl -sm p-6">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-800">Project Pipeline</h2>
                <p class="text-xs text-gray-500">Status breakdown</p>
            </div>
            @php $pTotal = max($totalProjects, 1); @endphp
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-blue-500 mr-1.5"></span>Planning</span>
                        <span class="font-medium">{{ $planningProjects }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-blue-500 h-2 rounded-full" style="width:{{ round($planningProjects/$pTotal*100) }}%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-indigo-500 mr-1.5"></span>Active</span>
                        <span class="font-medium">{{ $activeProjects }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-indigo-500 h-2 rounded-full" style="width:{{ round($activeProjects/$pTotal*100) }}%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-green-500 mr-1.5"></span>Completed</span>
                        <span class="font-medium">{{ $completedProjects }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-green-500 h-2 rounded-full" style="width:{{ round($completedProjects/$pTotal*100) }}%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-yellow-500 mr-1.5"></span>On Hold</span>
                        <span class="font-medium">{{ $onHoldProjects }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-yellow-500 h-2 rounded-full" style="width:{{ round($onHoldProjects/$pTotal*100) }}%"></div></div>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between text-xs text-gray-500">
                <span>Total Projects</span>
                <span class="font-bold text-gray-800">{{ $totalProjects }}</span>
            </div>
        </div>

        {{-- Ticket Status --}}
        <div class="bg-white rounded-xl -sm p-6">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-800">Ticket Status</h2>
                <p class="text-xs text-gray-500">Support queue overview</p>
            </div>
            <div class="relative h-44 flex items-center justify-center">
                <canvas id="ticketChart"></canvas>
                <div class="absolute text-center pointer-events-none">
                    <p class="text-2xl font-bold text-gray-800">{{ $totalTickets }}</p>
                    <p class="text-xs text-gray-400">Tickets</p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="bg-blue-50 rounded-lg p-2">
                    <p class="text-xl font-bold text-blue-600">{{ $openTickets }}</p>
                    <p class="text-gray-500">Open</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-2">
                    <p class="text-xl font-bold text-yellow-600">{{ $inProgressTickets }}</p>
                    <p class="text-gray-500">In Progress</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-2">
                    <p class="text-xl font-bold text-gray-600">{{ $closedTickets }}</p>
                    <p class="text-gray-500">Closed</p>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="bg-white rounded-xl -sm p-6">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-800">Quick Stats</h2>
                <p class="text-xs text-gray-500">Key metrics at a glance</p>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-pink-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-pink-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <span class="text-sm text-gray-700">Customers</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900">{{ $totalCustomers }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-cyan-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-cyan-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <span class="text-sm text-gray-700">Vendors</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900">{{ $totalVendors }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <span class="text-sm text-gray-700">Products</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900">{{ $totalProducts }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <span class="text-sm text-gray-700">System Users</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900">{{ $totalUsers }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 4: Products & Gate Passes ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Product Overview --}}
        <div class="bg-white rounded-xl -sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Product Overview</h2>
                    <p class="text-xs text-gray-500">Inventory, sold units & assignment status</p>
                </div>
                @if(auth()->user()->hasPermission('product.dashboard.view'))
                <a href="{{ route('products.dashboard') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View All &rarr;</a>
                @endif
            </div>

            {{-- KPI row --}}
            <div class="grid grid-cols-2 gap-3 mb-5">
                <button type="button" data-dashboard-modal-open="inventory-products-modal" class="w-full text-left bg-emerald-50 rounded-lg p-3 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <p class="text-2xl font-bold text-emerald-600">{{ number_format($totalInventoryUnits) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Total Inventory</p>
                </button>
                <button type="button" data-dashboard-modal-open="sold-products-modal" class="w-full text-left bg-indigo-50 rounded-lg p-3 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-2xl font-bold text-indigo-600">{{ number_format($soldProducts) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Sold</p>
                </button>
                <button type="button" data-dashboard-modal-open="sold-today-products-modal" class="w-full text-left bg-sky-50 rounded-lg p-3 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <p class="text-2xl font-bold text-sky-600">{{ number_format($soldProductsToday) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Sold Today</p>
                </button>
                <button type="button" data-dashboard-modal-open="scrap-products-modal" class="w-full text-left bg-red-50 rounded-lg p-3 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <p class="text-2xl font-bold text-red-500">{{ number_format($scrapProducts) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Scrap</p>
                </button>
            </div>

            {{-- Assignment bar --}}
            <div class="mb-1 flex justify-between text-xs text-gray-500">
                <span>Assignment Status</span>
                <span>{{ $totalProducts }} total</span>
            </div>
            @php $pBase = max($totalProducts, 1); @endphp
            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden flex">
                <div class="bg-indigo-500 h-3" style="width:{{ round($assignedProducts/$pBase*100) }}%" title="Assigned: {{ $assignedProducts }}"></div>
                <div class="bg-gray-300 h-3" style="width:{{ round($unassignedProducts/$pBase*100) }}%" title="Unassigned: {{ $unassignedProducts }}"></div>
            </div>
            <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-500 mr-1.5"></span>Assigned {{ $assignedProducts }}</span>
                <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-gray-300 mr-1.5"></span>Unassigned {{ $unassignedProducts }}</span>
            </div>

            {{-- Recent products table --}}
            <div class="mt-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2">Recently Added</p>
                <div class="space-y-2">
                    @forelse($recentProducts as $prod)
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $prod->title ?? $prod->name ?? 'Product #'.$prod->id }}</p>
                            <p class="text-xs text-gray-400">{{ $prod->category->name ?? '—' }} &bull; {{ $prod->unique_id ?? '—' }}</p>
                        </div>
                        @php
                            $psc = match(strtolower($prod->status ?? '')) {
                                'active'   => 'bg-green-100 text-green-700',
                                'inactive' => 'bg-yellow-100 text-yellow-700',
                                default    => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $psc }}">{{ ucfirst($prod->status ?? 'N/A') }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-4 text-center">No products found</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Gate Pass Overview --}}
        <div class="bg-white rounded-xl -sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Gate Pass Overview</h2>
                    <p class="text-xs text-gray-500">QC & Security approval pipeline</p>
                </div>
                @if(auth()->user()->hasPermission('product.dashboard.view'))
                <a href="{{ route('warehouse.gatepass.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View All &rarr;</a>
                @endif
            </div>

            {{-- Status cards --}}
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="bg-blue-50 rounded-lg p-3 flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-blue-600">{{ $pendingQcGatePasses }}</p>
                        <p class="text-xs text-gray-500">Pending QC</p>
                    </div>
                </div>
                <div class="bg-orange-50 rounded-lg p-3 flex items-center space-x-3">
                    <div class="w-10 h-10 bg-orange-400 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-orange-500">{{ $pendingSecGatePasses }}</p>
                        <p class="text-xs text-gray-500">Pending Security</p>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-3 flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-green-600">{{ $approvedGatePasses }}</p>
                        <p class="text-xs text-gray-500">Approved</p>
                    </div>
                </div>
                <div class="bg-red-50 rounded-lg p-3 flex items-center space-x-3">
                    <div class="w-10 h-10 bg-red-400 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-red-500">{{ $rejectedGatePasses }}</p>
                        <p class="text-xs text-gray-500">Rejected</p>
                    </div>
                </div>
            </div>

            {{-- Gate pass pipeline progress --}}
            @php $gpBase = max($totalGatePasses, 1); @endphp
            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden flex mb-2">
                <div class="bg-green-500 h-3" style="width:{{ round($approvedGatePasses/$gpBase*100) }}%"></div>
                <div class="bg-blue-400 h-3" style="width:{{ round($pendingQcGatePasses/$gpBase*100) }}%"></div>
                <div class="bg-orange-400 h-3" style="width:{{ round($pendingSecGatePasses/$gpBase*100) }}%"></div>
                <div class="bg-red-400 h-3" style="width:{{ round($rejectedGatePasses/$gpBase*100) }}%"></div>
            </div>
            <div class="text-xs text-gray-400 text-right mb-5">{{ $totalGatePasses }} total gate passes</div>

            {{-- Recent gate passes --}}
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">Recent Gate Passes</p>
            <div class="space-y-2">
                @forelse($recentGatePasses as $gp)
                <button type="button" data-dashboard-modal-open="gate-pass-products-{{ $gp->id }}" class="w-full flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0 text-left rounded-lg transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $gp->gate_pass_id ?? 'GP #'.$gp->id }}</p>
                        <p class="text-xs text-gray-400">By {{ $gp->preparedBy->name ?? '-' }} &bull; {{ \Carbon\Carbon::parse($gp->created_at)->format('M j, Y') }} &bull; {{ $gp->items_count }} products</p>
                    </div>
                    @php
                        $gsc = match($gp->status) {
                            'QC_APPROVAL_PENDING'       => 'bg-blue-100 text-blue-700',
                            'SECURITY_APPROVAL_PENDING' => 'bg-orange-100 text-orange-700',
                            'QC_AND_SECURITY_APPROVED'  => 'bg-green-100 text-green-700',
                            'REJECTED'                  => 'bg-red-100 text-red-700',
                            default                     => 'bg-gray-100 text-gray-600',
                        };
                        $gsl = match($gp->status) {
                            'QC_APPROVAL_PENDING'       => 'Pending QC',
                            'SECURITY_APPROVAL_PENDING' => 'Pending Security',
                            'QC_AND_SECURITY_APPROVED'  => 'Approved',
                            'REJECTED'                  => 'Rejected',
                            default                     => $gp->status,
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $gsc }}">{{ $gsl }}</span>
                </button>
                @empty
                <p class="text-sm text-gray-400 py-4 text-center">No gate passes found</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Row 5: Finance Summary + Attendance Card ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl -sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Finance Summary</h2>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Total Sales</span>
                        <span class="font-semibold text-indigo-600">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($totalSalesAmount) }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        @php $max = max($totalSalesAmount, $totalBillsAmount, 1); @endphp
                        <div class="bg-indigo-500 h-2 rounded-full" style="width:{{ round($totalSalesAmount/$max*100) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Total Bills</span>
                        <span class="font-semibold text-orange-500">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($totalBillsAmount) }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-orange-400 h-2 rounded-full" style="width:{{ round($totalBillsAmount/$max*100) }}%"></div>
                    </div>
                </div>
                <div class="pt-3 border-t border-gray-100">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Net</span>
                        @php $net = $totalSalesAmount - $totalBillsAmount; @endphp
                        <span class="font-bold {{ $net >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat(abs($net)) }} {{ $net >= 0 ? '▲' : '▼' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl -sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Today's Attendance</h2>
            <div class="flex flex-col items-center justify-center h-32">
                <p class="text-5xl font-bold text-emerald-600">{{ $todayAttendance }}</p>
                <p class="text-sm text-gray-500 mt-2">employees checked in</p>
                <div class="mt-3 text-xs text-gray-400">out of {{ $activeEmployees }} active employees</div>
            </div>
            @php $attPct = $activeEmployees > 0 ? round($todayAttendance / $activeEmployees * 100) : 0; @endphp
            <div class="mt-2">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Attendance Rate</span><span>{{ $attPct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="bg-emerald-500 h-2.5 rounded-full" style="width:{{ $attPct }}%"></div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl -sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">User Management</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Total Users</span>
                    <span class="font-bold text-gray-900">{{ $totalUsers }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 flex items-center"><span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>Enabled</span>
                    <span class="font-semibold text-green-600">{{ $enabledUsers }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 flex items-center"><span class="w-2 h-2 rounded-full bg-red-400 mr-2"></span>Disabled</span>
                    <span class="font-semibold text-red-500">{{ $disabledUsers }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Roles</span>
                    <span class="font-semibold text-gray-700">{{ $totalRoles }}</span>
                </div>
            </div>
            @php $enablePct = $totalUsers > 0 ? round($enabledUsers/$totalUsers*100) : 0; @endphp
            <div class="mt-4 pt-3 border-t border-gray-100">
                <div class="flex justify-between text-xs text-gray-500 mb-1"><span>Active Rate</span><span>{{ $enablePct }}%</span></div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width:{{ $enablePct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 5: Recent Sales & Recent Employees ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Recent Sales --}}
        <div class="bg-white rounded-xl -sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Recent Sales</h2>
                @if(auth()->user()->hasPermission('account.sale.view'))
                <a href="{{ route('account.dashboard') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View All &rarr;</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Invoice</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentSales as $sale)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 font-medium text-indigo-600">{{ $sale->invoice_no ?? $sale->ref_no ?? '#'.$sale->id }}</td>
                            <td class="px-6 py-3 text-gray-700">{{ $sale->customer->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ \Carbon\Carbon::parse($sale->date)->format('M j, Y') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ currency()['symbol'] }} {{ numberFormat($sale->total) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">No sales found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Employees --}}
        <div class="bg-white rounded-xl -sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Recent Employees</h2>
                @if(auth()->user()->hasPermission('hrm.employee.view'))
                <a href="{{ route('hrm.employee.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View All &rarr;</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Joined</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentEmployees as $emp)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $emp->full_name }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $emp->department->department ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ \Carbon\Carbon::parse($emp->created_at)->format('M j, Y') }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $sc = match($emp->status) {
                                        'active'     => 'bg-green-100 text-green-700',
                                        'inactive'   => 'bg-yellow-100 text-yellow-700',
                                        'resigned'   => 'bg-blue-100 text-blue-700',
                                        'terminated' => 'bg-red-100 text-red-700',
                                        default      => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">{{ ucfirst($emp->status) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">No employees found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $productModals = [
            [
                'id' => 'inventory-products-modal',
                'title' => 'Inventory Products',
                'count' => $totalInventoryUnits,
                'rows' => $inventoryProductRows,
                'empty' => 'No inventory products found.',
            ],
            [
                'id' => 'sold-products-modal',
                'title' => 'Sold Products',
                'count' => $soldProducts,
                'rows' => $soldProductRows,
                'empty' => 'No sold products found.',
            ],
            [
                'id' => 'sold-today-products-modal',
                'title' => 'Sold Today',
                'count' => $soldProductsToday,
                'rows' => $soldTodayProductRows,
                'empty' => 'No products sold today.',
            ],
            [
                'id' => 'scrap-products-modal',
                'title' => 'Scrap Products',
                'count' => $scrapProducts,
                'rows' => $scrapProductRows,
                'empty' => 'No scrap products found.',
            ],
        ];
    @endphp

    @foreach($productModals as $modal)
    <div id="{{ $modal['id'] }}" data-dashboard-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
        <div class="w-full max-w-4xl max-h-[85vh] overflow-hidden rounded-lg bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="{{ $modal['id'] }}-title">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 id="{{ $modal['id'] }}-title" class="text-base font-semibold text-gray-900">{{ $modal['title'] }}</h3>
                    <p class="text-xs text-gray-500">{{ number_format($modal['count']) }} total</p>
                </div>
                <button type="button" data-dashboard-modal-close class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">Close</button>
            </div>
            <div class="max-h-[68vh] overflow-y-auto">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Product</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Category</th>
                                <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500">Qty</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Source</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($modal['rows'] as $row)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-800">{{ $row['name'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $row['unique_id'] }}</p>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $row['category'] }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ number_format($row['quantity']) }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $row['status'] }}</span>
                                </td>
                                <td class="px-5 py-3 text-gray-500">
                                    <p>{{ $row['source'] }}</p>
                                    @if(!empty($row['date']))
                                    <p class="text-xs text-gray-400">{{ $row['date'] }}</p>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400">{{ $modal['empty'] }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @foreach($recentGatePasses as $gp)
    <div id="gate-pass-products-{{ $gp->id }}" data-dashboard-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
        <div class="w-full max-w-4xl max-h-[85vh] overflow-hidden rounded-lg bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="gate-pass-products-{{ $gp->id }}-title">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 id="gate-pass-products-{{ $gp->id }}-title" class="text-base font-semibold text-gray-900">{{ $gp->gate_pass_id ?? 'GP #'.$gp->id }}</h3>
                    <p class="text-xs text-gray-500">{{ $gp->items_count }} products &bull; {{ ucfirst(strtolower(str_replace('_', ' ', $gp->status))) }}</p>
                </div>
                <button type="button" data-dashboard-modal-close class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">Close</button>
            </div>
            <div class="max-h-[68vh] overflow-y-auto">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Product</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Category</th>
                                <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500">Qty</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500">Return</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($gp->items as $item)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-800">{{ $item->product->title ?? $item->product_name ?? 'Product #'.$item->product_id }}</p>
                                    <p class="text-xs text-gray-400">{{ $item->unique_id ?? $item->product->unique_id ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $item->category_name ?? $item->product->category->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ number_format($item->quantity ?? 1) }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $item->returned_at ? $item->returned_at->format('M j, Y') : 'Not returned' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No products found in this gate pass.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Revenue Bar Chart ──
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: @json($monthLabels->values()),
            datasets: [
                {
                    label: 'Sales',
                    data: @json($monthlySalesData->values()),
                    backgroundColor: 'rgba(99,102,241,0.8)',
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Bills',
                    data: @json($monthlyBillsData->values()),
                    backgroundColor: 'rgba(251,146,60,0.8)',
                    borderRadius: 4,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        font: { size: 11 },
                        callback: v => '{{ currency()['symbol'] ?? 'RS' }} ' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                    }
                }
            }
        }
    });

    // ── Employee Status Doughnut ──
    const empCtx = document.getElementById('employeeChart').getContext('2d');
    new Chart(empCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Inactive', 'Resigned', 'Terminated'],
            datasets: [{
                data: [{{ $activeEmployees }}, {{ $inactiveEmployees }}, {{ $resignedEmployees }}, {{ $terminatedEmployees }}],
                backgroundColor: ['#10b981','#facc15','#60a5fa','#f87171'],
                borderWidth: 0,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' '+ctx.label+': '+ctx.raw } } }
        }
    });

    // ── Ticket Doughnut ──
    const ticketCtx = document.getElementById('ticketChart').getContext('2d');
    new Chart(ticketCtx, {
        type: 'doughnut',
        data: {
            labels: ['Open', 'In Progress', 'Closed'],
            datasets: [{
                data: [{{ $openTickets }}, {{ $inProgressTickets }}, {{ $closedTickets }}],
                backgroundColor: ['#3b82f6','#f59e0b','#6b7280'],
                borderWidth: 0,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' '+ctx.label+': '+ctx.raw } } }
        }
    });

    const dashboardModals = document.querySelectorAll('[data-dashboard-modal]');
    const closeDashboardModal = (modal) => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (!document.querySelector('[data-dashboard-modal].flex')) {
            document.body.classList.remove('overflow-hidden');
        }
    };
    const openDashboardModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    document.querySelectorAll('[data-dashboard-modal-open]').forEach((button) => {
        button.addEventListener('click', () => openDashboardModal(button.dataset.dashboardModalOpen));
    });
    document.querySelectorAll('[data-dashboard-modal-close]').forEach((button) => {
        button.addEventListener('click', () => closeDashboardModal(button.closest('[data-dashboard-modal]')));
    });
    dashboardModals.forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeDashboardModal(modal);
            }
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            dashboardModals.forEach(closeDashboardModal);
        }
    });

});
</script>
@endpush
