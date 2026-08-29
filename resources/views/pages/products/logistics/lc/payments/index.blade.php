@extends('layouts.auth')

@section('title', 'LC Payments')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.lc') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">
            <div
                class="p-5 bg-white rounded-lg border border-black/10 flex flex-col gap-3 md:flex-row md:justify-between md:items-center mb-4">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">LC Payments</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Payment receipts linked with logistics bookings</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap justify-end">
                    <a href="{{ route('logistics.lc.index') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-file-invoice-dollar text-xs"></i> LCs
                    </a>
                    <button type="button" onclick="openLcPaymentImportModal()"
                        class="px-4 py-2 bg-white text-gray-700 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 flex items-center gap-1.5 transition-all shadow-sm">
                        <i class="fa-solid fa-file-import text-gray-400"></i> Import
                    </button>
                    <a href="{{ route('logistics.lc.payments.create') }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i> New Payment
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4" id="payments-summary">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-gray-900" x-data="countUp('{{ $stats['total'] }}')"
                        x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="total">
                        {{ $stats['total'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Total</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-amber-500" x-data="countUp('{{ $stats['pending'] }}')"
                        x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="pending">
                        {{ $stats['pending'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Pending</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-2xl font-black text-green-600" x-data="countUp('{{ $stats['completed'] }}')"
                        x-text="displayValue" @update-value="update($event.detail.value)" data-summary-key="completed">
                        {{ $stats['completed'] }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Completed</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-lg font-black text-[#0078C8]">
                        USD
                        <span x-data="countUp('{{ $stats['total_usd'] }}')" x-text="displayValue"
                            @update-value="update($event.detail.value)"
                            data-summary-key="total_usd">{{ number_format($stats['total_usd'], 2) }}</span>
                    </p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Total USD</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                    <p class="text-lg font-black text-purple-600">
                        PKR
                        <span x-data="countUp('{{ $stats['total_pkr'] }}')" x-text="displayValue"
                            @update-value="update($event.detail.value)"
                            data-summary-key="total_pkr">{{ number_format($stats['total_pkr'], 2) }}</span>
                    </p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Total PKR</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <x-common.filter-form action="{{ route('logistics.lc.payments.index') }}" summary="#payments-summary">
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select name="status"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>
                                    Completed</option>
                            </select>
                        </div>

                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Booking</label>
                            <select name="booking_id"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Bookings</option>
                                @foreach ($bookings as $booking)
                                    <option value="{{ $booking->id }}"
                                        {{ (string) request('booking_id') === (string) $booking->id ? 'selected' : '' }}>
                                        {{ $booking->booking_number ?: 'Booking #' . $booking->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Type</label>
                            <select name="payment_type"
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Payment Types</option>
                                <option value="freight_payment"
                                    {{ request('payment_type') === 'freight_payment' ? 'selected' : '' }}>Freight Payment</option>
                                <option value="product_payment"
                                    {{ request('payment_type') === 'product_payment' ? 'selected' : '' }}>Product Payment</option>
                            </select>
                        </div>

                        <x-common.date-filter name="start_date" id="start_date" value="{{ request('start_date') }}"
                            label="From Date" />
                        <x-common.date-filter name="end_date" id="end_date" value="{{ request('end_date') }}"
                            label="To Date" />
                        <x-common.search-filter name="q" value="{{ request('q') }}" label="Search" id="search" />
                        <x-filter-button />
                    </x-common.filter-form>
                </div>

                <div id="custom_table">
                    @include('pages.products.logistics.lc.payments.partials.table', ['payments' => $payments])
                </div>
                <div id="pagination-wrapper"></div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />

    <div id="lcPaymentImportModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4 overflow-hidden">
            <form action="{{ route('logistics.lc.payments.import') }}" method="POST" enctype="multipart/form-data"
                id="lcPaymentImportForm">
                @csrf
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-[#0078C8]/10 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-file-excel text-[#0078C8] text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Import LC Payments</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Bulk upload payment rows from Excel</p>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                            <div class="text-[11px] text-blue-800 leading-relaxed">
                                Use the standard payment template before uploading.
                                <a href="{{ route('logistics.lc.payments.download-template') }}"
                                    class="font-bold underline hover:text-blue-900 ml-1">Download Template</a>
                            </div>
                        </div>
                    </div>

                    <div id="lcPaymentImportErrors"
                        class="hidden rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-[11px] text-red-700">
                    </div>

                    <div id="lcPaymentImportInitial">
                        <label class="block text-xs font-medium text-gray-700 mb-2">Select Excel File</label>
                        <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                            class="w-full text-xs text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#0078C8] file:text-white hover:file:bg-[#006bb0] cursor-pointer" />
                        <p class="text-[11px] text-gray-400 mt-2">Accepted formats: XLSX, XLS, CSV. Maximum size: 10 MB.</p>
                    </div>

                    <div id="lcPaymentImportProgressWrapper" class="hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span id="lcPaymentImportStatusText" class="text-xs font-bold text-[#0078C8]">Preparing
                                upload...</span>
                            <span id="lcPaymentImportProgressPercent" class="text-xs font-black text-gray-900">0%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="lcPaymentImportProgressBar" class="bg-[#0078C8] h-full transition-all duration-300"
                                data-progress="0" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeLcPaymentImportModal()" id="lcPaymentImportCloseBtn"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Close
                    </button>
                    <button type="submit" id="lcPaymentImportSubmitBtn"
                        class="px-4 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-upload text-[10px]"></i> Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let lcPaymentImportBusy = false;
            let lcPaymentImportTicker = null;

            function openLcPaymentImportModal() {
                const form = document.getElementById('lcPaymentImportForm');
                form.reset();
                hideLcPaymentImportErrors();
                setLcPaymentImportProgress(0, 'Preparing upload...');
                document.getElementById('lcPaymentImportProgressWrapper').classList.add('hidden');
                document.getElementById('lcPaymentImportInitial').classList.remove('hidden');
                document.getElementById('lcPaymentImportSubmitBtn').disabled = false;
                document.getElementById('lcPaymentImportCloseBtn').disabled = false;
                document.getElementById('lcPaymentImportSubmitBtn').innerHTML =
                    '<i class="fa-solid fa-upload text-[10px]"></i> Start Import';
                document.getElementById('lcPaymentImportModal').classList.remove('hidden');
            }

            function closeLcPaymentImportModal() {
                if (lcPaymentImportBusy) {
                    return;
                }

                document.getElementById('lcPaymentImportModal').classList.add('hidden');
            }

            function setLcPaymentImportProgress(percent, text) {
                const safePercent = Math.max(0, Math.min(100, Math.round(percent)));
                const progressBar = document.getElementById('lcPaymentImportProgressBar');
                progressBar.dataset.progress = safePercent;
                progressBar.style.width = safePercent + '%';
                document.getElementById('lcPaymentImportProgressPercent').textContent = safePercent + '%';
                document.getElementById('lcPaymentImportStatusText').textContent = text;
            }

            function startLcPaymentProcessingPulse() {
                clearInterval(lcPaymentImportTicker);
                lcPaymentImportTicker = setInterval(() => {
                    const progressBar = document.getElementById('lcPaymentImportProgressBar');
                    const current = Number(progressBar.dataset.progress || 0);
                    if (current < 96) {
                        setLcPaymentImportProgress(current + 1, 'Processing spreadsheet...');
                    }
                }, 500);
            }

            function finishLcPaymentImportRequest() {
                clearInterval(lcPaymentImportTicker);
                lcPaymentImportBusy = false;
                document.getElementById('lcPaymentImportSubmitBtn').disabled = false;
                document.getElementById('lcPaymentImportCloseBtn').disabled = false;
                document.getElementById('lcPaymentImportSubmitBtn').innerHTML =
                    '<i class="fa-solid fa-upload text-[10px]"></i> Start Import';
            }

            function hideLcPaymentImportErrors() {
                const errorsBox = document.getElementById('lcPaymentImportErrors');
                errorsBox.classList.add('hidden');
                errorsBox.innerHTML = '';
            }

            function showLcPaymentImportErrors(errors, fallbackMessage) {
                const errorsBox = document.getElementById('lcPaymentImportErrors');
                const normalizedErrors = Array.isArray(errors) ? errors : Object.values(errors || {}).flat();
                const messages = normalizedErrors.length ? normalizedErrors : [fallbackMessage || 'Import failed.'];

                errorsBox.innerHTML = '<ul class="space-y-1 list-disc pl-4">' + messages.map((message) =>
                    '<li>' + escapeLcPaymentImportHtml(message) + '</li>'
                ).join('') + '</ul>';
                errorsBox.classList.remove('hidden');
            }

            function escapeLcPaymentImportHtml(value) {
                return String(value).replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                })[char]);
            }

            document.getElementById('lcPaymentImportForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const fileInput = form.querySelector('input[name="file"]');

                hideLcPaymentImportErrors();

                if (!fileInput.files.length) {
                    showLcPaymentImportErrors([], 'Please select an Excel or CSV file first.');
                    return;
                }

                lcPaymentImportBusy = true;
                document.getElementById('lcPaymentImportSubmitBtn').disabled = true;
                document.getElementById('lcPaymentImportCloseBtn').disabled = true;
                document.getElementById('lcPaymentImportSubmitBtn').innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Importing...';
                document.getElementById('lcPaymentImportInitial').classList.add('hidden');
                document.getElementById('lcPaymentImportProgressWrapper').classList.remove('hidden');
                setLcPaymentImportProgress(3, 'Starting upload...');

                const xhr = new XMLHttpRequest();
                const formData = new FormData(form);

                xhr.upload.addEventListener('progress', function(event) {
                    if (!event.lengthComputable) {
                        setLcPaymentImportProgress(35, 'Uploading file...');
                        return;
                    }

                    const uploadPercent = Math.min(90, (event.loaded / event.total) * 90);
                    setLcPaymentImportProgress(uploadPercent, 'Uploading file...');
                });

                xhr.upload.addEventListener('load', function() {
                    setLcPaymentImportProgress(92, 'Processing spreadsheet...');
                    startLcPaymentProcessingPulse();
                });

                xhr.addEventListener('load', function() {
                    let data = {};

                    try {
                        data = JSON.parse(xhr.responseText || '{}');
                    } catch (error) {
                        data = {
                            success: false,
                            message: 'Import failed. The server returned an invalid response.'
                        };
                    }

                    if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                        clearInterval(lcPaymentImportTicker);
                        setLcPaymentImportProgress(100, 'Import complete.');

                        if (typeof Notification !== 'undefined' && typeof Notification.success === 'function') {
                            Notification.success(data.message || 'LC payments imported successfully.');
                        }

                        setTimeout(() => window.location.reload(), 900);
                        return;
                    }

                    finishLcPaymentImportRequest();
                    document.getElementById('lcPaymentImportInitial').classList.remove('hidden');
                    document.getElementById('lcPaymentImportProgressWrapper').classList.add('hidden');
                    showLcPaymentImportErrors(data.errors, data.message || 'Import failed. Please try again.');
                });

                xhr.addEventListener('error', function() {
                    finishLcPaymentImportRequest();
                    document.getElementById('lcPaymentImportInitial').classList.remove('hidden');
                    document.getElementById('lcPaymentImportProgressWrapper').classList.add('hidden');
                    showLcPaymentImportErrors([], 'Network error. Please try the upload again.');
                });

                xhr.open('POST', form.action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute(
                    'content'));
                xhr.send(formData);
            });

            document.getElementById('lcPaymentImportModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeLcPaymentImportModal();
                }
            });
        </script>
    @endpush
@endsection
