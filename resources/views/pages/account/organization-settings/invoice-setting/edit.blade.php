@extends('layouts.auth')

@section('title', 'Edit Invoice Template')

@section('content')
    <div class="mb-8">
        <div class="p-3">
            <div class="">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Edit Invoice Template</h2>
                    <a href="{{ route('account.organization-settings.invoice-templates.index') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded hover:bg-[#006bb0] transition-all">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>

                <div class="bg-white border border-gray-300 rounded-lg shadow-sm mt-4">
                    <form id="invoiceSettingsForm"
                        action="{{ route('account.organization-settings.invoice-templates.update', $template->id) }}"
                        method="POST" enctype="multipart/form-data" class="p-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                            {{-- LEFT COLUMN --}}
                            <div class="space-y-5">
                                {{-- Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                    <input type="text" name="name" value="{{ old('name', $template->name) }}"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                </div>

                                {{-- Page Size --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Page Size</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="page_size" value="A4"
                                                {{ $template->page_size == 'A4' ? 'checked' : '' }}
                                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                                            <span class="ml-2 text-sm text-gray-700">A4</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="page_size" value="US Letter"
                                                {{ $template->page_size == 'US Letter' ? 'checked' : '' }}
                                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                                            <span class="ml-2 text-sm text-gray-700">US Letter</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Top Margin --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Top Margin</label>
                                    <input type="text" name="top_margin"
                                        value="{{ old('top_margin', $template->top_margin) }}"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none">
                                </div>

                                {{-- Bottom Margin --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bottom Margin</label>
                                    <input type="text" name="bottom_margin"
                                        value="{{ old('bottom_margin', $template->bottom_margin) }}"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none">
                                </div>

                                {{-- Address Padding --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address padding</label>
                                    <input type="text" name="address_padding"
                                        value="{{ old('address_padding', $template->address_padding) }}"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none">
                                </div>

                                {{-- Font --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Font</label>
                                    <select name="font"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none bg-white">
                                        <option {{ $template->font == 'Arial' ? 'selected' : '' }}>Arial</option>
                                        <option {{ $template->font == 'Times New Roman' ? 'selected' : '' }}>Times New Roman
                                        </option>
                                        <option {{ $template->font == 'Helvetica' ? 'selected' : '' }}>Helvetica</option>
                                        <option {{ $template->font == 'Courier' ? 'selected' : '' }}>Courier</option>
                                    </select>
                                </div>

                                {{-- Font Size --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Font Size</label>
                                    <select name="font_size"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none bg-white">
                                        @foreach (['8', '9', '10', '11', '12'] as $size)
                                            <option {{ $template->font_size == $size ? 'selected' : '' }}>
                                                {{ $size }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Dynamic Text Inputs --}}
                                @foreach ([
            'draft_invoice_title' => 'Draft invoice title',
            'approved_invoice_title' => 'Approved invoice title',
            'overdue_invoice_title' => 'Overdue invoice title',
            'credit_note_title' => 'Credit note title',
            'statement_title' => 'Statement title',
            'draft_purchase_order_title' => 'Draft Purchase order title',
            'purchase_order_title' => 'Purchase order title',
            'draft_quote_title' => 'Draft Quote title',
            'quote_title' => 'Quote title',
            'remittance_advice_title' => 'Remittance Advice title',
            'receipt_title' => 'Receipt title',
        ] as $field => $label)
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
                                        <input type="text" name="{{ $field }}"
                                            value="{{ old($field, $template->$field) }}"
                                            class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none">
                                    </div>
                                @endforeach
                            </div>

                            {{-- MIDDLE COLUMN --}}
                            <div class="space-y-5">
                                {{-- Measures In --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Measures in:</label>
                                    @foreach (['in', 'cm', 'inches'] as $unit)
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="measures_in" value="{{ $unit }}"
                                                {{ $template->measures_in == $unit ? 'checked' : '' }}
                                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                                            <span class="ml-2 text-sm text-gray-700">{{ $unit }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Checkbox options --}}
                                <div class="space-y-3">
                                    @foreach ([
            'show_tax_numbers' => 'Show Tax Numbers',
            'show_column_heading' => 'Show Column Heading',
            'show_item_code' => 'Show Item Code',
            'show_invoice_qty' => 'Show invoice & quantity',
            'show_payment_advance_cutaway' => 'Show payment advice cut-away',
            'show_tax_column' => 'Show tax Column',
            'show_registered_columns' => 'Show registered Columns',
            'show_logo' => 'Show logo',
            'show_hs' => 'Show HS',
            'hide_discount' => 'Hide discount',
            'show_contact_account_number' => 'Show Contact Account Number',
        ] as $field => $label)
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" name="{{ $field }}"
                                                {{ $template->$field ? 'checked' : '' }}
                                                class="w-4 h-4 text-[#0078C8] rounded focus:ring-[#0078C8]">
                                            <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Payment Services --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Services (Credit
                                        Card)</label>
                                    <input type="text" name="payment_credit_card"
                                        value="{{ old('payment_credit_card', $template->payment_credit_card) }}"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Services
                                        (Paypal)</label>
                                    <input type="text" name="payment_paypal"
                                        value="{{ old('payment_paypal', $template->payment_paypal) }}"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Services (Bank
                                        Payments)</label>
                                    <input type="text" name="payment_bank"
                                        value="{{ old('payment_bank', $template->payment_bank) }}"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm outline-none">
                                </div>

                                {{-- Terms --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Terms & Payment
                                        Advice</label>
                                    <textarea name="terms_payment_advice" rows="6"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none resize-none">{{ old('terms_payment_advice', $template->terms_payment_advice) }}</textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Terms (Quotes)</label>
                                    <textarea name="terms_quotes" rows="6"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none resize-none">{{ old('terms_quotes', $template->terms_quotes) }}</textarea>
                                </div>
                            </div>

                            {{-- RIGHT COLUMN --}}
                            <div class="space-y-5">
                                {{-- Logo Alignment --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Logo Alignment</label>
                                    @foreach (['left', 'center', 'right'] as $align)
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="logo_alignment" value="{{ $align }}"
                                                {{ $template->logo_alignment == $align ? 'checked' : '' }}
                                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                                            <span class="ml-2 text-sm text-gray-700 capitalize">{{ $align }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Show Head As --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Show Head as</label>
                                    @foreach (['logo', 'exclusive'] as $option)
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="show_head_as" value="{{ $option }}"
                                                {{ $template->show_head_as == $option ? 'checked' : '' }}
                                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                                            <span class="ml-2 text-sm text-gray-700 capitalize">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Is Default --}}
                                <div>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="is_default"
                                            {{ $template->is_default ? 'checked' : '' }}
                                            class="w-4 h-4 text-[#0078C8] rounded focus:ring-[#0078C8]">
                                        <span class="ml-2 text-sm text-gray-700">Set as Default Template</span>
                                    </label>
                                </div>

                                {{-- Logo Upload --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                                    @if ($template->logo)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $template->logo) }}" alt="Current Logo"
                                                class="w-16 h-16 object-contain border rounded">
                                            <p class="text-xs text-gray-500 mt-1">Current logo</p>
                                        </div>
                                    @endif
                                    <input type="file" name="logo" accept="image/*"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20">
                                    <p class="text-xs text-gray-500 mt-1">Upload a new logo image (PNG, JPG, etc.) - leave
                                        empty to keep current</p>
                                </div>

                                {{-- Contact Details --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Details</label>
                                    <textarea name="contact_details" rows="6"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none resize-none">{{ old('contact_details', $template->contact_details) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                            <a href="{{ route('account.organization-settings.invoice-templates.index') }}"
                                class="px-6 py-2 border-2 bg-[#006bb0]/10 border-[#006bb0] text-[#006bb0] text-sm font-medium rounded-md hover:bg-[#006bb0] hover:text-white transition-all">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
