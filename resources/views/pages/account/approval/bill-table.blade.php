<table class="w-full text-sm">
    <thead>
        <tr class="bg-gray-50 border-b border-gray-200">
            @if(($status ?? request('status')) === 'submitted' || ($status ?? request('status')) === 'draft')
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-12">
                <input type="checkbox" class="hidden">
            </th>
            @endif
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bill #</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created By</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Risk</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($bills as $bill)
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                @if(($status ?? request('status')) === 'submitted' || ($status ?? request('status')) === 'draft')
                <td class="px-4 py-3">
                    <input type="checkbox" name="bill_ids[]" value="{{ $bill->id }}" class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                </td>
                @endif
                <td class="px-4 py-3">
                    <a href="{{ route('account.bill-invoice.show', $bill->id) }}"
                        class="text-[#0078C8] font-semibold hover:underline">
                        {{ $bill->invoice_no ?: $bill->unique_no }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $bill->vendor->name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ dateFormat($bill->date) }}</td>
                <td class="px-4 py-3 text-right font-semibold">{{ numberFormat($bill->total) }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $bill->creator->first_name . ' ' . $bill->creator->last_name ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    @php $risk = ($riskScores ?? [])[$bill->id] ?? null; @endphp
                    @if ($risk)
                        @php
                            $riskColors = [
                                'low'    => 'bg-green-100 text-green-700',
                                'medium' => 'bg-yellow-100 text-yellow-800',
                                'high'   => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $riskColors[$risk['tier']] ?? 'bg-gray-100 text-gray-600' }}"
                            title="{{ implode(' | ', $risk['reasons']) }}">
                            {{ $risk['tier'] }} ({{ $risk['score'] }})
                        </span>
                        @if ($risk['tier'] === 'high' && !empty($risk['reasons']))
                            <span class="block text-[9px] text-red-500 mt-0.5 truncate max-w-[120px]"
                                title="{{ implode(', ', $risk['reasons']) }}">
                                {{ $risk['reasons'][0] ?? '' }}
                            </span>
                        @endif
                    @else
                        <span class="text-gray-300 text-xs">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @php
                        $isPaid = in_array($bill->payment_status, ['paid', 'overpaid'], true);
                        $badge = match (true) {
                            $isPaid => 'bg-[#0078C8] text-white',
                            $bill->approval_status === 'draft' => 'bg-gray-100 text-gray-600',
                            $bill->approval_status === 'submitted' => 'bg-amber-100 text-amber-700',
                            $bill->approval_status === 'approved' => 'bg-green-100 text-green-700',
                            $bill->approval_status === 'rejected' => 'bg-red-100 text-red-600',
                            default => 'bg-gray-100 text-gray-500',
                        };
                        $statusText = ucfirst($bill->approval_status);
                    @endphp
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">
                        {{ $statusText }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        @if ($bill->approval_status === 'draft' || $bill->approval_status === 'rejected')
                            <form method="POST" action="{{ route('account.approvals.bills.submit', $bill) }}">
                                @csrf
                                <button type="submit"
                                    class="text-xs bg-amber-500 text-white px-2.5 py-1 rounded hover:bg-amber-600">Submit</button>
                            </form>
                        @endif

                        @if ($bill->approval_status === 'submitted')
                            <form method="POST" action="{{ route('account.approvals.bills.approve', $bill) }}">
                                @csrf
                                <button type="submit"
                                    class="text-xs bg-green-600 text-white px-2.5 py-1 rounded hover:bg-green-700">Approve</button>
                            </form>
                            <button type="button" data-id="{{ $bill->id }}"
                                data-url="{{ route('account.approvals.bills.reject', $bill) }}"
                                onclick="openRejectModal(this.dataset.id, this.dataset.url)"
                                class="text-xs bg-red-500 text-white px-2.5 py-1 rounded hover:bg-red-600">Reject</button>
                        @endif

                        @if ($bill->approval_status === 'rejected' && $bill->bill_rejection_reason)
                            <span class="text-xs text-red-500 italic" title="{{ $bill->bill_rejection_reason }}">Reason
                                ⓘ</span>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ (($status ?? request('status')) === 'submitted' || ($status ?? request('status')) === 'draft') ? 9 : 8 }}" class="px-4 py-10 text-center text-gray-400">No bills with status
                    "{{ ucfirst($status) }}".</td>
            </tr>
        @endforelse
    </tbody>
</table>

<x-data-table-paginatior :paginator="$bills" />
