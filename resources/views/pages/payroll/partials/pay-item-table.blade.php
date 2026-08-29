<div class="bg-white rounded-md border border-black/10 mt-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable column="name"
                            label="Name" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable column="type"
                            label="Type" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable
                            column="calculation_type" label="Calculation" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable
                            column="is_taxable" label="Income Tax" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable
                            column="account_id" label="Account" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider text-right">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($payItems as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap capitalize">{{ $item->type }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($item->calculation_type === 'percentage')
                                {{ $item->percentage_value }}% of {{ $item->baseItem->name ?? 'N/A' }}
                            @else
                                Fixed Amount
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if (in_array($item->type, ['earning', 'reimbursement'], true))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item->is_taxable ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $item->is_taxable ? 'Taxable' : 'Exempt' }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->account->name ?? 'Not Linked' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                            <button onclick="editItem({{ json_encode($item) }})"
                                class="text-[#0078C8] hover:underline">Edit</button>
                            <button onclick="deleteItem({{ $item->id }})"
                                class="text-red-500 hover:underline">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <x-data-table-paginatior :paginator="$payItems" />
    </div>
</div>
