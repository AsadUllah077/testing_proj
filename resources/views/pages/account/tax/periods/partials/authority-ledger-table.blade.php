<!-- Summary Cards -->
<div id="authority-ledger-summary" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
    <!-- Sales Tax Total -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mr-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-5 h-5 text-blue-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 6.75v.75m0 6.75v.75m15-15h.75m-15 15h.75m15-15v.75m0 6.75v.75m0 6.75v.75M6.75 22.5a.75.75 0 010-1.5h10.5a.75.75 0 010 1.5H6.75zM6.75 1.5a.75.75 0 000 1.5h10.5a.75.75 0 000-1.5H6.75z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Sales Tax Total</p>
                <h3 data-summary-key="total_sales_tax" x-data="countUp('{{ $totalSalesTax }}')"
                    x-on:update-value="update($event.detail.value)"
                    class="text-lg font-semibold text-gray-900 tabular-nums" x-text="displayValue">
                    {{ numberFormat($totalSalesTax) }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Purchase Tax Total -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center mr-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-5 h-5 text-green-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v12m-3-2.818l.879.659c1.546 1.16 3.743 1.16 5.289 0m-5.289-8.402l.879.659c1.546 1.16 3.743 1.16 5.289 0m-5.289 4.352c1.724-1.367 3.312-1.367 5.289 0m-5.289-4.352V18.75m5.289-12.75V15.75" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Purchase Tax Total
                </p>
                <h3 data-summary-key="total_purchase_tax" x-data="countUp('{{ $totalPurchaseTax }}')"
                    x-on:update-value="update($event.detail.value)"
                    class="text-lg font-semibold text-gray-900 tabular-nums" x-text="displayValue">
                    {{ numberFormat($totalPurchaseTax) }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Tax Settlements -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-purple-50 rounded-full flex items-center justify-center mr-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-5 h-5 text-purple-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Tax Settlements</p>
                <h3 data-summary-key="total_settlements" x-data="countUp('{{ $totalSettlements }}')"
                    x-on:update-value="update($event.detail.value)"
                    class="text-lg font-semibold text-gray-900 tabular-nums" x-text="displayValue">
                    {{ numberFormat($totalSettlements) }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Tax Payments -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center mr-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-5 h-5 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 8.25H9m6 3H9m3 6l-3-3h1.5a3 3 0 100-6M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Tax Payments</p>
                <h3 data-summary-key="total_payments" x-data="countUp('{{ $totalPayments }}')"
                    x-on:update-value="update($event.detail.value)"
                    class="text-lg font-semibold text-gray-900 tabular-nums" x-text="displayValue">
                    {{ numberFormat($totalPayments) }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Remaining Balance -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center">
            <div
                class="w-10 h-10 {{ $remainingBalance > 0 ? 'bg-orange-50 text-orange-600' : 'bg-blue-50 text-blue-600' }} rounded-full flex items-center justify-center mr-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3v17.25m0 0L8.25 16.5m3.75 3.75L15.75 16.5" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Remaining Balance
                </p>
                <h3 data-summary-key="remaining_balance" x-data="countUp('{{ abs($remainingBalance) }}')"
                    x-on:update-value="update($event.detail.value)"
                    class="text-lg font-bold {{ $remainingBalance > 0 ? 'text-red-600' : 'text-green-600' }} tabular-nums"
                    x-text="displayValue">
                    {{ numberFormat(abs($remainingBalance)) }}
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Tax Periods Table -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-800">Tax Period History</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                        <x-sortable column="name" label="Period" />
                    </th>
                    <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                        <x-sortable column="type" label="Type" />
                    </th>
                    <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                        <x-sortable column="end_date" label="Date Range" />
                    </th>
                    <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                        <x-sortable column="status" label="Status" />
                    </th>
                    <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                        <x-sortable column="total_payable" label="Sales Tax" />
                    </th>
                    <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                        <x-sortable column="total_receivable" label="Purchase Tax" />
                    </th>
                    <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                        <x-sortable column="net_amount" label="Net Amount" />
                    </th>
                    <th class="px-6 py-4 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-center">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($taxPeriods as $period)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <a href="{{ route('account.tax-periods.show', $period->id) }}"
                                class="text-sm font-medium text-[#0078C8] hover:underline">
                                {{ $period->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs text-gray-600">{{ ucfirst($period->type) }}</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-600">
                            {{ $period->start_date->format('M d, Y') }} -
                            {{ $period->end_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($period->status === 'open')
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800">
                                    Open
                                </span>
                            @elseif($period->status === 'settled')
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-yellow-100 text-yellow-800">
                                    Settled
                                </span>
                            @elseif($period->status === 'paid')
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800">
                                    Paid
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if ($period->status !== 'open')
                                {{ numberFormat($period->total_payable) }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if ($period->status !== 'open')
                                {{ numberFormat($period->total_receivable) }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                            @if ($period->status !== 'open')
                                <span class="{{ $period->net_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ numberFormat(abs($period->net_amount)) }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('account.tax-periods.show', $period->id) }}"
                                class="text-xs text-[#0078C8] hover:underline">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                            No tax periods found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-data-table-paginatior :paginator="$taxPeriods" />
</div>
