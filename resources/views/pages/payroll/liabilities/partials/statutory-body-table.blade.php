<div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                    Statutory Body / Pay Item
                </th>
                <th class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                    Liability Account
                </th>
                <th class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                    Earliest Due
                </th>
                <th class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                    Total Accrued
                </th>
                <th class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                    Paid
                </th>
                <th class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                    Outstanding
                </th>
                <th class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                    Overdue
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse ($bodies as $body)
                <tr class="hover:bg-gray-50 {{ $body['overdue_count'] > 0 ? 'bg-red-50/40' : '' }}">
                    <td class="px-6 py-4">
                        <div class="text-sm font-semibold text-gray-800">{{ $body['name'] }}</div>
                        <div class="text-[11px] text-gray-500">{{ $body['pay_items'] }}</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">{{ $body['unpaid_count'] }} unpaid of {{ $body['item_count'] }} entries</div>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-600">
                        {{ $body['account'] ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-xs">
                        @if ($body['earliest_due_date'])
                            <div class="text-gray-700">{{ $body['earliest_due_date']->format('d M Y') }}</div>
                            @if ($body['overdue_count'] > 0)
                                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $body['overdue_count'] }} overdue
                                </span>
                            @elseif ($body['due_soon_count'] > 0)
                                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                    <i class="fa-regular fa-clock mr-1"></i> {{ $body['due_soon_count'] }} due soon
                                </span>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-right text-gray-700">
                        {{ numberFormat($body['total_amount']) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-right text-green-700">
                        {{ numberFormat($body['paid_amount']) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-right font-bold {{ $body['outstanding'] > 0 ? 'text-[#0078C8]' : 'text-gray-400' }}">
                        {{ numberFormat($body['outstanding']) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-right font-bold {{ $body['overdue_amount'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ $body['overdue_amount'] > 0 ? numberFormat($body['overdue_amount']) : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                        No payroll liabilities found for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($bodies->isNotEmpty())
            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                <tr class="font-bold">
                    <td class="px-6 py-3 text-sm text-gray-800" colspan="3">GRAND TOTAL</td>
                    <td class="px-6 py-3 text-sm text-right text-gray-800">{{ numberFormat($totals['total_amount']) }}</td>
                    <td class="px-6 py-3 text-sm text-right text-green-700">{{ numberFormat($totals['paid_amount']) }}</td>
                    <td class="px-6 py-3 text-sm text-right text-[#0078C8]">{{ numberFormat($totals['outstanding']) }}</td>
                    <td class="px-6 py-3 text-sm text-right {{ $totals['overdue_amount'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ $totals['overdue_amount'] > 0 ? numberFormat($totals['overdue_amount']) : '—' }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

@if ($bodies instanceof \Illuminate\Pagination\LengthAwarePaginator && $bodies->hasPages())
    <div class="mt-4 bg-white rounded-lg">
        <x-data-table-paginatior :paginator="$bodies" />
    </div>
@endif
