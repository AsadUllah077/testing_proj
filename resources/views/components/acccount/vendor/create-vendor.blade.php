<div id="vendorModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 transition-opacity duration-300">
    <div id="vendorModalBox"
        class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 transform transition-all duration-300 scale-95">

        <!-- Header -->
        <div class="border-b px-6 py-4 bg-gradient-to-r from-[#0078C8] to-[#006bb0]">
            <h2 id="vendorModalTitle" class="text-lg font-semibold text-white">Create Vendor</h2>
        </div>

        <!-- Form -->
        <form id="vendorForm" class="px-6 py-5 space-y-4" onsubmit="event.preventDefault(); submitVendorForm();">
            @csrf
            <input type="hidden" id="vendorId">
            <input type="hidden" id="vendorCompanyId" value="{{ auth()->user()->company_id ?? 1 }}">

            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="vendorName" name="name" placeholder="Enter vendor name"
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm focus:ring focus:ring-blue-100" />
                <span id="nameError" class="text-xs text-red-500 hidden mt-1"></span>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email
                </label>
                <input type="email" id="vendorEmail" name="email" placeholder="Enter email"
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm focus:ring focus:ring-blue-100" />
                <span id="emailError" class="text-xs text-red-500 hidden mt-1"></span>

                <!-- Restore Button (shown when email exists and is deleted) -->
                <div id="restoreSection" class="hidden mt-2">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="text-sm text-yellow-800">This email was previously deleted</span>
                        </div>
                        <button type="button" onclick="restoreVendor()"
                            class="px-3 py-1.5 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700 transition-colors">
                            Restore
                        </button>
                    </div>
                </div>
            </div>

            <!-- Phone -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="text" id="vendorPhone" name="phone" placeholder="Enter phone"
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm" />
            </div>

            <!-- Address -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                <textarea id="vendorAddress" name="address" rows="3" placeholder="Enter full address"
                    class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment terms (days)</label>
                <input type="number" min="0" max="3650" id="vendorPaymentTerms" name="payment_terms_days" value="30" class="w-full border border-gray-300 rounded-md px-4 py-2.5 text-sm">
            </div>

            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closeVendorModal()"
                    class="px-5 py-2.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100">
                    Cancel
                </button>

                <button type="submit" id="submitBtn"
                    class="px-5 py-2.5 bg-[#0078C8] text-white rounded-md hover:bg-[#006bb0] flex items-center gap-2 min-w-[140px] justify-center">
                    <!-- Loading Spinner -->
                    <svg id="submitSpinner" class="hidden animate-spin h-4 w-4 text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span id="submitBtnText">Add Vendor</span>
                </button>
            </div>
        </form>

    </div>
</div>

@push('scripts')
    <script>
        let deletedVendorId = null;

        // ----------------------------------
        // Open Modal
        // ----------------------------------
        function openVendorModal(mode, data = {}) {
            const modal = document.getElementById("vendorModal");
            const form = document.getElementById("vendorForm");
            const title = document.getElementById("vendorModalTitle");
            const submitBtn = document.getElementById("submitBtnText");

            form.reset();
            clearErrors();
            hideRestoreSection();
            deletedVendorId = null;

            if (mode === "edit") {
                title.textContent = "Edit Vendor";
                submitBtn.textContent = "Update Vendor";

                document.getElementById("vendorId").value = data.id;
                document.getElementById("vendorName").value = data.name;
                document.getElementById("vendorEmail").value = data.email;
                document.getElementById("vendorPhone").value = data.phone ?? '';
                document.getElementById("vendorAddress").value = data.address ?? '';
                document.getElementById("vendorPaymentTerms").value = data.payment_terms_days ?? 30;
            } else {
                title.textContent = "Create Vendor";
                submitBtn.textContent = "Add Vendor";
            }

            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        // Close modal
        function closeVendorModal() {
            document.getElementById("vendorModal").classList.add("hidden");
            hideRestoreSection();
            deletedVendorId = null;
        }

        // ----------------------------------
        // Check if email exists (on blur)
        // ----------------------------------
        document.getElementById("vendorEmail").addEventListener("blur", async function() {
            const email = this.value.trim();
            const vendorId = document.getElementById("vendorId").value;

            if (!email) return;

            // Don't check if we're editing and email hasn't changed
            if (vendorId) return;

            hideRestoreSection();
            deletedVendorId = null;

            try {
                const res = await fetch(`/vendors/check-email`, {
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
                    // Email exists and vendor is soft deleted
                    deletedVendorId = json.vendor_id;
                    showRestoreSection();
                }
            } catch (err) {
                console.error("Email check error:", err);
            }
        });

        // ----------------------------------
        // Show/Hide Restore Section
        // ----------------------------------
        function showRestoreSection() {
            document.getElementById("restoreSection").classList.remove("hidden");
        }

        function hideRestoreSection() {
            document.getElementById("restoreSection").classList.add("hidden");
        }

        // ----------------------------------
        // Restore Vendor
        // ----------------------------------
        async function restoreVendor() {
            if (!deletedVendorId) return;

            setLoading(true);

            try {
                const res = await fetch(`/vendors/restore/${deletedVendorId}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    }
                });

                const json = await res.json();

                if (!res.ok) {
                    Notification.error(json.message || "Failed to restore vendor.");
                    return;
                }

                Notification.success(json.message);
                closeVendorModal();
                location.reload();

            } catch (err) {
                console.error(err);
                Notification.error("Something went wrong while restoring.");
            } finally {
                setLoading(false);
            }
        }

        // ----------------------------------
        // Submit Vendor
        // ----------------------------------
        async function submitVendorForm() {
            clearErrors();
            setLoading(true);

            const id = document.getElementById("vendorId").value;
            const company_id = document.getElementById("vendorCompanyId").value;

            const data = {
                company_id,
                name: document.getElementById("vendorName").value.trim(),
                email: document.getElementById("vendorEmail").value.trim(),
                phone: document.getElementById("vendorPhone").value.trim(),
                address: document.getElementById("vendorAddress").value.trim(),
                payment_terms_days: document.getElementById("vendorPaymentTerms").value || 30
            };

            const url = id ? `/vendors/update/${id}` : `/vendors/store`;
            const method = id ? "PUT" : "POST";

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify(data)
                });

                const json = await res.json();

                if (res.status === 422) {
                    Object.entries(json.errors).forEach(([key, msg]) => {
                        showError(`${key}Error`, msg[0]);
                    });
                    return;
                }

                if (!res.ok) {
                    Notification.error(json.message || "Failed.");
                    return;
                }

                Notification.success(json.message);
                closeVendorModal();
                location.reload();

            } catch (err) {
                console.error(err);
                Notification.error("Something went wrong.");
            } finally {
                setLoading(false);
            }
        }

        // ----------------------------------
        // Loading State
        // ----------------------------------
        function setLoading(isLoading) {
            const submitBtn = document.getElementById("submitBtn");
            const submitSpinner = document.getElementById("submitSpinner");
            const submitText = document.getElementById("submitBtnText");

            if (isLoading) {
                submitBtn.disabled = true;
                submitBtn.classList.add("opacity-75", "cursor-not-allowed");
                submitSpinner.classList.remove("hidden");
            } else {
                submitBtn.disabled = false;
                submitBtn.classList.remove("opacity-75", "cursor-not-allowed");
                submitSpinner.classList.add("hidden");
            }
        }

        // Helper
        function clearErrors() {
            document.querySelectorAll("[id$='Error']").forEach(el => {
                el.classList.add("hidden");
                el.textContent = "";
            });
        }

        function showError(id, msg) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = msg;
                el.classList.remove("hidden");
            }
        }
    </script>
@endpush
