@extends('layouts.auth')

@section('title', 'Create SLA Contract')

@section('content')
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
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div>
                        <h1 class="text-gray-900 text-xl font-semibold">Create New SLA Contract</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Define service level commitments with a carrier</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-common.back-button url="{{ route('logistics.carriers.sla-contract.index') }}" />
                    </div>
                </div>

                {{-- Validation Errors --}}
                @if ($errors->any())
                    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-xs space-y-1">
                        <p class="font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation"></i> Please fix the following errors:
                        </p>
                        <ul class="list-disc list-inside space-y-0.5 pl-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-white rounded-lg border border-black/10 mb-4">
                    <form method="POST" action_url="{{ route('logistics.carriers.sla-contract.store') }}"
                        back_url="{{ route('logistics.carriers.sla-contract.index') }}" id="formSubmit"
                        enctype="multipart/form-data">
                        @csrf

                        @include('pages.products.logistics.carriers.sla-contract.partials._form')

                        {{-- Form Actions --}}
                        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
                            <a href="{{ route('logistics.carriers.sla-contract.index') }}"
                                class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" name="_save_as" value="draft"
                                class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                Save as Draft
                            </button>
                            <button type="submit"
                                class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                                Save SLA Contract
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection
