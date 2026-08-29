<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                {{-- <th class="px-4 py-3 text-left"><input type="checkbox" id="selectAll"></th> --}}
                <th class="px-4 py-3 text-left" data-col="cn_date"><x-sortable column="date" label="Date" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_reference_no"><x-sortable column="reference_no"
                        label="Reference No" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_invoice_no"><x-sortable column="invoice_no"
                        label="Invoice No" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_customer_name"><x-sortable column="customer_id"
                        label="Customer Name" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_description"><x-sortable column="description"
                        label="Description" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_total_amount"><x-sortable column="total_after_discount"
                        label="Total Amount" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_paid_amount"><x-sortable column="paid_amount"
                        label="Refunded Amount" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_allocated_amount"><x-sortable column="allocated_amount"
                        label="Allocated Amount" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_remaining_amount"><x-sortable column="remaining_amount"
                        label="Available Credit" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_created_by"><x-sortable column="created_by"
                        label="Created by" /></th>
                <th class="px-4 py-3 text-left" data-col="cn_updated_by"><x-sortable column="updated_by"
                        label="Updated by" /></th>
                <th class="px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($creditNotes as $creditNote)
                <tr class="hover:bg-gray-50 border-b">
                    {{-- <td class="px-4 py-2">
                                                <input type="checkbox" class="credit-note-checkbox"
                                                    value="{{ $creditNote->id }}">
                                            </td> --}}
                    <td class="px-4 py-2" data-col="cn_date">
                        {{ dateFormat($creditNote->date) }}</td>
                    <td class="px-4 py-2" data-col="cn_reference_no">
                        {{ $creditNote->ref_no ?? 'N/A' }}</td>
                    <td class="px-4 py-2" data-col="cn_invoice_no">
                        {{ $creditNote->invoice_no }}</td>
                    <td class="px-4 py-2" data-col="cn_customer_name">
                        {{ $creditNote->customer->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2" data-col="cn_description">
                        {{ Str::limit($creditNote->note, 30) ?: 'N/A' }}</td>
                    <td class="px-4 py-2 text-left" data-col="cn_total_amount">
                        {{ numberFormat($creditNote->total_after_discount) }}</td>
                    <td class="px-4 py-2 text-left" data-col="cn_paid_amount">
                        {{ numberFormat($creditNote->paid_amount) }}</td>
                    <td class="px-4 py-2 text-left" data-col="cn_allocated_amount">
                        {{ numberFormat($creditNote->allocated_amount ?? 0) }}</td>

                    <td class="px-4 py-2 text-center" data-col="cn_remaining_amount">
                        @if ($creditNote->remaining_amount > 0)
                            <button
                                onclick="openPaymentModal({{ $creditNote->id }}, '{{ $creditNote->invoice_no }}', '{{ $creditNote->customer->name ?? 'N/A' }}', {{ $creditNote->remaining_amount }})"
                                class="bg-[#B1AFAF] hover:bg-[#0078C8] px-3 py-1 text-white rounded-full text-xs transition-colors cursor-pointer"
                                title="Click to refund the available customer credit">
                                {{ number_format($creditNote->remaining_amount, 2) }}
                            </button>
                        @else
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#0078C8] text-white">
                                {{ ($creditNote->allocated_amount ?? 0) > 0 ? 'Allocated' : 'Used' }}
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-2 text-left" data-col="cn_created_by">
                        {{ $creditNote->createdBy->fullName ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-left" data-col="cn_updated_by">
                        {{ $creditNote->updatedBy->fullName ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-center relative">
                        <button
                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-1 rounded hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>
                        <div
                            class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg border border-gray-200 z-20 overflow-hidden">
                            @php
                                $hasLaterSettlements = ($creditNote->refunds_count ?? 0) > 0
                                    || (float) $creditNote->paid_amount > 0
                                    || $creditNote->allocations->count() > 1
                                    || ($creditNote->allocations->count() === 1
                                        && (int) $creditNote->allocations->first()->sale_id !== (int) $creditNote->sale_id);
                            @endphp
                            @unless ($hasLaterSettlements)
                            <form action="{{ route('account.sale.credit-notes.destroy', $creditNote->id) }}"
                                method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class=" w-full text-left px-4 py-2.5 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-600 transition-colors"
                                    onclick="return confirm('Are you sure you want to delete this credit note?')">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                            <a href="{{ route('account.sale.credit-notes.edit', $creditNote->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </a>
                            @endunless
                            <a href="{{ route('account.sale.credit-notes.show', $creditNote->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Details
                            </a>
                            <a href="{{ route('account.sale.credit-notes.audit-logs', $creditNote->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                        clip-rule="evenodd" />
                                </svg>
                                History
                            </a>

                            <a href="javascript:void(0)"
                                onclick="openExportModal('credit-note', {{ $creditNote->id }})"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z"
                                        clip-rule="evenodd" />
                                </svg>
                                Export
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                        No credit notes found.
                    </td>
                </tr>
            @endforelse

            <!-- Total Row -->
            @if ($creditNotes->count() > 0)
                <tr class="font-semibold">
                    <td colspan="5" class="px-6 py-4 text-[#0078C8] text-right text-sm uppercase tracking-wide">
                        Total:
                    </td>
                    <td class="px-3 py-4 items-center text-[#0078C8] text-sm font-bold">
                        {{ numberFormat($totalAmount) }}
                    </td>
                    <td class="px-3 py-4 items-center text-[#0078C8] text-sm font-bold">
                        {{ numberFormat($totalPaid) }}
                    </td>
                    <td class="px-3 py-4 items-center text-[#0078C8] text-sm font-bold">
                        {{ numberFormat($totalAllocated ?? 0) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#0078C8] text-white">
                            {{ numberFormat($totalRemaining) }}
                        </span>
                    </td>
                    <td colspan="3"></td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$creditNotes" />
