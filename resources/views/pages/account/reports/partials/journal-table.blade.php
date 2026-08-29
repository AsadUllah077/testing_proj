<!-- Table -->
<div class="overflow-x-auto overflow-y-auto max-h-[80vh]">
    <table class="xero-data-table">
        <thead class="sticky top-0 z-10 bg-white">
            <tr>
                <th>Date</th>
                <th>Journal No</th>
                <th>Account</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
            @foreach ($journals as $unique_no => $groups)
                @php
                    $clickablePrefixes = ['BILL-', 'SO-', 'TAX-PAY-', 'PR-'];
                    $isClickable =
                        collect($clickablePrefixes)->contains(fn($p) => str_starts_with($unique_no, $p)) &&
                        !str_contains($unique_no, 'PR-DED-') &&
                        !str_contains($unique_no, 'PR-PAY-');
                @endphp
                @php
                    $allRows = $groups->flatten();
                    $headerDate = $allRows->min('date');
                    $subtotalDebit = $allRows->sum('debit');
                    $subtotalCredit = $allRows->sum('credit');
                    $narration = $allRows->sortBy('date')->first()->description ?? 'No description';
                @endphp

        <tbody x-data="{
            open: true,
            init() {
                document.addEventListener('journal:expand-all', () => this.open = true);
                document.addEventListener('journal:collapse-all', () => this.open = false);
            }
        }" class="bg-white">
            <!-- Journal Header (Xero style) -->
            <tr class="xero-group cursor-pointer" @click="open = !open">
                <td class="whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                        <span>{{ dateFormat($headerDate) }}</span>
                    </div>
                </td>
                @if ($isClickable)
                    <td>
                        <span class="invoice-no"
                            data-invoice-no="{{ $unique_no }}">{{ $unique_no }}</span>
                    </td>
                @else
                    <td>{{ $unique_no }}</td>
                @endif

                {{-- Narration like Xero --}}
                <td class="text-gray-600" colspan="3">
                    <span class="italic">{{ $narration }}</span>
                </td>
            </tr>

            <!-- Line Items (hidden until expanded) -->
            @foreach ($groups as $partyKey => $rows)
                @php
                    $first = $rows->first();

                    if ($first->payable) {
                        $partyName = ucfirst($first->payable_type) . ': ' . $first->payable->display_name;
                    } elseif ($first->customer) {
                        $partyName = 'Customer: ' . $first->customer->name;
                    } else {
                        $partyName = 'General';
                    }
                @endphp

                <!-- Party Header -->
                <tr>
                    <td></td>
                    <td></td>
                    <td class="text-gray-600 italic font-medium" colspan="3">
                        {{ $partyName }}
                    </td>
                </tr>

                {{-- Lines --}}
                @foreach ($rows as $row)
                    <tr x-show="open" x-cloak>
                        <td></td>
                        <td></td>

                        <td style="padding-left: 40px;">
                            {{ $row->account?->name }}
                        </td>

                        <td class="text-right">
                            {{ formatAmount($row->debit) }}
                        </td>

                        <td class="text-right">
                            {{ formatAmount($row->credit) }}
                        </td>
                    </tr>
                @endforeach
            @endforeach

            <!-- Subtotal (only when expanded) -->
            <tr x-show="open" x-cloak class="xero-subtotal">
                <td colspan="3" class="text-right">
                    Subtotal - {{ $unique_no }}
                </td>
                <td class="text-right">
                    {{ numberFormat($subtotalDebit) }}
                </td>
                <td class="text-right">
                    {{ numberFormat($subtotalCredit) }}
                </td>
            </tr>
        </tbody>
        @endforeach

        <!-- Grand Total -->
        <tr class="xero-total">
            <td colspan="3" class="text-right">Grand Total</td>
            <td class="text-right">{{ numberFormat($grandDebit) }}</td>
            <td class="text-right">{{ numberFormat($grandCredit) }}</td>
        </tr>
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$transactions" />
