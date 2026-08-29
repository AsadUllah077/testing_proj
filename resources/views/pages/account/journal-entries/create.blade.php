@extends('layouts.auth')

@section('title', 'Create Journal Entry')

@section('content')
    <div class="mb-8">
        <div class="p-2">
            <div class="">
                <div class="bg-white border border-gray-300 rounded-md">
                    <!-- Header -->
                    <div class="p-5 border-b border-gray-200 flex rounded-md justify-between items-center">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">{{ $replacesUniqueNo ? 'Correct Journal Entry' : 'Journal Entries' }}</h2>
                            @if ($replacesUniqueNo)
                                <p class="text-sm text-amber-700">Reversing and replacing {{ $replacesUniqueNo }}. All original lines were copied automatically.</p>
                            @endif
                        </div>
                        <a href="{{ route('account.journal-entries.index') }}"
                            class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg">
                            ← BACK
                        </a>
                    </div>
                </div>
                <div class="bg-white border border-gray-300 rounded-lg my-4">
                    <!-- Form -->
                    <form id="formSubmit" action_url="{{ route('account.journal-entries.store') }}"
                        back_url="{{ route('account.journal-entries.index') }}" method="POST" enctype="multipart/form-data"
                        class="p-6">
                        @csrf
                        @if ($replacesUniqueNo)
                            <input type="hidden" name="replaces_unique_no" value="{{ $replacesUniqueNo }}">
                            <input type="hidden" name="reversal_already_posted" value="{{ $reversalAlreadyPosted ? '1' : '0' }}">
                            <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <label class="block text-sm font-medium text-amber-900 mb-1">Correction Reason <span class="text-red-500">*</span></label>
                                <input name="correction_reason" value="{{ old('correction_reason') }}" required maxlength="1000"
                                    placeholder="Explain the incorrect date, amount, or account"
                                    class="w-full h-10 px-3 border border-amber-300 outline-none rounded-md text-sm">
                                <p class="mt-2 text-xs text-amber-800">
                                    {{ $reversalAlreadyPosted ? 'The reversal already exists; saving posts only the copied replacement.' : 'Saving posts the reversal and replacement together. If either fails, neither is retained.' }}
                                </p>
                            </div>
                        @endif

                        <!-- Error Alert -->
                        <div id="error-alert" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
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
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
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
                                <input type="text" name="narration" placeholder="Narration"
                                    value="{{ old('narration', $copiedNarration) }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                <span class="text-red-500 text-xs error-message hidden" id="narration-error"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="date" required placeholder="" value="{{ old('date', $copiedDate) }}"
                                    class="date w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                <span class="text-red-500 text-xs error-message hidden" id="date-error"></span>
                            </div>
                        </div>

                        @include('components.duplicate-warning', [
                            'duplicateCheckType' => 'journal_entry',
                            'duplicateCheckUrl'  => route('account.check-duplicates'),
                        ])

                        <!-- Table Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6">
                            <div class="overflow-x-auto">
                                <table id="invoice-table" class="w-full text-sm border-collapse">
                                    <thead class=" border-b-2 border-gray-300 text-gray-700">
                                        <tr>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-4 column-item font-semibold">
                                                Item</th>
                                            <th
                                                class="border-r border-gray-300 px-3 w-4 py-3 text-left column-description font-semibold">
                                                Description</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-4 column-qty font-semibold">
                                                Account</th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-4 column-kg font-semibold">
                                                Debit </th>
                                            <th
                                                class="border-r border-gray-300 px-3 py-3 text-left w-4 column-price font-semibold">
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

                            <div class="border border-gray-200 rounded-lg p-4 w-full">
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
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
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
        const accounts = @json($accounts);
        const copiedRows = @json($copiedEntries ?? []);

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
            if (copiedRows.length) {
                copiedRows.forEach(function (data) {
                    const row = addNewRow();
                    row.querySelector('input[name$="[item]"]').value = data.item ?? '';
                    row.querySelector('input[name$="[description]"]').value = data.description ?? '';
                    $(row.querySelector('.row-account')).val(String(data.account_id)).trigger('change');
                    row.querySelector('.row-debit').value = data.debit ?? 0;
                    row.querySelector('.row-credit').value = data.credit ?? 0;
                });
                updateTotals();
            } else {
                addNewRow();
            }
            loadColumnVisibility();

            document.getElementById('add-new-line').addEventListener('click', addNewRow);

            document.querySelectorAll('.column-toggle').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleColumnVisibility(this.dataset.column, this.checked);
                    saveColumnVisibility();
                });
            });
        });

        // Function to add a new row
        function addNewRow() {
            const tbody = document.getElementById('invoice-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'border-b border-gray-200 hover:bg-blue-50 transition-colors';
            newRow.dataset.rowIndex = rowIndex;

            newRow.innerHTML = `
            <td class="border-r border-gray-300 px-2 py-2 column-item">
                <input type="text" name="rows[${rowIndex}][item]" placeholder="Item name"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                <span class="text-red-500 text-xs error-message hidden" id="rows-${rowIndex}-item-error"></span>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 column-description">
                <input type="text" name="rows[${rowIndex}][description]" placeholder="Description"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                <span class="text-red-500 text-xs error-message hidden" id="rows-${rowIndex}-description-error"></span>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-qty">
                <select name="rows[${rowIndex}][account_id]"
                    class="select2 w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm
                    focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-account">
                    ${generateAccountOptions()}
                </select>

                <span class="text-red-500 text-xs error-message hidden"
                    id="rows-${rowIndex}-account_id-error"></span>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-center column-kg">
                <input type="number" min="0" name="rows[${rowIndex}][debit]" step="0.01" value="0" placeholder="0"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-debit">
                <span class="text-red-500 text-xs error-message hidden" id="rows-${rowIndex}-debit-error"></span>
            </td>
            <td class="border-r border-gray-300 px-2 py-2 text-right column-price">
                <input type="number" min="0" name="rows[${rowIndex}][credit]" step="0.01" value="0" placeholder="0.00"
                    class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-right focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all row-credit">
                <span class="text-red-500 text-xs error-message hidden" id="rows-${rowIndex}-credit-error"></span>
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
            $('.select2').select2({
                width: '100%'
            });

            // AI account suggestions
            if (typeof window.attachAccountSuggestion === 'function') {
                window.attachAccountSuggestion(newRow);
            }

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
                    Notification.error("You need at least one row in the table.")
                }
            });

            rowIndex++;
            return newRow;
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

            // Update button state based on balance
            const submitButton = document.getElementById('submit-button');
            if (totalDebit.toFixed(2) === totalCredit.toFixed(2)) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
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
        updateTotals();
    </script>
    @include('components.account-suggestion')

    <style>
        .error-message {
            display: none;
        }

        .error-message:not(.hidden) {
            display: block;
        }
    </style>
@endsection
