@php
    $isEdit = isset($lc) && $lc;

    $value = fn (string $field, $fallback = '') => old($field, $isEdit ? ($lc->{$field} ?? $fallback) : $fallback);
    $dateValue = fn (string $field) => old($field, $isEdit && $lc->{$field} ? $lc->{$field}->format('Y-m-d') : '');

    $bankOptions = [
        'hbl' => 'HBL - Habib Bank Limited',
        'ubl' => 'UBL - United Bank Limited',
        'mcb' => 'MCB Bank Limited',
        'abl' => 'ABL - Allied Bank Limited',
        'nbp' => 'NBP - National Bank of Pakistan',
        'meezan' => 'Meezan Bank Limited',
        'bank_alfalah' => 'Bank Alfalah Limited',
        'bank_al_habib' => 'Bank AL Habib Limited',
        'faysal' => 'Faysal Bank Limited',
        'askari' => 'Askari Bank Limited',
        'habib_metropolitan' => 'Habib Metropolitan Bank Limited',
        'standard_chartered' => 'Standard Chartered Bank Pakistan',
        'bankislami' => 'BankIslami Pakistan Limited',
        'dubai_islamic' => 'Dubai Islamic Bank Pakistan',
        'al_baraka' => 'Al Baraka Bank Pakistan',
        'albaraka' => 'Al Baraka Bank Pakistan',
        'mcb_islamic' => 'MCB Islamic Bank Limited',
        'js_bank' => 'JS Bank Limited',
        'js' => 'JS Bank Limited',
        'soneri' => 'Soneri Bank Limited',
        'silkbank' => 'Silkbank Limited',
        'silk' => 'Silkbank Limited',
        'summit' => 'Summit Bank Limited',
        'samba' => 'Samba Bank Limited',
        'ztbl' => 'ZTBL - Zarai Taraqiati Bank Limited',
        'zarai_taraqiati' => 'ZTBL - Zarai Taraqiati Bank Limited',
        'pak_kuwait' => 'Pak Kuwait Investment Company',
        'khushhali' => 'Khushhali Microfinance Bank',
        'mobilink' => 'Mobilink Microfinance Bank',
        'telenor' => 'Telenor Microfinance Bank',
    ];

    $documents = [
        'doc_commercial_invoice' => ['Commercial Invoice', '3 originals + 3 copies'],
        'doc_bill_of_lading' => ['Clean On-Board Bill of Lading', 'Full set'],
        'doc_packing_list' => ['Packing List', 'Shipment quantities'],
        'doc_certificate_of_origin' => ['Certificate of Origin', 'SAARC / GSP'],
        'doc_insurance_certificate' => ['Insurance Certificate', 'CIF only'],
        'doc_phytosanitary' => ['Phytosanitary / Health Certificate', 'Where applicable'],
        'doc_inspection_certificate' => ['Inspection Certificate', 'SGS / Intertek'],
        'doc_weight_certificate' => ['Weight / Analysis Certificate', 'Cargo verification'],
    ];

    $defaultChecked = [
        'doc_commercial_invoice',
        'doc_bill_of_lading',
        'doc_packing_list',
        'doc_certificate_of_origin',
    ];

    $selectedDocuments = old(
        'documents_required',
        $isEdit && $lc->documents_required ? explode(',', $lc->documents_required) : $defaultChecked
    );
@endphp

<x-form.form-section title="LC & Bank Details" :required="true">
    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 xl:grid-cols-3">
        <x-form.form-select name="lc_type" label="LC Type" :required="true" placeholder="Select type..."
            :options="[
                'irrevocable' => 'Irrevocable',
                'irrevocable_confirmed' => 'Irrevocable Confirmed',
                'standby' => 'Standby LC',
                'transferable' => 'Transferable',
                'back_to_back' => 'Back-to-Back',
            ]" :selected="$value('lc_type')" />

        <x-form.form-select name="issuing_bank" label="Issuing Bank" :required="true" placeholder="Select bank..."
            :options="$bankOptions" :selected="$value('issuing_bank')" />

        <x-form.form-input name="applicant" label="Applicant (Importer)" :required="true"
            placeholder="Your company name" :value="$value('applicant')" />

        <x-form.form-input name="beneficiary" label="Beneficiary (Exporter)" :required="true"
            placeholder="Supplier company name" :value="$value('beneficiary')" />

        <x-form.form-input name="advising_bank" label="Advising Bank" placeholder="e.g. HSBC London"
            :value="$value('advising_bank')" />

        <x-form.form-input name="reimbursing_bank" label="Reimbursing Bank" placeholder="Correspondent bank"
            :value="$value('reimbursing_bank')" />
    </div>
</x-form.form-section>

<x-form.form-section title="Financial Terms" :required="true">
    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 xl:grid-cols-4">
        <x-form.form-input name="amount" label="LC Amount" :required="true" type="number" placeholder="48200"
            :value="$value('amount')" />

        <x-form.form-select name="currency" label="Currency" :required="true" placeholder="Select currency..."
            :options="[
                'USD' => 'USD',
                'GBP' => 'GBP',
                'EUR' => 'EUR',
                'AED' => 'AED',
                'PKR' => 'PKR',
            ]" :selected="$value('currency', 'USD')" />

        <x-form.form-input name="tolerance_pct" label="Tolerance % (+/-)" type="number" placeholder="5"
            :value="$value('tolerance_pct')" />

        <x-form.form-select name="payment_terms" label="Payment Terms" :required="true" placeholder="Select terms..."
            :options="[
                'at_sight' => 'At Sight',
                '30_days_usance' => '30 Days Usance',
                '60_days_usance' => '60 Days Usance',
                '90_days_usance' => '90 Days Usance',
                'deferred_payment' => 'Deferred Payment',
            ]" :selected="$value('payment_terms')" />

        <x-form.form-input name="issue_date" label="Issue Date" :required="true" type="date"
            :value="$dateValue('issue_date')" />

        <x-form.form-input name="expiry_date" label="Expiry Date" :required="true" type="date"
            :value="$dateValue('expiry_date')" />

        <x-form.form-input name="place_of_expiry" label="Place of Expiry" placeholder="Beneficiary bank counter"
            :value="$value('place_of_expiry')" />

        <x-form.form-input name="latest_shipment_date" label="Last Shipment Date" type="date"
            :value="$dateValue('latest_shipment_date')" />
    </div>
</x-form.form-section>

<x-form.form-section title="Goods & Shipment" :required="true">
    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="md:col-span-2 xl:col-span-4">
            <x-form.form-textarea name="goods_description" label="Description of Goods" :required="true"
                placeholder="Commercial invoice description" :value="$value('goods_description')" :rows="3" />
        </div>

        <x-form.form-input name="port_of_loading" label="Port of Loading" :required="true"
            placeholder="Karachi, Port Qasim" :value="$value('port_of_loading')" />

        <x-form.form-input name="port_of_discharge" label="Port of Discharge" :required="true"
            placeholder="Dubai Jebel Ali" :value="$value('port_of_discharge')" />

        <x-form.form-select name="incoterms" label="Incoterms" :required="true" placeholder="Select incoterms..."
            :options="[
                'FOB' => 'FOB',
                'CIF' => 'CIF',
                'CFR' => 'CFR',
                'EXW' => 'EXW',
                'DAP' => 'DAP',
                'DDP' => 'DDP',
            ]" :selected="$value('incoterms')" />

        <div class="hidden xl:block"></div>

        <x-form.form-select name="partial_shipments" label="Partial Shipments" placeholder="Select..."
            :options="[
                'not_allowed' => 'Not Allowed',
                'allowed' => 'Allowed',
            ]" :selected="$value('partial_shipments', 'not_allowed')" />

        <x-form.form-select name="transhipment" label="Transhipment" placeholder="Select..."
            :options="[
                'not_allowed' => 'Not Allowed',
                'allowed' => 'Allowed',
            ]" :selected="$value('transhipment', 'not_allowed')" />
    </div>
</x-form.form-section>

<x-form.form-section title="Required Documents">
    <div class="p-5">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($documents as $name => [$title, $detail])
                <label
                    class="lc-document-option group flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-[#0078C8]/40 hover:bg-sky-50/40">
                    <input type="checkbox" name="documents_required[]" value="{{ $name }}"
                        {{ in_array($name, (array) $selectedDocuments) ? 'checked' : '' }}
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0078C8] focus:ring-[#0078C8]">
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold leading-5 text-slate-800">{{ $title }}</span>
                        <span class="mt-0.5 block text-xs leading-4 text-slate-500">{{ $detail }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </div>
</x-form.form-section>

@if ($isEdit)
    <x-form.form-section title="Status">
        <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 xl:grid-cols-4">
            <x-form.form-select name="status" label="LC Status" :required="true" placeholder="Select status..."
                :options="[
                    'Draft' => 'Draft',
                    'Active' => 'Active',
                    'Expiring Soon' => 'Expiring Soon',
                    'Settled' => 'Settled',
                    'Expired' => 'Expired',
                    'Cancelled' => 'Cancelled',
                ]" :selected="$value('status', 'Draft')" />
        </div>
    </x-form.form-section>
@endif

<x-form.form-section title="Special Conditions">
    <div class="p-5">
        <x-form.form-textarea name="special_conditions" label="Additional Instructions"
            placeholder="Bank-specific clauses, FE regulations, or presentation conditions"
            :value="$value('special_conditions')" :rows="4" />
    </div>
</x-form.form-section>
