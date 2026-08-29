@extends('layouts.auth')

@section('title', 'Create Purchase Order')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-file-circle-plus text-[#0078C8] text-xl"></i>
                    <h1 class="text-gray-900 text-xl font-normal">New Purchase Order <span
                            class="text-gray-400 text-sm ml-2">#{{ $po_number }}</span></h1>
                </div>
                <x-common.back-button url="{{ route('logistics.purchase-orders.index') }}" />
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <form method="POST" action_url="{{ route('logistics.purchase-orders.store') }}"
                    back_url="{{ route('logistics.purchase-orders.index') }}" id="formSubmit" class="p-6 space-y-6"
                    id="poForm">
                    @csrf
                    <input type="hidden" name="po_number" value="{{ $po_number }}">

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
                                                <option value="{{ $supplier->id }}">{{ $supplier->company_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Order
                                            Date</label>
                                        <input type="date" name="order_date" value="{{ date('Y-m-d') }}"
                                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Expected
                                            Delivery Date</label>
                                        <input type="date" name="expected_delivery_date"
                                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-semibold text-slate-500 uppercase mb-2">Currency</label>
                                        <select name="currency"
                                            class="select2 w-full border-slate-200 rounded-lg text-sm focus:ring-[#0078C8]">
                                            <option value="USD" selected>USD - US Dollar</option>
                                            <option value="AED">AED - UAE Dirham</option>
                                            <option value="PKR">PKR - Pakistan Rupee</option>
                                            <option value="EUR">EUR - Euro</option>
                                            <option value="GBP">GBP - British Pound</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Payment
                                            Terms</label>
                                        <select name="payment_terms"
                                            class="select2 w-full border-slate-200 rounded-lg text-sm focus:ring-[#0078C8]">
                                            <option value="Net 30" selected>Net 30</option>
                                            <option value="Net 60">Net 60</option>
                                            <option value="Due on Receipt">Due on Receipt</option>
                                            <option value="Advanced">Advanced</option>
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
                                            {{-- Initial Row --}}
                                            <tr class="item-row group">
                                                <td
                                                    class="px-4 py-4 text-center text-slate-400 text-xs font-medium row-number">
                                                    1</td>
                                                <td class="px-4 py-4">
                                                    <input type="text" name="items[0][sku]"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all font-medium text-slate-700 placeholder-slate-300"
                                                        placeholder="SKU-000">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <input type="text" name="items[0][description]"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 placeholder-slate-300"
                                                        placeholder="Brief description">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <input type="text" name="items[0][hs_code]"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 placeholder-slate-300"
                                                        placeholder="0000.00">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <input type="number" name="items[0][quantity]"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-center font-bold text-slate-700 qty-input"
                                                        value="1" min="1">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <input type="number" name="items[0][weight_kg]"
                                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-center text-slate-500 weight-input"
                                                        value="0" step="0.01">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center gap-1 relative">
                                                        <span
                                                            class="absolute left-3 text-slate-400 text-xs font-bold">$</span>
                                                        <input type="number" name="items[0][price]"
                                                            class="w-full pl-7 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all font-semibold text-slate-700 price-input"
                                                            value="0.00" step="0.01">
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center gap-1 relative">
                                                        <input type="number" name="items[0][tax_percentage]"
                                                            class="w-full pr-7 pl-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] outline-none transition-all text-slate-500 tax-input"
                                                            value="5" step="0.1">
                                                        <span
                                                            class="absolute right-3 text-slate-400 text-xs font-bold">%</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4 text-right">
                                                    <span class="text-sm font-bold text-slate-900 line-total">0.00</span>
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <button type="button"
                                                        class="text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity remove-row p-2">
                                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                                    </button>
                                                </td>
                                            </tr>
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
                                            <span class="text-slate-900 font-semibold" id="sumSubtotal">0.00</span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-slate-500">Tax</span>
                                            <span class="text-slate-900 font-semibold" id="sumTax">0.00</span>
                                        </div>
                                        <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                                            <span class="text-base font-bold text-slate-900">Total</span>
                                            <span class="text-xl font-black text-[#0078C8]" id="sumTotal">0.00</span>
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
                                        class="relative group h-32 border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center gap-2 hover:border-[#0078C8] hover:bg-blue-50/30 transition-all cursor-pointer">
                                        <input type="file" name="attachments[]" id="fileInput" multiple
                                            class="absolute inset-0 opacity-0 cursor-pointer">
                                        <div
                                            class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center group-hover:bg-[#0078C8] group-hover:text-white transition-colors">
                                            <i class="fa-solid fa-cloud-arrow-up text-lg"></i>
                                        </div>
                                        <p class="text-xs font-medium text-slate-500">Click or drag files to upload quotes
                                            or
                                            specifications</p>
                                    </div>
                                    <div id="filePreviewContainer"
                                        class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        {{-- Previews will appear here --}}
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
                            SAVE PURCHASE ORDER
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let rowCount = 1;

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

                $('#sumSubtotal').text(subtotal.toFixed(2));
                $('#sumTax').text(totalTax.toFixed(2));
                $('#sumTotal').text((subtotal + totalTax).toFixed(2));
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

            // File input handling
            $('#fileInput').change(function() {
                let container = $('#filePreviewContainer');
                container.empty();

                Array.from(this.files).forEach(file => {
                    let reader = new FileReader();
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
                                window.location.href = response.redirect;
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
