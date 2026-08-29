{{-- Xero-style Balance Sheet standalone account row --}}
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
