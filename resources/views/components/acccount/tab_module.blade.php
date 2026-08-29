<div class="p-3 mb-2">
    <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
        <x-sub_module_tab url="{{ route('account.sale-invoice.index') }}" name="Sale Invoice"
            active="{{ Request::routeIs('account.sale-invoice.*') }}" />
        <x-sub_module_tab url="" name="Sale Transaction" active="" />
    </div>
</div>
