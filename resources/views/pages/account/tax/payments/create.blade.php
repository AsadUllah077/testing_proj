@extends('layouts.auth')

@section('title', 'Record Tax ' . ($taxPeriod->net_amount > 0 ? 'Payment' : 'Refund'))

@section('content')
    <div class="mb-8">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Record Tax
                        {{ $taxPeriod->net_amount > 0 ? 'Payment' : 'Refund' }}</h2>
                    <p class="text-xs text-gray-500 mt-1">Settling period: <span
                            class="font-medium text-gray-700">{{ $taxPeriod->name }}</span></p>
                </div>
                <a href="{{ route('account.tax-periods.show', $taxPeriod->id) }}"
                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-xs font-normal rounded hover:bg-[#0078C8] hover:text-white transition-all duration-200">
                    BACK TO DETAILS
                </a>
            </div>

            <!-- Form Card -->
            <div class="max-w-2xl bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <form action="{{ route('account.tax-periods.payment.store', $taxPeriod->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-8 space-y-6">
                        <!-- Amount Display -->
                        <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-1">Total
                                        Amount
                                        to {{ $taxPeriod->net_amount > 0 ? 'Pay' : 'Receive' }}</p>
                                    <h3 class="text-2xl font-bold text-[#0078C8]">
                                        {{ numberFormat(abs($taxPeriod->net_amount)) }}</h3>
                                </div>
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-[#0078C8]">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6v12m-3-2.818l.879.659c1.546 1.16 3.743 1.16 5.289 0m-5.289-8.402l.879.659c1.546 1.16 3.743 1.16 5.289 0m-5.289 4.352c1.724-1.367 3.312-1.367 5.289 0m-5.289-4.352V18.75m5.289-12.75V15.75" />
                                    </svg>
                                </div>
                            </div>

                            @php $paidAmount = $taxPeriod->paid_amount ?? 0; @endphp
                            @if ($paidAmount > 0)
                                <div class="border-t border-gray-200 pt-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600">Already Paid:</span>
                                        <span
                                            class="text-sm font-medium text-green-600">{{ numberFormat($paidAmount) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Remaining:</span>
                                        <span
                                            class="text-sm font-bold text-red-600">{{ numberFormat(abs($taxPeriod->net_amount) - $paidAmount) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Payment
                                Amount <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                max="{{ abs($taxPeriod->net_amount) - $paidAmount }}"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                                placeholder="Enter payment amount" value="{{ abs($taxPeriod->net_amount) - $paidAmount }}"
                                required>
                            <p class="text-xs text-gray-500 mt-1">Maximum allowed:
                                {{ numberFormat(abs($taxPeriod->net_amount) - $paidAmount) }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Select
                                Bank Account <span class="text-red-500">*</span></label>
                            <select name="bank_account_id"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                                required>
                                <option value="">-- select bank account --</option>
                                @foreach ($bankAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->name }} (Balance: {{ numberFormat($account->balance) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Payment
                                    Date <span class="text-red-500">*</span></label>
                                <input type="text" name="payment_date"
                                    class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Reference
                                    / Memo</label>
                                <input type="text" name="reference"
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                                    placeholder="e.g. TAX-PYMT-{{ $taxPeriod->name }}"
                                    value="TAX-PYMT-{{ $taxPeriod->name }}">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Receipt / Evidence</label>
                            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </div>

                    <div class="px-8 py-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                        <a href="{{ route('account.tax-periods.show', $taxPeriod->id) }}"
                            class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded shadow-sm hover:bg-green-700 focus:ring-2 focus:ring-offset-2 focus:ring-green-600 transition-all">
                            Record {{ $taxPeriod->net_amount > 0 ? 'Payment' : 'Refund' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
