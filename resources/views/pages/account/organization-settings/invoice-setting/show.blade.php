@extends('layouts.auth')

@section('title', 'Invoice Settings')

@section('content')
    <div class="mb-8">
        <div class="p-3">
            <div class="mx-auto">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Invoice Settings</h2>
                    <a href="{{ route('account.organization-settings.invoice-templates.index') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded hover:bg-[#006bb0] transition-all">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>

                <div class="bg-white border border-black/10 rounded-lg mt-4 p-6">

                    <!-- Header with Options Button -->
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900">{{ $template->name ?? 'Standard' }}</h2>
                        <div class="relative">
                            <button onclick="toggleOptionsDropdown()" type="button"
                                class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded hover:bg-[#f0f8ff] transition-all flex items-center gap-2">
                                Options
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="optionsDropdown"
                                class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-20">
                                <div class="py-1">
                                    <a href="{{ route('account.organization-settings.invoice-templates.edit', $template->id) }}"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit Settings</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                        <!-- LEFT COLUMN -->
                        <div class="lg:col-span-2 space-y-4">
                            <!-- Page Settings -->
                            <div class="text-sm">
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-gray-700">
                                    <span><strong>Page:</strong> {{ $template->page_size ?? 'A4' }}</span>
                                    <span><strong>Margins Top:</strong> {{ $template->top_margin ?? '0.5' }}
                                        {{ $template->measures_in }}</span>
                                    <span><strong>Bottom:</strong> {{ $template->bottom_margin ?? '0.4' }}
                                        {{ $template->measures_in }}</span>
                                    <span><strong>Address Padding:</strong> {{ $template->address_padding ?? '0.4' }}
                                        {{ $template->measures_in }}</span>
                                </div>
                                <div class="text-gray-700 mt-1">
                                    <strong>Font:</strong> {{ $template->font ?? 'Arial' }},
                                    {{ $template->font_size ?? '9pt' }}
                                </div>
                            </div>

                            <!-- Show Section -->
                            <div class="mt-6">
                                <h3 class="text-base font-semibold text-gray-900 mb-3">Show:</h3>
                                <div class="grid grid-cols-2 gap-x-8 gap-y-2">
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        @if ($template->show_column_heading)
                                            <li>• Column headings</li>
                                        @endif
                                        @if ($template->show_item_code)
                                            <li>• Item code</li>
                                        @endif
                                        @if ($template->show_invoice_qty)
                                            <li>• Unit Price & quantity</li>
                                        @endif
                                        @if ($template->show_payment_advance_cutaway)
                                            <li>• Payment advice cut-away</li>
                                        @endif
                                        @if ($template->show_registered_columns)
                                            <li>• Registered Columns</li>
                                        @endif
                                        @if ($template->show_logo)
                                            <li>• Logo</li>
                                        @endif
                                        @if ($template->show_hs)
                                            <li>• Show HS</li>
                                        @endif
                                        @if ($template->hide_discount)
                                            <li>• Hide discount</li>
                                        @endif
                                        @if ($template->show_contact_account_number)
                                            <li>• Contact Account Number</li>
                                        @endif
                                    </ul>
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        <li>• Logo align: {{ ucfirst($template->logo_alignment ?? 'Right') }}</li>
                                        <li>• Header style: {{ ucfirst($template->show_head_as ?? 'Logo') }}</li>
                                        <li>• Measure in: {{ strtoupper($template->measures_in ?? 'cm') }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Headings -->
                            <div class="mt-6">
                                <p class="text-sm text-gray-700">
                                    <strong>Headings:</strong>
                                    {{ implode(
                                        ', ',
                                        array_filter([
                                            $template->draft_invoice_title,
                                            $template->approved_invoice_title,
                                            $template->overdue_invoice_title,
                                            $template->credit_note_title,
                                            $template->statement_title,
                                            $template->draft_purchase_order_title,
                                            $template->purchase_order_title,
                                            $template->draft_quote_title,
                                            $template->quote_title,
                                            $template->remittance_advice_title,
                                            $template->receipt_title,
                                        ]),
                                    ) }}
                                </p>
                            </div>

                            <!-- Payment Service -->
                            <div class="mt-4">
                                <p class="text-sm text-gray-700">
                                    <strong>Payment Services:</strong>
                                    {{ $template->payment_credit_card ?? 'N/A' }},
                                    {{ $template->payment_paypal ?? 'N/A' }},
                                    {{ $template->payment_bank ?? 'N/A' }}
                                </p>
                            </div>

                            <!-- Terms & Payment Advice -->
                            <div class="mt-6">
                                <h3 class="text-sm font-semibold text-gray-900 mb-2">
                                    Terms & Payment Advice (Invoice & Statement):
                                </h3>
                                <div class="text-sm text-gray-700 space-y-1">
                                    {!! nl2br(e($template->terms_payment_advice ?? 'No payment terms specified.')) !!}

                                    <p class="mt-3"><strong>The Terms (Quotes):</strong></p>
                                    {!! nl2br(e($template->terms_quotes ?? 'No quote terms available.')) !!}
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN -->
                        <div>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <h3 class="text-base font-semibold text-gray-900 mb-4">Contact Details</h3>

                                <!-- Company Logo -->
                                <div class="mb-4 flex justify-center">
                                    <div
                                        class="w-32 h-32 border-gray-300 rounded-lg flex items-center justify-center bg-white">
                                        @if ($template->show_logo && $template->logo)
                                            <img src="{{ asset('storage/' . $template->logo) }}" alt="Template Logo"
                                                class="max-w-full max-h-full object-contain">
                                        @elseif ($template->show_logo)
                                            <img src="{{ asset('logo.png') }}" alt="Default Logo">
                                        @else
                                            <span class="text-xs text-gray-500">Logo hidden</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Company Details -->
                                <div class="text-sm text-gray-700 space-y-1">
                                    {!! nl2br(e($template->contact_details ?? 'Company details not provided.')) !!}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleOptionsDropdown() {
            const dropdown = document.getElementById('optionsDropdown');
            dropdown.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('optionsDropdown');
            const button = event.target.closest('button[onclick="toggleOptionsDropdown()"]');
            if (!button && dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
@endpush
