<!-- Table -->
<div class="mt-4 overflow-x-auto overflow-y-auto max-h-[75vh]">
    <table class="xero-data-table">
        <thead class="sticky top-0 z-10 bg-white">
            <tr>
                <th>Supplier</th>
                @if ($groupBy == 'unique_no')
                    <th>Invoice No</th>
                    @if ($includeStatutory)
                        <th>Account Name</th>
                    @endif
                @endif
                <th>Invoice Date</th>

                @foreach ($labels as $label)
                    <th class="text-right">{{ $label }}</th>
                @endforeach

                <th class="text-right">Total</th>
            </tr>
        </thead>

        <tbody class="text-gray-800">

            @foreach ($reportData as $vendor)
        <tbody x-data="{
            open: true,
            init() {
                document.addEventListener('aging:expand-all', () => this.open = true);
                document.addEventListener('aging:collapse-all', () => this.open = false);
            }
        }"
            class="divide-y divide-gray-100 border-b border-gray-200 last:border-0 hover:divide-y hover:divide-gray-100">
            <!-- Vendor Group -->
            <tr class="xero-group cursor-pointer" @click="open = !open">
                <td colspan="{{ 5 + count($labels) }}">
                    <div class="flex items-center gap-2">
                        <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                        <strong>{{ $vendor['entity_type'] }}:</strong> {{ $vendor['name'] }}
                    </div>
                </td>
            </tr>

            @foreach ($vendor['rows'] as $row)
                @php
                    $clickablePrefixes = ['BILL-', 'SO-', 'TAX-PAY-', 'PR-'];
                    $isClickable =
                        collect($clickablePrefixes)->contains(fn($p) => str_starts_with($row['invoice_no'], $p)) &&
                        !str_contains($row['invoice_no'], 'PR-DED-') &&
                        !str_contains($row['invoice_no'], 'PR-PAY-');
                @endphp
                <tr class="hover:bg-gray-50" x-show="open" x-cloak>
                    <td class="px-5 py-2"></td>
                    @if ($groupBy == 'unique_no')
                        @if ($isClickable)
                            <td class="px-5 py-2 invoice">
                                <span class="invoice-no font-bold"
                                    data-invoice-no="{{ $row['invoice_no'] }}">{{ $row['invoice_no'] }}</span>
                            </td>
                        @else
                            <td class="px-5 py-2 invoice">{{ $row['invoice_no'] }}</td>
                        @endif
                        @if ($includeStatutory)
                            <td class="px-5 py-2">{{ $row['account_name'] }}</td>
                        @endif
                    @endif
                    <td class="px-5 py-2">
                        {{ dateFormat($row['invoice_date']) }}
                    </td>

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

            <!-- Subtotal -->
            <tr class="xero-subtotal" x-show="open" x-cloak>
                @php
                    if ($groupBy == 'unique_no' && $includeStatutory) {
                        $colspan = 4;
                    } else {
                        $colspan = 3;
                    }
                @endphp
                <td colspan="{{ $colspan }}" class="px-5 py-2 text-right">Subtotal -
                    <strong>{{ $vendor['entity_type'] }}:</strong> {{ $vendor['name'] }}
                </td>

                @foreach ($vendor['totals'] as $t)
                    <td class="px-5 py-2 text-right">{{ numberFormat($t) }}</td>
                @endforeach

                <td class="px-5 py-2 text-right font-semibold">
                    {{ numberFormat($vendor['total']) }}
                </td>
            </tr>
        </tbody>
        @endforeach

        <!-- Grand Total -->
        <tr class="xero-total">
            @php
                if ($groupBy == 'unique_no' && $includeStatutory) {
                    $colspan = 4;
                } else {
                    $colspan = 3;
                }
            @endphp
            <td colspan="{{ $colspan }}" class="px-5 py-3 text-right">Grand Total</td>

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
        * Amounts shown in brackets indicate overdue payables. Report generated automatically by the
        ERP system.
    </p>
    <p class="text-xs text-gray-500 font-medium">
        Note: Includes non-trade liabilities (Tax/Pension) as per user configuration.
    </p>
</div>

<x-data-table-paginatior :paginator="$reportData" />
