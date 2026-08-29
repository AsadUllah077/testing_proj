@extends('layouts.auth')

@section('title', 'Edit Letter of Credit')

@include('pages.products.logistics.lc.partials.styles')

@section('content')
    @php
        $statusConfig = [
            'Draft' => 'bg-slate-50 text-slate-600 border-slate-200',
            'Active' => 'bg-sky-50 text-[#0078C8] border-sky-100',
            'Expiring Soon' => 'bg-amber-50 text-amber-700 border-amber-100',
            'Settled' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'Expired' => 'bg-red-50 text-red-700 border-red-100',
            'Cancelled' => 'bg-slate-100 text-slate-400 border-slate-200',
        ];
        $statusClass = $statusConfig[$lc->status ?? 'Draft'] ?? $statusConfig['Draft'];
    @endphp

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
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#0078C8] text-white shadow-lg shadow-[#0078C8]/20">
                            <i class="fa-solid fa-pen-to-square text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="truncate text-xl font-semibold text-slate-950">
                                    Edit {{ $lc->lc_number ?? 'LC' }}
                                </h1>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $statusClass }}">
                                    {{ $lc->status ?? 'Draft' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $lc->beneficiary ?: 'Letter of Credit' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('logistics.lc.show', $lc->id ?? 1) }}"
                            class="lc-action-btn inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-sky-50 hover:text-[#0078C8]">
                            <i class="fa-solid fa-eye text-[11px]"></i>
                            View
                        </a>
                        <a href="{{ route('logistics.lc.index') }}"
                            class="lc-action-btn inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-sky-50 hover:text-[#0078C8]">
                            <i class="fa-solid fa-arrow-left text-[11px]"></i>
                            Back to List
                        </a>
                    </div>
                </div>
            </div>

            <form method="POST" action_url="{{ route('logistics.lc.update', $lc->id ?? 1) }}"
                back_url="{{ route('logistics.lc.index') }}" id="formSubmit" class="lc-form-shell space-y-4">
                @csrf
                @method('PUT')

                @include('pages.products.logistics.lc.partials.form-fields', ['lc' => $lc])

                <div
                    class="sticky bottom-0 z-20 mt-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 px-5 py-4 shadow-2xl shadow-slate-900/10 backdrop-blur sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('logistics.lc.index') }}"
                        class="inline-flex min-h-[42px] items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0078C8] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0078C8]/20 transition hover:bg-[#006bb0] disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
