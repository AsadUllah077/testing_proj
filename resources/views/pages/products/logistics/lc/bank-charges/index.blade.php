@extends('layouts.auth')

@section('title', 'Bank Charges')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.lc') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">

            {{-- Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Bank Charges Register</h2>
                    <p class="text-xs text-gray-400 mt-0.5">All LC-related and trade finance charges</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.lc.index') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-file-invoice-dollar text-xs"></i> LCs
                    </a>
                    <a href="{{ route('logistics.lc.bank-charges.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i> Record Charge
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-lg font-black text-[#0078C8]">PKR {{ number_format($stats['total_pkr'] / 1000, 0) }}K</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Total This Month</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-lg font-black text-amber-500">PKR
                        {{ number_format($stats['lc_opening_pkr'] / 1000, 0) }}K</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">LC Opening Fees</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-gray-800">{{ $stats['count'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Transactions</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-lg font-black text-green-600">PKR {{ number_format($stats['avg_pkr'] / 1000, 0) }}K</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Avg. Per Charge</p>
                </div>
            </div>

            {{-- Filters + Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <x-common.filter-form action="{{ route('logistics.lc.bank-charges.index') }}">
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Charge Type</label>
                            <select name="charge_type"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Types</option>
                                <option value="lc_opening_commission"
                                    {{ request('charge_type') === 'lc_opening_commission' ? 'selected' : '' }}>LC Opening
                                    Commission</option>
                                <option value="lc_amendment_fee"
                                    {{ request('charge_type') === 'lc_amendment_fee' ? 'selected' : '' }}>LC Amendment
                                    Fee</option>
                                <option value="swift_telex"
                                    {{ request('charge_type') === 'swift_telex' ? 'selected' : '' }}>SWIFT / Telex
                                </option>
                                <option value="acceptance_commission"
                                    {{ request('charge_type') === 'acceptance_commission' ? 'selected' : '' }}>Acceptance
                                    Commission</option>
                                <option value="discrepancy_fee"
                                    {{ request('charge_type') === 'discrepancy_fee' ? 'selected' : '' }}>Discrepancy
                                    Fee</option>
                                <option value="bank_guarantee_fee"
                                    {{ request('charge_type') === 'bank_guarantee_fee' ? 'selected' : '' }}>Bank
                                    Guarantee Fee</option>
                                <option value="negotiation_fee"
                                    {{ request('charge_type') === 'negotiation_fee' ? 'selected' : '' }}>Negotiation
                                    Fee</option>
                                <option value="reimbursement_fee"
                                    {{ request('charge_type') === 'reimbursement_fee' ? 'selected' : '' }}>
                                    Reimbursement Fee</option>
                                <option value="other" {{ request('charge_type') === 'other' ? 'selected' : '' }}>Other
                                </option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Bank</label>
                            <select name="bank"
                                class="select2 w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Banks</option>
                                @foreach ($banks as $bankKey => $bankName)
                                    <option value="{{ $bankKey }}"
                                        {{ request('bank') === $bankKey ? 'selected' : '' }}>
                                        {{ $bankName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <x-common.date-filter name="start_date" id="start_date" value="{{ request('start_date') }}"
                            label="From Date" />
                        <x-common.date-filter name="end_date" id="end_date" value="{{ request('end_date') }}"
                            label="To Date" />
                        <x-common.search-filter name="q" value="{{ request('q') }}" label="Search" id="search" />
                        <x-filter-button />
                    </x-common.filter-form>
                </div>

                @include('pages.products.logistics.lc.bank-charges.partials.table')
            </div>

        </div>
    </div>
    <x-delete-confirmation />
@endsection
