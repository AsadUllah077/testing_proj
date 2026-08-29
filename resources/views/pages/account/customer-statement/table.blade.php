<table class="w-full text-sm">
    <thead>
        <tr class="bg-gray-50 border-b border-gray-200">
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-10">
                <input type="checkbox" id="select-all-customer-statements"
                    class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Open Invoices</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Outstanding Balance
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($customers as $customer)
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3">
                    <input type="checkbox" value="{{ $customer->id }}" data-label="{{ $customer->name }}"
                        class="customer-statement-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800">{{ $customer->name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $customer->email ?? '—' }}</td>
                <td class="px-4 py-3 text-center text-gray-700">{{ $customer->sales_count }}</td>
                <td class="px-4 py-3 text-right font-bold text-red-600">
                    {{ numberFormat($customer->sales_sum_remaining_amount) }}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('account.customer-statements.show', $customer) }}"
                            class="text-xs bg-[#0078C8] text-white px-2.5 py-1 rounded hover:bg-[#0687dd]">View</a>
                        <a href="{{ route('account.customer-statements.pdf', $customer) }}"
                            class="text-xs border border-[#0078C8] text-[#0078C8] px-2.5 py-1 rounded hover:bg-blue-50">PDF</a>
                        @if ($customer->email)
                            <form method="POST" action="{{ route('account.customer-statements.email', $customer) }}">
                                @csrf
                                <input type="hidden" name="as_of" value="{{ now()->toDateString() }}">
                                <button type="submit"
                                    class="text-xs border border-gray-300 text-gray-600 px-2.5 py-1 rounded hover:bg-gray-50">Email</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-gray-400">No customers with outstanding
                    balances.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<x-data-table-paginatior :paginator="$customers" />
