<div class="overflow-x-auto w-full p-4 ">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left" data-col="tax_name"><x-sortable column="tax_name" label="Name" /></th>
                <th class="px-4 py-3 text-left" data-col="tax_code"><x-sortable column="tax_code" label="Code" /></th>
                <th class="px-4 py-3 text-left" data-col="tax_rate"><x-sortable column="tax_rate" label="Rate" /></th>
                <th class="px-4 py-3 text-left" data-col="tax_type"><x-sortable column="tax_type" label="Type" /></th>
                <th class="px-4 py-3 text-right">Action</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
            @forelse ($taxRates as $rate)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2">{{ $loop->index + 1 }}</td>
                    <td class="px-4 py-2" data-col="tax_name">{{ $rate->name }}</td>
                    <td class="px-4 py-2" data-col="tax_code">{{ $rate->code }}</td>
                    <td class="px-4 py-2" data-col="tax_rate">{{ $rate->tax_rate }}%</td>
                    <td class="px-4 py-2" data-col="tax_type">{{ $rate->tax_type }}</td>

                    <!-- Action -->
                    <td class="px-4 py-2  text-right relative">

                        <button class="dropdown-btn text-gray-500 hover:text-gray-800">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div
                            class="dropdown-menu hidden absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg border border-gray-200 z-20">

                            <button onclick="loadTaxRate({{ $rate->id }})"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4 text-[#0078C8]" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>Edit
                            </button>

                            <button onclick="deleteTaxRate({{ $rate->id }})"
                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 text-sm text-red-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>Delete
                            </button>
                            <button
                                onclick="window.location.href='{{ route('account.organization-settings.tax_rate.logs', $rate->id) }}'"
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
                    <td colspan="6" class="text-center py-5 text-gray-500">
                        No Tax Rates Found
                    </td>
                </tr>
            @endforelse
        </tbody>

    </table>
</div>

<x-data-table-paginatior :paginator="$taxRates" />
