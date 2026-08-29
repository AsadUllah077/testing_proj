@extends('layouts.auth')

@section('title', 'Logistics Suppliers')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <h1 class="text-gray-900 text-xl font-normal">Edit Supplier</h1>
                    <x-common.back-button url="{{ route('logistics.suppliers.index') }}" />
                </div>
                <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                    <form method="POST" action_url="{{ route('logistics.suppliers.update', $supplier->id) }}"
                        back_url="{{ route('logistics.suppliers.index') }}" id="formSubmit" class="p-6 space-y-6"
                        id="supplierForm">
                        @csrf
                        @method('PUT')

                        {{-- ===================== --}}
                        {{-- SECTION 1: Legal Info --}}
                        {{-- ===================== --}}
                        <x-form.form-section title="Section 1: Legal Information" :required="true">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                                <x-form.form-input name="legal_company_name" label="Legal Company Name"
                                    placeholder="e.g. Global Logistics Corp LLC" :value="$supplier->legal_company_name" />

                                <x-form.form-input name="tax_id" label="Tax ID / VAT Number" placeholder="DE 123 456 789"
                                    :value="$supplier->tax_id" />

                                <x-form.form-select name="business_type" label="Business Type" placeholder="Select type..."
                                    :options="[
                                        'sole_proprietorship' => 'Sole Proprietorship',
                                        'partnership' => 'Partnership',
                                        'llc' => 'LLC',
                                        'corporation' => 'Corporation',
                                        'cooperative' => 'Cooperative',
                                    ]" :selected="$supplier->business_type" />

                                <x-form.form-select name="country_of_registration" label="Country of Registration"
                                    placeholder="Select country..." :options="[
                                        'US' => 'United States',
                                        'GB' => 'United Kingdom',
                                        'DE' => 'Germany',
                                        'FR' => 'France',
                                        'CN' => 'China',
                                        'IN' => 'India',
                                        'PK' => 'Pakistan',
                                    ]" :selected="$supplier->country_of_registration" />

                            </div>
                        </x-form.form-section>

                        {{-- ========================= --}}
                        {{-- SECTION 2: Contact Details --}}
                        {{-- ========================= --}}
                        <x-form.form-section title="Section 2: Contact Details">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                                <x-form.form-input name="primary_contact_name" label="Primary Contact Name"
                                    placeholder="John Smith" :value="$supplier->primary_contact_name" />

                                <x-form.form-input name="official_email" label="Official Email" type="email"
                                    placeholder="contact@company.com" :value="$supplier->official_email" />

                            </div>
                            <div class="px-6 pb-6">
                                <x-form.form-textarea name="registered_office_address" label="Registered Office Address"
                                    placeholder="Street, City, State, ZIP" :value="$supplier->registered_office_address" />
                            </div>
                        </x-form.form-section>

                        {{-- ============================== --}}
                        {{-- SECTION 3: Logistics Preferences --}}
                        {{-- ============================== --}}
                        <x-form.form-section title="Section 3: Logistics Preferences">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">

                                <x-form.form-select name="default_incoterms" label="Default Incoterms" :options="[
                                    'EXW' => 'EXW - Ex Works',
                                    'FCA' => 'FCA - Free Carrier',
                                    'FOB' => 'FOB - Free On Board',
                                    'CIF' => 'CIF - Cost, Insurance & Freight',
                                    'DDP' => 'DDP - Delivered Duty Paid',
                                ]"
                                    :selected="$supplier->default_incoterms" />

                                <x-form.form-input name="pref_port_of_loading" label="Pref. Port of Loading"
                                    placeholder="Port of Hamburg" :value="$supplier->pref_port_of_loading" />

                                <x-form.form-input name="lead_time_days" label="Lead Time (Days)" type="number"
                                    placeholder="14" :value="$supplier->lead_time_days" />

                            </div>
                        </x-form.form-section>

                        {{-- ================================ --}}
                        {{-- SECTION 4: Payment Details & Docs --}}
                        {{-- ================================ --}}
                        <x-form.form-section title="Section 4: Payment Details & Documents">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 pb-0">

                                <x-form.form-input name="iban" label="IBAN / Account Number"
                                    placeholder="GB12 3456 7890 1234" :value="$supplier->iban" />

                                <x-form.form-input name="swift_bic" label="SWIFT / BIC" placeholder="ABCDGB2L"
                                    :value="$supplier->swift_bic" />

                                <x-form.form-select name="payment_currency" label="Payment Currency" :options="[
                                    'USD' => 'USD - US Dollar',
                                    'EUR' => 'EUR - Euro',
                                    'GBP' => 'GBP - British Pound',
                                    'PKR' => 'PKR - Pakistani Rupee',
                                    'CNY' => 'CNY - Chinese Yuan',
                                ]"
                                    :selected="$supplier->payment_currency" />

                            </div>

                            {{-- File Upload --}}
                            <div class="p-6 flex flex-col gap-3">
                                <label class="text-slate-700 text-sm font-semibold">Business License &
                                    Certifications</label>

                                <label for="business_license"
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
                                    <span class="text-slate-400 text-xs">PDF, JPG, PNG, ZIP or EXE (max. 10MB)</span>
                                    <input id="business_license" name="business_license" type="file" class="hidden"
                                        accept=".pdf,.jpg,.jpeg,.png,.zip,.exe">
                                </label>

                                {{-- Uploaded File Preview --}}
                                <div id="filePreview"
                                    class="{{ $supplier->business_license_path ? '' : 'hidden' }} flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                                    <svg class="w-4 h-5 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5H7Zm5 1.5L18.5 11H14a2 2 0 0 1-2-2V4.5Z" />
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <p id="fileName" class="text-slate-700 text-sm font-medium truncate">
                                            {{ $supplier->business_license_path ? 'Current Document (' . basename($supplier->business_license_path) . ')' : '' }}
                                        </p>
                                        <p id="fileStatus"
                                            class="text-[10px] leading-4 {{ $supplier->business_license_path ? 'text-green-600' : 'text-slate-400' }}">
                                            {{ $supplier->business_license_path ? 'Uploaded' : '' }}
                                        </p>
                                        <div
                                            class="h-1 bg-slate-200 rounded-full mt-1 overflow-hidden {{ $supplier->business_license_path ? 'hidden' : '' }}">
                                            <div id="progressBar" class="h-1 bg-blue-600 rounded-full" style="width: 0%">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="removeFile" class="p-1 text-slate-400 hover:text-slate-600">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                            </div>
                        </x-form.form-section>

                        {{-- Submit --}}
                        <x-common.button url="{{ route('logistics.suppliers.index') }}" type="submit" />
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('business_license');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileStatus = document.getElementById('fileStatus');
            const progressBar = document.getElementById('progressBar');
            const removeBtn = document.getElementById('removeFile');

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Show preview
                    filePreview.classList.remove('hidden');
                    fileName.textContent = file.name;

                    // Format size
                    const size = (file.size / 1024 / 1024).toFixed(2);
                    fileStatus.textContent = `${size} MB • Uploading...`;

                    // Simulate upload progress
                    progressBar.style.width = '0%';
                    progressBar.parentElement.classList.remove('hidden');
                    fileStatus.classList.remove('text-green-600');
                    fileStatus.classList.add('text-slate-400');

                    let width = 0;
                    const interval = setInterval(function() {
                        if (width >= 100) {
                            clearInterval(interval);
                            fileStatus.textContent = `${size} MB • Completed`;
                            fileStatus.classList.remove('text-slate-400');
                            fileStatus.classList.add('text-green-600');
                            progressBar.parentElement.classList.add('hidden');
                        } else {
                            width += 10;
                            progressBar.style.width = width + '%';
                        }
                    }, 100);
                }
            });

            removeBtn.addEventListener('click', function() {
                fileInput.value = '';
                // Only hide if we aren't showing an existing file (though logic here might just hide it regardless for simplicity on "remove")
                filePreview.classList.add('hidden');
                fileName.textContent = '';
            });
        });
    </script>
@endpush
