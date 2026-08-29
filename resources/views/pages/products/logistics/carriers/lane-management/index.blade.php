@extends('layouts.auth')

@section('title', 'Logistics Carriers Rate Card - Ocean')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <!-- Module Tabs -->
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
                    <p class="text-xs text-gray-400 mt-0.5"></p>
                </div>
                <div class="flex items-center gap-2">
                    <x-common.export-dropdown name="lc" />
                    <a href="#"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i> New Rates
                    </a>
                </div>
            </div>
            <div class="overflow-hidden">

                <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Lane ID</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Origin</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Destination</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Mode</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Preferred Carrier</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Alt Carrier</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Avg Transit</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Monthly Volume</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Avg Rate</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Risk Level</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900 font-mono">LN-001</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">KHI (Port Qasim)</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">LHR (Felixstowe)</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">🌊 Ocean FCL</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">Maersk</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">MSC</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">25 days</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">18 TEU</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">$2,400/40ft</td>
                                    <td class="px-4 py-3 text-xs">
                                        <span
                                            class="px-2 py-0.5 rounded-full bg-lime-100 text-lime-700 font-semibold">Low</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md transition-colors">Manage</button>
                                    </td>
                                </tr>

                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900 font-mono">LN-002</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">LHE (Airport)</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">IST (Atat&uuml;rk)</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">✈ Air</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">Turkish Airlines</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">Emirates via DXB</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">2 days</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">12 shipments</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">$3.33/kg</td>
                                    <td class="px-4 py-3 text-xs">
                                        <span
                                            class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold">Medium</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md transition-colors">Manage</button>
                                    </td>
                                </tr>

                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900 font-mono">LN-003</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">KHI</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">DXB (Jebel Ali)</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">🌊 Ocean FCL</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">MSC</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">COSCO</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">4 days</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">9 TEU</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">$710/20ft</td>
                                    <td class="px-4 py-3 text-xs">
                                        <span
                                            class="px-2 py-0.5 rounded-full bg-lime-100 text-lime-700 font-semibold">Low</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md transition-colors">Manage</button>
                                    </td>
                                </tr>

                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900 font-mono">LN-004</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">KHI Port</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">ISB Dry Port</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">🚚 Road FTL</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">NLC Pakistan</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">Private Fleet</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">3 days</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">25 trucks</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">PKR 185K/40ft</td>
                                    <td class="px-4 py-3 text-xs">
                                        <span
                                            class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold">Medium</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md transition-colors">Manage</button>
                                    </td>
                                </tr>

                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900 font-mono">LN-005</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">PES (Torkham)</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">KBL (Afghanistan)</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">🚚 TIR Carnet</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">NLC / Private</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">&mdash;</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">1 day</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">6 trucks</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">PKR 45K/truck</td>
                                    <td class="px-4 py-3 text-xs">
                                        <span
                                            class="px-2 py-0.5 rounded-full bg-red-50 text-red-600 font-semibold">High</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md transition-colors">Manage</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
