@extends('layouts.auth')

@section('title', 'Customs GD Management')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.gd') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">

            {{-- Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-1">Customs Declaration (GD)</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Goods Declaration filing, duty assessment &amp; exam tracking</p>
                </div>
                <div class="flex items-center gap-2 mt-3 md:mt-0">
                    <button onclick="openImportModal()"
                        class="px-4 py-2 bg-white text-gray-700 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 flex items-center gap-1.5 transition-all shadow-sm">
                        <i class="fa-solid fa-file-import text-gray-400"></i> Import
                    </button>
                    <x-common.export-dropdown name="gd" />
                    <a href="{{ route('logistics.gd.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i>
                        New GD Filing
                    </a>
                </div>
            </div>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4" id="gd-summary">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-gray-900" x-data="countUp('{{ $stats['total'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="total">{{ $stats['total'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Total GDs</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-gray-400" x-data="countUp('{{ $stats['draft'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="draft">{{ $stats['draft'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Draft</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-[#0078C8]" x-data="countUp('{{ $stats['filed'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="filed">{{ $stats['filed'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Filed</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-green-600" x-data="countUp('{{ $stats['cleared'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="cleared">{{ $stats['cleared'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Cleared</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-amber-500" x-data="countUp('{{ $stats['exam'] }}')" x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="exam">{{ $stats['exam'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Under Exam</p>
                </div>
            </div>

            {{-- Filters + Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                {{-- Filters --}}
                <div class="p-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <x-common.filter-form action="{{ route('logistics.gd.index') }}" summary="#gd-summary">

                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">GD Status</label>
                            <select name="status"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Statuses</option>
                                <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft
                                </option>
                                <option value="Filed" {{ request('status') === 'Filed' ? 'selected' : '' }}>Filed
                                </option>
                                <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>
                                    Approved</option>
                                <option value="Cleared" {{ request('status') === 'Cleared' ? 'selected' : '' }}>Cleared
                                </option>
                                <option value="On Hold" {{ request('status') === 'On Hold' ? 'selected' : '' }}>On Hold
                                </option>
                                <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>
                                    Rejected</option>
                            </select>
                        </div>

                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Exam Status</label>
                            <select name="exam_status"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All</option>
                                <option value="no_exam" {{ request('exam_status') === 'no_exam' ? 'selected' : '' }}>
                                    No Exam</option>
                                <option value="doc_check" {{ request('exam_status') === 'doc_check' ? 'selected' : '' }}>
                                    Doc Check</option>
                                <option
                                    value="physical_exam"{{ request('exam_status') === 'physical_exam' ? 'selected' : '' }}>
                                    Physical Exam</option>
                            </select>
                        </div>

                        <x-common.date-filter name="start_date" id="start_date" value="{{ request('start_date') }}"
                            label="From" />
                        <x-common.date-filter name="end_date" id="end_date" value="{{ request('end_date') }}"
                            label="To" />
                        <x-common.search-filter name="q" value="{{ request('q') }}" label="Search" id="search" />
                        <x-filter-button />

                    </x-common.filter-form>
                </div>

                <div id="custom_table">
                    @include('pages.products.logistics.gd.partials.table', ['gds' => $gds])
                </div>
                <div id="pagination-wrapper"></div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />

    {{-- Import Modal --}}
    <div id="importModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4 overflow-hidden">
            <form action="{{ route('logistics.gd.import') }}" method="POST" enctype="multipart/form-data"
                id="importForm">
                @csrf
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-file-invoice text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Import Customs GDs</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Bulk upload Goods Declarations from Excel</p>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                            <div class="text-[11px] text-blue-800 leading-relaxed">
                                Please use our standard template for a successful import.
                                <a href="{{ route('logistics.gd.download-template') }}"
                                    class="font-bold underline hover:text-blue-900 ml-1">Download Template</a>
                            </div>
                        </div>
                    </div>

                    <div id="importInitial">
                        <label class="block text-xs font-medium text-gray-700 mb-2">Select Excel File</label>
                        <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                            class="w-full text-xs text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#0078C8] file:text-white hover:file:bg-[#006bb0] cursor-pointer" />
                    </div>

                    <div id="importProgressWrapper" class="hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span id="importStatusText" class="text-xs font-bold text-blue-600">Uploading records...</span>
                            <span id="importProgressPercent" class="text-xs font-black text-gray-900">0%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="importProgressBar" class="bg-[#0078C8] h-full transition-all duration-300"
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
                        class="px-4 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
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
