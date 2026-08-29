<!-- Customer Aged Receivable Table -->
<div class="mt-4 overflow-x-auto overflow-y-auto max-h-[75vh]">
    <table class="xero-data-table">
        <thead class="sticky top-0 z-10 bg-white">
            <tr>
                <th>Customer</th>
                <th>Invoice No</th>
                <th>Invoice Date</th>

                @foreach ($labels as $label)
                    <th class="text-right">{{ $label }}</th>
                @endforeach

                <th class="text-right">Total</th>
                <th class="text-center" title="Predicted payment date based on historical patterns">Pred. Pay Date</th>
                <th class="text-center" title="Customer payment risk score">Risk</th>
            </tr>
        </thead>

        <tbody class="text-gray-800">

            @forelse ($reportData as $customer)
        <tbody x-data="{
            open: true,
            init() {
                document.addEventListener('aging:expand-all', () => this.open = true);
                document.addEventListener('aging:collapse-all', () => this.open = false);
            }
        }"
            class="divide-y divide-gray-100 border-b border-gray-200 last:border-0 hover:divide-y hover:divide-gray-100">
            @php
                $custPrediction = $predictions[$customer['name']] ?? null;
                $custId = $custPrediction['customer_id'] ?? null;
            @endphp
            <!-- Customer Group Row -->
            <tr class="xero-group cursor-pointer" @click="open = !open">
                <td colspan="{{ 4 + count($labels) }}">
                    <div class="flex items-center gap-2">
                        <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                        <strong>Customer:</strong> {{ $customer['name'] }}
                        @if ($custPrediction && $custPrediction['risk_label'] !== 'no_data')
                            <span class="ml-2 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded
                                {{ $custPrediction['risk_label'] === 'high' ? 'bg-red-100 text-red-700' : ($custPrediction['risk_label'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700') }}"
                                title="Avg {{ $custPrediction['avg_days_to_pay'] }} days to pay, {{ $custPrediction['payment_count'] }} payments analyzed">
                                {{ ucfirst($custPrediction['risk_label']) }} Risk ({{ $custPrediction['risk_score'] }})
                            </span>
                            <span class="text-[10px] text-gray-400 ml-1">
                                Avg {{ $custPrediction['avg_days_to_pay'] }}d | {{ $custPrediction['payment_count'] }} payments
                            </span>
                        @endif
                    </div>
                </td>
                <td colspan="2"></td>
            </tr>

            @foreach ($customer['rows'] as $row)
                @php
                    $clickablePrefixes = ['SO-', 'INV-'];
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
                    <td class="px-5 py-2 text-center text-xs">
                        @if ($custPrediction && $custPrediction['avg_days_to_pay'] !== null && isset($behaviorService))
                            @php
                                $predDate = $behaviorService->predictInvoicePayDate($row['invoice_date'], $custId, $predictionsById ?? []);
                                $isPast = $predDate && \Carbon\Carbon::parse($predDate)->lt(now());
                            @endphp
                            @if ($predDate)
                                <span class="{{ $isPast ? 'text-red-600 font-semibold' : 'text-gray-600' }}"
                                    title="Based on avg {{ $custPrediction['avg_days_to_pay'] }} days to pay">
                                    {{ dateFormat($predDate) }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-2 text-center">
                        @if ($custPrediction && $custPrediction['risk_label'] !== 'no_data')
                            <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded
                                {{ $custPrediction['risk_label'] === 'high' ? 'bg-red-100 text-red-700' : ($custPrediction['risk_label'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700') }}">
                                {{ $custPrediction['risk_score'] }}
                            </span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach

            <!-- Customer Subtotal -->
            <tr class="xero-subtotal" x-show="open" x-cloak>
                <td colspan="3" class="px-5 py-2 text-right">
                    Subtotal - <strong>Customer:</strong> {{ $customer['name'] }}
                </td>
                @foreach ($customer['totals'] as $t)
                    <td class="px-5 py-2 text-right">{{ numberFormat($t) }}</td>
                @endforeach
                <td class="px-5 py-2 text-right font-semibold">
                    {{ numberFormat($customer['total']) }}
                </td>
                <td colspan="2"></td>
            </tr>
        </tbody>
        @empty
            <tr>
                <td colspan="{{ 6 + count($labels) }}" class="px-5 py-8 text-center text-gray-400">
                    No outstanding customer balances as of this date.
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
            <td colspan="2"></td>
        </tr>

        </tbody>
    </table>
</div>

<!-- Footer Note -->
<div class="mt-3 space-y-1">
    <p class="text-xs text-gray-500 italic">
        * This report shows trade customer balances only. Statutory/tax receivables are tracked separately.
    </p>
</div>

<x-data-table-paginatior :paginator="$reportData" />
