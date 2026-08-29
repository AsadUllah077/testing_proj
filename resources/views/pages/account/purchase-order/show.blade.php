@extends('layouts.auth')

@section('title', 'Purchase Order Details')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.purchase-order') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="p-2">
            <div class="mx-auto">
                <div class="bg-white border border-gray-300 rounded-md shadow-sm">
                    <!-- Header -->
                    <div
                        class="p-5 border-b border-gray-200 flex rounded-md justify-between items-center bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="text-xl font-semibold text-gray-800">Purchase Order Details</h2>
                        <div class="flex gap-2">
                            <a href="{{ route('account.purchase-order.index') }}"
                                class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg">
                                ← BACK
                            </a>
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-300 rounded-lg shadow-sm my-4">
                    <!-- Details -->
                    <div class="p-6">
                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Vendor</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->vendor->name ?? 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order No</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->order_no }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order Date</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->order_date->format('M d, Y') }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Departure Date</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->departure_date->format('M d, Y') }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ref No</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->ref_no ?? 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Requested By</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->requested_by ?? 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Invoice No</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->invoice_no ?? 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->currency->code ?? 'N/A' }} -
                                    {{ $purchaseOrder->currency->name ?? 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Created By</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->createdBy->username ?? 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Created Date</label>
                                <div
                                    class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-gray-50 flex items-center">
                                    {{ $purchaseOrder->created_at->format('M d, Y') }}
                                </div>
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6 shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm border-collapse">
                                    <thead
                                        class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 text-gray-700">
                                        <tr>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left w-24 font-semibold">Item
                                            </th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left font-semibold">
                                                Description</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-center w-20 font-semibold">HS
                                                Code</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-center w-20 font-semibold">
                                                Qty</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-center w-20 font-semibold">Kg
                                            </th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-right w-24 font-semibold">
                                                Price</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left w-32 font-semibold">
                                                Account</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left w-24 font-semibold">Tax
                                            </th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-right w-20 font-semibold">
                                                Project</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-right w-28 font-semibold">
                                                Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        @foreach ($purchaseOrder->purchaseOrderItems as $item)
                                            <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors">
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <div class="w-full h-9 px-2 rounded-md text-sm flex items-center">
                                                        {{ $item->item ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <div class="w-full h-9 px-2 rounded-md text-sm flex items-center">
                                                        {{ $item->description ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-center">
                                                    <div
                                                        class="w-full h-9 px-2 rounded-md text-sm flex items-center justify-center">
                                                        {{ $item->hs_code ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-center">
                                                    <div
                                                        class="w-full h-9 px-2 rounded-md text-sm flex items-center justify-center">
                                                        {{ $item->quantity }}
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-center">
                                                    <div
                                                        class="w-full h-9 px-2 rounded-md text-sm flex items-center justify-center">
                                                        {{ $item->kg ?? '0' }}
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-right">
                                                    <div
                                                        class="w-full h-9 px-2 rounded-md text-sm flex items-center justify-end">
                                                        {{ number_format($item->price, 2) }}
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <div class="w-full h-9 px-2 rounded-md text-sm flex items-center">
                                                        {{ $item->account->name ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <div class="w-full h-9 px-2 rounded-md text-sm flex items-center">
                                                        {{ $item->tax->name ?? 'N/A' }}
                                                        @if ($item->tax)
                                                            ({{ $item->tax->tax_rate }}%)
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-right">
                                                    <div
                                                        class="w-full h-9 px-2 rounded-md text-sm flex items-center justify-end">
                                                        {{ $item->project ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2 text-right">
                                                    <div
                                                        class="w-full h-9 px-2 rounded-md text-sm flex items-center justify-end font-semibold text-[#0078C8]">
                                                        {{ number_format($item->total, 2) }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Buttons and Totals -->
                        <div class="grid grid-cols-1 md:grid-cols-2 mb-6 gap-4">
                            <div>
                                <div class="mt-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                                        <div
                                            class="w-full px-2 border border-gray-300 py-2 rounded-md text-sm bg-gray-50 min-h-[100px]">
                                            {{ $purchaseOrder->note ?? 'No notes' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-lg p-4 shadow-sm w-full">
                                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                                    <div class="text-left text-gray-600 font-medium">Sub Total:</div>
                                    <div class="text-left font-semibold text-gray-800">
                                        {{ number_format($purchaseOrder->sub_total, 2) }}</div>
                                    <div class="text-left text-gray-600 font-medium">Tax:</div>
                                    <div class="text-left font-semibold text-gray-800">
                                        {{ number_format($purchaseOrder->tax, 2) }}</div>
                                    <div class="text-left text-[#0078C8] font-semibold border-t border-gray-300 pt-2">Total:
                                    </div>
                                    <div class="text-left font-bold text-[#0078C8] border-t border-gray-300 pt-2 text-lg">
                                        {{ number_format($purchaseOrder->total, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Section -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">Delivery Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
                                    <div class="w-full rounded-lg border border-gray-300 p-2 bg-white min-h-[80px] text-sm">
                                        {{ $purchaseOrder->delivery_address ?? 'No delivery address provided' }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Instruction</label>
                                    <div class="w-full rounded-lg border border-gray-300 p-2 bg-white min-h-[80px] text-sm">
                                        {{ $purchaseOrder->delivery_instruction ?? 'No delivery instructions provided' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status and Additional Information -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">Order Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <div
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-white flex items-center">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-800',
                                                '0' => 'bg-blue-100 text-blue-800',
                                                'confirmed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                            ];
                                            $statusColor =
                                                $statusColors[strtolower($purchaseOrder->status)] ??
                                                'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded text-xs font-medium {{ $statusColor }}">
                                            {{ $purchaseOrder->status == 0 ? 'pending' : '' }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Updated</label>
                                    <div
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-white flex items-center">
                                        {{ $purchaseOrder->updated_at->format('M d, Y h:i A') }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Value</label>
                                    <div
                                        class="w-full h-10 px-3 border border-gray-300 rounded-md text-sm bg-white flex items-center font-semibold text-[#0078C8]">
                                        {{ $purchaseOrder->currency->code ?? '' }}
                                        {{ number_format($purchaseOrder->total, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Records Table -->
                        <div class="mt-8">
                            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">
                                <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 p-5">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-xl font-semibold text-gray-800">Payment Records</h3>
                                        @php
                                            $totalPaid = $payments->sum('amount');
                                            $balance = $purchaseOrder->total - $totalPaid;
                                        @endphp
                                        <div class="flex items-center space-x-6">
                                            <div class="text-sm">
                                                <span class="text-gray-600 font-medium">Total Paid:</span>
                                                <span class="ml-2 font-bold text-green-600">
                                                    {{ $purchaseOrder->currency->code ?? '' }}
                                                    {{ number_format($totalPaid, 2) }}
                                                </span>
                                            </div>
                                            <div class="text-sm">
                                                <span class="text-gray-600 font-medium">Balance:</span>
                                                <span
                                                    class="ml-2 font-bold {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                                    {{ $purchaseOrder->currency->code ?? '' }}
                                                    {{ number_format($balance, 2) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if ($payments->count() > 0)
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">
                                                        Payment Date</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">
                                                        Account</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">
                                                        Amount</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">
                                                        Paid By</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">
                                                        Notes</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">
                                                        Recorded At</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">
                                                        Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach ($payments as $payment)
                                                    <tr class="hover:bg-gray-50 transition-colors">
                                                        <td class="px-4 py-3">
                                                            {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') : 'N/A' }}
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            {{ $payment->account->name ?? 'N/A' }}
                                                        </td>
                                                        <td class="px-4 py-3 font-medium text-green-600">
                                                            {{ $purchaseOrder->currency->code ?? '' }}
                                                            {{ number_format($payment->amount, 2) }}
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            {{ $payment->user->username ?? ($payment->user->first_name ?? 'N/A') }}
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-600">
                                                            {{ $payment->note ?? '-' }}
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-500">
                                                            {{ $payment->created_at->format('M d, Y h:i A') }}
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <button
                                                                class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-medium rounded-md transition-colors duration-200"
                                                                onclick="openPaymentModal({{ $payment->id }}, '{{ $payment->note }}', {{ $payment->account->id }}, '{{ $payment->payment_date }}')">
                                                                Edit
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-gray-50 font-semibold">
                                                <tr>
                                                    <td colspan="2" class="px-4 py-3 text-right">Total Paid:</td>
                                                    <td class="px-4 py-3 text-green-600">
                                                        {{ $purchaseOrder->currency->code ?? '' }}
                                                        {{ number_format($totalPaid, 2) }}
                                                    </td>
                                                    <td colspan="3" class="px-4 py-3 text-right">Remaining Balance:
                                                    </td>
                                                    <td
                                                        class="px-4 py-3 {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                                        {{ $purchaseOrder->currency->code ?? '' }}
                                                        {{ number_format($balance, 2) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-12">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-4 text-lg font-medium text-gray-900">No payments recorded</h3>
                                        <p class="mt-2 text-sm text-gray-500">No payment records found for this purchase
                                            order.</p>
                                    </div>
                                @endif

                                <!-- Edit Payment Modal -->
                                <div id="paymentModal"
                                    class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center backdrop-blur-sm">
                                    <div
                                        class="bg-white w-full max-w-md mx-4 rounded-xl shadow-2xl overflow-hidden animate-fadeIn">

                                        <!-- Header -->
                                        <div class="bg-[#0078C8] px-6 py-4">
                                            <h2 class="font-semibold text-xl text-white">Edit Payment</h2>
                                        </div>

                                        <!-- Body -->
                                        <div class="p-6">
                                            <input type="hidden" id="payment_id">



                                            <!-- Account Field -->
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Account</label>
                                                <x-acccount.coa.coa-select name="account_id" id="payment_account_id" />
                                            </div>



                                            <!-- Notes Field -->
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                                <textarea id="payment_note"
                                                    class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-blue-200 p-3 rounded-lg transition-all duration-200 resize-none"
                                                    rows="3" placeholder="Enter payment notes..."></textarea>
                                            </div>

                                            <!-- Loading Indicator -->
                                            <div id="payment_loading" class="hidden text-center py-4">
                                                <div
                                                    class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-200 border-t-[#0078C8]">
                                                </div>
                                                <p class="text-sm text-gray-600 mt-2">Updating payment...</p>
                                            </div>
                                        </div>

                                        <!-- Footer -->
                                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3" id="payment_footer">
                                            <button
                                                class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 font-medium"
                                                onclick="closePaymentModal()">
                                                Cancel
                                            </button>
                                            <button id="payment_save_btn"
                                                class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb5] transition-colors duration-200 font-medium shadow-sm hover:shadow-md"
                                                onclick="updatePayment()">
                                                Save Changes
                                            </button>
                                        </div>

                                    </div>
                                </div>

                                @push('scripts')
                                    <script>
                                        function openPaymentModal(id, note, accountId, paymentDate, amount) {
                                            document.getElementById("payment_id").value = id;
                                            document.getElementById("payment_note").value = note || '';
                                            document.getElementById("payment_account_id").value = accountId;



                                            document.getElementById("paymentModal").classList.remove('hidden');
                                        }

                                        function closePaymentModal() {
                                            document.getElementById("paymentModal").classList.add('hidden');
                                            // Reset footer visibility
                                            document.getElementById("payment_footer").classList.remove('hidden');
                                            document.getElementById("payment_loading").classList.add('hidden');
                                        }

                                        function updatePayment() {
                                            let id = document.getElementById("payment_id").value;

                                            // Hide footer, show loading
                                            document.getElementById("payment_footer").classList.add('hidden');
                                            document.getElementById("payment_loading").classList.remove('hidden');

                                            $.ajax({
                                               url: `/purchase-orders-quotation/payment/${id}`,
                                                type: "PUT",
                                                data: {
                                                    account_id: $("#payment_account_id").val(),
                                                    note: $("#payment_note").val(),

                                                    _token: "{{ csrf_token() }}"
                                                },
                                                success: function(res) {
                                                    if (res.success) {
                                                        Notification.success("Payment updated successfully!");

                                                        // Close modal after short delay
                                                        setTimeout(function() {
                                                            closePaymentModal();
                                                            // Reload the page to reflect changes
                                                            window.location.reload();
                                                        }, 500);
                                                    }
                                                },
                                                error: function(err) {
                                                    // Show footer, hide loading
                                                    document.getElementById("payment_footer").classList.remove('hidden');
                                                    document.getElementById("payment_loading").classList.add('hidden');

                                                    let errorMsg = err.responseJSON?.message || "Error updating payment";
                                                    Notification.error(errorMsg);
                                                }
                                            });
                                        }

                                        // Close modal when clicking outside
                                        document.getElementById('paymentModal').addEventListener('click', function(e) {
                                            if (e.target.id === 'paymentModal') {
                                                closePaymentModal();
                                            }
                                        });

                                        // Close modal with Escape key
                                        document.addEventListener('keydown', function(e) {
                                            if (e.key === 'Escape' && !document.getElementById('paymentModal').classList.contains('hidden')) {
                                                closePaymentModal();
                                            }
                                        });
                                    </script>

                                    <style>
                                        @keyframes fadeIn {
                                            from {
                                                opacity: 0;
                                                transform: scale(0.95);
                                            }

                                            to {
                                                opacity: 1;
                                                transform: scale(1);
                                            }
                                        }

                                        .animate-fadeIn {
                                            animation: fadeIn 0.2s ease-out;
                                        }
                                    </style>
                                @endpush
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-3 mt-6">
                            <a href="{{ route('account.purchase-order.edit', $purchaseOrder->id) }}"
                                class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                                EDIT PURCHASE ORDER
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
