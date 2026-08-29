@extends('layouts.auth')

@section('title', 'Logistics Entitys')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <h1 class="text-gray-900 text-xl font-normal">Entity Registration from Importer / Exporter</h1>
                    <x-common.back-button url="{{ route('logistics.entities.index') }}" />
                </div>
                <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                    <form method="POST" action_url="{{ route('logistics.entities.store') }}"
                        back_url="{{ route('logistics.entities.index') }}" id="formSubmit" class="p-6 space-y-6">
                        @csrf

                        {{-- Entity Information --}}
                        <x-form.form-section title="Entity Information" :required="true">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                                <x-form.form-input name="business_legal_name" label="Business Legal Name"
                                    placeholder="Enter full legal business name" :value="old('business_legal_name')" />

                                <x-form.form-select name="entity_type" label="Entity Type" placeholder="Select type..."
                                    :options="[
                                        'Importer' => 'Importer',
                                        'Exporter' => 'Exporter',
                                        'Both' => 'Both',
                                    ]" :selected="old('entity_type')" />

                                <x-form.form-input name="tax_id_vat_number" label="Tax ID / VAT Number"
                                    placeholder="e.g. US123456789" :value="old('tax_id_vat_number')" />

                                <x-form.form-input name="registration_country" label="Registration Country"
                                    placeholder="Enter country" :value="old('registration_country')" />
                            </div>
                        </x-form.form-section>

                        {{-- Contact Details --}}
                        <x-form.form-section title="Contact Details" :required="true">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                                <x-form.form-input name="contact_full_name" label="Full Name" placeholder="John Doe"
                                    :value="old('contact_full_name')" />

                                <x-form.form-input name="contact_email" label="Email Address"
                                    placeholder="john.doe@entity.com" :value="old('contact_email')" />

                                <x-form.form-input name="contact_phone_number" label="Phone Number"
                                    placeholder="(555) 000-0000" :value="old('contact_phone_number')" />
                            </div>
                        </x-form.form-section>

                        {{-- Registered Address --}}
                        <x-form.form-section title="Registered Address" :required="true">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                                <div class="col-span-1 md:col-span-2">
                                    <x-form.form-input name="registered_street_address" label="Street Address"
                                        placeholder="123 Business Way, Suite 100" :value="old('registered_street_address')" />
                                </div>
                                <x-form.form-input name="registered_city" label="City" placeholder="City"
                                    :value="old('registered_city')" />
                                <x-form.form-input name="registered_state_province" label="State / Province"
                                    placeholder="State/Province" :value="old('registered_state_province')" />
                                <x-form.form-input name="registered_postal_code" label="Postal Code"
                                    placeholder="e.g. 90210" :value="old('registered_postal_code')" />
                            </div>
                        </x-form.form-section>

                        {{-- Mailing Address --}}
                        <x-form.form-section title="Mailing Address" :required="false">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                                <div class="col-span-1 md:col-span-2">
                                    <x-form.form-input name="mailing_street_address" label="Street Address"
                                        placeholder="123 Business Way, Suite 100" :value="old('mailing_street_address')" />
                                </div>
                                <x-form.form-input name="mailing_city" label="City" placeholder="City"
                                    :value="old('mailing_city')" />
                                <x-form.form-input name="mailing_state_province" label="State / Province"
                                    placeholder="State/Province" :value="old('mailing_state_province')" />
                                <x-form.form-input name="mailing_postal_code" label="Postal Code" placeholder="e.g. 90210"
                                    :value="old('mailing_postal_code')" />
                            </div>
                        </x-form.form-section>

                        <x-common.button url="{{ route('logistics.entities.index') }}" type="submit" />
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
