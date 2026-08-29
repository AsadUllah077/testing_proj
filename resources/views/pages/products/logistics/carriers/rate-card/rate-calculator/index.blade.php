@extends('layouts.auth')

@section('title', 'Logistics Carriers Rate Calculator')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">

        {{-- Module Tabs --}}
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.carriers') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">

            {{-- Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Rates</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Live lookup from stored rate cards</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-common.export-dropdown name="lc" />
                </div>
            </div>

            {{-- Sub Tabs --}}
            <div class="flex items-center space-x-3 flex-wrap mb-3">
                @php
                    $subTabs = [
                        ['route' => 'logistics.carriers.rate-card.ocean.index', 'label' => 'Ocean NAC / Spot'],
                        ['route' => 'logistics.carriers.rate-card.air.index', 'label' => 'Air per-KG Slabs'],
                        ['route' => 'logistics.carriers.rate-card.inland.index', 'label' => 'Inland MOU Zone Matrix'],
                        ['route' => 'logistics.carriers.rate-card.rate-calculator.index', 'label' => 'Rate Calculator'],
                    ];
                @endphp
                @foreach ($subTabs as $tab)
                    <button onclick="window.location.href='{{ route($tab['route']) }}'"
                        class="{{ Request::routeIs($tab['route']) ? 'bg-[#0078C8] border-[#0078C8] text-white' : 'bg-[#eee5e5] border-[#eee5e5] text-black' }} py-2 px-3 rounded-[3px] border text-center uppercase text-sm font-normal">
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[360px_minmax(0,1fr)] gap-4">

                {{-- ── Left: Input Panel ── --}}
                <div class="bg-white border border-black/10 rounded-lg shadow-sm p-4 self-start sticky top-4">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-calculator text-[#0078C8] text-xs"></i>
                            Freight Rate Calculator
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">Looks up your stored rate cards — ocean NAC, air slabs,
                            inland MOU</p>
                    </div>

                    <div class="space-y-3">

                        {{-- Transport Mode --}}
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                Transport Mode <span class="text-red-500">*</span>
                            </label>
                            <select id="mode"
                                class="w-full h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-900 focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                <option value="">Select mode...</option>
                                <option value="ocean">Ocean FCL / LCL</option>
                                <option value="air">Air Freight</option>
                                <option value="inland">Inland Trucking</option>
                            </select>
                        </div>

                        {{-- Origin --}}
                        <div id="originRow">
                            <label id="originLabel"
                                class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                Origin <span class="text-red-500">*</span>
                            </label>
                            <select id="origin"
                                class="w-full h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-900 focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                <option value="">— select mode first —</option>
                            </select>
                        </div>

                        {{-- Destination (hidden for inland) --}}
                        <div id="destRow">
                            <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                Destination <span class="text-red-500">*</span>
                            </label>
                            <select id="destination"
                                class="w-full h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-900 focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                <option value="">— select mode first —</option>
                            </select>
                        </div>

                        {{-- Equipment (Ocean only) --}}
                        <div id="equipmentRow" class="hidden">
                            <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                Equipment
                            </label>
                            <select id="equipment"
                                class="w-full h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-900 focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                <option value="">Any equipment</option>
                            </select>
                        </div>

                        {{-- Weight Breakpoint (Air only) --}}
                        <div id="weightBreakRow" class="hidden">
                            <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                Weight Breakpoint
                            </label>
                            <select id="weight_breakpoint"
                                class="w-full h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-900 focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                                <option value="">Any breakpoint</option>
                            </select>
                        </div>

                        {{-- Shipment Weight KG (Air only) --}}
                        <div id="weightKgRow" class="hidden">
                            <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                Shipment Weight (KG) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="weight_kg" min="0.1" step="0.1" placeholder="e.g. 250"
                                class="w-full h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-900 focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                        </div>

                        {{-- Number of Containers (Ocean only) --}}
                        <div id="containersRow" class="hidden">
                            <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                Number of Containers
                            </label>
                            <input type="number" id="num_containers" value="1" min="1"
                                class="w-full h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-900 focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]">
                        </div>

                        {{-- Calculate Button --}}
                        <button type="button" id="calcBtn"
                            class="w-full h-10 rounded-lg bg-[#0078C8] text-white text-xs font-semibold hover:bg-[#006bb0] transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="calcBtnText">Calculate Rate</span>
                            <i class="fa-solid fa-arrow-right-long text-[10px]" id="calcArrow"></i>
                            <span id="calcSpinner" class="hidden">
                                <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                        </button>

                        {{-- Error --}}
                        <div id="calcError"
                            class="hidden p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 leading-relaxed">
                        </div>

                    </div>
                </div>

                {{-- ── Right: Results Panel ── --}}
                <div class="space-y-4">

                    {{-- Empty State --}}
                    <div id="emptyState"
                        class="bg-white border border-black/10 rounded-lg shadow-sm p-12 text-center text-gray-400">
                        <i class="fa-solid fa-calculator text-5xl mb-4 text-gray-200"></i>
                        <p class="text-sm font-medium text-gray-500">Select a mode, fill in the fields and click
                            Calculate</p>
                        <p class="text-xs mt-1">Results are pulled live from your stored rate cards</p>
                    </div>

                    {{-- ── Best Match Result Card ── --}}
                    <div id="resultCard" class="hidden bg-white border border-sky-100 rounded-lg shadow-sm p-5">

                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-gray-900">Best Match</h3>
                            <span id="matchedRef"
                                class="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-0.5 rounded"></span>
                        </div>
                        <p id="matchMeta" class="text-xs text-slate-500 mb-4"></p>

                        {{-- Ocean Breakdown --}}
                        <div id="oceanBreakdown" class="hidden">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Ocean
                                        Freight</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_ocean_freight">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">BAF</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_baf">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">CAF</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_caf">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">THC O+D</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_thc">—</p>
                                </div>
                            </div>
                        </div>

                        {{-- Air Breakdown --}}
                        <div id="airBreakdown" class="hidden">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Charge Rate
                                    </p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_charge_rate">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Fuel (FSC)
                                    </p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_fsc">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Security
                                    </p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_security">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Handling
                                    </p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_handling">—</p>
                                </div>
                            </div>
                        </div>

                        {{-- Inland Breakdown --}}
                        <div id="inlandBreakdown" class="hidden">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-3">
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Up to 500
                                        KG</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_500kg">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">501–1,000
                                        KG</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_1000kg">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">1,001–5,000
                                        KG</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_5000kg">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">20ft
                                        Container</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_20ft">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">40ft
                                        Container</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_40ft">—</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Transit
                                        Time</p>
                                    <p class="text-lg font-bold text-gray-900 mt-1" id="r_transit">—</p>
                                </div>
                            </div>
                        </div>

                        {{-- All-In Total Banner --}}
                        <div class="rounded-xl border-2 border-[#0078C8] bg-sky-50 px-5 py-5 text-center">
                            <p class="text-[11px] uppercase tracking-wider text-[#0078C8] font-semibold" id="allInLabel">
                                All-In Rate</p>
                            <p class="text-4xl font-bold text-[#0078C8] mt-1" id="r_all_in">—</p>
                            <p class="text-xs text-slate-500 mt-1" id="allInSub"></p>
                        </div>

                        {{-- Validity note --}}
                        <p id="validityNote" class="text-[11px] text-gray-400 text-center mt-2"></p>

                    </div>

                    {{-- ── Comparison Table ── --}}
                    <div id="comparisonCard"
                        class="hidden bg-white border border-black/10 rounded-lg shadow-sm overflow-hidden">
                        <div
                            class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-900">All Options for This Route</h3>
                            <p class="text-xs text-slate-400" id="compSubtitle"></p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead id="compHead"></thead>
                                <tbody id="compBody" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ── Constants from PHP models ──────────────────────────────────
            const OCEAN_ORIGINS = @json(\App\Models\LogisticsOceanRateCard::ORIGIN_PORTS);
            const OCEAN_DESTS = @json(\App\Models\LogisticsOceanRateCard::DESTINATION_PORTS);
            const OCEAN_EQUIPMENT = @json(\App\Models\LogisticsOceanRateCard::EQUIPMENT);
            const AIR_ORIGINS = @json(\App\Models\LogisticsAirRateCard::ORIGIN_PORTS);
            const AIR_DESTS = @json(\App\Models\LogisticsAirRateCard::DESTINATION_PORTS);
            const AIR_BREAKPOINTS = @json(\App\Models\LogisticsAirRateCard::WEIGHT_BREAKPOINTS);

            const CALC_URL = '{{ route('logistics.carriers.rate-card.rate-calculator.calculate') }}';
            const ZONES_URL = '{{ route('logistics.carriers.rate-card.rate-calculator.zones') }}';

            // ── DOM refs ──────────────────────────────────────────────────
            const modeEl = document.getElementById('mode');
            const originEl = document.getElementById('origin');
            const originLabel = document.getElementById('originLabel');
            const destRowEl = document.getElementById('destRow');
            const destEl = document.getElementById('destination');
            const equipRowEl = document.getElementById('equipmentRow');
            const equipEl = document.getElementById('equipment');
            const wBreakRowEl = document.getElementById('weightBreakRow');
            const wBreakEl = document.getElementById('weight_breakpoint');
            const wKgRowEl = document.getElementById('weightKgRow');
            const wKgEl = document.getElementById('weight_kg');
            const contRowEl = document.getElementById('containersRow');
            const contEl = document.getElementById('num_containers');
            const calcBtn = document.getElementById('calcBtn');
            const calcBtnText = document.getElementById('calcBtnText');
            const calcArrow = document.getElementById('calcArrow');
            const calcSpinner = document.getElementById('calcSpinner');
            const calcError = document.getElementById('calcError');

            // ── Helpers ───────────────────────────────────────────────────
            const show = el => el.classList.remove('hidden');
            const hide = el => el.classList.add('hidden');

            function fillSelect(el, obj, placeholder) {
                el.innerHTML = `<option value="">${placeholder}</option>`;
                Object.entries(obj).forEach(([k, v]) => {
                    el.innerHTML += `<option value="${k}">${v}</option>`;
                });
            }

            function fmtUSD(v) {
                if (v === null || v === undefined || v === '') return '—';
                return '$' + parseFloat(v).toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }

            function fmtPKR(v) {
                if (v === null || v === undefined || v === '') return '—';
                return 'PKR ' + parseFloat(v).toLocaleString('en-US', {
                    maximumFractionDigits: 0
                });
            }

            function showError(msg) {
                calcError.textContent = msg;
                show(calcError);
                calcBtnText.textContent = 'Calculate Rate';
                hide(calcSpinner);
                show(calcArrow);
                calcBtn.disabled = false;
            }

            function clearError() {
                hide(calcError);
                calcError.textContent = '';
            }

            // ── Mode change ───────────────────────────────────────────────
            modeEl.addEventListener('change', function() {
                const mode = this.value;
                clearError();
                hide(document.getElementById('emptyState'));
                hide(document.getElementById('resultCard'));
                hide(document.getElementById('comparisonCard'));
                show(document.getElementById('emptyState'));

                // Reset all conditional rows
                hide(equipRowEl);
                hide(wBreakRowEl);
                hide(wKgRowEl);
                hide(contRowEl);
                show(destRowEl);
                originLabel.innerHTML = 'Origin <span class="text-red-500">*</span>';

                if (mode === 'ocean') {
                    fillSelect(originEl, OCEAN_ORIGINS, 'Select origin...');
                    fillSelect(destEl, OCEAN_DESTS, 'Select destination...');
                    fillSelect(equipEl, OCEAN_EQUIPMENT, 'Any equipment');
                    show(equipRowEl);
                    show(contRowEl);
                    contEl.value = 1;

                } else if (mode === 'air') {
                    fillSelect(originEl, AIR_ORIGINS, 'Select origin...');
                    fillSelect(destEl, AIR_DESTS, 'Select destination...');
                    fillSelect(wBreakEl, AIR_BREAKPOINTS, 'Any breakpoint');
                    show(wBreakRowEl);
                    show(wKgRowEl);
                    wKgEl.value = '';

                } else if (mode === 'inland') {
                    originLabel.innerHTML = 'Zone / Route <span class="text-red-500">*</span>';
                    fillSelect(originEl, {}, 'Loading zones...');
                    hide(destRowEl);

                    fetch(ZONES_URL, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (!data.length) {
                                fillSelect(originEl, {},
                                    'No active zones found — add inland rates first');
                                return;
                            }
                            const opts = {};
                            data.forEach(z => {
                                opts[z.id] = z.zone_route_description + (z.carrier_label ?
                                    '  ·  ' + z.carrier_label : '');
                            });
                            fillSelect(originEl, opts, 'Select zone...');
                        })
                        .catch(() => fillSelect(originEl, {}, 'Error loading zones'));

                } else {
                    fillSelect(originEl, {}, '— select mode first —');
                    fillSelect(destEl, {}, '— select mode first —');
                }
            });

            // ── Calculate ─────────────────────────────────────────────────
            calcBtn.addEventListener('click', function() {
                clearError();
                const mode = modeEl.value;
                const origin = originEl.value;
                const dest = destEl.value;

                if (!mode) {
                    showError('Please select a transport mode.');
                    return;
                }
                if (!origin) {
                    showError('Please select an origin' + (mode === 'inland' ? ' / zone.' : '.'));
                    return;
                }
                if (mode !== 'inland' && !dest) {
                    showError('Please select a destination.');
                    return;
                }
                if (mode === 'air' && (!wKgEl.value || parseFloat(wKgEl.value) <= 0)) {
                    showError('Please enter a shipment weight in KG.');
                    return;
                }

                calcBtnText.textContent = 'Calculating...';
                hide(calcArrow);
                show(calcSpinner);
                calcBtn.disabled = true;

                const params = new URLSearchParams({
                    mode,
                    origin,
                    destination: dest || '',
                    equipment: equipEl.value || '',
                    weight_breakpoint: wBreakEl.value || '',
                    weight_kg: wKgEl.value || '1',
                    num_containers: contEl.value || '1',
                });

                fetch(`${CALC_URL}?${params}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(async r => {
                        const data = await r.json();
                        if (!r.ok || data.error) {
                            showError(data.error || 'Unexpected error.');
                            return;
                        }
                        renderResult(mode, data);
                    })
                    .catch(() => showError('Network error. Please try again.'))
                    .finally(() => {
                        calcBtnText.textContent = 'Calculate Rate';
                        hide(calcSpinner);
                        show(calcArrow);
                        calcBtn.disabled = false;
                    });
            });

            // ── Render Results ────────────────────────────────────────────
            function renderResult(mode, data) {
                const best = data.best;

                hide(document.getElementById('emptyState'));
                show(document.getElementById('resultCard'));
                show(document.getElementById('comparisonCard'));

                // Reset all breakdowns
                hide(document.getElementById('oceanBreakdown'));
                hide(document.getElementById('airBreakdown'));
                hide(document.getElementById('inlandBreakdown'));

                document.getElementById('matchedRef').textContent = best.ref_code ?? '';
                document.getElementById('validityNote').textContent = '';

                if (mode === 'ocean') {
                    renderOceanResult(best, data);
                } else if (mode === 'air') {
                    renderAirResult(best, data);
                } else if (mode === 'inland') {
                    renderInlandResult(best, data);
                }
            }

            // ── Ocean Result ──────────────────────────────────────────────
            function renderOceanResult(best, data) {
                const qty = parseInt(contEl.value) || 1;
                const thc = (best.thc_origin || 0) + (best.thc_destination || 0);
                const allIn = best.all_in_total * qty;

                document.getElementById('matchMeta').innerHTML =
                    `<span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full inline-block" style="background:${best.carrier_color}"></span>
                <strong>${best.carrier_label}</strong>
            </span>
            &nbsp;·&nbsp; ${best.rate_type?.toUpperCase()}
            &nbsp;·&nbsp; ${best.equipment_label}
            &nbsp;·&nbsp; ${best.origin_port} → ${best.destination_port}
            ${best.transit_time_days ? '&nbsp;·&nbsp; <strong>' + best.transit_time_days + ' days transit</strong>' : ''}`;

                document.getElementById('r_ocean_freight').textContent = fmtUSD(best.ocean_freight);
                document.getElementById('r_baf').textContent = fmtUSD(best.baf);
                document.getElementById('r_caf').textContent = fmtUSD(best.caf);
                document.getElementById('r_thc').textContent = fmtUSD(thc);
                show(document.getElementById('oceanBreakdown'));

                document.getElementById('allInLabel').textContent =
                    qty > 1 ? `All-In × ${qty} Containers` : 'All-In per Container';
                document.getElementById('r_all_in').textContent = fmtUSD(allIn);
                document.getElementById('allInSub').textContent =
                    qty > 1 ? fmtUSD(best.all_in_total) + ' per box' : 'Single container';

                if (best.valid_from && best.valid_to) {
                    document.getElementById('validityNote').textContent =
                        `Rate valid: ${best.valid_from} – ${best.valid_to}` +
                        (best.free_days_demurrage ? `  ·  ${best.free_days_demurrage} free demurrage days` : '');
                }

                buildOceanComparison(data.all, qty);
            }

            // ── Air Result ────────────────────────────────────────────────
            function renderAirResult(best, data) {
                const kg = data.weight_kg || parseFloat(wKgEl.value) || 1;

                document.getElementById('matchMeta').innerHTML =
                    `<span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full inline-block" style="background:${best.carrier_color}"></span>
                <strong>${best.carrier_label}</strong>
            </span>
            &nbsp;·&nbsp; ${best.origin_port} → ${best.destination_port}
            &nbsp;·&nbsp; ${best.weight_breakpoint_label}
            ${best.minimum_applied ? '&nbsp;·&nbsp; <span class="text-amber-600">Minimum charge applied</span>' : ''}`;

                document.getElementById('r_charge_rate').textContent = fmtUSD(best.charge_rate_usd_kg) + '/kg';
                document.getElementById('r_fsc').textContent = fmtUSD(best.fuel_surcharge_fsc) + '/kg';
                document.getElementById('r_security').textContent = fmtUSD(best.security_xray) + '/kg';
                document.getElementById('r_handling').textContent = fmtUSD(best.origin_handling) + '/kg';
                show(document.getElementById('airBreakdown'));

                document.getElementById('allInLabel').textContent = `All-In for ${kg} KG`;
                document.getElementById('r_all_in').textContent = fmtUSD(best.total_for_weight);
                document.getElementById('allInSub').textContent =
                    fmtUSD(best.all_in_per_kg) + '/kg' +
                    (best.minimum_applied ? ' (min charge: ' + fmtUSD(best.minimum_charge_usd) + ')' : '');

                if (best.valid_from && best.valid_to) {
                    document.getElementById('validityNote').textContent =
                        `Rate valid: ${best.valid_from} – ${best.valid_to}`;
                }

                buildAirComparison(data.all, kg);
            }

            // ── Inland Result ─────────────────────────────────────────────
            function renderInlandResult(best, data) {
                document.getElementById('matchMeta').innerHTML =
                    `<span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full inline-block" style="background:${best.carrier_color}"></span>
                <strong>${best.carrier_label}</strong>
            </span>
            &nbsp;·&nbsp; ${best.zone_route_description}
            ${best.example_routes ? '&nbsp;·&nbsp; ' + best.example_routes : ''}
            ${best.transit_time ? '&nbsp;·&nbsp; <strong>' + best.transit_time + '</strong>' : ''}`;

                document.getElementById('r_500kg').textContent = fmtPKR(best.rate_up_to_500kg);
                document.getElementById('r_1000kg').textContent = fmtPKR(best.rate_501_1000kg);
                document.getElementById('r_5000kg').textContent = fmtPKR(best.rate_1001_5000kg);
                document.getElementById('r_20ft').textContent = fmtPKR(best.rate_20ft_container);
                document.getElementById('r_40ft').textContent = fmtPKR(best.rate_40ft_container);
                document.getElementById('r_transit').textContent = best.transit_time ?? '—';
                show(document.getElementById('inlandBreakdown'));

                document.getElementById('allInLabel').textContent = 'Zone';
                document.getElementById('r_all_in').textContent = best.zone_route_description;
                document.getElementById('allInSub').textContent = best.example_routes ?? '';

                if (best.mou_expiry_date) {
                    document.getElementById('validityNote').textContent =
                        `MOU expiry: ${best.mou_expiry_date}  ·  Fuel levy: ${best.fuel_levy_label}`;
                }

                buildInlandComparison(data.all);
            }

            // ── Comparison Table Builders ─────────────────────────────────

            function buildOceanComparison(rows, qty) {
                document.getElementById('compSubtitle').textContent =
                    'Same origin – destination, same equipment, sorted by price';
                const minAllIn = Math.min(...rows.map(r => r.all_in_total));

                document.getElementById('compHead').innerHTML = `
            <tr class="bg-gray-50 border-y border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                <th class="px-4 py-3 text-left">Carrier</th>
                <th class="px-4 py-3 text-left">Ref</th>
                <th class="px-4 py-3 text-right">Ocean Frt</th>
                <th class="px-4 py-3 text-right">BAF</th>
                <th class="px-4 py-3 text-right">CAF</th>
                <th class="px-4 py-3 text-right">THC O+D</th>
                <th class="px-4 py-3 text-right">All-In / Box</th>
                ${qty > 1 ? '<th class="px-4 py-3 text-right">Total ×' + qty + '</th>' : ''}
                <th class="px-4 py-3 text-center">Transit</th>
                <th class="px-4 py-3 text-center">Type</th>
                <th class="px-4 py-3 text-center">Valid</th>
                <th class="px-4 py-3"></th>
            </tr>`;

                document.getElementById('compBody').innerHTML = rows.map(r => {
                    const isBest = r.all_in_total === minAllIn;
                    const thc = (r.thc_origin || 0) + (r.thc_destination || 0);
                    const action = isBest ?
                        `<span class="px-2 py-0.5 text-[10px] rounded-full bg-lime-100 text-lime-700 font-semibold">Best Rate</span>` :
                        `<button class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md">Select</button>`;
                    return `<tr class="${isBest ? 'bg-lime-50' : 'hover:bg-gray-50'} transition-colors">
                <td class="px-4 py-3 text-xs">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background:${r.carrier_color}"></span>
                        <span class="font-semibold text-gray-900">${r.carrier_label}</span>
                    </span>
                </td>
                <td class="px-4 py-3 text-[11px] font-mono text-gray-400">${r.ref_code}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtUSD(r.ocean_freight)}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtUSD(r.baf)}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtUSD(r.caf)}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtUSD(thc)}</td>
                <td class="px-4 py-3 text-xs text-right font-semibold text-[#0078C8]">${fmtUSD(r.all_in_total)}</td>
                ${qty > 1 ? `<td class="px-4 py-3 text-xs text-right font-bold text-[#0078C8]">${fmtUSD(r.all_in_total * qty)}</td>` : ''}
                <td class="px-4 py-3 text-xs text-center text-gray-600">${r.transit_time_days ? r.transit_time_days + 'd' : '—'}</td>
                <td class="px-4 py-3 text-xs text-center">
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold
                        ${r.rate_type === 'nac' ? 'bg-sky-50 text-[#0078C8]' : 'bg-amber-100 text-amber-700'}">
                        ${r.rate_type?.toUpperCase() ?? '—'}
                    </span>
                </td>
                <td class="px-4 py-3 text-[11px] text-center text-gray-400">${r.valid_to ?? '—'}</td>
                <td class="px-4 py-3 text-center">${action}</td>
            </tr>`;
                }).join('');
            }

            function buildAirComparison(rows, kg) {
                document.getElementById('compSubtitle').textContent = 'Same route, sorted by all-in rate per KG';

                document.getElementById('compHead').innerHTML = `
            <tr class="bg-gray-50 border-y border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                <th class="px-4 py-3 text-left">Carrier</th>
                <th class="px-4 py-3 text-left">Ref</th>
                <th class="px-4 py-3 text-center">Breakpoint</th>
                <th class="px-4 py-3 text-right">Charge/KG</th>
                <th class="px-4 py-3 text-right">FSC</th>
                <th class="px-4 py-3 text-right">Security</th>
                <th class="px-4 py-3 text-right">Handling</th>
                <th class="px-4 py-3 text-right">All-In/KG</th>
                <th class="px-4 py-3 text-right">Total (${kg} KG)</th>
                <th class="px-4 py-3 text-center">Valid</th>
                <th class="px-4 py-3"></th>
            </tr>`;

                const minTotal = Math.min(...rows.map(r => r.total_for_weight));
                document.getElementById('compBody').innerHTML = rows.map(r => {
                    const isBest = r.total_for_weight === minTotal;
                    const action = isBest ?
                        `<span class="px-2 py-0.5 text-[10px] rounded-full bg-lime-100 text-lime-700 font-semibold">Best Rate</span>` :
                        `<button class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md">Select</button>`;
                    return `<tr class="${isBest ? 'bg-lime-50' : 'hover:bg-gray-50'} transition-colors">
                <td class="px-4 py-3 text-xs">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background:${r.carrier_color}"></span>
                        <span class="font-semibold text-gray-900">${r.carrier_label}</span>
                    </span>
                </td>
                <td class="px-4 py-3 text-[11px] font-mono text-gray-400">${r.ref_code}</td>
                <td class="px-4 py-3 text-xs text-center">
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[10px] font-medium">${r.weight_breakpoint_label}</span>
                </td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtUSD(r.charge_rate_usd_kg)}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtUSD(r.fuel_surcharge_fsc)}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtUSD(r.security_xray)}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtUSD(r.origin_handling)}</td>
                <td class="px-4 py-3 text-xs text-right font-semibold text-[#0078C8]">${fmtUSD(r.all_in_per_kg)}/kg</td>
                <td class="px-4 py-3 text-xs text-right font-bold text-[#0078C8]">
                    ${fmtUSD(r.total_for_weight)}
                    ${r.minimum_applied ? '<span class="block text-[10px] text-amber-600 font-normal">min applied</span>' : ''}
                </td>
                <td class="px-4 py-3 text-[11px] text-center text-gray-400">${r.valid_to ?? '—'}</td>
                <td class="px-4 py-3 text-center">${action}</td>
            </tr>`;
                }).join('');
            }

            function buildInlandComparison(rows) {
                document.getElementById('compSubtitle').textContent = 'All zones for this carrier / MOU party';

                document.getElementById('compHead').innerHTML = `
            <tr class="bg-gray-50 border-y border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                <th class="px-4 py-3 text-left">Carrier</th>
                <th class="px-4 py-3 text-left">Zone</th>
                <th class="px-4 py-3 text-left">Example Routes</th>
                <th class="px-4 py-3 text-right">Up to 500 KG</th>
                <th class="px-4 py-3 text-right">501–1,000 KG</th>
                <th class="px-4 py-3 text-right">1,001–5,000 KG</th>
                <th class="px-4 py-3 text-right">20ft</th>
                <th class="px-4 py-3 text-right">40ft</th>
                <th class="px-4 py-3 text-center">Transit</th>
            </tr>`;

                document.getElementById('compBody').innerHTML = rows.map(r => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-xs">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background:${r.carrier_color}"></span>
                        <span class="font-semibold text-gray-900">${r.carrier_label}</span>
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-800 font-medium">${r.zone_route_description}</td>
                <td class="px-4 py-3 text-[11px] font-mono text-gray-400">${r.example_routes ?? '—'}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtPKR(r.rate_up_to_500kg)}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtPKR(r.rate_501_1000kg)}</td>
                <td class="px-4 py-3 text-xs text-right text-gray-700">${fmtPKR(r.rate_1001_5000kg)}</td>
                <td class="px-4 py-3 text-xs text-right font-semibold text-[#0078C8]">${fmtPKR(r.rate_20ft_container)}</td>
                <td class="px-4 py-3 text-xs text-right font-semibold text-[#0078C8]">${fmtPKR(r.rate_40ft_container)}</td>
                <td class="px-4 py-3 text-xs text-center text-gray-600">${r.transit_time ?? '—'}</td>
            </tr>`).join('');
            }

        });
    </script>
@endpush
