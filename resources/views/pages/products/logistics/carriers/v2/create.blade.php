@extends('layouts.auth')

@section('title', 'New Carrier')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-semibold">Onboard New Carrier</h1>
                    <p class="text-gray-500 text-sm mt-0.5">Complete all sections to add carrier to the approved vendor list
                    </p>
                </div>
                <x-common.back-button url="{{ route('logistics.carriers.v2.index') }}" />
            </div>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="text-sm text-red-600 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('logistics.carriers.v2.store') }}" id="carrierForm"
                enctype="multipart/form-data">
                @csrf

                {{-- Company Information --}}
                <div class="mt-2">
                    <x-form.form-section title="Company Information" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-input name="carrier_company_name" label="Carrier / Company Name"
                                placeholder="Official registered name" :required="true" :value="old('carrier_company_name')" />

                            <x-form.form-input name="trading_brand_name" label="Trading / Brand Name"
                                placeholder="e.g. Maersk, MSC" :value="old('trading_brand_name')" />

                            <x-form.form-select name="transport_modes" label="Transport Mode(s)" placeholder="Select mode"
                                :required="true" :options="\App\Models\LogisticsV2Carriers::TRANSPORT_MODES" :selected="old('transport_modes', 'ocean_liner')" />

                            <x-form.form-input name="country_of_incorporation" label="Country of Incorporation"
                                placeholder="e.g. Denmark, Switzerland" :required="true" :value="old('country_of_incorporation')" />

                            <x-form.form-input name="pakistan_liaison_office" label="Pakistan Liaison Office"
                                placeholder="City (KHI / LHE / ISB)" :value="old('pakistan_liaison_office')" />

                            <x-form.form-input name="ntn_scac_code" label="NTN (Pakistan Tax) / SCAC Code"
                                placeholder="NTN or SCAC carrier code" :value="old('ntn_scac_code')" />

                            <x-form.form-input name="contact_person" label="Contact Person"
                                placeholder="Primary contact at carrier" :required="true" :value="old('contact_person')" />

                            <x-form.form-input name="contact_email" label="Contact Email" type="email"
                                placeholder="agent@carrier.com" :required="true" :value="old('contact_email')" />

                            <x-form.form-input name="phone_pakistan_office" label="Phone (Pakistan Office)"
                                placeholder="+92-21-..." :value="old('phone_pakistan_office')" />

                            <x-form.form-input name="website" label="Website" type="url" placeholder="https://"
                                :value="old('website')" />

                        </div>
                    </x-form.form-section>
                </div>

                {{-- Service Coverage --}}
                <div class="mt-2">
                    <x-form.form-section title="Service Coverage">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-input name="key_origin_ports_airports" label="Key Origin Ports / Airports"
                                placeholder="e.g. KHI, LHE, ISB" :value="old('key_origin_ports_airports')" />

                            <x-form.form-input name="key_destination_markets" label="Key Destination Markets"
                                placeholder="e.g. UK, UAE, EU, USA, China" :value="old('key_destination_markets')" />

                            <x-form.form-select name="services_offered" label="Services Offered"
                                placeholder="Select service" :options="\App\Models\LogisticsV2Carriers::SERVICES_OFFERED" :selected="old('services_offered', 'fcl_sea')" />

                            <x-form.form-select name="tracking_portal_api" label="Tracking Portal / API Available?"
                                placeholder="Select option" :options="\App\Models\LogisticsV2Carriers::TRACKING_PORTAL_OPTIONS" :selected="old('tracking_portal_api', 'yes_web_portal')" />

                        </div>
                    </x-form.form-section>
                </div>

                {{-- Compliance & Documents --}}
                <div class="mt-2">
                    <x-form.form-section title="Compliance & Documents">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-select name="fiata_iata_membership" label="FIATA / IATA Membership"
                                placeholder="Select..." :options="\App\Models\LogisticsV2Carriers::FIATA_IATA_OPTIONS" :selected="old('fiata_iata_membership')" />

                            <x-form.form-select name="insurance_marine_liability" label="Insurance (Marine Liability)"
                                placeholder="Select..." :options="\App\Models\LogisticsV2Carriers::INSURANCE_OPTIONS" :selected="old('insurance_marine_liability')" />

                            <x-form.form-input name="aeo_ctpat_iso_certification" label="AEO / C-TPAT / ISO Certification"
                                placeholder="e.g. ISO 9001:2015, AEO-F" :value="old('aeo_ctpat_iso_certification')" />

                            <x-form.form-select name="payment_terms" label="Payment Terms" placeholder="Select..."
                                :required="true" :options="\App\Models\LogisticsV2Carriers::PAYMENT_TERMS" :selected="old('payment_terms', 'net_30')" />

                            <x-form.form-input name="credit_limit_usd" label="Credit Limit (USD)" type="number"
                                placeholder="e.g. 50000" :value="old('credit_limit_usd')" />

                            <x-form.form-input name="account_manager_internal" label="Account Manager (Internal)"
                                placeholder="Who manages this carrier internally" :value="old('account_manager_internal')" />

                            <div class="md:col-span-2">
                                <x-form.form-textarea name="notes_onboarding_remarks" label="Notes / Onboarding Remarks"
                                    placeholder="Any special arrangements, negotiation notes, referral source..."
                                    rows="4" :value="old('notes_onboarding_remarks')" />
                            </div>

                            {{-- Liability Insurance Upload --}}
                            <div class="md:col-span-2 flex flex-col gap-3">
                                <label class="text-slate-700 text-sm font-semibold">Liability Insurance</label>
                                <label for="liability_insurance"
                                    class="flex flex-col items-center justify-center gap-2 p-8 bg-slate-50 rounded-xl border-2 border-dashed border-slate-200 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                                    <div class="w-12 h-12 bg-white rounded-full shadow flex items-center justify-center">
                                        <svg class="w-5 h-6 text-slate-400" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                    <span class="text-slate-600 text-base font-medium">Click to upload or drag and
                                        drop</span>
                                    <span class="text-slate-400 text-xs">PDF, JPG, PNG — max 5 MB</span>
                                    <input id="liability_insurance" name="liability_insurance" type="file"
                                        class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                </label>

                                <div id="filePreview"
                                    class="hidden flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                                    <svg class="w-4 h-5 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5H7Zm5 1.5L18.5 11H14a2 2 0 0 1-2-2V4.5Z" />
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <p id="fileName" class="text-slate-700 text-sm font-medium truncate"></p>
                                        <p id="fileStatus" class="text-slate-400 text-[10px] leading-4"></p>
                                        <div class="h-1 bg-slate-200 rounded-full mt-1 overflow-hidden">
                                            <div id="progressBar" class="h-1 bg-blue-600 rounded-full" style="width:0%">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="removeFile"
                                        class="p-1 text-slate-400 hover:text-slate-600">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </x-form.form-section>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 mt-4 px-1 py-4">
                    <a href="{{ route('logistics.carriers.v2.index') }}"
                        class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="action" value="draft"
                        class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Save Draft
                    </button>
                    <button type="submit" name="action" value="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                        Submit for Approval
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('liability_insurance');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileStatus = document.getElementById('fileStatus');
            const progressBar = document.getElementById('progressBar');
            const removeBtn = document.getElementById('removeFile');

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                filePreview.classList.remove('hidden');
                fileName.textContent = file.name;
                const size = (file.size / 1024 / 1024).toFixed(2);
                fileStatus.textContent = `${size} MB • Uploading...`;
                progressBar.style.width = '0%';
                let width = 0;
                const interval = setInterval(function() {
                    if (width >= 100) {
                        clearInterval(interval);
                        fileStatus.textContent = `${size} MB • Ready`;
                        fileStatus.classList.replace('text-slate-400', 'text-green-600');
                    } else {
                        width += 10;
                        progressBar.style.width = width + '%';
                    }
                }, 80);
            });

            removeBtn.addEventListener('click', function() {
                fileInput.value = '';
                filePreview.classList.add('hidden');
            });
        });
    </script>
@endpush
