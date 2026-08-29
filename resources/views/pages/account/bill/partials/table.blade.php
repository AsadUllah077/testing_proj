<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                {{-- <th class="px-4 py-3 text-left"><input type="checkbox" id="select-all"
                                                data-col="remaining_amount"></th> --}}
                <th class="px-4 py-3 text-left" data-col="date"><x-sortable column="date" label="Date" /></th>
                <th class="px-4 py-3 text-left" data-col="ref_no"><x-sortable column="ref_no" label="Reference No" /></th>
                <th class="px-4 py-3 text-left" data-col="invoice_no"><x-sortable column="invoice_no"
                        label="Invoice No" /></th>
                <th class="px-4 py-3 text-left" data-col="vendor"><x-sortable column="vendor" label="Vendor" /></th>
                <th class="px-4 py-3 text-left" data-col="total"><x-sortable column="total" label="Total Amount" /></th>
                <th class="px-4 py-3 text-left" data-col="total_after_discount">
                    <x-sortable column="total_after_discount" label="Total After Discount / Tax" />
                </th>
                <th class="px-4 py-3 text-left" data-col="remaining_amount"><x-sortable column="remaining_amount"
                        label="Remaining Amount" /></th>
                <th class="px-4 py-3 text-left" data-col="created_by"><x-sortable column="created_by"
                        label="Created by" /></th>
                <th class="px-4 py-3 text-left" data-col="updated_by"><x-sortable column="updated_by"
                        label="Updated by" /></th>
                <th class="px-4 py-3 text-left" data-col="status"><x-sortable column="status"
                        label="Status" /></th>
                <th class="px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($bills as $bill)
                <tr class="hover:bg-gray-50 border-b">
                    {{-- <td class="px-4 py-2"><input type="checkbox" class="bill-checkbox"
                                                    value="{{ $bill->id }}"></td> --}}
                    <td class="px-4 py-2" data-col="date">{{ dateFormat($bill->date) }}</td>
                    <td class="px-4 py-2" data-col="ref_no">{{ $bill->ref_no ?? 'N/A' }}</td>
                    <td class="px-4 py-2" data-col="invoice_no">{{ $bill->invoice_no }}</td>
                    <td class="px-4 py-2" data-col="vendor">{{ $bill->vendor->name ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-2 text-left" data-col="total">
                        {{ numberFormat($bill->total) }}</td>
                    <td class="px-4 py-2 text-left" data-col="total_after_discount">
                        {{ numberFormat($bill->total_after_discount) }}</td>
                    <td class="px-4 py-2 text-center" data-col="remaining_amount">
                        @if ($bill->debitNotes->count() > 0)
                            <div class="flex flex-col items-center gap-1">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-600 text-white">
                                    Debit Note Applied
                                </span>
                                @if ($bill->remaining_amount > 0 && $bill->approval_status === 'approved')
                                    <button
                                        data-bill-id="{{ $bill->id }}"
                                        data-invoice-no="{{ $bill->invoice_no }}"
                                        data-vendor-name="{{ $bill->vendor->name ?? 'N/A' }}"
                                        data-remaining="{{ $bill->remaining_amount }}"
                                        onclick="openPaymentModal(this.dataset.billId, this.dataset.invoiceNo, this.dataset.vendorName, this.dataset.remaining)"
                                        class="bg-[#B1AFAF] hover:bg-[#0078C8] px-3 py-1 text-white rounded-full text-xs transition-colors cursor-pointer"
                                        title="Debit note applied. Click to record the remaining bill payment.">
                                        Due {{ number_format($bill->remaining_amount, 2) }}
                                    </button>
                                @elseif($bill->remaining_amount == 0 && $bill->approval_status === 'approved')
                                    <span class="bg-[#0078C8] px-3 py-1 text-white rounded-full text-xs">
                                        Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-bold text-gray-500">
                                        Due {{ number_format($bill->remaining_amount, 2) }}
                                    </span>
                                @endif
                            </div>
                        @elseif ($bill->remaining_amount > 0 && $bill->approval_status === 'approved')
                            <button
                                data-bill-id="{{ $bill->id }}"
                                data-invoice-no="{{ $bill->invoice_no }}"
                                data-vendor-name="{{ $bill->vendor->name ?? 'N/A' }}"
                                data-remaining="{{ $bill->remaining_amount }}"
                                onclick="openPaymentModal(this.dataset.billId, this.dataset.invoiceNo, this.dataset.vendorName, this.dataset.remaining)"
                                class="bg-[#B1AFAF] hover:bg-[#0078C8] px-3 py-1 text-white rounded-full text-xs transition-colors cursor-pointer"
                                title="Click to record payment">
                                {{ number_format($bill->remaining_amount, 2) }}
                            </button>
                        @elseif($bill->remaining_amount == 0 && $bill->approval_status === 'approved')
                            <span class="bg-[#0078C8] px-3 py-1 text-white rounded-full text-xs ">
                                Paid
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 text-xs font-bold text-gray-500">
                                {{ number_format($bill->remaining_amount, 2) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-left" data-col="created_by">
                        {{ $bill->creator->fullName ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-left" data-col="updated_by">
                        {{ $bill->updater->fullName ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-left" data-col="status">
                        @php
                            $approvalBadge = match($bill->approval_status) {
                                'approved' => 'bg-green-100 text-green-700', 'submitted' => 'bg-amber-100 text-amber-700',
                                'rejected', 'void' => 'bg-red-100 text-red-600', default => 'bg-gray-100 text-gray-600',
                            };
                            $paymentBadge = match($bill->payment_status) {
                                'paid' => 'bg-green-100 text-green-700', 'partial' => 'bg-yellow-100 text-yellow-700',
                                'overpaid' => 'bg-purple-100 text-purple-700', default => 'bg-red-100 text-red-600',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $approvalBadge }}">
                            {{ ucfirst($bill->approval_status) }}
                        </span>
                        <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $paymentBadge }}">
                            {{ ucfirst(str_replace('_', ' ', $bill->payment_status ?? 'unpaid')) }}
                        </span>
                    </td>

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
                            @if ($bill->isEditable())
                            <form action="{{ route('account.bill-invoice.destroy', $bill->id) }}" method="POST"
                                class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class=" w-full text-left px-4 py-2.5 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-600 transition-colors"
                                    onclick="return confirm('Are you sure you want to delete this bill?')">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                            <a href="{{ route('account.bill-invoice.edit', $bill->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </a>
                            @endif
                            <a href="{{ route('account.bill-invoice.show', $bill->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Details
                            </a>
                            <a href="{{ route('account.bill-invoice.audit-logs', $bill->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                        clip-rule="evenodd" />
                                </svg>
                                History
                            </a>
                            <a href="javascript:void(0)" onclick="openExportModal('bill', {{ $bill->id }})"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z"
                                        clip-rule="evenodd" />
                                </svg>
                                Export
                            </a>
                            @if ($bill->approval_status === 'draft' || $bill->approval_status === 'rejected')
                            <form action="{{ route('account.approvals.bills.submit', $bill->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2.5 hover:bg-amber-50 flex items-center gap-3 text-sm text-gray-700 hover:text-amber-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Submit Approval
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        No bills found.
                    </td>
                </tr>
            @endforelse

            <!-- Total Row -->
            @if ($bills->count() > 0)
                <tr class="font-semibold">
                    <td colspan="4" class="px-6 py-4 text-[#0078C8] text-right text-sm uppercase tracking-wide">
                        Total:
                    </td>
                    <td class="px-4 py-4 text-[#0078C8] text-sm font-bold">
                        {{ number_format($totalAmount, 2) }}
                    </td>
                    <td class="px-4 py-4 text-[#0078C8] text-sm font-bold">
                        {{ number_format($totalAfterDiscount, 2) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#0078C8] text-white">
                            {{ number_format($totalRemaining, 2) }}
                        </span>
                    </td>
                    <td colspan="4"></td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$bills" />
