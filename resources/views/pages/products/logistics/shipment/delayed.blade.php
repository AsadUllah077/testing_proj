@extends('layouts.auth')

@section('title', 'Delayed Shipments')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Shipments</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2">
                    <div></div>
                    <div></div>
                    <x-common.export-dropdown name="shipment" />
                    <a href="#"
                        class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Shipment
                    </a>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex items-center gap-1 mb-4">
                <a href="{{ route('logistics.shipments.index') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors text-gray-500 hover:text-gray-800 hover:bg-white">
                    All Shipments
                </a>
                <a href="{{ route('logistics.shipments.imports') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors text-gray-500 hover:text-gray-800 hover:bg-white">
                    Imports
                </a>
                <a href="{{ route('logistics.shipments.exports') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors text-gray-500 hover:text-gray-800 hover:bg-white">
                    Exports
                </a>
                <a href="{{ route('logistics.shipments.delayed') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-gray-900 text-white flex items-center gap-1.5">
                    Delayed
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-white/20 text-white text-[9px] font-bold">3</span>
                </a>
            </div>

            {{-- Alert Banner --}}
            <div class="mb-4 px-5 py-3.5 bg-red-50 border border-red-100 rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-sm shrink-0"></i>
                <p class="text-sm text-red-700 font-medium">3 shipments require immediate attention — delays may incur demurrage charges.</p>
            </div>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-red-100 shadow-sm px-6 py-5">
                    <p class="text-3xl font-black text-red-500">3</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Delayed</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                    <p class="text-3xl font-black text-amber-500">+8d</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Max Delay</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                    <p class="text-3xl font-black text-gray-700">2</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Import</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5">
                    <p class="text-3xl font-black text-gray-700">1</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Export</p>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @include('pages.products.logistics.shipment.partials.table', [
                    'rows' => [
                        ['id'=>'SH-395','type'=>'Import','route'=>'GBR → ISB','mode'=>'LCL','carrier'=>'Hapag-Lloyd',      'etd'=>'Mar 28','eta'=>'Apr 18','cargo'=>'Machinery · Shared',  'value'=>'$27,000','status'=>'Delayed +4d'],
                        ['id'=>'SH-389','type'=>'Import','route'=>'UAE → KHI','mode'=>'FCL','carrier'=>'MSC',              'etd'=>'Mar 20','eta'=>'Apr 05','cargo'=>'Steel Coils · 1×40ft','value'=>'$53,000','status'=>'Delayed +8d'],
                        ['id'=>'SH-386','type'=>'Export','route'=>'KHI → NL', 'mode'=>'FCL','carrier'=>'Maersk / SEALAND','etd'=>'Mar 18','eta'=>'Apr 10','cargo'=>'Knitwear · 2×20ft',   'value'=>'$41,500','status'=>'Delayed +5d'],
                    ]
                ])
            </div>

        </div>
    </div>
@endsection
