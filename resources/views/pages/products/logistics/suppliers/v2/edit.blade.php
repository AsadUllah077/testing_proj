@extends('layouts.auth')

@section('title', 'Edit Supplier')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div>
                        <h1 class="text-gray-900 text-xl font-normal">Edit Supplier</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Update supplier details and compliance documentation</p>
                    </div>
                    <x-common.back-button url="{{ route('logistics.suppliers.v2.index') }}" />
                </div>

                <form method="POST" action_url="{{ route('logistics.suppliers.v2.update', $supplier->id) }}"
                    back_url="{{ route('logistics.suppliers.v2.index') }}" id="formSubmit" class="space-y-4"
                    enctype="multipart/form-data">
                    @csrf

                    {{-- Company Details --}}
                    <x-form.form-section title="🏭 Company Details" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                            <x-form.form-input name="company_name" label="Company Name" :required="true"
                                placeholder="Legal registered name" :value="old('company_name', $supplier->company_name)" />

                            <x-form.form-input name="trade_name" label="Trade / Brand Name"
                                placeholder="If different from registered name" :value="old('trade_name', $supplier->trade_name)" />

                            <x-form.form-select name="business_type" label="Business Type" :required="true"
                                placeholder="Select type..." :options="[
                                    'Manufacturer / Exporter' => 'Manufacturer / Exporter',
                                    'Trading Company' => 'Trading Company',
                                    'Buying Agent' => 'Buying Agent',
                                    'Importer / Distributor' => 'Importer / Distributor',
                                    'Service Provider' => 'Service Provider',
                                ]" :selected="old('business_type', $supplier->business_type)" />

                            <x-form.form-select name="category" label="Product / Commodity Category" :required="true"
                                placeholder="Select category..." :options="[
                                    'Textiles & Garments' => 'Textiles & Garments',
                                    'Surgical Instruments' => 'Surgical Instruments',
                                    'Sports Goods' => 'Sports Goods',
                                    'Rice / Agri Commodities' => 'Rice / Agri Commodities',
                                    'Electronics / Components' => 'Electronics / Components',
                                    'Chemicals / Industrial' => 'Chemicals / Industrial',
                                    'Machinery & Equipment' => 'Machinery & Equipment',
                                    'Pharmaceuticals' => 'Pharmaceuticals',
                                    'Leather Goods' => 'Leather Goods',
                                    'Cement / Construction' => 'Cement / Construction',
                                    'Other' => 'Other',
                                ]" :selected="old('category', $supplier->category)" />

                            <x-form.form-select name="country" label="Country" :required="true"
                                placeholder="Select country..." :options="[
                                    'Pakistan' => 'Pakistan',
                                    'China' => 'China',
                                    'UAE' => 'UAE',
                                    'Germany' => 'Germany',
                                    'United Kingdom' => 'United Kingdom',
                                    'Turkey' => 'Turkey',
                                    'USA' => 'USA',
                                    'India' => 'India',
                                    'Bangladesh' => 'Bangladesh',
                                    'Other' => 'Other',
                                ]" :selected="old('country', $supplier->country)" />

                            <x-form.form-input name="city" label="City / Province" placeholder="e.g. Lahore, Punjab"
                                :value="old('city', $supplier->city)" />

                            <x-form.form-input name="registration_no" label="Registration / NTN No." :required="true"
                                placeholder="Pakistan NTN or foreign VAT reg." :value="old('registration_no', $supplier->registration_no)" />

                            <x-form.form-input name="strn_no" label="STRN (Sales Tax Reg. No.)"
                                placeholder="Pakistan STRN if applicable" :value="old('strn_no', $supplier->strn_no)" />

                            <x-form.form-input name="year_established" label="Year Established" type="number"
                                placeholder="e.g. 2005" :value="old('year_established', $supplier->year_established)" />

                            <x-form.form-input name="annual_value" label="Annual Export / Supply Value (USD)" type="number"
                                placeholder="e.g. 500000" :value="old('annual_value', $supplier->annual_value)" />
                        </div>
                    </x-form.form-section>

                    {{-- Key Contacts --}}
                    <x-form.form-section title="👤 Key Contacts" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                            <x-form.form-input name="primary_contact" label="Primary Contact Name" :required="true"
                                placeholder="Owner / Director / Export Manager" :value="old('primary_contact', $supplier->primary_contact)" />

                            <x-form.form-input name="designation" label="Designation" placeholder="e.g. Director Exports"
                                :value="old('designation', $supplier->designation)" />

                            <x-form.form-input name="email" label="Email" :required="true" type="email"
                                placeholder="contact@supplier.com" :value="old('email', $supplier->email)" />

                            <x-form.form-input name="phone" label="Phone / WhatsApp" :required="true"
                                placeholder="+92-…" :value="old('phone', $supplier->phone)" />

                            <x-form.form-input name="finance_contact" label="Finance / Accounts Contact"
                                placeholder="For LC / payment coordination" :value="old('finance_contact', $supplier->finance_contact)" />

                            <x-form.form-input name="logistics_contact" label="Logistics Contact"
                                placeholder="For shipment coordination" :value="old('logistics_contact', $supplier->logistics_contact)" />
                        </div>
                    </x-form.form-section>

                    {{-- Commercial Terms --}}
                    <x-form.form-section title="💰 Commercial Terms" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                            <x-form.form-select name="incoterms" label="Preferred Incoterms" :required="true"
                                placeholder="Select incoterms..." :options="[
                                    'FOB' => 'FOB',
                                    'CIF' => 'CIF',
                                    'CFR' => 'CFR',
                                    'EXW' => 'EXW',
                                    'DAP' => 'DAP',
                                    'FCA' => 'FCA',
                                ]" :selected="old('incoterms', $supplier->incoterms)" />

                            <x-form.form-select name="payment_terms" label="Payment Terms Requested"
                                placeholder="Select terms..." :options="[
                                    'LC at Sight' => 'LC at Sight',
                                    'LC 30 days' => 'LC 30 days',
                                    'LC 60 days' => 'LC 60 days',
                                    'TT 30 days after BL' => 'TT 30 days after BL',
                                    'TT 50% advance + 50% BL' => 'TT 50% advance + 50% BL',
                                    'TT 30% advance + 70% on delivery' => 'TT 30% advance + 70% on delivery',
                                    'Open Account 30 days' => 'Open Account 30 days',
                                ]" :selected="old('payment_terms', $supplier->payment_terms)" />

                            <x-form.form-select name="currency" label="Currency" :required="true"
                                placeholder="Select currency..." :options="[
                                    'USD' => 'USD',
                                    'PKR' => 'PKR',
                                    'EUR' => 'EUR',
                                    'AED' => 'AED',
                                    'GBP' => 'GBP',
                                ]" :selected="old('currency', $supplier->currency)" />

                            <x-form.form-input name="credit_limit" label="Credit Limit Requested (USD)" type="number"
                                placeholder="e.g. 100000" :value="old('credit_limit', $supplier->credit_limit)" />

                            <x-form.form-input name="moq" label="Min Order Quantity (MOQ)"
                                placeholder="e.g. 1×20ft or 10,000 pcs" :value="old('moq', $supplier->moq)" />

                            <x-form.form-input name="lead_time" label="Lead Time (Days)" type="number"
                                placeholder="e.g. 30 days production" :value="old('lead_time', $supplier->lead_time)" />
                        </div>
                    </x-form.form-section>

                    {{-- Actions --}}
                    <div class="bg-white rounded-lg border border-black/10 px-6 py-4 flex items-center justify-end gap-3">
                        <a href="{{ route('logistics.suppliers.v2.index') }}"
                            class="px-5 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-5 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                            Update Supplier <i class="fa-solid fa-check text-[10px]"></i>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
