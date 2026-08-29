@extends('layouts.auth')
@section('title', 'Maersk Ocean Invoices')
@section('content')
    <x-acccount.loader />

    @php
        // Helper: parse .NET /Date(ms)/ format
        function parseMaerskDate($val)
        {
            if (!$val) {
                return null;
            }
            preg_match('/\d+/', $val, $m);
            if (empty($m[0])) {
                return null;
            }
            try {
                return \Carbon\Carbon::createFromTimestampMs((int) $m[0]);
            } catch (\Exception $e) {
                return null;
            }
        }

        $activeTab = request('invoiceType', 'OPEN');
        $searchType = request('searchType', '');
        if ($searchType === 'BY_BOL_IDS') {
            $activeTab = 'BY_BOL';
        }
        if ($searchType === 'BY_INVOICE_NUMBER') {
            $activeTab = 'BY_INV';
        }

        $tabs = [
            'OPEN' => ['Open', 'fa-folder-open', 'bg-blue-500'],
            'PAID' => ['Paid', 'fa-circle-check', 'bg-green-500'],
            'OVERDUE' => ['Overdue', 'fa-triangle-exclamation', 'bg-red-500'],
            'CREDITS' => ['Credit', 'fa-rotate-left', 'bg-amber-500'],
            'BY_BOL' => ['By BOL IDs', 'fa-file-contract', 'bg-purple-500'],
            'BY_INV' => ['By Invoice No.', 'fa-hashtag', 'bg-teal-500'],
        ];

        $tabColors = [
            'OPEN' => ['text-blue-600 border-blue-500 bg-blue-50', 'text-blue-500'],
            'PAID' => ['text-green-600 border-green-500 bg-green-50', 'text-green-500'],
            'OVERDUE' => ['text-red-600 border-red-500 bg-red-50', 'text-red-500'],
            'CREDITS' => ['text-amber-600 border-amber-500 bg-amber-50', 'text-amber-500'],
            'BY_BOL' => ['text-purple-600 border-purple-500 bg-purple-50', 'text-purple-500'],
            'BY_INV' => ['text-teal-600 border-teal-500 bg-teal-50', 'text-teal-500'],
        ];

        $statusStyles = [
            'OPEN' => 'bg-blue-100 text-blue-700 border-blue-200',
            'PAID' => 'bg-green-100 text-green-700 border-green-200',
            'OVERDUE' => 'bg-red-100 text-red-700 border-red-200',
            'CREDITS' => 'bg-amber-100 text-amber-700 border-amber-200',
        ];

        // Unwrap API response
        $data = is_array($invoices) ? $invoices : [];
        $rows = $data['invoices'] ?? [];
        $meta = $data['responseMetaData'] ?? [];
        $totalCount = $data['invoiceCount'] ?? count($rows);
        $currencies = $meta['currencies'] ?? [];
        $sumOpen = $meta['sumOpenAmount'] ?? [];
        $sumPaid = $meta['sumPaidAmount'] ?? [];
        $divisions = $meta['divisionCounts'] ?? [];
    @endphp

    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Ocean Invoice Summary</h2>
                    <p class="text-xs text-slate-500 mt-1">Open, paid, overdue and credit invoices — API v1.1.2</p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Maersk Hub
                </a>
            </div>

            <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                {{-- Common Fields --}}
                <div class="p-5 border-b border-gray-100 bg-slate-50/50">
                    <form method="GET" action="{{ route('logistics.maersk.invoices') }}" id="invoice-form">
                        <input type="hidden" name="invoiceType" id="input-invoiceType"
                            value="{{ request('invoiceType', 'OPEN') }}">
                        <input type="hidden" name="searchType" id="input-searchType"
                            value="{{ request('searchType', '') }}">

                        <div class="flex flex-wrap items-end gap-3">
                            <div class="w-full md:w-28">
                                <label
                                    class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Carrier
                                    <span class="text-red-500">*</span></label>
                                <select name="carrierCode"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    @foreach (['MAEU', 'SAFM', 'SEAU', 'SEJJ', 'MCPU', 'MAEI'] as $code)
                                        <option value="{{ $code }}"
                                            {{ request('carrierCode', 'MAEU') == $code ? 'selected' : '' }}>
                                            {{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 min-w-[180px]">
                                <label
                                    class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer
                                    Code (CMD) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none"><i
                                            class="fa-solid fa-user text-xs"></i></span>
                                    <input type="text" name="customerCodeCMD" value="{{ request('customerCodeCMD') }}"
                                        placeholder="e.g. US04660082"
                                        class="w-full pl-8 border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                </div>
                            </div>

                            {{-- Paid: date range --}}
                            <div class="tab-field w-full md:w-40 hidden" data-tabs="PAID">
                                <label
                                    class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Start
                                    Date</label>
                                <input class="date" type="date" name="startDate"
                                    value="{{ request('startDate') ? \Carbon\Carbon::parse(request('startDate'))->format('Y-m-d') : now()->startOfYear()->format('Y-m-d') }}"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>
                            <div class="tab-field w-full md:w-40 hidden" data-tabs="PAID">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">End
                                    Date</label>
                                <input class="date" type="date" name="endDate"
                                    value="{{ request('endDate') ? \Carbon\Carbon::parse(request('endDate'))->format('Y-m-d') : now()->format('Y-m-d') }}"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>

                            {{-- BY BOL / BY INV: IDs field --}}
                            <div class="tab-field flex-1 min-w-[200px] hidden" data-tabs="BY_BOL BY_INV">
                                <label class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider"
                                    id="ids-label">IDs</label>
                                <input type="text" name="ids" value="{{ request('ids') }}"
                                    placeholder="Comma-separated IDs"
                                    class="w-full border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>

                            {{-- Open/Overdue: filters --}}
                            <div class="tab-field hidden" data-tabs="OPEN OVERDUE CREDITS">
                                <label
                                    class="block mb-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Credit
                                    Country</label>
                                <select name="isCreditCountry"
                                    class="border-gray-300 rounded-lg focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    <option value="false"
                                        {{ request('isCreditCountry', 'false') == 'false' ? 'selected' : '' }}>No</option>
                                    <option value="true" {{ request('isCreditCountry') == 'true' ? 'selected' : '' }}>Yes
                                    </option>
                                </select>
                            </div>

                            <button type="submit"
                                class="px-6 py-[9px] bg-[#0078C8] text-white text-xs font-semibold rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-magnifying-glass"></i> Search
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Tabs --}}
                <div class="border-b border-gray-200 overflow-x-auto">
                    <div class="flex min-w-max">
                        @foreach ($tabs as $key => [$label, $icon, $dot])
                            @php
                                $isActive = $activeTab === $key;
                                [$activeCls, $dotCls] = $tabColors[$key];
                            @endphp
                            <button type="button" onclick="switchTab('{{ $key }}')"
                                data-tab="{{ $key }}"
                                class="tab-btn flex items-center gap-2 px-5 py-3.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap
                                    {{ $isActive ? $activeCls . ' border-b-2' : 'text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-50' }}">
                                <i
                                    class="fa-solid {{ $icon }} text-[11px] {{ $isActive ? '' : 'text-slate-400' }}"></i>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- States --}}
                @if ($error)
                    <div class="p-6">
                        <div
                            class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            <i class="fa-solid fa-circle-xmark mt-0.5 text-red-400 shrink-0"></i>
                            <span>{{ $error }}</span>
                        </div>
                    </div>
                @elseif (!$searched)
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 mb-4">
                            <i class="fa-solid fa-file-invoice-dollar text-2xl text-slate-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Enter carrier and customer code to load invoices</p>
                        <p class="text-xs text-slate-400 mt-1">Select a tab above to filter by invoice type</p>
                    </div>
                @elseif (empty($rows))
                    <div class="py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-50 mb-4">
                            <i class="fa-solid fa-file-circle-question text-2xl text-amber-300"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No invoices found for this query.</p>
                    </div>
                @else
                    {{-- Summary Bar --}}
                    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap gap-4 items-center bg-slate-50/40">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-[#0078C8]/10 flex items-center justify-center">
                                <i class="fa-solid fa-file-invoice-dollar text-[#0078C8] text-sm"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Invoices</p>
                                <p class="text-lg font-bold text-slate-800 leading-none">{{ $totalCount }}</p>
                            </div>
                        </div>
                        @foreach ($sumOpen as $cur => $amt)
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                    <i class="fa-solid fa-circle-dollar-to-slot text-blue-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Open
                                        Amount</p>
                                    <p class="text-sm font-bold text-blue-700 leading-none">{{ $cur }}
                                        {{ number_format($amt, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                        @foreach ($sumPaid as $cur => $amt)
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                                    <i class="fa-solid fa-circle-check text-green-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Paid
                                        Amount</p>
                                    <p class="text-sm font-bold text-green-700 leading-none">{{ $cur }}
                                        {{ number_format($amt, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                        @foreach ($divisions as $div)
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                                    <i class="fa-solid fa-ship text-slate-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
                                        {{ $div['division'] ?? '' }}</p>
                                    <p class="text-sm font-bold text-slate-700 leading-none">{{ $div['count'] ?? 0 }} inv.
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Invoice Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-gray-500">
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Invoice #</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">B/L</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Type</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Invoice Date</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Due Date</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-right">
                                        Invoiced</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-right">Open
                                    </th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-center">Status
                                    </th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-center">Flags
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($rows as $inv)
                                    @php
                                        $invoiceNo = $inv['invoiceNo'] ?? '-';
                                        $bol = $inv['billOfLading'] ?? '-';
                                        $type = $inv['invoiceType'] ?? '-';
                                        $currency = $inv['currency'] ?? '';
                                        $statusCode = $inv['invoiceStatusCode'] ?? ($inv['invoiceStatus'] ?? '-');
                                        $statusCls =
                                            $statusStyles[$statusCode] ?? 'bg-gray-100 text-gray-600 border-gray-200';
                                        $isOverdue = $inv['isOverdue'] ?? false;
                                        $isPDF = $inv['isPDF'] ?? ($inv['hasPDF'] ?? false);
                                        $isPayable = $inv['isPayable'] ?? ($inv['payable'] ?? false);
                                        $invoicedAmt = $inv['invoicedAmount'] ?? null;
                                        $openAmt = $inv['openAmount'] ?? null;
                                        $invDate = parseMaerskDate($inv['invoiceDate'] ?? null);
                                        $dueDate = parseMaerskDate($inv['dueDate'] ?? null);
                                        $area = $inv['businessAreaDescription'] ?? ($inv['businessArea'] ?? '');
                                    @endphp
                                    <tr
                                        class="hover:bg-slate-50/60 transition-colors {{ $isOverdue ? 'border-l-2 border-l-red-400' : '' }}">
                                        <td class="px-5 py-3.5">
                                            <button type="button"
                                                onclick="openInvoiceDetail('{{ $invoiceNo }}', '{{ request('carrierCode', 'MAEU') }}', '{{ request('customerCodeCMD') }}', '{{ in_array($activeTab, ['OPEN','PAID','OVERDUE','CREDITS']) ? $activeTab : 'OPEN' }}')"
                                                class="font-mono text-xs font-bold text-[#0078C8] hover:underline hover:text-[#005fa0] transition-colors text-left">
                                                {{ $invoiceNo }}
                                            </button>
                                            @if ($area)
                                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $area }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 font-mono text-xs text-slate-700 font-semibold">
                                            {{ $bol }}</td>
                                        <td class="px-5 py-3.5 text-xs text-slate-600">{{ $type }}</td>
                                        <td class="px-5 py-3.5 text-xs text-slate-600">
                                            {{ $invDate ? $invDate->format('d M Y') : '-' }}</td>
                                        <td
                                            class="px-5 py-3.5 text-xs {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                                            {{ $dueDate ? $dueDate->format('d M Y') : '-' }}
                                            @if ($isOverdue)
                                                <span class="block text-[10px] text-red-400 font-normal">Overdue</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono text-xs font-semibold text-slate-800">
                                            {{ $currency }}
                                            {{ $invoicedAmt ? number_format((float) $invoicedAmt, 2) : '-' }}
                                        </td>
                                        <td
                                            class="px-5 py-3.5 text-right font-mono text-xs font-bold {{ $isOverdue ? 'text-red-600' : 'text-slate-900' }}">
                                            {{ $currency }} {{ $openAmt ? number_format((float) $openAmt, 2) : '-' }}
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusCls }}">
                                                {{ $statusCode }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                @if ($isPDF)
                                                    <span title="PDF available"
                                                        class="w-5 h-5 rounded flex items-center justify-center bg-red-50 text-red-500 border border-red-100">
                                                        <i class="fa-solid fa-file-pdf text-[9px]"></i>
                                                    </span>
                                                @endif
                                                @if ($isPayable)
                                                    <span title="Payable"
                                                        class="w-5 h-5 rounded flex items-center justify-center bg-green-50 text-green-500 border border-green-100">
                                                        <i class="fa-solid fa-credit-card text-[9px]"></i>
                                                    </span>
                                                @endif
                                                @if ($inv['isDisputable'] ?? false)
                                                    <span title="Disputable"
                                                        class="w-5 h-5 rounded flex items-center justify-center bg-amber-50 text-amber-500 border border-amber-100">
                                                        <i class="fa-solid fa-flag text-[9px]"></i>
                                                    </span>
                                                @endif
                                                @if ($inv['isPartialPay'] ?? false)
                                                    <span title="Partial Pay"
                                                        class="w-5 h-5 rounded flex items-center justify-center bg-blue-50 text-blue-500 border border-blue-100">
                                                        <i class="fa-solid fa-split text-[9px]"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Invoice Detail Modal --}}
    <div id="invoice-detail-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-labelledby="invoice-modal-title">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeInvoiceDetail()"></div>

        {{-- Panel --}}
        <div class="relative w-full max-w-3xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-[#0078C8]">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-file-invoice text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-white/70 uppercase tracking-wider font-semibold">Invoice Detail</p>
                        <h3 id="invoice-modal-title" class="text-white font-mono font-bold text-sm leading-none mt-0.5">—</h3>
                    </div>
                </div>
                <button onclick="closeInvoiceDetail()"
                    class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            {{-- Loading state --}}
            <div id="invoice-detail-loading" class="flex-1 flex flex-col items-center justify-center py-16 hidden">
                <div class="w-10 h-10 rounded-full border-2 border-[#0078C8]/20 border-t-[#0078C8] animate-spin mb-4"></div>
                <p class="text-sm text-slate-400">Loading invoice details…</p>
            </div>

            {{-- Error state --}}
            <div id="invoice-detail-error" class="p-6 hidden">
                <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    <i class="fa-solid fa-circle-xmark mt-0.5 text-red-400 shrink-0"></i>
                    <span id="invoice-detail-error-msg">An error occurred.</span>
                </div>
            </div>

            {{-- Content --}}
            <div id="invoice-detail-content" class="flex-1 overflow-y-auto hidden">

                {{-- Summary strip --}}
                <div id="invoice-detail-summary" class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-100 border-b border-gray-100">
                </div>

                {{-- Invoice rows table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" id="invoice-detail-table">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-gray-500">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Invoice #</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">B/L</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Invoice Date</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Due Date</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-right">Invoiced</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-right">Open</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="invoice-detail-tbody" class="divide-y divide-gray-50"></tbody>
                    </table>
                </div>

                {{-- Raw JSON --}}
                <details class="border-t border-gray-100">
                    <summary class="px-6 py-3 text-xs text-slate-400 cursor-pointer hover:text-slate-600 select-none font-semibold uppercase tracking-wider">
                        Raw JSON Response
                    </summary>
                    <div class="bg-gray-950 px-6 py-4">
                        <pre id="invoice-detail-raw" class="text-[11px] text-green-400 font-mono overflow-x-auto whitespace-pre-wrap leading-relaxed"></pre>
                    </div>
                </details>
            </div>

        </div>
    </div>

    <script>
        const TAB_CONFIG = {
            'OPEN': {
                invoiceType: 'OPEN',
                searchType: ''
            },
            'PAID': {
                invoiceType: 'PAID',
                searchType: ''
            },
            'OVERDUE': {
                invoiceType: 'OVERDUE',
                searchType: ''
            },
            'CREDITS': {
                invoiceType: 'CREDITS',
                searchType: ''
            },
            'BY_BOL': {
                invoiceType: '',
                searchType: 'BY_BOL_IDS'
            },
            'BY_INV': {
                invoiceType: '',
                searchType: 'BY_INVOICE_NUMBER'
            },
        };

        const TAB_ACTIVE = {
            'OPEN': 'text-blue-600 border-blue-500 bg-blue-50',
            'PAID': 'text-green-600 border-green-500 bg-green-50',
            'OVERDUE': 'text-red-600 border-red-500 bg-red-50',
            'CREDITS': 'text-amber-600 border-amber-500 bg-amber-50',
            'BY_BOL': 'text-purple-600 border-purple-500 bg-purple-50',
            'BY_INV': 'text-teal-600 border-teal-500 bg-teal-50',
        };

        const IDS_LABELS = {
            'BY_BOL': 'B/L Numbers (comma-separated)',
            'BY_INV': 'Invoice Numbers (comma-separated)',
        };

        function switchTab(tab) {
            const cfg = TAB_CONFIG[tab];
            if (!cfg) return;

            // Update hidden inputs
            document.getElementById('input-invoiceType').value = cfg.invoiceType;
            document.getElementById('input-searchType').value = cfg.searchType;

            // Update tab styles
            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                const t = btn.dataset.tab;
                btn.className = btn.className
                    .replace(
                        /text-\w+-600|border-\w+-500|bg-\w+-50|text-slate-500|border-transparent|hover:text-slate-700|hover:bg-slate-50/g,
                        '')
                    .trim();
                if (t === tab) {
                    btn.classList.add(...TAB_ACTIVE[t].split(' '));
                } else {
                    btn.classList.add('text-slate-500', 'border-transparent', 'hover:text-slate-700',
                        'hover:bg-slate-50');
                    btn.querySelectorAll('i').forEach(i => {
                        i.className = i.className.replace(/text-\w+-\d+/, 'text-slate-400');
                    });
                }
            });

            // Show/hide tab-specific fields
            document.querySelectorAll('.tab-field').forEach(function(el) {
                const tabs = (el.dataset.tabs || '').split(' ');
                el.classList.toggle('hidden', !tabs.includes(tab));
            });

            // Update IDs label
            const idsLabel = document.getElementById('ids-label');
            if (idsLabel && IDS_LABELS[tab]) idsLabel.textContent = IDS_LABELS[tab];
        }

        // Init on load
        switchTab('{{ $activeTab }}');

        // ── Invoice Detail Modal ──────────────────────────────────────────────

        const STATUS_COLORS = {
            'OPEN':    'bg-blue-100 text-blue-700 border-blue-200',
            'PAID':    'bg-green-100 text-green-700 border-green-200',
            'OVERDUE': 'bg-red-100 text-red-700 border-red-200',
            'CREDITS': 'bg-amber-100 text-amber-700 border-amber-200',
        };

        function parseMsDate(val) {
            if (!val) return '—';
            const m = String(val).match(/\d+/);
            if (!m) return val;
            try {
                const d = new Date(parseInt(m[0], 10));
                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            } catch (e) { return val; }
        }

        function openInvoiceDetail(invoiceNo, carrierCode, customerCodeCMD, invoiceType) {
            const modal = document.getElementById('invoice-detail-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            // Reset states
            document.getElementById('invoice-modal-title').textContent = invoiceNo;
            document.getElementById('invoice-detail-loading').classList.remove('hidden');
            document.getElementById('invoice-detail-error').classList.add('hidden');
            document.getElementById('invoice-detail-content').classList.add('hidden');

            const url = new URL('{{ route('logistics.maersk.invoices.detail', '__inv__') }}'.replace('__inv__', encodeURIComponent(invoiceNo)), window.location.origin);
            url.searchParams.set('carrierCode', carrierCode || 'MAEU');
            url.searchParams.set('invoiceType', invoiceType || 'OPEN');
            if (customerCodeCMD) url.searchParams.set('customerCodeCMD', customerCodeCMD);

            fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(json => {
                document.getElementById('invoice-detail-loading').classList.add('hidden');

                if (!json.success) {
                    document.getElementById('invoice-detail-error-msg').textContent = json.error || 'Failed to load invoice detail.';
                    document.getElementById('invoice-detail-error').classList.remove('hidden');
                    return;
                }

                const data = json.data || {};
                const rows = data.invoices || [];
                const meta = data.responseMetaData || {};

                // Summary strip
                const sumOpen = meta.sumOpenAmount || {};
                const sumPaid = meta.sumPaidAmount || {};
                const strip = document.getElementById('invoice-detail-summary');
                strip.innerHTML = '';
                const mkCell = (label, val, cls) => `
                    <div class="bg-white px-5 py-3">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">${label}</p>
                        <p class="text-sm font-bold ${cls} mt-0.5">${val}</p>
                    </div>`;
                strip.innerHTML += mkCell('Invoices', data.invoiceCount ?? rows.length, 'text-slate-800');
                Object.entries(sumOpen).forEach(([cur, amt]) =>
                    strip.innerHTML += mkCell('Open', `${cur} ${parseFloat(amt).toLocaleString('en', {minimumFractionDigits:2})}`, 'text-blue-700'));
                Object.entries(sumPaid).forEach(([cur, amt]) =>
                    strip.innerHTML += mkCell('Paid', `${cur} ${parseFloat(amt).toLocaleString('en', {minimumFractionDigits:2})}`, 'text-green-700'));

                // Table rows
                const tbody = document.getElementById('invoice-detail-tbody');
                tbody.innerHTML = '';
                if (rows.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-10 text-center text-sm text-slate-400">No invoice rows returned.</td></tr>`;
                } else {
                    rows.forEach(inv => {
                        const statusCode = inv.invoiceStatusCode || inv.invoiceStatus || '—';
                        const statusCls = STATUS_COLORS[statusCode] || 'bg-gray-100 text-gray-600 border-gray-200';
                        const isOverdue = inv.isOverdue || false;
                        const cur = inv.currency || '';
                        const invAmt = inv.invoicedAmount != null ? `${cur} ${parseFloat(inv.invoicedAmount).toLocaleString('en',{minimumFractionDigits:2})}` : '—';
                        const openAmt = inv.openAmount != null ? `${cur} ${parseFloat(inv.openAmount).toLocaleString('en',{minimumFractionDigits:2})}` : '—';
                        tbody.innerHTML += `
                            <tr class="hover:bg-slate-50/60 transition-colors ${isOverdue ? 'border-l-2 border-l-red-400' : ''}">
                                <td class="px-4 py-3 font-mono text-xs font-bold text-[#0078C8]">${inv.invoiceNo || '—'}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700 font-semibold">${inv.billOfLading || '—'}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">${inv.invoiceType || '—'}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">${parseMsDate(inv.invoiceDate)}</td>
                                <td class="px-4 py-3 text-xs ${isOverdue ? 'text-red-600 font-semibold' : 'text-slate-600'}">${parseMsDate(inv.dueDate)}${isOverdue ? '<span class="block text-[10px] text-red-400 font-normal">Overdue</span>' : ''}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-semibold text-slate-800">${invAmt}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-bold ${isOverdue ? 'text-red-600' : 'text-slate-900'}">${openAmt}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border ${statusCls}">${statusCode}</span>
                                </td>
                            </tr>`;
                    });
                }

                // Raw JSON
                document.getElementById('invoice-detail-raw').textContent = JSON.stringify(data, null, 2);

                document.getElementById('invoice-detail-content').classList.remove('hidden');
            })
            .catch(err => {
                document.getElementById('invoice-detail-loading').classList.add('hidden');
                document.getElementById('invoice-detail-error-msg').textContent = 'Network error: ' + err.message;
                document.getElementById('invoice-detail-error').classList.remove('hidden');
            });
        }

        function closeInvoiceDetail() {
            const modal = document.getElementById('invoice-detail-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeInvoiceDetail();
        });
    </script>
@endsection
