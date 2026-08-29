<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    @php
        $companyFavicon = auth()->check() && auth()->user()->company?->favicon
            ? asset('storage/' . auth()->user()->company->favicon)
            : null;
    @endphp
    @if($companyFavicon)
        <link rel="icon" type="image/x-icon" href="{{ $companyFavicon }}">
        <link rel="shortcut icon" href="{{ $companyFavicon }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/qms-design.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        *::-webkit-scrollbar {
            display: none;
        }

        /* === SELECT2 CUSTOM BRAND STYLES === */

        /* Dropdown background */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0078C8 !important;
            /* Your brand color */
            color: #ffffff !important;
            /* White text for contrast */
        }

        /* Option when selected */
        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: #0078C8 !important;
            color: #ffffff !important;
        }

        /* Option hover (when navigating with mouse) */
        .select2-container--default .select2-results__option--highlighted {
            background-color: #0078C8 !important;
            color: #ffffff !important;
        }

        /* Search field focus inside dropdown */
        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #0078C8 !important;
            box-shadow: 0 0 0 2px rgba(0, 120, 200, 0.25) !important;
        }

        /* Dropdown border and shadow */
        .select2-container--default .select2-dropdown {
            border: 1px solid #0078C8 !important;
            box-shadow: 0px 3px 8px rgba(0, 120, 200, 0.15);
        }


        .select2-container .select2-selection--single {
            height: 40px !important;
            /* 👈 Adjust height here */
            padding: 8px 12px !important;
            /* Extra padding for vertical centering */
            display: flex !important;
            align-items: center !important;
        }

        .select2-container--default .select2-selection__rendered {
            line-height: 28px !important;
            /* Keeps text vertically centered */
            font-size: 15px;
        }

        .select2-container--default .select2-selection__arrow {
            height: 40px !important;
            /* Match height with box */
        }
    </style>

    @stack('styles')
</head>

<body class="bg-[#F2F3F4] {{ Request::segment(1) == 'qms' ? 'qms-theme' : '' }}">
    <div class="flex mb-5 overflow-hidden">
        <!-- Sidebar -->
        <x-aside />

        <!-- Main Content -->
        <div class="min-w-0 flex-1 flex flex-col overflow-hidden lg:ml-64">
            <!-- Top Navigation -->
            <x-header />

            <!-- Dashboard Content -->
            <main class="min-w-0 flex-1 overflow-y-auto p-1">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    {{-- Global Notifications Container --}}
    <div id="notificationContainer" class="fixed top-5 right-5 z-[10000] space-y-3 w-full max-w-sm"></div>

    <!-- End Alert Error -->

    <!-- Corner Export Progress Notification -->
    <div id="exportNotification" class="hidden fixed bottom-4 right-4 z-50 w-80 md:w-96">
        <div
            class="bg-white rounded-lg shadow-2xl border border-gray-200 overflow-hidden transform transition-all duration-300">
            <!-- Header -->
            <div class="bg-linear-to-r from-[#0078C8] to-[#006bb0] p-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg id="exportSpinner" class="w-5 h-5 text-white animate-spin" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    <h3 class="text-sm font-semibold text-white">Exporting Data</h3>
                </div>
                <button onclick="minimizeNotification()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div id="exportNotificationContent" class="p-4">
                <!-- Progress Bar -->
                <div class="mb-3">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-medium text-gray-700">Progress</span>
                        <span id="notificationProgressPercentage" class="text-xs font-semibold text-[#0078C8]">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div id="notificationProgressBar"
                            class="bg-linear-to-r from-[#0078C8] to-[#006bb0] h-2 rounded-full transition-all duration-300 ease-out"
                            style="width: 0%"></div>
                    </div>
                </div>

                <!-- Status Message -->
                <p id="notificationProgressMessage" class="text-xs text-gray-600 mb-3">
                    Initializing export...
                </p>

                <!-- Download Button (Hidden initially) -->
                <div id="notificationDownloadSection" class="hidden">
                    <a id="notificationDownloadLink" href="#" download
                        class="w-full bg-linear-to-r from-green-500 to-green-600 text-white text-center py-2 px-4 rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 font-medium text-sm shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download File
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimized Notification Badge -->
    <div id="exportNotificationBadge" class="hidden fixed bottom-4 right-4 z-50">
        <button onclick="expandNotification()"
            class="bg-linear-to-r from-[#0078C8] to-[#006bb0] text-white rounded-full p-3 shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            <span id="badgePercentage" class="text-xs font-semibold">0%</span>
        </button>
    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <!-- Include Alpine.js once (if not already added) -->
    <script src="{{ asset('assets/js/jquery.js') }}"></script>
    <script src="{{ asset('assets/js/form-submit.js') }}"></script>
    <script src="{{ asset('assets/toastr/toastr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/js/flatpicker.js') }}"></script>
    <script src="{{ asset('assets/js/message.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Global Session Notifications
            @if (session()->has('success'))
                Notification.success("{{ session('success') }}");
            @endif

            @if (session()->has('error'))
                Notification.error("{{ session('error') }}");
            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    Notification.error("{!! addslashes($error) !!}");
                @endforeach
            @endif
        });
    </script>

    <script>
        // $(document).ready(function() {
        //     $('.select2').select2({
        //         width: '100%',
        //         height: '100%',
        //         placeholder: 'select an option',
        //         allowClear: true
        //     });
        // });

        $('select.select2').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    width: '100%',
                    height: '100%',
                    allowClear: true
                });
            }
        });

        flatpickr(".date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            allowInput: true,
            defaultDate: "",
            onReady: function(selectedDates, dateStr, instance) {
                instance.altInput.placeholder = "dd/mm/yyyy"; // <-- Add placeholder
            }
        });

        function formatHumanDate(dateStr) {
            const date = new Date(dateStr);
            const options = {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            };
            return date.toLocaleDateString('en-US', options);
        }
    </script>

    <script>
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });

        // Close sidebar when clicking on a link (mobile only)
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const pendingExport = localStorage.getItem('pendingExportJobId');
            const exportCompleted = localStorage.getItem('exportCompleted');

            if (pendingExport) {
                currentJobId = pendingExport;

                // If completed already → do NOT restart checking
                if (exportCompleted === 'true') {
                    showNotification();
                    updateNotificationProgress(100, 'Export completed successfully!');
                    document.getElementById('notificationDownloadSection').classList.remove('hidden');
                    return;
                }

                // If not completed → resume checking
                showNotification();
                checkExportProgress();
            }
        });


        let exportCheckInterval;
        let currentJobId;
        let isMinimized = false;

        /**
         * Start export process
         */
        function startExport(format, type) {
            console.log(format);

            // Collect all data from #filter-form if it exists
            const filterForm = document.getElementById('filter-form');
            let formData = {};
            if (filterForm) {
                const fd = new FormData(filterForm);
                for (let [key, value] of fd.entries()) {
                    formData[key] = value;
                }
            }

            // Collect selected IDs if any
            let selectedIds = [];
            if (typeof window.getExportSelectedIds === 'function') {
                selectedIds = Array.from(window.getExportSelectedIds(type) || []);
            } else if (type === 'warehouse-products' && typeof window.getWarehouseProductSelectedIds === 'function') {
                selectedIds = Array.from(window.getWarehouseProductSelectedIds() || []);
            }

            if (selectedIds.length === 0) {
                selectedIds = Array.from(document.querySelectorAll('.rowCheck:checked')).map(cb => cb.value);
            }

            if (selectedIds.length > 0) {
                formData['ids'] = selectedIds.join(',');
            }

            // Fallback for some hardcoded elements if they aren't in the form
            const fallbackData = {
                date_from: document.getElementById('from_date') ? document.getElementById('from_date').value : null,
                date_to: document.getElementById('to_date') ? document.getElementById('to_date').value : null,
                department_id: document.getElementById('department_id') ? document.getElementById('department_id').value : null,
                status: document.getElementById('statusFilter') ? document.getElementById('statusFilter').value : null,
                employee_id: document.getElementById('employee_id') ? document.getElementById('employee_id').value : null,
                leave_type_id: document.getElementById('leave_type_id') ? document.getElementById('leave_type_id').value : null,
                individual_employee_id: document.getElementById('selectedEmployeeId') ? document.getElementById('selectedEmployeeId').value : null,
                duration: document.getElementById('exportDuration') ? document.getElementById('exportDuration').value : null
            };

            // Merge formData with fallbackData (formData takes precedence)
            const finalData = Object.assign({}, fallbackData, formData);

            // Close dropdown
            const exportDropdown = document.getElementById('exportDropdown');
            if (exportDropdown) {
                exportDropdown.classList.add('hidden');
            }

            // Get current search value (might be in formData already if it has name="q" or "search")
            let searchElement = document.getElementById('search-q') ||
                document.querySelector('input[name="search"]') ||
                document.querySelector('input[name="q"]');
            let searchValue = searchElement ? searchElement.value : (finalData.q || finalData.search || '');

            // Show notification
            showNotification();
            resetNotificationProgress();

            // Make AJAX request to start export
            fetch('{{ route('export') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(Object.assign({
                        format: format,
                        search: searchValue,
                        type: type
                    }, finalData))
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentJobId = data.job_id;
                        checkExportProgress();
                    } else {
                        showNotificationError(data.message);
                    }
                })
                .catch(error => {
                    showNotificationError('Failed to start export: ' + error.message);
                });
        }

        /**
         * Check export progress
         */
        function checkExportProgress() {
            exportCheckInterval = setInterval(() => {
                fetch('{{ route('export.progress') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            job_id: currentJobId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const progress = data.progress;
                            const result = data.result;

                            updateNotificationProgress(progress.percentage, progress.message);

                            if (progress.percentage === 100 && result) {
                                localStorage.setItem('exportCompleted', 'true');
                                clearInterval(exportCheckInterval);
                                showNotificationDownload(result.url, result.filename);
                            } else if (progress.percentage === -1) {
                                clearInterval(exportCheckInterval);
                                showNotificationError(progress.message);
                            }
                        }
                    })
                    .catch(error => {
                        clearInterval(exportCheckInterval);
                        showNotificationError('Failed to check progress: ' + error.message);
                    });
            }, 1000);
        }

        /**
         * Show notification
         */
        function showNotification() {
            document.getElementById('exportNotification').classList.remove('hidden');
            document.getElementById('exportNotificationBadge').classList.add('hidden');
            isMinimized = false;
        }

        /**
         * Hide notification
         */
        function hideNotification() {
            document.getElementById('exportNotification').classList.add('hidden');
            document.getElementById('exportNotificationBadge').classList.add('hidden');
        }

        /**
         * Minimize notification
         */
        function minimizeNotification() {
            document.getElementById('exportNotification').classList.add('hidden');
            document.getElementById('exportNotificationBadge').classList.remove('hidden');
            isMinimized = true;
        }

        /**
         * Expand notification
         */
        function expandNotification() {
            document.getElementById('exportNotification').classList.remove('hidden');
            document.getElementById('exportNotificationBadge').classList.add('hidden');
            isMinimized = false;
        }

        /**
         * Update notification progress
         */
        function updateNotificationProgress(percentage, message) {
            const displayPercentage = Math.max(0, Math.min(100, percentage));

            // Update main notification
            document.getElementById('notificationProgressBar').style.width = displayPercentage + '%';
            document.getElementById('notificationProgressPercentage').textContent = displayPercentage + '%';
            document.getElementById('notificationProgressMessage').textContent = message;

            // Update badge
            document.getElementById('badgePercentage').textContent = displayPercentage + '%';
        }

        /**
         * Reset notification progress
         */
        function resetNotificationProgress() {
            updateNotificationProgress(0, 'Initializing export...');
            document.getElementById('notificationDownloadSection').classList.add('hidden');
            document.getElementById('exportSpinner').classList.remove('hidden');
            document.getElementById('notificationProgressBar').classList.remove('bg-red-500');
            document.getElementById('notificationProgressBar').classList.add('bg-gradient-to-r', 'from-[#0078C8]',
                'to-[#006bb0]');
        }

        /**
         * Show download link in notification
         */
        function showNotificationDownload(url, filename) {
            const downloadSection = document.getElementById('notificationDownloadSection');
            const downloadLink = document.getElementById('notificationDownloadLink');

            downloadLink.href = url;
            downloadLink.download = filename;
            downloadSection.classList.remove('hidden');

            document.getElementById('exportSpinner').classList.add('hidden');
            updateNotificationProgress(100, 'Export completed successfully!');

            // Add click event to hide notification after download
            downloadLink.addEventListener('click', function() {
                setTimeout(() => {
                    hideNotification();
                }, 1000);
            });
        }

        /**
         * Show error in notification
         */
        function showNotificationError(message) {
            updateNotificationProgress(0, message);
            document.getElementById('notificationProgressBar').classList.remove('bg-gradient-to-r', 'from-[#0078C8]',
                'to-[#006bb0]');
            document.getElementById('notificationProgressBar').classList.add('bg-red-500');
            document.getElementById('exportSpinner').classList.add('hidden');

            // Auto hide after 5 seconds
            setTimeout(() => {
                hideNotification();
            }, 5000);
        }

        // Check for pending exports on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a pending export in session/localStorage
            const pendingExport = localStorage.getItem('pendingExportJobId');
            if (pendingExport) {
                currentJobId = pendingExport;
                showNotification();
                checkExportProgress();
            }
        });
        // Clear localStorage when export completes
        function showNotificationDownload(url, filename) {

            const downloadSection = document.getElementById('notificationDownloadSection');
            const downloadLink = document.getElementById('notificationDownloadLink');

            downloadLink.href = url;
            downloadLink.download = filename;
            downloadSection.classList.remove('hidden');

            document.getElementById('exportSpinner').classList.add('hidden');
            updateNotificationProgress(100, 'Export completed successfully!');

            downloadLink.addEventListener('click', function() {
                localStorage.removeItem('pendingExportJobId');
                localStorage.setItem('exportCompleted', 'false');
                setTimeout(() => {
                    hideNotification();
                }, 1000);
            });
        }
    </script>

    <!-- Script to generate account dropdown options -->
    <script>
        function generateAccountOptions() {
            const categories = {};

            // Group accounts
            accounts.forEach(acc => {
                if (!categories[acc.category]) {
                    categories[acc.category] = [];
                }
                categories[acc.category].push(acc);
            });

            let html = '<option value="">Select Account</option>';

            // Render groups in correct order
            const order = ["Assets", "Liabilities", "Equity", "Revenue", "Expenses"];

            order.forEach(category => {
                if (categories[category]) {
                    html += `<optgroup label="${category}">`;

                    categories[category].forEach(acc => {
                        html += `
                    <option value="${acc.id}">
                        ${acc.account_number} - ${acc.name}
                    </option>
                `;
                    });

                    html += "</optgroup>";
                }
            });

            return html;
        }
    </script>
    @stack('scripts')
</body>

</html>
