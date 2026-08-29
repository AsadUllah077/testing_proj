{{-- resources/views/pages/products/warehouse/barcode/index.blade.php --}}
@extends('layouts.auth')

@section('title', 'Barcode Generator')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <h2 class="text-stone-950 text-xl font-normal">Barcode Generator</h2>
                        <div class="flex flex-wrap gap-2 items-center justify-end w-full sm:w-auto">
                            <div class="relative w-full sm:w-auto">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                    EXPORT ▼
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li>
                                            <a href="#" class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white"
                                                onclick="exportBarcodes('pdf')">PDF</a>
                                        </li>
                                        <li>
                                            <a href="#" class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white"
                                                onclick="exportBarcodes('excel')">Excel</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            {{-- <button id="printSelectedBtn"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                PRINT
                            </button> --}}
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-5">
                        <!-- Generate Barcode Section -->
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Generate Barcode</h3>
                            <form id="barcodeForm" method="POST" action="{{ route('warehouse.barcode.generate') }}">
                                @csrf
                                <div class="flex flex-wrap gap-2 items-end">
                                    <div class="flex-1 min-w-[130px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Container ID</label>
                                        <input type="text" id="containerId" name="container_id"
                                            class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none"
                                            placeholder="Container ID" required>
                                    </div>
                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                                        <select id="category" name="category_id"
                                            class="select2 w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none"
                                            required>
                                            <option value="">Select Category</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex-1 min-w-[110px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Start No</label>
                                        <input type="text" id="startNo" name="start_sequence"
                                            class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none"
                                            placeholder="e.g., 001" required>
                                    </div>
                                    <div class="flex-1 min-w-[110px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">End No</label>
                                        <input type="text" id="endNo" name="end_sequence"
                                            class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none"
                                            placeholder="e.g., 100" required>
                                    </div>
                                    <div>
                                        <button type="submit"
                                            class="py-2 px-5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors flex items-center gap-2 whitespace-nowrap">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            Generate
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Barcode List Section -->
                        <div class="p-6">
                            <!-- Search & Date Range Filters -->
                            <form method="GET" action="{{ route('warehouse.barcode') }}" class="flex flex-wrap gap-2 items-end mb-4">
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                                <div class="flex-1 min-w-[180px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        placeholder="Unique ID or Container ID"
                                        class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                                </div>
                                <div class="min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                                    <select name="category_id"
                                        class="select2 w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                                        <option value="">All Categories</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="min-w-[140px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Date From</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                                        class="date w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                                </div>
                                <div class="min-w-[140px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Date To</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                                        class="date w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                                </div>
                                <div class="min-w-[110px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Per Page</label>
                                    <select name="per_page"
                                        class="select2 w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                                        @foreach([25, 50, 100, 200, 500] as $n)
                                            <option value="{{ $n }}" {{ request('per_page', 100) == $n ? 'selected' : '' }}>{{ $n }} / page</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="py-2 px-4 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors whitespace-nowrap">
                                        Filter
                                    </button>
                                    @if(request()->hasAny(['search','category_id','date_from','date_to','per_page']))
                                        <a href="{{ route('warehouse.barcode') }}"
                                            class="py-2 px-4 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                                            Clear
                                        </a>
                                    @endif
                                </div>
                            </form>

                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Barcode List
                                    @if(request()->hasAny(['search','category_id','date_from','date_to']))
                                        <span class="text-sm font-normal text-gray-500 ml-2">({{ $barcodes->total() }} result{{ $barcodes->total() != 1 ? 's' : '' }})</span>
                                    @endif
                                </h3>

                                <!-- Bulk Actions - Positioned right of the title -->
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center bg-gray-50 px-3 py-1.5 rounded-lg">
                                        <span id="selectedCount" class="text-sm font-medium text-gray-700 mr-2">0</span>
                                        <span class="text-xs text-gray-500">selected</span>
                                        <button type="button" id="clearBarcodeSelection"
                                            class="hidden ml-3 text-xs font-medium text-[#0078C8] hover:text-[#005f9e]">
                                            Clear
                                        </button>
                                    </div>

                                    {{-- <button id="bulkUsedBtn"
                                        class="px-4 py-2 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        MARK AS USED
                                    </button> --}}

                                    <button id="bulkDeleteBtn"
                                        class="px-4 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        DELETE SELECTED
                                    </button>

                                    <button id="printSelectedQrBtn"
                                        class="px-4 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z" />
                                        </svg>
                                        PRINT SELECTED
                                    </button>

                                </div>
                            </div>

                            <div id="selectedBarcodesPanel"
                                class="hidden mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                                <div class="mb-2 text-xs font-medium text-blue-800">Selected barcode IDs</div>
                                <div id="selectedBarcodesList"
                                    class="flex max-h-28 flex-wrap gap-2 overflow-y-auto"
                                    aria-label="Selected barcode IDs"></div>
                            </div>

                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <div class="flex items-center">
                                                    <input id="selectAll" type="checkbox"
                                                        class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                                                    <label for="selectAll"
                                                        class="ml-2 text-xs text-gray-600">Select</label>
                                                </div>
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                @php
                                                    $isActive = request('sort') === 'unique_id';
                                                    $nextDir  = ($isActive && request('direction') === 'asc') ? 'desc' : 'asc';
                                                    $sortUrl  = request()->fullUrlWithQuery(['sort' => 'unique_id', 'direction' => $nextDir]);
                                                @endphp
                                                <a href="{{ $sortUrl }}" class="flex items-center gap-1 hover:text-[#0078C8] transition-colors {{ $isActive ? 'text-[#0078C8]' : '' }}">
                                                    Unique ID
                                                    @if($isActive)
                                                        @if(request('direction') === 'asc')
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                        @else
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                        @endif
                                                    @else
                                                        <svg class="w-3 h-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                                                    @endif
                                                </a>
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Container ID
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Category
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Sequence
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Generated At
                                            </th>
                                            <th scope="col"
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="qrTableBody" class="bg-white divide-y divide-gray-200">
                                        @forelse($barcodes as $barcode)
                                            <tr class="hover:bg-gray-50 transition-colors"
                                                data-id="{{ $barcode->unique_id }}"
                                                data-status="{{ $barcode->status }}">
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <input type="checkbox"
                                                        class="rowCheck h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded"
                                                        value="{{ $barcode->id }}">
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900">
                                                    {{ $barcode->unique_id }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $barcode->container_id }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $barcode->category->name ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $barcode->sequence_number }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span
                                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ $barcode->status == 'active'
                                                        ? 'bg-green-100 text-green-800'
                                                        : ($barcode->status == 'used'
                                                            ? 'bg-blue-100 text-blue-800'
                                                            : 'bg-red-100 text-red-800') }}">
                                                        {{ ucfirst($barcode->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $barcode->created_at->format('Y-m-d H:i') }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                                    <div class="relative">
                                                        <button class="dropdown-btn flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                            </svg>
                                                        </button>
                                                        <div class="dropdown-menu hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                                            <button onclick="printSingleBarcode('{{ $barcode->id }}')"
                                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                                                Print (PDF)
                                                            </button>
                                                            @if($barcode->status != 'used')
                                                                <button onclick="confirmDelete('{{ $barcode->id }}')"
                                                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-900">
                                                                    Delete
                                                                </button>
                                                            @else
                                                                <button disabled
                                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-400 cursor-not-allowed">
                                                                    Delete
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                                    No barcodes generated yet. Generate some using the form above.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($barcodes->hasPages())
                                <div class="mt-4">
                                    {{ $barcodes->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barcode Preview Modal -->
    <div id="barcodeModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title"
        role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideBarcodeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900" id="modal-title">
                            Barcode Preview
                        </h3>
                        <button onclick="hideBarcodeModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg">
                        <div id="barcodeModalImage" class="mb-4 p-4 bg-white rounded-lg shadow">
                            <!-- Image will be inserted here -->
                        </div>
                        <p id="barcodeModalId" class="text-sm font-mono text-gray-700 bg-gray-100 px-3 py-1 rounded"></p>
                    </div>

                    <div class="mt-6 flex justify-center space-x-3">
                        <button onclick="downloadBarcode()"
                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-2 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download
                        </button>
                        <button onclick="printBarcode()"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 flex items-center gap-2 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z" />
                            </svg>
                            Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-delete-confirmation />
@endsection

@push('styles')
    <style>
        .barcode-container {
            position: relative;
            display: inline-block;
        }

        .barcode-image {
            transition: all 0.3s ease;
        }

        .barcode-image:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }
        }

        #barcodeModalImage img {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
        }

        /* Loading spinner animation */
        .animate-spin {
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // ========== CSRF TOKEN ==========
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // ========== DOM ELEMENTS ==========
        const checkAll = document.getElementById('selectAll');
        const rowChecks = document.querySelectorAll('.rowCheck');
        const selectedCountSpan = document.getElementById('selectedCount');
        const clearBarcodeSelectionBtn = document.getElementById('clearBarcodeSelection');
        const selectedBarcodesPanel = document.getElementById('selectedBarcodesPanel');
        const selectedBarcodesList = document.getElementById('selectedBarcodesList');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        // const bulkUsedBtn = document.getElementById('bulkUsedBtn');
        const printSelectedBtn = document.getElementById('printSelectedQrBtn');
        const selectedBarcodes = new Map();
        const barcodeSelectionStorageKey = 'warehouse_barcode_selection_{{ auth()->id() }}_{{ auth()->user()->company_id ?? 'none' }}';

        // ========== TOAST NOTIFICATIONS ==========
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toast-content');

            toast.classList.remove('hidden');

            const icon = type === 'success' ?
                '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' :
                '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>';

            content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">${icon}</div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;

            setTimeout(hideToast, 3000);
        }

        function hideToast() {
            document.getElementById('toast').classList.add('hidden');
        }
        window.hideToast = hideToast;

        // ========== DROPDOWN FUNCTIONS ==========
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        // Close export dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative.w-full')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        // Three-dots row dropdown toggle
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                menu.classList.toggle('hidden');
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // ========== BULK CHECKBOX FUNCTIONS ==========
        function normalizeBarcodeId(id) {
            return id ? String(id) : '';
        }

        function getBarcodeMetaFromCheckbox(checkbox) {
            const row = checkbox?.closest('tr');

            return {
                id: normalizeBarcodeId(checkbox?.value),
                uniqueId: row?.dataset.id || '',
                status: row?.dataset.status || '',
            };
        }

        function getSelectedBarcodeEntries() {
            return Array.from(selectedBarcodes.values());
        }

        function persistBarcodeSelection() {
            try {
                sessionStorage.setItem(barcodeSelectionStorageKey, JSON.stringify(getSelectedBarcodeEntries()));
            } catch (error) {
                console.warn('Unable to save the barcode selection.', error);
            }
        }

        function restoreBarcodeSelection() {
            try {
                const savedSelection = JSON.parse(sessionStorage.getItem(barcodeSelectionStorageKey) || '[]');
                if (!Array.isArray(savedSelection)) return;

                savedSelection.forEach(barcode => {
                    const id = normalizeBarcodeId(barcode?.id);
                    if (!id) return;

                    selectedBarcodes.set(id, {
                        id,
                        uniqueId: barcode.uniqueId || '',
                        status: barcode.status || '',
                    });
                });
            } catch (error) {
                sessionStorage.removeItem(barcodeSelectionStorageKey);
            }
        }

        function syncSelectionFromCheckbox(checkbox) {
            const meta = getBarcodeMetaFromCheckbox(checkbox);
            if (!meta.id) return;

            if (checkbox.checked) {
                selectedBarcodes.set(meta.id, meta);
            } else {
                selectedBarcodes.delete(meta.id);
            }
        }

        function applySelectedStateToVisibleRows() {
            rowChecks.forEach(checkbox => {
                const id = normalizeBarcodeId(checkbox.value);
                checkbox.checked = selectedBarcodes.has(id);

                if (checkbox.checked) {
                    selectedBarcodes.set(id, getBarcodeMetaFromCheckbox(checkbox));
                }
            });

            updateSelectAllCheckbox();
            persistBarcodeSelection();
        }

        function clearBarcodeSelection() {
            selectedBarcodes.clear();

            try {
                sessionStorage.removeItem(barcodeSelectionStorageKey);
            } catch (error) {
                console.warn('Unable to clear the saved barcode selection.', error);
            }

            rowChecks.forEach(checkbox => checkbox.checked = false);
            updateSelectAllCheckbox();
            updateSelectedCount();
        }

        restoreBarcodeSelection();
        applySelectedStateToVisibleRows();

        // Select All affects only the currently visible search results.
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                const isChecked = this.checked;
                rowChecks.forEach(checkbox => {
                    checkbox.checked = isChecked;
                    syncSelectionFromCheckbox(checkbox);
                });
                persistBarcodeSelection();
                updateSelectedCount();
            });
        }

        // Row checkbox event
        rowChecks.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                syncSelectionFromCheckbox(checkbox);
                persistBarcodeSelection();
                updateSelectAllCheckbox();
                updateSelectedCount();
            });
        });

        clearBarcodeSelectionBtn?.addEventListener('click', clearBarcodeSelection);
        selectedBarcodesList?.addEventListener('click', function(event) {
            const removeButton = event.target.closest('[data-remove-selected-barcode]');
            if (!removeButton) return;

            const barcodeId = normalizeBarcodeId(removeButton.dataset.removeSelectedBarcode);
            selectedBarcodes.delete(barcodeId);
            rowChecks.forEach(checkbox => {
                if (normalizeBarcodeId(checkbox.value) === barcodeId) {
                    checkbox.checked = false;
                }
            });
            persistBarcodeSelection();
            updateSelectAllCheckbox();
            updateSelectedCount();
        });

        function updateSelectAllCheckbox() {
            const checkboxes = document.querySelectorAll('.rowCheck:not(#selectAll)');
            const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(checkbox => checkbox.checked);
            const someChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

            if (checkAll) {
                checkAll.checked = allChecked;
                checkAll.indeterminate = someChecked && !allChecked;
            }
        }

        function renderSelectedBarcodesList() {
            if (!selectedBarcodesList) return;

            selectedBarcodesList.replaceChildren();

            getSelectedBarcodeEntries().forEach(barcode => {
                const identifier = barcode.uniqueId || `#${barcode.id}`;
                const chip = document.createElement('span');
                chip.className = 'inline-flex max-w-full items-center gap-1.5 rounded-full border border-blue-200 bg-white px-2.5 py-1 text-xs text-blue-800';

                const label = document.createElement('span');
                label.className = 'max-w-xs truncate font-mono';
                label.textContent = identifier;
                label.title = identifier;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.dataset.removeSelectedBarcode = barcode.id;
                removeButton.className = 'flex h-4 w-4 flex-none items-center justify-center rounded-full text-blue-500 hover:bg-blue-100 hover:text-red-600';
                removeButton.setAttribute('aria-label', `Remove ${identifier} from selection`);
                removeButton.textContent = '\u00d7';

                chip.append(label, removeButton);
                selectedBarcodesList.appendChild(chip);
            });
        }

        function updateSelectedCount() {
            const selectedCount = selectedBarcodes.size;
            renderSelectedBarcodesList();

            if (selectedCountSpan) {
                selectedCountSpan.textContent = selectedCount;
            }
            clearBarcodeSelectionBtn?.classList.toggle('hidden', selectedCount === 0);
            selectedBarcodesPanel?.classList.toggle('hidden', selectedCount === 0);
            updateButtonStates();
        }

        function updateButtonStates() {
            const selectedCount = selectedBarcodes.size;

            if (bulkDeleteBtn) {
                if (selectedCount > 0) {
                    bulkDeleteBtn.removeAttribute('disabled');
                } else {
                    bulkDeleteBtn.setAttribute('disabled', 'disabled');
                }
            }

            if (printSelectedBtn) {
                if (selectedCount > 0) {
                    printSelectedBtn.removeAttribute('disabled');
                } else {
                    printSelectedBtn.setAttribute('disabled', 'disabled');
                }
            }


            // if (bulkUsedBtn) {
            //     if (selectedCount > 0) {
            //         bulkUsedBtn.removeAttribute('disabled');
            //     } else {
            //         bulkUsedBtn.setAttribute('disabled', 'disabled');
            //     }
            // }
        }

        // Initialize selected count and button states
        updateSelectedCount();

        // ========== BARCODE MODAL FUNCTIONS ==========
        let currentBarcodeUrl = '';
        let currentBarcodeId = '';

        function showBarcodeModal(imageUrl, barcodeId) {
            currentBarcodeUrl = imageUrl;
            currentBarcodeId = barcodeId;

            const modal = document.getElementById('barcodeModal');
            const modalImage = document.getElementById('barcodeModalImage');
            const modalId = document.getElementById('barcodeModalId');

            modalImage.innerHTML =
                `<img src="${imageUrl}" alt="${barcodeId}" class="max-w-full max-h-64 border border-gray-200 rounded">`;
            modalId.textContent = barcodeId;

            modal.classList.remove('hidden');
        }
        window.showBarcodeModal = showBarcodeModal;

        function hideBarcodeModal() {
            document.getElementById('barcodeModal').classList.add('hidden');
        }
        window.hideBarcodeModal = hideBarcodeModal;

        function downloadBarcode() {
            if (!currentBarcodeUrl || !currentBarcodeId) return;

            const link = document.createElement('a');
            link.href = currentBarcodeUrl;
            link.download = `barcode-${currentBarcodeId}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showToast('Barcode downloaded successfully', 'success');
        }
        window.downloadBarcode = downloadBarcode;

        function printBarcode() {
            if (!currentBarcodeUrl || !currentBarcodeId) return;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
            <html>
                <head>
                    <title>Print Barcode - ${currentBarcodeId}</title>
                    <style>
                        body {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            height: 100vh;
                            margin: 0;
                            font-family: monospace;
                        }
                        .container {
                            text-align: center;
                            padding: 20px;
                        }
                        img {
                            max-width: 100%;
                            height: auto;
                            border: 1px solid #ddd;
                            padding: 10px;
                            background: white;
                        }
                        .id {
                            margin-top: 15px;
                            font-size: 14px;
                            color: #333;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <img src="${currentBarcodeUrl}" alt="${currentBarcodeId}">
                        <div class="id">${currentBarcodeId}</div>
                    </div>
                </body>
            </html>
        `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();

            showToast('Barcode sent to printer', 'success');
        }
        window.printBarcode = printBarcode;

        // ========== PRINT SINGLE BARCODE ==========
        function printSingleBarcode(barcodeId) {
            showToast('Preparing barcode for print...', 'success');

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('warehouse.barcode.print-view') }}';
            form.target = '_blank';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            const idsInput = document.createElement('input');
            idsInput.type = 'hidden';
            idsInput.name = 'ids[]';
            idsInput.value = barcodeId;
            form.appendChild(idsInput);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        window.printSingleBarcode = printSingleBarcode;

        // ========== MARK AS USED FUNCTION ==========
        // function markAsUsed(barcodeId, uniqueId) {
        //     if (!confirm(`Are you sure you want to mark barcode ${uniqueId} as used?`)) {
        //         return;
        //     }

        //     showToast('Marking barcode as used...', 'success');

        //     fetch(`/warehouse/barcode/${barcodeId}/mark-as-used`, {
        //             method: 'POST',
        //             headers: {
        //                 'X-CSRF-TOKEN': csrfToken,
        //                 'Accept': 'application/json',
        //                 'Content-Type': 'application/json'
        //             }
        //         })
        //         .then(response => response.json())
        //         .then(data => {
        //             if (data.success) {
        //                 showToast(data.message, 'success');

        //                 const row = document.querySelector(`input[value="${barcodeId}"]`)?.closest('tr');
        //                 if (row) {
        //                     const statusCell = row.querySelector('td:nth-child(6) span');
        //                     if (statusCell) {
        //                         statusCell.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800';
        //                         statusCell.textContent = 'Used';
        //                     }

        //                     const useButton = row.querySelector('button[onclick*="markAsUsed"]');
        //                     if (useButton) {
        //                         useButton.textContent = 'USED';
        //                         useButton.classList.add('opacity-50', 'cursor-not-allowed');
        //                         useButton.disabled = true;
        //                     }

        //                     const deleteButton = row.querySelector('button[onclick*="confirmDelete"]');
        //                     if (deleteButton) {
        //                         deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        //                         deleteButton.disabled = true;
        //                     }
        //                 }

        //                 updateSelectedCount();
        //                 updateSelectAllCheckbox();
        //             } else {
        //                 showToast(data.message || 'Failed to mark barcode as used', 'error');
        //             }
        //         })
        //         .catch(error => {
        //             console.error('Mark as used error:', error);
        //             showToast('Failed to mark as used: ' + error.message, 'error');
        //         });
        // }
        // window.markAsUsed = markAsUsed;

        // ========== BULK MARK AS USED FUNCTION ==========
        // if (bulkUsedBtn) {
        //     bulkUsedBtn.addEventListener('click', function() {
        //         const selectedIds = getSelectedIds();

        //         if (selectedIds.length === 0) {
        //             showToast('Please select barcodes to mark as used', 'error');
        //             return;
        //         }

        //         let hasUsedBarcodes = false;
        //         let usedBarcodesList = [];

        //         selectedIds.forEach(id => {
        //             const row = document.querySelector(`input[value="${id}"]`)?.closest('tr');
        //             const statusSpan = row?.querySelector('td:nth-child(6) span');
        //             const currentStatus = statusSpan?.textContent?.toLowerCase().trim();
        //             const uniqueId = row?.querySelector('td:nth-child(2)')?.textContent?.trim();

        //             if (currentStatus === 'used') {
        //                 hasUsedBarcodes = true;
        //                 usedBarcodesList.push(uniqueId || id);
        //             }
        //         });

        //         if (hasUsedBarcodes) {
        //             const usedList = usedBarcodesList.join(', ');
        //             if (!confirm(`Warning: The following barcodes are already used: ${usedList}\n\nDo you want to continue with the remaining barcodes?`)) {
        //                 return;
        //             }
        //         }

        //         if (!confirm(`Are you sure you want to mark ${selectedIds.length} selected barcodes as used?`)) {
        //             return;
        //         }

        //         showToast(`Marking ${selectedIds.length} barcodes as used...`, 'success');

        //         fetch('{{ route('warehouse.barcode.bulk-mark-as-used') }}', {
        //                 method: 'POST',
        //                 headers: {
        //                     'Content-Type': 'application/json',
        //                     'X-CSRF-TOKEN': csrfToken,
        //                     'Accept': 'application/json'
        //                 },
        //                 body: JSON.stringify({
        //                     ids: selectedIds
        //                 })
        //             })
        //             .then(response => response.json())
        //             .then(data => {
        //                 if (data.success) {
        //                     showToast(data.message, 'success');

        //                     selectedIds.forEach(id => {
        //                         const row = document.querySelector(`input[value="${id}"]`)?.closest('tr');
        //                         if (row) {
        //                             const statusSpan = row.querySelector('td:nth-child(6) span');
        //                             const currentStatus = statusSpan?.textContent?.toLowerCase().trim();

        //                             if (currentStatus !== 'used') {
        //                                 if (statusSpan) {
        //                                     statusSpan.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800';
        //                                     statusSpan.textContent = 'Used';
        //                                 }

        //                                 const useButton = row.querySelector('button[onclick*="markAsUsed"]');
        //                                 if (useButton) {
        //                                     useButton.textContent = 'USED';
        //                                     useButton.classList.add('opacity-50', 'cursor-not-allowed');
        //                                     useButton.disabled = true;
        //                                 }

        //                                 const deleteButton = row.querySelector('button[onclick*="confirmDelete"]');
        //                                 if (deleteButton) {
        //                                     deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        //                                     deleteButton.disabled = true;
        //                                 }
        //                             }
        //                         }
        //                     });

        //                     if (checkAll) {
        //                         checkAll.checked = false;
        //                     }
        //                     rowChecks.forEach(chk => chk.checked = false);

        //                     updateSelectAllCheckbox();
        //                     updateSelectedCount();
        //                 } else {
        //                     showToast(data.message || 'Failed to mark barcodes as used', 'error');
        //                 }
        //             })
        //             .catch(error => {
        //                 console.error('Bulk mark as used error:', error);
        //                 showToast('Failed to mark as used: ' + error.message, 'error');
        //             });
        //     });
        // }

        // ========== CONFIRM DELETE FUNCTION ==========
        function confirmDelete(barcodeId) {
            const row = document.querySelector(`input[value="${barcodeId}"]`)?.closest('tr');
            const statusSpan = row?.querySelector('td:nth-child(6) span');
            const currentStatus = statusSpan?.textContent?.toLowerCase().trim();

            if (currentStatus === 'used') {
                showToast('Cannot delete a barcode that has been used', 'error');
                return;
            }

            if (!confirm('Are you sure you want to delete this barcode? This will also delete the associated product.')) {
                return;
            }

            showToast('Deleting barcode...', 'success');

            fetch(`/product/warehouse/barcode/${barcodeId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        if (row) {
                            row.remove();
                        }
                        selectedBarcodes.delete(normalizeBarcodeId(barcodeId));
                        persistBarcodeSelection();
                        updateSelectedCount();
                        updateSelectAllCheckbox();
                    } else {
                        showToast(data.message || 'Failed to delete barcode', 'error');
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    showToast('Delete failed: ' + error.message, 'error');
                });
        }
        window.confirmDelete = confirmDelete;

        // ========== BULK DELETE FUNCTION ==========
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                const selectedIds = getSelectedIds();

                if (selectedIds.length === 0) {
                    showToast('Please select barcodes to delete', 'error');
                    return;
                }

                let hasUsedBarcodes = false;
                let usedBarcodesList = [];

                getSelectedBarcodeEntries().forEach(barcode => {
                    if (barcode.status.toLowerCase() === 'used') {
                        hasUsedBarcodes = true;
                        usedBarcodesList.push(barcode.uniqueId || barcode.id);
                    }
                });

                if (hasUsedBarcodes) {
                    const usedList = usedBarcodesList.join(', ');
                    showToast(`Cannot delete barcodes that are marked as used: ${usedList}`, 'error');
                    return;
                }

                if (!confirm(`Are you sure you want to delete ${selectedIds.length} selected barcodes? This will also delete associated products.`)) {
                    return;
                }

                showToast(`Deleting ${selectedIds.length} barcodes...`, 'success');

                fetch('{{ route('warehouse.barcode.bulk-delete') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: selectedIds
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            selectedIds.forEach(id => {
                                const row = document.querySelector(`input[value="${id}"]`)?.closest('tr');
                                if (row) {
                                    row.remove();
                                }
                                selectedBarcodes.delete(normalizeBarcodeId(id));
                            });
                            persistBarcodeSelection();
                            updateSelectAllCheckbox();
                            updateSelectedCount();
                        } else {
                            showToast(data.message || 'Failed to delete barcodes', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Bulk delete error:', error);
                        showToast('Delete failed: ' + error.message, 'error');
                    });
            });
        }

        // ========== PRINT SELECTED BARCODES ==========
        if (printSelectedBtn) {
            printSelectedBtn.addEventListener('click', function() {
                const selectedIds = getSelectedIds();

                if (selectedIds.length === 0) {
                    showToast('Please select barcodes to print', 'error');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('warehouse.barcode.print-view') }}';
                form.target = '_blank';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);

                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        }

        // ========== EXPORT BARCODES ==========
        function exportBarcodes(format) {
            const selectedIds = getSelectedIds();
            let url = format === 'pdf' ?
                '{{ route('warehouse.barcode.export.pdf') }}' :
                '{{ route('warehouse.barcode.export.excel') }}';

            if (selectedIds.length > 0) {
                url += `?ids=${selectedIds.join(',')}`;
            }

            window.open(url, '_blank');
            showToast(`Exporting ${selectedIds.length || 'all'} barcodes as ${format.toUpperCase()}`, 'success');
        }
        window.exportBarcodes = exportBarcodes;


        // ========== GET SELECTED IDS ==========
        function getSelectedIds() {
            return Array.from(selectedBarcodes.keys());
        }

        // ========== FORM VALIDATION ==========
        const barcodeForm = document.getElementById('barcodeForm');
        if (barcodeForm) {
            barcodeForm.addEventListener('submit', function(e) {
                const containerId = document.getElementById('containerId').value.trim();
                const category = document.getElementById('category').value;
                const startNo = document.getElementById('startNo').value.trim();
                const endNo = document.getElementById('endNo').value.trim();

                if (!containerId || !category || !startNo || !endNo) {
                    e.preventDefault();
                    showToast('Please fill in all fields', 'error');
                    return;
                }

                if (!isValidSequence(startNo) || !isValidSequence(endNo)) {
                    e.preventDefault();
                    showToast('Start and End numbers must be in format like "001", "KAP1", "A100"', 'error');
                    return;
                }

                const startPrefix = startNo.replace(/\d+$/, '');
                const endPrefix = endNo.replace(/\d+$/, '');

                if (startPrefix !== endPrefix) {
                    e.preventDefault();
                    showToast('Start and End numbers must have the same prefix', 'error');
                    return;
                }

                const startNum = parseInt(startNo.replace(/\D/g, ''));
                const endNum = parseInt(endNo.replace(/\D/g, ''));

                if (startNum > endNum) {
                    e.preventDefault();
                    showToast('Start number must be less than or equal to End number', 'error');
                    return;
                }

                const count = endNum - startNum + 1;
                if (count > 100) {
                    if (!confirm(`You are about to generate ${count} barcodes. This may take a moment. Continue?`)) {
                        e.preventDefault();
                    }
                }
            });
        }

        function isValidSequence(seq) {
            return /^[A-Za-z]*\d+$/.test(seq);
        }

        // ========== PLACEHOLDER UPDATES ==========
        document.getElementById('startNo')?.addEventListener('focus', function() {
            if (this.value === '') {
                this.placeholder = 'e.g., 001 or KAP1';
            }
        });

        document.getElementById('endNo')?.addEventListener('focus', function() {
            if (this.value === '') {
                this.placeholder = 'e.g., 100 or KAP100';
            }
        });

        // ========== SESSION MESSAGES ==========
        // @if (session('success'))
        //     showToast('{{ session('success') }}', 'success');
        // @endif

        // @if (session('error'))
        //     showToast('{{ session('error') }}', 'error');
        // @endif

        // @if (session('generated_ids'))
        //     setTimeout(() => {
        //         document.getElementById('idListContainer')?.scrollIntoView({
        //             behavior: 'smooth'
        //         });
        //     }, 500);
        // @endif
    </script>
@endpush
