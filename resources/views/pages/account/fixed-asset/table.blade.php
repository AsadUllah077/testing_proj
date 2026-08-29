<div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Asset</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Category</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Cost</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acc. Dep.</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Book Value</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Monthly Dep.</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($assets as $asset)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-semibold text-gray-800">{{ $asset->name }}</p>
                        <p class="text-xs text-gray-400">{{ $asset->asset_number }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $asset->category ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $asset->depreciation_method === 'declining_balance' ? 'Declining' : 'Straight-Line' }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($asset->purchase_cost, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-500">
                        {{ number_format($asset->accumulated_depreciation, 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-blue-600">
                        {{ number_format($asset->current_book_value, 2) }}</td>
                    <td class="px-4 py-3 text-right text-xs text-gray-600">
                        {{ $asset->status === 'active' ? number_format($asset->monthlyDepreciation(), 2) : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @php $color = $asset->status_color; @endphp
                        <span
                            class="px-2 py-0.5 rounded text-xs font-semibold
                            bg-{{ $color }}-100 text-{{ $color }}-700">
                            {{ ucfirst(str_replace('_', ' ', $asset->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('account.fixed-asset.show', $asset) }}"
                                class="text-blue-600 hover:underline text-xs font-medium">View</a>
                            <a href="{{ route('account.fixed-asset.edit', $asset) }}"
                                class="text-amber-600 hover:underline text-xs font-medium">Edit</a>
                            <form method="POST" action="{{ route('account.fixed-asset.destroy', $asset) }}"
                                onsubmit="return confirm('Remove this asset from the register?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="text-red-600 hover:underline text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-400">No assets in the
                        register.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <x-data-table-paginatior :paginator="$assets" />
</div>
