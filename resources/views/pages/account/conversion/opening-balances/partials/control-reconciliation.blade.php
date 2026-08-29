@if (! empty($reconciliation['control_accounts']))
    <div class="mt-4 border-t border-gray-200 pt-3">
        <div class="font-semibold">Receivables and payables control reconciliation</div>
        <div class="text-xs text-gray-600">Matched controls come from posted outstanding documents and are not duplicated by the conversion journal.</div>
        @foreach ($reconciliation['control_accounts'] as $control)
            <div class="mt-2 grid gap-2 md:grid-cols-5">
                <div>{{ $control['account_number'] }} - {{ $control['account_name'] }}</div>
                <div>Target: {{ numberFormat($control['expected']) }}</div>
                <div>Documents: {{ numberFormat($control['subledger']) }}</div>
                <div>Difference: {{ numberFormat($control['difference']) }}</div>
                <div class="font-medium {{ $control['matched'] ? 'text-green-700' : 'text-red-700' }}">{{ $control['matched'] ? 'Reconciled' : 'Mismatch' }}</div>
            </div>
        @endforeach
    </div>
@endif
