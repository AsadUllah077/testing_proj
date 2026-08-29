@extends('layouts.auth')

@section('title', 'Edit Air Rate Card')

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

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-semibold">Edit Air Rate — {{ $air->ref_code }}</h1>
                    <p class="text-gray-500 text-sm mt-0.5">
                        {{ $air->carrier_label }} &bull;
                        {{ $air->origin_port }} &rarr; {{ $air->destination_port }} &bull;
                        {{ $air->weight_breakpoint_label }}
                    </p>
                </div>
                <x-common.back-button url="{{ route('logistics.carriers.rate-card.air.show', $air) }}" />
            </div>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="text-sm text-red-600 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('logistics.carriers.rate-card.air.update', $air) }}" id="airRateForm">
                @csrf
                @method('PUT')

                @include('pages.products.logistics.carriers.rate-card.air.partials._form', ['air' => $air])

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 mt-4 px-1 py-4">
                    <a href="{{ route('logistics.carriers.rate-card.air.show', $air) }}"
                        class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="action" value="draft"
                        class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Save as Draft
                    </button>
                    <button type="submit" name="action" value="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                        Update Air Rate
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fields = {
                charge: document.querySelector('[name="charge_rate_usd_kg"]'),
                fsc: document.querySelector('[name="fuel_surcharge_fsc"]'),
                security: document.querySelector('[name="security_xray"]'),
                handling: document.querySelector('[name="origin_handling"]'),
            };

            const fmt = v => '$' + (parseFloat(v) || 0).toFixed(2);

            function recalc() {
                const charge = parseFloat(fields.charge.value) || 0;
                const fsc = parseFloat(fields.fsc.value) || 0;
                const security = parseFloat(fields.security.value) || 0;
                const handling = parseFloat(fields.handling.value) || 0;

                document.getElementById('calc_charge').textContent = fmt(charge);
                document.getElementById('calc_fsc').textContent = fmt(fsc);
                document.getElementById('calc_security').textContent = fmt(security);
                document.getElementById('calc_handling').textContent = fmt(handling);
                document.getElementById('calc_total').textContent = fmt(charge + fsc + security + handling);
            }

            Object.values(fields).forEach(el => el && el.addEventListener('input', recalc));
            recalc();
        });
    </script>
@endpush
