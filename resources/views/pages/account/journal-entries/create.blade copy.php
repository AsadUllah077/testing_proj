@extends('layouts.auth')

@section('title', 'Create Bill')

@section('content')
    <div class="mb-8">
        <div class="p-2">
            <div class="">
                <div class="bg-white border border-gray-300 rounded-md shadow-sm">
                    <!-- Header -->
                    <div
                        class="p-5 border-b border-gray-200 flex rounded-md justify-between items-center bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="text-xl font-semibold text-gray-800">Journal Entries</h2>
                        <a href="{{ route('account.journal-entries.index') }}"
                            class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg">
                            ← BACK
                        </a>
                    </div>
                </div>
                <div class="bg-white border border-gray-300 rounded-lg shadow-sm my-4">
                    <!-- Form -->
                    <form id="journal-entry-form" action="{{route('account.journal-entries.store')}}" method="POST" enctype="multipart/form-data" class="p-6">
                        @csrf

                        <!-- Error Alert -->
                        <div id="error-alert" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 id="error-message" class="text-sm font-medium text-red-800"></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Success Alert -->
                        <div id="success-alert" class="hidden mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 id="success-message" class="text-sm font-medium text-green-800"></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Narration</label>
                                <input type="text" name="narration" placeholder="Narration" value="{{ old('narration') }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                <span class="text-red-500 text-xs error-message hidden" id="narration-error"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="date" required placeholder="" value="{{ old('date') }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                <span class="text-red-500 text-xs error-message hidden" id="date-error"></span>
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6 shadow-sm">
                            <div class="overflow-x-auto">
                                <table id="invoice-table" class="w-full text-sm border-collapse">
                                    <thead
                                        class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 text-gray-700">
                                        <tr>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-24 column-item font-semibold">
                                                Item</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left column-description font-semibold">
                                                Description</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-center w-20 column-qty font-semibold">
                                                Account</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-center w-20 column-kg font-semibold">
                                                Debit </th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-right w-24 column-price font-semibold">
                                                Credit </th>
                                            <th class="px-2 py-3 w-12 text-center font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-table-body" class="bg-white">
                                        <!-- Rows will be added dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Buttons and Totals -->
                        <div class="grid grid-cols-1 md:grid-cols-2 mb-6 gap-4">
                            <div class="flex items-start gap-3">
                                <button type="button" id="add-new-line"
                                    class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    ADD NEW LINE
                                </button>

                            </div>

                            <div
                                class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-lg p-4 shadow-sm w-full">
                                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                                    <div class="text-left text-gray-600 font-medium">Total Debit:</div>
                                    <div id="subtotal" class="text-left font-semibold text-gray-800">0.00</div>
                                    <div class="text-left text-gray-600 font-medium">Total Credit:</div>
                                    <div id="tax-total" class="text-left font-semibold text-gray-800">0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex justify-end gap-3">
                            <button type="reset"
                                class="px-6 py-2 border-2 border-[#0078C8] text-[#0078C8] hover:text-white text-sm font-medium rounded-md hover:bg-[#0078C8] bg-[#0078C8]/10 transition-all">
                                RESET
                            </button>
                            <button type="submit" id="submit-button"
                                class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                SAVE & APPROVE
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        let rowIndex = 0;

        // Function to toggle dropdown visibility
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('columnDropdown');
            const button = document.querySelector('button[onclick="toggleDropdown(\'columnDropdown\')"]');

            if (dropdown && !dropdown.contains(event.target) && button && !button.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Initialize the table with one row
        document.addEventListener('DOMContentLoaded', function() {
            addNewRow();
            loadColumnVisibility();

            document.getElementById('add-new-line').addEventListener('click', addNewRow);

            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleColumnVisibility(this.dataset.column, this.checked);
                    saveColumnVisibility();
                });
            });

            // AJAX form submission
            document.getElementById('journal-entry-form').addEventListener('submit', function(e) {
                e.preventDefault();
                submitForm();
            });
        });

        // Function to add a new row
        function addNewRow() {
            const tbody = document.getElementById('invoice-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'border-b border-gray-200 hover:bg-blue-50 transition-colors';
            newRow.dataset.rowIndex = rowIndex++;

            newRow.innerHTML = `
            <td class="border-r border-gray-300 px-2 py-2 column-item">
                <input type="text" name="rows[${rowIndex}][item]" placeholder="Item name"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                <span class="text-red-500 text-xs error-message hidden" id="item-${rowIndex}-error"></span>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 column-description">
                <input type="text" name="rows[${rowIndex}][description]" placeholder="Description"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                <span class="text-red-500 text-xs error-message hidden" id="description-${rowIndex}-error"></span>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-qty">
                <input type="number" min="0" name="rows[${rowIndex}][account_id]" step="1" value="0" placeholder="0"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-qty">
                <span class="text-red-500 text-xs error-message hidden" id="account_id-${rowIndex}-error"></span>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-kg">
                <input type="number" min="0" name="rows[${rowIndex}][debit]" step="0.01" value="0" placeholder="0"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-debit">
                <span class="text-red-500 text-xs error-message hidden" id="debit-${rowIndex}-error"></span>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-price">
                <input type="number" min="0" name="rows[${rowIndex}][credit]" step="0.01" value="0" placeholder="0.00"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-right focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-credit">
                <span class="text-red-500 text-xs error-message hidden" id="credit-${rowIndex}-error"></span>
            </td>
            <td class="px-2 py-2 text-center">
                <button type="button" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-md transition-all delete-row" title="Delete Row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" />
                    </svg>
                </button>
            </td>
        `;

            tbody.appendChild(newRow);

            // Add calculation listeners
            const debitInput = newRow.querySelector('.row-debit');
            const creditInput = newRow.querySelector('.row-credit');

            function updateRowTotals() {
                updateTotals();
            }

            debitInput.addEventListener('input', updateRowTotals);
            creditInput.addEventListener('input', updateRowTotals);

            // Delete row handler
            newRow.querySelector('.delete-row').addEventListener('click', function() {
                if (tbody.children.length > 1) {
                    newRow.remove();
                    updateTotals();
                } else {
                    alert('You need at least one row in the table.');
                }
            });
        }

        // Update all totals
        function updateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;

            document.querySelectorAll('#invoice-table-body tr').forEach(row => {
                const debit = parseFloat(row.querySelector('.row-debit')?.value) || 0;
                const credit = parseFloat(row.querySelector('.row-credit')?.value) || 0;

                totalDebit += debit;
                totalCredit += credit;
            });

            document.getElementById('subtotal').textContent = totalDebit.toFixed(2);
            document.getElementById('tax-total').textContent = totalCredit.toFixed(2);
        }

        // Function to toggle column visibility
        function toggleColumnVisibility(column, show) {
            const elements = document.querySelectorAll(`.column-${column}`);

            elements.forEach(el => {
                el.style.display = show ? '' : 'none';
            });

            const checkbox = document.querySelector(`.column-toggle[data-column="${column}"]`);
            if (checkbox) {
                checkbox.checked = show;
            }
        }

        // Function to save column visibility to localStorage
        function saveColumnVisibility() {
            const visibility = {};
            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                visibility[checkbox.dataset.column] = checkbox.checked;
            });
            localStorage.setItem('billColumnVisibility', JSON.stringify(visibility));
        }

        // Function to load column visibility from localStorage
        function loadColumnVisibility() {
            const savedVisibility = localStorage.getItem('billColumnVisibility');
            if (savedVisibility) {
                const visibility = JSON.parse(savedVisibility);
                for (const column in visibility) {
                    toggleColumnVisibility(column, visibility[column]);
                }
            }
        }

        // AJAX Form Submission
        function submitForm() {
            // Show loading state
            const submitButton = document.getElementById('submit-button');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;
            submitButton.disabled = true;

            // Hide previous alerts
            document.getElementById('error-alert').classList.add('hidden');
            document.getElementById('success-alert').classList.add('hidden');
            
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });

            // Prepare form data
            const form = document.getElementById('journal-entry-form');
            const formData = new FormData(form);

            // Send AJAX request
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show success message
                    document.getElementById('success-message').textContent = data.message;
                    document.getElementById('success-alert').classList.remove('hidden');
                    
                    // Optionally redirect or reset form
                    setTimeout(() => {
                        window.location.href = "{{ route('account.journal-entries.index') }}";
                    }, 2000);
                } else {
                    // Show error message
                    document.getElementById('error-message').textContent = data.message;
                    document.getElementById('error-alert').classList.remove('hidden');
                    
                    // Display field-specific errors if available
                    if (data.errors) {
                        for (const field in data.errors) {
                            const errorElement = document.getElementById(`${field}-error`);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field][0];
                                errorElement.classList.remove('hidden');
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('error-message').textContent = 'An unexpected error occurred. Please try again.';
                document.getElementById('error-alert').classList.remove('hidden');
            })
            .finally(() => {
                // Restore button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            });
        }
    </script>
@endsection