@extends('layouts.auth')

@section('title', 'Product Issue')

@section('content')
<x-acccount.loader />
<div class="mt-4 mx-auto px-3 max-w-3xl">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
        <a href="{{ route('warehouse.product-issue.index') }}" class="hover:text-[#0078C8]">Product Issue</a>
        <span>/</span>
        <span class="text-gray-800 tracking-wide uppercase text-xs font-bold">Issue Product</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Issue Product (P: {{ count($products) }}, E: {{ count($employees) }})</h2>
        <p class="text-xs text-gray-400 mb-6">Select a product and add optional employee/reference details to record an issue event.</p>

        <form id="formSubmit" method="POST" action_url="{{ route('warehouse.product-issue.store') }}" back_url="{{ route('warehouse.product-issue.index') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Product -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 uppercase tracking-tighter">Product <span class="text-red-500">*</span></label>
                    <select name="product_id" id="product_id" required class="select2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] outline-none">
                        <option value="">Select a product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" data-manual="{{ $product->manual_status }}" data-stock="{{ $product->count }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->unique_id }} — {{ $product->title }} 
                                @if($product->manual_status) (Stock: {{ $product->count }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')<p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>@enderror
                </div>

                <!-- Employee -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 uppercase tracking-tighter">Employee</label>
                    <select name="employee_id" id="employee_id" class="select2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] outline-none">
                        <option value="">Select employee (optional)</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')<p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>@enderror
                </div>

                <!-- Reference -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 uppercase tracking-tighter">Reference</label>
                    <input type="text" name="reference" id="reference"
                           value="{{ old('reference') }}"
                           placeholder="Enter reference"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    @error('reference')<p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>@enderror
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 uppercase tracking-tighter">Issue Date <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="issue_date" id="issue_date" 
                           value="{{ old('issue_date', now()->format('Y-m-d\TH:i')) }}" required
                           class="date w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    @error('issue_date')<p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>@enderror
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 uppercase tracking-tighter">Quantity <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="quantity" id="quantity" 
                            value="{{ old('quantity', 1) }}" min="1" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] outline-none transition-all">
                        <span id="qtyUnit" class="absolute right-3 top-2 text-xs text-gray-400 font-medium">Unit</span>
                    </div>
                    <p id="stockWarning" class="text-[10px] text-amber-600 mt-1 hidden"></p>
                    @error('quantity')<p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>@enderror
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1 uppercase tracking-tighter">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                        placeholder="Additional details about this issue..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">{{ old('notes') }}</textarea>
                    @error('notes')<p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>@enderror
                </div>

            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-100">
                <a href="{{ route('warehouse.product-issue.index') }}"
                    class="px-5 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                    class="px-8 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors shadow-sm tracking-wide uppercase font-bold text-xs">
                    Process Issue
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
    input:disabled {
        background-color: #f9fafb;
        color: #9ca3af;
        cursor: not-allowed;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%', allowClear: true });

        const productSelect = $('#product_id');
        const qtyInput = $('#quantity');
        const qtyUnit = $('#qtyUnit');
        const stockWarning = $('#stockWarning');

        productSelect.on('change', function() {
            const selected = $(this).find(':selected');
            const isManual = selected.data('manual') == 1;
            const stock = selected.data('stock');

            qtyInput.val(1);
            stockWarning.addClass('hidden');

            if (isManual) {
                qtyInput.prop('disabled', false).prop('readonly', false);
                qtyInput.attr('max', stock);
                qtyUnit.text('Units');
                if (stock > 0) {
                    stockWarning.text(`Available Stock: ${stock}`).removeClass('hidden');
                }
            } else {
                qtyInput.prop('disabled', true).prop('readonly', true);
                qtyUnit.text('Fixed');
                stockWarning.addClass('hidden');
            }
        });

        qtyInput.on('input', function() {
            const max = parseInt($(this).attr('max'));
            const val = parseInt($(this).val());
            if (max && val > max) {
                $(this).val(max);
            }
        });
    });
</script>
@endpush
