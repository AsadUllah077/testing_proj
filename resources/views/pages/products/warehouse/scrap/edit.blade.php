{{-- resources/views/pages/products/warehouse/scrap/edit.blade.php --}}
@extends('layouts.auth')

@section('title', 'Edit Scrap Material - ' . $scrap->scrap_id)

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('warehouse.scrap.details', $scrap->id) }}"
                                class="text-gray-600 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </a>
                            <h2 class="text-stone-950 text-xl font-normal">Edit Scrap Material</h2>
                            <span class="text-sm text-gray-400 font-mono">{{ $scrap->scrap_id }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-2 relative w-full sm:w-auto">
                            <!-- Update Button -->
                            <button type="submit" form="formSubmit" id="submitBtn"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                UPDATE
                            </button>

                            <!-- Cancel Button -->
                            <a href="{{ route('warehouse.scrap.details', $scrap->id) }}"
                                class="px-4 text-center py-2 border border-gray-300 text-gray-700 text-xs font-normal rounded hover:bg-gray-50 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                CANCEL
                            </a>
                        </div>
                    </div>

                    @if (session('error'))
                        <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Main Content Card -->
                    <div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-6">
                            <form id="formSubmit" action_url="{{ route('warehouse.scrap.update', $scrap->id) }}"
                                back_url="{{ route('warehouse.scrap.details', $scrap->id) }}" method="POST"
                                class="space-y-6">
                                @csrf
                                @method('PUT')

                                <!-- Row 1: Item Description & Scrap Category -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Item Description -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Item Description <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="item_description"
                                            value="{{ old('item_description', $scrap->item_description) }}" required
                                            placeholder="Enter item description"
                                            class="w-full py-2.5 px-4 border @error('item_description') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        @error('item_description')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Scrap Category -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Material Category <span class="text-red-500">*</span>
                                        </label>
                                        <select name="scrap_category_id" required
                                            class="select2 w-full py-2.5 px-4 border @error('scrap_category_id') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <option value="">Select material category</option>
                                            @foreach ($scrapCategories as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ old('scrap_category_id', $scrap->scrap_category_id) == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('scrap_category_id')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Row 2: Weight & Quantity Type -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Weight -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Weight <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="number" name="weight" step="0.01" min="0" required
                                                value="{{ old('weight', $scrap->weight) }}" placeholder="Enter weight"
                                                class="w-full py-2.5 pl-4 pr-12 border @error('weight') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <div
                                                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <span class="text-gray-500 text-sm">kg</span>
                                            </div>
                                        </div>
                                        @error('weight')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Quantity Type -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Quantity <span class="text-red-500">*</span>
                                        </label>
                                        <select name="quantity_type" required
                                            class="select2 w-full py-2.5 px-4 border @error('quantity_type') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <option value="">Select quantity type</option>
                                            <option value="pieces"
                                                {{ old('quantity_type', $scrap->quantity_type) == 'pieces' ? 'selected' : '' }}>
                                                Pieces</option>
                                            <option value="bundles"
                                                {{ old('quantity_type', $scrap->quantity_type) == 'bundles' ? 'selected' : '' }}>
                                                Bundles</option>
                                            <option value="pallets"
                                                {{ old('quantity_type', $scrap->quantity_type) == 'pallets' ? 'selected' : '' }}>
                                                Pallets</option>
                                            <option value="bags"
                                                {{ old('quantity_type', $scrap->quantity_type) == 'bags' ? 'selected' : '' }}>
                                                Bags</option>
                                            <option value="boxes"
                                                {{ old('quantity_type', $scrap->quantity_type) == 'boxes' ? 'selected' : '' }}>
                                                Boxes</option>
                                            <option value="containers"
                                                {{ old('quantity_type', $scrap->quantity_type) == 'containers' ? 'selected' : '' }}>
                                                Containers</option>
                                        </select>
                                        @error('quantity_type')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Row 3: Scrap Reason & Scrap Type -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Scrap Reason -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Quantity <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" name="quantity"
                                            value="{{ old('quantity', isset($scrap) ? $scrap->quantity : '') }}" required
                                            placeholder="Enter quantity .."
                                            class="w-full py-2.5 px-4 border @error('quantity') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        @error('quantity')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Scrap Type -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Scrap Type <span class="text-red-500">*</span>
                                        </label>
                                        <select name="scrap_type_id" required
                                            class="select2 w-full py-2.5 px-4 border @error('scrap_type_id') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <option value="">Select scrap type</option>
                                            @foreach ($scrapTypes as $type)
                                                <option value="{{ $type->id }}"
                                                    {{ old('scrap_type_id', $scrap->scrap_type_id) == $type->id ? 'selected' : '' }}>
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('scrap_type_id')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Row 4: Source & Current Location -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Source -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Source <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="source"
                                            value="{{ old('source', isset($scrap) ? $scrap->source : '') }}" required
                                            placeholder="Enter source (e.g., Failed Unit, Production Waste, etc.)"
                                            class="w-full py-2.5 px-4 border @error('source') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        @error('source')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Current Location -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                        <select name="warehouse_location_id"
                                            class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                            <option value="">Select Location</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}"
                                                    {{ ($scrap->warehouse_location_id ?? '') == $location->id ? 'selected' : '' }}>
                                                    {{ $location->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Scrap Reason <span class="text-red-500">*</span>
                                        </label>
                                        <textarea name="scrap_reason" rows="3" required placeholder="Enter scrap reason..."
                                            class="w-full py-2.5 px-4 border @error('scrap_reason') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none">{{ old('scrap_reason', $scrap->scrap_reason) }}</textarea>
                                        @error('scrap_reason')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <!-- Additional Notes -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional
                                            Notes</label>
                                        <textarea name="notes" rows="3" placeholder="Enter any additional notes or comments..."
                                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none">{{ old('notes', $scrap->notes) }}</textarea>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

@endsection

@push('scripts')
    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toast-content');
            const colors = {
                success: 'text-green-600',
                error: 'text-red-600',
                info: 'text-blue-600'
            };
            const icons = {
                success: '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/>',
                error: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>',
            };
            content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    <svg class="w-5 h-5 ${colors[type]||colors.success}" fill="currentColor" viewBox="0 0 20 20">${icons[type]||icons.success}</svg>
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('toast').classList.add('hidden')">✕</button>
            </div>`;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3500);
        }

        // Remove red border on interaction
        document.querySelectorAll('input, select, textarea').forEach(function(field) {
            field.addEventListener('input', function() {
                this.classList.remove('border-red-500');
            });
            field.addEventListener('change', function() {
                this.classList.remove('border-red-500');
            });
        });

        document.querySelectorAll('input[type="number"]').forEach(function(input) {
            input.addEventListener('input', function() {
                if (this.value < 0) this.value = 0;
            });
        });

        @if (session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif
        @if (session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif
    </script>
@endpush
