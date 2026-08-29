@extends('layouts.auth')
@section('title', 'Maersk Ocean Credit Summary')
@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Ocean Credit Summary</h2>
                    <p class="text-xs text-slate-500 mt-1">Credit notes, refundable &amp; non-refundable credits — API v2.0.1</p>
                </div>
                <a href="{{ route('logistics.maersk.index') }}"
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-normal rounded hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Maersk Hub
                </a>
            </div>

            <!-- Info Banner -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-circle-dollar-to-slot text-green-600"></i>
                        <span class="text-sm font-semibold text-green-800">Refundable Credits</span>
                    </div>
                    <p class="text-xs text-green-700">Overpayments, container deposits, advance payments that can be refunded.</p>
                </div>
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-file-invoice text-amber-600"></i>
                        <span class="text-sm font-semibold text-amber-800">Non-Refundable Credits</span>
                    </div>
                    <p class="text-xs text-amber-700">Cancellations and rebookings applied as credit notes against future invoices.</p>
                </div>
            </div>

            <!-- Search Form -->
            <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <form method="GET" action="{{ route('logistics.maersk.credits') }}">
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="w-full md:w-52">
                                <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">
                                    Customer Code <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="customerCode" value="{{ request('customerCode') }}"
                                    placeholder="e.g. DK00007951"
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                <p class="mt-1 text-xs text-slate-400">Starts with 2-letter country code</p>
                            </div>
                            <div class="w-full md:w-44">
                                <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">Search
                                    By</label>
                                <select name="searchType"
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                                    <option value="">All Credits</option>
                                    <option value="BOL_IDS" {{ request('searchType') == 'BOL_IDS' ? 'selected' : '' }}>BOL IDs</option>
                                    <option value="INV_NOS" {{ request('searchType') == 'INV_NOS' ? 'selected' : '' }}>Invoice Numbers</option>
                                </select>
                            </div>
                            <div class="w-full md:w-72" id="idsWrapper">
                                <label class="block mb-2 text-xs font-medium text-gray-700 uppercase tracking-wider">IDs
                                    <span class="text-slate-400 font-normal normal-case">(comma-separated, max 50)</span></label>
                                <input type="text" name="ids" value="{{ request('ids') }}"
                                    placeholder="e.g. 951000000 or MH25CN1234567890"
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] text-sm">
                            </div>
                            <div class="mb-[2px]">
                                <button type="submit"
                                    class="px-5 py-2 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#006bb0] flex items-center gap-2">
                                    <i class="fa-solid fa-magnifying-glass"></i> Search Credits
                                </button>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-400">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Carrier is fixed to <strong>MAEU</strong> for this API (v2.0.1 limitation).
                        </p>
                    </form>
                </div>

                <!-- Results -->
                @if ($error)
                    <div class="p-6">
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            <i class="fa-solid fa-circle-xmark mr-2"></i>{{ $error }}
                        </div>
                    </div>
                @elseif (!$searched)
                    <div class="px-6 py-12 text-center text-gray-400">
                        <i class="fa-solid fa-receipt text-4xl text-gray-200 mb-3"></i>
                        <p class="text-sm">Enter a customer code to retrieve credit notes.</p>
                    </div>
                @elseif (empty($credits))
                    <div class="px-6 py-12 text-center text-gray-500">
                        <i class="fa-solid fa-receipt text-4xl text-gray-200 mb-3"></i>
                        <p class="text-sm font-medium">No credits found for this customer.</p>
                    </div>
                @else
                    @php
                        $rows = isset($credits[0]) ? $credits
                              : (isset($credits['credits']) ? $credits['credits']
                              : (isset($credits['invoices']) ? $credits['invoices'] : [$credits]));
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100/50 text-gray-600 border-b border-gray-200">
                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Credit / Invoice #</th>
                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">B/L Number</th>
                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Currency</th>
                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Credit Type</th>
                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">Refundable</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($rows as $credit)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-mono font-semibold text-[#0078C8]">
                                            {{ $credit['invoiceNumber'] ?? $credit['creditNoteNumber'] ?? $credit['invoiceNo'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ $credit['bolNumber'] ?? $credit['billOfLadingNumber'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                            {{ isset($credit['amount']) ? number_format($credit['amount'], 2) : '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $credit['currency'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $credit['creditType'] ?? $credit['invoiceType'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $credit['issueDate'] ?? $credit['invoiceDate'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            @php $refundable = $credit['isRefundable'] ?? $credit['refundable'] ?? null; @endphp
                                            @if ($refundable === true || $refundable === 'true' || $refundable === 1)
                                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-green-100 text-green-700">YES</span>
                                            @elseif ($refundable === false || $refundable === 'false' || $refundable === 0)
                                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">NO</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">No records</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-100 text-xs text-slate-400">
                        {{ count($rows) }} credit(s) returned
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
