@extends('layouts.auth')

@section('title', 'SLA Contracts')

@section('content')
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
            <div
                class="p-5 bg-white rounded-lg border border-black/10 flex flex-wrap gap-3 justify-between items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-semibold">SLA Contracts</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Service-level agreements with carriers</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-common.export-dropdown name="sla_contracts" />
                    <a href="{{ route('logistics.carriers.sla-contract.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i> New SLA Contract
                    </a>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('logistics.carriers.sla-contract.index') }}"
                class="bg-white rounded-lg border border-black/10 p-4 mb-4 flex flex-wrap gap-3 items-end">

                {{-- Search --}}
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Contract ID, carrier..."
                        class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>

                {{-- Carrier (from DB) --}}
                <div class="min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Carrier</label>
                    <select name="carrier_id"
                        class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">All carriers</option>
                        @foreach ($carriers as $id => $name)
                            <option value="{{ $id }}" @selected(request('carrier_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="min-w-[130px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select name="status"
                        class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\SlaContract::statuses() as $val => $label)
                            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Transport Mode --}}
                <div class="min-w-[140px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Mode</label>
                    <select name="mode"
                        class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">All modes</option>
                        @foreach (\App\Models\SlaContract::transportModes() as $val => $label)
                            <option value="{{ $val }}" @selected(request('mode') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Filter
                    </button>
                    <a href="{{ route('logistics.carriers.sla-contract.index') }}"
                        class="px-4 py-1.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        Reset
                    </a>
                </div>
            </form>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div
                    class="mb-4 px-4 py-3 bg-lime-50 border border-lime-200 rounded-lg text-lime-700 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif

            {{-- Table --}}
            <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Contract ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Carrier</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Mode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Min OTD SLA</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Max Claim %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Volume Commitment</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Rate Lock</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">
                                    Start</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">
                                    Expiry</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Penalty Clause</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse($contracts as $contract)
                                @php
                                    $statusBadge =
                                        [
                                            'active' => 'bg-lime-100 text-lime-700',
                                            'breach' => 'bg-red-50 text-red-600',
                                            'draft' => 'bg-gray-100 text-gray-500',
                                            'expired' => 'bg-slate-100 text-slate-500',
                                            'terminated' => 'bg-orange-50 text-orange-600',
                                        ][$contract->status] ?? 'bg-gray-100 text-gray-500';

                                    $perfLabel =
                                        [
                                            'met' => 'Met',
                                            'marginal' => 'Marginal',
                                            'breach' => 'Breach',
                                            'pending' => 'Pending',
                                        ][$contract->performance_status] ?? '';
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-900 font-mono whitespace-nowrap">
                                        {{ $contract->contract_id }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        {{ $contract->carrier->carrier_company_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap">
                                        {{ $contract->transport_mode_label }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">{{ $contract->min_otd_guarantee }}%</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">{{ $contract->max_claim_rate }}%</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">{{ $contract->volume_commitment ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap">
                                        @if ($contract->rate_lock_period === 'no_lock')
                                            No
                                        @else
                                            Yes &mdash; {{ $contract->rate_lock_label }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap">
                                        {{ $contract->contract_start_date?->format('M j, Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs whitespace-nowrap">
                                        @if ($contract->isExpired())
                                            <span class="text-red-500 font-medium">
                                                {{ $contract->contract_expiry_date?->format('M j, Y') }}
                                            </span>
                                        @elseif($contract->isExpiringSoon())
                                            <span class="text-amber-600 font-medium">
                                                {{ $contract->contract_expiry_date?->format('M j, Y') }}
                                                <i class="fa-solid fa-triangle-exclamation ml-0.5 text-[10px]"></i>
                                            </span>
                                        @else
                                            <span class="text-gray-700">
                                                {{ $contract->contract_expiry_date?->format('M j, Y') ?? '—' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        {{ $contract->penalty_clause_label }}
                                        @if ($contract->penalty_rate_amount)
                                            <span class="text-gray-400"> &mdash;
                                                {{ $contract->penalty_rate_amount }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full font-semibold {{ $statusBadge }}">
                                            {{ ucfirst($contract->status) }}@if ($perfLabel)
                                                &middot; {{ $perfLabel }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('logistics.carriers.sla-contract.show', $contract) }}"
                                                class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-md transition-colors">
                                                View
                                            </a>
                                            <a href="{{ route('logistics.carriers.sla-contract.edit', $contract) }}"
                                                class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 rounded-md transition-colors">
                                                Edit
                                            </a>
                                            <form method="POST"
                                                action="{{ route('logistics.carriers.sla-contract.destroy', $contract) }}"
                                                onsubmit="return confirm('Delete {{ $contract->contract_id }}? This cannot be undone.')"
                                                class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="px-2.5 py-1 text-[11px] font-medium bg-white hover:bg-red-50 text-red-500 border border-red-200 rounded-md transition-colors">
                                                    Del
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="px-4 py-16 text-center">
                                            <i class="fa-regular fa-folder-open text-3xl text-gray-300 block mb-3"></i>
                                            <p class="text-sm text-gray-400 mb-2">No SLA contracts found.</p>
                                            <a href="{{ route('logistics.carriers.sla-contract.create') }}"
                                                class="text-xs text-blue-600 hover:underline font-medium">
                                                + Create your first SLA contract
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($contracts->hasPages())
                        <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                            <p class="text-xs text-gray-400">
                                Showing {{ $contracts->firstItem() }}–{{ $contracts->lastItem() }} of
                                {{ $contracts->total() }} contracts
                            </p>
                            {{ $contracts->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @endsection
