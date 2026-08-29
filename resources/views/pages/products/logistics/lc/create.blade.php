@extends('layouts.auth')

@section('title', 'Open Letter of Credit')

@include('pages.products.logistics.lc.partials.styles')

@section('content')
    <div class="lc-page mt-4">
        <div class="mb-5 px-3">
            <div class="flex flex-wrap items-center gap-3">
                @foreach (config('modules.lc') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="lc-shell mx-auto space-y-4 px-3 pb-8">
            <div class="lc-command-bar rounded-2xl border border-slate-200 px-5 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#0078C8] text-white shadow-lg shadow-[#0078C8]/20">
                            <i class="fa-solid fa-plus text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-semibold text-slate-950">Open Letter of Credit</h1>
                                <span
                                    class="rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">New
                                    LC</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Bank, beneficiary, shipment, and document terms.</p>
                        </div>
                    </div>

                    <a href="{{ route('logistics.lc.index') }}"
                        class="lc-action-btn inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-sky-50 hover:text-[#0078C8]">
                        <i class="fa-solid fa-arrow-left text-[11px]"></i>
                        Back to List
                    </a>
                </div>
            </div>

            <form method="POST" action_url="{{ route('logistics.lc.store') }}"
                back_url="{{ route('logistics.lc.index') }}" id="formSubmit" class="lc-form-shell space-y-4">
                @csrf

                @include('pages.products.logistics.lc.partials.form-fields')

                <div
                    class="sticky bottom-0 z-20 mt-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 px-5 py-4 shadow-2xl shadow-slate-900/10 backdrop-blur sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('logistics.lc.index') }}"
                        class="inline-flex min-h-[42px] items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0078C8] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0078C8]/20 transition hover:bg-[#006bb0] disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        Create LC
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
