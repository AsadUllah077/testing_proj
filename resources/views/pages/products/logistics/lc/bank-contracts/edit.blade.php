@extends('layouts.auth')

@section('title', 'Edit Bank Contract')

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
                        <h1 class="text-gray-900 text-xl font-normal">Edit Bank Contract — {{ $contract->lc_number }}</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Long-term banking facility agreement</p>
                    </div>
                    <div class="flex gap-2">
                        <x-common.back-button url="{{ route('logistics.lc.bank-contracts.show', $contract->id) }}"
                            text="View" />
                        <x-common.back-button url="{{ route('logistics.lc.bank-contracts.index') }}" />
                    </div>
                </div>

                <form method="POST" action_url="{{ route('logistics.lc.bank-contracts.update', $contract->id) }}"
                    back_url="{{ route('logistics.lc.bank-contracts.index') }}" id="formSubmit" class="space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Contract & Bank Details --}}
                    <x-form.form-section title="Contract & Bank Details" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-input name="contract_number" label="Contract Number" :required="true"
                                placeholder="Enter contract number" :value="old('contract_number', $contract->contract_number ?? '')" />

                            <x-form.form-select name="issuing_bank" label="Issuing Bank" :required="true"
                                placeholder="Select bank..." :options="config('logistics.banks', [])"
                                :selected="old('issuing_bank', $contract->issuing_bank ?? '')" class="select2" />

                            <x-form.form-input name="applicant" label="Applicant" :required="true"
                                placeholder="Your company name" :value="old('applicant', $contract->applicant ?? '')" />

                            <x-form.form-input name="beneficiary" label="Beneficiary" :required="true"
                                placeholder="Counterparty name" :value="old('beneficiary', $contract->beneficiary ?? '')" />

                            <x-form.form-input name="advising_bank" label="Advising Bank" placeholder="e.g. HSBC London"
                                :value="old('advising_bank', $contract->advising_bank ?? '')" />

                            <x-form.form-input name="reimbursing_bank" label="Reimbursing Bank"
                                placeholder="Usually Citibank or correspondent" :value="old('reimbursing_bank', $contract->reimbursing_bank ?? '')" />

                        </div>
                    </x-form.form-section>

                    {{-- Financial Terms --}}
                    <x-form.form-section title="Financial Terms">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-input name="amount" label="Contract Amount" :required="true" type="number"
                                placeholder="e.g. 500000" :value="old('amount', $contract->amount ?? '')" />

                            <x-form.form-select name="currency" label="Currency" :required="true"
                                placeholder="Select currency..." :options="[
                                    'USD' => 'USD',
                                    'GBP' => 'GBP',
                                    'EUR' => 'EUR',
                                    'AED' => 'AED',
                                    'PKR' => 'PKR',
                                ]" :selected="old('currency', $contract->currency ?? 'USD')" />

                            <x-form.form-input name="tolerance_pct" label="Tolerance % (+/-)" type="number"
                                placeholder="e.g. 5" :value="old('tolerance_pct', $contract->tolerance_pct ?? '')" />

                            <x-form.form-select name="payment_terms" label="Payment Terms" placeholder="Select terms..."
                                :options="[
                                    'at_sight' => 'At Sight',
                                    '30_days_usance' => '30 Days Usance',
                                    '60_days_usance' => '60 Days Usance',
                                    '90_days_usance' => '90 Days Usance',
                                    'deferred_payment' => 'Deferred Payment',
                                    'DAD' => 'DAD',
                                ]" :selected="old('payment_terms', $contract->payment_terms ?? '')" />

                            <x-form.form-input name="issue_date" label="Issue Date" :required="true" type="date"
                                :value="old('issue_date', $contract->issue_date?->format('Y-m-d') ?? '')" />

                            <x-form.form-input name="expiry_date" label="Expiry Date" :required="true" type="date"
                                :value="old('expiry_date', $contract->expiry_date?->format('Y-m-d') ?? '')" />

                            <x-form.form-input name="po_pi_indented_no" label="PO/PI/Indented No"
                                placeholder="Enter PO/PI/Indented No" :value="old('po_pi_indented_no', $contract->po_pi_indented_no ?? '')" />

                            <x-form.form-input name="date_in_document" label="Date in Document" type="date"
                                :value="old('date_in_document', $contract->date_in_document?->format('Y-m-d') ?? '')" />

                            <x-form.form-input name="booking_number" label="Booking Number"
                                placeholder="Enter Booking Number" :value="old('booking_number', $contract->booking_number ?? '')" />

                            <x-form.form-input name="booking_date" label="Booking Date" type="date"
                                :value="old('booking_date', $contract->booking_date?->format('Y-m-d') ?? '')" />

                            <x-form.form-input name="latest_shipment_date" label="Latest Shipment Date" type="date"
                                :value="old(
                                    'latest_shipment_date',
                                    $contract->latest_shipment_date?->format('Y-m-d') ?? '',
                                )" />

                        </div>
                    </x-form.form-section>

                    {{-- Goods & Shipping --}}
                    <x-form.form-section title="Goods & Shipping">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <div class="md:col-span-2">
                                <x-form.form-textarea name="goods_description" label="Description of Goods"
                                    placeholder="Describe the goods or services" :value="old('goods_description', $contract->goods_description ?? '')" :rows="3" />
                            </div>

                            <x-form.form-input name="port_of_loading" label="Port of Loading"
                                placeholder="e.g. Karachi, Port Qasim" :value="old('port_of_loading', $contract->port_of_loading ?? '')" />

                            <x-form.form-input name="port_of_discharge" label="Port of Discharge"
                                placeholder="e.g. Dubai Jebel Ali" :value="old('port_of_discharge', $contract->port_of_discharge ?? '')" />

                            <x-form.form-select name="incoterms" label="Incoterms" placeholder="Select incoterms..."
                                :options="config('logistics.bank_contract_incoterms', [])"
                                :selected="old('incoterms', $contract->incoterms ?? '')" />

                        </div>
                    </x-form.form-section>

                    {{-- Required Documents --}}
                    <x-form.form-section title="Required Documents">
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @php
                                    $documents = [
                                        'doc_commercial_invoice' => 'Commercial Invoice',
                                        'doc_bill_of_lading' => 'Bill of Lading',
                                        'doc_packing_list' => 'Packing List',
                                        'doc_certificate_of_origin' => 'Certificate of Origin',
                                        'doc_insurance_certificate' => 'Insurance Certificate',
                                        'doc_phytosanitary' => 'Phytosanitary Certificate',
                                        'doc_inspection_certificate' => 'Inspection Certificate',
                                        'doc_weight_certificate' => 'Weight Certificate',
                                    ];
                                    $defaultChecked = [
                                        'doc_commercial_invoice',
                                        'doc_bill_of_lading',
                                        'doc_packing_list',
                                    ];
                                @endphp

                                @foreach ($documents as $name => $label)
                                    <label
                                        class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors">
                                        <input type="checkbox" name="documents_required[]" value="{{ $name }}"
                                            {{ in_array($name, old('documents_required', $contract->documents_required ? explode(',', $contract->documents_required) : $defaultChecked)) ? 'checked' : '' }}
                                            class="w-4 h-4 rounded border-slate-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                                        <span class="text-sm text-slate-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </x-form.form-section>

                    {{-- Status --}}
                    <x-form.form-section title="Status">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                            <x-form.form-select name="status" label="Contract Status" :required="true" :options="[
                                'Draft' => 'Draft',
                                'Active' => 'Active',
                                'Expiring Soon' => 'Expiring Soon',
                                'Settled' => 'Settled',
                                'Expired' => 'Expired',
                                'Cancelled' => 'Cancelled',
                            ]"
                                :selected="old('status', $contract->status ?? 'Draft')" />
                        </div>
                    </x-form.form-section>

                    {{-- Special Conditions --}}
                    <x-form.form-section title="Special Conditions">
                        <div class="p-6">
                            <x-form.form-textarea name="special_conditions"
                                label="Additional Instructions / Special Conditions"
                                placeholder="Any additional clauses or bank-specific conditions..." :value="old('special_conditions', $contract->special_conditions ?? '')"
                                :rows="4" />
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('logistics.lc.bank-contracts.index') }}" type="submit" />

                </form>
            </div>
        </div>
    </div>
@endsection
