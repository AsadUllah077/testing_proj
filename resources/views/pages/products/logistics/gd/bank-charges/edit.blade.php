@extends('layouts.auth')

@section('title', 'Edit Bank Charge')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div>
                        <h1 class="text-gray-900 text-xl font-normal">Edit Bank Charge — {{ $charge->ref ?? 'BCH-2026-001' }}</h1>
                        <p class="text-xs text-gray-400 mt-0.5">All LC-related and trade finance charges</p>
                    </div>
                    <div class="flex gap-2">
                        <x-common.back-button url="{{ route('logistics.gd.bank-charges.show', $charge->id ?? 1) }}" text="View" />
                        <x-common.back-button url="{{ route('logistics.gd.bank-charges.index') }}" />
                    </div>
                </div>

                <form method="POST" action_url="{{ route('logistics.gd.bank-charges.update', $charge->id ?? 1) }}"
                    back_url="{{ route('logistics.gd.bank-charges.index') }}" id="formSubmit" class="space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Charge Details --}}
                    <x-form.form-section title="Charge Details" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-select name="charge_type" label="Charge Type" :required="true"
                                placeholder="Select charge type..."
                                :options="[
                                    'lc_opening_commission' => 'LC Opening Commission',
                                    'lc_amendment_fee'      => 'LC Amendment Fee',
                                    'swift_telex'           => 'SWIFT / Telex Charges',
                                    'acceptance_commission' => 'Acceptance Commission',
                                    'discrepancy_fee'       => 'Discrepancy Fee',
                                    'bank_guarantee_fee'    => 'Bank Guarantee Fee',
                                    'negotiation_fee'       => 'Negotiation Fee',
                                    'reimbursement_fee'     => 'Reimbursement Fee',
                                    'other'                 => 'Other',
                                ]"
                                :selected="old('charge_type', $charge->charge_type ?? '')" />

                            <x-form.form-select name="bank" label="Bank" :required="true"
                                placeholder="Select bank..."
                                :options="[
                                    'hbl' => 'HBL — Habib Bank',
                                    'mcb' => 'MCB Bank',
                                    'ubl' => 'UBL',
                                    'sc'  => 'Standard Chartered',
                                    'mbl' => 'Meezan Bank',
                                    'abl' => 'Allied Bank',
                                ]"
                                :selected="old('bank', $charge->bank ?? '')" />

                            <x-form.form-input name="linked_lc" label="Linked LC / Reference"
                                placeholder="e.g. LC-2026-0041"
                                :value="old('linked_lc', $charge->linked_lc ?? '')" />

                            <x-form.form-input name="charge_date" label="Date" :required="true" type="date"
                                :value="old('charge_date', $charge->charge_date ?? '')" />

                        </div>
                    </x-form.form-section>

                    {{-- Amount & Currency --}}
                    <x-form.form-section title="Amount & Currency" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-input name="amount_pkr" label="Amount (PKR)" :required="true" type="number"
                                placeholder="e.g. 25000"
                                :value="old('amount_pkr', $charge->amount_pkr ?? '')" />

                            <x-form.form-input name="amount_usd" label="Amount (USD)"
                                placeholder="Optional — if charged in USD"
                                :value="old('amount_usd', $charge->amount_usd ?? '')" />

                            <x-form.form-select name="payment_mode" label="Payment Mode"
                                placeholder="Select mode..."
                                :options="[
                                    'debit_account' => 'Debit from Account',
                                    'pay_order'     => 'Pay Order',
                                    'cash'          => 'Cash',
                                    'offset'        => 'Offset Against LC',
                                ]"
                                :selected="old('payment_mode', $charge->payment_mode ?? 'debit_account')" />

                            <x-form.form-input name="bank_reference" label="Bank Reference / Receipt No."
                                placeholder="e.g. HBL/TF/2026/4421"
                                :value="old('bank_reference', $charge->bank_reference ?? '')" />

                        </div>
                    </x-form.form-section>

                    {{-- Remarks --}}
                    <x-form.form-section title="Remarks">
                        <div class="p-6">
                            <x-form.form-textarea name="remarks" label="Notes / Remarks"
                                placeholder="Any additional details, breakdown, or memo..."
                                :value="old('remarks', $charge->remarks ?? '')" :rows="3" />
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('logistics.gd.bank-charges.index') }}" type="submit" />

                </form>
            </div>
        </div>
    </div>
@endsection
