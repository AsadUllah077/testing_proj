{{-- resources/views/pages/products/warehouse/scrap/create.blade.php --}}
@extends('layouts.auth')

@section('title', 'Add Scrap Material')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('warehouse.scrap.index') }}" class="text-gray-600 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </a>
                            <h2 class="text-stone-950 text-xl font-normal">Add Scrap Material</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 relative w-full sm:w-auto">
                            <!-- Save Button -->
                            <button type="submit" form="formSubmit" id="submitBtn"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                SAVE
                            </button>

                            <!-- Clear Button -->
                            <button type="button" onclick="clearForm()"
                                class="px-4 text-center py-2 border border-[#0078C8] text-[#0078C8] text-xs font-normal rounded hover:bg-gray-50 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                CLEAR
                            </button>

                            <!-- Cancel Button -->
                            <a href="{{ route('warehouse.scrap.index') }}"
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
                            <form id="formSubmit" action_url="{{ route('warehouse.scrap.store') }}"
                                back_url="{{ route('warehouse.scrap.index') }}" method="POST" class="space-y-6">
                                @csrf

                                <!-- Row 1: Item Description & Scrap Category -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Item Description -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Item Description <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="item_description" value="{{ old('item_description') }}"
                                            required placeholder="Enter item description"
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
                                                    {{ old('scrap_category_id') == $category->id ? 'selected' : '' }}>
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
                                                value="{{ old('weight') }}" placeholder="Enter weight"
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
                                            Quantity Type <span class="text-red-500">*</span>
                                        </label>
                                        <select name="quantity_type" required
                                            class="select2 w-full py-2.5 px-4 border @error('quantity_type') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <option value="">Select quantity type</option>
                                            <option value="pieces"
                                                {{ old('quantity_type') == 'pieces' ? 'selected' : '' }}>Pieces
                                            </option>
                                            <option value="bundles"
                                                {{ old('quantity_type') == 'bundles' ? 'selected' : '' }}>Bundles
                                            </option>
                                            <option value="pallets"
                                                {{ old('quantity_type') == 'pallets' ? 'selected' : '' }}>Pallets
                                            </option>
                                            <option value="bags" {{ old('quantity_type') == 'bags' ? 'selected' : '' }}>
                                                Bags</option>
                                            <option value="boxes" {{ old('quantity_type') == 'boxes' ? 'selected' : '' }}>
                                                Boxes</option>
                                            <option value="containers"
                                                {{ old('quantity_type') == 'containers' ? 'selected' : '' }}>Containers
                                            </option>
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
                                        <input type="number" name="quantity" value="{{ old('quantity') }}" required
                                            placeholder="Enter quantity "
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
                                                    {{ old('scrap_type_id') == $type->id ? 'selected' : '' }}>
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
                                        <input type="text" name="source" value="{{ old('source') }}" required
                                            placeholder="Enter source (e.g., Failed Unit, Production Waste, etc.)"
                                            class="w-full py-2.5 px-4 border @error('source') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        @error('source')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Current Location -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                                        <select name="warehouse_location_id"
                                            class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                            <option value="">Select Location</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}">
                                                    {{ $location->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Additional Notes -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Scrap Reason <span class="text-red-500">*</span>
                                        </label>
                                        <textarea name="scrap_reason" rows="3" required placeholder="Enter scrap reason..."
                                            class="w-full py-2.5 px-4 border @error('scrap_reason') border-red-500 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none">{{ old('scrap_reason') }}</textarea>
                                        @error('scrap_reason')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional
                                            Notes</label>
                                        <textarea name="notes" rows="3" placeholder="Enter any additional notes or comments..."
                                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none">{{ old('notes') }}</textarea>
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

    {{-- Clear Confirmation Modal --}}
    <div id="confirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Clear Form</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to clear all form fields? This
                                    action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="confirmClear()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Clear
                    </button>
                    <button type="button" onclick="hideConfirmationModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
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
                info: '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>',
            };
            content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    <svg class="w-5 h-5 ${colors[type]||colors.info}" fill="currentColor" viewBox="0 0 20 20">${icons[type]||icons.info}</svg>
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('toast').classList.add('hidden')">✕</button>
            </div>`;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3500);
        }

        function clearForm() {
            document.getElementById('confirmationModal').classList.remove('hidden');
        }

        function hideConfirmationModal() {
            document.getElementById('confirmationModal').classList.add('hidden');
        }

        function confirmClear() {
            document.getElementById('formSubmit').reset();
            hideConfirmationModal();
            showToast('Form cleared successfully', 'info');
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

        // Number min=0 guard
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
