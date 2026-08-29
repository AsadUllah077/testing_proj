@extends('layouts.auth')

@section('title', 'Logistics Carriers')

@section('content')
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-4 mb-0 text-sm text-green-800 rounded-lg bg-green-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Success!</span> {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="p-4 mb-0 text-sm text-amber-800 rounded-lg bg-amber-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Warning!</span> {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 mb-0 text-sm text-red-800 rounded-lg bg-red-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Error!</span> {{ session('error') }}
        </div>
    @endif
    <x-acccount.loader />
    <div class="mt-4">
        {{-- Module Tabs --}}
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
                    <h2 class="text-stone-950 text-xl font-normal">Carriers</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $carriers->total() }} carriers found</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.carriers.v2.download-template') }}"
                        class="px-3 py-2 bg-white text-gray-700 border border-gray-300 text-xs font-medium rounded hover:bg-gray-50 flex items-center gap-2">
                        <i class="fa-solid fa-download"></i> Template
                    </a>
                    <button onclick="openCarrierImportModal()"
                        class="px-3 py-2 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 flex items-center gap-2">
                        <i class="fa-solid fa-file-excel"></i> Import
                    </button>
                    <x-common.export-dropdown name="carrier" />
                    <a href="{{ route('logistics.carriers.v2.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i> New Carrier
                    </a>
                </div>
            </div>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
                <div class="bg-white border border-black/10 rounded-xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-semibold text-[#0078C8]">{{ $carriers->where('status', 'approved')->count() }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">Approved Carriers</div>
                </div>
                <div class="bg-white border border-black/10 rounded-xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $carriers->where('transport_modes', 'ocean_liner')->count() }}</div>
                    <div class="text-xs text-gray-400 mt-1">Ocean Lines</div>
                </div>
                <div class="bg-white border border-black/10 rounded-xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $carriers->where('transport_modes', 'air_freight')->count() }}</div>
                    <div class="text-xs text-gray-400 mt-1">Air Cargo</div>
                </div>
                <div class="bg-white border border-black/10 rounded-xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $carriers->where('transport_modes', 'inland_trucking')->count() }}</div>
                    <div class="text-xs text-gray-400 mt-1">Road / Inland</div>
                </div>
                <div class="bg-white border border-black/10 rounded-xl p-4 text-center shadow-sm">
                    <div class="text-2xl font-semibold text-amber-500">
                        {{ $carriers->where('status', 'pending_approval')->count() }}</div>
                    <div class="text-xs text-gray-400 mt-1">Pending Approval</div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white border border-black/10 rounded-xl shadow-sm p-4 mb-4">
                <form method="GET" action="{{ route('logistics.carriers.v2.index') }}"
                    class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="text-xs text-gray-500 font-medium block mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Name, email, SCAC code..."
                            class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8]" />
                    </div>
                    <div class="min-w-[150px]">
                        <label class="text-xs text-gray-500 font-medium block mb-1">Status</label>
                        <select name="status"
                            class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8]">
                            <option value="">All Statuses</option>
                            @foreach (\App\Models\LogisticsV2Carriers::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[150px]">
                        <label class="text-xs text-gray-500 font-medium block mb-1">Transport Mode</label>
                        <select name="transport_mode"
                            class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8]">
                            <option value="">All Modes</option>
                            @foreach (\App\Models\LogisticsV2Carriers::TRANSPORT_MODES as $key => $label)
                                <option value="{{ $key }}" @selected(request('transport_mode') === $key)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'status', 'transport_mode']))
                        <a href="{{ route('logistics.carriers.v2.index') }}"
                            class="px-4 py-2 bg-gray-100 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Carrier</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Mode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Country</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Services</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Contact</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Payment</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Added By</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($carriers as $carrier)
                                @php
                                    $initials = collect(
                                        explode(' ', $carrier->trading_brand_name ?: $carrier->carrier_company_name),
                                    )
                                        ->take(3)
                                        ->map(fn($w) => strtoupper($w[0] ?? ''))
                                        ->implode('');
                                    $colors = [
                                        '#0091CD',
                                        '#FF6B2B',
                                        '#E30613',
                                        '#005C9F',
                                        '#C60C30',
                                        '#007DC6',
                                        '#4B286D',
                                        '#0B8043',
                                        '#6B21A8',
                                        '#0F766E',
                                    ];
                                    $color = $colors[crc32($carrier->carrier_company_name) % count($colors)];
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    {{-- Carrier --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-[10px] font-bold shrink-0"
                                                style="background:{{ $color }}">{{ $initials }}</div>
                                            <div>
                                                <div class="font-semibold text-gray-900 text-xs">
                                                    {{ $carrier->trading_brand_name ?: $carrier->carrier_company_name }}
                                                </div>
                                                @if ($carrier->trading_brand_name)
                                                    <div class="text-[10px] text-gray-400">
                                                        {{ $carrier->carrier_company_name }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Mode --}}
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        @php
                                            $modeIcons = [
                                                'ocean_liner' => '🌊',
                                                'air_freight' => '✈',
                                                'inland_trucking' => '🚛',
                                                'rail_freight' => '🚂',
                                                'multimodal' => '🔀',
                                            ];
                                        @endphp
                                        {{ $modeIcons[$carrier->transport_modes] ?? '' }}
                                        {{ \App\Models\LogisticsV2Carriers::TRANSPORT_MODES[$carrier->transport_modes] ?? $carrier->transport_modes }}
                                    </td>

                                    {{-- Country --}}
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ $carrier->country_of_incorporation }}
                                        @if ($carrier->pakistan_liaison_office)
                                            <div class="text-[10px] text-gray-400">{{ $carrier->pakistan_liaison_office }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Services --}}
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ \App\Models\LogisticsV2Carriers::SERVICES_OFFERED[$carrier->services_offered] ?? '—' }}
                                    </td>

                                    {{-- Contact --}}
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <div>{{ $carrier->contact_person }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $carrier->contact_email }}</div>
                                    </td>

                                    {{-- Payment --}}
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ \App\Models\LogisticsV2Carriers::PAYMENT_TERMS[$carrier->payment_terms] ?? '—' }}
                                        @if ($carrier->credit_limit_usd)
                                            <div class="text-[10px] text-gray-400">USD
                                                {{ number_format($carrier->credit_limit_usd) }}</div>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-3">
                                        @php
                                            $statusStyles = [
                                                'draft' => 'bg-gray-100 text-gray-600',
                                                'pending_approval' => 'bg-amber-100 text-amber-700',
                                                'approved' => 'bg-green-100 text-green-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                            ];
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusStyles[$carrier->status] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ \App\Models\LogisticsV2Carriers::STATUSES[$carrier->status] ?? $carrier->status }}
                                        </span>
                                    </td>

                                    {{-- Added By --}}
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ $carrier->createdBy?->name ?? '—' }}
                                        <div class="text-[10px] text-gray-400">{{ $carrier->created_at->format('d M Y') }}
                                        </div>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3">
                                        <div class="flex gap-1.5 items-center">
                                            <a href="{{ route('logistics.carriers.v2.show', $carrier) }}"
                                                class="px-2.5 py-1 text-[11px] font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                                View
                                            </a>
                                            <a href="{{ route('logistics.carriers.v2.edit', $carrier) }}"
                                                class="px-2.5 py-1 text-[11px] font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                                Edit
                                            </a>
                                            <form action="{{ route('logistics.carriers.v2.destroy', $carrier) }}"
                                                method="POST" onsubmit="return confirm('Delete this carrier?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-2.5 py-1 text-[11px] font-medium bg-red-50 hover:bg-red-100 text-red-600 rounded-md transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-12 text-center text-gray-400 text-sm">
                                        No carriers found. <a href="{{ route('logistics.carriers.v2.create') }}"
                                            class="text-[#0078C8] hover:underline">Add the first one.</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($carriers->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $carriers->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Import Carriers Excel
    ═══════════════════════════════════════════════════════════ --}}
    <div id="carrierImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-md mx-4 overflow-hidden">
            <form id="importCarriersForm" action="{{ route('logistics.carriers.v2.import') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="relative px-6 py-8 bg-gradient-to-br from-green-600 to-green-700 text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-excel text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Import Carriers</h3>
                            <p class="text-green-100 text-xs opacity-90">Upload your Excel or CSV carrier file</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeCarrierImportModal()"
                        class="absolute top-4 right-4 text-white/50 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="px-8 py-8 space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Data Source</label>
                            <a href="{{ route('logistics.carriers.v2.download-template') }}"
                                class="text-[10px] font-bold text-green-600 hover:text-green-700 underline uppercase tracking-tighter">
                                Download Template
                            </a>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-paperclip text-gray-400"></i>
                            </div>
                            <input type="file" name="file" id="carrierImportFileInput" accept=".xlsx,.csv,.xls" required
                                class="block w-full pl-10 pr-3 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-xl focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all file:hidden cursor-pointer bg-gray-50/50 hover:bg-gray-50"
                                onchange="carrierUpdateFileName(this)">
                            <div id="carrierFileStatusLabel" class="mt-2 text-[10px] text-gray-400 italic">No file selected</div>
                        </div>
                    </div>

                    {{-- Status hint --}}
                    <div class="bg-blue-50 rounded-xl border border-blue-100 px-4 py-3 text-xs text-blue-700">
                        <p class="font-bold uppercase tracking-wider text-[10px] text-blue-500 mb-2">Status Column Values</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-semibold text-[10px]">draft</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold text-[10px]">pending_approval</span>
                            <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-semibold text-[10px]">approved</span>
                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-semibold text-[10px]">rejected</span>
                        </div>
                        <p class="text-[10px] text-blue-400 mt-1">Any other value defaults to <strong>pending_approval</strong>.</p>
                    </div>

                    {{-- Progress Bar --}}
                    <div id="carrierImportProgressWrapper" class="hidden space-y-2">
                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider">
                            <span class="text-gray-500" id="carrierProgressStatusText">Uploading Data...</span>
                            <span class="text-green-600" id="carrierProgressPercentage">0%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="carrierImportProgressBar"
                                class="bg-gradient-to-r from-green-500 to-green-400 h-full w-0 transition-all duration-300 ease-out"></div>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeCarrierImportModal()" id="carrierImportCancelBtn"
                        class="px-5 py-2.5 text-xs font-bold text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-all">
                        Cancel
                    </button>
                    <button type="submit" id="carrierImportSubmitBtn"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-lg shadow-green-600/20 active:scale-95 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-[10px]"></i>
                        <span id="carrierImportBtnText">Import Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openCarrierImportModal() {
                document.getElementById('carrierImportModal').classList.remove('hidden');
            }

            function closeCarrierImportModal() {
                document.getElementById('carrierImportModal').classList.add('hidden');
                document.getElementById('carrierImportFileInput').value = '';
                document.getElementById('carrierFileStatusLabel').textContent = 'No file selected';
                document.getElementById('carrierFileStatusLabel').className = 'mt-2 text-[10px] text-gray-400 italic';
                document.getElementById('carrierImportProgressWrapper').classList.add('hidden');
                document.getElementById('carrierImportProgressBar').style.width = '0%';
                document.getElementById('carrierProgressPercentage').textContent = '0%';
                document.getElementById('carrierImportSubmitBtn').disabled = false;
                document.getElementById('carrierImportBtnText').textContent = 'Import Now';
            }

            function carrierUpdateFileName(input) {
                const label = document.getElementById('carrierFileStatusLabel');
                if (input.files && input.files.length > 0) {
                    label.textContent = input.files[0].name;
                    label.className = 'mt-2 text-[10px] text-green-600 font-semibold';
                } else {
                    label.textContent = 'No file selected';
                    label.className = 'mt-2 text-[10px] text-gray-400 italic';
                }
            }

            document.getElementById('importCarriersForm').addEventListener('submit', function () {
                const wrapper   = document.getElementById('carrierImportProgressWrapper');
                const bar       = document.getElementById('carrierImportProgressBar');
                const pct       = document.getElementById('carrierProgressPercentage');
                const statusTx  = document.getElementById('carrierProgressStatusText');
                const submitBtn = document.getElementById('carrierImportSubmitBtn');
                const btnText   = document.getElementById('carrierImportBtnText');
                const cancelBtn = document.getElementById('carrierImportCancelBtn');

                wrapper.classList.remove('hidden');
                submitBtn.disabled = true;
                cancelBtn.disabled = true;
                btnText.textContent = 'Uploading...';

                let progress = 0;
                const interval = setInterval(function () {
                    progress += Math.random() * 18;
                    if (progress >= 90) { progress = 90; clearInterval(interval); }
                    bar.style.width = progress + '%';
                    pct.textContent = Math.round(progress) + '%';
                }, 300);

                window.addEventListener('beforeunload', function () {
                    clearInterval(interval);
                    bar.style.width = '100%';
                    pct.textContent = '100%';
                    statusTx.textContent = 'Processing...';
                });
            });

            document.getElementById('carrierImportModal').addEventListener('click', function (e) {
                if (e.target === this) closeCarrierImportModal();
            });
        </script>
    @endpush
@endsection
