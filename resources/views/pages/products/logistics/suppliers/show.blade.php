@extends('layouts.auth')

@section('title', 'Supplier Details')

@section('content')
    <div class="mt-4">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
            <h1 class="text-gray-900 text-xl font-normal">Supplier Details</h1>
            <div class="flex gap-2">
                <x-common.back-button url="{{ route('logistics.suppliers.edit', $supplier->id) }}" text="Edit" />
                <x-common.back-button url="{{ route('logistics.suppliers.index') }}" />
            </div>
        </div>

        {{-- Content --}}
        <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
            {{-- Section 1: Legal Information --}}
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Legal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Legal Company Name</p>
                        <p class="text-base text-gray-900 font-medium">{{ $supplier->legal_company_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Tax ID / VAT Number</p>
                        <p class="text-base text-gray-900">{{ $supplier->tax_id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Business Type</p>
                        <p class="text-base text-gray-900 capitalize">{{ str_replace('_', ' ', $supplier->business_type) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Country of Registration</p>
                        <p class="text-base text-gray-900">{{ $supplier->country_of_registration }}</p>
                    </div>
                </div>
            </div>

            {{-- Section 2: Contact Details --}}
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Contact Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Primary Contact Name</p>
                        <p class="text-base text-gray-900">{{ $supplier->primary_contact_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Official Email</p>
                        <p class="text-base text-gray-900">{{ $supplier->official_email ?? '-' }}</p>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <p class="text-sm text-gray-500 mb-1">Registered Office Address</p>
                        <p class="text-base text-gray-900">{{ $supplier->registered_office_address ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Section 3: Logistics Preferences --}}
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Logistics Preferences</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Default Incoterms</p>
                        <p class="text-base text-gray-900">{{ $supplier->default_incoterms ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Pref. Port of Loading</p>
                        <p class="text-base text-gray-900">{{ $supplier->pref_port_of_loading ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Lead Time (Days)</p>
                        <p class="text-base text-gray-900">{{ $supplier->lead_time_days ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Section 4: Payment Details --}}
            <div class="p-6 bg-gray-50/50">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Details & Documents</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-8 mb-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">IBAN / Account Number</p>
                        <p class="text-base text-gray-900 font-mono">{{ $supplier->iban ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">SWIFT / BIC</p>
                        <p class="text-base text-gray-900 font-mono">{{ $supplier->swift_bic ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Payment Currency</p>
                        <p class="text-base text-gray-900">{{ $supplier->payment_currency ?? '-' }}</p>
                    </div>
                </div>

                @if ($supplier->business_license_path)
                    <div class="mt-4">
                        <p class="text-sm text-gray-500 mb-2">Business License / Document</p>
                        <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200 w-full md:w-1/2">
                            <svg class="w-8 h-8 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5H7Zm5 1.5L18.5 11H14a2 2 0 0 1-2-2V4.5Z" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-900 text-sm font-medium truncate">Document</p>
                                <a href="{{ Storage::url($supplier->business_license_path) }}" target="_blank"
                                    class="text-blue-600 text-xs hover:underline">View / Download</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
