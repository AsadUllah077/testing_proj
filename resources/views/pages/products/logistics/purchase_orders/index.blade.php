@extends('layouts.auth')

@section('title', 'Logistics Purchase Orders')

@section('content')
    <x-acccount.loader />
    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Success!</span> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Error!</span> {{ session('error') }}
        </div>
    @endif
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Purchase Orders</h2>
                    <div class="flex items-center gap-2">
                        <x-common.export-dropdown name="purchase_order" />
                        <a href="{{ route('logistics.purchase-orders.download-template') }}"
                            class="px-3 py-2 bg-white text-gray-700 border border-gray-300 text-xs font-medium rounded hover:bg-gray-50 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-download"></i> Template
                        </a>
                        <button onclick="openPOImportModal()"
                            class="px-3 py-2 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-file-excel"></i> Import
                        </button>
                        <a href="{{ route('logistics.purchase-orders.create') }}"
                            class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            CREATE PO
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="my-4 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                        <x-common.filter-form action="{{ route('logistics.purchase-orders.index') }}">
                            <div class="flex flex-wrap items-end gap-x-4 gap-y-4">
                                <div class="w-full md:w-48">
                                    <label for="supplier_id"
                                        class="block mb-[13px] text-xs font-medium text-gray-700 uppercase tracking-wider">Supplier</label>
                                    <select name="supplier_id" id="supplier_id"
                                        class="select2 w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        <option value="">All Suppliers</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->company_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-full md:w-48">
                                    <label for="status"
                                        class="block mb-[13px] text-xs font-medium text-gray-700 uppercase tracking-wider">Status</label>
                                    <select name="status" id="status"
                                        class="select2 w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        <option value="">All Statuses</option>
                                        <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft
                                        </option>
                                        <option value="Sent" {{ request('status') == 'Sent' ? 'selected' : '' }}>Sent
                                        </option>
                                        <option value="Partially Received"
                                            {{ request('status') == 'Partially Received' ? 'selected' : '' }}>Partially
                                            Received
                                        </option>
                                        <option value="Received" {{ request('status') == 'Received' ? 'selected' : '' }}>
                                            Received</option>
                                        <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>
                                            Cancelled</option>
                                    </select>
                                </div>
                                <x-common.date-filter name="from_date" id="from_date" value="{{ request('from_date') }}"
                                    label="From Date" />
                                <x-common.date-filter name="to_date" id="to_date" value="{{ request('to_date') }}"
                                    label="To Date" />

                                <div class="flex-1 min-w-[200px]">
                                    <x-common.search-filter name="q" value="{{ request('q') }}" label="Search PO #"
                                        id="search" />
                                </div>
                                <div class="mb-[2px]">
                                    <x-filter-button />
                                </div>
                            </div>
                        </x-common.filter-form>
                    </div>

                    <!-- Table -->
                    <div id="custom_table">
                        @include('pages.products.logistics.purchase_orders.partials.table')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />

    {{-- Premium Import Modal --}}
    <div id="poImportModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closePOImportModal()"></div>
            
            <form action="{{ route('logistics.purchase-orders.import') }}" method="POST" enctype="multipart/form-data" 
                id="importPOForm" class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all animate-in zoom-in-95 duration-300">
                @csrf
                
                <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-file-excel text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-800 uppercase tracking-tight">Import Purchase Orders</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Bulk Data Upload</p>
                        </div>
                    </div>
                    <button type="button" onclick="closePOImportModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition-colors">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="p-8 space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Select Excel File</label>
                            <a href="{{ route('logistics.purchase-orders.download-template') }}" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 underline uppercase tracking-wider">
                                Download Template
                            </a>
                        </div>
                        
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-cloud-arrow-up text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                            </div>
                            <input type="file" name="file" id="poImportFileInput" accept=".xlsx,.csv,.xls" required
                                class="block w-full pl-10 pr-3 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all file:hidden cursor-pointer bg-gray-50/50 hover:bg-gray-50"
                                onchange="poUpdateFileName(this)">
                            <div id="poFileStatusLabel" class="mt-2 text-[10px] text-gray-400 italic font-medium px-1">No file selected</div>
                        </div>
                    </div>

                    <div class="bg-amber-50 rounded-2xl border border-amber-100 px-5 py-4 text-xs text-amber-700">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-circle-info text-amber-500"></i>
                            <p class="font-black uppercase tracking-widest text-[10px] text-amber-600">Important Instructions</p>
                        </div>
                        <ul class="space-y-1.5 ml-1">
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mt-1 flex-shrink-0"></span>
                                <p class="text-[10px] leading-relaxed">Ensure <strong>PO Number</strong> is unique for each order.</p>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mt-1 flex-shrink-0"></span>
                                <p class="text-[10px] leading-relaxed">Duplicate <strong>PO Numbers</strong> in Excel will group items under the same order.</p>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mt-1 flex-shrink-0"></span>
                                <p class="text-[10px] leading-relaxed"><strong>Supplier Name</strong> must match exactly with the system.</p>
                            </li>
                        </ul>
                    </div>

                    {{-- Progress Bar --}}
                    <div id="poImportProgressWrapper" class="hidden space-y-2 animate-in fade-in slide-in-from-top-2 duration-300">
                        <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-wider">
                            <span class="text-slate-500" id="poProgressStatusText">Uploading Data...</span>
                            <span class="text-blue-600" id="poProgressPercentage">0%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden border border-gray-100 shadow-inner">
                            <div id="poImportProgressBar"
                                class="bg-gradient-to-r from-blue-600 to-blue-400 h-full w-0 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(37,99,235,0.3)]"></div>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closePOImportModal()" id="poImportCancelBtn"
                        class="px-6 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-700 hover:bg-gray-100 rounded-xl transition-all uppercase tracking-widest">
                        Cancel
                    </button>
                    <button type="submit" id="poImportSubmitBtn"
                        class="px-8 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg shadow-blue-600/20 active:scale-95 transition-all flex items-center gap-2 uppercase tracking-widest">
                        <i class="fa-solid fa-cloud-arrow-up text-[10px]"></i>
                        <span id="poImportBtnText">Import Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openPOImportModal() {
                document.getElementById('poImportModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closePOImportModal() {
                document.getElementById('poImportModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
                
                // Reset file input & label
                document.getElementById('poImportFileInput').value = '';
                document.getElementById('poFileStatusLabel').textContent = 'No file selected';
                document.getElementById('poFileStatusLabel').classList.remove('text-blue-600', 'font-bold');
                document.getElementById('poFileStatusLabel').classList.add('text-gray-400');
                
                // Reset progress
                document.getElementById('poImportProgressWrapper').classList.add('hidden');
                document.getElementById('poImportProgressBar').style.width = '0%';
                document.getElementById('poProgressPercentage').textContent = '0%';
                document.getElementById('poImportSubmitBtn').disabled = false;
                document.getElementById('poImportBtnText').textContent = 'Import Now';
                document.getElementById('poImportCancelBtn').disabled = false;
            }

            function poUpdateFileName(input) {
                const label = document.getElementById('poFileStatusLabel');
                if (input.files && input.files.length > 0) {
                    label.textContent = "✓ Selected: " + input.files[0].name;
                    label.classList.remove('text-gray-400', 'italic');
                    label.classList.add('text-blue-600', 'font-bold');
                } else {
                    label.textContent = 'No file selected';
                    label.classList.add('text-gray-400', 'italic');
                    label.classList.remove('text-blue-600', 'font-bold');
                }
            }

            document.getElementById('importPOForm').addEventListener('submit', function () {
                const wrapper  = document.getElementById('poImportProgressWrapper');
                const bar      = document.getElementById('poImportProgressBar');
                const pct      = document.getElementById('poProgressPercentage');
                const statusTx = document.getElementById('poProgressStatusText');
                const submitBtn= document.getElementById('poImportSubmitBtn');
                const btnText  = document.getElementById('poImportBtnText');
                const cancelBtn= document.getElementById('poImportCancelBtn');

                wrapper.classList.remove('hidden');
                submitBtn.disabled = true;
                cancelBtn.disabled = true;
                btnText.textContent = 'Uploading...';

                let progress = 0;
                const interval = setInterval(function () {
                    progress += Math.random() * 15;
                    if (progress >= 92) { progress = 92; clearInterval(interval); }
                    bar.style.width = progress + '%';
                    pct.textContent = Math.round(progress) + '%';
                }, 400);

                window.addEventListener('beforeunload', function () {
                    clearInterval(interval);
                    bar.style.width = '100%';
                    pct.textContent = '100%';
                    statusTx.textContent = 'Processing...';
                });
            });

            $(document).ready(function() {
                $('.select2').select2();
            });
        </script>
    @endpush
@endsection
