@php
    $periods = $balanceSheet['periods'];
    $showVarianceAmount = $showVarianceAmount ?? request('show_variance_amount', false);
    $showVariancePercentage = $showVariancePercentage ?? request('show_variance_percentage', false);
    $colSpan = 1;
    foreach ($periods as $index => $period) {
        $colSpan++;
        if ($index > 0) {
            if ($showVarianceAmount) {
                $colSpan++;
            }
            if ($showVariancePercentage) {
                $colSpan++;
            }
        }
    }
@endphp

<!-- Xero-style Balance Sheet Table -->
<div class="mt-4 overflow-x-auto overflow-y-auto max-h-[80vh]">
    <table class="report-table">
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

        {{-- ═══════════════════ ASSETS ═══════════════════ --}}
        <tbody>
            <tr class="section-header">
                <td colspan="{{ $colSpan }}">Assets</td>
            </tr>
        </tbody>

        @if (!empty($balanceSheet['current_assets']['groups']) || !empty($balanceSheet['current_assets']['accounts']))
            <tbody>
                <tr class="sub-section-label">
                    <td colspan="{{ $colSpan }}">Current Assets</td>
                </tr>
            </tbody>

            @foreach ($balanceSheet['current_assets']['groups'] ?? [] as $group)
                @include('pages.account.reports.partials._bs-group-row', ['group' => $group, 'periods' => $periods, 'eventPrefix' => 'balance-sheet'])
            @endforeach

            <tbody>
                @foreach ($balanceSheet['current_assets']['accounts'] ?? [] as $account)
                    @include('pages.account.reports.partials._bs-account-row', ['account' => $account, 'periods' => $periods])
                @endforeach

                @if (isset($balanceSheet['total_current_assets']))
                    <tr class="sub-section-total">
                        <td><strong>Total Current Assets</strong></td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount"><strong>{{ numberFormat($balanceSheet['total_current_assets'][$key]) }}</strong></td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_current_assets']['variance_' . $index]) }}</strong></td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_current_assets']['variance_pct_' . $index]) }}%</strong></td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endif
            </tbody>
        @endif

        @if (!empty($balanceSheet['non_current_assets']['groups']) || !empty($balanceSheet['non_current_assets']['accounts']))
            <tbody>
                <tr class="sub-section-label">
                    <td colspan="{{ $colSpan }}">Non-Current Assets</td>
                </tr>
            </tbody>

            @foreach ($balanceSheet['non_current_assets']['groups'] ?? [] as $group)
                @include('pages.account.reports.partials._bs-group-row', ['group' => $group, 'periods' => $periods, 'eventPrefix' => 'balance-sheet'])
            @endforeach

            <tbody>
                @foreach ($balanceSheet['non_current_assets']['accounts'] ?? [] as $account)
                    @include('pages.account.reports.partials._bs-account-row', ['account' => $account, 'periods' => $periods])
                @endforeach

                @if (isset($balanceSheet['total_non_current_assets']))
                    <tr class="sub-section-total">
                        <td><strong>Total Non-Current Assets</strong></td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount"><strong>{{ numberFormat($balanceSheet['total_non_current_assets'][$key]) }}</strong></td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_non_current_assets']['variance_' . $index]) }}</strong></td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_non_current_assets']['variance_pct_' . $index]) }}%</strong></td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endif
            </tbody>
        @endif

        <tbody>
            <tr class="section-total">
                <td><strong>Total Assets</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($balanceSheet['total_assets'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_assets']['variance_' . $index]) }}</strong></td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_assets']['variance_pct_' . $index]) }}%</strong></td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>

        {{-- ═══════════════════ LIABILITIES ═══════════════════ --}}
        <tbody>
            <tr class="section-header">
                <td colspan="{{ $colSpan }}">Liabilities</td>
            </tr>
        </tbody>

        @if (!empty($balanceSheet['current_liabilities']['groups']) || !empty($balanceSheet['current_liabilities']['accounts']))
            <tbody>
                <tr class="sub-section-label">
                    <td colspan="{{ $colSpan }}">Current Liabilities</td>
                </tr>
            </tbody>

            @foreach ($balanceSheet['current_liabilities']['groups'] ?? [] as $group)
                @include('pages.account.reports.partials._bs-group-row', ['group' => $group, 'periods' => $periods, 'eventPrefix' => 'balance-sheet'])
            @endforeach

            <tbody>
                @foreach ($balanceSheet['current_liabilities']['accounts'] ?? [] as $account)
                    @include('pages.account.reports.partials._bs-account-row', ['account' => $account, 'periods' => $periods])
                @endforeach

                @if (isset($balanceSheet['total_current_liabilities']))
                    <tr class="sub-section-total">
                        <td><strong>Total Current Liabilities</strong></td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount"><strong>{{ numberFormat($balanceSheet['total_current_liabilities'][$key]) }}</strong></td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_current_liabilities']['variance_' . $index]) }}</strong></td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_current_liabilities']['variance_pct_' . $index]) }}%</strong></td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endif
            </tbody>
        @endif

        @if (!empty($balanceSheet['non_current_liabilities']['groups']) || !empty($balanceSheet['non_current_liabilities']['accounts']))
            <tbody>
                <tr class="sub-section-label">
                    <td colspan="{{ $colSpan }}">Non-Current Liabilities</td>
                </tr>
            </tbody>

            @foreach ($balanceSheet['non_current_liabilities']['groups'] ?? [] as $group)
                @include('pages.account.reports.partials._bs-group-row', ['group' => $group, 'periods' => $periods, 'eventPrefix' => 'balance-sheet'])
            @endforeach

            <tbody>
                @foreach ($balanceSheet['non_current_liabilities']['accounts'] ?? [] as $account)
                    @include('pages.account.reports.partials._bs-account-row', ['account' => $account, 'periods' => $periods])
                @endforeach

                @if (isset($balanceSheet['total_non_current_liabilities']))
                    <tr class="sub-section-total">
                        <td><strong>Total Non-Current Liabilities</strong></td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount"><strong>{{ numberFormat($balanceSheet['total_non_current_liabilities'][$key]) }}</strong></td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_non_current_liabilities']['variance_' . $index]) }}</strong></td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_non_current_liabilities']['variance_pct_' . $index]) }}%</strong></td>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                @endif
            </tbody>
        @endif

        <tbody>
            <tr class="section-total">
                <td><strong>Total Liabilities</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($balanceSheet['total_liabilities'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_liabilities']['variance_' . $index]) }}</strong></td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-column"><strong>{{ numberFormat($balanceSheet['total_liabilities']['variance_pct_' . $index]) }}%</strong></td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>

        {{-- ═══════════════════ NET ASSETS (Xero signature row) ═══════════════════ --}}
        <tbody>
            <tr class="net-assets-row">
                <td><strong>Net Assets</strong></td>
                @foreach ($periods as $index => $period)
                    @php
                        $key = ($index === 0) ? 'current' : 'compare_' . $index;
                        $netAssets = $balanceSheet['total_assets'][$key] - $balanceSheet['total_liabilities'][$key];
                    @endphp
                    <td class="amount"><strong>{{ numberFormat($netAssets) }}</strong></td>
                    @if ($index > 0)
                        @php
                            $netAssetsCurrent = $balanceSheet['total_assets']['current'] - $balanceSheet['total_liabilities']['current'];
                            $netAssetsVariance = $netAssetsCurrent - $netAssets;
                            $netAssetsVariancePct = $netAssets != 0 ? ($netAssetsVariance / abs($netAssets)) * 100 : 0;
                        @endphp
                        @if ($showVarianceAmount)
                            <td class="amount variance-column"><strong>{{ numberFormat($netAssetsVariance) }}</strong></td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-column"><strong>{{ numberFormat($netAssetsVariancePct) }}%</strong></td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>

        {{-- ═══════════════════ EQUITY ═══════════════════ --}}
        <tbody>
            <tr class="section-header">
                <td colspan="{{ $colSpan }}">Equity</td>
            </tr>
        </tbody>

        @foreach ($balanceSheet['equity']['groups'] as $group)
            <tbody x-data="{
                open: true,
                init() {
                    document.addEventListener('balance-sheet:expand-all', () => this.open = true);
                    document.addEventListener('balance-sheet:collapse-all', () => this.open = false);
                }
            }">
                <tr class="group-header cursor-pointer" @click="open = !open">
                    <td class="whitespace-nowrap">
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
                                <td class="amount variance-column">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-column">{{ numberFormat($group['total']['variance_pct_' . $index]) }}%</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
                @foreach ($group['accounts'] as $account)
                    <tr x-show="open" x-cloak>
                        <td class="sub-account">
                            <span class="account-name" data-account-id="{{ $account['account']->id }}">
                                {{ $account['account']->name }}
                            </span>
                        </td>
                        @foreach ($periods as $index => $period)
                            @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                            <td class="amount">{{ numberFormat($account[$key]) }}</td>
                            @if ($index > 0)
                                @if ($showVarianceAmount)
                                    <td class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                        {{ numberFormat($account['variance_' . $index]) }}
                                    </td>
                                @endif
                                @if ($showVariancePercentage)
                                    <td class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
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
            @foreach ($balanceSheet['equity']['accounts'] as $account)
                <tr>
                    <td style="padding-left: 32px;">
                        <span class="account-name" data-account-id="{{ $account['account']->id }}">
                            {{ $account['account']->name }}
                        </span>
                    </td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($account[$key]) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($account['variance_' . $index]) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($account['variance_pct_' . $index]) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach

            <!-- Prior Year Retained Earnings (unclosed prior years) -->
            @if (isset($balanceSheet['prior_year_retained']) && ($balanceSheet['prior_year_retained']['current'] ?? 0) != 0)
                <tr>
                    <td style="padding-left: 32px;">
                        <span class="account-name">Prior Year Retained Earnings</span>
                    </td>
                    @foreach ($periods as $index => $period)
                        @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                        <td class="amount">{{ numberFormat($balanceSheet['prior_year_retained'][$key] ?? 0) }}</td>
                        @if ($index > 0)
                            @if ($showVarianceAmount)
                                <td class="amount variance-column {{ ($balanceSheet['prior_year_retained']['variance_' . $index] ?? 0) > 0 ? 'positive' : (($balanceSheet['prior_year_retained']['variance_' . $index] ?? 0) < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($balanceSheet['prior_year_retained']['variance_' . $index] ?? 0) }}
                                </td>
                            @endif
                            @if ($showVariancePercentage)
                                <td class="amount variance-column {{ ($balanceSheet['prior_year_retained']['variance_pct_' . $index] ?? 0) > 0 ? 'positive' : (($balanceSheet['prior_year_retained']['variance_pct_' . $index] ?? 0) < 0 ? 'negative' : 'zero') }}">
                                    {{ numberFormat($balanceSheet['prior_year_retained']['variance_pct_' . $index] ?? 0) }}%
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endif

            <!-- Current Year Earnings -->
            <tr>
                <td style="padding-left: 32px;">
                    <span class="account-name">Current Year Earnings</span>
                </td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount">{{ numberFormat($balanceSheet['current_year_earnings'][$key]) }}</td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount variance-column {{ $balanceSheet['current_year_earnings']['variance_' . $index] > 0 ? 'positive' : ($balanceSheet['current_year_earnings']['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                                {{ numberFormat($balanceSheet['current_year_earnings']['variance_' . $index]) }}
                            </td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount variance-column {{ $balanceSheet['current_year_earnings']['variance_pct_' . $index] > 0 ? 'positive' : ($balanceSheet['current_year_earnings']['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                                {{ numberFormat($balanceSheet['current_year_earnings']['variance_pct_' . $index]) }}%
                            </td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <tr class="section-total">
                <td><strong>Total Equity</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($balanceSheet['total_equity'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount"><strong>{{ numberFormat($balanceSheet['total_equity']['variance_' . $index]) }}</strong></td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount"><strong>{{ numberFormat($balanceSheet['total_equity']['variance_pct_' . $index]) }}%</strong></td>
                        @endif
                    @endif
                @endforeach
            </tr>

            {{-- Total Liabilities & Equity --}}
            <tr class="grand-total">
                <td><strong>Total Liabilities & Equity</strong></td>
                @foreach ($periods as $index => $period)
                    @php $key = ($index === 0) ? 'current' : 'compare_' . $index; @endphp
                    <td class="amount"><strong>{{ numberFormat($balanceSheet['total_liabilities_equity'][$key]) }}</strong></td>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <td class="amount"><strong>{{ numberFormat($balanceSheet['total_liabilities_equity']['variance_' . $index]) }}</strong></td>
                        @endif
                        @if ($showVariancePercentage)
                            <td class="amount"><strong>{{ numberFormat($balanceSheet['total_liabilities_equity']['variance_pct_' . $index]) }}%</strong></td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
