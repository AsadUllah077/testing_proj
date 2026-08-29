@extends('layouts.auth')

@section('title', 'Letter of Credit')

@include('pages.products.logistics.lc.partials.styles')

@section('content')
    <x-acccount.loader />

    <div class="lc-page mt-4">
        <div class="mb-5 px-3">
            <div class="flex flex-wrap items-center gap-3">
                @foreach (config('modules.lc') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="lc-shell lc-list-shell mx-auto space-y-4 px-3 pb-8">
            <div class="lc-command-bar rounded-2xl border border-slate-200 px-5 py-5">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#0078C8] text-white shadow-lg shadow-[#0078C8]/20">
                            <i class="fa-solid fa-file-invoice-dollar text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-semibold text-slate-950">Letter of Credit (LC)</h1>
                                <span
                                    class="rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#0078C8]">UCP
                                    600</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Trade finance controls, exposure, and expiry tracking.</p>
                        </div>
                    </div>

                    <div class="lc-command-actions flex flex-wrap items-center justify-end gap-2">
                        <button type="button" onclick="openImportModal()"
                            class="lc-action-btn inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-sky-50 hover:text-[#0078C8]">
                            <i class="fa-solid fa-file-import text-slate-400"></i>
                            Import LC
                        </button>
                        <div class="min-w-[126px]">
                            <x-common.export-dropdown name="lc" />
                        </div>
                        <a href="{{ route('logistics.lc.create') }}"
                            class="lc-action-btn lc-primary-action inline-flex items-center justify-center gap-2 rounded-xl bg-[#0078C8] px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-[#0078C8]/20 transition hover:bg-[#006bb0]">
                            <i class="fa-solid fa-plus text-[11px]"></i>
                            New LC
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5" id="lc-summary">
                <div class="lc-stat-card rounded-2xl border border-slate-200 bg-white p-4" style="--lc-accent:#0f172a">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Total LCs</p>
                            <p class="mt-2 text-2xl font-black text-slate-950" x-data="lcCountUp('{{ $stats['total'] }}')"
                                x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="total">
                                {{ number_format($stats['total']) }}
                            </p>
                        </div>
                        <span class="rounded-xl bg-slate-100 p-2 text-slate-600">
                            <i class="fa-solid fa-layer-group"></i>
                        </span>
                    </div>
                </div>

                <div class="lc-stat-card rounded-2xl border border-slate-200 bg-white p-4" style="--lc-accent:#0078c8">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Active</p>
                            <p class="mt-2 text-2xl font-black text-[#0078C8]" x-data="lcCountUp('{{ $stats['active'] }}')"
                                x-text="displayValue" @update-value="update($event.detail.value)"
                                data-summary-key="active">
                                {{ number_format($stats['active']) }}
                            </p>
                        </div>
                        <span class="rounded-xl bg-sky-50 p-2 text-[#0078C8]">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>
                    </div>
                </div>

                <div class="lc-stat-card rounded-2xl border border-slate-200 bg-white p-4" style="--lc-accent:#f59e0b">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Expiring Soon</p>
                            <p class="mt-2 text-2xl font-black text-amber-500"
                                x-data="lcCountUp('{{ $stats['expiring_soon'] }}')" x-text="displayValue"
                                @update-value="update($event.detail.value)" data-summary-key="expiring_soon">
                                {{ number_format($stats['expiring_soon']) }}
                            </p>
                        </div>
                        <span class="rounded-xl bg-amber-50 p-2 text-amber-600">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                    </div>
                </div>

                <div class="lc-stat-card rounded-2xl border border-slate-200 bg-white p-4" style="--lc-accent:#16a34a">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Settled</p>
                            <p class="mt-2 text-2xl font-black text-emerald-600"
                                x-data="lcCountUp('{{ $stats['settled'] }}')" x-text="displayValue"
                                @update-value="update($event.detail.value)" data-summary-key="settled">
                                {{ number_format($stats['settled']) }}
                            </p>
                        </div>
                        <span class="rounded-xl bg-emerald-50 p-2 text-emerald-600">
                            <i class="fa-solid fa-check-double"></i>
                        </span>
                    </div>
                </div>

                <div class="lc-stat-card rounded-2xl border border-slate-200 bg-white p-4" style="--lc-accent:#0891b2">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Active Exposure</p>
                            <p class="mt-2 text-xl font-black text-cyan-700"
                                x-data="lcCountUp('{{ $stats['total_amount'] }}')" x-text="displayValue"
                                @update-value="update($event.detail.value)" data-summary-key="total_amount">
                                {{ number_format($stats['total_amount'], 0) }}
                            </p>
                        </div>
                        <span class="rounded-xl bg-cyan-50 p-2 text-cyan-700">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </span>
                    </div>
                    <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">USD</p>
                </div>
            </div>

            <div class="lc-panel lc-table-panel overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="lc-filter-panel border-b border-slate-200 bg-slate-50/80 p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-500">
                                <i class="fa-solid fa-filter text-xs"></i>
                            </span>
                            <h2 class="text-sm font-bold text-slate-900">Filters</h2>
                        </div>
                    </div>

                    <x-common.filter-form action="{{ route('logistics.lc.index') }}" summary="#lc-summary">
                        <div class="min-w-[150px] flex-1">
                            <label class="mb-2 block">Status</label>
                            <select name="status"
                                class="w-full border px-3 py-2.5 outline-none transition">
                                <option value="">All Statuses</option>
                                <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                                <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Expiring Soon" {{ request('status') === 'Expiring Soon' ? 'selected' : '' }}>
                                    Expiring Soon</option>
                                <option value="Settled" {{ request('status') === 'Settled' ? 'selected' : '' }}>Settled</option>
                                <option value="Expired" {{ request('status') === 'Expired' ? 'selected' : '' }}>Expired</option>
                                <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <div class="min-w-[170px] flex-1">
                            <label class="mb-2 block">LC Type</label>
                            <select name="lc_type"
                                class="w-full border px-3 py-2.5 outline-none transition">
                                <option value="">All Types</option>
                                <option value="irrevocable" {{ request('lc_type') === 'irrevocable' ? 'selected' : '' }}>
                                    Irrevocable</option>
                                <option value="irrevocable_confirmed"
                                    {{ request('lc_type') === 'irrevocable_confirmed' ? 'selected' : '' }}>
                                    Irrevocable Confirmed</option>
                                <option value="standby" {{ request('lc_type') === 'standby' ? 'selected' : '' }}>Standby</option>
                                <option value="transferable" {{ request('lc_type') === 'transferable' ? 'selected' : '' }}>
                                    Transferable</option>
                                <option value="back_to_back" {{ request('lc_type') === 'back_to_back' ? 'selected' : '' }}>
                                    Back-to-Back</option>
                            </select>
                        </div>

                        <x-common.date-filter name="start_date" id="start_date" value="{{ request('start_date') }}"
                            label="Issue From" />
                        <x-common.date-filter name="end_date" id="end_date" value="{{ request('end_date') }}"
                            label="Expiry To" />
                        <x-common.search-filter name="q" value="{{ request('q') }}" label="Search" id="search" />
                        <x-filter-button />
                    </x-common.filter-form>
                </div>

                <div id="custom_table">
                    @include('pages.products.logistics.lc.partials.table', ['records' => $lcs, 'type' => 'lc'])
                </div>
                <div id="pagination-wrapper"></div>
            </div>
        </div>
    </div>

    <x-delete-confirmation />

    <div id="importModal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-slate-950/55 px-4 backdrop-blur-sm">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <form action="{{ route('logistics.lc.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                <div class="relative border-b border-slate-100 bg-slate-50 px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#0078C8] text-white">
                            <i class="fa-solid fa-file-excel text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-950">Import Letter of Credit</h3>
                            <p class="mt-1 text-xs text-slate-500">Excel and CSV LC records.</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeImportModal()"
                        class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white hover:text-slate-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-5 px-6 py-6">
                    <div class="rounded-xl border border-sky-100 bg-sky-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs font-semibold text-sky-800">
                                <i class="fa-solid fa-circle-info text-sky-600"></i>
                                Standard import template
                            </div>
                            <a href="{{ route('logistics.lc.download-template') }}"
                                class="text-xs font-bold text-[#0078C8] underline-offset-2 hover:underline">Download</a>
                        </div>
                    </div>

                    <div id="importInitial">
                        <label class="mb-2 block text-xs font-bold text-slate-600">Excel File</label>
                        <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                            class="lc-file-input w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none transition focus:border-[#0078C8] focus:ring-4 focus:ring-[#0078C8]/10"
                            onchange="lcUpdateImportFileName(this)" />
                        <p id="importFileName" class="mt-2 text-xs text-slate-400">No file selected</p>
                    </div>

                    <div id="importProgressWrapper" class="hidden">
                        <div class="mb-2 flex items-center justify-between">
                            <span id="importStatusText" class="text-xs font-bold text-[#0078C8]">Uploading records...</span>
                            <span id="importProgressPercent" class="text-xs font-black text-slate-900">0%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div id="importProgressBar" class="h-full rounded-full bg-[#0078C8] transition-all duration-300"
                                style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" onclick="closeImportModal()"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                        Close
                    </button>
                    <button type="submit" id="importSubmitBtn"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#0078C8] px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-[#0078C8]/20 transition hover:bg-[#006bb0]">
                        <i class="fa-solid fa-upload text-[10px]"></i>
                        Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('lcCountUp', (initialValue, decimals = 0) => ({
                    currentValue: 0,
                    targetValue: 0,
                    displayValue: '0',
                    duration: 700,
                    startTime: null,
                    decimals,

                    init() {
                        this.targetValue = this.parse(initialValue);
                        this.currentValue = this.targetValue;
                        this.displayValue = this.format(this.targetValue);
                    },

                    parse(value) {
                        return parseFloat((value ?? '0').toString().replace(/,/g, '')) || 0;
                    },

                    format(value) {
                        return new Intl.NumberFormat('en-US', {
                            minimumFractionDigits: this.decimals,
                            maximumFractionDigits: this.decimals
                        }).format(value);
                    },

                    update(newValue) {
                        const next = this.parse(newValue);
                        if (next === this.targetValue) return;
                        const start = this.targetValue;
                        const change = next - start;
                        this.targetValue = next;
                        this.startTime = null;

                        const step = (timestamp) => {
                            if (!this.startTime) this.startTime = timestamp;
                            const progress = Math.min((timestamp - this.startTime) / this.duration, 1);
                            const eased = 1 - Math.pow(1 - progress, 4);
                            this.currentValue = start + change * eased;
                            this.displayValue = this.format(this.currentValue);
                            if (progress < 1) window.requestAnimationFrame(step);
                        };

                        window.requestAnimationFrame(step);
                    }
                }));
            });

            function openImportModal() {
                document.getElementById('importModal').classList.remove('hidden');
                document.getElementById('importProgressWrapper').classList.add('hidden');
                document.getElementById('importInitial').classList.remove('hidden');
                document.getElementById('importSubmitBtn').disabled = false;
                document.getElementById('importSubmitBtn').innerHTML =
                    '<i class="fa-solid fa-upload text-[10px]"></i> Start Import';
                document.getElementById('importProgressBar').style.width = '0%';
                document.getElementById('importProgressPercent').textContent = '0%';
            }

            function closeImportModal() {
                document.getElementById('importModal').classList.add('hidden');
            }

            function lcUpdateImportFileName(input) {
                const label = document.getElementById('importFileName');
                if (input.files && input.files.length) {
                    label.textContent = input.files[0].name;
                    label.className = 'mt-2 text-xs font-semibold text-[#0078C8]';
                    return;
                }

                label.textContent = 'No file selected';
                label.className = 'mt-2 text-xs text-slate-400';
            }

            document.getElementById('importForm').addEventListener('submit', function() {
                const btn = document.getElementById('importSubmitBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Processing...';

                document.getElementById('importInitial').classList.add('hidden');
                document.getElementById('importProgressWrapper').classList.remove('hidden');

                let progress = 0;
                const progressBar = document.getElementById('importProgressBar');
                const progressPercent = document.getElementById('importProgressPercent');
                const statusText = document.getElementById('importStatusText');

                const interval = setInterval(() => {
                    progress += Math.random() * 12;
                    if (progress > 95) {
                        progress = 95;
                        statusText.textContent = 'Finalizing import...';
                        clearInterval(interval);
                    }
                    progressBar.style.width = progress + '%';
                    progressPercent.textContent = Math.round(progress) + '%';
                }, 280);
            });

            document.getElementById('importModal').addEventListener('click', function(e) {
                if (e.target === this) closeImportModal();
            });
        </script>
    @endpush
@endsection
