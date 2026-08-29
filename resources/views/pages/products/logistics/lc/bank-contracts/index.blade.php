@extends('layouts.auth')

@section('title', 'Bank Contracts')

@section('content')
    <x-acccount.loader />
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
                    <h2 class="text-stone-950 text-xl font-normal">Bank Contracts</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Long-term banking facility agreements</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="openImportModal()"
                        class="px-4 py-2 bg-white text-gray-700 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 flex items-center gap-1.5 transition-all shadow-sm">
                        <i class="fa-solid fa-file-import text-gray-400"></i> Import
                    </button>
                    <x-common.export-dropdown name="lc.bank-contracts" />
                    <a href="{{ route('logistics.lc.bank-contracts.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i> New Contract
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4" id="lc-summary">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-gray-900" x-data="countUp('{{ $stats['total'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="total">{{ $stats['total'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Total</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-[#0078C8]" x-data="countUp('{{ $stats['active'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="active">{{ $stats['active'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Active</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-amber-500" x-data="countUp('{{ $stats['expiring_soon'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="expiring_soon">{{ $stats['expiring_soon'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Expiring Soon</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-green-600" x-data="countUp('{{ $stats['settled'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="settled">{{ $stats['settled'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Settled</p>
                </div>
            </div>

            {{-- Filters + Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <x-common.filter-form action="{{ route('logistics.lc.bank-contracts.index') }}" summary="#lc-summary">
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select name="status"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Statuses</option>
                                <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft
                                </option>
                                <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="Expiring Soon"
                                    {{ request('status') === 'Expiring Soon' ? 'selected' : '' }}>Expiring Soon</option>
                                <option value="Settled" {{ request('status') === 'Settled' ? 'selected' : '' }}>
                                    Settled</option>
                                <option value="Expired" {{ request('status') === 'Expired' ? 'selected' : '' }}>
                                    Expired</option>
                                <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>
                                    Cancelled</option>
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
                    @include('pages.products.logistics.lc.partials.table', [
                        'records' => $contracts,
                        'type' => 'bank_contract',
                    ])
                </div>
                <div id="pagination-wrapper"></div>
            </div>

        </div>
    </div>
    <x-delete-confirmation />

    {{-- Import Modal --}}
    <div id="importModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4 overflow-hidden">
            <form action="{{ route('logistics.lc.bank-contracts.import') }}" method="POST" enctype="multipart/form-data"
                id="importForm">
                @csrf
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-file-contract text-emerald-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Import Bank Contracts</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Bulk upload contract records from Excel</p>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-lg">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-circle-info text-emerald-500 mt-0.5"></i>
                            <div class="text-[11px] text-emerald-800 leading-relaxed">
                                Please use our standard template for a successful import.
                                <a href="{{ route('logistics.lc.bank-contracts.download-template') }}"
                                    class="font-bold underline hover:text-emerald-900 ml-1">Download Template</a>
                            </div>
                        </div>
                    </div>

                    <div id="importInitial">
                        <label class="block text-xs font-medium text-gray-700 mb-2">Select Excel File</label>
                        <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                            class="w-full text-xs text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer" />
                    </div>

                    <div id="importProgressWrapper" class="hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span id="importStatusText" class="text-xs font-bold text-emerald-600">Uploading records...</span>
                            <span id="importProgressPercent" class="text-xs font-black text-gray-900">0%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="importProgressBar" class="bg-emerald-600 h-full transition-all duration-300"
                                style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeImportModal()"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Close
                    </button>
                    <button type="submit" id="importSubmitBtn"
                        class="px-4 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-upload text-[10px]"></i> Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openImportModal() {
                document.getElementById('importModal').classList.remove('hidden');
                document.getElementById('importProgressWrapper').classList.add('hidden');
                document.getElementById('importInitial').classList.remove('hidden');
                document.getElementById('importSubmitBtn').disabled = false;
                document.getElementById('importSubmitBtn').innerHTML =
                    '<i class="fa-solid fa-upload text-[10px]"></i> Start Import';
            }

            function closeImportModal() {
                document.getElementById('importModal').classList.add('hidden');
            }

            document.getElementById('importForm').addEventListener('submit', function(e) {
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
                    progress += Math.random() * 10;
                    if (progress > 95) {
                        progress = 95;
                        statusText.textContent = 'Finalizing Import...';
                        clearInterval(interval);
                    }
                    progressBar.style.width = progress + '%';
                    progressPercent.textContent = Math.round(progress) + '%';
                }, 300);
            });

            document.getElementById('importModal').addEventListener('click', function(e) {
                if (e.target === this) closeImportModal();
            });
        </script>
    @endpush
@endsection
