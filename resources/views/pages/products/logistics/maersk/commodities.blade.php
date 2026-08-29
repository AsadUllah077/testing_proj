@extends('layouts.auth')
@section('title', 'Maersk Commodities Reference')
@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Commodities Reference</h2>
                    <p class="text-xs text-slate-500 mt-1">Search Maersk commodity codes and HS codes — API v1.1.0</p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Maersk Hub
                </a>
            </div>

            {{-- Search --}}
            <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <form method="GET" action="{{ route('logistics.maersk.commodities') }}">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="flex-1 min-w-[180px]">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Commodity Name</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                        <i class="fa-solid fa-tag text-xs"></i>
                                    </span>
                                    <input type="text" name="commodityName" value="{{ request('commodityName') }}"
                                        placeholder="e.g. Palm oil"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                            </div>
                            <div class="w-full md:w-36">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Commodity Code</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                        <i class="fa-solid fa-barcode text-xs"></i>
                                    </span>
                                    <input type="text" name="commodityCode" value="{{ request('commodityCode') }}"
                                        placeholder="e.g. 000903" maxlength="6"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm font-mono">
                                </div>
                            </div>
                            <div class="w-full md:w-36">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">HS Code</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                        <i class="fa-solid fa-hashtag text-xs"></i>
                                    </span>
                                    <input type="text" name="hsCommodityCode" value="{{ request('hsCommodityCode') }}"
                                        placeholder="e.g. 030326"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm font-mono">
                                </div>
                            </div>
                            <div class="w-full md:w-40">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Cargo Type</label>
                                <select name="cargoType"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    <option value="">All Types</option>
                                    <option value="DRY"       {{ request('cargoType') == 'DRY'       ? 'selected' : '' }}>DRY</option>
                                    <option value="REEFER"    {{ request('cargoType') == 'REEFER'    ? 'selected' : '' }}>REEFER</option>
                                    <option value="OOG"       {{ request('cargoType') == 'OOG'       ? 'selected' : '' }}>OOG</option>
                                    <option value="DANGEROUS" {{ request('cargoType') == 'DANGEROUS' ? 'selected' : '' }}>DANGEROUS</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit"
                                    class="px-6 py-[9px] bg-[#0078C8] text-white text-xs font-semibold rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-2 shadow-sm">
                                    <i class="fa-solid fa-magnifying-glass"></i> Search
                                </button>
                                @if (request()->hasAny(['commodityName','commodityCode','hsCommodityCode','cargoType']))
                                    <a href="{{ route('logistics.maersk.commodities') }}"
                                        class="px-4 py-[9px] border border-gray-300 text-gray-500 text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-1.5">
                                        <i class="fa-solid fa-xmark"></i> Clear
                                    </a>
                                @endif
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Search by name <strong>or</strong> commodity code (not both). HS code and cargo type can be combined with either.
                        </p>
                    </form>
                </div>

                {{-- States --}}
                @if ($error)
                    <div class="p-6">
                        <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            <i class="fa-solid fa-circle-xmark mt-0.5 text-red-400 shrink-0"></i>
                            <span>{{ $error }}</span>
                        </div>
                    </div>
                @elseif (!$searched)
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 mb-4">
                            <i class="fa-solid fa-boxes-stacked text-2xl text-slate-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Search for Maersk commodity classifications</p>
                        <p class="text-xs text-slate-400 mt-1">Lookup by name, commodity code, HS code, or cargo type</p>
                        <div class="mt-5 flex flex-wrap justify-center gap-2">
                            @foreach ([
                                ['commodityName' => 'Palm oil'],
                                ['cargoType' => 'REEFER'],
                                ['commodityCode' => '000903'],
                                ['hsCommodityCode' => '030326'],
                            ] as $preset)
                                <a href="{{ route('logistics.maersk.commodities', $preset) }}"
                                    class="px-3 py-1.5 rounded-full text-xs bg-slate-100 text-slate-600 hover:bg-[#0078C8]/10 hover:text-[#0078C8] transition-colors border border-slate-200">
                                    @foreach ($preset as $k => $v)
                                        <i class="fa-solid fa-magnifying-glass mr-1 text-[10px]"></i>{{ $k }}: <strong>{{ $v }}</strong>
                                    @endforeach
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @php
                        // API returns {"commodities": [...]} — unwrap
                        $items = $commodities['commodities'] ?? (array_is_list($commodities ?? []) ? $commodities : []);
                        $cargoTypeStyles = [
                            'DRY'       => ['bg-stone-100 text-stone-700 border-stone-200',   'fa-sun'],
                            'REEFER'    => ['bg-blue-100 text-blue-700 border-blue-200',       'fa-snowflake'],
                            'OOG'       => ['bg-amber-100 text-amber-700 border-amber-200',    'fa-up-right-and-down-left-from-center'],
                            'DANGEROUS' => ['bg-red-100 text-red-700 border-red-200',          'fa-radiation'],
                        ];
                    @endphp

                    @if (empty($items))
                        <div class="py-16 text-center">
                            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-50 mb-4">
                                <i class="fa-solid fa-circle-question text-2xl text-amber-300"></i>
                            </div>
                            <p class="text-sm font-medium text-slate-500">No commodities found for your search.</p>
                            <p class="text-xs text-slate-400 mt-1">Try a different name or code.</p>
                        </div>
                    @else
                        {{-- Results Header --}}
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <p class="text-xs text-slate-500" id="commodity-count-label">
                                <span class="font-bold text-slate-800">{{ count($items) }}</span> commodit{{ count($items) === 1 ? 'y' : 'ies' }} found
                            </p>
                            @php
                                $activeFilters = array_filter([
                                    request('commodityName') ? 'Name: "' . request('commodityName') . '"' : null,
                                    request('commodityCode') ? 'Code: ' . request('commodityCode') : null,
                                    request('hsCommodityCode') ? 'HS: ' . request('hsCommodityCode') : null,
                                    request('cargoType') ? 'Type: ' . request('cargoType') : null,
                                ]);
                            @endphp
                            @if (!empty($activeFilters))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($activeFilters as $filter)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#0078C8]/10 text-[#0078C8] border border-[#0078C8]/20">{{ $filter }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Commodity Cards --}}
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="commodity-grid">
                            @foreach ($items as $idx => $item)
                                @php $page = (int)floor($idx / 9) + 1; @endphp
                                @php
                                    $cargoTypes    = $item['cargoTypes'] ?? [];
                                    $hsCommodities = $item['hsCommodities'] ?? [];
                                    $hasUSFlag     = $item['hasUSFlag'] ?? false;
                                    $modalId       = 'modal-commodity-' . $idx;
                                @endphp
                                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:border-[#0078C8]/40 hover:shadow-md transition-all group flex flex-col commodity-card" data-page="{{ $page }}">
                                    {{-- Card Header --}}
                                    <div class="px-4 pt-4 pb-3 border-b border-gray-100">
                                        <div class="flex items-start gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-[#0078C8]/10 flex items-center justify-center shrink-0 group-hover:bg-[#0078C8]/20 transition-colors">
                                                <i class="fa-solid fa-box text-[#0078C8] text-sm"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-slate-800 leading-snug">{{ $item['commodityName'] ?? '-' }}</p>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="font-mono text-xs font-bold text-[#0078C8] bg-[#0078C8]/10 px-1.5 py-0.5 rounded">{{ $item['commodityCode'] ?? '-' }}</span>
                                                    @if ($hasUSFlag)
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded">
                                                            <i class="fa-solid fa-flag text-[9px]"></i> US Flag
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Cargo Types --}}
                                    <div class="px-4 py-2.5 flex flex-wrap gap-1.5 border-b border-gray-100">
                                        @forelse ($cargoTypes as $ct)
                                            @php [$ctStyle, $ctIcon] = $cargoTypeStyles[$ct] ?? ['bg-gray-100 text-gray-600 border-gray-200', 'fa-box']; @endphp
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $ctStyle }}">
                                                <i class="fa-solid {{ $ctIcon }} text-[9px]"></i>{{ $ct }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400 italic">No cargo type</span>
                                        @endforelse
                                    </div>

                                    {{-- HS Codes Button --}}
                                    <div class="px-4 py-3 mt-auto">
                                        @if (!empty($hsCommodities))
                                            <button type="button" onclick="openCommodityModal('{{ $modalId }}')"
                                                class="w-full flex items-center justify-between px-3 py-2 rounded-lg border border-gray-200 hover:border-[#0078C8]/40 hover:bg-[#0078C8]/5 transition-all group/btn">
                                                <span class="flex items-center gap-2 text-xs font-semibold text-slate-600 group-hover/btn:text-[#0078C8] transition-colors">
                                                    <i class="fa-solid fa-list-ol text-slate-400 group-hover/btn:text-[#0078C8] text-[10px] transition-colors"></i>
                                                    {{ count($hsCommodities) }} HS Code{{ count($hsCommodities) !== 1 ? 's' : '' }}
                                                </span>
                                                <i class="fa-solid fa-arrow-up-right-from-square text-slate-300 group-hover/btn:text-[#0078C8] text-[10px] transition-colors"></i>
                                            </button>
                                        @else
                                            <p class="text-xs text-slate-400 italic text-center">No HS codes available</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Modal --}}
                                @if (!empty($hsCommodities))
                                    <div id="{{ $modalId }}" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
                                        {{-- Backdrop --}}
                                        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeCommodityModal('{{ $modalId }}')"></div>
                                        {{-- Panel --}}
                                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col overflow-hidden">
                                            {{-- Modal Header --}}
                                            <div class="px-5 pt-5 pb-4 border-b border-gray-100 flex items-start gap-3 shrink-0">
                                                <div class="w-10 h-10 rounded-xl bg-[#0078C8]/10 flex items-center justify-center shrink-0">
                                                    <i class="fa-solid fa-box text-[#0078C8]"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h3 class="text-base font-bold text-slate-900 leading-snug">{{ $item['commodityName'] ?? '-' }}</h3>
                                                    <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                                        <span class="font-mono text-xs font-bold text-[#0078C8] bg-[#0078C8]/10 px-2 py-0.5 rounded">{{ $item['commodityCode'] ?? '-' }}</span>
                                                        @foreach ($cargoTypes as $ct)
                                                            @php [$ctStyle, $ctIcon] = $cargoTypeStyles[$ct] ?? ['bg-gray-100 text-gray-600 border-gray-200', 'fa-box']; @endphp
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $ctStyle }}">
                                                                <i class="fa-solid {{ $ctIcon }} text-[9px]"></i>{{ $ct }}
                                                            </span>
                                                        @endforeach
                                                        @if ($hasUSFlag)
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full">
                                                                <i class="fa-solid fa-flag text-[9px]"></i> US Flag
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <button type="button" onclick="closeCommodityModal('{{ $modalId }}')"
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors shrink-0">
                                                    <i class="fa-solid fa-xmark text-sm"></i>
                                                </button>
                                            </div>

                                            {{-- HS Count Badge --}}
                                            <div class="px-5 py-2.5 bg-slate-50 border-b border-gray-100 shrink-0 flex items-center gap-2">
                                                <i class="fa-solid fa-list-ol text-slate-400 text-xs"></i>
                                                <p class="text-xs font-semibold text-slate-600">{{ count($hsCommodities) }} HS Classification{{ count($hsCommodities) !== 1 ? 's' : '' }}</p>
                                            </div>

                                            {{-- HS List --}}
                                            <div class="overflow-y-auto flex-1 divide-y divide-gray-100">
                                                @foreach ($hsCommodities as $hs)
                                                    <div class="px-5 py-3.5 flex gap-3.5 hover:bg-slate-50/60 transition-colors">
                                                        <span class="font-mono text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-lg shrink-0 h-fit mt-0.5 tracking-wide">{{ $hs['hsCommodityCode'] ?? '-' }}</span>
                                                        <p class="text-sm text-slate-600 leading-relaxed">{{ $hs['hsCommodityName'] ?? '-' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>

                                            {{-- Modal Footer --}}
                                            <div class="px-5 py-3 border-t border-gray-100 shrink-0 flex justify-end">
                                                <button type="button" onclick="closeCommodityModal('{{ $modalId }}')"
                                                    class="px-4 py-2 text-xs font-semibold text-slate-600 border border-gray-300 rounded-lg hover:bg-slate-50 transition-colors">
                                                    Close
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="px-5 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3" id="commodity-pagination">
                            <p class="text-xs text-slate-400" id="pagination-info"></p>
                            <div class="flex items-center gap-1" id="pagination-controls"></div>
                        </div>

                        {{-- Raw JSON --}}
                        <details class="border-t border-gray-100 group">
                            <summary class="px-5 py-3 text-xs font-semibold text-slate-500 cursor-pointer hover:bg-slate-50 flex items-center gap-2 select-none">
                                <i class="fa-solid fa-code text-slate-300 group-open:text-[#0078C8] transition-colors"></i>
                                Raw JSON Response
                                <i class="fa-solid fa-chevron-down text-slate-300 ml-auto text-[10px] group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <pre class="text-xs bg-slate-950 text-green-400 p-5 overflow-x-auto max-h-96 leading-relaxed">{{ json_encode($commodities, JSON_PRETTY_PRINT) }}</pre>
                        </details>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <script>
        // ── Modal ────────────────────────────────────────────────────────────────
        function openCommodityModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeCommodityModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[id^="modal-commodity-"]').forEach(function (m) {
                    m.classList.add('hidden');
                    m.classList.remove('flex');
                });
                document.body.style.overflow = '';
            }
        });

        // ── Pagination ───────────────────────────────────────────────────────────
        (function () {
            const PER_PAGE   = 9;
            const cards      = Array.from(document.querySelectorAll('.commodity-card'));
            const total      = cards.length;
            const totalPages = Math.ceil(total / PER_PAGE);
            let   current    = 1;

            if (totalPages <= 1) {
                const pg = document.getElementById('commodity-pagination');
                if (pg) pg.classList.add('hidden');
                return;
            }

            function showPage(page) {
                current = page;
                cards.forEach(function (card, i) {
                    const cardPage = Math.floor(i / PER_PAGE) + 1;
                    card.style.display = (cardPage === page) ? '' : 'none';
                });
                renderControls();
                updateInfo();
                document.getElementById('commodity-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function updateInfo() {
                const from = (current - 1) * PER_PAGE + 1;
                const to   = Math.min(current * PER_PAGE, total);
                const el   = document.getElementById('pagination-info');
                if (el) el.textContent = `Showing ${from}–${to} of ${total} commodities`;
            }

            function btn(label, page, isActive, isDisabled, isIcon) {
                const b = document.createElement('button');
                b.type = 'button';
                b.innerHTML = label;
                b.disabled  = isDisabled;

                let cls = 'min-w-[32px] h-8 px-2 rounded-lg text-xs font-medium transition-all ';
                if (isDisabled) {
                    cls += 'text-slate-300 cursor-not-allowed';
                } else if (isActive) {
                    cls += 'bg-[#0078C8] text-white shadow-sm';
                } else {
                    cls += 'text-slate-600 border border-gray-200 hover:border-[#0078C8]/50 hover:text-[#0078C8] hover:bg-[#0078C8]/5';
                }
                b.className = cls;
                if (!isDisabled && !isActive) b.addEventListener('click', function () { showPage(page); });
                return b;
            }

            function renderControls() {
                const wrap = document.getElementById('pagination-controls');
                if (!wrap) return;
                wrap.innerHTML = '';

                // Prev
                wrap.appendChild(btn('<i class="fa-solid fa-chevron-left text-[10px]"></i>', current - 1, false, current === 1, true));

                // Page numbers with ellipsis
                const range = [];
                for (let p = 1; p <= totalPages; p++) {
                    if (p === 1 || p === totalPages || (p >= current - 1 && p <= current + 1)) {
                        range.push(p);
                    } else if (range[range.length - 1] !== '…') {
                        range.push('…');
                    }
                }

                range.forEach(function (p) {
                    if (p === '…') {
                        const sp = document.createElement('span');
                        sp.textContent = '…';
                        sp.className = 'text-xs text-slate-400 px-1';
                        wrap.appendChild(sp);
                    } else {
                        wrap.appendChild(btn(p, p, p === current, false, false));
                    }
                });

                // Next
                wrap.appendChild(btn('<i class="fa-solid fa-chevron-right text-[10px]"></i>', current + 1, false, current === totalPages, true));
            }

            showPage(1);
        })();
    </script>
@endsection
