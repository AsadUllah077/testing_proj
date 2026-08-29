<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Charge #</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Charge Type</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Bank</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Bank Instrument</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Amount</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Date</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Payment Mode</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($charges as $charge)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-4 text-sm font-bold text-gray-900">
                        <a href="{{ route('logistics.lc.bank-charges.show', $charge->id) }}" class="hover:text-[#0078C8] hover:underline">
                            {{ $charge->charge_number }}
                        </a>
                    </td>
                    <td class="px-5 py-4 text-sm text-[#0078C8]">{{ $charge->charge_type_label ?: '—' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $charge->bank_name ?: '—' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $charge->linked_lc ?: '—' }}</td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <p class="text-sm font-semibold text-gray-800">PKR {{ number_format($charge->amount_pkr, 2) }}</p>
                        <p class="text-xs font-medium text-green-600 mt-0.5">USD {{ number_format($charge->amount_usd, 2) }}</p>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">{{ $charge->charge_date?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ ucwords(str_replace('_', ' ', $charge->payment_mode ?? '—')) }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="relative inline-block" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-500 transition-all">
                                <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                            </button>
                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-100 z-20 overflow-hidden"
                                style="display:none;">
                                <a href="{{ route('logistics.lc.bank-charges.show', $charge->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-eye w-3.5 text-gray-400"></i> View
                                </a>
                                <a href="{{ route('logistics.lc.bank-charges.edit', $charge->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-pen w-3.5 text-gray-400"></i> Edit
                                </a>
                                <button class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-red-600 hover:bg-red-50 transition-colors delete-btn"
                                    data-url="{{ route('logistics.lc.bank-charges.destroy', $charge->id) }}">
                                    <i class="fa-solid fa-trash-can w-3.5"></i> Delete
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-14 text-center">
                        <i class="fa-solid fa-building-columns text-gray-200 text-4xl mb-3 block"></i>
                        <p class="text-sm text-gray-400 font-medium">No charges recorded</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your filters</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between gap-4">
    <p class="text-xs text-gray-500">
        Showing <span class="font-semibold text-gray-700">{{ $charges->firstItem() ?? 0 }}</span>–<span class="font-semibold text-gray-700">{{ $charges->lastItem() ?? 0 }}</span>
        of <span class="font-semibold text-gray-700">{{ $charges->total() }}</span> records
    </p>
    <div class="text-xs">{{ $charges->links() }}</div>
</div>
