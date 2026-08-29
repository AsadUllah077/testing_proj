<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-100/50 text-gray-600 border-b border-gray-200">
                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Reference</th>
                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Charge Type</th>
                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Bank</th>
                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Linked LC / Ref</th>
                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Amount (PKR)</th>
                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Date</th>
                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @php
                $rows = [
                    ['id' => 1, 'ref' => 'BCH-2026-001', 'type' => 'LC Opening Commission',  'bank' => 'HBL',               'linked' => 'LC-2026-0041', 'amount' => 'PKR 185,000', 'date' => 'Apr 02, 2026'],
                    ['id' => 2, 'ref' => 'BCH-2026-002', 'type' => 'SWIFT / Telex Charges',  'bank' => 'HBL',               'linked' => 'LC-2026-0041', 'amount' => 'PKR 12,500',  'date' => 'Apr 02, 2026'],
                    ['id' => 3, 'ref' => 'BCH-2026-003', 'type' => 'LC Opening Commission',  'bank' => 'MCB Bank',          'linked' => 'LC-2026-0039', 'amount' => 'PKR 92,000',  'date' => 'Mar 28, 2026'],
                    ['id' => 4, 'ref' => 'BCH-2026-004', 'type' => 'LC Amendment Fee',       'bank' => 'MCB Bank',          'linked' => 'LC-2026-0039', 'amount' => 'PKR 45,000',  'date' => 'Apr 05, 2026'],
                    ['id' => 5, 'ref' => 'BCH-2026-005', 'type' => 'Acceptance Commission',  'bank' => 'UBL Trade',         'linked' => 'LC-2026-0037', 'amount' => 'PKR 210,000', 'date' => 'Apr 08, 2026'],
                    ['id' => 6, 'ref' => 'BCH-2026-006', 'type' => 'Discrepancy Fee',        'bank' => 'Standard Chartered','linked' => 'LC-2026-0033', 'amount' => 'PKR 38,000',  'date' => 'Apr 10, 2026'],
                ];
            @endphp

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-4 text-sm font-semibold text-gray-800">{{ $row['ref'] }}</td>
                    <td class="px-5 py-4 text-sm text-[#0078C8]">{{ $row['type'] }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $row['bank'] }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $row['linked'] }}</td>
                    <td class="px-5 py-4 text-sm font-semibold text-gray-800">{{ $row['amount'] }}</td>
                    <td class="px-5 py-4 text-sm text-gray-500">{{ $row['date'] }}</td>
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
                                <a href="{{ route('logistics.gd.bank-charges.show', $row['id']) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-eye w-3.5 text-gray-400"></i> View
                                </a>
                                <a href="{{ route('logistics.gd.bank-charges.edit', $row['id']) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-pen w-3.5 text-gray-400"></i> Edit
                                </a>
                                <button class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-red-600 hover:bg-red-50 transition-colors delete-btn"
                                    data-url="{{ route('logistics.gd.bank-charges.destroy', $row['id']) }}">
                                    <i class="fa-solid fa-trash-can w-3.5"></i> Delete
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>
</div>

<div class="px-5 py-4 border-t border-gray-100">
    <p class="text-xs text-gray-500">Showing <span class="font-semibold text-gray-700">6</span> records</p>
</div>
