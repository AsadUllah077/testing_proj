@extends('layouts.auth')

@section('title', 'Edit Accounting Period')

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

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden m-3">

            <!-- Header Section -->
            <div class="bg-gray-50 border-b border-gray-200 px-6 py-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">Edit Accounting Period</h2>
                        <p class="text-sm text-gray-500 mt-1">Edit accounting period</p>
                    </div>
                    <a href="{{ get_index_url('account.organization-settings.accounting-period.index') }}"
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

                {{-- IMPORTANT: EDIT form must submit to update --}}
                <form method="POST" id="formSubmit" class="space-y-8"
                    action_url="{{ route('account.organization-settings.accounting-period.update', $accounting_period->id) }}"
                    back_url="{{ get_index_url('account.organization-settings.accounting-period.index') }}">
                    @csrf
                    @method('PUT')

                    <!-- Fiscal Year Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <span
                                class="w-8 h-8 bg-[#0078C8] text-white rounded-full flex items-center justify-center text-sm mr-3">1</span>
                            Fiscal Year
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pl-11">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="name" required
                                    value="{{ old('name', $accounting_period->name) }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date <span
                                        class="text-red-500">*</span></label>
                                <input class="date" type="date" name="start_date" required
                                    value="{{ old('start_date', $accounting_period->start_date) }}"
                                    class="date w-full border rounded-lg px-4 py-2.5 text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Date <span
                                        class="text-red-500">*</span></label>
                                <input class="date" type="date" name="end_date" required
                                    value="{{ old('end_date', $accounting_period->end_date) }}"
                                    class="date w-full border rounded-lg px-4 py-2.5 text-sm">
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
                                    class="select2 w-full border rounded-lg px-4 py-2.5 text-sm">

                                    <option value="">Select tax for sale</option>

                                    @foreach ($taxRate['sales_tax'] ?? [] as $sale_tax)
                                        <option value="{{ $sale_tax->id }}" data-code="{{ $sale_tax->code }}"
                                            {{ $accounting_period->tax_rate_id == $sale_tax->id ? 'selected' : '' }}>
                                            {{ $sale_tax->name }} ({{ $sale_tax->tax_rate }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax ID Number</label>
                                <input type="text" disabled id="tax_code"
                                    value="{{ $accounting_period->taxRate->code ?? '' }}"
                                    class="w-full border rounded-lg px-4 py-2.5 text-sm bg-gray-50">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Period</label>
                                <select name="tax_period" class="select2 w-full border rounded-lg px-4 py-2.5 text-sm">
                                    <option value="">Select tax period</option>

                                    @foreach (config('default.tax_period') as $key => $period)
                                        <option value="{{ $key }}"
                                            {{ $accounting_period->tax_period == $key ? 'selected' : '' }}>
                                            {{ $period }}
                                        </option>
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

                            <!-- Sales Default -->
                            <div class="grid md:grid-cols-2 gap-6">

                                <div>
                                    <label class="block text-sm font-medium">For Sales</label>
                                    <select name="default_sale_tax_rate_id"
                                        class="select2 w-full border rounded-lg px-4 py-2.5 text-sm">

                                        <option value="">Select tax for default sale</option>

                                        @foreach ($taxRate['default_sale_tax'] ?? [] as $default_sale_tax)
                                            <option value="{{ $default_sale_tax->id }}"
                                                {{ $accounting_period->default_sale_tax_rate_id == $default_sale_tax->id ? 'selected' : '' }}>
                                                {{ $default_sale_tax->name }} ({{ $default_sale_tax->tax_rate }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex items-center pt-8">
                                    <p class="text-sm text-gray-500">Includes invoices, quotes, credit notes, receive money
                                        items</p>
                                </div>
                            </div>

                            <!-- Purchase Default -->
                            <div class="grid md:grid-cols-2 gap-6">

                                <div>
                                    <label class="block text-sm font-medium">For Purchase</label>
                                    <select name="default_purchase_tax_rate_id"
                                        class="select2 w-full border rounded-lg px-4 py-2.5 text-sm">

                                        <option value="">Select tax for default purchase</option>

                                        @foreach ($taxRate['default_purchase_tax'] ?? [] as $default_purchase_tax)
                                            <option value="{{ $default_purchase_tax->id }}"
                                                {{ $accounting_period->default_purchase_tax_rate_id == $default_purchase_tax->id ? 'selected' : '' }}>
                                                {{ $default_purchase_tax->name }} ({{ $default_purchase_tax->tax_rate }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex items-center pt-8">
                                    <p class="text-sm text-gray-500">Includes bills, purchase orders, credit notes, spend
                                        money items</p>
                                </div>

                            </div>

                        </div>
                    </div>


                    <!-- Lock Dates Section -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-medium flex items-center">
                                <span
                                    class="w-8 h-8 bg-[#0078C8] text-white rounded-full flex items-center justify-center text-sm mr-3">4</span>
                                Lock Dates
                            </h3>
                            <span
                                class="inline-flex items-center w-5 h-5 rounded-full bg-blue-100 text-[#0078C8] text-xs cursor-help"
                                title="Lock dates prevent changes in a specific period.">?</span>
                        </div>

                        <div class="pl-11">
                            <p class="text-sm text-gray-600 mb-4">
                                Lock dates stop data from being changed for a specific period. You can change these at any
                                time.
                            </p>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Allow these users (Advisors) to make changes when period is locked
                                </label>

                                <select multiple name="user_id[]"
                                    class="select2 w-full border rounded-lg px-4 py-2.5 text-sm">

                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"
                                            {{ in_array($user->id, $period_lock_users) ? 'selected' : '' }}>
                                            {{ $user->full_name }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>

                        </div>
                    </div>


                    <!-- Buttons -->
                    <div class="flex justify-end gap-3 pt-6 border-t">

                        <a href="{{ get_index_url('account.organization-settings.accounting-period.index') }}"
                            class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 text-sm rounded-lg">
                            Cancel
                        </a>

                        <button type="submit"
                            class="px-6 py-2.5 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0]">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Update
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
                let taxCode = $(this).find(':selected').data('code');
                $('#tax_code').val(taxCode ?? '');
            });

        });
    </script>
@endpush
