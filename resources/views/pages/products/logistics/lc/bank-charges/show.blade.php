@extends('layouts.auth')

@section('title', 'Bank Charge Details')

@section('content')
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

            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">{{ $charge->charge_number }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Bank Charge ·
                        {{ $charge->charge_date?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('logistics.lc.bank-charges.edit', $charge->id) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-2">
                        <i class="fa-solid fa-pen text-[10px]"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.lc.bank-charges.index') }}" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Main Details --}}
                <div class="lg:col-span-2 space-y-4">

                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Charge Details</h3>
                        </div>
                        <div class="p-6 grid grid-cols-2 gap-y-5 gap-x-8">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Charge Type</p>
                                <p class="text-sm font-medium text-[#0078C8]">
                                    {{ ucwords(str_replace('_', ' ', $charge->charge_type ?? '—')) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Bank</p>
                                <p class="text-sm font-medium text-gray-900">{{ $charge->bank_name ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Charge Date</p>
                                <p class="text-sm text-gray-700">{{ $charge->charge_date?->format('M d, Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Payment Mode</p>
                                <p class="text-sm text-gray-700">
                                    {{ ucwords(str_replace('_', ' ', $charge->payment_mode ?? '—')) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Bank Reference</p>
                                <p class="text-sm text-gray-700">{{ $charge->bank_reference ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Bank Instrument</p>
                                @if ($charge->lc)
                                    <a href="{{ route('logistics.lc.show', $charge->lc->id) }}"
                                        class="text-sm text-[#0078C8] hover:underline font-medium">{{ $charge->lc->lc_number }}</a>
                                @else
                                    <p class="text-sm text-gray-700">{{ $charge->linked_lc ?: '—' }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($charge->remarks)
                        <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-800">Remarks</h3>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $charge->remarks }}
                                </p>
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Amount Sidebar --}}
                <div class="space-y-4">
                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="text-sm font-semibold text-gray-800">Amount</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="text-center py-4 bg-blue-50 rounded-lg border border-blue-100">
                                <p class="text-2xl font-black text-[#0078C8]">
                                    {{ $charge->charge_currency === 'USD' ? 'USD' : 'PKR' }}
                                    {{ number_format($charge->charge_currency === 'USD' ? $charge->amount_usd : $charge->amount_pkr, 2) }}
                                </p>
                                <p class="text-xs text-blue-400 mt-1 font-medium uppercase tracking-wider">Total Charged</p>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <p class="text-xs text-gray-500">Converted Amount</p>
                                <p class="text-sm font-semibold text-gray-700">
                                    {{ $charge->charge_currency === 'USD' ? 'PKR' : 'USD' }}
                                    {{ number_format($charge->charge_currency === 'USD' ? $charge->amount_pkr : $charge->amount_usd, 2) }}
                                </p>
                            </div>
                            @if ($charge->exchange_rate)
                                <div class="flex justify-between items-center py-2">
                                    <p class="text-xs text-gray-500">Exchange Rate</p>
                                    <p class="text-sm text-gray-700">{{ $charge->exchange_rate }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <x-delete-confirmation />
@endsection
