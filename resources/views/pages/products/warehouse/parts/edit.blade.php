{{-- resources/views/pages/products/warehouse/parts/edit.blade.php --}}
@extends('layouts.auth')

@section('title', 'Edit Part - ' . $part->name)

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Edit Part</h2>
                        <div class="text-sm text-gray-600 mt-1">Part Number: {{ $part->part_number }}</div>
                    </div>
                    <a href="{{ route('warehouse.parts.show', $part->id) }}"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-normal rounded hover:bg-gray-50 w-full sm:w-auto text-center">
                        BACK TO DETAIL
                    </a>
                </div>

                <!-- Main Content -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-4">
                    <div class="p-6">
                        <form action_url="{{ route('warehouse.parts.update', $part->id) }}"
                            back_url="{{ route('warehouse.parts.index') }}" id="formSubmit" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="space-y-6">
                                <!-- Basic Information -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Part Name <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="name" value="{{ old('name', $part->name) }}"
                                                required
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8] @error('name') border-red-500 @enderror"
                                                placeholder="Enter part name">
                                            @error('name')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                            <input type="text" name="category"
                                                value="{{ old('category', $part->category) }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                                placeholder="e.g., Memory, Storage, Processor">
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                            <textarea name="description" rows="3"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                                placeholder="Enter part description...">{{ old('description', $part->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Information -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Location Information</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                                            <select name="warehouse_location_id"
                                                class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                                <option value="">Select Location</option>
                                                @foreach ($locations as $location)
                                                    <option value="{{ $location->id }}"
                                                        {{ ($part->warehouse_location_id ?? '') == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Rack
                                                Position</label>
                                            <input type="text" name="rack_position"
                                                value="{{ old('rack_position', $part->rack_position) }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                                placeholder="e.g., Rack A-12">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Shelf</label>
                                            <input type="text" name="shelf" value="{{ old('shelf', $part->shelf) }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                                placeholder="e.g., Shelf 3">
                                        </div>
                                    </div>
                                </div>

                                <!-- Stock Information -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Stock Information</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Quantity <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" name="quantity"
                                                value="{{ old('quantity', $part->quantity) }}" min="0" required
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8] @error('quantity') border-red-500 @enderror">
                                            @error('quantity')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Min Stock
                                                Level</label>
                                            <input type="number" name="min_stock_level"
                                                value="{{ old('min_stock_level', $part->min_stock_level ?? 5) }}"
                                                min="0"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Reorder
                                                Level</label>
                                            <input type="number" name="reorder_level"
                                                value="{{ old('reorder_level', $part->reorder_level ?? 10) }}"
                                                min="0"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]">
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Display (Read-only) -->
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700">Current Status</h4>
                                            <p class="text-xs text-gray-500 mt-1">Status is automatically calculated based
                                                on quantity</p>
                                        </div>
                                        @php
                                            $statusClass = match ($part->status) {
                                                'IN_STOCK' => 'bg-green-100 text-green-800',
                                                'LOW_STOCK' => 'bg-yellow-100 text-yellow-800',
                                                'OUT_OF_STOCK' => 'bg-red-100 text-red-800',
                                                'REQUESTED' => 'bg-blue-100 text-blue-800',
                                                'RESERVED' => 'bg-purple-100 text-purple-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <span class="px-3 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                                            {{ str_replace('_', ' ', $part->status) }}
                                        </span>
                                    </div>
                                    <input type="hidden" name="status" value="{{ $part->status }}">
                                </div>
                            </div>

                            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                                <a href="{{ route('warehouse.parts.show', $part->id) }}"
                                    class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                                    CANCEL
                                </a>
                                <button type="submit"
                                    class="px-6 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0]">
                                    UPDATE PART
                                </button>
                            </div>
                        </form>
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
            toast.classList.remove('hidden');
            content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    ${type === 'success'
                        ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                        : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;
            setTimeout(hideToast, 3000);
        }

        function hideToast() {
            document.getElementById('toast').classList.add('hidden');
        }

        @if (session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif

        @if (session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif
    </script>
@endpush
