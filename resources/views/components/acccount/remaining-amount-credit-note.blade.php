<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-gray-600/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Refund Customer</h3>
            <button type="button" onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <form id="paymentForm" method="POST">
            @csrf
            <input type="hidden" name="credit_note_id" id="credit_note_id">

            <!-- Invoice Info -->
            <div class="mb-4 p-3 bg-gray-50 rounded">
                <p class="text-sm text-gray-600">Invoice No: <span id="modal_invoice_no"
                        class="font-semibold text-gray-900"></span></p>
                <p class="text-sm text-gray-600">Customer: <span id="modal_customer_name"
                        class="font-semibold text-gray-900"></span></p>
                <p class="text-sm text-gray-600">Remaining: <span id="modal_remaining_amount"
                        class="font-semibold text-red-600"></span></p>
            </div>

            <!-- Date Field -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Refund Date <span
                        class="text-red-500">*</span></label>
                <input type="date" name="payment_date" id="payment_date" required
                    class="date w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none"
                    value="{{ date('Y-m-d') }}">
                <span class="text-red-500 text-xs error-message" id="error_payment_date"></span>
            </div>

            <!-- Amount Field -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Refund Amount <span
                        class="text-red-500">*</span></label>
                <input type="number" name="amount" id="payment_amount" step="0.01" min="0.01" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none"
                    placeholder="Enter payment amount">
                <span class="text-red-500 text-xs error-message" id="error_amount"></span>
            </div>

            <!-- Account Field -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bank / Cash Account <span
                        class="text-red-500">*</span></label>
                <x-chart-account-bank-select name="account_id" :selected="old('account_id')" id="account_id" />
                <span class="text-red-500 text-xs error-message" id="error_account_id"></span>
            </div>

            <!-- Description Field -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="payment_description" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none"
                    placeholder="Enter payment description (optional)"></textarea>
                <span class="text-red-500 text-xs error-message" id="error_description"></span>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closePaymentModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="submitPaymentBtn"
                    class="px-4 py-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-colors">
                    <span id="submitBtnText">Refund Customer</span>
                    <span id="submitBtnLoader" class="hidden">Processing...</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        let currentSaleData = {};

        function openPaymentModal(saleId, invoiceNo, customerName, remainingAmount) {
            // Store sale data
            currentSaleData = {
                id: saleId,
                invoice_no: invoiceNo,
                customer_name: customerName,
                remaining_amount: remainingAmount
            };

            // Populate modal
            document.getElementById('credit_note_id').value = saleId;
            
            document.getElementById('modal_invoice_no').textContent = invoiceNo;
            document.getElementById('modal_customer_name').textContent = customerName;
            document.getElementById('modal_remaining_amount').textContent = remainingAmount;
            document.getElementById('payment_amount').setAttribute('max', remainingAmount);

            // Reset form
            document.getElementById('paymentForm').reset();
            document.getElementById('credit_note_id').value = saleId;
            document.getElementById('payment_date').value = '{{ date('Y-m-d') }}';

            // Clear errors
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

            // Show modal
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.getElementById('paymentForm').reset();
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        }

        // Handle form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitPaymentBtn');
            const btnText = document.getElementById('submitBtnText');
            const btnLoader = document.getElementById('submitBtnLoader');

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

            // Disable button and show loader
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            btnLoader.classList.remove('hidden');

            // Validate amount doesn't exceed remaining
            const amount = parseFloat(formData.get('amount'));
            const remaining = parseFloat(currentSaleData.remaining_amount);

            if (amount > remaining) {
                document.getElementById('error_amount').textContent = 'Amount cannot exceed remaining amount';
                submitBtn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoader.classList.add('hidden');
                return;
            }

            // Submit payment
            fetch('{{ route('account.sale.credit-notes.record-payment') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        Notification.success(data.message);

                        // Close modal
                        closePaymentModal();

                        // Reload page to show updated remaining amount
                        window.location.reload();
                    } else {
                        // Show validation errors
                        if (data.errors) {
                            Object.keys(data.errors).forEach(key => {
                                const errorEl = document.getElementById('error_' + key);
                                if (errorEl) {
                                    errorEl.textContent = data.errors[key][0];
                                }
                            });
                        } else {
                            Notification.error(data.message || 'An error occurred');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Notification.error('An error occurred while processing payment');
                })
                .finally(() => {
                    // Re-enable button
                    submitBtn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnLoader.classList.add('hidden');
                });
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaymentModal();
            }
        });

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>
@endpush
