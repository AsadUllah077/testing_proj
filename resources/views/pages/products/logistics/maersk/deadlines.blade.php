@extends('layouts.auth')
@section('title', 'Maersk Shipment Deadlines')
@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Shipment Deadlines</h2>
                    <p class="text-xs text-slate-500 mt-1">Cargo cutoff, VGM, shipping instructions & DG deadlines — API v2.3.0</p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Maersk Hub
                </a>
            </div>

            <!-- Search Form -->
            <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <form method="GET" action="{{ route('logistics.maersk.deadlines') }}">
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="w-full md:w-40">
                                <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">Vessel
                                    IMO Number <span class="text-red-500">*</span></label>
                                <input type="text" name="vesselIMONumber" value="{{ request('vesselIMONumber') }}"
                                    placeholder="e.g. 9456783" maxlength="7" required
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>
                            <div class="w-full md:w-36">
                                <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">Voyage
                                    <span class="text-red-500">*</span></label>
                                <input type="text" name="voyage" value="{{ request('voyage') }}"
                                    placeholder="e.g. 216E" maxlength="4" required
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>
                            <div class="w-full md:w-44">
                                <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">UN
                                    Location Code</label>
                                <input type="text" name="UNLocationCode" value="{{ request('UNLocationCode') }}"
                                    placeholder="e.g. HKHKG" maxlength="5"
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                <p class="mt-1 text-xs text-slate-400">Or use ISO Country + Port below</p>
                            </div>
                            <div class="text-xs text-slate-400 self-center pb-1">— OR —</div>
                            <div class="w-full md:w-28">
                                <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">ISO
                                    Country</label>
                                <input type="text" name="ISOCountryCode" value="{{ request('ISOCountryCode') }}"
                                    placeholder="e.g. HK" maxlength="2"
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>
                            <div class="w-full md:w-44">
                                <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">Port of
                                    Load</label>
                                <input type="text" name="portOfLoad" value="{{ request('portOfLoad') }}"
                                    placeholder="e.g. Hong Kong"
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>
                            <div class="mb-[2px]">
                                <button type="submit"
                                    class="px-5 py-2 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#006bb0] flex items-center gap-2">
                                    <i class="fa-solid fa-magnifying-glass"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Results -->
                @if ($error)
                    <div class="p-6">
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                            <i class="fa-solid fa-circle-xmark text-red-500 mt-0.5"></i>
                            <p class="text-sm text-red-700">{{ $error }}</p>
                        </div>
                    </div>
                @elseif (!$searched)
                    <div class="px-6 py-12 text-center text-gray-400">
                        <i class="fa-solid fa-clock text-4xl text-gray-200 mb-3"></i>
                        <p class="text-sm">Enter a Vessel IMO Number and Voyage to look up deadlines.</p>
                    </div>
                @elseif (empty($deadlines))
                    <div class="px-6 py-12 text-center text-gray-500">
                        <i class="fa-solid fa-clock text-4xl text-gray-200 mb-3"></i>
                        <p class="text-sm font-medium">No deadlines found for that vessel/voyage combination.</p>
                    </div>
                @else
                    @php $terminals = is_array($deadlines) && isset($deadlines[0]) ? $deadlines : [$deadlines]; @endphp
                    <div class="p-6 space-y-4">
                        @foreach ($terminals as $terminal)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="px-5 py-3 bg-slate-50 border-b border-gray-200">
                                    <h4 class="font-semibold text-slate-800 text-sm">
                                        {{ $terminal['terminalName'] ?? $terminal['portTerminalCode'] ?? 'Terminal' }}
                                    </h4>
                                    @if (!empty($terminal['UNLocationCode']))
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $terminal['UNLocationCode'] }} &middot; {{ $terminal['portOfLoad'] ?? '' }}</p>
                                    @endif
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @php
                                        $deadlineLabels = [
                                            'commercialCargoCutoff'          => ['Commercial Cargo Cutoff', 'fa-box', 'text-red-500'],
                                            'commercialVGMDeadline'          => ['VGM Deadline', 'fa-weight-scale', 'text-purple-500'],
                                            'shippingInstructionsDeadline'   => ['Shipping Instructions', 'fa-file-lines', 'text-blue-500'],
                                            'shippingInstructionsDeadlineAMS'=> ['Shipping Instructions (AMS)', 'fa-flag-usa', 'text-indigo-500'],
                                            'specialCargoDocumentationDeadline' => ['Special Cargo Documentation', 'fa-file-circle-exclamation', 'text-amber-500'],
                                            'dangerousGoodsDeclarationDeadline' => ['Dangerous Goods Declaration', 'fa-skull-crossbones', 'text-orange-500'],
                                            'emptyContainerPickupDeadline'   => ['Empty Container Pickup', 'fa-truck-ramp-box', 'text-teal-500'],
                                        ];
                                    @endphp
                                    @foreach ($deadlineLabels as $key => [$label, $icon, $color])
                                        @if (isset($terminal[$key]))
                                            <div class="px-5 py-3 flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <i class="fa-solid {{ $icon }} {{ $color }} w-4 text-center"></i>
                                                    <span class="text-sm text-slate-700">{{ $label }}</span>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-800">
                                                    {{ \Carbon\Carbon::parse($terminal[$key])->format('d M Y H:i') }}
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
