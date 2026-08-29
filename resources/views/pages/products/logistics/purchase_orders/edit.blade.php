@extends('layouts.auth')

@section('title', 'Edit Purchase Order #' . $purchaseOrder->po_number)

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-pen-to-square text-[#0078C8] text-xl"></i>
                    <h1 class="text-gray-900 text-xl font-normal">Edit Purchase Order <span
                            class="text-gray-400 text-sm ml-2">#{{ $purchaseOrder->po_number }}</span></h1>
                </div>
                <x-common.back-button url="{{ route('logistics.purchase-orders.index') }}" />
            </div>

            <form id="poForm" action="{{ route('logistics.purchase-orders.update', $purchaseOrder->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {{-- Left Column: Order Details --}}
                    <div class="lg:col-span-12 space-y-6">
                        {{-- Vendor Information --}}
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                                <i class="fa-solid fa-shop text-[#0078C8]"></i>
                                <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">Vendor Information
                                </h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-500 uppercase mb-2">Supplier</label>
                                    <select name="supplier_id"
                                        class="select2 w-full border-slate-200 rounded-lg text-sm focus:ring-[#0078C8]">
                                        <option value="">Select Supplier</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->company_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Order
                                        Date</label>
                                    <input class="date" type="date" name="order_date" value="{{ $purchaseOrder->order_date }}"
                                        class="date w-full h-[42px] px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Expected
                                        Delivery Date</label>
                                    <input class="date" type="date" name="expected_delivery_date"
                                        value="{{ $purchaseOrder->expected_delivery_date }}"
                                        class="date w-full h-[42px] px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-500 uppercase mb-2">Currency</label>
                                    <select name="currency"
                                        class="select2 w-full border-slate-200 rounded-lg text-sm focus:ring-[#0078C8]">
                                        <option value="USD" {{ $purchaseOrder->currency == 'USD' ? 'selected' : '' }}>USD
                                            - US Dollar</option>
                                        <option value="AED" {{ $purchaseOrder->currency == 'AED' ? 'selected' : '' }}>AED
                                            - UAE Dirham</option>
                                        <option value="PKR" {{ $purchaseOrder->currency == 'PKR' ? 'selected' : '' }}>
                                            PKR - Pakistan Rupee</option>
                                        <option value="EUR" {{ $purchaseOrder->currency == 'EUR' ? 'selected' : '' }}>
                                            EUR - Euro</option>
                                        <option value="GBP" {{ $purchaseOrder->currency == 'GBP' ? 'selected' : '' }}>
                                            GBP - British Pound</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Payment
                                        Terms</label>
                                    <select name="payment_terms"
                                        class="select2 w-full border-slate-200 rounded-lg text-sm focus:ring-[#0078C8]">
                                        <option value="Net 30"
                                            {{ $purchaseOrder->payment_terms == 'Net 30' ? 'selected' : '' }}>Net 30
                                        </option>
                                        <option value="Net 60"
                                            {{ $purchaseOrder->payment_terms == 'Net 60' ? 'selected' : '' }}>Net 60
                                        </option>
                                        <option value="Due on Receipt"
                                            {{ $purchaseOrder->payment_terms == 'Due on Receipt' ? 'selected' : '' }}>Due
                                            on Receipt</option>
                                        <option value="Advanced"
                                            {{ $purchaseOrder->payment_terms == 'Advanced' ? 'selected' : '' }}>Advanced
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Status</label>
                                    <select name="status"
                                        class="select2 w-full border-slate-200 rounded-lg text-sm focus:ring-[#0078C8]">
                                        <option value="Draft" {{ $purchaseOrder->status == 'Draft' ? 'selected' : '' }}>
                                            Draft</option>
                                        <option value="Sent" {{ $purchaseOrder->status == 'Sent' ? 'selected' : '' }}>
                                            Sent</option>
                                        <option value="Partially Received"
                                            {{ $purchaseOrder->status == 'Partially Received' ? 'selected' : '' }}>
                                            Partially Received</option>
                                        <option value="Received"
                                            {{ $purchaseOrder->status == 'Received' ? 'selected' : '' }}>Received</option>
                                        <option value="Cancelled"
                                            {{ $purchaseOrder->status == 'Cancelled' ? 'selected' : '' }}>Cancelled
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Line Items --}}
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                                <i class="fa-solid fa-list-ul text-[#0078C8]"></i>
                                <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">Line Items</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left" id="itemsTable">
                                    <thead class="bg-slate-50/50 border-b border-slate-100">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center w-12">
                                                #</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                                Item / SKU</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                                Description</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-32">
                                                HS Code</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-24 text-center">
                                                QTY</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-24 text-center">
                                                KG</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-32">
                                                Price</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-24">
                                                Tax (%)</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-36 text-right">
                                                Total</th>
                                            <th class="px-4 py-3 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody" class="divide-y divide-slate-100">
                                        @foreach ($purchaseOrder->items as $index => $item)
                                            <tr class="item-row group">
                                                <td
                                                    class="px-4 py-4 text-center text-slate-400 text-xs font-medium row-number">
                                                    {{ $index + 1 }}</td>
                                                <td class="px-4 py-4">
                                                    <input type="text" name="items[{{ $index }}][sku]"
                                                        value="{{ $item->sku }}"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all font-medium text-slate-700 placeholder-slate-300"
                                                        placeholder="SKU-000">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <input type="text" name="items[{{ $index }}][description]"
                                                        value="{{ $item->description }}"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 placeholder-slate-300"
                                                        placeholder="Brief description">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <input type="text" name="items[{{ $index }}][hs_code]"
                                                        value="{{ $item->hs_code }}"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 placeholder-slate-300"
                                                        placeholder="0000.00">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <input type="number" name="items[{{ $index }}][quantity]"
                                                        value="{{ $item->quantity }}"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-center font-bold text-slate-700 qty-input"
                                                        min="1">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <input type="number" name="items[{{ $index }}][weight_kg]"
                                                        value="{{ $item->weight_kg }}"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-center text-slate-500 weight-input"
                                                        step="0.01">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center gap-1 relative">
                                                        <span
                                                            class="absolute left-3 text-slate-400 text-xs font-bold">$</span>
                                                        <input type="number" name="items[{{ $index }}][price]"
                                                            value="{{ $item->price }}"
                                                            class="w-full pl-7 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all font-semibold text-slate-700 price-input"
                                                            step="0.01">
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center gap-1 relative">
                                                        <input type="number"
                                                            name="items[{{ $index }}][tax_percentage]"
                                                            value="{{ $item->tax_percentage }}"
                                                            class="w-full pr-7 pl-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 tax-input"
                                                            step="0.1">
                                                        <span
                                                            class="absolute right-3 text-slate-400 text-xs font-bold">%</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4 text-right">
                                                    <span
                                                        class="text-sm font-bold text-slate-900 line-total">{{ number_format($item->total, 2) }}</span>
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <button type="button"
                                                        class="text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity remove-row p-2">
                                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 border-t border-slate-100 flex justify-between items-start bg-slate-50/30">
                                <button type="button" id="addLine"
                                    class="px-4 py-2 border-2 border-dashed border-slate-200 text-slate-500 text-xs font-bold rounded-lg hover:bg-white hover:border-[#0078C8] hover:text-[#0078C8] transition-all flex items-center gap-2">
                                    <i class="fa-solid fa-plus"></i>
                                    ADD LINE
                                </button>

                                <div class="w-72 space-y-3">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500">Subtotal</span>
                                        <span class="text-slate-900 font-semibold"
                                            id="sumSubtotal">{{ number_format($purchaseOrder->subtotal, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500">Tax</span>
                                        <span class="text-slate-900 font-semibold"
                                            id="sumTax">{{ number_format($purchaseOrder->tax_amount, 2) }}</span>
                                    </div>
                                    <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                                        <span class="text-base font-bold text-slate-900">Total</span>
                                        <span class="text-xl font-black text-[#0078C8]"
                                            id="sumTotal">{{ number_format($purchaseOrder->total_amount, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Attachments --}}
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                                <i class="fa-solid fa-paperclip text-[#0078C8]"></i>
                                <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">Attachments</h3>
                            </div>
                            <div class="p-6">
                                <div id="dropzone"
                                    class="relative group h-32 border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center gap-2 hover:border-[#0078C8] hover:bg-blue-50/30 transition-all cursor-pointer mb-6">
                                    <input type="file" name="attachments[]" id="fileInput" multiple
                                        class="absolute inset-0 opacity-0 cursor-pointer">
                                    <div
                                        class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center group-hover:bg-[#0078C8] group-hover:text-white transition-colors">
                                        <i class="fa-solid fa-cloud-arrow-up text-lg"></i>
                                    </div>
                                    <p class="text-xs font-medium text-slate-500">Click or drag files to upload additional
                                        documents</p>
                                </div>

                                {{-- Progress Bar --}}
                                <div id="uploadProgressContainer"
                                    class="mt-4 hidden animate-in fade-in slide-in-from-top-2 duration-300">
                                    <div class="flex justify-between items-center mb-2">
                                        <span
                                            class="text-[10px] font-black text-[#0078C8] uppercase tracking-widest">Uploading
                                            files...</span>
                                        <span id="uploadPercentage"
                                            class="text-[10px] font-black text-slate-400">0%</span>
                                    </div>
                                    <div
                                        class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden border border-slate-200">
                                        <div id="uploadProgressBar"
                                            class="bg-[#0078C8] h-full w-0 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(0,120,200,0.5)]">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {{-- Existing Attachments --}}
                                    @foreach ($purchaseOrder->attachments as $attachment)
                                        <div
                                            class="bg-slate-50 border border-slate-200 rounded-lg p-3 flex items-center gap-3 group relative">
                                            <div
                                                class="w-10 h-10 bg-white rounded flex items-center justify-center border border-slate-100 shadow-sm">
                                                @if (Str::contains($attachment->file_type, 'pdf'))
                                                    <i class="fa-solid fa-file-pdf text-[#0078C8] text-lg"></i>
                                                @else
                                                    <i class="fa-solid fa-file-image text-[#0078C8] text-lg"></i>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[11px] font-bold text-slate-700 truncate capitalize">
                                                    {{ $attachment->file_name }}</p>
                                                <p class="text-[9px] text-slate-400">
                                                    {{ round($attachment->file_size / 1024, 2) }} KB</p>
                                            </div>
                                            <div
                                                class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <a href="{{ asset('storage/' . $attachment->file_path) }}"
                                                    target="_blank"
                                                    class="p-1.5 hover:bg-blue-100 rounded text-blue-600 transition-colors">
                                                    <i class="fa-solid fa-download text-[10px]"></i>
                                                </a>
                                                <button type="button"
                                                    class="p-1.5 hover:bg-red-100 rounded text-red-600 transition-colors remove-attachment"
                                                    data-id="{{ $attachment->id }}">
                                                    <i class="fa-solid fa-trash-can text-[10px]"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- New File Previews --}}
                                    <div id="filePreviewContainer" class="contents"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3 mb-12">
                    <button type="button"
                        onclick="window.location.href='{{ route('logistics.purchase-orders.index') }}'"
                        class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-lg hover:bg-slate-50 transition-colors">CANCEL</button>
                    <button type="submit"
                        class="px-8 py-2.5 bg-[#0078C8] text-white font-bold rounded-lg hover:bg-[#006bb0] shadow-lg shadow-blue-200 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-save"></i>
                        UPDATE PURCHASE ORDER
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let rowCount = {{ $purchaseOrder->items->count() }};

            function updateCalculations() {
                let subtotal = 0;
                let totalTax = 0;

                $('.item-row').each(function() {
                    let qty = parseFloat($(this).find('.qty-input').val()) || 0;
                    let price = parseFloat($(this).find('.price-input').val()) || 0;
                    let taxRate = parseFloat($(this).find('.tax-input').val()) || 0;

                    let lineSubtotal = qty * price;
                    let lineTax = (lineSubtotal * taxRate) / 100;
                    let lineTotal = lineSubtotal + lineTax;

                    $(this).find('.line-total').text(lineTotal.toFixed(2));

                    subtotal += lineSubtotal;
                    totalTax += lineTax;
                });

                $('#sumSubtotal').text(subtotal.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#sumTax').text(totalTax.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#sumTotal').text((subtotal + totalTax).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }

            $('#addLine').click(function() {
                let newRow = `
                    <tr class="item-row group">
                        <td class="px-4 py-4 text-center text-slate-400 text-xs font-medium row-number">${rowCount + 1}</td>
                        <td class="px-4 py-4">
                            <input type="text" name="items[${rowCount}][sku]" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all font-medium text-slate-700 placeholder-slate-300" placeholder="SKU-000">
                        </td>
                        <td class="px-4 py-4">
                            <input type="text" name="items[${rowCount}][description]" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 placeholder-slate-300" placeholder="Brief description">
                        </td>
                        <td class="px-4 py-4">
                            <input type="text" name="items[${rowCount}][hs_code]" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 placeholder-slate-300" placeholder="0000.00">
                        </td>
                        <td class="px-4 py-4">
                            <input type="number" name="items[${rowCount}][quantity]" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-center font-bold text-slate-700 qty-input" value="1" min="1">
                        </td>
                        <td class="px-4 py-4">
                            <input type="number" name="items[${rowCount}][weight_kg]" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-center text-slate-500 weight-input" value="0" step="0.01">
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-1 relative">
                                <span class="absolute left-3 text-slate-400 text-xs font-bold">$</span>
                                <input type="number" name="items[${rowCount}][price]" class="w-full pl-7 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all font-semibold text-slate-700 price-input" value="0.00" step="0.01">
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-1 relative">
                                <input type="number" name="items[${rowCount}][tax_percentage]" class="w-full pr-7 pl-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 tax-input" value="5" step="0.1">
                                <span class="absolute right-3 text-slate-400 text-xs font-bold">%</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <span class="text-sm font-bold text-slate-900 line-total">0.00</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <button type="button" class="text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity remove-row p-2">
                                <i class="fa-solid fa-trash-can text-sm"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#itemsBody').append(newRow);
                rowCount++;
                updateRowNumbers();
            });

            $(document).on('click', '.remove-row', function() {
                if ($('.item-row').length > 1) {
                    $(this).closest('tr').remove();
                    updateRowNumbers();
                    updateCalculations();
                }
            });

            $(document).on('input', '.qty-input, .price-input, .tax-input', function() {
                updateCalculations();
            });

            function updateRowNumbers() {
                $('.item-row').each(function(index) {
                    $(this).find('.row-number').text(index + 1);
                });
            }

            $(document).on('click', '.remove-attachment', function() {
                let id = $(this).data('id');
                let $btn = $(this);
                if (confirm('Are you sure you want to delete this attachment?')) {
                    $.ajax({
                        url: `/logistics/purchase-orders/attachments/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                $btn.closest('.group').remove();
                            }
                        },
                        error: function(xhr) {
                            alert('Error: ' + (xhr.responseJSON?.message ||
                                'Could not delete attachment'));
                        }
                    });
                }
            });

            // File input handling
            $('#fileInput').change(function() {
                let container = $('#filePreviewContainer');
                container.empty();

                Array.from(this.files).forEach(file => {
                    let icon = file.type.includes('pdf') ? 'fa-file-pdf text-red-500' :
                        'fa-file-image text-blue-500';
                    let size = (file.size / 1024).toFixed(2) + ' KB';

                    let preview = `
                        <div class="bg-white border border-slate-200 rounded-lg p-3 flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-50 rounded flex items-center justify-center">
                                <i class="fa-solid ${icon} text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-700 truncate">${file.name}</p>
                                <p class="text-[10px] text-slate-400">${size}</p>
                            </div>
                        </div>
                    `;
                    container.append(preview);
                });
            });

            // Form submission
            $('#poForm').submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let $submitBtn = $(this).find('button[type="submit"]');
                let $progressContainer = $('#uploadProgressContainer');
                let $progressBar = $('#uploadProgressBar');
                let $percentageText = $('#uploadPercentage');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $submitBtn.prop('disabled', true).addClass(
                            'opacity-50 cursor-not-allowed');
                        $progressContainer.removeClass('hidden');
                        $progressBar.width('0%');
                        $percentageText.text('0%');
                    },
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function(evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = (evt.loaded / evt.total) * 100;
                                $progressBar.width(percentComplete + '%');
                                $percentageText.text(Math.round(percentComplete) + '%');

                                if (percentComplete >= 100) {
                                    $percentageText.text('Processing...');
                                }
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        if (response.success) {
                            $progressBar.addClass('bg-green-500');
                            setTimeout(() => {
                                window.location.href = response.redirect ||
                                    '{{ route('logistics.purchase-orders.index') }}';
                            }, 500);
                        }
                    },
                    error: function(xhr) {
                        $submitBtn.prop('disabled', false).removeClass(
                            'opacity-50 cursor-not-allowed');
                        $progressContainer.addClass('hidden');
                        alert('Error: ' + (xhr.responseJSON?.message ||
                            'Something went wrong'));
                    }
                });
            });
        });
    </script>
@endpush
