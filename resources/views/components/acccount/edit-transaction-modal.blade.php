<!-- Edit Transaction Modal -->
<div id="txnModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white w-full max-w-md mx-4 rounded-xl shadow-2xl overflow-hidden animate-fadeIn">

        <!-- Header -->
        <div class="bg-[#0078C8] px-6 py-4">
            <h2 class="font-semibold text-xl text-white">Edit Transaction</h2>
        </div>

        <!-- Body -->
        <div class="p-6">
            <input type="hidden" id="txn_id">

            <!-- Account Field -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Account</label>
                <x-acccount.coa.coa-select name="account_id" id="txn_account_id" />
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input type="date" name="date" id="txn_date"
                    class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-blue-200 p-3 rounded-lg transition-all duration-200 resize-none date">
            </div>

            <!-- Description Field -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea id="txn_description"
                    class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-blue-200 p-3 rounded-lg transition-all duration-200 resize-none"
                    rows="4" placeholder="Enter transaction description..."></textarea>
            </div>

            <!-- Loading Indicator -->
            <div id="txn_loading" class="hidden text-center py-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-200 border-t-[#0078C8]">
                </div>
                <p class="text-sm text-gray-600 mt-2">Updating transaction...</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3" id="txn_footer">
            <button
                class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 font-medium"
                onclick="closeTxnModal()">
                Cancel
            </button>
            <button id="txn_save_btn"
                class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb5] transition-colors duration-200 font-medium shadow-sm hover:shadow-md"
                onclick="updateTransaction()">
                Save Changes
            </button>
        </div>

    </div>
</div>

@push('scripts')
    <script>
        function openTxnModal(id, description, accountId, date) {
            document.getElementById("txn_id").value = id;
            document.getElementById("txn_description").value = description;
            document.getElementById("txn_account_id").value = accountId;
            document.getElementById("txn_date").value = date;
            document.getElementById("txnModal").classList.remove('hidden');
        }

        function closeTxnModal() {
            document.getElementById("txnModal").classList.add('hidden');
            // Reset footer visibility
            document.getElementById("txn_footer").classList.remove('hidden');
            document.getElementById("txn_loading").classList.add('hidden');
        }

        function updateTransaction() {
            let id = document.getElementById("txn_id").value;

            // Hide footer, show loading
            document.getElementById("txn_footer").classList.add('hidden');
            document.getElementById("txn_loading").classList.remove('hidden');

            $.ajax({
                url: "/account/sale/transaction/" + id,
                type: "PUT",
                data: {
                    account_id: $("#txn_account_id").val(),
                    date: $("#txn_date").val(),
                    description: $("#txn_description").val(),
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    if (res.success) {
                        Notification.success("Transaction updated successfully!");

                        // Close modal after short delay
                        setTimeout(function() {
                            closeTxnModal();
                            // Reload the page to reflect changes
                            window.location.reload();
                        }, 500);
                    }
                },
                error: function(err) {
                    // Show footer, hide loading
                    document.getElementById("txn_footer").classList.remove('hidden');
                    document.getElementById("txn_loading").classList.add('hidden');

                    let errorMsg = err.responseJSON?.message || "Error updating transaction";
                    Notification.error(errorMsg);
                }
            });
        }
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
