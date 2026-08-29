@extends('layouts.auth')

@section('title', 'Record Bank Charge')

@section('content')
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.lc') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div>
                        <h1 class="text-gray-900 text-xl font-normal">Record Bank Charge</h1>
                        <p class="text-xs text-gray-400 mt-0.5">All LC-related and trade finance charges</p>
                    </div>
                    <x-common.back-button url="{{ route('logistics.lc.bank-charges.index') }}" />
                </div>

                <form method="POST" action_url="{{ route('logistics.lc.bank-charges.store') }}"
                    back_url="{{ route('logistics.lc.bank-charges.index') }}" id="formSubmit" class="space-y-4">
                    @csrf

                    {{-- Charge Details --}}
                    <x-form.form-section title="Charge Details" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-select name="charge_type" class="select2" label="Charge Type" :required="true"
                                placeholder="Select charge type..." :options="[
            'lc_opening_commission' => 'LC Opening Commission',
            'lc_amendment_fee' => 'LC Amendment Fee',
            'swift_telex' => 'SWIFT / Telex Charges',
            'acceptance_commission' => 'Acceptance Commission',
            'discrepancy_fee' => 'Discrepancy Fee',
            'bank_guarantee_fee' => 'Bank Guarantee Fee',
            'negotiation_fee' => 'Negotiation Fee',
            'reimbursement_fee' => 'Reimbursement Fee',
            'other' => 'Other',
        ]" :selected="old('charge_type')" />

                            <x-form.form-select name="bank" label="Bank" :required="true" placeholder="Select bank..."
                                :options="$banks" :selected="old('bank')" class="select2" />

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Bank Instrument <span
                                        class="text-gray-400">(optional)</span></label>
                                <select name="lc_id"
                                    class="select2 w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    <option value="">— Select Bank Instrument —</option>
                                    @foreach ($lcs as $lc)
                                        <option value="{{ $lc->id }}" {{ old('lc_id') == $lc->id ? 'selected' : '' }}>
                                            {{ $lc->lc_number }} —
                                            {{ $lc->record_type === 'bank_contract' ? 'Bank Contract' : 'LC' }}
                                            @if ($lc->beneficiary)
                                                — {{ $lc->beneficiary }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <x-form.form-input name="charge_date" label="Date" :required="true" type="date"
                                :value="old('charge_date')" />

                        </div>
                    </x-form.form-section>

                    {{-- Amount & Currency --}}
                    <x-form.form-section title="Amount & Currency" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <input type="hidden" name="charge_currency" id="charge_currency"
                                value="{{ old('charge_currency', 'PKR') }}">

                            <p class="text-xs text-blue-600 md:col-span-2">
                                Enter either PKR or USD and the other amount will be calculated from the exchange rate.
                            </p>

                            <x-form.form-input name="amount_pkr" label="Amount (PKR)" type="number" step="0.01" min="0"
                                placeholder="Enter PKR or USD amount" :value="old('amount_pkr')" />

                            <x-form.form-input name="amount_usd" label="Amount (USD)" type="number" step="0.01" min="0"
                                placeholder="Enter USD or PKR amount" :value="old('amount_usd')" />

                            <x-form.form-input name="exchange_rate" label="Exchange Rate (USD→PKR)"
                                placeholder="e.g. 278.40" type="number" step="0.0001" min="0.0001"
                                :value="old('exchange_rate')" :required="true" />

                            <x-form.form-select name="payment_mode" label="Payment Mode" placeholder="Select mode..."
                                :options="[
            'debit_account' => 'Debit from Account',
            'pay_order' => 'Pay Order',
            'cash' => 'Cash',
            'offset' => 'Offset Against LC',
            'third_party' => 'Third Party',
        ]" :selected="old('payment_mode', 'debit_account')" />

                            <x-form.form-input name="bank_reference" label="Bank Reference / Receipt No."
                                placeholder="e.g. HBL/TF/2026/4421" :value="old('bank_reference')" />

                        </div>
                    </x-form.form-section>

                    {{-- Remarks --}}
                    <x-form.form-section title="Remarks">
                        <div class="p-6">
                            <x-form.form-textarea name="remarks" label="Notes / Remarks"
                                placeholder="Any additional details, breakdown, or memo..." :value="old('remarks')"
                                :rows="3" />
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('logistics.lc.bank-charges.index') }}" type="submit" />

                </form>
            </div>
        </div>
    </div>
    @include('pages.products.logistics.lc.bank-charges.partials.currency-calculator')
@endsection
