@extends('layouts.auth')
@section('title', 'Maersk Demurrage & Detention')
@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Demurrage &amp; Detention</h2>
                    <p class="text-xs text-slate-500 mt-1">Free time and accrued charges per container — API v1.1.0</p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Maersk Hub
                </a>
            </div>

            {{-- Info Cards --}}
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-anchor text-white text-xs"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-blue-800">Demurrage (DMR)</p>
                        <p class="text-xs text-blue-700 mt-0.5">Charges for holding a container <strong>at the port
                                terminal</strong> beyond free time after vessel arrival.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-4 bg-orange-50 border border-orange-200 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-orange-500 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-warehouse text-white text-xs"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-orange-800">Detention (DET)</p>
                        <p class="text-xs text-orange-700 mt-0.5">Charges for keeping a container <strong>away from the
                                port</strong> (e.g., at customer premises) beyond free time.</p>
                    </div>
                </div>
            </div>

            {{-- Search Form --}}
            <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <form method="GET" action="{{ route('logistics.maersk.demurrage') }}">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="w-full md:w-32">
                                <label
                                    class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Charge
                                    Type</label>
                                <select name="chargeType"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    <option value="DMR" {{ request('chargeType', 'DMR') == 'DMR' ? 'selected' : '' }}>
                                        Demurrage</option>
                                    <option value="DET" {{ request('chargeType') == 'DET' ? 'selected' : '' }}>Detention
                                    </option>
                                </select>
                            </div>
                            <div class="w-full md:w-44">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Bill of Lading <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                        <i class="fa-solid fa-file-contract text-xs"></i>
                                    </span>
                                    <input type="text" name="billOfLadingNumber"
                                        value="{{ request('billOfLadingNumber') }}" placeholder="e.g. 260368453"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                            </div>
                            <div class="w-full md:w-44">
                                <label
                                    class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer
                                    Code</label>
                                <input type="text" name="carrierCustomerCode"
                                    value="{{ request('carrierCustomerCode') }}" placeholder="e.g. 33104660082"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>
                            <div class="w-full md:w-28">
                                <label
                                    class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Carrier</label>
                                <select name="carrierCode"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    @foreach (['MAEU', 'SEAU', 'SEJJ', 'MCPU', 'MAEI'] as $code)
                                        <option value="{{ $code }}"
                                            {{ request('carrierCode', 'MAEU') == $code ? 'selected' : '' }}>
                                            {{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-full md:w-40">
                                <label
                                    class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Charges
                                    End Date</label>
                                <input type="date" name="chargesEndDate" value="{{ request('chargesEndDate') }}"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm date">
                            </div>
                            <button type="submit"
                                class="px-6 py-[9px] bg-[#0078C8] text-white text-xs font-semibold rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-magnifying-glass"></i> Check Charges
                            </button>
                        </div>
                    </form>
                </div>

                {{-- States --}}
                @if ($error)
                    <div class="p-6">
                        <div
                            class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            <i class="fa-solid fa-circle-xmark mt-0.5 text-red-400 shrink-0"></i>
                            <span>{{ $error }}</span>
                        </div>
                    </div>
                @elseif (!$searched)
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 mb-4">
                            <i class="fa-solid fa-hourglass-half text-2xl text-slate-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Enter a Bill of Lading to check charges</p>
                        <p class="text-xs text-slate-400 mt-1">View free time, chargeable days, and rate tiers per container
                        </p>
                    </div>
                @elseif (empty($charges))
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-green-50 mb-4">
                            <i class="fa-solid fa-circle-check text-2xl text-green-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No charges found for this shipment.</p>
                    </div>
                @else
                    @php
                        $chargeType = $charges['chargeType'] ?? 'DMR';
                        $isDet = $chargeType === 'DET';
                        $chargeName = $charges['chargeName'] ?? ($isDet ? 'Detention' : 'Demurrage');
                        $currencyCode = $charges['currencyCode'] ?? 'USD';
                        $location = $charges['location'] ?? [];
                        $equipmentCharges = $charges['equipmentCharges'] ?? [];

                        // Compute grand total from chargesByDates amounts
                        $grandTotal = 0;
                        foreach ($equipmentCharges as $eq) {
                            foreach ($eq['chargeablePeriod']['chargesByDates'] ?? [] as $cbd) {
                                $grandTotal += $cbd['amount'] ?? 0;
                            }
                        }

                        $accentColor = $isDet ? '#ea580c' : '#0078C8';
                        $accentBg = $isDet ? 'bg-orange-500' : 'bg-[#0078C8]';
                        $accentLight = $isDet
                            ? 'bg-orange-50 border-orange-200 text-orange-700'
                            : 'bg-blue-50 border-blue-200 text-blue-700';
                        $accentText = $isDet ? 'text-orange-600' : 'text-[#0078C8]';
                    @endphp

                    {{-- Document Banner --}}
                    <div
                        class="border-b border-gray-100 bg-linear-to-r {{ $isDet ? 'from-orange-500/5' : 'from-[#0078C8]/5' }} via-white to-white px-6 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-xl {{ $accentBg }} flex items-center justify-center shadow-sm shrink-0">
                                    <i class="fa-solid {{ $isDet ? 'fa-warehouse' : 'fa-anchor' }} text-white text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">
                                        {{ $chargeName }}</p>
                                    <p class="text-xl font-bold text-slate-900 tracking-wide font-mono">
                                        {{ $charges['billOfLadingNumber'] ?? '-' }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ $charges['carrierCode'] ?? '' }}
                                        @if (!empty($charges['carrierCustomerCode']))
                                            &nbsp;·&nbsp; Customer: {{ $charges['carrierCustomerCode'] }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border {{ $isDet ? 'bg-orange-100 text-orange-700 border-orange-200' : 'bg-blue-100 text-blue-700 border-blue-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                                    {{ $chargeName }}
                                </span>
                                @if (!empty($charges['isInvalidFreeTime']) && $charges['isInvalidFreeTime'])
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border bg-red-100 text-red-700 border-red-200">
                                        <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Invalid Free Time
                                    </span>
                                @endif
                                @if ($grandTotal > 0)
                                    <div class="text-right">
                                        <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Accrued</p>
                                        <p class="text-lg font-bold {{ $accentText }}">{{ $currencyCode }}
                                            {{ number_format($grandTotal, 2) }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">

                        {{-- Summary Cards --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @if (!empty($location['locationName']))
                                <div class="bg-white rounded-xl border border-gray-200 p-3.5 col-span-2">
                                    <div class="flex items-center gap-1.5 mb-1.5">
                                        <i class="fa-solid fa-location-dot text-xs {{ $accentText }}"></i>
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
                                            Location</p>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $location['locationName'] }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ $location['cityName'] ?? '' }}{{ !empty($location['countryName']) ? ', ' . $location['countryName'] : '' }}
                                        @if (!empty($location['UNLocationCode']))
                                            &nbsp;<span
                                                class="font-mono bg-slate-100 px-1 rounded">{{ $location['UNLocationCode'] }}</span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                            <div class="bg-white rounded-xl border border-gray-200 p-3.5">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <i class="fa-solid fa-cube text-xs text-slate-400"></i>
                                    <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Containers
                                    </p>
                                </div>
                                <p class="text-xl font-bold text-slate-800">{{ count($equipmentCharges) }}</p>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-3.5">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <i class="fa-solid fa-dollar-sign text-xs text-slate-400"></i>
                                    <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Currency
                                    </p>
                                </div>
                                <p class="text-xl font-bold text-slate-800">{{ $currencyCode }}</p>
                            </div>
                        </div>

                        {{-- Equipment Charges --}}
                        @foreach ($equipmentCharges as $eq)
                            @php
                                $fp = $eq['freePeriod'] ?? [];
                                $cp = $eq['chargeablePeriod'] ?? [];
                                $cbd = $cp['chargesByDates'][0] ?? [];
                                $rateTiers = $cbd['rateTiers'] ?? [];
                                $eqTotal = $cbd['amount'] ?? 0;
                                $chargeableDays = $cbd['chargeableDays'] ?? 0;
                                $isFinal = $eq['isFinalCharge'] ?? false;
                            @endphp
                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                {{-- Container Header --}}
                                <div
                                    class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 {{ $isDet ? 'bg-linear-to-r from-orange-500/5 to-white' : 'bg-linear-to-r from-[#0078C8]/5 to-white' }}">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl {{ $accentBg }} flex items-center justify-center shrink-0 shadow-sm">
                                            <i class="fa-solid fa-cube text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-base font-bold font-mono text-slate-900">
                                                {{ $eq['equipmentReference'] ?? '-' }}</p>
                                            @if (!empty($eq['chargesCalculatedDateTime']))
                                                <p class="text-xs text-slate-400">Calculated:
                                                    {{ \Carbon\Carbon::parse($eq['chargesCalculatedDateTime'])->format('d M Y, H:i') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        @if ($isFinal)
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-green-100 text-green-700 border border-green-200">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i> Final Charge
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                                                <i class="fa-solid fa-clock text-[10px]"></i> Accruing
                                            </span>
                                        @endif
                                        @if ($eqTotal > 0)
                                            <div class="text-right">
                                                <p class="text-[10px] text-slate-400 uppercase tracking-wider">Accrued</p>
                                                <p class="text-lg font-bold {{ $accentText }}">{{ $currencyCode }}
                                                    {{ number_format($eqTotal, 2) }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">

                                    {{-- Free Period --}}
                                    @if (!empty($fp))
                                        <div>
                                            <p
                                                class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                                <i class="fa-solid fa-circle-check text-green-500"></i> Free Period
                                            </p>
                                            <div class="grid grid-cols-2 gap-2">
                                                @if (!empty($fp['freeDays']))
                                                    <div
                                                        class="bg-green-50 border border-green-100 rounded-xl p-3 text-center">
                                                        <p class="text-2xl font-bold text-green-700">{{ $fp['freeDays'] }}
                                                        </p>
                                                        <p
                                                            class="text-[10px] text-green-600 uppercase tracking-wider font-semibold mt-0.5">
                                                            Free Days</p>
                                                    </div>
                                                @endif
                                                @if (!empty($cp['startDate']) || !empty($fp['actualFreeStartDate']))
                                                    <div
                                                        class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                                                        <p class="text-sm font-bold text-slate-700">
                                                            {{ $fp['actualFreeStartDate'] ?? '-' }}</p>
                                                        <p
                                                            class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold mt-0.5">
                                                            Start Date</p>
                                                    </div>
                                                @endif
                                                @if (!empty($fp['actualLastFreeDate']))
                                                    <div
                                                        class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                                                        <p class="text-sm font-bold text-slate-700">
                                                            {{ $fp['actualLastFreeDate'] }}</p>
                                                        <p
                                                            class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold mt-0.5">
                                                            Last Free Date</p>
                                                    </div>
                                                @endif
                                                @if (!empty($fp['originalLastFreeDate']) && $fp['originalLastFreeDate'] !== $fp['actualLastFreeDate'])
                                                    <div
                                                        class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                                                        <p class="text-sm font-bold text-slate-500 line-through">
                                                            {{ $fp['originalLastFreeDate'] }}</p>
                                                        <p
                                                            class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold mt-0.5">
                                                            Original LFD</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Chargeable Period --}}
                                    <div>
                                        <p
                                            class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                            <i
                                                class="fa-solid fa-triangle-exclamation {{ $isDet ? 'text-orange-500' : 'text-blue-500' }}"></i>
                                            Chargeable Period
                                        </p>
                                        <div class="grid grid-cols-2 gap-2 mb-3">
                                            @if (!empty($cp['startDate']))
                                                <div
                                                    class="{{ $isDet ? 'bg-orange-50 border-orange-100' : 'bg-blue-50 border-blue-100' }} border rounded-xl p-3 text-center">
                                                    <p
                                                        class="text-sm font-bold {{ $isDet ? 'text-orange-700' : 'text-blue-700' }}">
                                                        {{ $cp['startDate'] }}</p>
                                                    <p
                                                        class="text-[10px] {{ $isDet ? 'text-orange-500' : 'text-blue-500' }} uppercase tracking-wider font-semibold mt-0.5">
                                                        Charge Start</p>
                                                </div>
                                            @endif
                                            @if ($chargeableDays > 0)
                                                <div
                                                    class="{{ $isDet ? 'bg-orange-50 border-orange-100' : 'bg-blue-50 border-blue-100' }} border rounded-xl p-3 text-center">
                                                    <p
                                                        class="text-2xl font-bold {{ $isDet ? 'text-orange-700' : 'text-blue-700' }}">
                                                        {{ $chargeableDays }}</p>
                                                    <p
                                                        class="text-[10px] {{ $isDet ? 'text-orange-500' : 'text-blue-500' }} uppercase tracking-wider font-semibold mt-0.5">
                                                        Chargeable Days</p>
                                                </div>
                                            @endif
                                            @if (!empty($cbd['chargesByDate']))
                                                <div
                                                    class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center col-span-2">
                                                    <p class="text-sm font-bold text-slate-700">
                                                        {{ $cbd['chargesByDate'] }}</p>
                                                    <p
                                                        class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold mt-0.5">
                                                        Charges As Of</p>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Rate Tiers --}}
                                        @if (!empty($rateTiers))
                                            <p
                                                class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold mb-2">
                                                Rate Tiers</p>
                                            <div class="space-y-1.5">
                                                @foreach ($rateTiers as $ti => $tier)
                                                    @php
                                                        $tierAmount =
                                                            ($tier['rate'] ?? 0) * ($tier['numberOfDays'] ?? 0);
                                                    @endphp
                                                    <div
                                                        class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                                                        <div
                                                            class="w-5 h-5 rounded-full {{ $accentBg }} flex items-center justify-center shrink-0">
                                                            <span
                                                                class="text-white text-[9px] font-bold">{{ $ti + 1 }}</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-xs text-slate-600">
                                                                <span
                                                                    class="font-semibold text-slate-800">{{ $tier['numberOfDays'] ?? '-' }}
                                                                    days</span>
                                                                <span class="text-slate-400 mx-1">×</span>
                                                                <span
                                                                    class="font-semibold {{ $accentText }}">{{ $currencyCode }}
                                                                    {{ number_format($tier['rate'] ?? 0, 2) }}/day</span>
                                                            </p>
                                                        </div>
                                                        <p class="text-xs font-bold text-slate-800 shrink-0">
                                                            {{ $currencyCode }} {{ number_format($tierAmount, 2) }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Force Majeure --}}
                                @if (!empty($cp['forceMajeureDaysDetails']))
                                    <div class="px-5 pb-4">
                                        <p
                                            class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                            <i class="fa-solid fa-shield-halved text-slate-400"></i> Force Majeure Details
                                        </p>
                                        <div class="space-y-1">
                                            @foreach ($cp['forceMajeureDaysDetails'] as $fm)
                                                <div
                                                    class="p-2.5 bg-slate-50 rounded-lg border border-slate-100 text-xs text-slate-600">
                                                    {{ json_encode($fm) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
