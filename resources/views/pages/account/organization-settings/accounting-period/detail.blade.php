@extends('layouts.auth')

@section('title', 'Accounting Period')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.organization_settings') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">
                    <div class="mx-auto bg-white shadow-sm border border-gray-200 rounded-lg p-6 space-y-8">

                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                            <h1 class="text-xl font-semibold text-gray-800">
                                Accounting Period Details
                            </h1>

                            <a href="{{ route('account.organization-settings.accounting-period.edit', $period->id) }}"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Edit
                            </a>
                        </div>

                        <!-- Fiscal Year -->
                        <section>
                            <h2 class="text-gray-800 font-semibold mb-3">Fiscal Year</h2>

                            <div class="bg-gray-50 rounded-md border border-gray-200 p-3">
                                <p class="text-sm text-gray-700 flex justify-between">
                                    <span>Fiscal Year Label:</span>
                                    <span class="font-medium">
                                        {{ $period->name ?? 'N/A' }}
                                    </span>
                                </p>

                                <p class="text-sm text-gray-700 flex justify-between mt-2">
                                    <span>Start Date:</span>
                                    <span class="font-medium">
                                        {{ $period->start_date ? $period->start_date->format('d F Y') : 'N/A' }}
                                    </span>
                                </p>

                                <p class="text-sm text-gray-700 flex justify-between mt-2">
                                    <span>End Date:</span>
                                    <span class="font-medium">
                                        {{ $period->end_date ? $period->end_date->format('d F Y') : 'N/A' }}
                                    </span>
                                </p>
                            </div>
                        </section>

                        <!-- Sales Tax -->
                        <section>
                            <h2 class="text-gray-800 font-semibold mb-3 border-b border-gray-200 pb-1">Sales Tax</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                                <!-- Tax Basics -->
                                <div>
                                    <p class="text-xs uppercase text-gray-500">Tax Basics</p>
                                    <p class="text-sm font-medium text-gray-800 mt-1">
                                        {{ $period->taxRate ? 'Registered' : 'Not Registered' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase text-gray-500">Tax Rate</p>
                                    <p class="text-sm font-medium text-gray-800 mt-1">
                                        {{ $period->taxRate->tax_rate ?? '0.0' }}
                                    </p>
                                </div>

                                <!-- Tax ID -->
                                <div>
                                    <p class="text-xs uppercase text-gray-500">Tax ID Number</p>
                                    <p class="text-sm font-medium text-gray-800 mt-1">
                                        {{ $period->taxRate->code ?? 'N/A' }}
                                    </p>
                                </div>

                                <!-- Tax Period -->
                                <div>
                                    <p class="text-xs uppercase text-gray-500">Tax Period</p>
                                    <p class="text-sm font-medium text-gray-800 mt-1">
                                        {{ $period->tax_period ? ucfirst($period->tax_period) : 'N/A' }}
                                    </p>
                                </div>

                            </div>
                        </section>

                        <!-- Tax Defaults -->
                        <section>
                            <h2 class="text-gray-800 font-semibold mb-3 border-b border-gray-200 pb-1">Tax Defaults</h2>

                            <div class="space-y-3">

                                <!-- Sales Tax Default -->
                                <div class="grid md:grid-cols-2 gap-6 items-center">
                                    <div>
                                        <p class="text-xs uppercase text-gray-500">For Sales</p>
                                        <p class="text-sm font-medium text-gray-800 mt-1">
                                            {{ $period->default_sale_tax_rate_id ? 'Tax Exclusive' : 'No Tax' }}
                                        </p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        Includes invoices, quotes, credit notes and receive money items
                                    </p>
                                </div>

                                <!-- Purchase Tax Default -->
                                <div class="grid md:grid-cols-2 gap-6 items-center">
                                    <div>
                                        <p class="text-xs uppercase text-gray-500">For Purchase</p>
                                        <p class="text-sm font-medium text-gray-800 mt-1">
                                            {{ $period->default_purchase_tax_rate_id ? 'Tax Inclusive' : 'No Tax' }}
                                        </p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        Includes bills, purchase orders, credit notes and spend money items
                                    </p>
                                </div>

                            </div>
                        </section>

                        <!-- Lock Dates -->
                        <section>
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="text-gray-800 font-semibold">Lock Users</h2>
                            </div>

                            <span class="text-sm text-gray-700">
                                Users prevented from making changes during this locked period:
                            </span>

                            <div class="bg-gray-50 rounded-md border border-gray-200 divide-y divide-gray-100 mt-2">
                                <div class="px-4 py-3">
                                    @if ($period->lockUsers->count() > 0)
                                        <ul class="list-disc list-inside text-gray-800 text-sm">
                                            @foreach ($period->lockUsers as $lockUser)
                                                <li>{{ $lockUser->user->fullName ?? 'Unknown User' }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-sm text-gray-500">No users are locked for this period.</p>
                                    @endif
                                </div>
                            </div>
                        </section>


                        <!-- Footer -->
                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-200">
                            <a href="{{ route('account.organization-settings.accounting-period.edit', $period->id) }}"
                                class="px-4 py-2 bg-[#0078C8] text-white rounded-md hover:bg-[#006bb0] text-sm font-medium">
                                Edit Settings
                            </a>

                            <a href="{{ get_index_url('account.organization-settings.accounting-period.index') }}"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">
                                Back
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
