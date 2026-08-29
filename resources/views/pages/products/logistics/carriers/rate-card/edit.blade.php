@extends('layouts.auth')

@section('title', 'Edit Ocean Rate Card')

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
                    <h1 class="text-gray-900 text-xl font-semibold">Edit Ocean Rate — {{ $ocean->ref_code }}</h1>
                    <p class="text-gray-500 text-sm mt-0.5">
                        {{ $ocean->carrier_label }} &bull;
                        {{ $ocean->origin_port }} &rarr; {{ $ocean->destination_port }} &bull;
                        {{ $ocean->equipment_label }}
                    </p>
                </div>
                <x-common.back-button url="{{ route('logistics.carriers.rate-card.ocean.show', $ocean) }}" />
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

            <form method="POST" action="{{ route('logistics.carriers.rate-card.ocean.update', $ocean) }}"
                id="oceanRateForm">
                @csrf
                @method('PUT')

                @include('pages.products.logistics.carriers.rate-card.ocean._form', ['ocean' => $ocean])

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 mt-4 px-1 py-4">
                    <a href="{{ route('logistics.carriers.rate-card.ocean.show', $ocean) }}"
                        class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" name="action" value="draft"
                        class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Save as Draft
                    </button>
                    <button type="submit" name="action" value="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                        Update Rate Card
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
    @include('pages.products.logistics.carriers.partials.ocean-rate-card-form')
@endpush
