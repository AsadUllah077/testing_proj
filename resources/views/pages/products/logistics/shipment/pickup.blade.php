@extends('layouts.auth')

@section('title', 'Shipment Pickup Details')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#0078C8]/10 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-truck-pickup text-[#0078C8] text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-gray-900 text-xl font-normal">Pickup Execution <span
                                class="text-slate-400 text-xs ml-2 font-bold uppercase tracking-widest">Shipment
                                #SHP-1001</span></h1>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Track arrival,
                            loading verification and dispatch</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button"
                        class="px-4 py-2 bg-emerald-500 text-white text-[11px] font-black rounded-lg uppercase hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-100 flex items-center gap-2">
                        <i class="fa-solid fa-check-double"></i>
                        Confirm Dispatch
                    </button>
                    <x-common.back-button url="{{ route('logistics.shipments.index') }}" />
                </div>
            </div>

            <!-- Progress Timeline -->
            {{-- <div class="bg-white rounded-xl border border-slate-200 p-6 mb-8 shadow-sm">
                <div class="flex items-center w-full">
                    <!-- Step 1 -->
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full bg-[#0078C8] text-white flex items-center justify-center mb-2">
                            <span class="material-symbols-outlined">calendar_today</span>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-wider text-[#0078C8]">Scheduled</p>
                        <p class="text-[10px] text-slate-500">Oct 24, 08:00 AM</p>
                    </div>
                    <div class="h-1 flex-1 bg-[#0078C8]"></div>
                    <!-- Step 2 -->
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 rounded-full bg-[#0078C8] text-white flex items-center justify-center mb-2 ring-4 ring-[#0078C8]/20">
                            <span class="material-symbols-outlined">location_on</span>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-wider text-[#0078C8]">Arrived</p>
                        <p class="text-[10px] text-slate-500">Oct 24, 09:15 AM</p>
                    </div>
                    <div class="h-1 flex-1 bg-[#0078C8]/20"></div>
                    <!-- Step 3 -->
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 rounded-full bg-white border-2 border-[#0078C8] text-[#0078C8] flex items-center justify-center mb-2">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-wider text-[#0078C8]">Loading</p>
                        <p class="text-[10px] text-[#0078C8] font-medium italic">In Progress</p>
                    </div>
                    <div class="h-1 flex-1 bg-slate-200"></div>
                    <!-- Step 4 -->
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 rounded-full bg-slate-100 border-2 border-slate-200 text-slate-400 flex items-center justify-center mb-2">
                            <span class="material-symbols-outlined">route</span>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Dispatched</p>
                        <p class="text-[10px] text-slate-500">Pending</p>
                    </div>
                </div>
            </div> --}}

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-12">
                <!-- Left Column: Pickup & Loading Details -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Pickup Details Section -->
                    <section class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4 bg-slate-50/50">
                            <h3 class="font-bold flex items-center gap-2 text-slate-800 text-sm uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[#0078C8]">info</span>
                                1. Pickup Details & Arrival
                            </h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Actual
                                    Arrival Time</label>
                                <input
                                    class="w-full rounded-lg border-slate-300 text-sm focus:ring-[#0078C8] focus:border-[#0078C8] px-4 py-2.5 bg-slate-50"
                                    type="datetime-local" value="2023-10-24T09:15" />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Driver
                                    Contact</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        class="flex-1 rounded-lg border-slate-300 bg-slate-100 text-sm italic py-2.5 px-4"
                                        readonly="" type="text" value="+1 555-0192" />
                                    <button type="button"
                                        class="p-2.5 rounded-lg bg-[#0078C8]/10 text-[#0078C8] hover:bg-[#0078C8]/20 transition-all">
                                        <span class="material-symbols-outlined text-lg">call</span>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Gate
                                    Entry ID</label>
                                <input class="w-full rounded-lg border-slate-300 text-sm font-mono py-2.5 px-4 bg-slate-50"
                                    type="text" value="GT-7721" />
                            </div>
                            <div class="flex items-end">
                                <label
                                    class="flex items-center gap-3 cursor-pointer p-2.5 rounded-lg border border-slate-200 w-full bg-blue-50/30">
                                    <input checked="" class="rounded text-[#0078C8] focus:ring-[#0078C8] h-5 w-5"
                                        type="checkbox" />
                                    <span class="text-sm font-bold text-slate-700">Vehicle Confirmed On-Site</span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <!-- Comparison Grid -->
                    <section class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4 bg-slate-50/50 flex justify-between items-center">
                            <h3 class="font-bold flex items-center gap-2 text-slate-800 text-sm uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[#0078C8]">list_alt</span>
                                2. Loading Confirmation Grid
                            </h3>
                            <span
                                class="text-[10px] px-2.5 py-1 rounded bg-amber-100 text-amber-700 font-black uppercase tracking-widest">2
                                Discrepancies</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        <th class="px-6 py-4 border-b border-slate-100">SKU / Item</th>
                                        <th class="px-6 py-4 border-b border-slate-100 text-center">Ordered</th>
                                        <th class="px-6 py-4 border-b border-slate-100 text-center">Loaded</th>
                                        <th class="px-6 py-4 border-b border-slate-100 text-center">Short/Dam</th>
                                        <th class="px-6 py-4 border-b border-slate-100">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-slate-100">
                                    <!-- Row 1 -->
                                    <tr class="hover:bg-slate-50/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-800 text-xs">#EL-204</p>
                                            <p class="text-[10px] text-slate-500 font-medium">Electric Drills</p>
                                        </td>
                                        <td class="px-6 py-4 text-center font-bold text-slate-600">400</td>
                                        <td class="px-6 py-4 text-center">
                                            <input
                                                class="w-20 rounded border-slate-300 py-1.5 text-sm text-center font-bold focus:ring-[#0078C8]"
                                                type="number" value="400" />
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="px-2 py-0.5 bg-green-50 text-green-600 text-[10px] font-black uppercase rounded border border-green-100">Match</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input
                                                class="w-full rounded border-slate-200 py-1.5 text-xs px-3 focus:ring-[#0078C8]"
                                                placeholder="Add remark..." type="text" />
                                        </td>
                                    </tr>
                                    <!-- Row 2 - Shortage -->
                                    <tr class="bg-red-50/30">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-800 text-xs">#SM-110</p>
                                            <p class="text-[10px] text-slate-500 font-medium">Solar Modules</p>
                                        </td>
                                        <td class="px-6 py-4 text-center font-bold text-slate-600">120</td>
                                        <td class="px-6 py-4 text-center">
                                            <input
                                                class="w-20 rounded border-red-200 py-1.5 text-sm text-center font-black text-red-600 focus:ring-red-500"
                                                type="number" value="115" />
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-black uppercase rounded">-5
                                                (Short)</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input
                                                class="w-full rounded border-red-200 py-1.5 text-xs px-3 bg-white focus:ring-red-500"
                                                type="text" value="Damaged box at loading bay" />
                                        </td>
                                    </tr>
                                    <!-- Row 3 -->
                                    <tr class="hover:bg-slate-50/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-800 text-xs">#BT-992</p>
                                            <p class="text-[10px] text-slate-500 font-medium">Lithium Batteries</p>
                                        </td>
                                        <td class="px-6 py-4 text-center font-bold text-slate-600">80</td>
                                        <td class="px-6 py-4 text-center">
                                            <input
                                                class="w-20 rounded border-slate-300 py-1.5 text-sm text-center font-bold focus:ring-[#0078C8]"
                                                type="number" value="80" />
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="px-2 py-0.5 bg-green-50 text-green-600 text-[10px] font-black uppercase rounded border border-green-100">Match</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input
                                                class="w-full rounded border-slate-200 py-1.5 text-xs px-3 focus:ring-[#0078C8]"
                                                placeholder="Add remark..." type="text" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Documents & Sidebar -->
                <div class="space-y-8">
                    <!-- Documents Panel -->
                    <section class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4 bg-slate-50/50">
                            <h3 class="font-bold flex items-center gap-2 text-slate-800 text-sm uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[#0078C8]">description</span>
                                3. Documentation
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- BOL Upload -->
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2.5">Bill
                                    of Lading (BOL)</label>
                                <div
                                    class="flex items-center justify-between p-3 border border-dashed border-slate-300 rounded-lg bg-slate-50 group hover:border-[#0078C8] transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-red-500">picture_as_pdf</span>
                                        <span class="text-xs font-bold text-slate-700">BOL_VH9928_SIGNED.pdf</span>
                                    </div>
                                    <button type="button"
                                        class="text-[#0078C8] hover:underline text-[10px] font-black uppercase tracking-wider">View</button>
                                </div>
                            </div>
                            <!-- Loading Photos -->
                            <div>
                                <div class="flex justify-between items-center mb-2.5">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Loading
                                        Photos</label>
                                    <button type="button"
                                        class="text-[10px] text-[#0078C8] font-black uppercase tracking-widest hover:underline">+
                                        Upload</button>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div
                                        class="aspect-square bg-slate-100 rounded-lg overflow-hidden border border-slate-200 group relative shadow-inner">
                                        <img alt="Warehouse loading bay" class="w-full h-full object-cover"
                                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuAXaI0ZTQu1oDhNce-MerXO1dDO9efBrCNhEJGxfMrI17Kr55vkC_qoIcmKkBKsv8sIHkbluZYARU6cBVpvbsi5fKX8XaB4K0lmwP9DK1JDMwpjwJ6ViEXhVGam9gRwqJYj6MUNtyTTpDNBkPSuDW_aPJlpxOFimrBzKhz1AopIfugqrsklG46tjzoOHTINenwMQ0zruDH9gDBMrv8Hw5Qc2AZtMfk_uz-lLv3mFflNhhPQZ90TjuekbERd-8AxiRdWjSaw8N7wZ7av" />
                                        <div
                                            class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                            <span
                                                class="material-symbols-outlined text-white cursor-pointer">visibility</span>
                                        </div>
                                    </div>
                                    <div
                                        class="aspect-square bg-slate-100 rounded-lg overflow-hidden border border-slate-200 group relative shadow-inner">
                                        <img alt="Truck interior loaded" class="w-full h-full object-cover"
                                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuDvQC_LQHfwvCqHUmzCeQjX-prM_ERa-Boup3X2Dm8LM-cGi4gy7nalNQ_ZPzqV_f2PnZPLw98RMyVlrECj-EsYJGwB0yRIpvEFxij6RD3NVBSscYMm_uGfIYMY3YTkKeTbV10n2zpY-TGqsNdnMRpRWCV2TvkEocfJFUIKYEMbzxwcbn-Zy091WOF8qsqcTBxQk1roAXr6M52_L95QCi38aB5K8T45FyANpquChcVRqTjZ3uBYIyG-Qiuwis1rYQtJ6NtjYng0a_pM" />
                                        <div
                                            class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                            <span
                                                class="material-symbols-outlined text-white cursor-pointer">visibility</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="border-slate-100" />
                            <!-- ETA Update -->
                            <div>
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2.5">Estimated
                                    Time of Arrival (DC)</label>
                                <div class="flex gap-2">
                                    <input
                                        class="flex-1 rounded-lg border-slate-300 text-sm py-2 px-3 focus:ring-[#0078C8]"
                                        type="time" value="14:30" />
                                    <button
                                        class="px-4 py-2 bg-slate-100 rounded-lg text-[10px] font-black uppercase text-slate-600 hover:bg-slate-200 transition-all">Update</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
