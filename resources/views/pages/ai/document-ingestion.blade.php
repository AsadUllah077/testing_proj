@extends('layouts.ai')

@section('title', 'Document Ingestion')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="px-3 mt-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-4">

                <!-- Left side -->
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">
                        Intelligent Document Processing
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Upload invoices, receipts, or bank statements for AI-powered data extraction.
                    </p>
                </div>

                <!-- Right side -->
                <div class="mt-3 md:mt-0">
                    <a href="{{ route('dashboard') }}" class="text-sm text-white bg-[#0078C8] hover:bg-[#006bb0] py-2 px-4 rounded-md flex items-center gap-1">
                        ← Back
                    </a>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Upload Area -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h3 class="font-medium text-gray-800 text-sm">Batch Upload</h3>
                        </div>

                        <div class="p-6">
                            <form id="uploadForm" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-4">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Document Type</label>
                                    <select name="document_type" id="document_type"
                                        class="w-full py-2 px-3 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-[#0078C8] outline-none">
                                        <option value="invoice">Supplier Invoices</option>
                                        <option value="receipt">Customer Receipts</option>
                                        <option value="bank_statement">Bank Statements</option>
                                    </select>
                                </div>

                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:bg-gray-50 transition-colors"
                                    id="dropZone">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="mt-4 flex text-sm text-gray-600 justify-center">
                                        <label for="file-upload"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-[#0078C8] hover:text-[#006bb0] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-[#0078C8]">
                                            <span>Upload files</span>
                                            <input id="file-upload" name="files[]" type="file" class="sr-only" multiple
                                                accept=".pdf,.jpg,.jpeg,.png">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">PDF, PNG, JPG up to 10MB</p>
                                    <div id="fileList"
                                        class="mt-4 text-left text-xs text-gray-600 max-h-32 overflow-y-auto"></div>
                                </div>

                                <div class="mt-6">
                                    <button type="submit" id="processBtn"
                                        class="w-full bg-[#0078C8] text-white py-2 px-4 rounded text-sm font-medium hover:bg-[#006bb0] disabled:opacity-50 transition flex items-center justify-center">
                                        <span id="processText">Process Documents</span>
                                        <svg id="processSpinner" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white hidden"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Drafts Area -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm h-full">
                        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h3 class="font-medium text-gray-800 text-sm">Processing Queue</h3>
                            <span class="text-xs font-semibold px-2 py-1 bg-amber-100 text-amber-700 rounded-full"
                                id="draftCount">{{ $drafts->total() }} Records</span>
                        </div>

                        <!-- Filters Section -->
                        <form method="GET" id="filter-form">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="flex-1 min-w-[120px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">From Date</label>
                                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                                            class="date w-full py-1.5 px-3 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-[#0078C8] outline-none">
                                    </div>
                                    <div class="flex-1 min-w-[120px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">To Date</label>
                                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                                            class="date w-full py-1.5 px-3 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-[#0078C8] outline-none">
                                    </div>
                                    <div class="flex-1 min-w-[150px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                                        <select name="document_type"
                                            class="w-full py-1.5 px-3 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-[#0078C8] outline-none">
                                            <option value="">All Types</option>
                                            <option value="invoice" {{ request('document_type') == 'invoice' ? 'selected' : '' }}>Invoices</option>
                                            <option value="receipt" {{ request('document_type') == 'receipt' ? 'selected' : '' }}>Receipts</option>
                                            <option value="bank_statement" {{ request('document_type') == 'bank_statement' ? 'selected' : '' }}>Bank Statements</option>
                                        </select>
                                    </div>
                                    <div class="flex-1 min-w-[120px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                        <select name="status"
                                            class="w-full py-1.5 px-3 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-[#0078C8] outline-none">
                                            <option value="draft" {{ request('status') == 'draft' || !request('status') ? 'selected' : '' }}>Draft</option>
                                            <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Processed</option>
                                        </select>
                                    </div>
                                    <div class="flex-1 min-w-[120px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Min Amount</label>
                                        <input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}"
                                            placeholder="Min"
                                            class="w-full py-1.5 px-3 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-[#0078C8] outline-none">
                                    </div>
                                    <div class="flex-1 min-w-[120px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Max Amount</label>
                                        <input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}"
                                            placeholder="Max"
                                            class="w-full py-1.5 px-3 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-[#0078C8] outline-none">
                                    </div>
                                    <div class="flex-1 min-w-[120px]">
                                        <x-default-page />
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" id="search-btn"
                                            class="px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8] text-xs font-medium">
                                            <span id="search-text">SEARCH</span>
                                            <span id="search-loader" class="hidden ml-2">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                        <button type="button" id="reset-btn"
                                            class="px-3 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4] text-xs font-medium">
                                            RESET
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="p-0 overflow-x-auto" id="drafts-table">
                            @include('pages.ai.partials.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="closeReviewModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Review Extracted Data
                            </h3>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Left Col: File Preview (placeholder) -->
                                <div
                                    class="bg-gray-100 rounded-lg flex items-center justify-center p-4 border border-gray-200 min-h-[400px]">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500" id="previewFileName">document.pdf</p>
                                        <a id="viewFileBtn" href="#" target="_blank"
                                            class="mt-2 inline-block text-xs text-[#0078C8] hover:underline">Open Original
                                            File</a>
                                    </div>
                                </div>

                                <!-- Right Col: Form Data -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Vendor / Payee</label>
                                        <input type="text" id="reviewVendor"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Invoice/Ref No</label>
                                            <input type="text" id="reviewRef"
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Date</label>
                                            <input type="date" id="reviewDate"
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-2">Line Items & Account
                                            Mapping</label>
                                        <div class="border border-gray-200 rounded-md overflow-hidden">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                                            Description</th>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                                            Amount</th>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                                            Suggested Account</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="reviewItems" class="bg-white divide-y divide-gray-200 text-sm">
                                                    <!-- Populated by JS -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Total Amount</label>
                                        <input type="text" id="reviewTotal" readonly
                                            class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm font-semibold">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                    <button type="button" onclick="confirmDraft()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-base font-medium text-white hover:bg-[#006bb0] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm & Create Record
                    </button>
                    <button type="button" onclick="closeReviewModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/flatpicker.js') }}"></script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        flatpickr(".date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            allowInput: true,
            defaultDate: "",
            onReady: function(selectedDates, dateStr, instance) {
                instance.altInput.placeholder = "dd/mm/yyyy";
            }
        });

        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('ai.document.ingestion.index') }}',
            tableSelector: '#drafts-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        const DRAFTS = @json($drafts->keyBy('id'));
        const ACCOUNTS = @json($accounts);
        let currentReviewId = null;

        const fileInput = document.getElementById('file-upload');
        const dropZone = document.getElementById('dropZone');
        const fileList = document.getElementById('fileList');
        const uploadForm = document.getElementById('uploadForm');
        const processBtn = document.getElementById('processBtn');
        const processText = document.getElementById('processText');
        const processSpinner = document.getElementById('processSpinner');

        // Drag and drop handlers
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('border-[#0078C8]', 'bg-blue-50'),
                false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-[#0078C8]', 'bg-blue-50'),
                false);
        });

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            updateFileList();
        }

        fileInput.addEventListener('change', updateFileList);

        function updateFileList() {
            fileList.innerHTML = '';
            for (let i = 0; i < fileInput.files.length; i++) {
                const div = document.createElement('div');
                div.className = 'mt-1 text-sm text-gray-700 truncate';
                div.textContent = `📄 ${fileInput.files[i].name}`;
                fileList.appendChild(div);
            }
        }

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (fileInput.files.length === 0) {
                alert('Please select files to upload.');
                return;
            }

            const formData = new FormData(uploadForm);

            processBtn.disabled = true;
            processText.textContent = 'Processing...';
            processSpinner.classList.remove('hidden');

            try {
                const response = await fetch('{{ route('ai.document.ingestion.upload') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // In a real app we'd dynamically append to the table.
                    // For simplicity, reload to show new drafts.
                    window.location.reload();
                } else {
                    alert(result.message || 'Error processing documents.');
                }
            } catch (error) {
                console.error(error);
                alert('An error occurred during upload.');
            } finally {
                processBtn.disabled = false;
                processText.textContent = 'Process Documents';
                processSpinner.classList.add('hidden');
            }
        });

        async function deleteDraft(id) {
            if (!confirm('Are you sure you want to discard this draft?')) return;

            try {
                const response = await fetch(`/document-ingestion/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();
                if (result.success) {
                    document.getElementById(`draft-row-${id}`).remove();
                    let countEl = document.getElementById('draftCount');
                    let count = parseInt(countEl.textContent) - 1;
                    countEl.textContent = `${count} Drafts`;

                    if (count === 0) {
                        window.location.reload();
                    }
                }
            } catch (e) {
                alert('Error deleting draft');
            }
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
            currentReviewId = null;
        }

        function reviewDraft(id) {
            currentReviewId = id;
            const draft = DRAFTS[id];
            if (!draft) return;

            document.getElementById('previewFileName').textContent = draft.file_name;
            document.getElementById('viewFileBtn').href = `/storage/${draft.file_path}`;

            const data = draft.extracted_data || {};

            document.getElementById('reviewVendor').value = data.vendor_name || '';
            document.getElementById('reviewRef').value = data.invoice_no || data.ref_no || '';
            document.getElementById('reviewDate').value = data.date || '';
            document.getElementById('reviewTotal').value = data.total || data.total_amount || 0;

            const tbody = document.getElementById('reviewItems');
            tbody.innerHTML = '';

            let items = [];
            if (draft.document_type === 'bank_statement') {
                items = data.transactions || [];
            } else {
                items = data.rows || data.line_items || [];
            }

            let optionsHtml = '<option value="">Select Account...</option>';
            ACCOUNTS.forEach(acc => {
                optionsHtml += `<option value="${acc.id}">${acc.account_number} - ${acc.name}</option>`;
            });

            items.forEach((item, index) => {
                const tr = document.createElement('tr');

                let desc = item.description || '';
                let amount = item.total || item.amount || 0;
                let accId = item.account_id || '';

                tr.innerHTML = `
                <td class="px-3 py-2 whitespace-nowrap"><input type="text" value="${desc}" class="w-full border-0 bg-transparent focus:ring-0 p-0 text-sm"></td>
                <td class="px-3 py-2 whitespace-nowrap"><input type="number" step="0.01" value="${amount}" class="w-24 border-0 bg-transparent focus:ring-0 p-0 text-sm"></td>
                <td class="px-3 py-2 whitespace-nowrap">
                    <select class="w-full border-gray-300 rounded text-sm py-1">
                        ${optionsHtml}
                    </select>
                </td>
            `;

                // Set selected value if matched by AI
                if (accId) {
                    const select = tr.querySelector('select');
                    select.value = accId;
                }

                tbody.appendChild(tr);
            });

            document.getElementById('reviewModal').classList.remove('hidden');
        }

        async function confirmDraft() {
            if (!currentReviewId) return;

            // Disable button to prevent double submit
            const btn = document.querySelector('#reviewModal button.bg-\\[\\#0078C8\\]');
            const origText = btn.textContent;
            btn.textContent = 'Processing...';
            btn.disabled = true;

            try {
                const draft = DRAFTS[currentReviewId];

                // Gather data from the modal
                let payload = {
                    vendor_id: draft.extracted_data.vendor_id, // We'd want a proper vendor select in reality
                    invoice_no: document.getElementById('reviewRef').value,
                    date: document.getElementById('reviewDate').value,
                    rows: []
                };

                // If we don't have a vendor_id, try to use a default or prompt user (simplified for MVP)
                if (!payload.vendor_id) {
                    payload.vendor_id = 1; // Assuming 1 is a valid vendor ID for fallback in MVP
                }

                const trs = document.querySelectorAll('#reviewItems tr');
                trs.forEach(tr => {
                    const desc = tr.querySelector('td:nth-child(1) input').value;
                    const amount = tr.querySelector('td:nth-child(2) input').value;
                    const accId = tr.querySelector('td:nth-child(3) select').value;

                    if (desc && amount && accId) {
                        payload.rows.push({
                            description: desc,
                            amount: parseFloat(amount),
                            account_id: accId
                        });
                    }
                });

                if (payload.rows.length === 0) {
                    alert('Please ensure all rows have an account selected and valid amount.');
                    return;
                }

                const response = await fetch(`/document-ingestion/${currentReviewId}/confirm`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    // Remove draft from UI and redirect or close
                    document.getElementById(`draft-row-${currentReviewId}`).remove();
                    closeReviewModal();

                    // Show success and redirect to the created bill
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(result.message || 'Error confirming document.');
                }
            } catch (e) {
                console.error(e);
                alert('Error confirming document.');
            } finally {
                if (btn) {
                    btn.textContent = origText;
                    btn.disabled = false;
                }
            }
        }
    </script>
@endpush
