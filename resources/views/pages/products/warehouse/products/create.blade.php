@extends('layouts.auth')

@section('title', 'Add Product Manually')

@section('content')
<div class="mt-4 mx-auto px-3 max-w-3xl">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
        <a href="{{ route('warehouse.products.index') }}" class="hover:text-[#0078C8]">Products</a>
        <span>/</span>
        <span class="text-gray-800">Add Product Manually</span>
    </div>

    <!-- Flash Messages -->
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg shadow-sm">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-red-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <p class="font-medium">{{ session('error') }}</p>
                    @if(session('deleted_product_id'))
                        <form method="POST" action="{{ route('warehouse.products.restore', session('deleted_product_id')) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-xs font-bold rounded shadow hover:bg-red-700 transition-all uppercase tracking-widest flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Restore and Edit This Product
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Add Product Manually</h2>
        <p class="text-xs text-gray-400 mb-6">The Unique ID will be generated from Container ID + Category + Sequence Number.</p>

        <form method="POST" action="{{ route('warehouse.products.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Container ID -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Container ID <span class="text-red-500">*</span></label>
                    <input type="text" name="container_id" id="container_id"
                        value="{{ old('container_id') }}"
                        placeholder="e.g. CONT, BOX1, WH-A"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all @error('container_id') border-red-400 @enderror">
                    @error('container_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" id="category_id"
                        class="select2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all @error('category_id') border-red-400 @enderror">
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                data-prefix="{{ strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3)) }}"
                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Sequence Number -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Sequence Number <span class="text-red-500">*</span></label>
                    <input type="text" name="sequence_number" id="sequence_number"
                        value="{{ old('sequence_number') }}"
                        placeholder="e.g. 001, A01, 100"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all @error('sequence_number') border-red-400 @enderror">
                    @error('sequence_number')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Count -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Count</label>
                    <input type="number" name="count" id="count"
                        value="{{ old('count', 0) }}"
                        min="0" placeholder="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="title"
                        value="{{ old('title') }}"
                        placeholder="Enter product title"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                </div>

                <!-- DPN/SN Number -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">DPN/SN Number</label>
                    <input type="text" name="dpn_sn_number" id="dpn_sn_number"
                        value="{{ old('dpn_sn_number') }}"
                        placeholder="Enter DPN/SN number"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all @error('dpn_sn_number') border-red-400 @enderror">
                    @error('dpn_sn_number')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Problem -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="problem" id="problem" rows="2"
                        placeholder="Notes with the product"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">{{ old('problem') }}</textarea>
                </div>

                <!-- Warehouse Location -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Warehouse Location</label>
                    <select name="warehouse_location_id" id="warehouse_location_id"
                        class="select2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        <option value="">Select location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ old('warehouse_location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}{{ $location->code ? ' (' . $location->code . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <!-- Unique ID Preview -->
            <div class="mt-5 p-4 bg-gray-50 rounded-lg border border-gray-200" id="previewBox">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Generated Unique ID Preview</p>
                <p id="uniqueIdPreview" class="text-lg font-mono font-bold text-[#0078C8]">—</p>
                <p id="uniqueIdStatus" class="text-xs mt-1 hidden"></p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('warehouse.products.index') }}"
                    class="px-5 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                    class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors">
                    Create Product
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .select2-container--default .select2-selection--single {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        height: 38px;
        padding: 4px 8px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%', allowClear: true });

        const checkUrl   = "{{ route('warehouse.products.check-unique-id') }}";
        const statusEl   = document.getElementById('uniqueIdStatus');
        const previewBox = document.getElementById('previewBox');
        let   checkTimer = null;

        function setStatus(data) {
            statusEl.classList.remove('hidden', 'text-green-600', 'text-red-600', 'text-gray-400');
            statusEl.innerHTML = ''; // Clear previous content

            if (!data) {
                statusEl.classList.add('hidden');
                previewBox.classList.remove('border-green-300', 'border-red-300');
                return;
            }

            if (!data.exists) {
                statusEl.textContent = '✓ Unique ID is available';
                statusEl.classList.add('text-green-600');
                previewBox.classList.remove('border-red-300');
                previewBox.classList.add('border-green-300');
            } else if (data.is_deleted) {
                statusEl.classList.add('text-red-600');
                previewBox.classList.remove('border-green-300');
                previewBox.classList.add('border-red-300');
                
                // Use the route helper but replace the placeholder
                let restoreUrl = "{{ route('warehouse.products.restore', ':id') }}".replace(':id', data.product_id);

                statusEl.innerHTML = `
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                            <span class="font-bold">This Unique ID exists in the "Deleted" records.</span>
                        </div>
                        <p class="text-[11px] text-gray-500 lowercase px-6">you cannot create a new record with this id, but you can restore the old one and reuse it.</p>
                        <form method="POST" action="${restoreUrl}" class="px-6">
                            @csrf
                            <button type="submit" class="mt-1 px-4 py-1.5 bg-red-600 text-white text-[10px] font-bold rounded hover:bg-red-700 transition-all uppercase tracking-wider flex items-center gap-2">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Restore & Edit Product
                            </button>
                        </form>
                    </div>
                `;
            } else if (data.exists) {
                statusEl.textContent = '✗ This Unique ID already exists and is active';
                statusEl.classList.add('text-red-600');
                previewBox.classList.remove('border-green-300');
                previewBox.classList.add('border-red-300');
            }
        }

        function checkUniqueness(uniqueId) {
            clearTimeout(checkTimer);
            if (!uniqueId) { setStatus(null); return; }
            statusEl.textContent = 'Checking…';
            statusEl.classList.remove('hidden', 'text-green-600', 'text-red-600');
            statusEl.classList.add('text-gray-400');
            checkTimer = setTimeout(function () {
                $.getJSON(checkUrl, { unique_id: uniqueId }, function (data) {
                    setStatus(data);
                }).fail(function () {
                    setStatus(null);
                });
            }, 400);
        }

        function updatePreview() {
            const container = document.getElementById('container_id').value.trim().toUpperCase();
            const catSelect = document.getElementById('category_id');
            const selected  = catSelect.options[catSelect.selectedIndex];
            const prefix    = selected ? (selected.dataset.prefix || '') : '';
            const seq       = document.getElementById('sequence_number').value.trim().toUpperCase();

            const preview = document.getElementById('uniqueIdPreview');
            if (container && prefix && seq) {
                const uid = container + '-' + prefix + '-' + seq;
                preview.textContent = uid;
                preview.classList.remove('text-gray-400');
                preview.classList.add('text-[#0078C8]');
                checkUniqueness(uid);
            } else {
                preview.textContent = '—';
                preview.classList.add('text-gray-400');
                preview.classList.remove('text-[#0078C8]');
                setStatus(null);
            }
        }

        document.getElementById('container_id').addEventListener('input', updatePreview);
        document.getElementById('sequence_number').addEventListener('input', updatePreview);
        $('#category_id').on('change', updatePreview);

        // Run preview once on load
        updatePreview();
    });
</script>
@endpush
