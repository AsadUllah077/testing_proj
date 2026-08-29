{{-- resources/views/pages/parts/create.blade.php --}}
@extends('layouts.auth')

@section('title', 'Add New Part')

@section('content')
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6 mb-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-black">Add New Part</h2>
                            <a href="{{ route('warehouse.parts.index') }}"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] w-full sm:w-auto text-center">
                                Back
                            </a>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md  p-6">
                        <form class="space-y-8" method="post" action_url="{{ route('warehouse.parts.store') }}"
                            back_url="{{ route('warehouse.parts.index') }}" id="formSubmit">
                            @csrf

                            <!-- ================= BASIC INFORMATION ================= -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Basic Information</h3>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Part Name *</label>
                                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            placeholder="Enter part name" required />
                                        <div id="name_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Category</label>
                                        <input type="text" name="category" id="category" value="{{ old('category') }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            placeholder="e.g., Memory, Storage, Processor" />
                                        <div id="category_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="block text-sm text-gray-600 mb-1">Description</label>
                                        <textarea name="description" id="description" rows="3"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            placeholder="Enter part description...">{{ old('description') }}</textarea>
                                        <div id="description_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ================= LOCATION INFORMATION ================= -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Location Information</h3>
                                <div class="grid sm:grid-cols-3 gap-4">
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

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Rack Position</label>
                                        <input type="text" name="rack_position" id="rack_position"
                                            value="{{ old('rack_position') }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            placeholder="e.g., Rack A-12" />
                                        <div id="rack_position_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Shelf</label>
                                        <input type="text" name="shelf" id="shelf" value="{{ old('shelf') }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            placeholder="e.g., Shelf 3" />
                                        <div id="shelf_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ================= STOCK INFORMATION ================= -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Stock Information</h3>
                                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Quantity *</label>
                                        <input type="number" name="quantity" id="quantity"
                                            value="{{ old('quantity', 0) }}" min="0" required
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none" />
                                        <div id="quantity_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Min Stock Level</label>
                                        <input type="number" name="min_stock_level" id="min_stock_level"
                                            value="{{ old('min_stock_level', 5) }}" min="0"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none" />
                                        <div id="min_stock_level_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Reorder Level</label>
                                        <input type="number" name="reorder_level" id="reorder_level"
                                            value="{{ old('reorder_level', 10) }}" min="0"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none" />
                                        <div id="reorder_level_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Status *</label>
                                        <select name="status" id="status" required
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none">
                                            <option value="">Select status</option>
                                            <option value="IN_STOCK" {{ old('status') == 'IN_STOCK' ? 'selected' : '' }}>
                                                IN STOCK</option>
                                            <option value="LOW_STOCK"
                                                {{ old('status') == 'LOW_STOCK' ? 'selected' : '' }}>LOW STOCK</option>
                                            <option value="OUT_OF_STOCK"
                                                {{ old('status') == 'OUT_OF_STOCK' ? 'selected' : '' }}>OUT OF STOCK
                                            </option>
                                            <option value="REQUESTED"
                                                {{ old('status') == 'REQUESTED' ? 'selected' : '' }}>REQUESTED</option>
                                            <option value="RESERVED" {{ old('status') == 'RESERVED' ? 'selected' : '' }}>
                                                RESERVED</option>
                                        </select>
                                        <div id="status_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-4 border-t border-gray-100">
                                <a href="{{ route('warehouse.parts.index') }}"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                    CANCEL
                                </a>
                                <button type="submit" id="submitBtn"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                    CREATE PART
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
