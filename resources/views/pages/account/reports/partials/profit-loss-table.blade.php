@php
    $showVarianceAmount = request('show_variance_amount', false);
    $showVariancePercentage = request('show_variance_percentage', false);
    $periods = $profitLoss['periods'];
    $numCols = count($periods);
    if ($showVarianceAmount) {
        $numCols += count($periods) - 1;
    }
    if ($showVariancePercentage) {
        $numCols += count($periods) - 1;
    }
    $totalCols = $numCols + 1; // +1 for Account column
@endphp

<!-- Profit & Loss Table -->
<div class="mt-4 overflow-x-auto overflow-y-auto max-h-[80vh]">
    <table class="xero-table">
        <thead class="sticky top-0 z-10 bg-white">
            <tr>
                <th>Account</th>
                @foreach ($periods as $index => $period)
                    <th class="amount">{{ $period['label'] }}</th>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <th class="amount">Variance</th>
                        @endif
                        @if ($showVariancePercentage)
                            <th class="amount">Variance %</th>
                        @endif
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            <!-- REVENUE -->
            <tr class="xero-section-header">
                <td colspan="{{ $totalCols }}">REVENUE</td>
            </tr>
        </tbody>

        @foreach ($profitLoss['revenue']['groups'] as $group)
            <tbody x-data="{
                open: true,
                init() {
                    document.addEventListener('profit-loss:expand-all', () => this.open = true);
                    document.addEventListener('profit-loss:collapse-all', () => this.open = false);
                }
            }">
                <tr class="xero-group-header cursor-pointer" @click="open = !open">
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                            <span>{{ $group['group']->name }}</span>
                        </div>
                    </td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr x-show="open" x-cloak>
                        <td class="xero-sub-account xero-link account-name" data-account-id="{{ $account['account']->id }}">
                            {{ $account['account']->name }}
                        </td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td
                                        class="amount {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : '') }}">
                                        {{ numberFormat($account['variance_' . $index]) }}
                                    </td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td
                                        class="amount {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : '') }}">
                                        {{ numberFormat($account['variance_pct_' . $index]) }}%
                                    </td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        @endforeach

        <tbody>
            @foreach ($profitLoss['revenue']['accounts'] as $account)
                <tr>
                    <td class="xero-link account-name" data-account-id="{{ $account['account']->id }}">
                        {{ $account['account']->name }}
                    </td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td
                                    class="amount {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : '') }}">
                                    {{ numberFormat($account['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td
                                    class="amount {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : '') }}">
                                    {{ numberFormat($account['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <tr class="xero-sub-total">
                <td><strong>Total Revenue</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['total_revenue'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_revenue']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_revenue']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- COST OF GOODS SOLD -->
            @if (!empty($profitLoss['cogs']['accounts']) || !empty($profitLoss['cogs']['groups']))
                <tr class="xero-section-header">
                    <td colspan="{{ $totalCols }}">COST OF GOODS SOLD</td>
                </tr>
            @endif
        </tbody>

        @foreach ($profitLoss['cogs']['groups'] as $group)
            <tbody x-data="{
                open: true,
                init() {
                    document.addEventListener('profit-loss:expand-all', () => this.open = true);
                    document.addEventListener('profit-loss:collapse-all', () => this.open = false);
                }
            }">
                <tr class="xero-group-header cursor-pointer" @click="open = !open">
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                            <span>{{ $group['group']->name }}</span>
                        </div>
                    </td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr x-show="open" x-cloak>
                        <td class="xero-sub-account xero-link account-name" data-account-id="{{ $account['account']->id }}">
                            {{ $account['account']->name }}
                        </td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        @endforeach

        <tbody>
            @foreach ($profitLoss['cogs']['accounts'] as $account)
                <tr>
                    <td class="xero-link account-name" data-account-id="{{ $account['account']->id }}">
                        {{ $account['account']->name }}
                    </td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <tr class="xero-sub-total">
                <td><strong>Total COGS</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['total_cogs'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_cogs']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_cogs']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>

        <tbody>
            <!-- GROSS PROFIT -->
            <tr class="xero-section-total">
                <td><strong>GROSS PROFIT</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['gross_profit'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['gross_profit']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['gross_profit']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- EXPENSES -->
            <tr class="xero-section-header">
                <td colspan="{{ $totalCols }}">EXPENSES</td>
            </tr>
        </tbody>

        @foreach ($profitLoss['expenses']['groups'] as $group)
            <tbody x-data="{
                open: true,
                init() {
                    document.addEventListener('profit-loss:expand-all', () => this.open = true);
                    document.addEventListener('profit-loss:collapse-all', () => this.open = false);
                }
            }">
                <tr class="xero-group-header cursor-pointer" @click="open = !open">
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                            <span>{{ $group['group']->name }}</span>
                        </div>
                    </td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr x-show="open" x-cloak>
                        <td class="xero-sub-account xero-link account-name" data-account-id="{{ $account['account']->id }}">
                            {{ $account['account']->name }}
                        </td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        @endforeach

        <tbody>
            @foreach ($profitLoss['expenses']['accounts'] as $account)
                <tr>
                    <td class="xero-link account-name" data-account-id="{{ $account['account']->id }}">
                        {{ $account['account']->name }}
                    </td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <tr class="xero-sub-total">
                <td><strong>Total Expenses</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['total_expenses'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_expenses']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['total_expenses']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- OPERATING PROFIT -->
            <tr class="xero-section-total">
                <td><strong>OPERATING PROFIT</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['operating_profit'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['operating_profit']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['operating_profit']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>

        <!-- OTHER INCOME -->
        @if (!empty($profitLoss['other_income']['accounts']) || !empty($profitLoss['other_income']['groups']))
            <tbody>
                <tr class="xero-section-header">
                    <td colspan="{{ $totalCols }}">OTHER INCOME</td>
                </tr>
            </tbody>

            @foreach ($profitLoss['other_income']['groups'] as $group)
                <tbody x-data="{
                    open: true,
                    init() {
                        document.addEventListener('profit-loss:expand-all', () => this.open = true);
                        document.addEventListener('profit-loss:collapse-all', () => this.open = false);
                    }
                }">
                    <tr class="xero-group-header cursor-pointer" @click="open = !open">
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                                <span>{{ $group['group']->name }}</span>
                            </div>
                        </td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                    @foreach ($group['accounts'] as $account)
                        <tr x-show="open" x-cloak>
                            <td class="xero-sub-account xero-link account-name" data-account-id="{{ $account['account']->id }}">
                                {{ $account['account']->name }}
                            </td>
                            @foreach ($periods as $index => $period)
                                @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                                <td class="amount">{{ numberFormat($account[$key]) }}</td>
                                @if ($index > 0)
                                    @if ($showVarianceAmount)
                                        <td class="amount {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : '') }}">
                                            {{ numberFormat($account['variance_' . $index]) }}
                                        </td>
                                    @endif
                                    @if ($showVariancePercentage)
                                        <td class="amount {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : '') }}">
                                            {{ numberFormat($account['variance_pct_' . $index]) }}%
                                        </td>
                                    @endif
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            @endforeach

            <tbody>
                @foreach ($profitLoss['other_income']['accounts'] as $account)
                    <tr>
                        <td class="xero-link account-name" data-account-id="{{ $account['account']->id }}">
                            {{ $account['account']->name }}
                        </td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : '') }}">
                                        {{ numberFormat($account['variance_' . $index]) }}
                                    </td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : '') }}">
                                        {{ numberFormat($account['variance_pct_' . $index]) }}%
                                    </td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach

                <tr class="xero-sub-total">
                    <td><strong>Total Other Income</strong></td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount"><strong>{{ numberFormat($profitLoss['total_other_income'][$key]) }}</strong></td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount">
                                    <strong>{{ numberFormat($profitLoss['total_other_income']['variance_' . $index]) }}</strong>
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount">
                                    <strong>{{ numberFormat($profitLoss['total_other_income']['variance_pct_' . $index]) }}%</strong>
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            </tbody>
        @endif

        <!-- FINANCE COSTS -->
        @if (!empty($profitLoss['finance_costs']['accounts']) || !empty($profitLoss['finance_costs']['groups']))
            <tbody>
                <tr class="xero-section-header">
                    <td colspan="{{ $totalCols }}">FINANCE COSTS</td>
                </tr>
            </tbody>

            @foreach ($profitLoss['finance_costs']['groups'] ?? [] as $group)
                <tbody x-data="{ open: true, init() { document.addEventListener('profit-loss:expand-all', () => this.open = true); document.addEventListener('profit-loss:collapse-all', () => this.open = false); } }">
                    <tr class="xero-group-header cursor-pointer" @click="open = !open">
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="inline-block transition-transform text-xs text-gray-400" :class="open ? 'rotate-90' : ''">&#9654;</span>
                                <span>{{ $group['group']->name }}</span>
                            </div>
                        </td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($group['total'][$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount) <td class="amount">{{ numberFormat($group['total']['variance_' . $index]) }}</td> @endif
                                @if ($showVariancePercentage) <td class="amount">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td> @endif
                            @endif
                        @endforeach
                    </tr>
                    @foreach ($group['accounts'] as $account)
                        <tr x-show="open" x-cloak>
                            <td class="xero-sub-account xero-link account-name" data-account-id="{{ $account['account']->id }}">{{ $account['account']->name }}</td>
                            @foreach ($periods as $index => $period)
                                @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                                <td class="amount">{{ numberFormat($account[$key]) }}</td>
                                @if ($index > 0)
                                    @if ($showVarianceAmount) <td class="amount {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : '') }}">{{ numberFormat($account['variance_' . $index]) }}</td> @endif
                                    @if ($showVariancePercentage) <td class="amount {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : '') }}">{{ numberFormat($account['variance_pct_' . $index]) }}%</td> @endif
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            @endforeach

            <tbody>
                @foreach ($profitLoss['finance_costs']['accounts'] ?? [] as $account)
                    <tr>
                        <td class="xero-link account-name" data-account-id="{{ $account['account']->id }}">{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount) <td class="amount {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : '') }}">{{ numberFormat($account['variance_' . $index]) }}</td> @endif
                                @if ($showVariancePercentage) <td class="amount {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : '') }}">{{ numberFormat($account['variance_pct_' . $index]) }}%</td> @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach

                <tr class="xero-sub-total">
                    <td><strong>Total Finance Costs</strong></td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount"><strong>{{ numberFormat($profitLoss['total_finance_costs'][$key]) }}</strong></td>
                        @if ($index > 0)
                            @if ($showVarianceAmount) <td class="amount"><strong>{{ numberFormat($profitLoss['total_finance_costs']['variance_' . $index]) }}</strong></td> @endif
                            @if ($showVariancePercentage) <td class="amount"><strong>{{ numberFormat($profitLoss['total_finance_costs']['variance_pct_' . $index]) }}%</strong></td> @endif
                        @endif
                    @endforeach
                </tr>
            </tbody>
        @endif

        <!-- PROFIT BEFORE TAX -->
        <tbody>
            <tr class="xero-section-total">
                <td><strong>PROFIT BEFORE TAX</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['profit_before_tax'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount) <td class="amount"><strong>{{ numberFormat($profitLoss['profit_before_tax']['variance_' . $index]) }}</strong></td> @endif
                        @if ($showVariancePercentage) <td class="amount"><strong>{{ numberFormat($profitLoss['profit_before_tax']['variance_pct_' . $index]) }}%</strong></td> @endif
                    @endif
                @endforeach
            </tr>
        </tbody>

        <!-- TAX EXPENSE -->
        @if (!empty($profitLoss['tax_expenses']['accounts']) || !empty($profitLoss['tax_expenses']['groups']))
            <tbody>
                <tr class="xero-section-header">
                    <td colspan="{{ $totalCols }}">TAX EXPENSE</td>
                </tr>
                @foreach ($profitLoss['tax_expenses']['accounts'] ?? [] as $account)
                    <tr>
                        <td class="xero-link account-name" data-account-id="{{ $account['account']->id }}">{{ $account['account']->name }}</td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount) <td class="amount">{{ numberFormat($account['variance_' . $index]) }}</td> @endif
                                @if ($showVariancePercentage) <td class="amount">{{ numberFormat($account['variance_pct_' . $index]) }}%</td> @endif
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        @endif

        <tbody>
            <!-- NET PROFIT -->
            <tr class="xero-grand-total">
                <td><strong>NET PROFIT</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($profitLoss['net_profit'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['net_profit']['variance_' . $index]) }}</strong>
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount">
                                <strong>{{ numberFormat($profitLoss['net_profit']['variance_pct_' . $index]) }}%</strong>
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
