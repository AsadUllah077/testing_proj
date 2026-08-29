@extends('layouts.auth')

@section('title', 'Accounting Period')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.organization_settings') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-lg  border border-gray-200 overflow-hidden m-3">

            <!-- Header Section -->
            <div class="bg-gray-50 border-b border-gray-200 px-6 py-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">Create Accounting Period</h2>
                        <p class="text-sm text-gray-500 mt-1">Create a new accounting period</p>
                    </div>
                    <a href="{{ route('account.organization-settings.accounting-period.index') }}"
                        class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors duration-200 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Accounts
                    </a>
                </div>
            </div>

            <div class="p-6">
                <form method="post" id="formSubmit" class="space-y-8"
                    action_url="{{ route('account.organization-settings.accounting-period.store') }}"
                    back_url="{{ route('account.organization-settings.accounting-period.index') }}">
                    @csrf

                    <!-- Fiscal Year Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <span
                                class="w-8 h-8 bg-[#0078C8] text-white rounded-full flex items-center justify-center text-sm mr-3">1</span>
                            Fiscal Year
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pl-11">
                            @php
                                $currentYear = date('Y');
                                $nextYear = $currentYear + 1;
                            @endphp
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" required
                                    value="FY - {{ $currentYear }}-{{ $nextYear }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="start_date" id="start_date" required
                                    class="date w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Date <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="end_date" id="end_date" required
                                    class="date w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200" />
                            </div>
                        </div>
                    </div>

                    <!-- Sales Tax Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <span
                                class="w-8 h-8 bg-[#0078C8] text-white rounded-full flex items-center justify-center text-sm mr-3">2</span>
                            Sales Tax
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pl-11">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax</label>
                                <select id="sale_tax_select" name="tax_rate_id"
                                    class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                    <option value="">Select tax for sale</option>
                                    @if (isset($taxRate['sales_tax']))
                                        @foreach ($taxRate['sales_tax'] as $sale_tax)
                                            <option value="{{ $sale_tax->id }}" data-code="{{ $sale_tax->code }}">
                                                {{ $sale_tax->name . ' (' . $sale_tax->tax_rate . ')' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax ID Number</label>
                                <input type="text" disabled id="tax_code"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200 bg-gray-50 cursor-not-allowed" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Period</label>
                                <select name="tax_period"
                                    class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                    <option value="">Select tax period</option>
                                    @foreach (config('default.tax_period') as $key => $period)
                                        <option value="{{ $key }}">{{ $period }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tax Defaults Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <span
                                class="w-8 h-8 bg-[#0078C8] text-white rounded-full flex items-center justify-center text-sm mr-3">3</span>
                            Tax Defaults
                        </h3>
                        <div class="space-y-5 pl-11">
                            <div class="grid md:grid-cols-2 gap-6 items-start">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">For Sales</label>
                                    <select name="default_sale_tax_rate_id"
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                        <option value="">Select tax for default sale</option>
                                        @if (isset($taxRate['default_sale_tax']))
                                            @foreach ($taxRate['default_sale_tax'] as $default_sale_tax)
                                                <option value="{{ $default_sale_tax->id }}"
                                                    data-code="{{ $default_sale_tax->code }}">
                                                    {{ $default_sale_tax->name . ' (' . $default_sale_tax->tax_rate . ')' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="flex items-center h-full pt-8">
                                    <p class="text-sm text-gray-500">Includes invoices, quotes, credit notes and receive
                                        money items</p>
                                </div>
                            </div>
                            <div class="grid md:grid-cols-2 gap-6 items-start">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">For Purchase</label>
                                    <select name="default_purchase_tax_rate_id"
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                        <option value="">Select tax for default purchase</option>
                                        @if (isset($taxRate['default_purchase_tax']))
                                            @foreach ($taxRate['default_purchase_tax'] as $default_purchase_tax)
                                                <option value="{{ $default_purchase_tax->id }}"
                                                    data-code="{{ $default_purchase_tax->code }}">
                                                    {{ $default_purchase_tax->name . ' (' . $default_purchase_tax->tax_rate . ')' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="flex items-center h-full pt-8">
                                    <p class="text-sm text-gray-500">Includes bills, purchase orders, credit notes and spend
                                        money items</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lock Dates Section -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <span
                                    class="w-8 h-8 bg-[#0078C8] text-white rounded-full flex items-center justify-center text-sm mr-3">4</span>
                                Lock Dates
                            </h3>
                            <span
                                class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-[#0078C8] text-xs cursor-help"
                                title="Lock dates stop data from being changed for a specific period.">?</span>
                        </div>
                        <div class="pl-11">
                            <p class="text-sm text-gray-600 mb-4">Lock dates stop data from being changed for a specific
                                period. You can change these at any time.</p>
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Allow these users
                                        (Advisors)
                                        to make changes when period is locked</label>
                                    <select multiple name="user_id[]"
                                        class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                        <option value="">Select advisor users</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <button type="reset"
                            class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all duration-200">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-all duration-200 shadow-sm hover:shadow-md">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Save
                            </span>
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
            $('#sale_tax_select').on('change', function() {
                let taxRate = $(this).val();
                let taxCode = $(this).find(':selected').data('code');

                if (taxRate !== "") {
                    $('#tax_code').val(taxCode);
                } else {
                    $('#tax_code').val('');
                }
            });
        });
    </script>
@endpush
