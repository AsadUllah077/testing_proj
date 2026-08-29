@extends('layouts.auth')
@section('title', 'Maersk API Integration')
@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#00243D] rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-ship text-white text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Maersk API Integration</h2>
                        <p class="text-xs text-slate-500 mt-0.5">A.P. Moller-Maersk shipping & logistics APIs</p>
                    </div>
                </div>
                <a href="{{ route('logistics.maersk.settings') }}"
                    class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-2">
                    <i class="fa-solid fa-gear"></i> API Settings
                </a>
            </div>

            @if (!$configured)
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">API credentials not configured</p>
                        <p class="text-xs text-amber-700 mt-1">Please <a href="{{ route('logistics.maersk.settings') }}"
                                class="underline font-medium">configure your Maersk Consumer Key and Secret</a> before using any features.</p>
                    </div>
                </div>
            @else
                <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                    <p class="text-sm text-emerald-700 font-medium">API credentials configured — all features are available.</p>
                </div>
            @endif

            <!-- Feature Cards -->
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">

                {{-- Track & Trace --}}
                <a href="{{ route('logistics.maersk.track-trace') }}"
                    class="bg-white rounded-xl border border-slate-200 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-blue-50 rounded-lg flex items-center justify-center group-hover:bg-blue-100 transition-colors shrink-0">
                            <i class="fa-solid fa-location-dot text-[#0078C8] text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">Track & Trace</h3>
                            <p class="text-xs text-slate-500 mt-1">Track shipments by booking reference, Bill of Lading, or container number. View all equipment and transport events.</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">v1.5.0 &middot; Auth required</span>
                        </div>
                    </div>
                </a>

                {{-- Shipment Deadlines --}}
                <a href="{{ route('logistics.maersk.deadlines') }}"
                    class="bg-white rounded-xl border border-slate-200 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-orange-50 rounded-lg flex items-center justify-center group-hover:bg-orange-100 transition-colors shrink-0">
                            <i class="fa-solid fa-clock text-orange-500 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">Shipment Deadlines</h3>
                            <p class="text-xs text-slate-500 mt-1">Check cargo cutoff, VGM, shipping instructions, and dangerous goods deadlines by vessel and voyage.</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">v2.3.0 &middot; Consumer-Key only</span>
                        </div>
                    </div>
                </a>

                {{-- Commodities --}}
                <a href="{{ route('logistics.maersk.commodities') }}"
                    class="bg-white rounded-xl border border-slate-200 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-teal-50 rounded-lg flex items-center justify-center group-hover:bg-teal-100 transition-colors shrink-0">
                            <i class="fa-solid fa-boxes-stacked text-teal-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">Commodities Reference</h3>
                            <p class="text-xs text-slate-500 mt-1">Look up Maersk commodity codes and HS codes by name, code, or cargo type (DRY, REEFER, OOG, DANGEROUS).</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">v1.1.0 &middot; Consumer-Key only</span>
                        </div>
                    </div>
                </a>

                {{-- VGM --}}
                <a href="{{ route('logistics.maersk.vgm') }}"
                    class="bg-white rounded-xl border border-slate-200 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-purple-50 rounded-lg flex items-center justify-center group-hover:bg-purple-100 transition-colors shrink-0">
                            <i class="fa-solid fa-weight-scale text-purple-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">VGM — Verified Gross Mass</h3>
                            <p class="text-xs text-slate-500 mt-1">Submit and retrieve SOLAS-compliant VGM for export containers. Required before loading.</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">v1.2.0 &middot; Auth required</span>
                        </div>
                    </div>
                </a>

                {{-- Invoices --}}
                <a href="{{ route('logistics.maersk.invoices') }}"
                    class="bg-white rounded-xl border border-slate-200 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-green-50 rounded-lg flex items-center justify-center group-hover:bg-green-100 transition-colors shrink-0">
                            <i class="fa-solid fa-file-invoice-dollar text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">Ocean Invoices</h3>
                            <p class="text-xs text-slate-500 mt-1">View open, paid, overdue, and credit invoices by customer code. Search by BOL or invoice number.</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">v1.1.2 &middot; Auth required</span>
                        </div>
                    </div>
                </a>

                {{-- Ocean Credit Summary --}}
                <a href="{{ route('logistics.maersk.credits') }}"
                    class="bg-white rounded-xl border border-slate-200 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-emerald-50 rounded-lg flex items-center justify-center group-hover:bg-emerald-100 transition-colors shrink-0">
                            <i class="fa-solid fa-receipt text-emerald-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">Ocean Credit Summary</h3>
                            <p class="text-xs text-slate-500 mt-1">View refundable and non-refundable credit notes. Search by BOL or invoice number.</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">v2.0.1 &middot; Auth required &middot; MAEU only</span>
                        </div>
                    </div>
                </a>

                {{-- Demurrage & Detention --}}
                <a href="{{ route('logistics.maersk.demurrage') }}"
                    class="bg-white rounded-xl border border-slate-200 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-red-50 rounded-lg flex items-center justify-center group-hover:bg-red-100 transition-colors shrink-0">
                            <i class="fa-solid fa-hourglass-half text-red-500 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">Demurrage & Detention</h3>
                            <p class="text-xs text-slate-500 mt-1">Check free time and accrued charges for import containers at port (demurrage) or customer premises (detention).</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">v1.1.0 &middot; Auth required</span>
                        </div>
                    </div>
                </a>

                {{-- Bill of Lading --}}
                <a href="{{ route('logistics.maersk.bill-of-lading') }}"
                    class="bg-white rounded-xl border border-slate-200 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-indigo-50 rounded-lg flex items-center justify-center group-hover:bg-indigo-100 transition-colors shrink-0">
                            <i class="fa-solid fa-file-lines text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">Bill of Lading</h3>
                            <p class="text-xs text-slate-500 mt-1">Retrieve full Bill of Lading details including cargo, parties, and charges using DCSA standard v2.0.</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">v1.2.0 &middot; Auth required</span>
                        </div>
                    </div>
                </a>

                {{-- Settings --}}
                <a href="{{ route('logistics.maersk.settings') }}"
                    class="bg-white rounded-xl border border-dashed border-slate-300 p-5 hover:border-[#0078C8] hover:shadow-md transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-slate-50 rounded-lg flex items-center justify-center group-hover:bg-slate-100 transition-colors shrink-0">
                            <i class="fa-solid fa-gear text-slate-500 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 text-sm">API Settings</h3>
                            <p class="text-xs text-slate-500 mt-1">Configure your Maersk Developer Portal Consumer Key and Secret. Test the connection.</p>
                            <span class="mt-2 inline-block text-xs text-slate-400">Required for all features</span>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </div>
@endsection
