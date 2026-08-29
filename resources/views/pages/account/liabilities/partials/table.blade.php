<form action="{{ route('account.liabilities.store') }}" method="POST" id="batchPayForm" enctype="multipart/form-data">
    @csrf
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- List of Liabilities -->
        <div class="lg:col-span-2 space-y-6">
            @if (empty($groupedLiabilities))
                <div class="bg-white rounded-lg shadow-sm border border-dashed border-gray-300 p-12 text-center">
                    <div class="text-gray-400 mb-2">
                        <i class="fa-solid fa-circle-check text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No outstanding liabilities</h3>
                    <p class="text-gray-500">All liability account balances for the selected periods are zero or
                        negative.</p>
                </div>
            @else
                @foreach ($groupedLiabilities as $accountId => $items)
                    @php $account = $items[0]['account']; @endphp
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-semibold text-gray-700">{{ $account->name }}
                                ({{ $account->account_number }})</h3>
                            <span class="text-xs font-medium px-2 py-1 bg-green-100 text-green-700 rounded-full">
                                {{ $account->category }}
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-10">
                                            <input type="checkbox"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 select-all-group"
                                                data-group="{{ $accountId }}">
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">
                                            Balance Basis</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">
                                            Outstanding Balance</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Pay Now</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2">
                                                <input type="checkbox" value="{{ $item['account']->id }}"
                                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 item-checkbox"
                                                    data-amount="{{ $item['amount'] }}"
                                                    data-group="{{ $accountId }}">
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600 font-medium">
                                                Current outstanding balance
                                                <span class="block text-xs text-gray-400 font-normal">
                                                    Includes all posted liability debits and credits
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right font-bold text-gray-800">
                                                {{ number_format($item['amount'], 2) }}
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                <input type="hidden" disabled class="allocation-account" name="allocations[{{ $item['account']->id }}][account_id]" value="{{ $item['account']->id }}">
                                                <input type="number" disabled step="0.01" min="0.01" max="{{ $item['amount'] }}" class="allocation-amount w-28 border border-gray-300 rounded px-2 py-1 text-sm text-right" name="allocations[{{ $item['account']->id }}][amount]" value="{{ number_format($item['amount'], 2, '.', '') }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="3"
                                            class="px-4 py-2 text-xs font-bold text-right text-gray-600 uppercase">
                                            Subtotal</td>
                                        <td class="px-4 py-2 text-sm text-right font-black text-gray-900">
                                            {{ number_format(collect($items)->sum('amount'), 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Payment Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 sticky top-8">
                <h2 class="text-lg font-bold text-gray-800 mb-4 uppercase tracking-wider">Record Payment</h2>

                <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                    <p class="text-xs text-indigo-600 font-bold uppercase mb-1">Total to Pay</p>
                    <p class="text-3xl font-black text-indigo-900" id="totalAmount">0.00</p>
                    <p class="text-xs text-indigo-500 mt-1" id="selectedCount">0 items selected</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                            class="date block w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Payee / Authority</label>
                        <input type="text" name="payee" required placeholder="e.g. Tax authority, EPF, SOCSO" class="block w-full border-gray-300 rounded shadow-sm sm:text-sm border p-2">
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
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Receipt / Evidence</label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Reference</label>
                        <input type="text" name="reference" placeholder="e.g. Sales Tax Payment Jan 2024"
                            class="block w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2 text-sm">
                    </div>

                    <button type="submit" id="submitBtn" disabled
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-widest text-xs">
                        Record Payment
                    </button>

                    <p class="text-[10px] text-gray-400 text-center uppercase mt-4">
                        This will generate a Journal Entry: <br>
                        DR Liability Account | CR Bank Account
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
