<div class="overflow-x-auto w-full">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                {{-- <th class="px-4 py-3 text-left">
                                            <input type="checkbox" id="select-all">
                                        </th> --}}
                <th class="px-4 py-3 text-left" data-col="j_date"><x-sortable column="date" label="Date" /></th>
                <th class="px-4 py-3 text-left" data-col="j_unique_no"><x-sortable column="unique_no"
                        label="Reference No" /></th>
                <th class="px-4 py-3 text-left" data-col="j_narration">Narration</th>
                <th class="px-4 py-3 text-left" data-col="j_item">Item</th>
                <th class="px-4 py-3 text-left" data-col="j_description">Description</th>
                <th class="px-4 py-3 text-left" data-col="j_account">Account</th>
                <th class="px-4 py-3 text-left" data-col="j_debit">Debit</th>
                <th class="px-4 py-3 text-left" data-col="j_credit">Credit</th>
                <th class="px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white" id="journal-entries-table-body">
            @php $previousEntry = null; @endphp
            @forelse($entries as $entry)
                @php
                    separateByGroup($entry, $previousEntry, 10);
                    $previousEntry = $entry;
                @endphp
                <tr class="hover:bg-gray-50" data-unique-no="{{ $entry->unique_no }}">
                    {{-- <td class="px-4 py-2">
                                                <input type="checkbox" class="row-checkbox" value="{{ $entry->unique_no }}">
                                            </td> --}}
                    <td class="px-4 py-2 whitespace-nowrap" data-col="j_date">
                        {{ $entry->date->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap" data-col="j_unique_no">
                        <a href="{{ route('account.journal-entries.show', $entry->unique_no) }}"
                            class="text-[#0078C8] hover:underline">{{ $entry->unique_no }}</a>
                    </td>
                    <td class="px-4 py-2" data-col="j_narration">
                        {{ Str::limit($entry->narration, 30) }}
                    </td>
                    <td class="px-4 py-2" data-col="j_item">
                        {{ Str::limit($entry->item, 20) }}
                    </td>
                    <td class="px-4 py-2" data-col="j_description">
                        {{ Str::limit($entry->description, 25) }}
                    </td>
                    <td class="px-4 py-2" data-col="j_account">
                        {{ $entry->account->name ?? 'N/A' }}
                        @if ($entry->account->account_number ?? false)
                            <br><span class="text-xs text-gray-500">({{ $entry->account->account_number }})</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-right" data-col="j_debit">
                        @if ($entry->debit > 0)
                            <span class="text-green-600 font-medium">{{ number_format($entry->debit, 2) }}</span>
                        @else
                            <span class="text-gray-400">0.00</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-right" data-col="j_credit">
                        @if ($entry->credit > 0)
                            <span class="text-red-600 font-medium">{{ number_format($entry->credit, 2) }}</span>
                        @else
                            <span class="text-gray-400">0.00</span>
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
                            <a href="{{ route('account.journal-entries.show', $entry->unique_no) }}"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-blue-600">

                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                View / Reverse
                            </a>
                            <button
                                onclick="window.location.href='{{ route('account.journal-entries.logs', $entry->unique_no) }}'"
                                class="flex w-full items-center gap-1 px-4 py-1.5 text-xs  text-gray-600 rounded hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#0078C8]" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2h6v2m3 4H6a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v13a2 2 0 01-2 2z" />
                                </svg>
                                Logs
                            </button>

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-4 text-center text-gray-500">
                        No journal entries found.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($entries->isNotEmpty())
            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                    <td colspan="6" class="px-4 py-3 text-right font-semibold text-gray-700">
                        Totals:</td>
                    <td class="px-4 py-3 text-right font-semibold text-green-600">
                        {{ number_format($entries->sum('debit'), 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-red-600">
                        {{ number_format($entries->sum('credit'), 2) }}
                    </td>
                    <td class="px-4 py-3"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-data-table-paginatior :paginator="$journalPaginator" />
