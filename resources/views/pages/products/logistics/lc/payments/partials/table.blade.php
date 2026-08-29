@php
    $statusConfig = [
        'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
        'completed' => 'bg-green-50 text-green-600 border-green-100',
    ];
@endphp

<div class="overflow-x-auto">
    <table class="w-full min-w-[1500px] text-left border-collapse">
        <thead>
            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Receipt #</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Booking</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Date</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Payment Type</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Details</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Original Amount</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Currency Value</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">PKR Value</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">USD Value</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Status</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Document</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($payments as $payment)
                @php
                    $statusClass = $statusConfig[$payment->status] ?? 'bg-gray-50 text-gray-500 border-gray-200';
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-4 text-sm font-bold text-gray-900">
                        <a href="{{ route('logistics.lc.payments.edit', $payment->id) }}"
                            class="hover:text-[#0078C8] hover:underline">
                            {{ $payment->receipt_no ?: 'Payment #' . $payment->id }}
                        </a>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">
                        {{ $payment->booking?->booking_number ?: '-' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">
                        {{ $payment->date?->format('M d, Y') ?? '-' }}
                    </td>
                    <td class="px-5 py-4">
                        <span
                            class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $payment->payment_type === 'freight_payment' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-purple-50 text-purple-600 border-purple-100' }}">
                            {{ $payment->payment_type_label }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-700 max-w-64 truncate" title="{{ $payment->details }}">
                        {{ $payment->details ?: '-' }}
                    </td>
                    <td class="px-5 py-4 text-sm font-semibold text-gray-800">
                        {{ $payment->amount !== null ? ($payment->currency ?? 'PKR') . ' ' . number_format((float) $payment->amount, 2) : '-' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
                        {{ $payment->hasCurrencyValue() ? 'PKR ' . number_format((float) $payment->currency_value, 6) : '-' }}
                    </td>
                    <td class="px-5 py-4 text-sm font-semibold text-purple-700 whitespace-nowrap">
                        {{ $payment->amountInPkr() !== null ? 'PKR ' . number_format($payment->amountInPkr(), 2) : 'Rate required' }}
                    </td>
                    <td class="px-5 py-4 text-sm font-semibold text-green-700 whitespace-nowrap">
                        {{ $payment->amountInUsd() !== null ? 'USD ' . number_format($payment->amountInUsd(), 2) : 'Rate required' }}
                    </td>
                    <td class="px-5 py-4">
                        @if ($payment->status)
                            <span
                                class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $statusClass }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm">
                        @if ($payment->upload_doc)
                            <a href="{{ asset('storage/' . ltrim($payment->upload_doc, '/')) }}" target="_blank"
                                class="inline-flex items-center gap-1.5 text-[#0078C8] hover:underline">
                                <i class="fa-solid fa-paperclip text-xs"></i>
                                <span class="max-w-32 truncate">{{ $payment->original_filename ?: 'View file' }}</span>
                            </a>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="relative inline-block" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-500 transition-all">
                                <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-100 z-20 overflow-hidden"
                                style="display:none;">
                                <a href="{{ route('logistics.lc.payments.edit', $payment->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-pen w-3.5 text-gray-400"></i> Edit
                                </a>
                                <button
                                    class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-red-600 hover:bg-red-50 transition-colors delete-btn"
                                    data-url="{{ route('logistics.lc.payments.destroy', $payment->id) }}">
                                    <i class="fa-solid fa-trash-can w-3.5"></i> Delete
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="px-5 py-14 text-center">
                        <i class="fa-solid fa-receipt text-gray-200 text-4xl mb-3 block"></i>
                        <p class="text-sm text-gray-400 font-medium">No payments recorded</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your filters</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between gap-4">
    <p class="text-xs text-gray-500">
        Showing <span class="font-semibold text-gray-700">{{ $payments->firstItem() ?? 0 }}</span>-<span
            class="font-semibold text-gray-700">{{ $payments->lastItem() ?? 0 }}</span>
        of <span class="font-semibold text-gray-700">{{ $payments->total() }}</span> records
    </p>
    <div class="text-xs">{{ $payments->links() }}</div>
</div>
