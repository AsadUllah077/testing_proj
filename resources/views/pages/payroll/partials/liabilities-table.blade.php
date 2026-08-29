<form action="{{ route('payroll.liabilities.store') }}" method="POST" id="batchPayForm" enctype="multipart/form-data">
    @csrf
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- List of Liabilities -->
        <div class="lg:col-span-2 space-y-6">
            @foreach ($groupedLiabilities as $payItemId => $items)
                @php 
                    $firstItem = collect($items->items())->first();
                    $payItem = $firstItem ? $firstItem->payItem : \App\Models\PayItem::with('account')->find($payItemId); 
                @endphp
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                        <div><h3 class="font-semibold text-gray-700">{{ $payItem->name ?? 'Unknown' }}</h3><p class="text-[11px] text-gray-500">{{ $payItem?->statutoryBody?->name ?? 'Statutory authority not configured' }}</p></div>
                        <span class="text-xs font-medium px-2 py-1 bg-blue-100 text-blue-700 rounded-full">
                            {{ $payItem->account->name ?? 'No Account' }}
                        </span>
                    </div>
                    <div class="overflow-x-auto overflow-y-auto max-h-[80vh]">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">
                                        <input type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 select-all-group"
                                            data-group="{{ $payItemId }}">
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">
                                        Pay Run</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">
                                        Period</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">
                                        Statutory Due</th>
                                    <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">
                                        Outstanding</th>
                                    <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">
                                        Remit Now</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($items as $item)
                                    <tr class="hover:bg-gray-50 {{ $item->is_overdue ? 'bg-red-50/40' : '' }}">
                                        <td class="px-4 py-2">
                                            <input type="checkbox" value="{{ $item->id }}" {{ (!$item->payItem?->statutoryBody || !$item->payItem?->account) ? 'disabled' : '' }}
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 item-checkbox"
                                                data-amount="{{ $item->outstanding_amount }}" data-group="{{ $payItemId }}">
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600 font-medium">
                                            PR-{{ str_pad($item->payslip->payRun->id, 6, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-500">
                                            {{ $item->payslip->payRun->period_start->format('d M') }} -
                                            {{ $item->payslip->payRun->period_end->format('d M Y') }}
                                        </td>
                                        <td class="px-4 py-2 text-xs">
                                            @if ($item->statutory_due_date)
                                                <div class="text-gray-700">{{ $item->statutory_due_date->format('d M Y') }}</div>
                                                @if ($item->is_overdue)
                                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $item->days_overdue }}d overdue
                                                    </span>
                                                @elseif ($item->is_due_soon)
                                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                        <i class="fa-regular fa-clock mr-1"></i> Due soon
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right font-bold text-gray-800">
                                            {{ number_format($item->outstanding_amount, 2) }}
                                            @if ((float) $item->paid_amount > 0)
                                                <span class="block text-[10px] text-green-600">{{ number_format($item->paid_amount, 2) }} already remitted</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <input type="number" disabled step="0.01" min="0.01" max="{{ $item->outstanding_amount }}" value="{{ number_format($item->outstanding_amount, 2, '.', '') }}" class="allocation-amount w-28 rounded border border-gray-300 px-2 py-1 text-right text-sm">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="5"
                                        class="px-4 py-2 text-xs font-bold text-right text-gray-600 uppercase">
                                        Subtotal (Current Page)</td>
                                    <td class="px-4 py-2 text-sm text-right font-black text-gray-900">
                                        {{ number_format(collect($items->items())->sum('outstanding_amount'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if($items->hasPages())
                        <div class="p-3 bg-white border-t border-gray-200">
                            <x-data-table-paginatior :paginator="$items" />
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Payment Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 sticky top-8">
                <h2 class="text-lg font-bold text-gray-800 mb-4 uppercase tracking-wider">Record Payment</h2>

                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-xs text-blue-600 font-bold uppercase mb-1">Total Selected</p>
                    <p class="text-3xl font-black text-blue-900" id="totalAmount">0.00</p>
                    <p class="text-xs text-blue-500 mt-1" id="selectedCount">0 items selected</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                            class="date block w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Paid From</label>
                        <select name="bank_account_id" required
                            class="select2 block w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2">
                            <option value="">Select Bank Account</option>
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}
                                    ({{ $acc->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Payee / Statutory Authority</label>
                        <input type="text" name="payee" required placeholder="Authority receiving this remittance" class="block w-full border-gray-300 rounded shadow-sm sm:text-sm border p-2">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Reference</label>
                        <input type="text" name="reference" placeholder="e.g. Tax Payment Jan 2024"
                            class="block w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Filing / Return Reference</label>
                        <input type="text" name="filing_reference" placeholder="Return or acknowledgement number" class="block w-full border-gray-300 rounded shadow-sm sm:text-sm border p-2">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Receipt / Evidence</label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Notes</label>
                        <textarea name="notes" maxlength="1000" rows="2" class="block w-full border-gray-300 rounded shadow-sm sm:text-sm border p-2"></textarea>
                    </div>

                    <button type="submit" id="submitBtn" disabled
                        class="w-full bg-[#0078C8] hover:bg-[#006bb0] text-white font-bold py-3 px-4 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-widest text-xs">
                        Submit Remittance
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="mt-4 bg-white rounded-lg">
    <x-data-table-paginatior :paginator="$groupedLiabilities" />
</div>
