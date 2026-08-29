@props(['orders'])
<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-100/50 text-gray-600 border-b border-gray-200">
                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">PO Number</th>
                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Supplier</th>
                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Date</th>
                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Total</th>
                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($orders ?? [] as $order)
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4 text-sm text-[#0078C8] font-medium">
                        <a href="{{ route('logistics.purchase-orders.show', $order->id) }}">{{ $order->po_number }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $order->supplier->company_name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $order->order_date }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 font-bold">{{ $order->currency }}
                        {{ number_format($order->total_amount, 2) }}</td>
                    <td class="px-6 py-4 text-sm">
                        @php
                            $statusClasses = [
                                'Draft' => 'bg-gray-100 text-gray-700',
                                'Sent' => 'bg-blue-100 text-blue-700',
                                'Partially Received' => 'bg-amber-100 text-amber-700',
                                'Received' => 'bg-green-100 text-green-700',
                                'Cancelled' => 'bg-red-100 text-red-700',
                            ];
                            $class = $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span
                            class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $class }}">
                            {{ $order->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div
                            class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('logistics.purchase-orders.show', $order->id) }}"
                                class="p-1.5 hover:bg-gray-100 rounded text-gray-500 hover:text-[#0078C8]"
                                title="View">
                                <i class="fa-solid fa-eye text-sm"></i>
                            </a>
                            <a href="{{ route('logistics.purchase-orders.edit', $order->id) }}"
                                class="p-1.5 hover:bg-gray-100 rounded text-gray-500 hover:text-[#0078C8]"
                                title="Edit">
                                <i class="fa-solid fa-pen-to-square text-sm"></i>
                            </a>
                            <button class="p-1.5 hover:bg-red-50 rounded text-gray-500 hover:text-red-600 delete-btn"
                                data-url="{{ route('logistics.purchase-orders.destroy', $order->id) }}" title="Delete">
                                <i class="fa-solid fa-trash-can text-sm"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fa-solid fa-file-invoice-dollar text-4xl text-gray-200 mb-3"></i>
                            <p class="text-sm font-medium">No purchase orders found</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 px-6 pb-6" id="pagination-wrapper">
    <x-pagiantion-link :items="$orders" />
</div>
