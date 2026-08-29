<!-- Account Section -->
<section class="space-y-6">

    @forelse ($ledgers as $ledger)
        <div x-data="{
            open: true,
            init() {
                document.addEventListener('ledger:expand-all', () => this.open = true);
                document.addEventListener('ledger:collapse-all', () => this.open = false);
            }
        }" class="border border-gray-200 rounded-lg mb-8">

            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center group cursor-pointer hover:bg-gray-50"
                @click="open = !open">
                <h2 class="font-semibold flex items-center gap-2">
                    <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                    <span class="xero-link">{{ $ledger['account']->name }} ({{ $ledger['account']->account_number }})</span>
                </h2>

                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">
                        Opening Balance:
                        <span class="font-semibold text-gray-900">
                            {{ numberFormat($ledger['opening_balance']) }}
                        </span>
                    </span>
                    <button type="button" onclick="this.closest('.border').remove()"
                        class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition-colors"
                        title="Remove this account from view" @click.stop>
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto overflow-y-auto max-h-[80vh]" x-show="open" x-cloak x-collapse>
                <table class="xero-data-table">
                    <thead class="sticky top-0 z-10 bg-white">
                        <tr>
                            <th class="px-5 py-3" data-col="date">Date</th>
                            <th class="px-5 py-3" data-col="journal_no">Journal No</th>
                            <th class="px-5 py-3" data-col="description">Description</th>
                            <th class="px-5 py-3 text-right" data-col="debit">Debit</th>
                            <th class="px-5 py-3 text-right" data-col="credit">Credit</th>
                            <th class="px-5 py-3 text-right" data-col="running_balance">Running Balance</th>
                            <th class="px-5 py-3 text-right" data-col="gross">Gross</th>
                            <th class="px-5 py-3 text-right" data-col="tax">Tax</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        @forelse ($ledger['rows'] as $row)
                            <tr>
                                <td class="px-5 py-2" data-col="date">{{ dateFormat($row['date']) }}</td>
                                @php
                                    $clickablePrefixes = ['BILL-', 'SO-', 'TAX-PAY-', 'PR-'];
                                    $isClickable =
                                        collect($clickablePrefixes)->contains(
                                            fn($p) => str_starts_with($row['unique_no'], $p),
                                        ) &&
                                        !str_contains($row['unique_no'], 'PR-DED-') &&
                                        !str_contains($row['unique_no'], 'PR-PAY-');
                                @endphp
                                @if ($isClickable)
                                    <td class="px-5 py-2 invoice" data-col="journal_no">
                                        <span class="invoice-no font-bold"
                                            data-invoice-no="{{ $row['unique_no'] }}">{{ $row['journal_no'] }}</span>
                                    </td>
                                @else
                                    <td class="px-5 py-2 invoice" data-col="journal_no">{{ $row['journal_no'] }}</td>
                                @endif
                                <td class="px-5 py-2" data-col="description">{{ $row['description'] }}</td>

                                <td class="px-5 py-2 text-right text-black" data-col="debit">
                                    {{ formatAmount($row['debit']) }}
                                </td>

                                <td class="px-5 py-2 text-right text-black" data-col="credit">
                                    {{ formatAmount($row['credit']) }}
                                </td>

                                <td class="px-5 py-2 text-right font-medium" data-col="running_balance">
                                    {{ formatAmount($row['running_balance']) }}
                                </td>

                                <td class="px-5 py-2 text-right" data-col="gross">
                                    {{ formatAmount($row['gross']) }}
                                </td>

                                <td class="px-5 py-2 text-right text-black" data-col="tax">
                                    {{ formatAmount($row['tax']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-3 text-center text-gray-500" colspan="8">
                                    No transactions found.
                                </td>
                            </tr>
                        @endforelse

                        <!-- Total -->
                        <tr class="xero-page-total">
                            <td colspan="3" class="px-5 py-3 text-right">Total</td>
                            <td class="px-5 py-3 text-right text-black" data-col="debit">
                                {{ formatAmount($ledger['total_debit']) }}
                            </td>
                            <td class="px-5 py-3 text-right text-black" data-col="credit">
                                {{ formatAmount($ledger['total_credit']) }}
                            </td>
                            <td class="px-5 py-3 text-right text-black" data-col="running_balance"></td>
                            <td class="px-5 py-3 text-right text-black" data-col="gross">
                                {{ formatAmount($ledger['total_gross']) }}
                            </td>
                            <td class="px-5 py-3 text-right text-black" data-col="tax">
                                {{ formatAmount($ledger['total_tax']) }}
                            </td>
                        </tr>

                        <!-- Closing Balance -->
                        <tr class="xero-total">
                            <td colspan="5" class="px-5 py-3 text-right">Closing Balance</td>
                            <td class="px-5 py-3 text-right text-black" data-col="running_balance">
                                {{ formatAmount($ledger['closing_balance']) }}
                            </td>
                            <td colspan="2" data-col="gross_tax_spacer"></td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 italic">
            Please select an account or filter and click SEARCH to view the general ledger.
        </p>
    @endforelse


</section>


<!-- Footer Note -->
<p class="text-xs text-gray-500 italic mt-3">
    * The running balance reflects cumulative debits and credits. Generated automatically by the
    ERP system.
</p>

<x-data-table-paginatior :paginator="$ledgers" />
