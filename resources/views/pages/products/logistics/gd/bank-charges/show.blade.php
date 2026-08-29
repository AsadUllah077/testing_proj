@extends('layouts.auth')

@section('title', 'Bank Charge Details')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">

            @php
                $charge = $charge ?? (object)[
                    'id'             => 1,
                    'ref'            => 'BCH-2026-001',
                    'charge_type'    => 'LC Opening Commission',
                    'bank'           => 'HBL — Habib Bank',
                    'linked_lc'      => 'LC-2026-0041',
                    'charge_date'    => 'Apr 02, 2026',
                    'amount_pkr'     => '185,000',
                    'amount_usd'     => '665',
                    'payment_mode'   => 'Debit from Account',
                    'bank_reference' => 'HBL/TF/2026/4421',
                    'remarks'        => 'LC opening charge for Faizan Textiles shipment. Deducted from current account #0001-7890-1.',
                ];
            @endphp

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">{{ $charge->ref }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Bank Charge · {{ $charge->charge_type }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('logistics.gd.bank-charges.edit', $charge->id) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-2">
                        <i class="fa-solid fa-pen text-[10px]"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.gd.bank-charges.index') }}" />
                </div>
            </div>

            <div class="space-y-4">

                {{-- Charge Details --}}
                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Charge Details</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-y-5 gap-x-8">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Reference</p>
                            <p class="text-sm font-bold text-gray-900">{{ $charge->ref }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Charge Type</p>
                            <p class="text-sm font-medium text-[#0078C8]">{{ $charge->charge_type }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Bank</p>
                            <p class="text-sm font-medium text-gray-900">{{ $charge->bank }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Linked LC / Reference</p>
                            <p class="text-sm text-gray-700">{{ $charge->linked_lc ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Date</p>
                            <p class="text-sm text-gray-700">{{ $charge->charge_date }}</p>
                        </div>
                    </div>
                </div>

                {{-- Amount --}}
                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-sm font-semibold text-gray-800">Amount & Payment</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-y-5 gap-x-8">
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 mb-1">Amount (PKR)</p>
                            <p class="text-2xl font-black text-gray-900">PKR {{ $charge->amount_pkr }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Amount (USD)</p>
                            <p class="text-sm font-medium text-gray-700">{{ $charge->amount_usd ? '$' . $charge->amount_usd : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Payment Mode</p>
                            <p class="text-sm text-gray-700">{{ $charge->payment_mode }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 mb-1">Bank Reference / Receipt No.</p>
                            <p class="text-sm font-mono text-gray-800">{{ $charge->bank_reference ?: '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Remarks --}}
                @if($charge->remarks)
                <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Remarks</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-700 leading-relaxed">{{ $charge->remarks }}</p>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
@endsection
