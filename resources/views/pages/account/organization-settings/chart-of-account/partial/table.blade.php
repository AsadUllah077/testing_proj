<div class="overflow-x-auto w-full">
    <table class="min-w-full divide-y divide-gray-200 text-sm font-normal">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                @if($currentTab !== 'deleted_history')
                    <th class="py-3 px-4 text-left w-10">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] w-4 h-4 cursor-pointer">
                    </th>
                @endif
                <th class="py-3 px-4 text-left font-semibold text-gray-700 w-24">
                    @if($currentTab === 'deleted_history')
                        Code
                    @else
                        <x-sortable column="account_number" label="Code" />
                    @endif
                </th>
                <th class="py-3 px-4 text-left font-semibold text-gray-700">
                    @if($currentTab === 'deleted_history')
                        Name
                    @else
                        <x-sortable column="name" label="Name" />
                    @endif
                </th>
                <th class="py-3 px-4 text-left font-semibold text-gray-700 w-36">
                    @if($currentTab === 'deleted_history')
                        Type
                    @else
                        <x-sortable column="category" label="Type" />
                    @endif
                </th>
                @if($currentTab === 'deleted_history')
                    <th class="py-3 px-4 text-left font-semibold text-gray-700 w-48">Deleted By</th>
                    <th class="py-3 px-4 text-right font-semibold text-gray-700 w-48">Deleted Date</th>
                @else
                    <th class="py-3 px-4 text-left font-semibold text-gray-700 w-48">
                        <x-sortable column="tax_code" label="Tax Rate" />
                    </th>
                    <th class="py-3 px-4 text-right font-semibold text-gray-700 w-32">
                        YTD
                    </th>
                    <th class="py-3 px-4 text-right font-semibold text-gray-700 w-20">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($accounts as $account)
                @if($currentTab === 'deleted_history')
                    @php
                        $oldVals = $account->changes['old'] ?? [];
                        $deletedBy = 'System';
                        if ($account->employee_id) {
                            $deletedBy = $account->employee?->full_name;
                        } elseif ($account->user_id) {
                            $deletedBy = ($account->user?->first_name ?? '') . ' ' . ($account->user?->last_name ?? '');
                        }
                    @endphp
                    <tr class="hover:bg-red-50/20 transition-colors">
                        <td class="py-3.5 px-4 font-semibold text-gray-600">
                            {{ $oldVals['account_number'] ?? 'N/A' }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-700">{{ $oldVals['name'] ?? 'N/A' }}</span>
                                @if(!empty($oldVals['description']))
                                    <span class="text-xs text-gray-400 mt-0.5 font-normal leading-relaxed">
                                        {{ $oldVals['description'] }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-gray-600">
                            {{ $oldVals['type'] ?? $oldVals['category'] ?? 'N/A' }}
                        </td>
                        <td class="py-3.5 px-4 text-gray-600">
                            {{ trim($deletedBy) ?: 'System' }}
                        </td>
                        <td class="py-3.5 px-4 text-right text-gray-500 font-medium">
                            {{ $account->created_at ? $account->created_at->format('d M Y h:i A') : 'N/A' }}
                        </td>
                    </tr>
                @else
                    @php
                        $balObj = $ytdBalances->get($account->id);
                        $debit = $balObj ? (float) $balObj->total_debit : 0.0;
                        $credit = $balObj ? (float) $balObj->total_credit : 0.0;
                        $isDebitNormal = in_array($account->category, ['Assets', 'Expenses', 'COGS']);
                        $ytdValue = $isDebitNormal ? ($debit - $credit) : ($credit - $debit);
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <td class="py-3.5 px-4">
                            <input type="checkbox" name="ids[]" value="{{ $account->id }}" class="account-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] w-4 h-4 cursor-pointer" data-is-system="{{ $account->is_system ? '1' : '0' }}">
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-gray-900">
                            {{ $account->account_number }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex flex-col">
                                @if($account->deleted_at)
                                    <span class="font-medium text-gray-500">{{ $account->name }}</span>
                                @else
                                    <a href="{{ route('account.organization-settings.account.edit', $account->id) }}" class="font-medium text-[#0078C8] hover:underline hover:text-[#005c99]">
                                        {{ $account->name }}
                                    </a>
                                @endif
                                @if($account->description)
                                    <span class="text-xs text-gray-400 mt-0.5 font-normal leading-relaxed">
                                        {{ $account->description }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-gray-600">
                            {{ $account->type ?? $account->category }}
                        </td>
                        <td class="py-3.5 px-4 text-gray-600">
                            {{ $account->tax_code ?? 'Tax Exempt (0%)' }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-medium {{ $ytdValue != 0 ? 'text-[#0078C8]' : 'text-gray-900' }}">
                            {{ numberFormat($ytdValue) }}
                        </td>
                        <td class="py-3.5 px-4 text-right relative">
                            <button class="text-gray-400 hover:text-gray-700 dropdown-btn focus:outline-none">
                                <span class="material-symbols-outlined text-lg align-middle">more_vert</span>
                            </button>
                            <div class="dropdown-menu hidden absolute right-0 mt-1 w-48 bg-white rounded shadow-lg border border-gray-200 z-30 py-1">
                                @if($account->deleted_at)
                                    <button type="button" onclick="restoreSingleAccount({{ $account->id }})" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-xs text-gray-700 font-medium">
                                        <span class="material-symbols-outlined text-sm">restore</span> Restore
                                    </button>
                                @else
                                    <a href="{{ route('account.organization-settings.account.detail', $account->id) }}" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-xs text-gray-700 font-medium">
                                        <span class="material-symbols-outlined text-sm">visibility</span> Details
                                    </a>
                                    <a href="{{ route('account.organization-settings.account.edit', $account->id) }}" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-xs text-gray-700 font-medium">
                                        <span class="material-symbols-outlined text-sm">edit</span> Edit
                                    </a>
                                    <a href="{{ route('account.bank-reconciliation.index', $account->id) }}" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-xs text-gray-700 font-medium">
                                        <span class="material-symbols-outlined text-sm">account_balance</span> Reconcile
                                    </a>
                                    <a href="{{ route('account.organization-settings.account.logs', $account->id) }}" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-xs text-gray-700 font-medium">
                                        <span class="material-symbols-outlined text-sm">history</span> Logs
                                    </a>
                                    @if(!$account->is_system)
                                        <hr class="my-1 border-gray-100">
                                        <button type="button" onclick="archiveSingleAccount({{ $account->id }})" class="w-full text-left px-4 py-2 hover:bg-orange-50 hover:text-orange-700 flex items-center gap-2 text-xs font-medium">
                                            <span class="material-symbols-outlined text-sm">archive</span> Archive
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-400 font-medium bg-gray-50/50">
                        No accounts found matching the current filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 p-4 border-t border-gray-100" id="pagination-wrapper">
    <x-data-table-paginatior :paginator="$accounts" />
</div>
