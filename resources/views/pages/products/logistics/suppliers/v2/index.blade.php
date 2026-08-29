@extends('layouts.auth')

@section('title', 'Supplier Directory')

@section('content')
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-4 mb-0 text-sm text-green-800 rounded-lg bg-green-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Success!</span> {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="p-4 mb-0 text-sm text-amber-800 rounded-lg bg-amber-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Warning!</span> {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 mb-0 text-sm text-red-800 rounded-lg bg-red-50 mt-4 mx-3" role="alert">
            <span class="font-medium">Error!</span> {{ session('error') }}
        </div>
    @endif
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Header --}}
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Supplier Directory</h2>
                    <p class="text-gray-500 text-sm">Global supplier network, performance tracking, and onboarding - <strong
                            class="text-green-500">{{ $stats['approved'] }} Approved</strong></p>
                </div>
                <div class="flex items-center flex-wrap gap-2 mt-3 md:mt-0">
                    <a href="{{ route('logistics.suppliers.v2.download-template') }}"
                        class="px-3 py-2 bg-white text-gray-700 border border-gray-300 text-xs font-medium rounded hover:bg-gray-50 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-download"></i> Template
                    </a>
                    <button onclick="openSupplierImportModal()"
                        class="px-3 py-2 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-file-excel"></i> Import
                    </button>
                    <a href="{{ route('logistics.suppliers.v2.create') }}"
                        class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Supplier
                    </a>
                </div>
            </div>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="p-5 bg-white rounded-xl border border-gray-100 shadow-sm">
                    <div class="text-2xl font-bold text-[#0078C8]">{{ $stats['total'] }}</div>
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mt-1">Total Suppliers</div>
                </div>
                <div class="p-5 bg-white rounded-xl border border-gray-100 shadow-sm">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</div>
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mt-1">Approved</div>
                </div>
                <div class="p-5 bg-white rounded-xl border border-gray-100 shadow-sm">
                    <div class="text-2xl font-bold text-amber-500">{{ $stats['review'] }}</div>
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mt-1">Under Review</div>
                </div>
                <div class="p-5 bg-white rounded-xl border border-gray-100 shadow-sm">
                    <div class="text-2xl font-bold text-red-500">{{ $stats['suspended'] }}</div>
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mt-1">Suspended</div>
                </div>
                <div class="p-5 bg-white rounded-xl border border-gray-100 shadow-sm">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['new'] }}</div>
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mt-1">New This Month</div>
                </div>
            </div>

            {{-- Filters + Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <form action="{{ route('logistics.suppliers.v2.index') }}" method="GET"
                        class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Category</label>
                            <select name="category"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option>All Categories</option>
                                <option @if (request('category') == 'Textiles') selected @endif>Textiles</option>
                                <option @if (request('category') == 'Electronics') selected @endif>Electronics</option>
                                <option @if (request('category') == 'Machinery') selected @endif>Machinery</option>
                                <option @if (request('category') == 'Chemicals') selected @endif>Chemicals</option>
                                <option @if (request('category') == 'Agriculture') selected @endif>Agriculture</option>
                                <option @if (request('category') == 'Medical') selected @endif>Medical</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Country</label>
                            <select name="country"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option>All Countries</option>
                                <option @if (request('country') == 'Pakistan') selected @endif>Pakistan</option>
                                <option @if (request('country') == 'China') selected @endif>China</option>
                                <option @if (request('country') == 'UAE') selected @endif>UAE</option>
                                <option @if (request('country') == 'Germany') selected @endif>Germany</option>
                                <option @if (request('country') == 'UK') selected @endif>UK</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                            <select name="status"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option>All Statuses</option>
                                <option @if (request('status') == 'Approved') selected @endif>Approved</option>
                                <option @if (request('status') == 'Pending') selected @endif>Pending</option>
                                <option @if (request('status') == 'Suspended') selected @endif>Suspended</option>
                            </select>
                        </div>
                        <div class="flex-[2] min-w-[200px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Search by name / NTN..."
                                    class="w-full py-2.5 pl-10 pr-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                </div>
                            </div>
                        </div>
                        <button type="submit"
                            class="px-6 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                            Filter
                        </button>
                    </form>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Supplier</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Category</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Contact</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">NTN / VAT</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Incoterm</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Payment Terms</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">Credit Limit</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">Shipments</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">Score</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">Status</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-right whitespace-nowrap">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($suppliers as $supplier)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-5 py-4 min-w-[240px]">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-full bg-blue-50 text-[#0078C8] flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ strtoupper(substr($supplier->company_name, 0, 2)) }}
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="text-sm font-bold text-gray-900 truncate" title="{{ $supplier->company_name }}">{{ $supplier->company_name }}
                                                </div>
                                                <div class="text-[10px] text-gray-500 whitespace-nowrap">{{ $supplier->supplier_code }} ·
                                                    {{ $supplier->city }}, {{ $supplier->country }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $supplier->category }}</td>
                                    <td class="px-5 py-4 min-w-[180px]">
                                        <div class="text-sm text-gray-700 font-medium whitespace-nowrap">{{ $supplier->primary_contact }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 whitespace-nowrap">{{ $supplier->email }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 font-mono whitespace-nowrap">{{ $supplier->registration_no }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $supplier->incoterms }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $supplier->payment_terms }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-gray-800 text-center whitespace-nowrap">
                                        ${{ number_format($supplier->credit_limit, 0) }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600 text-center">0</td>
                                    <td class="px-5 py-4 text-center">
                                        <span
                                            class="inline-block px-2 py-1 text-[10px] font-bold rounded-lg bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">
                                            A · 92/100
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border whitespace-nowrap
                                            @if($supplier->status === 'Approved') bg-green-50 text-green-600 border-green-200
                                            @elseif($supplier->status === 'Pending') bg-blue-50 text-blue-600 border-blue-200
                                            @elseif($supplier->status === 'Under Review') bg-amber-50 text-amber-600 border-amber-200
                                            @elseif($supplier->status === 'Suspended') bg-red-50 text-red-600 border-red-200
                                            @else bg-gray-50 text-gray-600 border-gray-200 @endif">
                                            {{ $supplier->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="relative inline-block" x-data="{ open: false }">
                                                <button @click="open = !open" @click.outside="open = false"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 transition-all">
                                                    <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                                                </button>
                                                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-100 z-20 overflow-hidden"
                                                    style="display:none;">
                                                    <a href="{{ route('logistics.suppliers.v2.show', $supplier->id) }}"
                                                        class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                        <i class="fa-solid fa-eye w-3.5 text-gray-400"></i> View
                                                    </a>
                                                    <a href="{{ route('logistics.suppliers.v2.edit', $supplier->id) }}"
                                                        class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                        <i class="fa-solid fa-pen w-3.5 text-gray-400"></i> Edit
                                                    </a>

                                                    @if($supplier->status !== 'Approved')
                                                    <form action="{{ route('logistics.suppliers.v2.approve', $supplier->id) }}" method="POST" class="block">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-green-600 hover:bg-green-50 transition-colors">
                                                            <i class="fa-solid fa-circle-check w-3.5"></i> Approve
                                                        </button>
                                                    </form>
                                                    @endif

                                                    @if($supplier->status !== 'Suspended')
                                                    <form action="{{ route('logistics.suppliers.v2.reject', $supplier->id) }}" method="POST" class="block">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-amber-600 hover:bg-amber-50 transition-colors">
                                                            <i class="fa-solid fa-circle-xmark w-3.5"></i> Suspend
                                                        </button>
                                                    </form>
                                                    @endif

                                                    <button
                                                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-red-600 hover:bg-red-50 transition-colors"
                                                        onclick="confirmSupplierDelete({{ $supplier->id }}, '{{ $supplier->company_name }}')">
                                                        <i class="fa-solid fa-trash-can w-3.5"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-5 py-12 text-center text-gray-500">No suppliers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Showing <span
                            class="font-semibold text-gray-700">{{ $suppliers->firstItem() ?? 0 }}–{{ $suppliers->lastItem() ?? 0 }}</span>
                        of <span class="font-semibold text-gray-700">{{ $suppliers->total() }}</span> suppliers
                    </p>
                    <div class="flex items-center gap-2">
                        {{ $suppliers->links('vendor.pagination.tailwind') }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div x-data="{ 
            open: false, 
            supplierId: null, 
            supplierName: '',
            loading: false,
            async deleteSupplier() {
                this.loading = true;
                try {
                    const response = await fetch(`{{ route('logistics.suppliers.v2.index') }}/${this.supplierId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Error deleting supplier');
                        this.loading = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An unexpected error occurred');
                    this.loading = false;
                }
            }
         }"
        @open-delete-modal.window="open = true; supplierId = $event.detail.id; supplierName = $event.detail.name; loading = false"
        x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        style="display: none;">
        <div class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden"
             @click.outside="if(!loading) open = false">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <template x-if="!loading">
                        <i class="fa-solid fa-trash-can text-2xl"></i>
                    </template>
                    <template x-if="loading">
                        <i class="fa-solid fa-circle-notch fa-spin text-2xl"></i>
                    </template>
                </div>
                <h3 class="text-lg font-bold text-gray-900" x-text="loading ? 'Deleting...' : 'Delete Supplier?'"></h3>
                <p class="text-sm text-gray-500 mt-2" x-show="!loading">Are you sure you want to delete <span
                        class="font-bold text-gray-800" x-text="supplierName"></span>? This action cannot be undone.</p>
                <p class="text-sm text-gray-500 mt-2" x-show="loading">Please wait while we process the deletion.</p>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                <button @click="open = false" :disabled="loading"
                    class="px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">Cancel</button>
                <button @click="deleteSupplier()" :disabled="loading"
                    class="px-4 py-2 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                    <template x-if="loading">
                        <i class="fa-solid fa-circle-notch fa-spin text-[10px]"></i>
                    </template>
                    <span x-text="loading ? 'Deleting...' : 'Delete Supplier'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Import Suppliers Excel
    ═══════════════════════════════════════════════════════════ --}}
    <div id="supplierImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-md mx-4 overflow-hidden transform transition-all">
            <form id="importSuppliersForm" action="{{ route('logistics.suppliers.v2.import') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="relative px-6 py-8 bg-gradient-to-br from-green-600 to-green-700 text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 shadow-inner">
                            <i class="fa-solid fa-file-excel text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Import Suppliers</h3>
                            <p class="text-green-100 text-xs opacity-90">Upload your Excel or CSV supplier file</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeSupplierImportModal()"
                        class="absolute top-4 right-4 text-white/50 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="px-8 py-8 space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Data Source</label>
                            <a href="{{ route('logistics.suppliers.v2.download-template') }}"
                                class="text-[10px] font-bold text-green-600 hover:text-green-700 underline uppercase tracking-tighter">
                                Download Template
                            </a>
                        </div>

                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-paperclip text-gray-400 group-focus-within:text-green-500 transition-colors"></i>
                            </div>
                            <input type="file" name="file" id="supplierImportFileInput" accept=".xlsx,.csv,.xls" required
                                class="block w-full pl-10 pr-3 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-xl focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all file:hidden cursor-pointer bg-gray-50/50 hover:bg-gray-50"
                                onchange="supplierUpdateFileName(this)">
                            <div id="supplierFileStatusLabel" class="mt-2 text-[10px] text-gray-400 italic">No file selected</div>
                        </div>
                    </div>

                    {{-- Allowed status values hint --}}
                    <div class="bg-blue-50 rounded-xl border border-blue-100 px-4 py-3 text-xs text-blue-700 space-y-1">
                        <p class="font-bold uppercase tracking-wider text-[10px] text-blue-500 mb-2">Status Column Values</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-semibold text-[10px]">Approved</span>
                            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-semibold text-[10px]">Pending</span>
                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-semibold text-[10px]">Suspended</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold text-[10px]">Under Review</span>
                        </div>
                        <p class="text-[10px] text-blue-400 mt-1">Any other value defaults to <strong>Pending</strong>.</p>
                    </div>

                    {{-- Progress Bar --}}
                    <div id="supplierImportProgressWrapper" class="hidden space-y-2">
                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider">
                            <span class="text-gray-500" id="supplierProgressStatusText">Uploading Data...</span>
                            <span class="text-green-600" id="supplierProgressPercentage">0%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="supplierImportProgressBar"
                                class="bg-gradient-to-r from-green-500 to-green-400 h-full w-0 transition-all duration-300 ease-out"></div>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeSupplierImportModal()" id="supplierImportCancelBtn"
                        class="px-5 py-2.5 text-xs font-bold text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-all">
                        Cancel
                    </button>
                    <button type="submit" id="supplierImportSubmitBtn"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-lg shadow-green-600/20 active:scale-95 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-[10px]"></i>
                        <span id="supplierImportBtnText">Import Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function confirmSupplierDelete(id, name) {
                window.dispatchEvent(new CustomEvent('open-delete-modal', {
                    detail: { id, name }
                }));
            }

            // ── Import Modal ──────────────────────────────────────────────
            function openSupplierImportModal() {
                document.getElementById('supplierImportModal').classList.remove('hidden');
            }

            function closeSupplierImportModal() {
                document.getElementById('supplierImportModal').classList.add('hidden');
                // Reset file input & label
                document.getElementById('supplierImportFileInput').value = '';
                document.getElementById('supplierFileStatusLabel').textContent = 'No file selected';
                document.getElementById('supplierFileStatusLabel').classList.remove('text-green-600');
                document.getElementById('supplierFileStatusLabel').classList.add('text-gray-400');
                // Reset progress
                document.getElementById('supplierImportProgressWrapper').classList.add('hidden');
                document.getElementById('supplierImportProgressBar').style.width = '0%';
                document.getElementById('supplierProgressPercentage').textContent = '0%';
                document.getElementById('supplierImportSubmitBtn').disabled = false;
                document.getElementById('supplierImportBtnText').textContent = 'Import Now';
            }

            // Show chosen filename
            function supplierUpdateFileName(input) {
                const label = document.getElementById('supplierFileStatusLabel');
                if (input.files && input.files.length > 0) {
                    label.textContent = input.files[0].name;
                    label.classList.remove('text-gray-400', 'italic');
                    label.classList.add('text-green-600', 'font-semibold');
                } else {
                    label.textContent = 'No file selected';
                    label.classList.add('text-gray-400', 'italic');
                    label.classList.remove('text-green-600', 'font-semibold');
                }
            }

            // Animate progress on submit
            document.getElementById('importSuppliersForm').addEventListener('submit', function () {
                const wrapper  = document.getElementById('supplierImportProgressWrapper');
                const bar      = document.getElementById('supplierImportProgressBar');
                const pct      = document.getElementById('supplierProgressPercentage');
                const statusTx = document.getElementById('supplierProgressStatusText');
                const submitBtn= document.getElementById('supplierImportSubmitBtn');
                const btnText  = document.getElementById('supplierImportBtnText');
                const cancelBtn= document.getElementById('supplierImportCancelBtn');

                wrapper.classList.remove('hidden');
                submitBtn.disabled = true;
                cancelBtn.disabled = true;
                btnText.textContent = 'Uploading...';

                let progress = 0;
                const interval = setInterval(function () {
                    progress += Math.random() * 18;
                    if (progress >= 90) { progress = 90; clearInterval(interval); }
                    bar.style.width = progress + '%';
                    pct.textContent = Math.round(progress) + '%';
                }, 300);

                // On actual load complete set to 100
                window.addEventListener('beforeunload', function () {
                    clearInterval(interval);
                    bar.style.width = '100%';
                    pct.textContent = '100%';
                    statusTx.textContent = 'Processing...';
                });
            });

            // Close modal when clicking backdrop
            document.getElementById('supplierImportModal').addEventListener('click', function (e) {
                if (e.target === this) closeSupplierImportModal();
            });
        </script>
    @endpush
@endsection
