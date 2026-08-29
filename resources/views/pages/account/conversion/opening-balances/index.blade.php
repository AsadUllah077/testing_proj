@extends('layouts.auth')

@section('title', 'Opening Balances')

@section('content')
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Opening Balances</h1>
                <p class="text-sm text-gray-500">Maintain one effective conversion balance set and its controlled version history.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" id="open-template-export-modal"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Template</button>
                @if ($openConversionRevision)
                    <a href="{{ $openConversionRevision->status === 'Draft' ? route('account.conversion.opening-balances.edit', $openConversionRevision) : route('account.conversion.opening-balances.show', $openConversionRevision) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Continue Conversion Draft</a>
                @elseif ($currentConversionBatch && auth()->user()->hasPermission('account.opening-balance-conversion.edit'))
                    <form method="POST" action="{{ route('account.conversion.opening-balances.revise', $currentConversionBatch) }}">
                        @csrf
                        <input type="hidden" name="reason" value="Continue entering or update conversion balances">
                        <button class="px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Continue Conversion Balances</button>
                    </form>
                @elseif ($legacyConversionConflictCount > 1)
                    <span class="px-4 py-2 border border-amber-300 bg-amber-50 text-amber-800 rounded-lg text-sm">Consolidation review required</span>
                @else
                    <a href="{{ route('account.conversion.opening-balances.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Enter Conversion Balances</a>
                @endif
                <a href="{{ route('account.organization-settings.account.index') }}"
                    class="px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">Back to COA</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($legacyConversionConflictCount > 1)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                {{ $legacyConversionConflictCount }} legacy Posted conversion sets exist for {{ $defaultClosingDate }}. The system will not combine or reverse them automatically. Complete the controlled, accountant-approved consolidation repair before continuing conversion balances.
            </div>
        @else
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                Enter any available account balances now and return later to finish them. Complete the full target set before validation because an omitted eligible account is treated as a zero target when posting. Continuing creates a complete replacement version: the previous conversion journal is reversed only when the new version is approved and posted. Account 399 Historical Adjustment is calculated during validation.
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <form method="POST" action="{{ route('account.conversion.opening-balances.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Source</label>
                        <input id="source-system-input" name="source_system" value="{{ old('source_system', 'Xero') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Xero, QuickBooks" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reference</label>
                        <input id="reference-input" name="reference" value="{{ old('reference', 'XERO-OB-' . loggedCompanyId()['company_id'] . '-' . now()->format('YmdHis')) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Balance As At</label>
                        <input name="closing_date" type="text" value="{{ old('closing_date', $defaultClosingDate) }}"
                            class="opening-balance-date w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Effective Reporting Date</label>
                        <input name="conversion_date" type="text" value="{{ old('conversion_date', $defaultConversionDate) }}"
                            class="opening-balance-date w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Using ERP From</label>
                        <input name="go_live_date" type="text" value="{{ old('go_live_date', $defaultGoLiveDate) }}"
                            class="opening-balance-date w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fiscal Period</label>
                        <input value="Resolved from conversion date" readonly
                            class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File</label>
                        <input name="import_file" type="file" accept=".csv,.txt,.xlsx,.xls"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <input name="notes" value="{{ old('notes') }}" placeholder="Notes"
                        class="w-full max-w-xl border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <button type="submit" class="ml-4 px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm hover:bg-[#006bb0]">
                        Import Draft
                    </button>
                </div>
            </form>
        </div>

        <div style="display: none;" class="bg-white border border-amber-200 rounded-lg overflow-hidden">
            <div class="p-5 bg-amber-50 border-b border-amber-200">
                <h2 class="font-semibold text-gray-900">Legacy Posted Opening Balances</h2>
                <p class="mt-1 text-sm text-gray-700">
                    These were created earlier from Chart of Accounts. Posted history cannot be edited directly. Use Adjust to create a balanced draft for the difference between the current effective balance and your new target.
                </p>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Account</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Journal Dates</th>
                        <th class="px-4 py-3 text-right">Effective Opening</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($legacyOpeningBalances as $legacy)
                        @php
                            $isDebit = bccomp((string) $legacy->net_debit, '0', 4) >= 0;
                            $effectiveAmount = ltrim((string) $legacy->net_debit, '-');
                            $isDesignatedObe = (string) $legacy->account_number === '399';
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $legacy->account_number }} - {{ $legacy->name }}</div>
                                @if ($legacy->system_account_type === 'OPENING_BALANCE_EQUITY' && ! $isDesignatedObe)
                                    <div class="mt-1 text-xs text-red-700">Review required: this is also tagged as Opening Balance Equity.</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $legacy->category }}</td>
                            <td class="px-4 py-3">{{ $legacy->first_date }}{{ $legacy->last_date !== $legacy->first_date ? ' to '.$legacy->last_date : '' }}</td>
                            <td class="px-4 py-3 text-right font-medium">
                                {{ numberFormat($effectiveAmount) }} {{ $isDebit ? 'DR' : 'CR' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($isDesignatedObe)
                                    <span class="text-xs text-gray-500">Adjusted as the offset account</span>
                                @elseif (auth()->user()->hasPermission('account.opening-balance-conversion.create'))
                                    <details class="text-left inline-block w-full max-w-xl">
                                        <summary class="cursor-pointer text-[#0078C8] text-right">Adjust</summary>
                                        <form method="POST" action="{{ route('account.conversion.opening-balances.legacy.adjustment', $legacy->account_id) }}" class="mt-3 grid grid-cols-2 gap-2 rounded border border-gray-200 bg-gray-50 p-3">
                                            @csrf
                                            <div>
                                                <label class="text-xs text-gray-600">New target amount</label>
                                                <input name="target_amount" type="number" step="0.0001" min="0" value="{{ $effectiveAmount }}" required class="mt-1 w-full border rounded px-2 py-1.5">
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600">Target side</label>
                                                <select name="target_side" required class="mt-1 w-full border rounded px-2 py-1.5">
                                                    <option value="debit" @selected($isDebit)>Debit</option>
                                                    <option value="credit" @selected(! $isDebit)>Credit</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600">Adjustment posting date</label>
                                                <input name="posting_date" type="text" value="{{ $defaultConversionDate }}" required class="opening-balance-date mt-1 w-full border rounded px-2 py-1.5">
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600">Reason</label>
                                                <input name="reason" required maxlength="1000" placeholder="Why is this balance changing?" class="mt-1 w-full border rounded px-2 py-1.5">
                                            </div>
                                            <div class="col-span-2 text-right">
                                                <button type="submit" class="px-3 py-1.5 bg-[#0078C8] text-white rounded">Create Adjustment Draft</button>
                                            </div>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No legacy posted opening balances found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Balance As At</th>
                        <th class="px-4 py-3">Version</th>
                        <th class="px-4 py-3 text-right">Debit</th>
                        <th class="px-4 py-3 text-right">Credit</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($batches as $batch)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <a href="{{ route('account.conversion.opening-balances.show', $batch) }}" class="text-[#0078C8] hover:underline">
                                    {{ $batch->reference }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $batch->source_system }}</td>
                            <td class="px-4 py-3">{{ optional($batch->closing_date)->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                {{ ucfirst($batch->batch_kind ?: 'conversion') }}
                                @if ($batch->supersedes_batch_id)
                                    <div class="text-xs text-gray-500">Replaces batch #{{ $batch->supersedes_batch_id }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ numberFormat($batch->total_debit) }}</td>
                            <td class="px-4 py-3 text-right">{{ numberFormat($batch->total_credit) }}</td>
                            <td class="px-4 py-3">{{ $batch->status }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('account.conversion.opening-balances.show', $batch) }}" class="text-[#0078C8]">View</a>
                                    @if ($batch->status === 'Draft')
                                        <a href="{{ route('account.conversion.opening-balances.edit', $batch) }}" class="text-[#0078C8]">Edit</a>
                                        <form method="POST" action="{{ route('account.conversion.opening-balances.validate', $batch) }}">
                                            @csrf
                                            <button class="text-gray-700" type="submit">Validate</button>
                                        </form>
                                    @elseif ($batch->status === 'Validated')
                                        <form method="POST" action="{{ route('account.conversion.opening-balances.approve', $batch) }}">
                                            @csrf
                                            <button class="text-blue-700" type="submit">Approve</button>
                                        </form>
                                    @elseif ($batch->status === 'Approved')
                                        <form method="POST" action="{{ route('account.conversion.opening-balances.post', $batch) }}">
                                            @csrf
                                            <button class="text-green-700" type="submit">Post</button>
                                        </form>
                                    @elseif ($batch->status === 'Posted')
                                        <a href="{{ route('account.conversion.opening-balances.show', $batch) }}" class="text-red-700">Reverse…</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">No opening balance batches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $batches->links() }}
    </div>

    <div id="template-export-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
        aria-labelledby="template-export-title">
        <div class="fixed inset-0 bg-black/40" data-template-modal-close></div>
        <div class="relative min-h-full flex items-start justify-center p-4 sm:items-center">
            <div class="relative flex max-h-[calc(100vh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
                <form method="GET" action="{{ route('account.conversion.opening-balances.template') }}" id="template-export-form"
                    class="flex min-h-0 flex-1 flex-col">
                    <div class="flex shrink-0 items-start justify-between border-b border-gray-200 px-6 py-4">
                        <div>
                            <h2 id="template-export-title" class="text-lg font-semibold text-gray-900">Download Opening Balance Template</h2>
                            <p class="mt-1 text-sm text-gray-500">Choose which Chart of Accounts should be included in the workbook.</p>
                        </div>
                        <button type="button" data-template-modal-close class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Close">&times;</button>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5">
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50">
                            <input type="radio" name="export_scope" value="all" class="mt-1"
                                @checked(old('export_scope', 'all') === 'all')>
                            <span>
                                <span class="block font-medium text-gray-900">All Chart of Accounts</span>
                                <span class="block text-sm text-gray-500">Include all {{ $templateAccounts->count() }} accounts and show eligibility guidance for each one.</span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50">
                            <input type="radio" name="export_scope" value="selected" class="mt-1"
                                @checked(old('export_scope') === 'selected')>
                            <span>
                                <span class="block font-medium text-gray-900">Selected Chart of Accounts</span>
                                <span class="block text-sm text-gray-500">Create a smaller template containing only the accounts selected below.</span>
                            </span>
                        </label>

                        <div id="template-account-selection" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="template-account-ids" class="block text-sm font-medium text-gray-700">Accounts</label>
                                <div class="flex items-center gap-3">
                                    <span id="template-account-count" class="text-xs font-medium text-[#0078C8]">0 selected</span>
                                    <button type="button" id="reset-template-account-selection"
                                        class="text-xs font-medium text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:text-gray-400">
                                        Reset Selected Accounts
                                    </button>
                                </div>
                            </div>
                            <select id="template-account-ids" name="account_ids[]" multiple class="w-full">
                                @foreach ($templateAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(in_array((string) $account->id, array_map('strval', (array) old('account_ids', [])), true))>
                                        {{ $account->account_number }} - {{ $account->name }} ({{ $account->category }} · {{ \App\Exports\OpeningBalanceConversionTemplateExport::isEligible($account) ? 'Eligible' : 'Ineligible' }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-gray-500">Search and select one or more accounts.</p>
                            @error('account_ids')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('account_ids.*')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <fieldset class="rounded-lg border border-gray-200 p-4">
                            <legend class="px-1 text-sm font-medium text-gray-700">Account eligibility</legend>
                            <div class="mt-1 grid gap-3 sm:grid-cols-3">
                                <label class="cursor-pointer rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                                    <span class="flex items-start gap-2">
                                        <input type="radio" name="eligibility_scope" value="eligible" class="mt-1"
                                            @checked(old('eligibility_scope', 'eligible') === 'eligible')>
                                        <span>
                                            <span class="block text-sm font-medium text-gray-900">Eligible only</span>
                                            <span class="block text-xs text-gray-500">{{ $templateEligibleCount }} accounts</span>
                                        </span>
                                    </span>
                                </label>
                                <label class="cursor-pointer rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                                    <span class="flex items-start gap-2">
                                        <input type="radio" name="eligibility_scope" value="ineligible" class="mt-1"
                                            @checked(old('eligibility_scope') === 'ineligible')>
                                        <span>
                                            <span class="block text-sm font-medium text-gray-900">Ineligible only</span>
                                            <span class="block text-xs text-gray-500">{{ $templateIneligibleCount }} accounts</span>
                                        </span>
                                    </span>
                                </label>
                                <label class="cursor-pointer rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                                    <span class="flex items-start gap-2">
                                        <input type="radio" name="eligibility_scope" value="both" class="mt-1"
                                            @checked(old('eligibility_scope') === 'both')>
                                        <span>
                                            <span class="block text-sm font-medium text-gray-900">Both</span>
                                            <span class="block text-xs text-gray-500">{{ $templateAccounts->count() }} accounts</span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            @error('eligibility_scope')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    </div>

                    <div class="flex shrink-0 flex-col gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            @if (auth()->user()->hasPermission('account.chart-of-account.create'))
                                <a id="create-template-coa-account" href="{{ route('account.organization-settings.account.create') }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center rounded-lg border border-[#0078C8] px-4 py-2 text-sm text-[#0078C8] hover:bg-blue-50">
                                    + Create COA Account
                                </a>
                                <p class="mt-1 text-xs text-gray-500">Your selections are saved in this browser tab. Create the account, then refresh this page.</p>
                            @endif
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" data-template-modal-close class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-[#0078C8] px-4 py-2 text-sm text-white hover:bg-[#006bb0]">Download Template</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        #template-export-modal .select2-container .select2-selection--multiple {
            min-height: 42px;
            max-height: 128px;
            overflow-y: auto;
        }

        #template-export-modal .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: block;
            padding-bottom: 4px;
        }
    </style>
@endsection

@push('scripts')
<script>
$(function() {
    let lastSource = $('#source-system-input').val().trim();

    function updateSourceSystemText() {
        let val = $('#source-system-input').val() ? $('#source-system-input').val().trim() : '';
        let displayName = val || 'Xero';
        $('.source-name-display').text(displayName);

        let refInput = $('#reference-input');
        if (refInput.length && lastSource) {
            let oldPrefix = lastSource.toUpperCase().replace(/[^A-Z0-9]/g, '');
            let newPrefix = (val ? val.toUpperCase() : 'XERO').replace(/[^A-Z0-9]/g, '');

            if (refInput.val().startsWith(oldPrefix + '-OB-')) {
                refInput.val(refInput.val().replace(oldPrefix + '-OB-', newPrefix + '-OB-'));
            } else if (refInput.val().startsWith('OB-')) {
                refInput.val(refInput.val().replace('OB-', newPrefix + '-OB-'));
            }
        }
        if (val) {
            lastSource = val;
        }
    }

    $('#source-system-input').on('input change', updateSourceSystemText);
    updateSourceSystemText();

    if (typeof flatpickr !== 'undefined') {
        $('.opening-balance-date').each(function() {
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

    const $templateModal = $('#template-export-modal');
    const $accountSelection = $('#template-account-selection');
    const $accountSelect = $('#template-account-ids');
    const $accountCount = $('#template-account-count');
    const templateStateKey = @json('opening-balance-template-state-company-' . loggedCompanyId()['company_id']);
    const templateReopenKey = @json('opening-balance-template-reopen-company-' . loggedCompanyId()['company_id']);
    const hasServerTemplateState = @json(
        old('export_scope') !== null
        || $errors->has('export_scope')
        || $errors->has('eligibility_scope')
        || $errors->has('account_ids')
        || $errors->has('account_ids.*')
    );

    function saveTemplateExportState() {
        try {
            sessionStorage.setItem(templateStateKey, JSON.stringify({
                exportScope: $('input[name="export_scope"]:checked').val() || 'all',
                eligibilityScope: $('input[name="eligibility_scope"]:checked').val() || 'eligible',
                accountIds: $accountSelect.val() || []
            }));
        } catch (error) {
            // The export remains usable when browser storage is unavailable.
        }
    }

    function restoreTemplateExportState() {
        try {
            const savedState = JSON.parse(sessionStorage.getItem(templateStateKey) || 'null');
            if (!savedState) return;

            if (['all', 'selected'].includes(savedState.exportScope)) {
                $('input[name="export_scope"][value="' + savedState.exportScope + '"]').prop('checked', true);
            }
            if (['eligible', 'ineligible', 'both'].includes(savedState.eligibilityScope)) {
                $('input[name="eligibility_scope"][value="' + savedState.eligibilityScope + '"]').prop('checked', true);
            }
            if (Array.isArray(savedState.accountIds)) {
                const availableIds = savedState.accountIds.map(String).filter(function(accountId) {
                    return $accountSelect.find('option[value="' + accountId + '"]').length > 0;
                });
                $accountSelect.val(availableIds);
            }
        } catch (error) {
            sessionStorage.removeItem(templateStateKey);
        }
    }

    function updateTemplateAccountCount() {
        const count = ($accountSelect.val() || []).length;
        $accountCount.text(count + ' selected');
        $('#reset-template-account-selection').prop('disabled', count === 0);
    }

    function updateTemplateExportScope() {
        const selectedScope = $('input[name="export_scope"]:checked').val() === 'selected';
        $accountSelection.toggleClass('hidden', !selectedScope);
        $accountSelect.prop('disabled', !selectedScope);
        updateTemplateAccountCount();
    }

    function openTemplateExportModal() {
        $templateModal.removeClass('hidden');
        $('body').addClass('overflow-hidden');
        if (!$accountSelect.hasClass('select2-hidden-accessible')) {
            $accountSelect.select2({
                width: '100%',
                placeholder: 'Select Chart of Accounts',
                closeOnSelect: false,
                dropdownParent: $templateModal
            });
        }
        updateTemplateExportScope();
    }

    function closeTemplateExportModal() {
        $templateModal.addClass('hidden');
        $('body').removeClass('overflow-hidden');
    }

    $('#open-template-export-modal').on('click', openTemplateExportModal);
    $('[data-template-modal-close]').on('click', closeTemplateExportModal);
    $('input[name="export_scope"]').on('change', function() {
        updateTemplateExportScope();
        saveTemplateExportState();
    });
    $('input[name="eligibility_scope"]').on('change', saveTemplateExportState);
    $accountSelect.on('change', function() {
        updateTemplateAccountCount();
        saveTemplateExportState();
    });
    $('#reset-template-account-selection').on('click', function() {
        $accountSelect.val(null).trigger('change');
    });
    $('#create-template-coa-account').on('click', function() {
        saveTemplateExportState();
        try {
            sessionStorage.setItem(templateReopenKey, '1');
        } catch (error) {
            // The new-tab COA workflow still works without browser storage.
        }
    });
    $(document).on('keydown', function(event) {
        if (event.key === 'Escape' && !$templateModal.hasClass('hidden')) {
            closeTemplateExportModal();
        }
    });

    if (!hasServerTemplateState) {
        restoreTemplateExportState();
        updateTemplateExportScope();
    } else {
        saveTemplateExportState();
    }

    try {
        if (sessionStorage.getItem(templateReopenKey) === '1') {
            sessionStorage.removeItem(templateReopenKey);
            openTemplateExportModal();
        }
    } catch (error) {
        // Ignore storage restrictions.
    }

    @if ($errors->has('export_scope') || $errors->has('eligibility_scope') || $errors->has('account_ids') || $errors->has('account_ids.*'))
        openTemplateExportModal();
    @endif
});
</script>
@endpush
