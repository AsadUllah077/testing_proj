@extends('layouts.auth')

@section('title', 'Booking Landed Cost')

@section('content')
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.lc') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col gap-4 md:flex-row md:justify-between md:items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Booking-wise Landed Cost</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Each booking total is calculated from all payment entries linked to that booking.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.lc.payments.index') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-receipt text-xs"></i> All Payments
                    </a>
                    <a href="{{ route('logistics.lc.payments.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i> New Payment
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-gray-900">{{ number_format($stats['bookings']) }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Bookings</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-[#0078C8]">{{ number_format($stats['payments']) }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Payment Entries</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-lg font-black text-purple-600">PKR {{ number_format($stats['total_pkr'], 2) }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Converted Total (PKR)</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-lg font-black text-green-600">USD {{ number_format($stats['total_usd'], 2) }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Converted Total (USD)</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <form method="GET" action="{{ route('logistics.lc.landed-cost') }}"
                    class="p-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-end gap-3">
                        <div class="flex-1">
                            <label for="landed-cost-search" class="block text-xs font-medium text-gray-600 mb-1.5">
                                Search booking or payment
                            </label>
                            <input id="landed-cost-search" type="text" name="q" value="{{ request('q') }}"
                                placeholder="Booking ID, route, receipt number or payment details..."
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <button type="submit"
                            class="px-5 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-search text-xs"></i> Search
                        </button>
                        @if (request()->filled('q'))
                            <a href="{{ route('logistics.lc.landed-cost') }}"
                                class="px-5 py-2 bg-white border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 text-center transition-colors">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                                <th class="w-12 px-4 py-4"><span class="sr-only">Expand</span></th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Booking ID</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Route</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-center">Payments</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-right">Total Payment</th>
                            </tr>
                        </thead>

                        @forelse ($bookings as $booking)
                            @php
                                $bookingLabel = $booking->booking_number ?: 'Booking #' . $booking->id;
                                $route = collect([$booking->origin_port, $booking->destination_port])->filter()->implode(' → ');
                            @endphp
                            <tbody x-data="{ expanded: false }" class="border-b border-gray-100 last:border-b-0">
                                <tr class="hover:bg-blue-50/40 transition-colors cursor-pointer"
                                    @click="expanded = !expanded">
                                    <td class="px-4 py-4 text-center">
                                        <button type="button" @click.stop="expanded = !expanded" :aria-expanded="expanded"
                                            aria-label="Toggle payment details for {{ $bookingLabel }}"
                                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:text-[#0078C8] hover:border-blue-200 transition-colors">
                                            <i class="fa-solid fa-chevron-right text-[10px] transition-transform duration-200"
                                                :class="expanded ? 'rotate-90' : ''"></i>
                                        </button>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-bold text-gray-900">{{ $bookingLabel }}</p>
                                        <p class="text-[10px] text-gray-400 mt-0.5">Internal ID: {{ $booking->id }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600">
                                        {{ $route ?: '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex min-w-8 justify-center px-2.5 py-1 text-[10px] font-bold rounded-full bg-blue-50 text-[#0078C8] border border-blue-100">
                                            {{ $booking->lc_payments_count }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <p class="text-sm font-black text-gray-900">
                                            PKR {{ number_format((float) ($booking->total_pkr ?? 0), 2) }}
                                        </p>
                                        <p class="text-xs font-semibold text-green-600 mt-0.5">
                                            USD {{ number_format((float) ($booking->total_usd ?? 0), 2) }}
                                        </p>
                                    </td>
                                </tr>

                                <tr x-show="expanded" x-cloak>
                                    <td colspan="5" class="p-0 bg-slate-50/80 border-t border-blue-100">
                                        <div class="px-5 py-4">
                                            <div class="flex items-center justify-between gap-3 mb-3">
                                                <div>
                                                    <h3 class="text-xs font-bold text-gray-800">Payment details</h3>
                                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $bookingLabel }}</p>
                                                </div>
                                                <a href="{{ route('logistics.lc.payments.index', ['booking_id' => $booking->id]) }}"
                                                    @click.stop
                                                    class="text-xs font-semibold text-[#0078C8] hover:underline">
                                                    Open filtered payments
                                                </a>
                                            </div>

                                            <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
                                                <table class="w-full min-w-[1600px] text-left border-collapse">
                                                    <thead>
                                                        <tr class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Receipt #</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Date</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Payment Type</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Details</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Original Amount</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Currency Value</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">PKR Value</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">USD Value</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Status</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Document</th>
                                                            <th class="px-4 py-3 text-[9px] font-semibold uppercase tracking-wider">Remarks</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @forelse ($booking->lcPayments as $payment)
                                                            @php
                                                                $statusClass = match ($payment->status) {
                                                                    'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                                                    'completed' => 'bg-green-50 text-green-600 border-green-100',
                                                                    default => 'bg-gray-50 text-gray-500 border-gray-200',
                                                                };
                                                            @endphp
                                                            <tr class="hover:bg-gray-50/70 transition-colors">
                                                                <td class="px-4 py-3 text-xs font-semibold text-gray-900">
                                                                    <a href="{{ route('logistics.lc.payments.edit', $payment->id) }}"
                                                                        @click.stop class="hover:text-[#0078C8] hover:underline">
                                                                        {{ $payment->receipt_no ?: 'Payment #' . $payment->id }}
                                                                    </a>
                                                                </td>
                                                                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                                                    {{ $payment->date?->format('M d, Y') ?? '—' }}
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <span
                                                                        class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $payment->payment_type === 'freight_payment' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-purple-50 text-purple-600 border-purple-100' }}">
                                                                        {{ $payment->payment_type_label }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3 text-xs text-gray-700 max-w-64">
                                                                    {{ $payment->details ?: '—' }}
                                                                </td>
                                                                <td class="px-4 py-3 text-xs font-bold text-gray-900 whitespace-nowrap">
                                                                    {{ strtoupper($payment->currency ?? 'PKR') }}
                                                                    {{ number_format((float) ($payment->amount ?? 0), 2) }}
                                                                </td>
                                                                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                                                    {{ $payment->hasCurrencyValue() ? 'PKR ' . number_format((float) $payment->currency_value, 6) : '—' }}
                                                                </td>
                                                                <td class="px-4 py-3 text-xs font-bold text-purple-700 whitespace-nowrap">
                                                                    {{ $payment->amountInPkr() !== null ? 'PKR ' . number_format($payment->amountInPkr(), 2) : 'Rate required' }}
                                                                </td>
                                                                <td class="px-4 py-3 text-xs font-bold text-green-700 whitespace-nowrap">
                                                                    {{ $payment->amountInUsd() !== null ? 'USD ' . number_format($payment->amountInUsd(), 2) : 'Rate required' }}
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    @if ($payment->status)
                                                                        <span class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $statusClass }}">
                                                                            {{ ucfirst($payment->status) }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-xs text-gray-400">—</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 text-xs">
                                                                    @if ($payment->upload_doc)
                                                                        <a href="{{ asset('storage/' . ltrim($payment->upload_doc, '/')) }}"
                                                                            target="_blank" @click.stop
                                                                            class="inline-flex items-center gap-1.5 text-[#0078C8] hover:underline">
                                                                            <i class="fa-solid fa-paperclip text-[10px]"></i>
                                                                            <span class="max-w-28 truncate">{{ $payment->original_filename ?: 'View file' }}</span>
                                                                        </a>
                                                                    @else
                                                                        <span class="text-gray-400">—</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 text-xs text-gray-600 max-w-56">
                                                                    {{ $payment->remarks ?: '—' }}
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="11" class="px-4 py-8 text-center">
                                                                    <p class="text-xs font-medium text-gray-400">No payments linked to this booking.</p>
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="bg-gray-50 border-t border-gray-200">
                                                            <td colspan="4" class="px-4 py-3 text-xs font-bold text-gray-700">Booking Total</td>
                                                            <td colspan="7" class="px-4 py-3 text-xs font-black text-gray-900">
                                                                PKR {{ number_format((float) ($booking->total_pkr ?? 0), 2) }}
                                                                <span class="ml-4 text-green-600">USD {{ number_format((float) ($booking->total_usd ?? 0), 2) }}</span>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="5" class="px-5 py-14 text-center">
                                        <i class="fa-solid fa-receipt text-gray-200 text-4xl mb-3 block"></i>
                                        <p class="text-sm text-gray-400 font-medium">No bookings found</p>
                                        <p class="text-xs text-gray-300 mt-1">
                                            {{ request()->filled('q') ? 'Try a different search.' : 'Bookings will appear here once they are created.' }}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>

                @if ($bookings->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between gap-4">
                        <p class="text-xs text-gray-500">
                            Showing <span class="font-semibold text-gray-700">{{ $bookings->firstItem() }}</span>-<span
                                class="font-semibold text-gray-700">{{ $bookings->lastItem() }}</span>
                            of <span class="font-semibold text-gray-700">{{ $bookings->total() }}</span> bookings
                        </p>
                        <div class="text-xs">{{ $bookings->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
