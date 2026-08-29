<!-- Table -->
<div class="mt-4 overflow-x-auto overflow-y-auto max-h-[80vh]">

    <table class="xero-data-table">
        <thead class="sticky top-0 z-10 bg-white">
            <tr class="text-left">
                <th>Account Code</th>
                <th>Account Name</th>
                <th class="text-right">Initial Balance</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right" style="border-left: 1px solid #ddd;">Year Balance</th>

                @if (!empty($comparisonHeaders))
                    @foreach ($comparisonHeaders as $header)
                        <th class="text-right" style="border-left: 1px solid #ddd;"
                            title="{{ $header['from'] }} to {{ $header['to'] }}">
                            {{ $header['label'] }} <br>
                            <span class="text-xs font-normal">Initial</span>
                        </th>
                        <th class="text-right">
                            {{ $header['label'] }} <br>
                            <span class="text-xs font-normal">Debit</span>
                        </th>
                        <th class="text-right">
                            {{ $header['label'] }} <br>
                            <span class="text-xs font-normal">Credit</span>
                        </th>
                        <th class="text-right">
                            {{ $header['label'] }} <br>
                            <span class="text-xs font-normal">Closing</span>
                        </th>
                    @endforeach
                @endif
            </tr>
        </thead>

        @foreach ($trialData as $category => $rows)
            <tbody x-data="{
                open: true,
                init() {
                    document.addEventListener('trial-balance:expand-all', () => this.open = true);
                    document.addEventListener('trial-balance:collapse-all', () => this.open = false);
                }
            }" class="divide-y divide-gray-100 text-gray-800">
                <!-- CATEGORY TITLE -->
                @php
                    $catOpening = collect($rows)->sum('opening');
                    $catDebit = collect($rows)->sum('debit');
                    $catCredit = collect($rows)->sum('credit');
                    $catYearBalance = collect($rows)->sum('year_balance');
                @endphp

                <tr class="xero-group cursor-pointer" @click="open = !open">
                    <!-- Account Code -->
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                            <span>{{ $category }}</span>
                        </div>
                    </td>

                    <!-- Account Name (empty) -->
                    <td></td>

                    <!-- Initial Balance -->
                    <td class="text-right">
                        {{ numberFormat($catOpening) }}
                    </td>

                    <!-- Debit -->
                    <td class="text-right">
                        {{ numberFormat($catDebit) }}
                    </td>

                    <!-- Credit -->
                    <td class="text-right">
                        {{ numberFormat($catCredit) }}
                    </td>

                    <!-- Year Balance -->
                    <td class="text-right font-bold" style="border-left: 1px solid #ddd;">
                        {{ numberFormat($catYearBalance) }}
                    </td>

                    {{-- Comparison placeholders --}}
                    @if (!empty($comparisonHeaders))
                        @foreach ($comparisonHeaders as $header)
                            <td class="text-right" style="border-left: 1px solid #ddd;"></td>
                            <td class="text-right"></td>
                            <td class="text-right"></td>
                            <td class="text-right"></td>
                        @endforeach
                    @endif
                </tr>

                <!-- ACCOUNTS -->
                @foreach ($rows as $row)
                    <tr x-show="open" x-cloak>
                        <td>{{ $row['code'] }}</td>
                        <td class="sub-account">
                            <span class="account-name xero-link"
                                data-account-id="{{ $row['id'] }}">{{ $row['name'] }}</span>
                        </td>

                        <td class="text-right text-gray-700">
                            {{ numberFormat($row['opening']) }}
                        </td>

                        <td class="text-right">
                            {{ numberFormat($row['debit']) }}
                        </td>

                        <td class="text-right">
                            {{ numberFormat($row['credit']) }}
                        </td>

                        <td class="text-right font-semibold" style="border-left: 1px solid #ddd;">
                            {{ numberFormat($row['year_balance']) }}
                        </td>

                        @if (!empty($comparisonHeaders))
                            @if (!empty($row['comparisons']))
                                @foreach ($row['comparisons'] as $comp)
                                    <td class="text-right text-gray-600" style="border-left: 1px solid #ddd;">
                                        {{ numberFormat($comp['opening']) }}
                                    </td>
                                    <td class="text-right text-gray-600">
                                        {{ numberFormat($comp['debit']) }}
                                    </td>
                                    <td class="text-right text-gray-600">
                                        {{ numberFormat($comp['credit']) }}
                                    </td>
                                    <td class="text-right text-gray-600 font-semibold">
                                        {{ numberFormat($comp['year_balance']) }}
                                    </td>
                                @endforeach
                            @else
                                <!-- Fill empty cells if no comparisons for this row -->
                                @foreach ($comparisonHeaders as $header)
                                    <td class="text-right" style="border-left: 1px solid #ddd;">-</td>
                                    <td class="text-right">-</td>
                                    <td class="text-right">-</td>
                                    <td class="text-right">-</td>
                                @endforeach
                            @endif
                        @endif

                    </tr>
                @endforeach
            </tbody>
        @endforeach

        <!-- GRAND TOTAL -->
        <tfoot>
            <tr class="xero-page-total">
                <td colspan="2" class="text-right">Page Total</td>
                <td class="text-right">---</td>
                <td class="text-right">
                    {{ numberFormat($totalDebit) }}
                </td>
                <td class="text-right">
                    {{ numberFormat($totalCredit) }}
                </td>

                <td class="text-right" style="border-left: 1px solid #ddd;">
                    {{ numberFormat($totalDebit - $totalCredit) }}
                </td>

                @if (!empty($comparisonHeaders))
                    @foreach ($comparisonHeaders as $header)
                        <td class="text-right" style="border-left: 1px solid #ddd;">---</td>
                        <td class="text-right">---</td>
                        <td class="text-right">---</td>
                        <td class="text-right">---</td>
                    @endforeach
                @endif

            </tr>
            <tr class="xero-total">
                <td colspan="2" class="text-right">Grand Total (All Accounts)
                </td>
                <td class="text-right">---</td>
                <td class="text-right">
                    {{ numberFormat($allAccountsTotalDebit) }}
                </td>
                <td class="text-right">
                    {{ numberFormat($allAccountsTotalCredit) }}
                </td>
                <td class="text-right" style="border-left: 1px solid #ddd;">
                    {{ numberFormat($allAccountsTotalDebit - $allAccountsTotalCredit) }}
                </td>

                @if (!empty($comparisonHeaders))
                    @foreach ($comparisonHeaders as $header)
                        <td class="text-right" style="border-left: 1px solid #ddd;">---</td>
                        <td class="text-right">---</td>
                        <td class="text-right">---</td>
                        <td class="text-right">---</td>
                    @endforeach
                @endif

            </tr>
        </tfoot>

        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$chartOfAccounts" />
