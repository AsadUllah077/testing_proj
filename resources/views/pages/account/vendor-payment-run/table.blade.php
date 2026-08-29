<table class="w-full text-sm">
    <thead>
        <tr class="bg-gray-50 border-b border-gray-200">
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payment Date</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Account</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Bills</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Amount</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created By</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($runs as $run)
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3">
                    <a href="{{ route('account.vendor-payment-run.show', $run) }}"
                        class="text-[#0078C8] font-semibold hover:underline">{{ $run->run_reference }}</a>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ dateFormat($run->payment_date) }}</td>
                <td class="px-4 py-3 text-gray-800 font-medium">{{ $run->items->first()->vendor->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $run->account->name ?? '—' }}</td>
                <td class="px-4 py-3 text-center text-gray-700">{{ $run->items_count }}</td>
                <td class="px-4 py-3 text-right font-bold text-gray-800">{{ numberFormat($run->total_amount) }}
                </td>
                <td class="px-4 py-3">
                    @php
                        $badge = match ($run->status) {
                            'draft' => 'bg-gray-100 text-gray-600',
                            'confirmed' => 'bg-amber-100 text-amber-700',
                            'processed' => 'bg-green-100 text-green-700',
                            default => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">
                        {{ ucfirst($run->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $run->createdBy->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('account.vendor-payment-run.show', $run) }}"
                            class="text-xs bg-[#0078C8] text-white px-2.5 py-1 rounded hover:bg-[#0786db]">View</a>
                        <a href="{{ route('account.vendor-payment-run.remittance-pdf', $run) }}"
                            class="text-xs border border-gray-300 text-gray-600 px-2.5 py-1 rounded hover:bg-gray-50">PDF</a>
                        @if ($run->status === 'draft')
                            <form method="POST" action="{{ route('account.vendor-payment-run.destroy', $run) }}">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this run?')"
                                    class="text-xs text-red-500 hover:underline">Delete</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">No payment runs found. <a
                        href="{{ route('account.vendor-payment-run.create') }}" class="text-[#0078C8]">Create
                        one</a>.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<x-data-table-paginatior :paginator="$runs" />
