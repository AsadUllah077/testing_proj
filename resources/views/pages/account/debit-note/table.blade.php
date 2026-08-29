<div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ref / No</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Linked Bill</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Sub-Total</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Tax</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Allocated</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Refunded</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Available</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Usage</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($debitNotes as $dn)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-semibold text-gray-800">{{ $dn->unique_no }}</p>
                        @if ($dn->ref_no)
                            <p class="text-xs text-gray-500">{{ $dn->ref_no }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $dn->vendor?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $dn->bill_unique_no ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $dn->date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($dn->sub_total, 2) }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($dn->tax, 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($dn->total, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-red-700">
                        {{ number_format($dn->allocated_amount ?? 0, 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-indigo-700">
                        {{ number_format($dn->refunded_amount ?? 0, 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-blue-700">
                        {{ number_format($dn->remaining_amount ?? 0, 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase border
                            {{ ($dn->remaining_amount ?? 0) <= 0
                                ? 'bg-green-100 text-green-700 border-green-200'
                                : 'bg-amber-100 text-amber-700 border-amber-200' }}">
                            {{ $dn->usage_status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-center gap-2">
                            @php
                                $hasLaterSettlements = ($dn->refunds_count ?? 0) > 0
                                    || $dn->allocations->count() > 1
                                    || ($dn->allocations->count() === 1
                                        && (int) $dn->allocations->first()->bill_id !== (int) $dn->bill_id);
                            @endphp
                            <a href="{{ route('account.debit-note.show', $dn) }}"
                                class="text-blue-600 hover:underline text-xs font-medium">View</a>
                            @unless ($hasLaterSettlements)
                                <a href="{{ route('account.debit-note.edit', $dn) }}"
                                    class="text-amber-600 hover:underline text-xs font-medium">Edit</a>
                                <form method="POST" action="{{ route('account.debit-note.destroy', $dn) }}"
                                    onsubmit="return confirm('Delete this debit note? Its journal entries will also be removed.')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="text-red-600 hover:underline text-xs font-medium">Delete</button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="px-4 py-10 text-center text-sm text-gray-400">No debit notes found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    <x-data-table-paginatior :paginator="$debitNotes" />
</div>
