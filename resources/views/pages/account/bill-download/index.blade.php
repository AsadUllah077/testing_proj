@extends('layouts.auth')

@section('title', 'Download Bills')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.bill') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-4">
                <div>
                    <a href="{{ route('account.bill-invoice.index') }}" class="text-md mb-1 text-[#0078C8] hover:underline">
                        <i class="fa-solid fa-arrow-left mr-2"></i>Back
                    </a>
                    <h2 class="text-2xl font-light text-gray-800">Download Bills</h2>
                    <p class="text-sm text-gray-500">Select bills and download them as a PDF report or CSV file.</p>
                </div>
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <form method="GET" action="{{ route('account.bill-download.index') }}" id="bill-download-filter-form"
                    class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="selected_bill_ids" id="selected-bill-ids-input">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Search</label>
                        <input type="search" name="search" value="{{ request('search') }}"
                            placeholder="Invoice, ref, vendor, note"
                            class="block w-64 border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">From Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                            class="date block border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">To Date</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                            class="date block border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                    </div>

                    <div class="min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Select Vendor</label>
                        <select name="vendor_id"
                            class="ajax-vendor-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Status</label>
                        <select name="approval_status"
                            class="block border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('approval_status') === 'draft' ? 'selected' : '' }}>Draft
                            </option>
                            <option value="submitted" {{ request('approval_status') === 'submitted' ? 'selected' : '' }}>
                                Pending Approval</option>
                            <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>
                                Approved</option>
                            <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>
                                Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Payment</label>
                        <select name="payment_status" class="block border-gray-300 rounded-md sm:text-sm border p-2">
                            <option value="">All payments</option>
                            @foreach (['unpaid', 'partial', 'paid', 'overpaid'] as $status)
                                <option value="{{ $status }}" {{ request('payment_status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-default-page />
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                            Apply Filters
                        </button>
                        <a href="{{ route('account.bill-download.index') }}" id="bill-download-reset-link"
                            class="px-4 py-2 text-xs bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">
                            RESET
                        </a>
                    </div>
                </form>
            </div>

            <div id="bulk-action-bar"
                class="hidden bg-[#0078C8] text-white rounded-lg border border-[#0078C8]/30 p-4 mb-4 items-center justify-between shadow-sm">
                <div class="text-sm font-medium">
                    <span id="selected-count" class="font-bold text-lg">0</span> bill(s) selected
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="openBillExportColumnModal('pdf')"
                        class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm">
                        Download PDF
                    </button>
                    <button type="button" onclick="openBillExportColumnModal('separate-pdfs')"
                        class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm">
                        Separate PDFs
                    </button>
                    <button type="button" onclick="openBillExportColumnModal('csv')"
                        class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm">
                        Download CSV
                    </button>
                    <button type="button" onclick="clearSelection()"
                        class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded text-sm transition">
                        Clear
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-black/10 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 w-10">
                                    <input type="checkbox" id="select-all-bills"
                                        class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                                </th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Invoice No</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider text-right">Total
                                </th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider text-right">
                                    Remaining</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider text-right">Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($bills as $bill)
                                @php
                                    $statusLabel = ucfirst($bill->approval_status) . ' / ' . ucfirst(str_replace('_', ' ', $bill->payment_status ?? 'unpaid'));
                                    $badge = $bill->approval_status === 'approved' ? 'bg-green-100 text-green-700' : ($bill->approval_status === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-700');
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-4">
                                        <input type="checkbox" value="{{ $bill->id }}"
                                            data-label="{{ $bill->invoice_no ?? ($bill->ref_no ?? $bill->id) }}"
                                            class="bill-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ dateFormat($bill->date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-800">
                                        {{ $bill->invoice_no ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $bill->ref_no ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ $bill->vendor?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-gray-800">
                                        {{ numberFormat($bill->total_after_discount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-gray-700">
                                        {{ numberFormat($bill->remaining_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button type="button" onclick="openBillExportColumnModal('single-pdf', '{{ $bill->id }}')"
                                            class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-medium border transition">
                                            PDF
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-10 text-center text-gray-400 italic">
                                        No bills found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-data-table-paginatior :paginator="$bills" />
            </div>
        </div>
    </div>

    <div id="bill-export-column-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="min-h-screen px-4 py-6 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/40" onclick="closeBillExportColumnModal()"></div>
            <div class="relative bg-white rounded-lg shadow-xl border border-black/10 w-full max-w-2xl p-6">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h3 id="bill-export-modal-title" class="text-lg font-semibold text-gray-800">Select Export Columns</h3>
                        <p class="text-sm text-gray-500">Choose the columns for the selected bills.</p>
                    </div>
                    <button type="button" onclick="closeBillExportColumnModal()"
                        class="text-gray-400 hover:text-gray-700 text-xl leading-none">
                        &times;
                    </button>
                </div>

                <div id="bill-report-column-options" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
                    @foreach ($exportColumns as $key => $column)
                        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-700 cursor-pointer hover:border-[#0078C8]">
                            <input type="checkbox" value="{{ $key }}" checked
                                class="bill-report-export-column-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                            <span>{{ $column['label'] }}</span>
                        </label>
                    @endforeach
                </div>

                <div id="bill-invoice-column-options" class="hidden grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
                    @foreach ($invoiceExportColumns as $key => $label)
                        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-700 cursor-pointer hover:border-[#0078C8]">
                            <input type="checkbox" value="{{ $key }}" checked
                                class="bill-invoice-export-column-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div id="bill-paper-size-wrapper" class="mb-5">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Paper Size</label>
                    <select id="bill-paper-size"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                        @foreach ($paperSizeOptions ?? [] as $key => $label)
                            <option value="{{ $key }}" {{ $key === \App\Support\PdfPaperSize::DEFAULT_INVOICE ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap justify-between gap-3">
                    <div class="flex gap-2">
                        <button type="button" onclick="setBillExportColumns(true)"
                            class="px-3 py-2 text-xs border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            Select All
                        </button>
                        <button type="button" onclick="setBillExportColumns(false)"
                            class="px-3 py-2 text-xs border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            Clear
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeBillExportColumnModal()"
                            class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="button" onclick="exportSelectedBillsWithColumns()"
                            class="px-4 py-2 text-sm bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">
                            Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const billSelectionStorageKey = 'account.bill-download.selected-bills';
        const selectedBills = new Map(JSON.parse(localStorage.getItem(billSelectionStorageKey) || '[]'));
        const selectedCount = document.getElementById('selected-count');
        const bulkActionBar = document.getElementById('bulk-action-bar');
        let pendingBillExportFormat = null;
        let pendingBillExportId = null;
        let pendingBillColumnMode = 'report';

        function persistSelection() {
            localStorage.setItem(billSelectionStorageKey, JSON.stringify(Array.from(selectedBills.entries())));
            syncSelectedBillIdsInput();
        }

        function syncSelectedBillIdsInput() {
            const input = document.getElementById('selected-bill-ids-input');
            if (input) input.value = Array.from(selectedBills.keys()).join(',');
        }

        function updateBulkBar() {
            selectedCount.textContent = selectedBills.size;

            if (selectedBills.size > 0) {
                bulkActionBar.classList.remove('hidden');
                bulkActionBar.classList.add('flex');
            } else {
                bulkActionBar.classList.add('hidden');
                bulkActionBar.classList.remove('flex');
            }
        }

        function clearSelection() {
            selectedBills.clear();
            persistSelection();
            document.querySelectorAll('.bill-checkbox').forEach(checkbox => checkbox.checked = false);
            const selectAll = document.getElementById('select-all-bills');
            if (selectAll) selectAll.checked = false;
            updateBulkBar();
        }

        function restoreVisibleSelection() {
            document.querySelectorAll('.bill-checkbox').forEach(checkbox => {
                checkbox.checked = selectedBills.has(checkbox.value);
            });

            const visibleCheckboxes = Array.from(document.querySelectorAll('.bill-checkbox'));
            const selectAll = document.getElementById('select-all-bills');
            if (selectAll) {
                selectAll.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(checkbox => checkbox.checked);
            }
        }

        document.getElementById('select-all-bills')?.addEventListener('change', function() {
            document.querySelectorAll('.bill-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;

                if (this.checked) {
                    selectedBills.set(checkbox.value, checkbox.dataset.label);
                } else {
                    selectedBills.delete(checkbox.value);
                }
            });

            persistSelection();
            updateBulkBar();
        });

        document.querySelectorAll('.bill-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    selectedBills.set(this.value, this.dataset.label);
                } else {
                    selectedBills.delete(this.value);
                }

                persistSelection();
                restoreVisibleSelection();
                updateBulkBar();
            });
        });

        document.getElementById('bill-download-filter-form')?.addEventListener('submit', syncSelectedBillIdsInput);
        document.getElementById('bill-download-reset-link')?.addEventListener('click', function(event) {
            syncSelectedBillIdsInput();
            if (selectedBills.size === 0) return;

            event.preventDefault();
            const url = new URL(this.href);
            url.searchParams.set('selected_bill_ids', Array.from(selectedBills.keys()).join(','));
            window.location.href = url.toString();
        });

        function openBillExportColumnModal(format, billId = null) {
            if (!billId && selectedBills.size === 0) {
                alert('Please select at least one bill.');
                return;
            }

            pendingBillExportFormat = format;
            pendingBillExportId = billId;
            pendingBillColumnMode = ['separate-pdfs', 'single-pdf'].includes(format) ? 'invoice' : 'report';

            const titles = {
                pdf: 'Select PDF Columns',
                csv: 'Select CSV Columns',
                'separate-pdfs': 'Select Separate PDF Columns',
                'single-pdf': 'Select Bill PDF Columns',
            };

            document.getElementById('bill-export-modal-title').textContent = titles[format] || 'Select Export Columns';
            document.getElementById('bill-report-column-options').classList.toggle('hidden', pendingBillColumnMode !== 'report');
            document.getElementById('bill-invoice-column-options').classList.toggle('hidden', pendingBillColumnMode !== 'invoice');

            const isPdfExport = ['pdf', 'separate-pdfs', 'single-pdf'].includes(format);
            document.getElementById('bill-paper-size-wrapper').classList.toggle('hidden', !isPdfExport);

            const paperSizeSelect = document.getElementById('bill-paper-size');
            if (paperSizeSelect && isPdfExport) {
                paperSizeSelect.value = format === 'pdf' ? 'a4' : 'long_invoice';
            }

            document.getElementById('bill-export-column-modal').classList.remove('hidden');
        }

        function closeBillExportColumnModal() {
            document.getElementById('bill-export-column-modal').classList.add('hidden');
        }

        function setBillExportColumns(checked) {
            const selector = pendingBillColumnMode === 'invoice'
                ? '.bill-invoice-export-column-checkbox'
                : '.bill-report-export-column-checkbox';

            document.querySelectorAll(selector).forEach(checkbox => {
                checkbox.checked = checked;
            });
        }

        function exportSelectedBillsWithColumns() {
            const selector = pendingBillColumnMode === 'invoice'
                ? '.bill-invoice-export-column-checkbox:checked'
                : '.bill-report-export-column-checkbox:checked';
            const columns = Array.from(document.querySelectorAll(selector))
                .map(checkbox => checkbox.value);

            if (columns.length === 0) {
                alert('Please select at least one column.');
                return;
            }

            const paperSize = document.getElementById('bill-paper-size')?.value || 'long_invoice';

            if (pendingBillExportFormat === 'single-pdf') {
                exportSingleBillPdf(pendingBillExportId, columns, paperSize);
            } else {
                exportSelectedBills(pendingBillExportFormat || 'pdf', columns, paperSize);
            }

            closeBillExportColumnModal();
        }

        function exportSingleBillPdf(billId, columns = [], paperSize = 'long_invoice') {
            if (!billId) {
                return;
            }

            const route = `{{ route('account.bill-invoice.export-pdf', ['id' => '__ID__']) }}`.replace('__ID__', billId);
            const params = new URLSearchParams();
            params.set('columns', columns.join(','));
            params.set('paper_size', paperSize);

            window.open(`${route}?${params.toString()}`, '_blank');
        }

        function exportSelectedBills(format, columns = [], paperSize = 'a4') {
            if (selectedBills.size === 0) {
                alert('Please select at least one bill.');
                return;
            }

            const params = new URLSearchParams();
            selectedBills.forEach((label, billId) => params.append('bill_ids[]', billId));
            columns.forEach(column => params.append('columns[]', column));
            if (format !== 'csv') {
                params.set('paper_size', paperSize);
            }

            const routes = {
                pdf: `{{ route('account.bill-download.pdf') }}`,
                'separate-pdfs': `{{ route('account.bill-download.separate-pdfs') }}`,
                csv: `{{ route('account.bill-download.csv') }}`,
            };

            const url = `${routes[format] || routes.pdf}?${params.toString()}`;

            window.location.href = url;
        }

        syncSelectedBillIdsInput();
        restoreVisibleSelection();
        updateBulkBar();

        // Initialize AJAX Vendor Select
        $('.ajax-vendor-select').select2({
            placeholder: '-- select vendor --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.bill.vendors.paginate') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data.map(vendor => ({
                            id: vendor.id,
                            text: vendor.name
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });
    </script>
@endpush
