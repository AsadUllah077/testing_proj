<div class="p-6 space-y-6">

    {{-- Row 1: Carrier / Transport Mode / Contract Term --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- Carrier — populated from DB --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">
                Carrier <span class="text-red-500">*</span>
            </label>
            <select name="carrier_id"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 @error('carrier_id') border-red-400 @enderror">
                <option value="">Select carrier...</option>
                @foreach ($carriers as $id => $carrier_company_name)
                    <option value="{{ $id }}"
                        {{ old('carrier_id', $contract->carrier_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $carrier_company_name }}
                    </option>
                @endforeach
            </select>
            @error('carrier_id')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Transport Mode --}}
        <x-form.form-select name="transport_mode" label="Transport Mode" :required="true" placeholder="Select mode..."
            :options="$transportModes" :selected="old('transport_mode', $contract->transport_mode ?? '')" />

        {{-- Contract Term --}}
        <x-form.form-select name="contract_term" label="Contract Term" :required="true" placeholder="Select term..."
            :options="$contractTerms" :selected="old('contract_term', $contract->contract_term ?? '')" />

    </div>

    {{-- Row 2: Min OTD / Max Claim Rate / Volume Commitment --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <x-form.form-input name="min_otd_guarantee" label="Min OTD % Guarantee" type="number" step="0.01"
            :required="true" placeholder="e.g. 88" :value="old('min_otd_guarantee', $contract->min_otd_guarantee ?? '')" />

        <x-form.form-input name="max_claim_rate" label="Max Claim Rate %" type="number" step="0.01"
            :required="true" placeholder="e.g. 2.0" :value="old('max_claim_rate', $contract->max_claim_rate ?? '')" />

        <x-form.form-input name="volume_commitment" label="Volume Commitment" placeholder="e.g. 40 TEU/month"
            :value="old('volume_commitment', $contract->volume_commitment ?? '')" />

    </div>

    {{-- Row 3: Contract Start / Expiry / Rate Lock Period --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <x-form.form-input name="contract_start_date" label="Contract Start Date" type="date" :value="old('contract_start_date', isset($contract) ? $contract->contract_start_date?->format('Y-m-d') : '')" />

        <x-form.form-input name="contract_expiry_date" label="Contract Expiry Date" type="date" :value="old(
            'contract_expiry_date',
            isset($contract) ? $contract->contract_expiry_date?->format('Y-m-d') : '',
        )" />

        <x-form.form-select name="rate_lock_period" label="Rate Lock Period" placeholder="Select period..."
            :options="$rateLockPeriods" :selected="old('rate_lock_period', $contract->rate_lock_period ?? 'no_lock')" />

    </div>

    {{-- Row 4: Penalty Clause / Penalty Rate / Review Frequency --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <x-form.form-select name="penalty_clause" label="Penalty Clause" placeholder="Select clause..."
            :options="$penaltyClauses" :selected="old('penalty_clause', $contract->penalty_clause ?? 'none')" />

        <x-form.form-input name="penalty_rate_amount" label="Penalty Rate / Amount"
            placeholder="e.g. 2% rebate or PKR 5,000/day" :value="old('penalty_rate_amount', $contract->penalty_rate_amount ?? '')" />

        <x-form.form-select name="review_frequency" label="Review Frequency" placeholder="Select frequency..."
            :options="$reviewFrequencies" :selected="old('review_frequency', $contract->review_frequency ?? 'monthly')" />

    </div>

    {{-- Row 5: Status / Performance (only on edit) --}}
    @isset($contract)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <x-form.form-select name="status" label="Contract Status" :options="$statuses" :selected="old('status', $contract->status)" />

            <x-form.form-select name="performance_status" label="Performance Status" :options="$performanceStatuses"
                :selected="old('performance_status', $contract->performance_status)" />

        </div>
    @endisset

    {{-- Special Conditions --}}
    <div>
        <x-form.form-textarea name="special_conditions" label="Special Conditions"
            placeholder="Escalation process, dispute resolution, force majeure clauses..." rows="4"
            :value="old('special_conditions', $contract->special_conditions ?? '')" />
    </div>

</div>
