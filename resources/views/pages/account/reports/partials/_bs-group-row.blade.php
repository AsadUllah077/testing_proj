{{-- Xero-style Balance Sheet group row with expand/collapse --}}
<tbody x-data="{
    open: true,
    init() {
        document.addEventListener('{{ $eventPrefix }}:expand-all', () => this.open = true);
        document.addEventListener('{{ $eventPrefix }}:collapse-all', () => this.open = false);
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
                @if ($showVarianceAmount ?? false)
                    <td class="amount variance-column">{{ numberFormat($group['total']['variance_' . $index]) }}</td>
                @endif
                @if ($showVariancePercentage ?? false)
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
                    @if ($showVarianceAmount ?? false)
                        <td class="amount variance-column {{ $account['variance_' . $index] > 0 ? 'positive' : ($account['variance_' . $index] < 0 ? 'negative' : 'zero') }}">
                            {{ numberFormat($account['variance_' . $index]) }}
                        </td>
                    @endif
                    @if ($showVariancePercentage ?? false)
                        <td class="amount variance-column {{ $account['variance_pct_' . $index] > 0 ? 'positive' : ($account['variance_pct_' . $index] < 0 ? 'negative' : 'zero') }}">
                            {{ numberFormat($account['variance_pct_' . $index]) }}%
                        </td>
                    @endif
                @endif
            @endforeach
        </tr>
    @endforeach
</tbody>
