<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left" data-col="bt_date"><x-sortable column="date" label="Date" /></th>
                <th class="px-4 py-3 text-left" data-col="bt_reference_no"><x-sortable column="reference_no"
                        label="Reference No" /></th>
                <th class="px-4 py-3 text-left" data-col="bt_vendor"><x-sortable column="vendor_name" label="Vendor" />
                </th>
                <th class="px-4 py-3 text-left" data-col="bt_product"><x-sortable column="product_name"
                        label="Product" /></th>
                <th class="px-4 py-3 text-left" data-col="bt_account"><x-sortable column="account_id" label="Account" />
                </th>
                <th class="px-4 py-3" data-col="bt_kg"><x-sortable column="kg" label="KG" /></th>
                <th class="px-4 py-3" data-col="bt_quantity"><x-sortable column="quantity" label="Quantity" /></th>
                <th class="px-4 py-3" data-col="bt_price"><x-sortable column="price" label="Price" /></th>
                <th class="px-4 py-3 text-center" data-col="bt_total_amount"><x-sortable column="total"
                        label="Total Amount" /></th>
            </tr>
        </thead>
        <tbody class="min-w-full divide-y divide-gray-200 text-sm">
            @forelse ($bill_transactions as $transaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2" data-col="bt_date">
                        {{ $transaction->bill?->date ? date('d/m/Y', strtotime($transaction->bill?->date)) : 'N/A' }}
                    </td>
                    <td class="px-4 py-2" data-col="bt_reference_no">
                        {{ $transaction->unique_no }}</td>
                    <td class="px-4 py-2" data-col="bt_vendor">
                        {{ $transaction->bill?->vendor?->name ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-2" data-col="bt_product">
                        {{ $transaction->item ?? 'N/A' }}</td>
                    <td class="px-4 py-2" data-col="bt_account">
                        {{ $transaction->account?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2" data-col="bt_kg">
                        {{ numberFormat($transaction->kg) }}</td>
                    <td class="px-4 py-2" data-col="bt_quantity">
                        {{ numberFormat($transaction->quantity) }}</td>
                    <td class="px-4 py-2" data-col="bt_price">
                        {{ numberFormat($transaction->price) }}</td>
                    <td class="px-4 py-2 text-center" data-col="bt_total_amount">
                        <span class="px-5 py-1 rounded-full">
                            {{ numberFormat($transaction->total) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        No transactions found
                    </td>
                </tr>
            @endforelse

            @if ($bill_transactions->count() > 0)
                <tr class="bg-gray-50 font-semibold">
                    <td colspan="5" class="px-4 py-2 text-[#0078C8]">TOTAL:</td>
                    <td class="px-4 py-2 text-[#0078C8]">
                        {{ numberFormat($totals['total_kg'], 2) }}</td>
                    <td colspan="2" class="px-4 py-2 text-[#0078C8]">
                        {{ numberFormat($totals['total_quantity']) }}</td>
                    <td class="px-4 py-2 text-[#0078C8]">
                        {{ numberFormat($totals['total_amount'], 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$bill_transactions" />
