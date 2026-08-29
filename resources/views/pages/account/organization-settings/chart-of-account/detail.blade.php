@extends('layouts.auth')

@section('title', 'Account Details')

@section('content')

    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3">
                @foreach (config('modules.organization_settings') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden ">
                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold">Account Details</h2>
                        </div>

                        <a href="{{ route('account.organization-settings.account.edit', $account->id) }}"
                            class="px-4 py-2 bg-[#0078C8] text-white rounded-md text-sm hover:bg-[#0a8de4]">
                            Edit
                        </a>
                    </div>

                    <div class="bg-white border-[#0078C8] rounded-md shadow-sm p-6 space-y-8 mt-5">

                        <!-- Account Info -->
                        <section>
                            <h3 class="text-gray-800 font-semibold mb-3">Account Information</h3>
                            <div class="grid md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-md">

                                <div>
                                    <p class="text-xs uppercase text-gray-500">Account Number</p>
                                    <p class="text-sm font-medium mt-1">{{ $account->account_number }}</p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase text-gray-500">Name</p>
                                    <p class="text-sm font-medium mt-1">{{ $account->name }}</p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase text-gray-500">Parent Account</p>
                                    <p class="text-sm font-medium mt-1">
                                        {{ $account->parent?->name ?? '—' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase text-gray-500">Opening Date</p>
                                    <p class="text-sm font-medium mt-1">
                                        {{ dateFormat($account->opening_date) }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase text-gray-500">Created Date</p>
                                    <p class="text-sm font-medium mt-1">
                                        {{ $account->created_at->format('d M Y') }}
                                    </p>
                                </div>

                            </div>
                        </section>

                        <!-- Summary Cards -->
                        <section class="grid md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-md">
                                <p class="text-xs text-gray-600">Associated Accounts</p>
                                <p class="text-2xl font-semibold text-[#0078C8] mt-1">{{ $associatedAccounts }}</p>
                            </div>

                            <div class="bg-green-50 p-4 rounded-md">
                                <p class="text-xs text-gray-600">Total Initial Balance</p>
                                <p class="text-2xl font-semibold text-green-700 mt-1">
                                    {{ number_format($totalInitialBalance, 2) }}
                                </p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-md">
                                <p class="text-xs text-gray-600">Hierarchy Level</p>
                                <p class="text-2xl font-semibold text-gray-700 mt-1">
                                    Level {{ $level }}
                                </p>
                            </div>
                        </section>

                        <!-- Account Hierarchy -->
                        <section>
                            <h3 class="text-gray-800 font-semibold mb-3">Account Hierarchy</h3>

                            <div class="bg-gray-50 rounded-md p-4">
                                <ul class="space-y-2 text-sm">

                                    {{-- ROOT --}}
                                    @php
                                        $root = $account;
                                        while ($root->parent) {
                                            $root = $root->parent;
                                        }
                                    @endphp

                                    <li class="font-semibold text-gray-800">{{ $root->name }}

                                        <!-- Nested tree -->
                                        <ul class="ml-6 mt-2 space-y-1 border-l pl-4">
                                            @include('components.acccount.coa.hierarchy-node', [
                                                'node' => $root,
                                                'current' => $account,
                                            ])
                                        </ul>

                                    </li>

                                </ul>
                            </div>
                        </section>

                        <!-- Recent Balances (Static Placeholder) -->
                        <section>
                            <h3 class="text-gray-800 font-semibold mb-3">Recent Balances</h3>

                            <div class="overflow-x-auto rounded-md">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-left py-2 px-4">Date</th>
                                            <th class="text-left py-2 px-4">Description</th>
                                            <th class="text-left py-2 px-4">Amount</th>
                                            <th class="text-left py-2 px-4">Type</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr>
                                            <td class="py-2 px-4">{{ dateFormat($account->opening_date) }}</td>
                                            <td class="py-2 px-4">Initial Entry</td>
                                            <td class="py-2 px-4 text-green-700">
                                                {{ numberFormat($account->initial_balance) }}
                                            </td>
                                            <td class="py-2 px-4 text-gray-700">Credit</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- Footer -->
                        <div class="flex justify-end gap-2 pt-4">
                            <a href="{{ route('account.organization-settings.account.edit', $account->id) }}"
                                class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded hover:bg-[#0a8de4]">
                                Edit Account
                            </a>

                            <a href="{{ get_index_url('account.organization-settings.account.index') }}"
                                class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm rounded hover:bg-[#e6f4ff]">
                                Back
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
