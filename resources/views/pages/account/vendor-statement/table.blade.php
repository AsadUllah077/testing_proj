<table class="w-full text-sm">
    <thead>
        <tr class="bg-gray-50 border-b border-gray-200">
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-10">
                <input type="checkbox" id="select-all-vendor-statements"
                    class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Open Bills</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Outstanding Balance
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($vendors as $vendor)
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3">
                    <input type="checkbox" value="{{ $vendor->id }}" data-label="{{ $vendor->name }}"
                        class="vendor-statement-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800">{{ $vendor->name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $vendor->email ?? '—' }}</td>
                <td class="px-4 py-3 text-center text-gray-700">{{ $vendor->bills_count }}</td>
                <td class="px-4 py-3 text-right font-bold text-red-600">
                    {{ numberFormat($vendor->bills_sum_remaining_amount) }}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('account.vendor-statements.show', $vendor) }}"
                            class="text-xs bg-[#0078C8] text-white px-2.5 py-1 rounded hover:bg-[#078ae2]">View</a>
                        <a href="{{ route('account.vendor-statements.pdf', $vendor) }}"
                            class="text-xs border border-[#0078C8] text-[#0078C8] px-2.5 py-1 rounded hover:bg-blue-50">PDF</a>
                        @if ($vendor->email)
                            <form method="POST" action="{{ route('account.vendor-statements.email', $vendor) }}">
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
                <td colspan="6" class="px-4 py-10 text-center text-gray-400">No vendors with outstanding
                    balances.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<x-data-table-paginatior :paginator="$vendors" />
