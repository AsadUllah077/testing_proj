@extends('layouts.auth')

@section('title', 'Carrier Details')

@section('content')
    <div class="mt-4">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
            <h1 class="text-gray-900 text-xl font-normal">Carrier Details</h1>
            <div class="flex gap-2">
                <x-common.back-button url="{{ route('logistics.carriers.edit', $carrier->id) }}" text="Edit" />
                <x-common.back-button url="{{ route('logistics.carriers.index') }}" />
            </div>
        </div>

        {{-- Content --}}
        <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
            {{-- Section 1: General Information --}}
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">General Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Legal Carrier Name</p>
                        <p class="text-base text-gray-900 font-medium">{{ $carrier->legal_carrier_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Logistics Mode</p>
                        @php
                            $modeConfigs = [
                                'inland_trucking' => [
                                    'label' => 'Inland (Trucking)',
                                    'icon' => 'fa-truck',
                                    'class' => 'bg-orange-50 text-orange-600 border-orange-100',
                                ],
                                'air_freight' => [
                                    'label' => 'Air Freight',
                                    'icon' => 'fa-plane',
                                    'class' => 'bg-blue-50 text-blue-600 border-blue-100',
                                ],
                                'ocean_freight' => [
                                    'label' => 'Ocean Freight',
                                    'icon' => 'fa-ship',
                                    'class' => 'bg-cyan-50 text-cyan-600 border-cyan-100',
                                ],
                                'rail_freight' => [
                                    'label' => 'Rail Freight',
                                    'icon' => 'fa-train',
                                    'class' => 'bg-purple-50 text-purple-600 border-purple-100',
                                ],
                                'multimodal' => [
                                    'label' => 'Multimodal',
                                    'icon' => 'fa-shuffle',
                                    'class' => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                                ],
                            ];
                            $config = $modeConfigs[$carrier->logistics_mode] ?? [
                                'label' => $carrier->logistics_mode,
                                'icon' => 'fa-circle-question',
                                'class' => 'bg-gray-50 text-gray-600 border-gray-100',
                            ];
                        @endphp
                        <span
                            class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md text-xs font-medium border {{ $config['class'] }}">
                            <i class="fa-solid {{ $config['icon'] }}"></i>
                            {{ $config['label'] }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">SCAC / IATA Code</p>
                        <p class="text-base text-gray-900">{{ $carrier->scac_iata_code ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Section 2: Compliance & Insurance --}}
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Compliance & Insurance</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">DOT Number</p>
                        <p class="text-base text-gray-900">{{ $carrier->dot_number ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">MC Authority</p>
                        <p class="text-base text-gray-900">{{ $carrier->mc_authority ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Safety Rating</p>
                        <p class="text-base text-gray-900">
                            @if ($carrier->safety_rating == 'Satisfactory')
                                <span
                                    class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">Satisfactory</span>
                            @elseif($carrier->safety_rating == 'Conditional')
                                <span
                                    class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded-full">Conditional</span>
                            @elseif($carrier->safety_rating == 'Unsatisfactory')
                                <span
                                    class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full">Unsatisfactory</span>
                            @else
                                {{ $carrier->safety_rating ?? '-' }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Section 3: Documentation --}}
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Documentation</h3>
                @if ($carrier->liability_insurance_path)
                    <div class="mt-4">
                        <p class="text-sm text-gray-500 mb-2">Liability Insurance</p>
                        <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200 w-full md:w-1/2">
                            <svg class="w-8 h-8 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5H7Zm5 1.5L18.5 11H14a2 2 0 0 1-2-2V4.5Z" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-900 text-sm font-medium truncate">Document</p>
                                <a href="{{ Storage::url($carrier->liability_insurance_path) }}" target="_blank"
                                    class="text-blue-600 text-xs hover:underline">View / Download</a>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">No documentation uploaded.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
