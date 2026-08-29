@extends('layouts.auth')

@section('title', 'Opening Balance Conversion')

@section('content')
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Opening Balance Conversion</h1>
                <p class="text-sm text-gray-500" id="batch-subtitle">{{ $batch->exists ? $batch->reference : (($batch->source_system ?: 'Xero') . ' opening trial balance batch') }}</p>
            </div>
            <a href="{{ route('account.conversion.opening-balances.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Back</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            Enter target balances from the closing trial balance. You may save available accounts now and finish later, but complete the full target set before validation because an omitted eligible account is treated as a zero target when posting. During validation, account 399 Opening Balance Equity is recalculated automatically as the Historical Adjustment so total debits equal total credits.
        </div>

        <form id="opening-balance-form" method="POST" action="{{ $batch->exists ? route('account.conversion.opening-balances.update', $batch) : route('account.conversion.opening-balances.store') }}" class="space-y-6">
            @csrf
            @if ($batch->exists)
                @method('PUT')
            @endif

            <div class="bg-white border border-gray-200 rounded-lg p-5 grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Source System</label>
                    <input id="source-system-input" name="source_system" value="{{ old('source_system', $batch->source_system ?: 'Xero') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required placeholder="e.g. Xero, QuickBooks, Sage">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reference</label>
                    <input id="reference-input" name="reference" value="{{ old('reference', $batch->reference) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Resolved Fiscal Period</label>
                    <input value="{{ $batch->fiscal_year ?: 'Resolved from posting date' }}" class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Balance As At</label>
                    <input id="opening-balance-closing-date" name="closing_date" type="text" value="{{ old('closing_date', optional($batch->closing_date)->format('Y-m-d') ?: $batch->closing_date) }}" class="{{ $batch->supersedes_batch_id ? 'bg-gray-50' : 'opening-balance-date' }} w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required @readonly($batch->supersedes_batch_id)>
                    <p class="mt-1 text-xs text-gray-500">May equal the posting date when balances must appear in the closing Balance Sheet.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Effective Reporting Date</label>
                    <input id="opening-balance-conversion-date" name="conversion_date" type="text" value="{{ old('conversion_date', optional($batch->conversion_date)->format('Y-m-d') ?: $batch->conversion_date) }}" class="{{ $batch->supersedes_batch_id ? 'bg-gray-50' : 'opening-balance-date' }} w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required @readonly($batch->supersedes_batch_id)>
                    <p class="mt-1 text-xs text-gray-500">The journal uses this date so balances appear in reports as at this date.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Using ERP From</label>
                    <input id="opening-balance-go-live-date" name="go_live_date" type="text" value="{{ old('go_live_date', optional($batch->go_live_date)->format('Y-m-d') ?: $batch->go_live_date) }}" class="{{ $batch->supersedes_batch_id ? 'bg-gray-50' : 'opening-balance-date' }} w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @readonly($batch->supersedes_batch_id)>
                    <p class="mt-1 text-xs text-gray-500">Normally the next day, such as 1 July after balances as at 30 June.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <input name="notes" value="{{ old('notes', $batch->notes) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <table class="min-w-full text-sm" id="lines-table">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-3 py-3">Account</th>
                            <th class="px-3 py-3 source-code-label"><span class="source-name-display">{{ $batch->source_system ?: 'Xero' }}</span> Code</th>
                            <th class="px-3 py-3 source-name-label"><span class="source-name-display">{{ $batch->source_system ?: 'Xero' }}</span> Name</th>
                            <th class="px-3 py-3 text-right">Debit</th>
                            <th class="px-3 py-3 text-right">Credit</th>
                            <th class="px-3 py-3">Description</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($lines = old('lines', $batch->exists ? $batch->lines->toArray() : [['debit' => '', 'credit' => '']]))
                        @foreach ($lines as $index => $line)
                            <tr class="line-row border-t border-gray-100">
                                <td class="px-3 py-2">
                                    <select name="lines[{{ $index }}][account_id]" class="account-select select2 w-full border border-gray-300 rounded px-2 py-2" required>
                                        <option value="">Select account</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" {{ (string)($line['account_id'] ?? '') === (string)$account->id ? 'selected' : '' }} {{ ! $account->opening_balance_eligible ? 'disabled' : '' }}>
                                                {{ $account->account_number }} - {{ $account->name }}{{ ! $account->opening_balance_eligible ? ' — not eligible' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2"><input name="lines[{{ $index }}][source_account_code]" value="{{ $line['source_account_code'] ?? '' }}" class="w-full border border-gray-300 rounded px-2 py-2"></td>
                                <td class="px-3 py-2"><input name="lines[{{ $index }}][source_account_name]" value="{{ $line['source_account_name'] ?? '' }}" class="w-full border border-gray-300 rounded px-2 py-2"></td>
                                <td class="px-3 py-2"><input name="lines[{{ $index }}][debit]" value="{{ $line['debit'] ?? '' }}" type="number" step="0.01" min="0" class="amount debit w-full border border-gray-300 rounded px-2 py-2 text-right"></td>
                                <td class="px-3 py-2"><input name="lines[{{ $index }}][credit]" value="{{ $line['credit'] ?? '' }}" type="number" step="0.01" min="0" class="amount credit w-full border border-gray-300 rounded px-2 py-2 text-right"></td>
                                <td class="px-3 py-2"><input name="lines[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}" class="w-full border border-gray-300 rounded px-2 py-2"></td>
                                <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-700">Remove</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-3 py-3 font-medium text-right">Totals</td>
                            <td class="px-3 py-3 text-right font-medium" id="total-debit">0.00</td>
                            <td class="px-3 py-3 text-right font-medium" id="total-credit">0.00</td>
                            <td class="px-3 py-3 font-medium" id="difference">Difference 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @isset($reconciliation)
                <div class="rounded-lg border {{ $reconciliation['balanced'] && empty($reconciliation['warnings']) ? 'border-green-200 bg-green-50' : 'border-amber-300 bg-amber-50' }} p-4 text-sm">
                    <div class="font-semibold">Conversion reconciliation</div>
                    <div class="mt-2 grid gap-2 md:grid-cols-4">
                        <div>Difference: <strong>{{ numberFormat($reconciliation['difference']) }}</strong></div>
                        <div>Imported P&amp;L result: <strong>{{ numberFormat($reconciliation['pnl_net_result']) }}</strong></div>
                        <div>Historical adjustment: <strong>{{ numberFormat($reconciliation['historical_adjustment']) }}</strong></div>
                        <div>Status: <strong>{{ $reconciliation['balanced'] ? 'Balanced' : 'Out of balance' }}</strong></div>
                    </div>
                    @foreach ($reconciliation['warnings'] as $warning)
                        <div class="mt-2 text-amber-900">{{ $warning }}</div>
                    @endforeach
                    @include('pages.account.conversion.opening-balances.partials.control-reconciliation')
                </div>
            @endisset

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="add-line" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Add Line</button>
                    <button type="button" id="reset-opening-balance-lines" class="px-4 py-2 border border-red-300 rounded-lg text-sm text-red-700 hover:bg-red-50">Reset Account Lines</button>
                    @if (! $batch->exists && auth()->user()->hasPermission('account.chart-of-account.create'))
                        <a id="create-coa-from-opening-balance" href="{{ route('account.organization-settings.account.create') }}"
                            target="_blank" rel="noopener"
                            class="px-4 py-2 border border-[#0078C8] rounded-lg text-sm text-[#0078C8] hover:bg-blue-50">
                            + Create COA Account
                        </a>
                    @endif
                </div>
                <button type="submit" class="px-5 py-2 bg-[#0078C8] text-white rounded-lg text-sm">Save Draft</button>
            </div>
            @if (! $batch->exists)
                <p class="text-xs text-gray-500">Unsaved header values and account lines are preserved in this browser tab when you refresh to load a newly created COA account.</p>
            @endif
        </form>
    </div>
@endsection

@push('scripts')
<script>
$(function() {
    const accountOptions = @json($accounts->map(fn($account) => ['id' => $account->id, 'label' => $account->account_number . ' - ' . $account->name, 'eligible' => (bool) $account->opening_balance_eligible])->values());
    const isBatchExists = @json($batch->exists);
    const hasServerOldInput = @json(old('lines') !== null || old('reference') !== null);
    const openingBalanceDraftStateKey = @json('opening-balance-create-state-company-' . loggedCompanyId()['company_id']);
    let previousSource = $('#source-system-input').val() ? $('#source-system-input').val().trim() : 'Xero';
    let draftStateSaveTimer = null;

    function updateSourceSystemLabels() {
        let val = $('#source-system-input').val() ? $('#source-system-input').val().trim() : '';
        let displayName = val || 'Xero';
        
        $('.source-name-display').text(displayName);
        if (!isBatchExists) {
            $('#batch-subtitle').text(displayName + ' opening trial balance batch');

            let refInput = $('#reference-input');
            if (refInput.length && previousSource) {
                let prevPrefix = previousSource.toUpperCase().replace(/[^A-Z0-9]/g, '');
                let newPrefix = displayName.toUpperCase().replace(/[^A-Z0-9]/g, '');

                if (refInput.val().startsWith(prevPrefix + '-OB-')) {
                    refInput.val(refInput.val().replace(prevPrefix + '-OB-', newPrefix + '-OB-'));
                } else if (refInput.val().startsWith('OB-')) {
                    refInput.val(refInput.val().replace('OB-', newPrefix + '-OB-'));
                }
            }
        }
        previousSource = displayName;
    }

    $('#source-system-input').on('input change', updateSourceSystemLabels);
    updateSourceSystemLabels();

    function rowTemplate(index) {
        const options = accountOptions.map(account => `<option value="${account.id}" ${account.eligible ? '' : 'disabled'}>${account.label}${account.eligible ? '' : ' — not eligible'}</option>`).join('');
        return `<tr class="line-row border-t border-gray-100">
            <td class="px-3 py-2"><select name="lines[${index}][account_id]" class="account-select select2 w-full border border-gray-300 rounded px-2 py-2" required><option value="">Select account</option>${options}</select></td>
            <td class="px-3 py-2"><input name="lines[${index}][source_account_code]" class="w-full border border-gray-300 rounded px-2 py-2"></td>
            <td class="px-3 py-2"><input name="lines[${index}][source_account_name]" class="w-full border border-gray-300 rounded px-2 py-2"></td>
            <td class="px-3 py-2"><input name="lines[${index}][debit]" type="number" step="0.01" min="0" class="amount debit w-full border border-gray-300 rounded px-2 py-2 text-right"></td>
            <td class="px-3 py-2"><input name="lines[${index}][credit]" type="number" step="0.01" min="0" class="amount credit w-full border border-gray-300 rounded px-2 py-2 text-right"></td>
            <td class="px-3 py-2"><input name="lines[${index}][description]" class="w-full border border-gray-300 rounded px-2 py-2"></td>
            <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-700">Remove</button></td>
        </tr>`;
    }

    function totals() {
        let debit = 0;
        let credit = 0;
        $('.debit').each(function() { debit += parseFloat($(this).val() || 0); });
        $('.credit').each(function() { credit += parseFloat($(this).val() || 0); });
        $('#total-debit').text(debit.toFixed(2));
        $('#total-credit').text(credit.toFixed(2));
        $('#difference').text('Difference ' + (debit - credit).toFixed(2));
    }

    function openingBalanceLinesState() {
        return $('.line-row').map(function() {
            const $row = $(this);
            return {
                accountId: $row.find('select[name$="[account_id]"]').val() || '',
                sourceAccountCode: $row.find('input[name$="[source_account_code]"]').val() || '',
                sourceAccountName: $row.find('input[name$="[source_account_name]"]').val() || '',
                debit: $row.find('input[name$="[debit]"]').val() || '',
                credit: $row.find('input[name$="[credit]"]').val() || '',
                description: $row.find('input[name$="[description]"]').val() || ''
            };
        }).get();
    }

    function saveOpeningBalanceCreateState() {
        if (isBatchExists) return;

        try {
            sessionStorage.setItem(openingBalanceDraftStateKey, JSON.stringify({
                sourceSystem: $('#source-system-input').val() || '',
                reference: $('#reference-input').val() || '',
                closingDate: $('#opening-balance-closing-date').val() || '',
                conversionDate: $('#opening-balance-conversion-date').val() || '',
                goLiveDate: $('#opening-balance-go-live-date').val() || '',
                notes: $('input[name="notes"]').val() || '',
                lines: openingBalanceLinesState()
            }));
        } catch (error) {
            // The form remains usable when browser storage is unavailable.
        }
    }

    function queueOpeningBalanceCreateStateSave() {
        clearTimeout(draftStateSaveTimer);
        draftStateSaveTimer = setTimeout(saveOpeningBalanceCreateState, 150);
    }

    function restoreOpeningBalanceCreateState() {
        if (isBatchExists || hasServerOldInput) return;

        try {
            const state = JSON.parse(sessionStorage.getItem(openingBalanceDraftStateKey) || 'null');
            if (!state) return;

            $('#source-system-input').val(state.sourceSystem || 'Xero');
            $('#reference-input').val(state.reference || '');
            $('#opening-balance-closing-date').val(state.closingDate || '');
            $('#opening-balance-conversion-date').val(state.conversionDate || '');
            $('#opening-balance-go-live-date').val(state.goLiveDate || '');
            $('input[name="notes"]').val(state.notes || '');

            const savedLines = Array.isArray(state.lines) && state.lines.length
                ? state.lines
                : [{ accountId: '', debit: '', credit: '' }];
            const $tbody = $('#lines-table tbody').empty();
            savedLines.forEach(function(line, index) {
                const $row = $(rowTemplate(index));
                $row.find('select[name$="[account_id]"]').val(String(line.accountId || ''));
                $row.find('input[name$="[source_account_code]"]').val(line.sourceAccountCode || '');
                $row.find('input[name$="[source_account_name]"]').val(line.sourceAccountName || '');
                $row.find('input[name$="[debit]"]').val(line.debit || '');
                $row.find('input[name$="[credit]"]').val(line.credit || '');
                $row.find('input[name$="[description]"]').val(line.description || '');
                $tbody.append($row);
            });
            previousSource = state.sourceSystem || 'Xero';
        } catch (error) {
            sessionStorage.removeItem(openingBalanceDraftStateKey);
        }
    }

    function initAccountSelects(context) {
        $(context).find('select.account-select').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    width: '100%',
                    placeholder: 'Select account',
                    allowClear: true
                });
            }
        });
    }

    function initOpeningBalanceDates(context) {
        if (typeof flatpickr === 'undefined') {
            return;
        }

        $(context).find('.opening-balance-date').each(function() {
            if (this._flatpickr) {
                return;
            }
            const savedIsoDate = this.value;
            flatpickr(this, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
                defaultDate: savedIsoDate || null
            });
        });
    }

    $('#add-line').on('click', function() {
        const $row = $(rowTemplate($('.line-row').length));
        $('#lines-table tbody').append($row);
        initAccountSelects($row);
        totals();
        saveOpeningBalanceCreateState();
    });

    $(document).on('click', '.remove-line', function() {
        if ($('.line-row').length > 1) {
            $(this).closest('tr').remove();
            totals();
            saveOpeningBalanceCreateState();
        }
    });

    $('#reset-opening-balance-lines').on('click', function() {
        if (!window.confirm('Clear all unsaved opening balance account lines?')) return;

        const $row = $(rowTemplate(0));
        $('#lines-table tbody').empty().append($row);
        initAccountSelects($row);
        totals();
        saveOpeningBalanceCreateState();
    });

    $('#create-coa-from-opening-balance').on('click', saveOpeningBalanceCreateState);
    $('#opening-balance-form').on('input change', 'input, select', queueOpeningBalanceCreateStateSave);
    $('#opening-balance-form').on('submit', function() {
        if (!isBatchExists) {
            sessionStorage.removeItem(openingBalanceDraftStateKey);
        }
    });
    $(document).on('input', '.amount', totals);
    restoreOpeningBalanceCreateState();
    updateSourceSystemLabels();
    initAccountSelects(document);
    initOpeningBalanceDates(document);
    totals();
});
</script>
@endpush
