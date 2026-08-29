@extends('layouts.auth')

@section('title', 'Customer Details')

@section('content')
    <div class="mb-8">
        <div class="mx-auto px-3">
            <div class="bg-white p-5 mt-4 flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ get_index_url('account.sale.customers.index') }}"
                        class="p-2 bg-white rounded-lg border border-black/10 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h2 class="text-stone-950 text-2xl font-normal">Customer Details: {{ $customer->name }}</h2>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('account.customer-statements.show', $customer->id) }}"
                        class="px-5 py-2 border border-[#0078C8] text-[#0078C8] rounded-lg hover:bg-blue-50 transition-colors text-sm font-medium">
                        View Statement
                    </a>
                    <button
                        onclick="openCustomerModal('edit', {
                        id: '{{ $customer->id }}',
                        name: '{{ $customer->name }}',
                        email: '{{ $customer->email }}',
                        phone: '{{ $customer->phone }}',
                        address: '{{ $customer->address }}'
                    })"
                        class="px-5 py-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-colors text-sm font-medium">
                        Edit Customer
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Profile Information -->
                <div class="bg-white rounded-xl shadow-sm border border-black/10 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Profile Information
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs text-gray-500 block uppercase tracking-wider">Full Name</label>
                            <p class="text-gray-900 font-medium">{{ $customer->name }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block uppercase tracking-wider">Email Address</label>
                            <p class="text-gray-900 font-medium">{{ $customer->email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block uppercase tracking-wider">Phone Number</label>
                            <p class="text-gray-900 font-medium">{{ $customer->phone ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block uppercase tracking-wider">Address</label>
                            <p class="text-gray-900 font-medium">{{ $customer->address ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="bg-white rounded-xl shadow-sm border border-black/10 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Financial Summary
                    </h3>
                    <div class="space-y-6">
                        <div class="p-4 bg-orange-50 rounded-lg">
                            <label class="text-xs text-orange-600 block uppercase tracking-wider mb-1">Total Due
                                Balance</label>
                            <p class="text-2xl font-bold text-orange-700">
                                {{ number_format($customer->total_remaining_balance, 2) }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 block uppercase tracking-wider">Total Sales</label>
                                <p class="text-lg font-semibold text-gray-900">{{ $customer->sales->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grow bg-white rounded-xl shadow-sm border border-black/10 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Recent Activity
                    </h3>
                    <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2">
                        @forelse($logs as $log)
                            <div class="flex gap-3">
                                <div class="w-2 h-2 rounded-full bg-[#0078C8] mt-2 shrink-0"></div>
                                <div>
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium">{{ $log->user->name }}</span>
                                        {{ $log->event }} this customer
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 italic">No recent activity.</p>
                        @endforelse
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('account.sale.customers.logs', $customer->id) }}"
                            class="text-sm text-[#0078C8] hover:underline font-medium flex items-center gap-1">
                            View All Logs
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sales History -->
            <div class="bg-white rounded-xl shadow-sm border border-black/10 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        Sales History
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sale No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Remaining</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($customer->sales as $sale)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-[#0078C8]">
                                        {{ $sale->unique_no }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ dateFormat($sale->date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        {{ number_format($sale->total, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 font-medium">
                                        {{ number_format($sale->remaining_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($sale->remaining_amount <= 0)
                                            <span
                                                class="px-2.5 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Paid</span>
                                        @elseif($sale->remaining_amount < $sale->total)
                                            <span
                                                class="px-2.5 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Partial</span>
                                        @else
                                            <span
                                                class="px-2.5 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Unpaid</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('account.sale-invoice.edit', $sale->id) }}" target="_blank"
                                            class="text-[#0078C8] hover:text-[#006bb0] font-medium">View Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 italic">
                                        No sales found for this customer.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Customer Modal -->
    <x-acccount.customer.create-customer />
@endsection
