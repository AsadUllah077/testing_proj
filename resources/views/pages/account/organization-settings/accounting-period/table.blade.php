<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left"><x-sortable column="start_date" label="Start Date" /></th>
                <th class="px-4 py-3 text-left"><x-sortable column="end_date" label="End Date" />
                </th>
                <th class="px-4 py-3 text-left"><x-sortable column="status" label="Status" /></th>
                <th class="px-4 py-3 text-left">Allowed Advisors</th>
                <th class="px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($accountingPeriods as $period)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2">{{ $loop->index + 1 }}</td>
                    <td class="px-4 py-2">{{ dateFormat($period->start_date) }}</td>
                    <td class="px-4 py-2">{{ dateFormat($period->end_date) }}</td>
                    <td class="px-4 py-2">
                        <button type="button"
                            onclick="openStatusModal(
                                '{{ $period->id }}',
                                '{{ $period->start_date }}',
                                '{{ $period->end_date }}',
                                '{{ $period->is_locked }}',
                                '{{ route('account.organization-settings.accounting-period.updateStatus', $period->id) }}'
                            )"
                            class="text-white bg-[#0078C8] rounded-3xl p-1 hover:underline">
                            {{ $period->is_locked ? 'Lock' : 'Open' }}
                        </button>
                    </td>
                    <td class="px-4 py-2">
                        @if ($canManageAllowedUsers)
                            <button type="button"
                                class="allowed-advisors-btn inline-flex items-center gap-2 rounded border border-[#0078C8] px-3 py-1.5 text-xs text-[#0078C8] hover:bg-[#0078C8] hover:text-white"
                                data-period-name="{{ $period->name }}"
                                data-update-url="{{ route('account.organization-settings.accounting-period.allowed-users.update', $period) }}"
                                data-user-ids="{{ $period->allowedUsers->pluck('user_id')->values()->toJson() }}"
                                title="Allow these users (Advisors) to make changes when period is locked">
                                <i class="fa-solid fa-user-shield"></i>
                                <span class="advisor-count">
                                    {{ $period->allowedUsers->count() }} {{ Str::plural('Advisor', $period->allowedUsers->count()) }}
                                </span>
                            </button>
                        @else
                            <span class="text-xs text-gray-600">
                                {{ $period->allowedUsers->count() }} {{ Str::plural('Advisor', $period->allowedUsers->count()) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center relative">
                        <button class="text-gray-500 hover:text-gray-800 dropdown-btn">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>
                        <div
                            class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                            <button
                                onclick="window.location.href='{{ route('account.organization-settings.accounting-period.edit', $period->id) }}'"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                <span>▶</span>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </button>
                            <button
                                onclick="window.location.href='{{ route('account.organization-settings.accounting-period.detail', $period->id) }}'"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                <span>▶</span>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Details
                            </button>
                            <button
                                onclick="window.location.href='{{ route('account.organization-settings.accounting-period.logs', $period->id) }}'"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                        clip-rule="evenodd" />
                                </svg>
                                Logs
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-gray-500">
                        No Fiscal Periods Found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$accountingPeriods" />
