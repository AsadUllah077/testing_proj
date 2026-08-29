@php
    $periods = $cashFlow['periods'];
    $pData = $cashFlow['data'];
    $showVariance = $showVarianceAmount || $showVariancePercentage;

    $getDescriptions = function ($type) use ($pData) {
        $descs = [];
        foreach ($pData as $period) {
            foreach ($period[$type] as $activity) {
                $descs[$activity['description']] = true;
            }
        }
        return array_keys($descs);
    };

    $operatingDescriptions = $getDescriptions('operating_activities');
    $investingDescriptions = $getDescriptions('investing_activities');
    $financingDescriptions = $getDescriptions('financing_activities');
@endphp

<div class="mt-4 overflow-x-auto overflow-y-auto max-h-[80vh]">
    <table class="xero-data-table">
        <thead class="sticky top-0 z-10 bg-white">
            <tr class="text-left text-gray-600">
                <th class="px-5 py-3 font-medium">Category</th>
                @foreach ($periods as $index => $period)
                    <th
                        class="px-5 py-3 font-medium text-right {{ $index === 0 ? 'text-gray-800' : '' }}">
                        {{ $period['label'] }}
                    </th>
                    @if ($index > 0)
                        @if ($showVarianceAmount)
                            <th class="px-5 py-3 font-medium text-right text-gray-500">Var Amt</th>
                        @endif
                        @if ($showVariancePercentage)
                            <th class="px-5 py-3 font-medium text-right text-gray-500">Var %</th>
                        @endif
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <!-- Opening Balance -->
            <tr class="font-semibold border-b border-gray-200">
                <td class="px-5 py-3">Opening Cash Balance</td>
                @foreach ($pData as $index => $data)
                    <td
                        class="px-5 py-3 text-right {{ $index === 0 ? 'text-gray-800' : 'text-gray-600' }}">
                        {{ numberFormat($data['opening_balance']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['opening_balance'];
                            $current = $data['opening_balance'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td
                                class="px-5 py-3 text-right font-medium {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variance) }}</td>
                        @endif
                        @if ($showVariancePercentage)
                            <td
                                class="px-5 py-3 text-right font-medium {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variancePct) }}%</td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Operating Activities -->
            <tr class="xero-section-header">
                <td colspan="{{ 1 + count($periods) + ($showVarianceAmount ? count($periods) - 1 : 0) + ($showVariancePercentage ? count($periods) - 1 : 0) }}"
                    class="px-5 py-2">
                    CASH FLOWS FROM OPERATING ACTIVITIES (DIRECT)
                </td>
            </tr>
            @foreach ($operatingDescriptions as $desc)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-2 pl-10 text-gray-700">
                        {{ $desc }}</td>
                    @foreach ($pData as $index => $data)
                        @php
                            $found = 0;
                            foreach ($data['operating_activities'] as $act) {
                                if ($act['description'] === $desc) {
                                    $found = $act['amount'];
                                    break;
                                }
                            }
                        @endphp
                        <td
                            class="px-5 py-2 text-right {{ $index === 0 ? 'text-gray-800 font-medium' : 'text-gray-600' }}">
                            {{ $found != 0 ? numberFormat($found) : '-' }}
                        </td>
                        @if ($index > 0)
                            @php
                                $primaryFound = 0;
                                foreach ($pData[0]['operating_activities'] as $act) {
                                    if ($act['description'] === $desc) {
                                        $primaryFound = $act['amount'];
                                        break;
                                    }
                                }
                                $variance = $primaryFound - $found;
                                $variancePct =
                                    $found != 0 ? ($variance / abs($found)) * 100 : ($primaryFound != 0 ? 100 : 0);
                            @endphp
                            @if ($showVarianceAmount)
                                <td
                                    class="px-5 py-2 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $variance != 0 ? numberFormat($variance) : '-' }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td
                                    class="px-5 py-2 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $variance != 0 ? numberFormat($variancePct) . '%' : '-' }}</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach
            <tr class="xero-page-total">
                <td class="px-5 py-3">Net Cash from Operating
                    Activities</td>
                @foreach ($pData as $index => $data)
                    <td
                        class="px-5 py-3 text-right {{ $index === 0 ? 'text-gray-800' : 'text-gray-600' }}">
                        {{ numberFormat($data['net_cash_from_operating']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['net_cash_from_operating'];
                            $current = $data['net_cash_from_operating'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td
                                class="px-5 py-3 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variance) }}</td>
                        @endif
                        @if ($showVariancePercentage)
                            <td
                                class="px-5 py-3 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variancePct) }}%</td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Investing Activities -->
            <tr class="xero-section-header">
                <td colspan="{{ 1 + count($periods) + ($showVarianceAmount ? count($periods) - 1 : 0) + ($showVariancePercentage ? count($periods) - 1 : 0) }}"
                    class="px-5 py-2">
                    CASH FLOWS FROM INVESTING ACTIVITIES
                </td>
            </tr>
            @foreach ($investingDescriptions as $desc)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-2 pl-10 text-gray-700">
                        {{ $desc }}</td>
                    @foreach ($pData as $index => $data)
                        @php
                            $found = 0;
                            foreach ($data['investing_activities'] as $act) {
                                if ($act['description'] === $desc) {
                                    $found = $act['amount'];
                                    break;
                                }
                            }
                        @endphp
                        <td
                            class="px-5 py-2 text-right {{ $index === 0 ? 'text-gray-800 font-medium' : 'text-gray-600' }}">
                            {{ $found != 0 ? numberFormat($found) : '-' }}
                        </td>
                        @if ($index > 0)
                            @php
                                $primaryFound = 0;
                                foreach ($pData[0]['investing_activities'] as $act) {
                                    if ($act['description'] === $desc) {
                                        $primaryFound = $act['amount'];
                                        break;
                                    }
                                }
                                $variance = $primaryFound - $found;
                                $variancePct =
                                    $found != 0 ? ($variance / abs($found)) * 100 : ($primaryFound != 0 ? 100 : 0);
                            @endphp
                            @if ($showVarianceAmount)
                                <td
                                    class="px-5 py-2 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $variance != 0 ? numberFormat($variance) : '-' }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td
                                    class="px-5 py-2 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $variance != 0 ? numberFormat($variancePct) . '%' : '-' }}</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach
            <tr class="xero-page-total">
                <td class="px-5 py-3">Net Cash from Investing
                    Activities</td>
                @foreach ($pData as $index => $data)
                    <td
                        class="px-5 py-3 text-right {{ $index === 0 ? 'text-gray-800' : 'text-gray-600' }}">
                        {{ numberFormat($data['net_cash_from_investing']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['net_cash_from_investing'];
                            $current = $data['net_cash_from_investing'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td
                                class="px-5 py-3 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variance) }}</td>
                        @endif
                        @if ($showVariancePercentage)
                            <td
                                class="px-5 py-3 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variancePct) }}%</td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Financing Activities -->
            <tr class="xero-section-header">
                <td colspan="{{ 1 + count($periods) + ($showVarianceAmount ? count($periods) - 1 : 0) + ($showVariancePercentage ? count($periods) - 1 : 0) }}"
                    class="px-5 py-2">
                    CASH FLOWS FROM FINANCING ACTIVITIES
                </td>
            </tr>
            @foreach ($financingDescriptions as $desc)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-2 pl-10 text-gray-700">
                        {{ $desc }}</td>
                    @foreach ($pData as $index => $data)
                        @php
                            $found = 0;
                            foreach ($data['financing_activities'] as $act) {
                                if ($act['description'] === $desc) {
                                    $found = $act['amount'];
                                    break;
                                }
                            }
                        @endphp
                        <td
                            class="px-5 py-2 text-right {{ $index === 0 ? 'text-gray-800 font-medium' : 'text-gray-600' }}">
                            {{ $found != 0 ? numberFormat($found) : '-' }}
                        </td>
                        @if ($index > 0)
                            @php
                                $primaryFound = 0;
                                foreach ($pData[0]['financing_activities'] as $act) {
                                    if ($act['description'] === $desc) {
                                        $primaryFound = $act['amount'];
                                        break;
                                    }
                                }
                                $variance = $primaryFound - $found;
                                $variancePct =
                                    $found != 0 ? ($variance / abs($found)) * 100 : ($primaryFound != 0 ? 100 : 0);
                            @endphp
                            @if ($showVarianceAmount)
                                <td
                                    class="px-5 py-2 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $variance != 0 ? numberFormat($variance) : '-' }}</td>
                            @endif
                            @if ($showVariancePercentage)
                                <td
                                    class="px-5 py-2 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $variance != 0 ? numberFormat($variancePct) . '%' : '-' }}</td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach
            <tr class="xero-page-total">
                <td class="px-5 py-3">Net Cash from Financing
                    Activities</td>
                @foreach ($pData as $index => $data)
                    <td
                        class="px-5 py-3 text-right {{ $index === 0 ? 'text-gray-800' : 'text-gray-600' }}">
                        {{ numberFormat($data['net_cash_from_financing']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['net_cash_from_financing'];
                            $current = $data['net_cash_from_financing'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td
                                class="px-5 py-3 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variance) }}</td>
                        @endif
                        @if ($showVariancePercentage)
                            <td
                                class="px-5 py-3 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variancePct) }}%</td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Net Movement -->
            <tr class="xero-grand-total">
                <td class="px-5 py-4">Net Increase/(Decrease)
                    in Cash</td>
                @foreach ($pData as $index => $data)
                    <td class="px-5 py-4 text-right">
                        {{ numberFormat($data['net_cash_movement']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['net_cash_movement'];
                            $current = $data['net_cash_movement'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td
                                class="px-5 py-4 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variance) }}</td>
                        @endif
                        @if ($showVariancePercentage)
                            <td
                                class="px-5 py-4 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variancePct) }}%</td>
                        @endif
                    @endif
                @endforeach
            </tr>

            <!-- Closing Balance -->
            <tr class="font-bold border-t border-gray-300">
                <td class="px-5 py-4">Closing Cash Balance</td>
                @foreach ($pData as $index => $data)
                    <td class="px-5 py-4 text-right {{ $index === 0 ? 'text-gray-800' : 'text-gray-600' }}">
                        {{ numberFormat($data['closing_balance']) }}
                    </td>
                    @if ($index > 0)
                        @php
                            $primary = $pData[0]['closing_balance'];
                            $current = $data['closing_balance'];
                            $variance = $primary - $current;
                            $variancePct =
                                $current != 0 ? ($variance / abs($current)) * 100 : ($primary != 0 ? 100 : 0);
                        @endphp
                        @if ($showVarianceAmount)
                            <td
                                class="px-5 py-4 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variance) }}</td>
                        @endif
                        @if ($showVariancePercentage)
                            <td
                                class="px-5 py-4 text-right {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ numberFormat($variancePct) }}%</td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
