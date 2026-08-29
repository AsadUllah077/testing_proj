<div id="customerModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 transition-opacity duration-300">
    <div id="customerModalBox"
        class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 transform transition-all duration-300 scale-95">
        <!-- Header -->
        <div class="border-b px-6 py-4 bg-gradient-to-r from-[#0078C8] to-[#006bb0]">
            <h2 id="customerModalTitle" class="text-lg font-semibold text-white">Create Customer</h2>
        </div>

        <!-- Form -->
        <form id="CustomerForm" class="px-6 py-5 space-y-4" onsubmit="submitCustomerForm(event)">
            @csrf
            <input type="hidden" id="customerId" name="id">
            <input type="hidden" id="companyId" name="company_id" value="{{ loggedCompanyId()['company_id'] }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name <span
                        class="text-red-500">*</span></label>
                <input type="text" id="vendorName" name="name" placeholder="Enter customer name" required
                    class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 rounded-md px-4 py-2.5 text-sm transition-all outline-none" />
                <span id="nameError" class="text-xs text-red-500 hidden mt-1"></span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" id="vendorEmail" name="email" placeholder="Enter email address"
                    class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 rounded-md px-4 py-2.5 text-sm transition-all outline-none" />
                <span id="emailError" class="text-xs text-red-500 hidden mt-1"></span>

                <!-- Restore Section (shown when email exists and is deleted) -->
                <div id="restoreCustomerSection" class="hidden mt-2">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="text-sm text-yellow-800">This email was previously deleted</span>
                        </div>
                        <button type="button" onclick="restoreCustomer()"
                            class="px-3 py-1.5 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700 transition-colors flex items-center gap-2">
                            <svg id="restoreBtnSpinner" class="hidden animate-spin h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span id="restoreBtnText">Restore</span>
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="tel" id="vendorPhone" name="phone" placeholder="Enter phone number"
                    class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 rounded-md px-4 py-2.5 text-sm transition-all outline-none" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                <textarea id="vendorAddress" name="address" rows="3" placeholder="Enter full address"
                    class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 rounded-md px-4 py-2.5 text-sm transition-all outline-none resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Credit Limit</label>
                    <input type="number" min="0" step="0.01" id="customerCreditLimit" name="credit_limit" class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm" placeholder="No limit">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Terms (days)</label>
                    <input type="number" min="0" max="3650" id="customerPaymentTerms" name="payment_terms_days" value="30" class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closeCustomerModal()"
                    class="px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-md hover:bg-gray-100 text-sm font-medium transition-all">
                    Cancel
                </button>
                <button type="submit" id="customerSubmitBtn"
                    class="px-5 py-2.5 flex items-center justify-center gap-2 bg-[#0078C8] text-white rounded-md hover:bg-[#006bb0] text-sm font-medium transition-all shadow-md hover:shadow-lg min-w-[140px]">
                    <!-- Loading Spinner -->
                    <svg id="submitBtnLoader" class="hidden animate-spin h-4 w-4 text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span id="submitBtnText">Add Customer</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        let deletedCustomerId = null;
        let customerModalMode = 'create';

        // ----------------------------------
        // Loading Functions
        // ----------------------------------
        function startLoading() {
            const submitBtn = document.getElementById("customerSubmitBtn");
            const loader = document.getElementById("submitBtnLoader");
            const text = document.getElementById("submitBtnText");

            loader.classList.remove("hidden");
            submitBtn.disabled = true;
            submitBtn.classList.add("opacity-70", "cursor-not-allowed");
        }

        function stopLoading() {
            const submitBtn = document.getElementById("customerSubmitBtn");
            const loader = document.getElementById("submitBtnLoader");
            const text = document.getElementById("submitBtnText");

            loader.classList.add("hidden");
            submitBtn.disabled = false;
            submitBtn.classList.remove("opacity-70", "cursor-not-allowed");
        }

        function setRestoreLoading(isLoading) {
            const spinner = document.getElementById("restoreBtnSpinner");
            const text = document.getElementById("restoreBtnText");
            const restoreBtn = event?.target?.closest('button');

            if (isLoading) {
                spinner.classList.remove("hidden");
                if (restoreBtn) {
                    restoreBtn.disabled = true;
                    restoreBtn.classList.add("opacity-70", "cursor-not-allowed");
                }
            } else {
                spinner.classList.add("hidden");
                if (restoreBtn) {
                    restoreBtn.disabled = false;
                    restoreBtn.classList.remove("opacity-70", "cursor-not-allowed");
                }
            }
        }

        // ----------------------------------
        // Open/Close Modal
        // ----------------------------------
        function openCustomerModal(mode = 'create', data = {}) {
            customerModalMode = mode;
            const modal = document.getElementById('customerModal');
            const modalBox = document.getElementById('customerModalBox');
            const title = document.getElementById('customerModalTitle');
            const submitBtn = document.getElementById('submitBtnText');
            const form = document.getElementById('CustomerForm');

            form.reset();
            clearErrors();
            hideRestoreCustomerSection();
            deletedCustomerId = null;

            if (mode === 'edit') {
                title.textContent = 'Edit Customer';
                submitBtn.textContent = 'Update Customer';
                document.getElementById('customerId').value = data.id ?? '';
                document.getElementById('vendorName').value = data.name ?? '';
                document.getElementById('vendorEmail').value = data.email ?? '';
                document.getElementById('vendorPhone').value = data.phone ?? '';
                document.getElementById('vendorAddress').value = data.address ?? '';
                document.getElementById('customerCreditLimit').value = data.credit_limit ?? '';
                document.getElementById('customerPaymentTerms').value = data.payment_terms_days ?? 30;
            } else {
                title.textContent = 'Create Customer';
                submitBtn.textContent = 'Add Customer';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            setTimeout(() => {
                modal.classList.add('opacity-100');
                modalBox.classList.remove('scale-95');
                modalBox.classList.add('scale-100');
            }, 10);

            document.body.style.overflow = 'hidden';
        }

        function closeCustomerModal() {
            const modal = document.getElementById('customerModal');
            const modalBox = document.getElementById('customerModalBox');

            modal.classList.remove('opacity-100');
            modalBox.classList.remove('scale-100');
            modalBox.classList.add('scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }, 300);

            hideRestoreCustomerSection();
            deletedCustomerId = null;
        }

        // ----------------------------------
        // Email Check (on blur)
        // ----------------------------------
        document.getElementById("vendorEmail").addEventListener("blur", async function () {
            const email = this.value.trim();
            const customerId = document.getElementById("customerId").value;

            if (!email) return;

            // Don't check if we're editing
            if (customerId) return;

            hideRestoreCustomerSection();
            deletedCustomerId = null;

            try {
                const res = await fetch('/customers/check-email', {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        email
                    })
                });

                const json = await res.json();

                if (json.exists && json.deleted) {
                    // Email exists and customer is soft deleted
                    deletedCustomerId = json.customer_id;
                    showRestoreCustomerSection();
                }
            } catch (err) {
                Notification.error(err.message);
            }
        });

        // ----------------------------------
        // Show/Hide Restore Section
        // ----------------------------------
        function showRestoreCustomerSection() {
            document.getElementById("restoreCustomerSection").classList.remove("hidden");
        }

        function hideRestoreCustomerSection() {
            document.getElementById("restoreCustomerSection").classList.add("hidden");
        }

        // ----------------------------------
        // Restore Customer
        // ----------------------------------
        async function restoreCustomer() {
            if (!deletedCustomerId) return;

            setRestoreLoading(true);

            try {
                const res = await fetch(`/customers/restore/${deletedCustomerId}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    }
                });

                const json = await res.json();

                if (!res.ok) {
                    Notification.error(json.message || "Failed to restore customer.");
                    return;
                }

                Notification.success(json.message);
                closeCustomerModal();
                location.reload();

            } catch (err) {
                console.error(err);
                Notification.error("Something went wrong while restoring.");
            } finally {
                setRestoreLoading(false);
            }
        }

        // ----------------------------------
        // Submit Customer Form
        // ----------------------------------
        async function submitCustomerForm(event) {
            event.preventDefault();
            clearErrors();
            startLoading();

            const id = document.getElementById('customerId').value;
            const company_id = document.getElementById('companyId').value;
            const name = document.getElementById('vendorName').value.trim();
            const email = document.getElementById('vendorEmail').value.trim();
            const phone = document.getElementById('vendorPhone').value.trim();
            const address = document.getElementById('vendorAddress').value.trim();

            const data = {
                company_id,
                name,
                email,
                phone,
                address
            };

            const url = customerModalMode === 'create' ?
                '{{ route('account.sale.customers.store') }}' :
                '{{ url('customers/update') }}/' + id;

            const method = customerModalMode === 'create' ? 'POST' : 'PUT';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(data),
                });

                const response = await res.json();

                if (response.success) {
                    Notification.success(response.message);
                    closeCustomerModal();
                    location.reload();
                } else if (res.status === 422) {
                    Object.entries(response.errors).forEach(([field, messages]) => {
                        showError(`${field}Error`, messages[0]);
                        Notification.error(messages);
                    });
                } else {
                    Notification.error(response.message);
                }
            } catch (error) {
                console.error(error);
                alert('An error occurred. Please try again.');
            } finally {
                stopLoading();
            }
        }

        // ----------------------------------
        // Helper Functions
        // ----------------------------------
        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            if (!errorElement) return;
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
            const input = errorElement.previousElementSibling;
            if (input && input.tagName === 'INPUT' || input.tagName === 'TEXTAREA') {
                input.classList.add('border-red-500');
            }
        }

        function clearErrors() {
            document.querySelectorAll('[id$="Error"]').forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });
            document.querySelectorAll('#CustomerForm input, #CustomerForm textarea').forEach(el => {
                el.classList.remove('border-red-500');
            });
        }
    </script>
@endpush
