@extends('layouts.auth')

@section('title', 'Edit Journal Entry')

@section('content')
    <div class="mb-8">
        <div class="p-2">
            <div class="">
                <div class="bg-white border border-gray-300 rounded-md">
                    <!-- Header -->
                    <div class="p-5 border-b border-gray-200 flex rounded-md justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Edit Journal Entry</h2>
                        <a href="{{ route('account.journal-entries.index') }}"
                            class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg">
                            ← BACK
                        </a>
                    </div>
                </div>
                <div class="bg-white border border-gray-300 rounded-lg my-4">
                    <!-- Form -->
                    <form id="formSubmit" back_url="{{ route('account.journal-entries.index') }}"
                        action_url="{{ route('account.journal-entries.update', $journal_entry->unique_no) }}" method="POST"
                        enctype="multipart/form-data" class="p-6">
                        @csrf
                        @method('PUT')

                        <!-- Error Alert -->
                        @if ($errors->any())
                            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">
                                            There were {{ $errors->count() }} errors with your submission
                                        </h3>
                                        <div class="mt-2 text-sm text-red-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Success Alert -->
                        @if (session('success'))
                            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
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
                                        <h3 class="text-sm font-medium text-green-800">{{ session('success') }}</h3>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Top Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Narration</label>
                                <input type="text" name="narration" placeholder="Narration"
                                    value="{{ old('narration', $journal_entry->narration) }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                @error('narration')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date <span
                                        class="text-red-500">*</span></label>
                                <input class="date" type="date" name="date" required
                                    value="{{ old('date', $journal_entry->date->format('Y-m-d')) }}"
                                    class="w-full h-10 px-3 border border-gray-300 outline-none rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                @error('date')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm border-collapse">
                                    <thead class="border-b-2 border-gray-300 text-gray-700">
                                        <tr>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left">Item</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left">Description</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left">Account</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left">Debit</th>
                                            <th class="border-r border-gray-300 px-3 py-3 text-left">Credit</th>
                                            <th class="px-2 py-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-table-body" class="bg-white">
                                        @foreach ($entries as $index => $entry)
                                            <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors">
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <input type="text" name="rows[{{ $index }}][item]"
                                                        value="{{ old('rows.' . $index . '.item', $entry->item) }}"
                                                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <input type="text" name="rows[{{ $index }}][description]"
                                                        value="{{ old('rows.' . $index . '.description', $entry->description) }}"
                                                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <select name="rows[{{ $index }}][account_id]"
                                                        class="select2 w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                                                        <option value="">Select Account</option>
                                                        @foreach ($accounts as $account)
                                                            <option value="{{ $account->id }}"
                                                                {{ old('rows.' . $index . '.account_id', $entry->account_id) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_number }} - {{ $account->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <input type="number" min="0"
                                                        name="rows[{{ $index }}][debit]" step="0.01"
                                                        value="{{ old('rows.' . $index . '.debit', $entry->debit) }}"
                                                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center row-debit">
                                                </td>
                                                <td class="border-r border-gray-300 px-2 py-2">
                                                    <input type="number" min="0"
                                                        name="rows[{{ $index }}][credit]" step="0.01"
                                                        value="{{ old('rows.' . $index . '.credit', $entry->credit) }}"
                                                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center row-credit">
                                                </td>
                                                <td class="px-2 py-2 text-center">
                                                    <button type="button"
                                                        class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-md transition-all remove-row">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                            class="w-5 h-5">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Add New Row Button -->
                        <div class="mb-6">
                            <button type="button" id="add-new-line"
                                class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                ADD NEW LINE
                            </button>
                        </div>

                        <!-- Buttons and Totals -->
                        <div class="grid grid-cols-1 md:grid-cols-2 mb-6 gap-4">
                            <div></div>
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
                            <a href="{{ route('account.journal-entries.index') }}"
                                class="px-6 py-2 border-2 border-[#0078C8] text-[#0078C8] hover:text-white text-sm font-medium rounded-md hover:bg-[#0078C8] bg-[#0078C8]/10 transition-all">
                                CANCEL
                            </a>
                            <button type="submit" id="submit-button"
                                class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                UPDATE ENTRY
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let rowIndex = {{ count($entries) }};
        const accounts = @json($accounts);

        document.getElementById('add-new-line').addEventListener('click', function() {
            const tbody = document.getElementById('invoice-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'border-b border-gray-200 hover:bg-blue-50 transition-colors';

            newRow.innerHTML = `
                <td class="border-r border-gray-300 px-2 py-2">
                    <input type="text" name="rows[${rowIndex}][item]"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                </td>
                <td class="border-r border-gray-300 px-2 py-2">
                    <input type="text" name="rows[${rowIndex}][description]"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                </td>
                <td class="border-r border-gray-300 px-2 py-2">
                    <select name="rows[${rowIndex}][account_id]"
                        class="select2 w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all">
                        ${generateAccountOptions()}
                    </select>
                </td>
                <td class="border-r border-gray-300 px-2 py-2">
                    <input type="number" min="0" name="rows[${rowIndex}][debit]" step="0.01" value="0"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center row-debit">
                </td>
                <td class="border-r border-gray-300 px-2 py-2">
                    <input type="number" min="0" name="rows[${rowIndex}][credit]" step="0.01" value="0"
                        class="w-full h-9 px-2 outline-none border border-gray-200 rounded-md text-sm text-center row-credit">
                </td>
                <td class="px-2 py-2 text-center">
                    <button type="button" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-md transition-all remove-row">
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
            // Add event listeners for the new row
            const debitInput = newRow.querySelector('.row-debit');
            const creditInput = newRow.querySelector('.row-credit');
            const removeBtn = newRow.querySelector('.remove-row');

            debitInput.addEventListener('input', updateTotals);
            creditInput.addEventListener('input', updateTotals);

            removeBtn.addEventListener('click', function() {
                if (tbody.children.length > 1) {
                    newRow.remove();
                    updateTotals();
                } else {
                    Notification.error('You need at least one row in the table.');
                }
            });

            rowIndex++;
        });

        // Add event listeners for existing remove buttons
        document.querySelectorAll('.remove-row').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const tbody = document.getElementById('invoice-table-body');
                if (tbody.children.length > 1) {
                    row.remove();
                    updateTotals();
                } else {
                    Notification.error('You need at least one row in the table.')
                }
            });
        });

        function updateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;

            document.querySelectorAll('#invoice-table-body tr').forEach(row => {
                const debit = parseFloat(row.querySelector('.row-debit')?.value) || 0;
                const credit = parseFloat(row.querySelector('.row-credit')?.value) || 0;
                totalDebit += debit;
                totalCredit += credit;
            });

            const balance = totalDebit - totalCredit;

            document.getElementById('subtotal').textContent = totalDebit.toFixed(2);
            document.getElementById('tax-total').textContent = totalCredit.toFixed(2);
            document.getElementById('balance').textContent = balance.toFixed(2);

            // Update button state
            const submitButton = document.getElementById('submit-button');
            if (totalDebit.toFixed(2) === totalCredit.toFixed(2)) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        // Initialize totals and add event listeners to existing inputs
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.row-debit, .row-credit').forEach(input => {
                input.addEventListener('input', updateTotals);
            });
            updateTotals();
        });

        // // Form validation
        // document.getElementById('journal-entry-form').addEventListener('submit', function(e) {
        //     const totalDebit = parseFloat(document.getElementById('subtotal').textContent) || 0;
        //     const totalCredit = parseFloat(document.getElementById('tax-total').textContent) || 0;

        //     if (totalDebit.toFixed(2) !== totalCredit.toFixed(2)) {
        //         e.preventDefault();
        //         Notification.error('Total Debit and Total Credit must be EQUAL before saving!');
        //         return false;
        //     }
        // });
    </script>
@endsection
