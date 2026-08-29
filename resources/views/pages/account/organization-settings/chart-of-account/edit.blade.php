@extends('layouts.auth')

@section('title', 'Chart of Accounts')

@section('content')
    <div class="mb-8 ">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.organization_settings') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mx-3">
            <!-- Header Section -->
            <div class="bg-gray-50 border-b border-gray-200 px-6 py-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">Edit Account</h2>
                        <p class="text-sm text-gray-500 mt-1">Update account information in your chart of accounts</p>
                    </div>
                    <a href="{{ get_index_url('account.organization-settings.account.index') }}"
                        class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors duration-200 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Accounts
                    </a>
                </div>
            </div>

            <!-- Form Section -->
            <div class="p-6">
                <form class="space-y-8" id="formSubmit"
                    action_url="{{ route('account.organization-settings.account.update', $account->id) }}"
                    back_url="{{ get_index_url('account.organization-settings.account.index') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <span
                                class="w-8 h-8 bg-[#0078C8] text-white rounded-full flex items-center justify-center text-sm mr-3">1</span>
                            Basic Information
                        </h3>
                        <div class="grid md:grid-cols-3 gap-5 pl-11">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Code <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="account_number" value="{{ $account->account_number }}" required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200"
                                    placeholder="e.g., 1000" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                                <input type="text" name="acc_no" value="{{ $account->acc_no }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200"
                                    placeholder="e.g., GB33BUKB20201555555555" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ $account->name }}" required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200"
                                    placeholder="e.g., Cash in Hand" />
                            </div>
                        </div>
                    </div>

                    <!-- Classification & Details Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <span
                                class="w-8 h-8 bg-[#0078C8] text-white rounded-full flex items-center justify-center text-sm mr-3">2</span>
                            Classification & Details
                        </h3>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5 pl-11">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parent Account</label>
                                <select name="parent_id"
                                    class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                    <option value="">None (Top Level)</option>
                                    @foreach ($parents as $parent)
                                        <option value="{{ $parent->id }}"
                                            {{ $parent->id == $account->parent_id ? 'selected' : '' }}>
                                            {{ $parent->account_number }} - {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select name="category"
                                    class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                    <option value="">Select Category</option>
                                    @foreach (['Assets', 'Liabilities', 'Equity', 'Expenses', 'Revenue', 'COGS'] as $cat)
                                        <option value="{{ $cat }}"
                                            {{ $cat == $account->category ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Type <span
                                        class="text-red-500">*</span></label>
                                <select name="type" required
                                    class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                    <option value="Account" {{ $account->type == 'Account' ? 'selected' : '' }}>Account
                                    </option>
                                    <option value="Group" {{ $account->type == 'Group' ? 'selected' : '' }}>Group</option>
                                </select>
                            </div>

                            <div class="md:col-span-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                                Effective posted opening balance: {{ numberFormat($openingBalance['amount']) }} {{ strtoupper($openingBalance['side'] ?? '') }}{{ $openingBalance['date'] ? ' as of '.$openingBalance['date'] : '' }}. Use the controlled Opening Balance Batch workflow for changes.
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Code</label>
                                <input type="text" name="tax_code" value="{{ $account->tax_code }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200"
                                    placeholder="e.g., TAX-01" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">System Account Type</label>
                                <select name="system_account_type" class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                    <option value="" {{ empty($account->system_account_type) ? 'selected' : '' }}>None (Standard Account)</option>
                                    <option value="ACCOUNTS_RECEIVABLE" {{ $account->system_account_type == 'ACCOUNTS_RECEIVABLE' ? 'selected' : '' }}>Accounts Receivable (ACCOUNTS_RECEIVABLE)</option>
                                    <option value="ACCOUNTS_PAYABLE" {{ $account->system_account_type == 'ACCOUNTS_PAYABLE' ? 'selected' : '' }}>Accounts Payable (ACCOUNTS_PAYABLE)</option>
                                    <option value="SALES_TAX" {{ $account->system_account_type == 'SALES_TAX' ? 'selected' : '' }}>Sales Tax (SALES_TAX)</option>
                                    <option value="PURCHASE_TAX" {{ $account->system_account_type == 'PURCHASE_TAX' ? 'selected' : '' }}>Purchase Tax (PURCHASE_TAX)</option>
                                    <option value="SALES_DISCOUNT" {{ $account->system_account_type == 'SALES_DISCOUNT' ? 'selected' : '' }}>Sales Discount (SALES_DISCOUNT)</option>
                                    <option value="PURCHASE_DISCOUNT" {{ $account->system_account_type == 'PURCHASE_DISCOUNT' ? 'selected' : '' }}>Purchase Discount (PURCHASE_DISCOUNT)</option>
                                    <option value="WAGES_PAYABLE" {{ $account->system_account_type == 'WAGES_PAYABLE' ? 'selected' : '' }}>Wages Payable (WAGES_PAYABLE)</option>
                                    <option value="OPENING_BALANCE_EQUITY" {{ $account->system_account_type == 'OPENING_BALANCE_EQUITY' ? 'selected' : '' }}>Opening Balance Equity (OPENING_BALANCE_EQUITY)</option>
                                    <option value="RETAINED_EARNINGS" {{ $account->system_account_type == 'RETAINED_EARNINGS' ? 'selected' : '' }}>Retained Earnings (RETAINED_EARNINGS)</option>
                                    <option value="EMPLOYEE_ADVANCES" {{ $account->system_account_type == 'EMPLOYEE_ADVANCES' ? 'selected' : '' }}>Employee Advances (EMPLOYEE_ADVANCES)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sub-Type</label>
                                <select name="sub_type" class="select2 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200">
                                    <option value="" {{ empty($account->sub_type) ? 'selected' : '' }}>Select Sub-Type</option>
                                    <option value="BANK" {{ $account->sub_type == 'BANK' ? 'selected' : '' }}>Bank / Cash (BANK)</option>
                                    <option value="CURRENT_ASSET" {{ $account->sub_type == 'CURRENT_ASSET' ? 'selected' : '' }}>Current Asset (CURRENT_ASSET)</option>
                                    <option value="RECEIVABLE" {{ $account->sub_type == 'RECEIVABLE' ? 'selected' : '' }}>Accounts Receivable (RECEIVABLE)</option>
                                    <option value="INVENTORY" {{ $account->sub_type == 'INVENTORY' ? 'selected' : '' }}>Inventory (INVENTORY)</option>
                                    <option value="FIXED_ASSET" {{ $account->sub_type == 'FIXED_ASSET' ? 'selected' : '' }}>Fixed Asset (FIXED_ASSET)</option>
                                    <option value="NON_CURRENT_ASSET" {{ $account->sub_type == 'NON_CURRENT_ASSET' ? 'selected' : '' }}>Non-current Asset (NON_CURRENT_ASSET)</option>
                                    <option value="CURRENT_LIABILITY" {{ $account->sub_type == 'CURRENT_LIABILITY' ? 'selected' : '' }}>Current Liability (CURRENT_LIABILITY)</option>
                                    <option value="NON_CURRENT_LIABILITY" {{ $account->sub_type == 'NON_CURRENT_LIABILITY' ? 'selected' : '' }}>Non-current Liability (NON_CURRENT_LIABILITY)</option>
                                    <option value="EQUITY" {{ $account->sub_type == 'EQUITY' ? 'selected' : '' }}>Equity (EQUITY)</option>
                                    <option value="REVENUE" {{ $account->sub_type == 'REVENUE' ? 'selected' : '' }}>Revenue (REVENUE)</option>
                                    <option value="DIRECT_COST" {{ $account->sub_type == 'DIRECT_COST' ? 'selected' : '' }}>Direct Cost / COGS (DIRECT_COST)</option>
                                    <option value="OPERATING_EXPENSE" {{ $account->sub_type == 'OPERATING_EXPENSE' ? 'selected' : '' }}>Operating Expense (OPERATING_EXPENSE)</option>
                                    <option value="DEPRECIATION" {{ $account->sub_type == 'DEPRECIATION' ? 'selected' : '' }}>Depreciation / Amortization (DEPRECIATION)</option>
                                </select>
                            </div>

                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" rows="3"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all duration-200"
                                    placeholder="Brief description of the account's purpose...">{{ $account->description }}</textarea>
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
                                Update Account
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
    const systemTypeMap = {
        'ACCOUNTS_RECEIVABLE': { sub_type: 'RECEIVABLE', category: 'Assets' },
        'ACCOUNTS_PAYABLE': { sub_type: 'CURRENT_LIABILITY', category: 'Liabilities' },
        'SALES_TAX': { sub_type: 'CURRENT_LIABILITY', category: 'Liabilities' },
        'PURCHASE_TAX': { sub_type: 'CURRENT_ASSET', category: 'Assets' },
        'SALES_DISCOUNT': { sub_type: 'REVENUE', category: 'Revenue' },
        'PURCHASE_DISCOUNT': { sub_type: 'DIRECT_COST', category: 'COGS' },
        'WAGES_PAYABLE': { sub_type: 'CURRENT_LIABILITY', category: 'Liabilities' },
        'OPENING_BALANCE_EQUITY': { sub_type: 'EQUITY', category: 'Equity' },
        'RETAINED_EARNINGS': { sub_type: 'EQUITY', category: 'Equity' },
        'EMPLOYEE_ADVANCES': { sub_type: 'CURRENT_ASSET', category: 'Assets' }
    };

    const subTypeMap = {
        'BANK': 'Assets',
        'CURRENT_ASSET': 'Assets',
        'RECEIVABLE': 'Assets',
        'INVENTORY': 'Assets',
        'FIXED_ASSET': 'Assets',
        'NON_CURRENT_ASSET': 'Assets',
        'CURRENT_LIABILITY': 'Liabilities',
        'NON_CURRENT_LIABILITY': 'Liabilities',
        'EQUITY': 'Equity',
        'REVENUE': 'Revenue',
        'DIRECT_COST': 'COGS',
        'OPERATING_EXPENSE': 'Expenses',
        'DEPRECIATION': 'Expenses'
    };

    $('select[name="system_account_type"]').on('change', function() {
        const val = $(this).val();
        if (val && systemTypeMap[val]) {
            const map = systemTypeMap[val];
            $('select[name="sub_type"]').val(map.sub_type).trigger('change');
            $('select[name="category"]').val(map.category).trigger('change');
        }
    });

    $('select[name="sub_type"]').on('change', function() {
        const val = $(this).val();
        if (val && subTypeMap[val]) {
            $('select[name="category"]').val(subTypeMap[val]).trigger('change');
        }
    });

    function toggleOpeningBalanceFields() {
        const isGroup = $('select[name="type"]').val() === 'Group';
        $('.opening-balance-field').toggle(!isGroup);
        $('.opening-balance-field :input').prop('disabled', isGroup);
    }

    $('select[name="type"]').on('change', toggleOpeningBalanceFields);
    toggleOpeningBalanceFields();
});
</script>
@endpush
