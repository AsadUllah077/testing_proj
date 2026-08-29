<table class="w-full text-sm text-left">
    <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Reference</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Customer</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Account</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($payments as $payment)
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 font-semibold text-gray-700">{{ $payment->reference_no }}</td>
                <td class="px-4 py-3 text-gray-600">{{ dateFormat($payment->payment_date) }}</td>
                <td class="px-4 py-3 text-gray-800 font-medium">{{ $payment->customer->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $payment->account->name }}</td>
                <td class="px-4 py-3">
                    @if ($payment->status === 'draft')
                        <span
                            class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded bg-gray-100 text-gray-500 border border-gray-200">Draft</span>
                    @else
                        <span
                            class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded bg-green-100 text-green-600 border border-green-200">Confirmed</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-bold text-green-600">
                    {{ numberFormat($payment->total_amount) }}</td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('account.partial-payment.show', $payment->id) }}"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full text-[#0078C8] hover:bg-blue-50"
                        title="View Details">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="{{ route('account.partial-payment.receipt', $payment->id) }}"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full text-[#0078C8] hover:bg-blue-50">
                        <i class="fa-solid fa-file-pdf mr-1"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                    No payment records found. <br>
                    Click "New Allocation" to record your first payment distribution.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<x-data-table-paginatior :paginator="$payments" />
