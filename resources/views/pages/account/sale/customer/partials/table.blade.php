<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"
                        class="w-4 h-4 text-[#0078C8] border-gray-300 rounded focus:ring-[#0078C8]">
                </th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="cs_no"><x-sortable column="cs_no"
                        label="S.No" /></th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="c_name"><x-sortable column="c_name"
                        label="Name" /></th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="c_email"><x-sortable
                        column="c_email" label="Email" /></th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="c_phone"><x-sortable
                        column="c_phone" label="Phone" /></th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="c_address">
                    <x-sortable column="c_address" label="Address" />
                </th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="c_due_balance">
                    <x-sortable column="c_due_balance" label="Due Balance" />
                </th>
                <th class="px-4 py-3 text-right font-medium text-gray-700">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($customers as $index => $customer)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <input type="checkbox"
                            class="customer-checkbox w-4 h-4 text-[#0078C8] border-gray-300 rounded focus:ring-[#0078C8]"
                            value="{{ $customer->id }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-4 py-3" data-col="cs_no">
                        {{ $customers->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900" data-col="c_name">
                        {{ $customer->name }}</td>
                    <td class="px-4 py-3 text-gray-600" data-col="c_email">{{ $customer->email ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600" data-col="c_phone">
                        {{ $customer->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600" data-col="c_address">
                        {{ $customer->address ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-900" data-col="c_due_balance">
                        {{ number_format($customer->sales_sum_remaining_amount, 2) }}</td>
                    <td class="px-4 py-3 text-right relative">
                        <button class="text-gray-500 hover:text-gray-800 dropdown-btn p-1 rounded hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div
                            class="dropdown-menu hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                            <button
                                onclick="openCustomerModal('edit', {
                                    id: '{{ $customer->id }}',
                                    name: '{{ $customer->name }}',
                                    email: '{{ $customer->email }}',
                                    phone: '{{ $customer->phone }}',
                                    address: '{{ $customer->address }}'
                                })"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-sm text-gray-700">
                                <svg class="w-4 h-4 text-[#0078C8]" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </button>

                            <button
                                onclick="window.location.href='{{ route('account.sale.customers.show', $customer->id) }}'"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-sm text-gray-700">
                                <svg class="w-4 h-4 text-[#0078C8]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View
                            </button>

                            <button
                                onclick="window.location.href='{{ route('account.customer-statements.show', $customer->id) }}'"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-sm text-gray-700">
                                <svg class="w-4 h-4 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Statement
                            </button>

                            <form action="{{ route('account.sale.customers.destroy', $customer->id) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-sm text-red-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Delete
                                </button>
                            </form>

                            <button
                                onclick="window.location.href='{{ route('account.sale.customers.logs', $customer->id) }}'"
                                class="flex items-center w-full gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="h-4 w-4 text-[#0078C8]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2h6v2m3 4H6a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v13a2 2 0 01-2 2z" />
                                </svg>
                                Logs
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        No customers found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$customers" />
