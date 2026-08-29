@include('pages.products.logistics.lc.partials.styles')

@php
    $statusConfig = [
        'Draft' => ['pill' => 'bg-slate-50 text-slate-500 border-slate-200', 'dot' => 'bg-slate-400'],
        'Active' => ['pill' => 'bg-sky-50 text-[#0078C8] border-sky-100', 'dot' => 'bg-[#0078C8]'],
        'Expiring Soon' => ['pill' => 'bg-amber-50 text-amber-700 border-amber-100', 'dot' => 'bg-amber-500'],
        'Settled' => ['pill' => 'bg-emerald-50 text-emerald-700 border-emerald-100', 'dot' => 'bg-emerald-500'],
        'Expired' => ['pill' => 'bg-red-50 text-red-700 border-red-100', 'dot' => 'bg-red-500'],
        'Cancelled' => ['pill' => 'bg-slate-100 text-slate-400 border-slate-200', 'dot' => 'bg-slate-300'],
    ];

    $routePrefix = ($type ?? 'lc') === 'bank_contract' ? 'logistics.lc.bank-contracts' : 'logistics.lc';
    $recordVar = ($type ?? 'lc') === 'bank_contract' ? 'contracts' : 'lcs';
    $items = $records ?? $$recordVar ?? collect();
    $isBankContract = ($type ?? 'lc') === 'bank_contract';
@endphp

<div class="lc-table-scroll">
    <table class="w-full min-w-[1180px] border-collapse text-left">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/90 text-slate-500">
                <th class="px-5 py-3.5 text-[11px] font-bold uppercase tracking-wide">
                    {{ $isBankContract ? 'Contract #' : 'LC Number' }}
                </th>
                <th class="px-5 py-3.5 text-[11px] font-bold uppercase tracking-wide">
                    {{ $isBankContract ? 'Ext Contract #' : 'Type' }}
                </th>
                <th class="px-5 py-3.5 text-[11px] font-bold uppercase tracking-wide">Applicant</th>
                <th class="px-5 py-3.5 text-[11px] font-bold uppercase tracking-wide">Beneficiary</th>
                <th class="px-5 py-3.5 text-[11px] font-bold uppercase tracking-wide">Issuing Bank</th>
                <th class="px-5 py-3.5 text-right text-[11px] font-bold uppercase tracking-wide">Amount</th>
                <th class="px-5 py-3.5 text-[11px] font-bold uppercase tracking-wide">Issue</th>
                <th class="px-5 py-3.5 text-[11px] font-bold uppercase tracking-wide">Expiry</th>
                <th class="px-5 py-3.5 text-[11px] font-bold uppercase tracking-wide">Status</th>
                <th class="px-5 py-3.5 text-right text-[11px] font-bold uppercase tracking-wide">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse ($items as $lc)
                @php
                    $status = $lc->status ?: 'Draft';
                    $sc = $statusConfig[$status] ?? $statusConfig['Draft'];
                    $showRoute = $routePrefix . '.show';
                    $editRoute = $routePrefix . '.edit';
                    $delRoute = $routePrefix . '.destroy';

                    $daysToExpiry = $lc->expiry_date
                        ? now()->startOfDay()->diffInDays($lc->expiry_date->copy()->startOfDay(), false)
                        : null;
                    $isExpiring = $daysToExpiry !== null && $daysToExpiry >= 0 && $daysToExpiry <= 30;
                    $isOverdue = $daysToExpiry !== null && $daysToExpiry < 0 && ! in_array($status, ['Settled', 'Cancelled'], true);

                    $nameSeed = trim($lc->beneficiary ?: $lc->applicant ?: $lc->lc_number);
                    $initials = collect(preg_split('/\s+/', $nameSeed))
                        ->filter()
                        ->take(2)
                        ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                        ->implode('') ?: 'LC';
                @endphp

                <tr class="transition hover:bg-slate-50/80 {{ $isExpiring ? 'bg-amber-50/30' : '' }}">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-[11px] font-black text-white">
                                {{ $initials }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route($showRoute, $lc->id) }}"
                                    class="block truncate text-sm font-bold text-slate-950 transition hover:text-[#0078C8]">
                                    {{ $lc->lc_number }}
                                </a>
                                <span class="mt-0.5 block text-[11px] font-medium text-slate-400">
                                    {{ $lc->created_at?->format('M d, Y') ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-sm text-slate-600">
                        @if ($isBankContract)
                            <span class="font-medium">{{ $lc->contract_number ?: '-' }}</span>
                        @else
                            <span class="font-medium">{{ ucwords(str_replace('_', ' ', $lc->lc_type ?: '-')) }}</span>
                        @endif
                    </td>

                    <td class="px-5 py-4">
                        <div class="max-w-[190px] truncate text-sm font-semibold text-slate-800" title="{{ $lc->applicant }}">
                            {{ $lc->applicant ?: '-' }}
                        </div>
                    </td>

                    <td class="px-5 py-4">
                        <div class="max-w-[210px] truncate text-sm font-semibold text-slate-800" title="{{ $lc->beneficiary }}">
                            {{ $lc->beneficiary ?: '-' }}
                        </div>
                    </td>

                    <td class="px-5 py-4">
                        <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold uppercase text-slate-600">
                            {{ $lc->issuing_bank ?: '-' }}
                        </span>
                    </td>

                    <td class="px-5 py-4 text-right">
                        <span class="whitespace-nowrap text-sm font-black text-slate-950">
                            {{ $lc->amount !== null ? ($lc->currency ?: 'USD') . ' ' . number_format((float) $lc->amount, 0) : '-' }}
                        </span>
                    </td>

                    <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-600">
                        {{ $lc->issue_date?->format('M d, Y') ?? '-' }}
                    </td>

                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold {{ $isOverdue ? 'text-red-600' : ($isExpiring ? 'text-amber-700' : 'text-slate-700') }}">
                            {{ $lc->expiry_date?->format('M d, Y') ?? '-' }}
                        </div>
                        @if ($isOverdue)
                            <div class="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-red-500">Past due</div>
                        @elseif ($isExpiring)
                            <div class="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600">
                                {{ $daysToExpiry === 0 ? 'Today' : $daysToExpiry . 'd left' }}
                            </div>
                        @endif
                    </td>

                    <td class="px-5 py-4">
                        <span
                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $sc['pill'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $sc['dot'] }}"></span>
                            {{ $status }}
                        </span>
                    </td>

                    <td class="px-5 py-4 text-right">
                        <details class="lc-row-menu relative inline-block">
                            <summary
                                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50">
                                <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                            </summary>
                            <div
                                class="absolute right-0 z-30 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-xl shadow-slate-900/10">
                                <a href="{{ route($showRoute, $lc->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-sky-50 hover:text-[#0078C8]">
                                    <i class="fa-solid fa-eye w-4 text-slate-400"></i>
                                    View
                                </a>
                                <a href="{{ route($editRoute, $lc->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">
                                    <i class="fa-solid fa-pen w-4 text-slate-400"></i>
                                    Edit
                                </a>
                                <button type="button"
                                    class="delete-btn flex w-full items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-red-600 transition hover:bg-red-50"
                                    data-url="{{ route($delRoute, $lc->id) }}">
                                    <i class="fa-solid fa-trash-can w-4"></i>
                                    Delete
                                </button>
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-5 py-16 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-300">
                            <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
                        </div>
                        <p class="mt-4 text-sm font-bold text-slate-700">No LC records found</p>
                        <p class="mt-1 text-xs text-slate-400">Adjust the filters or create a new LC.</p>
                        @if (! $isBankContract)
                            <a href="{{ route('logistics.lc.create') }}"
                                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#0078C8] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#006bb0]">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                New LC
                            </a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="lc-table-footer flex flex-col gap-3 border-t border-slate-100 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-xs text-slate-500">
        Showing <span class="font-bold text-slate-800">{{ $items->firstItem() ?? 0 }}</span> to
        <span class="font-bold text-slate-800">{{ $items->lastItem() ?? 0 }}</span> of
        <span class="font-bold text-slate-800">{{ $items->total() }}</span> records
    </p>
    <div class="text-xs">{{ $items->links() }}</div>
</div>
