@forelse($taxPeriods as $period)
    <tr class="hover:bg-gray-50 transition-colors {{ $period->is_overdue ? 'bg-red-50/40' : '' }}">
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
            {{ $period->start_date->format('M d, Y') }}
        </td>
        <td class="px-6 py-4 text-xs text-gray-600">
            {{ $period->end_date->format('M d, Y') }}
        </td>
        <td class="px-6 py-4 text-xs">
            @if ($period->due_date)
                <div class="text-gray-700">{{ $period->due_date->format('M d, Y') }}</div>
                @if ($period->is_overdue)
                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $period->days_overdue }}d overdue
                    </span>
                @elseif ($period->is_due_soon)
                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                        <i class="fa-regular fa-clock mr-1"></i> Due soon
                    </span>
                @endif
            @else
                <span class="text-gray-400">—</span>
            @endif
        </td>
        <td class="px-6 py-4">
            @if ($period->status === 'open')
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800">
                    Open
                </span>
            @elseif($period->status === 'settled')
                @if ($period->paid_amount > 0 && $period->paid_amount < abs($period->net_amount))
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-orange-100 text-orange-800">
                        Settled (Partial)
                    </span>
                @else
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-yellow-100 text-yellow-800">
                        Settled
                    </span>
                @endif
            @elseif($period->status === 'paid')
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800">
                    Paid
                </span>
            @endif
        </td>
        <td class="px-6 py-4 text-sm text-gray-900 font-medium">
            <span class="{{ $period->net_amount > 0 ? 'text-red-600' : ($period->net_amount < 0 ? 'text-green-600' : 'text-gray-600') }}">
                {{ numberFormat(abs($period->net_amount)) }}
            </span>
        </td>
        <td class="px-6 py-4 text-right">
            <div class="flex justify-end gap-2">
                <a href="{{ route('account.tax-periods.show', $period->id) }}"
                    class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 transition-colors text-gray-700">
                    View
                </a>
                @if ($period->status === 'open')
                    <form action="{{ route('account.tax-periods.destroy', $period->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this open period?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-3 py-1 text-xs border border-red-300 text-red-600 rounded hover:bg-red-50 transition-colors">
                            Delete
                        </button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
            No tax periods found. Click "Create Tax Period" to get started.
        </td>
    </tr>
@endforelse
