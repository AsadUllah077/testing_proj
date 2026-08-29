 <div id="currency"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 transition-opacity duration-300">
     <div id="currencyBox"
         class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 transform transition-all duration-300 scale-95">
         <div class="border-b px-6 py-4 rounded-t-lg bg-gradient-to-r from-[#0078C8] to-[#006bb0]">
             <h2 id="currencyTitle" class="text-lg font-semibold text-white">Create Vendor</h2>
         </div>

         <form id="vendorForm" class="px-6 py-5 space-y-4">
             <div>
                 <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country <span
                         class="text-red-500">*</span></label>
                 <input type="text" id="country" name="country" placeholder="Enter Country Name" required
                     class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 rounded-md px-4 py-2.5 text-sm transition-all outline-none" />
                 <span id="nameError" class="text-xs text-red-500 hidden mt-1"></span>
             </div>

             <div>
                 <label for="iso_code" class="block text-sm font-medium text-gray-700 mb-2">ISO Code <span
                         class="text-red-500">*</span></label>
                 <input type="text" id="iso_code" name="iso_code" placeholder="Enter ISO Code" required
                     class="w-full border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 rounded-md px-4 py-2.5 text-sm transition-all outline-none" />
                 <span id="emailError" class="text-xs text-red-500 hidden mt-1"></span>
             </div>
         </form>

         <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 rounded-b-lg bg-gray-50">
             <button onclick="closeCurrencyModal()"
                 class="px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-md hover:bg-gray-100 text-sm font-medium transition-all">
                 Cancel
             </button>
             <button onclick="submitCurrencyForm()"
                 class="px-5 py-2.5 bg-[#0078C8] text-white rounded-md hover:bg-[#006bb0] text-sm font-medium transition-all shadow-md hover:shadow-lg">
                 <span id="submitBtnText">Add Vendor</span>
             </button>
         </div>
     </div>
 </div>
 @push('scripts')
     <script>
         let currencyMode = 'create';

         // Open Vendor Modal
         function openCurrencyModal(mode = 'create', data = {}) {
             currencyMode = mode;
             const modal = document.getElementById('currency');
             const modalBox = document.getElementById('currencyBox');
             const title = document.getElementById('currencyTitle');
             const submitBtn = document.getElementById('submitBtnText');
             const form = document.getElementById('vendorForm');

             // Reset form
             form.reset();
             clearErrors();

             if (mode === 'edit') {
                 title.textContent = 'Edit  Currency';
                 submitBtn.textContent = 'Update  Currency';

                 // Populate form with data
                 if (data.country) document.getElementById('country').value = data.country;
                 if (data.iso_code) document.getElementById('iso_code').value = data.iso_code;
             } else {
                 title.textContent = 'Create Currency';
                 submitBtn.textContent = 'Add Currency';
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

         // Close Vendor Modal
         function closeCurrencyModal() {
             const modal = document.getElementById('currency');
             const modalBox = document.getElementById('currencyBox');

             modal.classList.remove('opacity-100');
             modalBox.classList.remove('scale-100');
             modalBox.classList.add('scale-95');

             setTimeout(() => {
                 modal.classList.add('hidden');
                 modal.classList.remove('flex');
                 document.body.style.overflow = '';
             }, 300);
         }

         // Submit Vendor Form
         function submitCurrencyForm() {
             clearErrors();

             const name = document.getElementById('country').value.trim();
             const iso_code = document.getElementById('iso_code').value.trim();

             let isValid = true;

             // Validate Name
             if (!name) {
                 showError('nameError', 'Name is required');
                 isValid = false;
             }

             // Validate Email
             if (!iso_code) {
                 showError('emailError', 'iso_code is required');
                 isValid = false;
             }

             if (!isValid) return;

             // Success message
             const action = currencyMode === 'create' ? 'created' : 'updated';
             alert(`Currency ${action} successfully!`);
             closeCurrencyModal();
         }

         // Helper Functions
         function showError(elementId, message) {
             const errorElement = document.getElementById(elementId);
             errorElement.textContent = message;
             errorElement.classList.remove('hidden');
             errorElement.previousElementSibling.classList.add('border-red-500');
         }

         function clearErrors() {
             document.querySelectorAll('[id$="Error"]').forEach(el => {
                 el.classList.add('hidden');
                 el.textContent = '';
             });
             document.querySelectorAll('input, textarea').forEach(el => {
                 el.classList.remove('border-red-500');
             });
         }

         function isValidEmail(email) {
             return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
         }

         // Close modal on backdrop click
         document.getElementById('currency')?.addEventListener('click', function(e) {
             if (e.target === this) {
                 closeCurrencyModal();
             }
         });

         // Close modal with Escape key
         document.addEventListener('keydown', function(e) {
             if (e.key === 'Escape') {
                 const modal = document.getElementById('currency');
                 if (modal && !modal.classList.contains('hidden')) {
                     closeCurrencyModal();
                 }
             }
         });
     </script>
 @endpush
