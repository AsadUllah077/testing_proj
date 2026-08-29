@extends('layouts.auth')

@section('title')
    Inland Rate - {{ $inland->ref_code }}
@endsection

@section('content')
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.carriers') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $statusStyle = match ($inland->status) {
                    'active' => 'bg-green-100 text-green-700',
                    'expired' => 'bg-red-50 text-red-500',
                    'draft' => 'bg-gray-100 text-gray-500',
                    default => 'bg-gray-100 text-gray-500',
                };
                $fmt = fn($v) => $v ? 'PKR ' . number_format($v) : '—';
            @endphp

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="font-mono text-lg font-bold text-gray-900">{{ $inland->ref_code }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusStyle }}">
                        {{ $inland->status_label }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.carriers.rate-card.inland.edit', $inland) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-pen text-xs"></i> Edit
                    </a>
                    <x-common.back-button url="{{ route('logistics.carriers.rate-card.inland.index') }}" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Left: Main Details --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Zone Banner --}}
                    <div class="bg-gradient-to-r from-[#0B8043] to-[#057032] rounded-xl p-5 text-white">
                        <p class="text-xs text-green-200 uppercase tracking-wider mb-1">Zone / Route</p>
                        <p class="text-2xl font-bold">{{ $inland->zone_route_description }}</p>
                        <p class="text-sm text-green-200 mt-1">
                            {{ $inland->carrier_label }}
                            @if ($inland->example_routes)
                                &bull; {{ $inland->example_routes }}
                            @endif
                            @if ($inland->transit_time)
                                &bull; {{ $inland->transit_time }}
                            @endif
                        </p>
                    </div>

                    {{-- Rate Matrix --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Rate Matrix (PKR)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th
                                            class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase bg-blue-50">
                                            Up to 500 KG
                                            <span class="block text-[10px] font-medium text-gray-400">LTL / Part-Load</span>
                                        </th>
                                        <th
                                            class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase bg-blue-50">
                                            501 – 1,000 KG
                                        </th>
                                        <th
                                            class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase bg-blue-50">
                                            1,001 – 5,000 KG
                                        </th>
                                        <th
                                            class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase bg-blue-50">
                                            20ft Container
                                        </th>
                                        <th
                                            class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase bg-blue-50">
                                            40ft Container
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-5 py-4 text-center text-sm font-bold text-gray-900 bg-blue-50/50">
                                            {{ $fmt($inland->rate_up_to_500kg) }}
                                        </td>
                                        <td class="px-5 py-4 text-center text-sm font-bold text-gray-900 bg-blue-50/50">
                                            {{ $fmt($inland->rate_501_1000kg) }}
                                        </td>
                                        <td class="px-5 py-4 text-center text-sm font-bold text-gray-900 bg-blue-50/50">
                                            {{ $fmt($inland->rate_1001_5000kg) }}
                                        </td>
                                        <td class="px-5 py-4 text-center text-sm font-bold text-[#0078C8] bg-blue-50/50">
                                            {{ $fmt($inland->rate_20ft_container) }}
                                        </td>
                                        <td class="px-5 py-4 text-center text-sm font-bold text-[#0078C8] bg-blue-50/50">
                                            {{ $fmt($inland->rate_40ft_container) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Route Info --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Route Details</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Example Routes</p>
                                <p class="text-sm font-mono text-gray-800">{{ $inland->example_routes ?? '—' }}</p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Transit Time</p>
                                <p class="text-sm font-semibold text-gray-800">{{ $inland->transit_time ?? '—' }}</p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-xs text-gray-400 mb-0.5">Fuel Levy Clause</p>
                                <p class="text-sm text-gray-800">{{ $inland->fuel_levy_label }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if ($inland->notes)
                        <div class="bg-white border border-black/10 rounded-xl shadow-sm p-5">
                            <p class="text-xs text-gray-400 mb-1 font-medium">Notes</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $inland->notes }}</p>
                        </div>
                    @endif

                </div>

                {{-- Right: Meta --}}
                <div class="space-y-4">

                    {{-- MOU & Carrier Info --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">MOU Details</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Carrier / MOU Party</p>
                                <p class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full inline-block"
                                        style="background:{{ $inland->carrier_color }}"></span>
                                    {{ $inland->carrier_label }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">MOU Effective Date</p>
                                <p class="text-sm text-gray-800">
                                    {{ $inland->mou_effective_date ? $inland->mou_effective_date->format('d M Y') : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">MOU Expiry Date</p>
                                <p
                                    class="text-sm text-gray-800 {{ $inland->is_expired ? 'text-red-500 font-semibold' : '' }}">
                                    {{ $inland->mou_expiry_date ? $inland->mou_expiry_date->format('d M Y') : '—' }}
                                    @if ($inland->is_expired)
                                        <span class="text-[10px]">(Expired)</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Record Info --}}
                    <div class="bg-white border border-black/10 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">Record Info</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Ref Code</p>
                                <p class="text-sm font-mono font-bold text-gray-800">{{ $inland->ref_code }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Status</p>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusStyle }}">
                                    {{ $inland->status_label }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Added By</p>
                                <p class="text-sm text-gray-800">{{ $inland->createdBy?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $inland->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Last Updated</p>
                                <p class="text-sm text-gray-800">{{ $inland->updated_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Danger Zone --}}
                    <div class="bg-white border border-red-100 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-red-100 bg-red-50">
                            <h3 class="text-sm font-semibold text-red-700">Danger Zone</h3>
                        </div>
                        <div class="p-5">
                            <p class="text-xs text-gray-500 mb-3">Permanently delete this rate card.</p>
                            <form action="{{ route('logistics.carriers.rate-card.inland.destroy', $inland) }}"
                                method="POST"
                                onsubmit="return confirm('Delete {{ $inland->ref_code }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                                    Delete Rate Card
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection
