@extends('layouts.auth')

@section('title', 'LC Payment Tracker')

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

            {{-- Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">LC Payment Tracker</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Monitor payment status across all active letters of credit</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.lc.index') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-arrow-left text-xs"></i> Back to LCs
                    </a>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-4">
                <div class="p-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <x-common.filter-form action="{{ route('logistics.lc.payment-tracker') }}">
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select name="status"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Statuses</option>
                                <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="Expiring Soon" {{ request('status') === 'Expiring Soon' ? 'selected' : '' }}>
                                    Expiring Soon</option>
                                <option value="Settled" {{ request('status') === 'Settled' ? 'selected' : '' }}>
                                    Settled</option>
                                <option value="Expired" {{ request('status') === 'Expired' ? 'selected' : '' }}>
                                    Expired</option>
                            </select>
                        </div>
                        <x-common.search-filter name="q" value="{{ request('q') }}" label="Search LC / Beneficiary"
                            id="search" />
                        <x-filter-button />
                    </x-common.filter-form>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">LC Number</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Beneficiary</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Amount</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Payment Terms</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Issue Date</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Expiry Date</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Days Left</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Status</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-right">Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $statusConfig = [
                                    'Draft' => 'bg-gray-50 text-gray-500 border-gray-200',
                                    'Active' => 'bg-blue-50 text-blue-600 border-blue-100',
                                    'Expiring Soon' => 'bg-amber-50 text-amber-600 border-amber-100',
                                    'Settled' => 'bg-green-50 text-green-600 border-green-100',
                                    'Expired' => 'bg-red-50 text-red-600 border-red-100',
                                    'Cancelled' => 'bg-gray-100 text-gray-400 border-gray-200',
                                ];
                            @endphp
                            @forelse ($lcs as $lc)
                                @php
                                    $sc = $statusConfig[$lc->status] ?? 'bg-gray-50 text-gray-500 border-gray-200';
                                    $daysLeft = $lc->expiry_date ? now()->diffInDays($lc->expiry_date, false) : null;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-5 py-4 text-sm font-bold text-gray-900">
                                        <a href="{{ route('logistics.lc.show', $lc->id) }}"
                                            class="hover:text-[#0078C8] hover:underline">{{ $lc->lc_number }}</a>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700 max-w-36 truncate"
                                        title="{{ $lc->beneficiary }}">{{ $lc->beneficiary ?: '—' }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-gray-800">
                                        {{ $lc->amount ? number_format($lc->amount, 0) . ' ' . $lc->currency : '—' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">
                                        {{ $lc->payment_terms ? ucwords(str_replace('_', ' ', $lc->payment_terms)) : '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600">
                                        {{ $lc->issue_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">
                                        {{ $lc->expiry_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-sm">
                                        @if ($daysLeft !== null)
                                            <span
                                                class="{{ $daysLeft < 0 ? 'text-red-500 font-semibold' : ($daysLeft <= 30 ? 'text-amber-600 font-semibold' : 'text-gray-600') }}">
                                                {{ $daysLeft < 0 ? abs((int) $daysLeft) . 'd overdue' : (int) $daysLeft . 'd' }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $sc }}">{{ $lc->status }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('logistics.lc.show', $lc->id) }}"
                                            class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-14 text-center">
                                        <i class="fa-solid fa-clock text-gray-200 text-4xl mb-3 block"></i>
                                        <p class="text-sm text-gray-400 font-medium">No LCs found</p>
                                        <p class="text-xs text-gray-300 mt-1">Try adjusting your filters</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-500">
                        Showing <span class="font-semibold text-gray-700">{{ $lcs->firstItem() ?? 0 }}</span>–<span
                            class="font-semibold text-gray-700">{{ $lcs->lastItem() ?? 0 }}</span>
                        of <span class="font-semibold text-gray-700">{{ $lcs->total() }}</span> records
                    </p>
                    <div class="text-xs">{{ $lcs->links() }}</div>
                </div>
            </div>

        </div>
    </div>
@endsection
