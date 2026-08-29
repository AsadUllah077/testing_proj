<!-- Vendor Aged Payable Table -->
<div class="mt-4 overflow-x-auto overflow-y-auto max-h-[75vh]">
    <table class="xero-data-table">
        <thead class="sticky top-0 z-10 bg-white">
            <tr>
                <th>Vendor</th>
                <th>Invoice No</th>
                <th>Invoice Date</th>

                @foreach ($labels as $label)
                    <th class="text-right">{{ $label }}</th>
                @endforeach

                <th class="text-right">Total</th>
            </tr>
        </thead>

        <tbody class="text-gray-800">

            @forelse ($reportData as $vendor)
        <tbody x-data="{
            open: true,
            init() {
                document.addEventListener('aging:expand-all', () => this.open = true);
                document.addEventListener('aging:collapse-all', () => this.open = false);
            }
        }"
            class="divide-y divide-gray-100 border-b border-gray-200 last:border-0 hover:divide-y hover:divide-gray-100">
            <!-- Vendor Group Row -->
            <tr class="xero-group cursor-pointer" @click="open = !open">
                <td colspan="{{ 4 + count($labels) }}">
                    <div class="flex items-center gap-2">
                        <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                        <strong>Vendor:</strong> {{ $vendor['name'] }}
                    </div>
                </td>
            </tr>

            @foreach ($vendor['rows'] as $row)
                @php
                    $clickablePrefixes = ['BILL-', 'PO-'];
                    $isClickable = collect($clickablePrefixes)->contains(fn($p) => str_starts_with($row['invoice_no'], $p));
                @endphp
                <tr class="hover:bg-gray-50" x-show="open" x-cloak>
                    <td class="px-5 py-2"></td>
                    @if ($isClickable)
                        <td class="px-5 py-2 invoice">
                            <span class="invoice-no font-bold"
                                data-invoice-no="{{ $row['invoice_no'] }}">{{ $row['invoice_no'] }}</span>
                        </td>
                    @else
                        <td class="px-5 py-2 invoice">{{ $row['invoice_no'] }}</td>
                    @endif
                    <td class="px-5 py-2">{{ dateFormat($row['invoice_date']) }}</td>

                    @foreach ($row['buckets'] as $amount)
                        <td class="px-5 py-2 text-right {{ $amount > 0 ? 'text-green-700' : '' }}">
                            {{ $amount > 0 ? numberFormat($amount) : '—' }}
                        </td>
                    @endforeach

                    <td class="px-5 py-2 text-right font-medium">
                        {{ numberFormat($row['total']) }}
                    </td>
                </tr>
            @endforeach

            <!-- Vendor Subtotal -->
            <tr class="xero-subtotal" x-show="open" x-cloak>
                <td colspan="3" class="px-5 py-2 text-right">
                    Subtotal - <strong>Vendor:</strong> {{ $vendor['name'] }}
                </td>
                @foreach ($vendor['totals'] as $t)
                    <td class="px-5 py-2 text-right">{{ numberFormat($t) }}</td>
                @endforeach
                <td class="px-5 py-2 text-right font-semibold">
                    {{ numberFormat($vendor['total']) }}
                </td>
            </tr>
        </tbody>
        @empty
            <tr>
                <td colspan="{{ 4 + count($labels) }}" class="px-5 py-8 text-center text-gray-400">
                    No outstanding vendor balances as of this date.
                </td>
            </tr>
        @endforelse

        <!-- Grand Total -->
        <tr class="xero-total">
            <td colspan="3" class="px-5 py-3 text-right">Grand Total</td>
            @foreach ($grandTotals as $gt)
                <td class="px-5 py-3 text-right">{{ numberFormat($gt) }}</td>
            @endforeach
            <td class="px-5 py-3 text-right font-semibold">
                {{ numberFormat($grandTotal) }}
            </td>
        </tr>

        </tbody>
    </table>
</div>

<!-- Footer Note -->
<div class="mt-3 space-y-1">
    <p class="text-xs text-gray-500 italic">
        * This report shows trade vendor balances only. Tax liabilities and payroll liabilities are tracked separately.
    </p>
</div>

<x-data-table-paginatior :paginator="$reportData" />
