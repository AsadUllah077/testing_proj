@extends('layouts.auth')

@section('title', 'Download Sales')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-4">
                <div>
                    <a href="{{ route('account.sale-invoice.index') }}" class="text-md mb-1 text-[#0078C8] hover:underline">
                        <i class="fa-solid fa-arrow-left mr-2"></i>Back
                    </a>
                    <h2 class="text-2xl font-light text-gray-800">Download Sales</h2>
                    <p class="text-sm text-gray-500">Select sale invoices and download them as a PDF report or CSV file.</p>
                </div>
            </div>

            <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                <form method="GET" action="{{ route('account.sale-download.index') }}" id="sale-download-filter-form"
                    class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="selected_sale_ids" id="selected-sale-ids-input">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Search</label>
                        <input type="search" name="search" value="{{ request('search') }}"
                            placeholder="Invoice, ref, customer, note"
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
                        <label class="block text-xs font-medium text-gray-600 mb-2">Select Customer</label>
                        <select name="customer_id"
                            class="ajax-customer-select w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
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
                        <select name="payment_status" class="block border-gray-300 rounded-md shadow-sm sm:text-sm border p-2">
                            <option value="">All Payments</option>
                            @foreach (['unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid', 'overpaid' => 'Overpaid'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
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
                        <a href="{{ route('account.sale-download.index') }}" id="sale-download-reset-link"
                            class="px-4 py-2 text-xs bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">
                            RESET
                        </a>
                    </div>
                </form>
            </div>

            <div id="bulk-action-bar"
                class="hidden bg-[#0078C8] text-white rounded-lg border border-[#0078C8]/30 p-4 mb-4 items-center justify-between shadow-sm">
                <div class="text-sm font-medium">
                    <span id="selected-count" class="font-bold text-lg">0</span> sale(s) selected
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="openSaleExportColumnModal('pdf')"
                        class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm">
                        Download PDF
                    </button>
                    <button type="button" onclick="openSaleExportColumnModal('separate-pdfs')"
                        class="px-5 py-2 bg-white text-[#0078C8] rounded-md text-sm font-bold hover:bg-gray-100 transition shadow-sm">
                        Separate PDFs
                    </button>
                    <button type="button" onclick="openSaleExportColumnModal('csv')"
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
                                    <input type="checkbox" id="select-all-sales"
                                        class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                                </th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Invoice No</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Customer</th>
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
                            @forelse($sales as $sale)
                                @php
                                    if ($sale->approval_status === 'approved') {
                                        $statusLabel = match ($sale->payment_status) {
                                            'paid', 'overpaid' => 'Paid',
                                            'partial' => 'Partially Paid',
                                            default => 'Unpaid',
                                        };
                                        $badge = match ($statusLabel) {
                                            'Paid' => 'bg-green-100 text-green-700',
                                            'Partially Paid' => 'bg-yellow-100 text-yellow-700',
                                            default => 'bg-red-100 text-red-600',
                                        };
                                    } else {
                                        $statusLabel = ucfirst($sale->approval_status);
                                        $badge = match ($sale->approval_status) {
                                            'draft' => 'bg-gray-100 text-gray-600',
                                            'submitted' => 'bg-amber-100 text-amber-700',
                                            'rejected' => 'bg-red-100 text-red-600',
                                            default => 'bg-gray-100 text-gray-500',
                                        };
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-4">
                                        <input type="checkbox" value="{{ $sale->id }}"
                                            data-label="{{ $sale->invoice_no ?? ($sale->ref_no ?? $sale->id) }}"
                                            class="sale-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ dateFormat($sale->date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-800">
                                        {{ $sale->invoice_no ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $sale->ref_no ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ $sale->customer?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-gray-800">
                                        {{ numberFormat($sale->total_after_discount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-gray-700">
                                        {{ numberFormat($sale->remaining_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button type="button" onclick="openSaleExportColumnModal('single-pdf', '{{ $sale->id }}')"
                                            class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-medium border transition">
                                            PDF
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-10 text-center text-gray-400 italic">
                                        No sales found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-data-table-paginatior :paginator="$sales" />
            </div>
        </div>
    </div>

    <div id="sale-export-column-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="min-h-screen px-4 py-6 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/40" onclick="closeSaleExportColumnModal()"></div>
            <div class="relative bg-white rounded-lg shadow-xl border border-black/10 w-full max-w-2xl p-6">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h3 id="sale-export-modal-title" class="text-lg font-semibold text-gray-800">Select Export Columns</h3>
                        <p class="text-sm text-gray-500">Choose the columns for the selected sales.</p>
                    </div>
                    <button type="button" onclick="closeSaleExportColumnModal()"
                        class="text-gray-400 hover:text-gray-700 text-xl leading-none">
                        &times;
                    </button>
                </div>

                <div id="sale-report-column-options" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
                    @foreach ($exportColumns as $key => $column)
                        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-700 cursor-pointer hover:border-[#0078C8]">
                            <input type="checkbox" value="{{ $key }}" checked
                                class="sale-report-export-column-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                            <span>{{ $column['label'] }}</span>
                        </label>
                    @endforeach
                </div>

                <div id="sale-invoice-column-options" class="hidden grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
                    @foreach ($invoiceExportColumns as $key => $label)
                        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-700 cursor-pointer hover:border-[#0078C8]">
                            <input type="checkbox" value="{{ $key }}" checked
                                class="sale-invoice-export-column-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div id="sale-paper-size-wrapper" class="mb-5">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Paper Size</label>
                    <select id="sale-paper-size"
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
                        <button type="button" onclick="setSaleExportColumns(true)"
                            class="px-3 py-2 text-xs border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            Select All
                        </button>
                        <button type="button" onclick="setSaleExportColumns(false)"
                            class="px-3 py-2 text-xs border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            Clear
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeSaleExportColumnModal()"
                            class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="button" onclick="exportSelectedSalesWithColumns()"
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
        const saleSelectionStorageKey = 'account.sale-download.selected-sales';
        const selectedSales = new Map(JSON.parse(localStorage.getItem(saleSelectionStorageKey) || '[]'));
        const selectedCount = document.getElementById('selected-count');
        const bulkActionBar = document.getElementById('bulk-action-bar');
        let pendingSaleExportFormat = null;
        let pendingSaleExportId = null;
        let pendingSaleColumnMode = 'report';

        function persistSelection() {
            localStorage.setItem(saleSelectionStorageKey, JSON.stringify(Array.from(selectedSales.entries())));
            syncSelectedSaleIdsInput();
        }

        function syncSelectedSaleIdsInput() {
            const input = document.getElementById('selected-sale-ids-input');
            if (input) input.value = Array.from(selectedSales.keys()).join(',');
        }

        function updateBulkBar() {
            selectedCount.textContent = selectedSales.size;

            if (selectedSales.size > 0) {
                bulkActionBar.classList.remove('hidden');
                bulkActionBar.classList.add('flex');
            } else {
                bulkActionBar.classList.add('hidden');
                bulkActionBar.classList.remove('flex');
            }
        }

        function clearSelection() {
            selectedSales.clear();
            persistSelection();
            document.querySelectorAll('.sale-checkbox').forEach(checkbox => checkbox.checked = false);
            const selectAll = document.getElementById('select-all-sales');
            if (selectAll) selectAll.checked = false;
            updateBulkBar();
        }

        function restoreVisibleSelection() {
            document.querySelectorAll('.sale-checkbox').forEach(checkbox => {
                checkbox.checked = selectedSales.has(checkbox.value);
            });

            const visibleCheckboxes = Array.from(document.querySelectorAll('.sale-checkbox'));
            const selectAll = document.getElementById('select-all-sales');
            if (selectAll) {
                selectAll.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(checkbox => checkbox.checked);
            }
        }

        document.getElementById('select-all-sales')?.addEventListener('change', function() {
            document.querySelectorAll('.sale-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;

                if (this.checked) {
                    selectedSales.set(checkbox.value, checkbox.dataset.label);
                } else {
                    selectedSales.delete(checkbox.value);
                }
            });

            persistSelection();
            updateBulkBar();
        });

        document.querySelectorAll('.sale-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    selectedSales.set(this.value, this.dataset.label);
                } else {
                    selectedSales.delete(this.value);
                }

                persistSelection();
                restoreVisibleSelection();
                updateBulkBar();
            });
        });

        document.getElementById('sale-download-filter-form')?.addEventListener('submit', syncSelectedSaleIdsInput);
        document.getElementById('sale-download-reset-link')?.addEventListener('click', function(event) {
            syncSelectedSaleIdsInput();
            if (selectedSales.size === 0) return;

            event.preventDefault();
            const url = new URL(this.href);
            url.searchParams.set('selected_sale_ids', Array.from(selectedSales.keys()).join(','));
            window.location.href = url.toString();
        });

        function openSaleExportColumnModal(format, saleId = null) {
            if (!saleId && selectedSales.size === 0) {
                alert('Please select at least one sale invoice.');
                return;
            }

            pendingSaleExportFormat = format;
            pendingSaleExportId = saleId;
            pendingSaleColumnMode = ['separate-pdfs', 'single-pdf'].includes(format) ? 'invoice' : 'report';

            const titles = {
                pdf: 'Select PDF Columns',
                csv: 'Select CSV Columns',
                'separate-pdfs': 'Select Separate PDF Columns',
                'single-pdf': 'Select Sale PDF Columns',
            };

            document.getElementById('sale-export-modal-title').textContent = titles[format] || 'Select Export Columns';
            document.getElementById('sale-report-column-options').classList.toggle('hidden', pendingSaleColumnMode !== 'report');
            document.getElementById('sale-invoice-column-options').classList.toggle('hidden', pendingSaleColumnMode !== 'invoice');

            const isPdfExport = ['pdf', 'separate-pdfs', 'single-pdf'].includes(format);
            document.getElementById('sale-paper-size-wrapper').classList.toggle('hidden', !isPdfExport);

            const paperSizeSelect = document.getElementById('sale-paper-size');
            if (paperSizeSelect && isPdfExport) {
                paperSizeSelect.value = format === 'pdf' ? 'a4' : 'long_invoice';
            }

            document.getElementById('sale-export-column-modal').classList.remove('hidden');
        }

        function closeSaleExportColumnModal() {
            document.getElementById('sale-export-column-modal').classList.add('hidden');
        }

        function setSaleExportColumns(checked) {
            const selector = pendingSaleColumnMode === 'invoice'
                ? '.sale-invoice-export-column-checkbox'
                : '.sale-report-export-column-checkbox';

            document.querySelectorAll(selector).forEach(checkbox => {
                checkbox.checked = checked;
            });
        }

        function exportSelectedSalesWithColumns() {
            const selector = pendingSaleColumnMode === 'invoice'
                ? '.sale-invoice-export-column-checkbox:checked'
                : '.sale-report-export-column-checkbox:checked';
            const columns = Array.from(document.querySelectorAll(selector))
                .map(checkbox => checkbox.value);

            if (columns.length === 0) {
                alert('Please select at least one column.');
                return;
            }

            const paperSize = document.getElementById('sale-paper-size')?.value || 'long_invoice';

            if (pendingSaleExportFormat === 'single-pdf') {
                exportSingleSalePdf(pendingSaleExportId, columns, paperSize);
            } else {
                exportSelectedSales(pendingSaleExportFormat || 'pdf', columns, paperSize);
            }

            closeSaleExportColumnModal();
        }

        function exportSingleSalePdf(saleId, columns = [], paperSize = 'long_invoice') {
            if (!saleId) {
                return;
            }

            const route = `{{ route('account.sale-invoice.export-pdf', ['id' => '__ID__']) }}`.replace('__ID__', saleId);
            const params = new URLSearchParams();
            params.set('columns', columns.join(','));
            params.set('paper_size', paperSize);

            window.open(`${route}?${params.toString()}`, '_blank');
        }

        function exportSelectedSales(format, columns = [], paperSize = 'a4') {
            if (selectedSales.size === 0) {
                alert('Please select at least one sale invoice.');
                return;
            }

            const params = new URLSearchParams();
            selectedSales.forEach((label, saleId) => params.append('sale_ids[]', saleId));
            columns.forEach(column => params.append('columns[]', column));
            if (format !== 'csv') {
                params.set('paper_size', paperSize);
            }

            const routes = {
                pdf: `{{ route('account.sale-download.pdf') }}`,
                'separate-pdfs': `{{ route('account.sale-download.separate-pdfs') }}`,
                csv: `{{ route('account.sale-download.csv') }}`,
            };

            const url = `${routes[format] || routes.pdf}?${params.toString()}`;

            window.location.href = url;
        }

        syncSelectedSaleIdsInput();
        restoreVisibleSelection();
        updateBulkBar();

        // Initialize AJAX Customer Select
        $('.ajax-customer-select').select2({
            placeholder: '-- select customer --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.sale.customers.paginate') }}',
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
                        results: data.data.map(customer => ({
                            id: customer.id,
                            text: customer.name
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
