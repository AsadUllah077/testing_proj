@extends('layouts.auth')
@section('title', 'Maersk VGM')
@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">VGM — Verified Gross Mass</h2>
                    <p class="text-xs text-slate-500 mt-1">Submit and retrieve SOLAS VGM for export containers — API v1.2.0</p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Maersk Hub
                </a>
            </div>

            <div class="mt-4 grid grid-cols-1 xl:grid-cols-3 gap-4">
                <!-- Retrieve VGM -->
                <div class="xl:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="font-semibold text-slate-800 text-sm">Retrieve VGM</h3>
                    </div>
                    <div class="p-6">
                        <form method="GET" action="{{ route('logistics.maersk.vgm') }}">
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="w-full md:w-52">
                                    <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">Shipment
                                        Number <span class="text-red-500">*</span></label>
                                    <input type="text" name="shipmentNumber" value="{{ request('shipmentNumber') }}"
                                        placeholder="e.g. 205284917"
                                        class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                                <div class="w-full md:w-48">
                                    <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">Customer
                                        Code</label>
                                    <input type="text" name="customerCode" value="{{ request('customerCode') }}"
                                        placeholder="e.g. 33100971688"
                                        class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                                <div class="w-full md:w-36">
                                    <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">Brand
                                        Code</label>
                                    <select name="brandCode"
                                        class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                        <option value="MAEU" {{ request('brandCode', 'MAEU') == 'MAEU' ? 'selected' : '' }}>MAEU</option>
                                        <option value="SEAU" {{ request('brandCode') == 'SEAU' ? 'selected' : '' }}>SEAU</option>
                                        <option value="SEJJ" {{ request('brandCode') == 'SEJJ' ? 'selected' : '' }}>SEJJ</option>
                                        <option value="MCPU" {{ request('brandCode') == 'MCPU' ? 'selected' : '' }}>MCPU</option>
                                        <option value="MAEI" {{ request('brandCode') == 'MAEI' ? 'selected' : '' }}>MAEI</option>
                                    </select>
                                </div>
                                <div class="mb-[2px]">
                                    <button type="submit"
                                        class="px-5 py-2 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#006bb0] flex items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i> Retrieve
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if ($error)
                            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                <i class="fa-solid fa-circle-xmark mr-2"></i>{{ $error }}
                            </div>
                        @elseif ($searched && !empty($vgmData))
                            <div class="mt-4 overflow-x-auto">
                                @php $containers = isset($vgmData[0]) ? $vgmData : [$vgmData]; @endphp
                                <table class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-gray-100/50 text-gray-600 border-b border-gray-200">
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Container</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">VGM</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Unit</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Method</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Source</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($containers as $c)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 font-mono font-semibold text-[#0078C8]">{{ $c['containerNumber'] ?? '-' }}</td>
                                                <td class="px-4 py-3 font-bold">{{ $c['vgm'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-gray-600">{{ $c['vgmUnit'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-gray-600">{{ $c['vgmMethod'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-gray-600">{{ $c['vgmSource'] ?? '-' }}</td>
                                                <td class="px-4 py-3">
                                                    @php $st = $c['status'] ?? 'UNKNOWN'; @endphp
                                                    <span class="px-2 py-1 rounded-full text-[11px] font-bold uppercase {{ $st === 'CONFIRMED' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                                        {{ $st }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif ($searched)
                            <div class="mt-4 text-center py-8 text-gray-400 text-sm">No VGM data found.</div>
                        @endif
                    </div>
                </div>

                <!-- Submit VGM -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="font-semibold text-slate-800 text-sm">Submit VGM</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Async — 202 = queued for processing</p>
                    </div>
                    <form id="vgmSubmitForm" class="p-5 space-y-4">
                        @csrf
                        <div>
                            <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Shipment
                                Number <span class="text-red-500">*</span></label>
                            <input type="text" name="shipmentNumber" placeholder="205284917"
                                class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                        </div>
                        <div>
                            <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Container
                                Number <span class="text-red-500">*</span></label>
                            <input type="text" name="containerNumber" placeholder="MAEU1234567"
                                class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">VGM
                                    <span class="text-red-500">*</span></label>
                                <input type="number" name="vgm" placeholder="23000" step="0.01"
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>
                            <div>
                                <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Unit</label>
                                <select name="vgmUnit" class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    <option value="KGS">KGS</option>
                                    <option value="LBS">LBS</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Method</label>
                                <select name="vgmMethod" class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    <option value="CALCULATED">CALCULATED</option>
                                    <option value="SCALED">SCALED</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Brand</label>
                                <select name="brandCode" class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    <option value="MAEU">MAEU</option>
                                    <option value="SEAU">SEAU</option>
                                    <option value="SEJJ">SEJJ</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Authorized Person Name</label>
                            <input type="text" name="authorizedPersonName" placeholder="John Doe"
                                class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                        </div>
                        <div>
                            <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Authorized Person Email</label>
                            <input type="email" name="authorizedPersonEmail" placeholder="john@example.com"
                                class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                        </div>
                        <div>
                            <label class="block mb-1.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Customer Code</label>
                            <input type="text" name="carrierCustomerCode" placeholder="33100971688"
                                class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                        </div>
                        <button type="submit"
                            class="w-full py-2 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                            <i class="fa-solid fa-paper-plane"></i> Submit VGM
                        </button>
                        <div id="vgmResult" class="hidden text-xs"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('vgmSubmitForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

            const formData = Object.fromEntries(new FormData(this));
            fetch('{{ route('logistics.maersk.vgm.submit') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit VGM';
                    const el = document.getElementById('vgmResult');
                    el.className = data.success
                        ? 'mt-2 p-3 rounded bg-emerald-50 border border-emerald-200 text-emerald-700'
                        : 'mt-2 p-3 rounded bg-red-50 border border-red-200 text-red-700';
                    el.textContent = data.message;
                    el.classList.remove('hidden');
                });
        });
    </script>
@endpush
