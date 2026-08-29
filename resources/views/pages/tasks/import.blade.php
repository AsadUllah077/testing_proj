@extends('layouts.auth')

@section('title', 'Import Tasks')

@section('content')
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg text-black">Bulk Import Tasks</h2>
                            <a href="{{ route('tasks.index') }}"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Back to Tasks
                            </a>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column - Instructions & Template -->
                        <div class="lg:col-span-1">
                            <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                                <h3 class="text-md font-medium text-gray-800 mb-4">Instructions</h3>
                                <div class="space-y-3 text-sm text-gray-600">
                                    <div class="flex items-start gap-2">
                                        <div
                                            class="w-6 h-6 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                            1</div>
                                        <p>Download the Excel template using the button below.</p>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <div
                                            class="w-6 h-6 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                            2</div>
                                        <p>Fill in your task data following the format guidelines.</p>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <div
                                            class="w-6 h-6 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                            3</div>
                                        <p>Do not modify the column headers or remove any columns.</p>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <div
                                            class="w-6 h-6 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                            4</div>
                                        <p>Required fields: Title, Deadline, Priority, Category.</p>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <div
                                            class="w-6 h-6 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                            5</div>
                                        <p>Save your Excel file and upload it using the form on the right.</p>
                                    </div>
                                </div>

                                <div class="mt-6 pt-6 border-t border-gray-100">
                                    <a href="{{ route('tasks.download-template') }}"
                                        class="flex items-center justify-center gap-2 px-4 py-3 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#0078C8] transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Download Excel Template
                                    </a>
                                    <p class="text-xs text-gray-500 mt-2 text-center">Download the pre-formatted Excel
                                        template with instructions</p>
                                </div>
                            </div>

                            <!-- Format Guidelines -->
                            <!-- Format Guidelines -->
                            <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                                <h3 class="text-md font-medium text-gray-800 mb-4">Format Guidelines</h3>
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-1">Deadline Format</h4>
                                        <p class="text-sm text-gray-600">Use: YYYY-MM-DD HH:MM:SS</p>
                                        <p class="text-xs text-gray-500">Example: 2024-12-31 17:00:00</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-1">Priority Values</h4>
                                        <div class="flex gap-2">
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">low</span>
                                            <span
                                                class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">medium</span>
                                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">high</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-1">Category Values</h4>
                                        <div class="flex gap-2">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">daily</span>
                                            <span
                                                class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">weekly</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-1">Employee Full Names</h4>
                                        <p class="text-sm text-gray-600">Separate multiple names with commas</p>
                                        <p class="text-xs text-gray-500">Example: John Doe,Jane Smith,Alex Johnson</p>
                                        <p class="text-xs text-red-500 mt-1">Note: Names must match exactly with employee
                                            records</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Upload Form -->
                        <div class="lg:col-span-2">
                            <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6">
                                <h3 class="text-md font-medium text-gray-800 mb-4">Upload Excel File</h3>

                                <!-- Upload Form -->
                                <form id="uploadForm" enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-6">
                                        <label class="block text-sm text-gray-600 mb-1">Select Excel File *</label>
                                        <div
                                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                            <div class="space-y-1 text-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor"
                                                    fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                    <path
                                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <div class="flex text-sm text-gray-600">
                                                    <label for="excel_file"
                                                        class="relative cursor-pointer bg-white rounded-md font-medium text-[#0078C8] hover:text-[#006bb0] focus-within:outline-none">
                                                        <span>Upload a file</span>
                                                        <input id="excel_file" name="excel_file" type="file"
                                                            accept=".xlsx,.xls,.csv" class="sr-only" required>
                                                    </label>
                                                    <p class="pl-1">or drag and drop</p>
                                                </div>
                                                <p class="text-xs text-gray-500">Excel files only (XLSX, XLS, CSV) up to
                                                    10MB</p>
                                            </div>
                                        </div>
                                        <div id="fileInfo" class="mt-2 text-sm text-gray-600 hidden"></div>
                                        <div id="fileError" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    <!-- Preview Section (Hidden by default) -->
                                    <div id="previewSection" class="mb-6 hidden">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">File Preview (First 5 rows)</h4>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                                                <thead id="previewHeaders" class="bg-gray-50">
                                                    <!-- Headers will be populated by JS -->
                                                </thead>
                                                <tbody id="previewBody" class="bg-white divide-y divide-gray-200">
                                                    <!-- Rows will be populated by JS -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <div id="previewInfo" class="mt-2 text-sm text-gray-600"></div>
                                    </div>

                                    <!-- Validation Results -->
                                    <div id="validationResults" class="hidden mb-6">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Validation Results</h4>
                                        <div id="validationSuccess"
                                            class="p-3 bg-green-50 border border-green-200 rounded-md hidden">
                                            <div class="flex items-center gap-2 text-green-800">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                <span id="validationSuccessText"></span>
                                            </div>
                                        </div>
                                        <div id="validationError"
                                            class="p-3 bg-red-50 border border-red-200 rounded-md hidden">
                                            <div class="flex items-center gap-2 text-red-800">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                <span id="validationErrorText"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Import Results (Will be shown after import) -->
                                    <div id="importResults" class="hidden mb-6">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Import Results</h4>
                                        <div class="space-y-3">
                                            <div class="grid grid-cols-3 gap-4">
                                                <div class="bg-blue-50 p-3 rounded-md border border-blue-100">
                                                    <div class="text-2xl font-bold text-blue-700" id="totalRows">0</div>
                                                    <div class="text-sm text-blue-600">Total Rows</div>
                                                </div>
                                                <div class="bg-green-50 p-3 rounded-md border border-green-100">
                                                    <div class="text-2xl font-bold text-green-700" id="successRows">0
                                                    </div>
                                                    <div class="text-sm text-green-600">Successful</div>
                                                </div>
                                                <div class="bg-red-50 p-3 rounded-md border border-red-100">
                                                    <div class="text-2xl font-bold text-red-700" id="failedRows">0</div>
                                                    <div class="text-sm text-red-600">Failed</div>
                                                </div>
                                            </div>

                                            <!-- Error List (if any) -->
                                            <div id="errorList" class="hidden">
                                                <h5 class="text-sm font-medium text-gray-700 mb-2">Errors Details:</h5>
                                                <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                                                    <table class="min-w-full divide-y divide-gray-200">
                                                        <thead class="bg-gray-50">
                                                            <tr>
                                                                <th
                                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                                                    Row</th>
                                                                <th
                                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                                                    Error</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="errorRows" class="bg-white divide-y divide-gray-200">
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Successful Tasks -->
                                            <div id="successList" class="hidden">
                                                <h5 class="text-sm font-medium text-gray-700 mb-2">Imported Tasks:</h5>
                                                <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                                                    <table class="min-w-full divide-y divide-gray-200">
                                                        <thead class="bg-gray-50">
                                                            <tr>
                                                                <th
                                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                                                    Row</th>
                                                                <th
                                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                                                    Task ID</th>
                                                                <th
                                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500">
                                                                    Title</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="successRowsList"
                                                            class="bg-white divide-y divide-gray-200">
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                                        <button type="button" onclick="window.location.href='{{ route('tasks.index') }}'"
                                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                            CANCEL
                                        </button>
                                        <button type="button" id="validateBtn"
                                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#0078C8] disabled:opacity-50 disabled:cursor-not-allowed">
                                            VALIDATE FILE
                                        </button>
                                        <button type="button" id="importBtn" disabled
                                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] disabled:opacity-50 disabled:cursor-not-allowed">
                                            IMPORT TASKS
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
            <div class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#0078C8] mb-4"></div>
                <p class="text-gray-700 font-medium" id="loadingText">Processing...</p>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let fileData = null;
            let validationPassed = false;
            let selectedFile = null;

            // File input change handler
            $('#excel_file').on('change', function(e) {
                const file = e.target.files[0];
                selectedFile = file;

                if (file) {
                    // Validate file type
                    const allowedTypes = ['application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'
                    ];
                    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/)) {
                        showError('Invalid file type. Please upload an Excel file.');
                        resetForm();
                        return;
                    }

                    // Validate file size (10MB max)
                    if (file.size > 10 * 1024 * 1024) {
                        showError('File size exceeds 10MB limit.');
                        resetForm();
                        return;
                    }

                    // Show file info
                    $('#fileInfo').html(`
                <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-md">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">${file.name}</span>
                    <span class="text-gray-500">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </div>
            `).removeClass('hidden');

                    $('#fileError').text('');
                    $('#validateBtn').prop('disabled', false);
                    resetResults();
                }
            });

            // Validate button click
            $('#validateBtn').on('click', function() {
                if (!selectedFile) {
                    showError('Please select a file first.');
                    return;
                }

                validateFile();
            });

            // Import button click
            $('#importBtn').on('click', function() {
                if (!validationPassed || !selectedFile) {
                    showError('Please validate the file first.');
                    return;
                }

                importTasks();
            });

            // Drag and drop functionality
            const dropArea = $('.border-dashed').parent();

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.on(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.on(eventName, function() {
                    $(this).addClass('border-[#0078C8] bg-blue-50');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.on(eventName, function() {
                    $(this).removeClass('border-[#0078C8] bg-blue-50');
                });
            });

            dropArea.on('drop', function(e) {
                const file = e.originalEvent.dataTransfer.files[0];
                $('#excel_file').prop('files', e.originalEvent.dataTransfer.files);
                $('#excel_file').trigger('change');
            });

            function validateFile() {
                const formData = new FormData();
                formData.append('excel_file', selectedFile);
                formData.append('_token', '{{ csrf_token() }}');

                showLoading('Validating file...');

                $.ajax({
                    url: '{{ route('tasks.validate-import') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            validationPassed = response.headers_valid;
                            fileData = response;

                            // Show preview
                            showPreview(response);

                            // Show validation results
                            $('#validationResults').removeClass('hidden');

                            if (response.headers_valid) {
                                $('#validationSuccess').removeClass('hidden');
                                $('#validationSuccessText').text(
                                    `File validated successfully! ${response.total_rows} rows found.`
                                );
                                $('#importBtn').prop('disabled', false);
                            } else {
                                $('#validationError').removeClass('hidden');
                                let errorMsg =
                                    'Invalid file format. The following headers are missing or incorrect: ';
                                let invalidHeaders = [];
                                $.each(response.header_validation, function(header, valid) {
                                    if (!valid) invalidHeaders.push(header);
                                });
                                errorMsg += invalidHeaders.join(', ');
                                $('#validationErrorText').text(errorMsg);
                            }
                        } else {
                            showError(response.message || 'Validation failed.');
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        const error = xhr.responseJSON?.message ||
                            'An error occurred during validation.';
                        showError(error);
                    }
                });
            }

            function importTasks() {
                const formData = new FormData();
                formData.append('excel_file', selectedFile);
                formData.append('_token', '{{ csrf_token() }}');

                showLoading('Importing tasks...');

                $.ajax({
                    url: '{{ route('tasks.process-import') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            showImportResults(response.data);
                            showToast('success', 'Tasks imported successfully!');
                        } else {
                            showError(response.message || 'Import failed.');
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        const error = xhr.responseJSON?.message || 'An error occurred during import.';
                        showError(error);
                    }
                });
            }

            function showPreview(data) {
                if (!data.preview || data.preview.length === 0) {
                    $('#previewSection').addClass('hidden');
                    return;
                }

                // Clear previous preview
                $('#previewHeaders').empty();
                $('#previewBody').empty();

                // Show preview section
                $('#previewSection').removeClass('hidden');

                // Set preview info
                $('#previewInfo').text(
                    `Showing first ${data.preview.length} rows of ${data.total_rows} total rows.`);

                // Use actual headers from the file
                const headers = data.actual_headers || [
                    'Title', 'Description', 'Deadline', 'Priority', 'Category',
                    'Department Name', 'Employee Emails', 'Manager Remarks'
                ];

                let headersHtml = '<tr>';
                headers.forEach(header => {
                    headersHtml +=
                        `<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">${header}</th>`;
                });
                headersHtml += '</tr>';
                $('#previewHeaders').html(headersHtml);

                // Create rows
                let rowsHtml = '';
                data.preview.forEach((row, index) => {
                    rowsHtml += '<tr>';
                    headers.forEach((header, colIndex) => {
                        let cellValue = row[colIndex] || '';

                        // Handle Excel dates (Excel serial numbers)
                        if (typeof cellValue === 'number' && cellValue > 25569) {
                            try {
                                // Excel serial number to date conversion
                                const excelEpoch = new Date(1899, 11, 30);
                                const millisecondsPerDay = 24 * 60 * 60 * 1000;
                                const date = new Date(excelEpoch.getTime() + cellValue *
                                    millisecondsPerDay);
                                cellValue = date.toLocaleString();
                            } catch (e) {
                                // Keep original value if conversion fails
                            }
                        }

                        // Handle date strings
                        if (typeof cellValue === 'string' && !isNaN(Date.parse(cellValue))) {
                            try {
                                const date = new Date(cellValue);
                                if (!isNaN(date.getTime())) {
                                    cellValue = date.toLocaleString();
                                }
                            } catch (e) {
                                // Keep original value
                            }
                        }

                        // Truncate long text
                        if (typeof cellValue === 'string' && cellValue.length > 30) {
                            cellValue = cellValue.substring(0, 30) + '...';
                        }

                        rowsHtml +=
                            `<td class="px-3 py-2 text-xs text-gray-600 border border-gray-100">${cellValue}</td>`;
                    });
                    rowsHtml += '</tr>';
                });
                $('#previewBody').html(rowsHtml);
            }

            // Update validation success/error messages
            function validateFile() {
                const formData = new FormData();
                formData.append('excel_file', selectedFile);
                formData.append('_token', '{{ csrf_token() }}');

                showLoading('Validating file...');

                $.ajax({
                    url: '{{ route('tasks.validate-import') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            validationPassed = response.headers_valid;
                            fileData = response;

                            // Show preview
                            showPreview(response);

                            // Show validation results
                            $('#validationResults').removeClass('hidden');

                            if (response.headers_valid) {
                                $('#validationSuccess').removeClass('hidden');
                                $('#validationSuccessText').text(
                                    `File validated successfully! ${response.total_rows} rows found.`
                                );
                                $('#importBtn').prop('disabled', false);
                            } else {
                                $('#validationError').removeClass('hidden');
                                let errorMsg = 'Missing headers: ' + response.missing_headers.join(
                                    ', ');
                                $('#validationErrorText').text(errorMsg);

                                // Also show what headers were found
                                $('#validationErrorText').append('<br><br>Found headers: ' + response
                                    .actual_headers.join(', '));
                            }
                        } else {
                            showError(response.message || 'Validation failed.');
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        const error = xhr.responseJSON?.message ||
                            'An error occurred during validation.';
                        showError(error);
                    }
                });
            }

            function showImportResults(data) {
                $('#importResults').removeClass('hidden');

                // Update counters
                $('#totalRows').text(data.total);
                $('#successRows').text(data.success);
                $('#failedRows').text(data.failed);

                // Show errors if any
                if (data.errors && data.errors.length > 0) {
                    $('#errorList').removeClass('hidden');
                    let errorsHtml = '';
                    data.errors.forEach(error => {
                        errorsHtml += `
                    <tr>
                        <td class="px-3 py-2 text-xs text-gray-700">${error.row}</td>
                        <td class="px-3 py-2 text-xs text-red-600">
                            ${error.errors.join(', ')}
                        </td>
                    </tr>
                `;
                    });
                    $('#errorRows').html(errorsHtml);
                }

                // Show successful imports if any
                if (data.successful_rows && data.successful_rows.length > 0) {
                    $('#successList').removeClass('hidden');
                    let successHtml = '';
                    data.successful_rows.forEach(row => {
                        successHtml += `
                    <tr>
                        <td class="px-3 py-2 text-xs text-gray-700">${row.row}</td>
                        <td class="px-3 py-2 text-xs text-gray-700">${row.task_id}</td>
                        <td class="px-3 py-2 text-xs text-gray-700">${row.title}</td>
                    </tr>
                `;
                    });
                    $('#successRowsList').html(successHtml);
                }

                // Disable import button after successful import
                $('#importBtn').prop('disabled', true);
                $('#validateBtn').prop('disabled', true);
            }

            function resetForm() {
                $('#excel_file').val('');
                $('#fileInfo').addClass('hidden');
                $('#previewSection').addClass('hidden');
                $('#validationResults').addClass('hidden');
                $('#importResults').addClass('hidden');
                $('#validateBtn').prop('disabled', true);
                $('#importBtn').prop('disabled', true);
                validationPassed = false;
                fileData = null;
                selectedFile = null;
            }

            function resetResults() {
                $('#validationResults').addClass('hidden');
                $('#importResults').addClass('hidden');
                $('#validationSuccess').addClass('hidden');
                $('#validationError').addClass('hidden');
                $('#errorList').addClass('hidden');
                $('#successList').addClass('hidden');
                $('#importBtn').prop('disabled', true);
                validationPassed = false;
            }

            function showLoading(text) {
                $('#loadingText').text(text);
                $('#loadingModal').removeClass('hidden');
            }

            function hideLoading() {
                $('#loadingModal').addClass('hidden');
            }

            function showError(message) {
                showToast('error', message);
            }

            function showToast(type, message) {
                const toast = $('#toast');
                const content = $('#toast-content');

                // Set color based on type
                if (type === 'error') {
                    content.html(`
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span>${message}</span>
                </div>
            `);
                } else {
                    content.html(`
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>${message}</span>
                </div>
            `);
                }

                toast.removeClass('hidden');

                // Auto hide after 5 seconds
                setTimeout(() => {
                    toast.addClass('hidden');
                }, 5000);
            }
        });
    </script>
@endpush
