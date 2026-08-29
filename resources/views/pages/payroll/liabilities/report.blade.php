@extends('layouts.auth')

@section('title', 'Payroll Liabilities Report')

@section('content')
    <x-acccount.loader />
    <div class="min-h-screen bg-gray-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header Section -->
            <div class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">Payroll Liabilities</h1>
                            <p class="mt-1 text-sm text-gray-600">Track and manage outstanding payroll deductions</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    EXPORT
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                                    <button type="button"
                                        class="export-btn block w-full text-left px-4 py-2 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:bg-blue-50 focus:text-blue-700"
                                        onclick="return false;" data-export-type="payroll-liability"
                                        data-export-format="pdf">
                                        <i class="far fa-file-pdf mr-2 text-red-500"></i> PDF
                                    </button>
                                    <button type="button"
                                        class="export-btn block w-full text-left px-4 py-2 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:bg-blue-50 focus:text-blue-700"
                                        onclick="return false;" data-export-type="payroll-liability"
                                        data-export-format="xlsx">
                                        <i class="fas fa-file-excel mr-2 text-green-500"></i> Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <form class="grid grid-cols-1 md:grid-cols-8 gap-4" id="filter-form">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pay Item</label>
                            <select name="pay_item_id"
                                class="select2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                                <option value="">All pay items</option>
                                @foreach ($payItems as $payItem)
                                    <option value="{{ $payItem->id }}"
                                        {{ request('pay_item_id') == $payItem->id ? 'selected' : '' }}>
                                        {{ $payItem->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remittance Status</label>
                            <select name="payment_status" class="select2 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2">
                                <option value="">All</option><option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option><option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option><option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                            <select name="employee_id"
                                class="select2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                                <option value="">All employees</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}"
                                        {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select name="type"
                                class="select2 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2">
                                <option value="">All</option>
                                <option value="earning" {{ request('type') === 'earning' ? 'selected' : '' }}>Earning
                                </option>
                                <option value="deduction" {{ request('type') === 'deduction' ? 'selected' : '' }}>Deduction
                                </option>
                                <option value="reimbursement" {{ request('type') === 'reimbursement' ? 'selected' : '' }}>
                                    Reimbursement
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pay Run Status</label>
                            <select name="status"
                                class="select2 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2">
                                <option value="">All</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted
                                    (Unpaid)</option>
                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>
                                    Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                class="date block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                class="date block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border px-3 py-2">
                        </div>

                        <div>
                            <x-default-page />
                        </div>

                        <div class="flex items-end space-x-2">
                            <button type="button" id="search-btn"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[#0078C8] hover:bg-[#0078C8]/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <span id="search-text">
                                    <i class="fas fa-search mr-2"></i>
                                    Search
                                </span>
                                <span id="search-loader" class="hidden ml-2">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                            <button type="button" id="reset-btn"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Content Section -->
            <div id="liabilities-table">
                @include('pages.payroll.liabilities.partials.liabilities-report-table')
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Function to toggle dropdown visibility
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
            }
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // Initialize ReportFilter for AJAX filtering
        ReportFilter.init({
            url: '{{ route('payroll.liabilities.report') }}',
            summarySelector: '#liabilities-summary',
            tableSelector: '#liabilities-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        // Handle payroll liability export buttons
        document.addEventListener('DOMContentLoaded', function() {
            const exportButtons = document.querySelectorAll('.export-btn');

            exportButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const exportType = this.getAttribute('data-export-type');
                    const exportFormat = this.getAttribute('data-export-format');

                    // Close dropdown
                    document.getElementById('exportDropdown').classList.add('hidden');

                    // Get current filter values
                    const formData = new FormData(document.getElementById('filter-form'));
                    const filterData = {
                        format: exportFormat,
                        type: exportType,
                        status: formData.get('status'),
                        payment_status: formData.get('payment_status'),
                        pay_item_id: formData.get('pay_item_id'),
                        pay_run_id: formData.get('pay_run_id'),
                        employee_id: formData.get('employee_id'),
                        date_from: formData.get('date_from'),
                        date_to: formData.get('date_to')
                    };

                    // Show loading notification
                    showExportNotification('Starting export...', 'info');

                    // Make AJAX request to start export
                    fetch('{{ route('export') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(filterData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Start polling for progress
                                pollExportProgress(data.job_id);
                            } else {
                                showExportNotification('Export failed: ' + data.message,
                                    'error');
                            }
                        })
                        .catch(error => {
                            showExportNotification('Failed to start export: ' + error.message,
                                'error');
                        });
                });
            });
        });

        function pollExportProgress(jobId) {
            const checkProgress = () => {
                fetch('{{ route('export.progress') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            job_id: jobId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.progress) {
                            if (data.progress.percentage >= 100) {
                                showExportNotification('Export completed! Downloading...', 'success');
                                if (data.result && data.result.url) {
                                    window.open(data.result.url, '_blank');
                                }
                            } else if (data.progress.percentage < 0) {
                                showExportNotification('Export failed: ' + data.progress.message, 'error');
                            } else {
                                showExportNotification(data.progress.message, 'info');
                                setTimeout(checkProgress, 1000); // Check again in 1 second
                            }
                        } else {
                            setTimeout(checkProgress, 1000);
                        }
                    })
                    .catch(error => {
                        showExportNotification('Error checking progress: ' + error.message, 'error');
                    });
            };

            checkProgress();
        }

        function showExportNotification(message, type = 'info') {
            // Remove existing notification
            const existingNotification = document.getElementById('export-notification');
            if (existingNotification) {
                existingNotification.remove();
            }

            // Create notification element
            const notification = document.createElement('div');
            notification.id = 'export-notification';
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg max-w-sm ${
                type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' :
                type === 'error' ? 'bg-red-100 border border-red-400 text-red-700' :
                'bg-blue-100 border border-blue-400 text-blue-700'
            }`;

            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas ${
                            type === 'success' ? 'fa-check-circle' :
                            type === 'error' ? 'fa-exclamation-circle' :
                            'fa-info-circle'
                        }"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 3000);
            }
        }
    </script>
@endsection
