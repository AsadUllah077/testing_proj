@extends('layouts.auth')

@section('title', 'Duty & Tax Calculator')

@section('content')
    <div class="mt-4" x-data="{
        hsCode: '',
        cifUsd: '',
        exchangeRate: 278.40,
        customsDuty: '',
        additionalDuty: 3,
        regulatoryDuty: 0,
        salesTax: 17,
        incomeTax: 2,

        cifPkr: 0,
        customsDutyAmt: 0,
        additionalDutyAmt: 0,
        regulatoryDutyAmt: 0,
        salesTaxAmt: 0,
        incomeTaxAmt: 0,
        totalDuty: 0,
        landedCost: 0,

        calculate() {
            const cif = parseFloat(this.cifUsd) || 0;
            const rate = parseFloat(this.exchangeRate) || 0;
            const cd = parseFloat(this.customsDuty) || 0;
            const acd = parseFloat(this.additionalDuty) || 0;
            const rd = parseFloat(this.regulatoryDuty) || 0;
            const st = parseFloat(this.salesTax) || 0;
            const it = parseFloat(this.incomeTax) || 0;

            this.cifPkr = cif * rate;
            this.customsDutyAmt = this.cifPkr * (cd / 100);
            this.additionalDutyAmt = this.cifPkr * (acd / 100);
            this.regulatoryDutyAmt = this.cifPkr * (rd / 100);
            const assessable = this.cifPkr + this.customsDutyAmt + this.additionalDutyAmt + this.regulatoryDutyAmt;
            this.salesTaxAmt = assessable * (st / 100);
            this.incomeTaxAmt = this.cifPkr * (it / 100);
            this.totalDuty = this.customsDutyAmt + this.additionalDutyAmt + this.regulatoryDutyAmt + this.salesTaxAmt + this.incomeTaxAmt;
            this.landedCost = this.cifPkr + this.totalDuty;
        },

        fmt(n) {
            return 'PKR ' + Number(n).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }
    }">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.gd') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">Duty & Tax Calculator</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Estimate Pakistan customs duties before filing a GD</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-[#0078C8] hidden md:block">Based on Pakistan Customs Tariff — Fifth
                        Schedule</span>
                    <x-common.back-button url="{{ route('logistics.gd.index') }}" />
                </div>
            </div>

            {{-- Calculator Card --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6">

                    {{-- Inputs --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">HS Code</label>
                            <input type="text" x-model="hsCode" placeholder="e.g. 84713000"
                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Customs Value
                                (USD)</label>
                            <input type="number" x-model="cifUsd" placeholder="e.g. 38500"
                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">USD/PKR Exchange
                                Rate</label>
                            <input type="number" x-model="exchangeRate"
                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Customs Duty
                                %</label>
                            <input type="number" x-model="customsDuty" placeholder="e.g. 15"
                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Additional
                                Customs Duty %</label>
                            <input type="number" x-model="additionalDuty"
                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Regulatory Duty
                                %</label>
                            <input type="number" x-model="regulatoryDuty"
                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Sales Tax
                                %</label>
                            <input type="number" x-model="salesTax"
                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Income Tax / WHT
                                %</label>
                            <input type="number" x-model="incomeTax"
                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                        </div>

                    </div>

                    {{-- Button --}}
                    <div class="mt-6">
                        <button @click="calculate()"
                            class="px-6 py-2.5 bg-[#0078C8] hover:bg-[#006bb0] text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            Calculate Duties
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>

                    {{-- Result Boxes --}}
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">

                        <div class="rounded-xl border border-gray-200 px-6 py-5 text-center">
                            <p class="text-2xl font-black text-gray-900" x-text="fmt(cifPkr)"></p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1.5">Customs Value (PKR)</p>
                        </div>

                        <div class="rounded-xl border border-gray-200 px-6 py-5 text-center">
                            <p class="text-2xl font-black text-[#0078C8]" x-text="fmt(customsDutyAmt)"></p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1.5">Customs Duty
                            </p>
                        </div>

                        <div class="rounded-xl border border-gray-200 px-6 py-5 text-center">
                            <p class="text-2xl font-black text-purple-600" x-text="fmt(salesTaxAmt)"></p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1.5">Sales Tax</p>
                        </div>

                        <div class="rounded-xl border border-gray-200 px-6 py-5 text-center">
                            <p class="text-2xl font-black text-amber-500" x-text="fmt(incomeTaxAmt)"></p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1.5">Income Tax
                            </p>
                        </div>

                        <div class="rounded-xl border-2 border-red-400 px-6 py-5 text-center">
                            <p class="text-2xl font-black text-red-500" x-text="fmt(totalDuty)"></p>
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mt-1.5">Total Duty (PKR)
                            </p>
                        </div>

                        <div class="rounded-xl border border-gray-200 px-6 py-5 text-center">
                            <p class="text-2xl font-black text-green-600" x-text="fmt(landedCost)"></p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1.5">Landed Cost
                                (PKR)</p>
                        </div>

                    </div>

                    <p class="text-[10px] text-gray-400 mt-4">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        This estimate is based on the values entered above and Pakistan Customs Tariff (Fifth Schedule).
                        Actual assessed duties may vary. Consult your customs agent before filing.
                    </p>

                </div>
            </div>
        </div>
    </div>
@endsection
