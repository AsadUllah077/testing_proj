@extends('layouts.auth')

@section('title', 'Landed Cost Calculation')

@section('content')
    <div class="mt-4" x-data="{
        query: '',
        searched: false,
        shipment: '',

        // Inputs (will be populated on search)
        cifUsd: 0,
        exchangeRate: 278.40,
        customsDutyPct: 0,
        additionalDutyPct: 0,
        salesTaxPct: 0,
        incomeTaxPct: 0,
        portHandling: 0,
        customsAgentFee: 0,
        inlandTransport: 0,
        bankCharges: 0,

        // Dummy dataset keyed by shipment ref
        dataset: {
            'SH-397': { cif: 38500, rate: 278.40, cd: 15, acd: 3, st: 17, it: 2, ph: 420, ca: 250, it2: 380, bc: 185 },
            'SH-395': { cif: 24000, rate: 278.40, cd: 10, acd: 3, st: 17, it: 2, ph: 310, ca: 200, it2: 260, bc: 120 },
            'SH-394': { cif: 51200, rate: 278.40, cd: 20, acd: 3, st: 17, it: 2, ph: 580, ca: 300, it2: 420, bc: 220 },
        },

        // Computed
        get customsDutyUsd()   { return this.cifUsd * (this.customsDutyPct / 100); },
        get additionalDutyUsd(){ return this.cifUsd * (this.additionalDutyPct / 100); },
        get assessableUsd()    { return this.cifUsd + this.customsDutyUsd + this.additionalDutyUsd; },
        get salesTaxUsd()      { return this.assessableUsd * (this.salesTaxPct / 100); },
        get incomeTaxUsd()     { return this.cifUsd * (this.incomeTaxPct / 100); },
        get totalUsd() {
            return this.cifUsd + this.customsDutyUsd + this.additionalDutyUsd
                 + this.salesTaxUsd + this.incomeTaxUsd
                 + parseFloat(this.portHandling || 0) + parseFloat(this.customsAgentFee || 0)
                 + parseFloat(this.inlandTransport || 0) + parseFloat(this.bankCharges || 0);
        },
        get totalPkr() { return this.totalUsd * this.exchangeRate; },

        search() {
            const key = this.query.trim().toUpperCase();
            if (!key) return;
            const d = this.dataset[key];
            if (d) {
                this.shipment        = key;
                this.cifUsd          = d.cif;
                this.exchangeRate    = d.rate;
                this.customsDutyPct  = d.cd;
                this.additionalDutyPct = d.acd;
                this.salesTaxPct     = d.st;
                this.incomeTaxPct    = d.it;
                this.portHandling    = d.ph;
                this.customsAgentFee = d.ca;
                this.inlandTransport = d.it2;
                this.bankCharges     = d.bc;
            } else {
                this.shipment        = key;
                this.cifUsd          = 0;
                this.customsDutyPct  = 0;
                this.additionalDutyPct = 0;
                this.salesTaxPct     = 0;
                this.incomeTaxPct    = 0;
                this.portHandling    = 0;
                this.customsAgentFee = 0;
                this.inlandTransport = 0;
                this.bankCharges     = 0;
            }
            this.searched = true;
        },

        reset() { this.searched = false; this.query = ''; this.shipment = ''; },

        pct(usd) { return this.cifUsd > 0 ? ((usd / this.cifUsd) * 100).toFixed(1) + '%' : '—'; },
        usdFmt(n) { return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); },
        pkrFmt(usd) {
            const v = usd * this.exchangeRate;
            if (v >= 1_000_000) return 'PKR ' + (v / 1_000_000).toFixed(2) + 'M';
            if (v >= 1_000)     return 'PKR ' + (v / 1_000).toFixed(1) + 'K';
            return 'PKR ' + Number(v).toFixed(0);
        },
        pkrDirect(pkr) {
            if (pkr >= 1_000_000) return 'PKR ' + (pkr / 1_000_000).toFixed(2) + 'M';
            if (pkr >= 1_000)     return 'PKR ' + (pkr / 1_000).toFixed(1) + 'K';
            return 'PKR ' + Number(pkr).toFixed(0);
        },
    }">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">Landed Cost Calculation</h1>
                    <p class="text-xs text-gray-400 mt-0.5">All costs to bring goods to your warehouse door</p>
                </div>
                <x-common.back-button url="{{ route('logistics.gd.index') }}" />
            </div>

            {{-- Search Bar --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5 mb-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Enter Shipment Reference</p>
                <div class="flex items-center gap-3 max-w-lg">
                    <div class="relative flex-1">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" x-model="query"
                            @keydown.enter="search()"
                            placeholder="e.g. SH-397, SH-395..."
                            class="w-full pl-9 pr-4 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                    </div>
                    <button @click="search()"
                        class="px-5 py-2.5 bg-[#0078C8] hover:bg-[#006bb0] text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                        Calculate
                    </button>
                    <button x-show="searched" @click="reset()"
                        class="px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-600 text-sm rounded-lg border border-gray-200 transition-colors"
                        style="display:none;">
                        Clear
                    </button>
                </div>
                <p class="text-[10px] text-gray-400 mt-2">Try: SH-397, SH-395, SH-394</p>
            </div>

            {{-- Default State: Summary List --}}
            <div x-show="!searched"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100">

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Recent Calculations</h3>
                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Total Landed Cost</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @php
                            $recent = [
                                ['ref' => 'SH-397', 'desc' => 'Electronics · DXB → KHI', 'cif' => '$38,500', 'total_usd' => '$55,320', 'total_pkr' => 'PKR 15.40M', 'date' => 'Apr 12, 2026', 'status' => 'Cleared'],
                                ['ref' => 'SH-395', 'desc' => 'Machinery · GBR → ISB',   'cif' => '$24,000', 'total_usd' => '$33,140', 'total_pkr' => 'PKR 9.22M',  'date' => 'Apr 08, 2026', 'status' => 'Pending'],
                                ['ref' => 'SH-394', 'desc' => 'Cement · KHI → AUH',      'cif' => '$51,200', 'total_usd' => '$76,480', 'total_pkr' => 'PKR 21.28M', 'date' => 'Apr 03, 2026', 'status' => 'Cleared'],
                            ];
                        @endphp
                        @foreach ($recent as $r)
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50/50 cursor-pointer transition-colors"
                            @click="query = '{{ $r['ref'] }}'; search()">
                            <div class="flex items-center gap-4">
                                <div class="w-9 h-9 rounded-lg bg-[#0078C8]/8 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-ship text-[#0078C8] text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $r['ref'] }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $r['desc'] }} · CIF {{ $r['cif'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-8">
                                <div class="text-right hidden md:block">
                                    <p class="text-xs text-gray-400">Calculated</p>
                                    <p class="text-xs text-gray-600 mt-0.5">{{ $r['date'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-gray-900">{{ $r['total_usd'] }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $r['total_pkr'] }}</p>
                                </div>
                                <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border
                                    {{ $r['status'] === 'Cleared' ? 'bg-green-50 text-green-600 border-green-100' : 'bg-amber-50 text-amber-600 border-amber-100' }}">
                                    {{ $r['status'] }}
                                </span>
                                <i class="fa-solid fa-chevron-right text-gray-300 text-xs"></i>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="px-6 py-3 border-t border-gray-100">
                        <p class="text-xs text-gray-400">Showing <span class="font-semibold text-gray-600">3</span> recent calculations · Click any row to load breakdown</p>
                    </div>
                </div>
            </div>

            {{-- Results: Full Breakdown Table --}}
            <div x-show="searched"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                style="display:none;">

                {{-- Total Summary Cards --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Customs Value</p>
                        <p class="text-lg font-black text-gray-800" x-text="usdFmt(cifUsd)"></p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Invoice basis</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Govt. Duties</p>
                        <p class="text-lg font-black text-amber-600" x-text="usdFmt(customsDutyUsd + additionalDutyUsd + salesTaxUsd + incomeTaxUsd)"></p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Customs + FBR</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Service Charges</p>
                        <p class="text-lg font-black text-gray-700" x-text="usdFmt(parseFloat(portHandling||0)+parseFloat(customsAgentFee||0)+parseFloat(inlandTransport||0)+parseFloat(bankCharges||0))"></p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Port + Agent + Transport</p>
                    </div>
                    <div class="bg-[#0078C8]/5 rounded-xl border border-[#0078C8]/20 shadow-sm p-4 text-center">
                        <p class="text-[10px] font-semibold text-[#0078C8]/70 uppercase tracking-wider mb-1">Total Landed Cost</p>
                        <p class="text-lg font-black text-[#0078C8]" x-text="usdFmt(totalUsd)"></p>
                        <p class="text-[10px] text-[#0078C8]/60 mt-0.5" x-text="pkrDirect(totalPkr)"></p>
                    </div>
                </div>

                {{-- Breakdown Table --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">
                                Landed Cost Calculation —
                                <span class="text-[#0078C8]" x-text="shipment"></span>
                            </h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">All costs to bring goods to your warehouse door</p>
                        </div>
                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider hidden md:block">Rate: <span x-text="exchangeRate"></span> PKR/USD</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                                    <th class="px-5 py-3.5 text-[10px] font-semibold uppercase tracking-wider">Cost Component</th>
                                    <th class="px-5 py-3.5 text-[10px] font-semibold uppercase tracking-wider">Type</th>
                                    <th class="px-5 py-3.5 text-[10px] font-semibold uppercase tracking-wider">Amount (USD)</th>
                                    <th class="px-5 py-3.5 text-[10px] font-semibold uppercase tracking-wider">Amount (PKR)</th>
                                    <th class="px-5 py-3.5 text-[10px] font-semibold uppercase tracking-wider">% of CIF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-gray-800">Customs Value</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">Invoice</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(cifUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrDirect(cifUsd * exchangeRate)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">100%</td>
                                </tr>

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-amber-600" x-text="'Customs Duty (' + customsDutyPct + '%)'"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">Government</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(customsDutyUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrFmt(customsDutyUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500" x-text="customsDutyPct + '%'"></td>
                                </tr>

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-amber-600" x-text="'Additional Customs Duty (' + additionalDutyPct + '%)'"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">Government</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(additionalDutyUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrFmt(additionalDutyUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500" x-text="additionalDutyPct + '%'"></td>
                                </tr>

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-[#0078C8]" x-text="'Sales Tax (' + salesTaxPct + '%)'"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">FBR</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(salesTaxUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrFmt(salesTaxUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500" x-text="pct(salesTaxUsd)"></td>
                                </tr>

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-[#0078C8]" x-text="'Income Tax / WHT (' + incomeTaxPct + '%)'"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">FBR</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(incomeTaxUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrFmt(incomeTaxUsd)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500" x-text="pct(incomeTaxUsd)"></td>
                                </tr>

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-[#0078C8]">Port Handling / THC</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">Port Charges</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(portHandling)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrFmt(portHandling)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500" x-text="pct(portHandling)"></td>
                                </tr>

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-gray-800">Customs Agent Fee</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">Service</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(customsAgentFee)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrFmt(customsAgentFee)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500" x-text="pct(customsAgentFee)"></td>
                                </tr>

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-[#0078C8]">Inland Transport (KHI→ISB)</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">Logistics</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(inlandTransport)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrFmt(inlandTransport)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500" x-text="pct(inlandTransport)"></td>
                                </tr>

                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-3.5 text-sm text-[#0078C8]">Bank Charges / LC Fee</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500">Banking</td>
                                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800" x-text="usdFmt(bankCharges)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-700" x-text="pkrFmt(bankCharges)"></td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500" x-text="pct(bankCharges)"></td>
                                </tr>

                                {{-- Total Row --}}
                                <tr class="bg-gray-50/80 border-t-2 border-gray-200">
                                    <td class="px-5 py-4 text-sm font-bold text-gray-900" colspan="2">Total Landed Cost</td>
                                    <td class="px-5 py-4 text-sm font-black text-gray-900" x-text="usdFmt(totalUsd)"></td>
                                    <td class="px-5 py-4 text-sm font-black text-gray-900" x-text="pkrDirect(totalPkr)"></td>
                                    <td class="px-5 py-4 text-sm font-black text-gray-900" x-text="pct(totalUsd)"></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3">
                        <button class="px-5 py-2 bg-[#0078C8] hover:bg-[#006bb0] text-white text-xs font-medium rounded-lg transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-file-excel text-xs"></i> Export to Excel
                        </button>
                        <button class="px-5 py-2 bg-white hover:bg-gray-50 text-gray-700 text-xs font-medium rounded-lg border border-gray-200 transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-paper-plane text-xs text-gray-400"></i> Send to Accounting
                        </button>
                        <p class="ml-auto text-[10px] text-gray-400">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Estimates only — actual duties may vary
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
