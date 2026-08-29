@extends('layouts.auth')

@section('title', 'Entity Details')

@section('content')
    <div class="mt-4">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
            <h1 class="text-gray-900 text-xl font-normal">Entity Details</h1>
            <div class="flex gap-2">
                <x-common.back-button url="{{ route('logistics.entities.edit', $entity->id) }}" text="Edit" />
                <x-common.back-button url="{{ route('logistics.entities.index') }}" />
            </div>
        </div>

        {{-- Content --}}
        <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
            {{-- Section 1: Entity Information --}}
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Entity Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Business Legal Name</p>
                        <p class="text-base text-gray-900 font-medium">{{ $entity->business_legal_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Entity Type</p>
                        <p class="text-base text-gray-900">
                            @if ($entity->entity_type == 'Importer')
                                <span
                                    class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">Importer</span>
                            @elseif($entity->entity_type == 'Exporter')
                                <span
                                    class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">Exporter</span>
                            @else
                                <span
                                    class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">{{ $entity->entity_type }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Tax ID / VAT Number</p>
                        <p class="text-base text-gray-900">{{ $entity->tax_id_vat_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Registration Country</p>
                        <p class="text-base text-gray-900">{{ $entity->registration_country }}</p>
                    </div>
                </div>
            </div>

            {{-- Section 2: Contact Details --}}
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Contact Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Full Name</p>
                        <p class="text-base text-gray-900">{{ $entity->contact_full_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Email Address</p>
                        <p class="text-base text-gray-900">{{ $entity->contact_email }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Phone Number</p>
                        <p class="text-base text-gray-900">{{ $entity->contact_phone_number }}</p>
                    </div>
                </div>
            </div>

            {{-- Section 3: Addresses --}}
            <div class="grid grid-cols-1 md:grid-cols-2">
                {{-- Registered Address --}}
                <div class="p-6 border-r border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Registered Address</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Street Address</p>
                            <p class="text-base text-gray-900">{{ $entity->registered_street_address }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">City</p>
                                <p class="text-base text-gray-900">{{ $entity->registered_city }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">State / Province</p>
                                <p class="text-base text-gray-900">{{ $entity->registered_state_province }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Postal Code</p>
                            <p class="text-base text-gray-900">{{ $entity->registered_postal_code }}</p>
                        </div>
                    </div>
                </div>

                {{-- Mailing Address --}}
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Mailing Address</h3>
                    <div class="space-y-3">
                        @if ($entity->mailing_street_address)
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Street Address</p>
                                <p class="text-base text-gray-900">{{ $entity->mailing_street_address }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">City</p>
                                    <p class="text-base text-gray-900">{{ $entity->mailing_city }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">State / Province</p>
                                    <p class="text-base text-gray-900">{{ $entity->mailing_state_province }}</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Postal Code</p>
                                <p class="text-base text-gray-900">{{ $entity->mailing_postal_code }}</p>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 italic">Same as registered address or not provided.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
