@extends('layouts.auth')

@section('title', 'Payroll Liabilities')

@section('content')
    <x-acccount.loader />
    <div class="px-4 py-8 mx-auto">
        @if ($unpaidLiabilities->isEmpty() && !request()->hasAny(['pay_item_id', 'pay_run_id']))
            <div class="bg-white rounded-lg shadow p-8 text-center border border-dashed border-gray-300">
                <div class="text-gray-400 mb-2">
                    <i class="fa-solid fa-circle-check text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">All caught up!</h3>
                <p class="text-gray-500">There are no outstanding payroll liabilities to pay.</p>
            </div>
        @else
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-light text-gray-800">Payroll Liabilities</h2>
                    <p class="text-sm text-gray-500">Manage and pay deducted salary taxes and other liabilities.</p>
                </div>
                <div>
                    <form method="GET" class="flex items-center gap-4" id="filter-form">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Filter by Pay Item</label>
                            <select name="pay_item_id"
                                class="select2 block w-64 border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                                <option value="">All Liabilities</option>
                                @foreach ($groupedLiabilities as $itemId => $group)
                                    <option value="{{ $itemId }}"
                                        {{ request('pay_item_id') == $itemId ? 'selected' : '' }}>
                                        {{ $group->first()->payItem->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-default-page />
                        </div>

                        <div class="flex gap-2 items-end mt-5">
                            <button type="button" id="search-btn"
                                class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                <span id="search-text">Apply Filters</span>
                                <span id="search-loader" class="hidden ml-2">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                            <button type="button" id="reset-btn"
                                class="px-4 py-2 text-xs bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div id="liabilities-table">
                @include('pages.payroll.partials.liabilities-table')
            </div>
        @endif

            <div class="mt-8 bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b"><h3 class="font-semibold text-gray-800">Recent Remittance Batches</h3><p class="text-xs text-gray-500">Approval, posting and reversal audit trail</p></div>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Reference</th><th class="px-4 py-3 text-left">Authority</th><th class="px-4 py-3 text-left">Payment Date</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y">
                    @forelse ($remittances as $remittance)
                        <tr><td class="px-4 py-3"><div class="font-medium">{{ $remittance->reference }}</div><div class="text-xs text-gray-400">{{ $remittance->external_reference ?: 'No external reference' }}</div>@if($remittance->attachment)<a href="{{ route('payroll.liabilities.receipt', $remittance->id) }}" class="text-[11px] text-[#0078C8]">Download evidence</a>@endif</td><td class="px-4 py-3">{{ $remittance->statutoryBody?->name }}</td><td class="px-4 py-3">{{ $remittance->payment_date->format('d M Y') }}</td><td class="px-4 py-3 text-right font-semibold">{{ numberFormat($remittance->total_amount) }}</td><td class="px-4 py-3 uppercase text-xs">{{ $remittance->status }}</td><td class="px-4 py-3"><div class="flex justify-end gap-2">
                            @if (in_array($remittance->status, ['submitted', 'approved'], true) && (int) $remittance->created_by === (int) auth('web')->id())
                                <form method="POST" action="{{ route('payroll.liabilities.payment-date.update', $remittance->id) }}" class="flex items-center gap-1" @if($remittance->status === 'approved') onsubmit="return confirm('Changing the date will clear the current approval. Continue?')" @endif>
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="payment_date" value="{{ $remittance->payment_date->format('Y-m-d') }}" required class="date w-28 border rounded px-2 py-1 text-xs" title="Payment date">
                                    <button class="px-2 py-1 border border-[#0078C8] text-[#0078C8] rounded text-xs whitespace-nowrap">UPDATE DATE</button>
                                </form>
                            @endif
                            @if ($remittance->status === 'submitted' && (int) $remittance->created_by !== (int) auth('web')->id())<form method="POST" action="{{ route('payroll.liabilities.approve', $remittance->id) }}">@csrf<button class="px-2 py-1 border border-green-600 text-green-700 rounded text-xs">APPROVE</button></form>@endif
                            @if ($remittance->status === 'approved')<form method="POST" action="{{ route('payroll.liabilities.post', $remittance->id) }}">@csrf<button class="px-2 py-1 bg-[#0078C8] text-white rounded text-xs">POST</button></form>@endif
                            @if ($remittance->status === 'posted')<form method="POST" action="{{ route('payroll.liabilities.reverse', $remittance->id) }}" class="flex gap-1">@csrf<input name="reason" required maxlength="1000" placeholder="Reversal reason" class="w-32 border rounded px-2 text-xs"><button class="px-2 py-1 border border-red-500 text-red-600 rounded text-xs">REVERSE</button></form>@endif
                        </div></td></tr>
                    @empty <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No remittance batches yet.</td></tr> @endforelse
                </tbody></table></div>
            </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectedLiabilities = new Map();

            function updateTotal() {
                let total = 0;
                selectedLiabilities.forEach(item => {
                    total += parseFloat(item.amount || 0);
                });
                const count = selectedLiabilities.size;
                
                const totalAmountDisplay = document.getElementById('totalAmount');
                const selectedCountDisplay = document.getElementById('selectedCount');
                const submitBtn = document.getElementById('submitBtn');

                if (totalAmountDisplay) {
                    totalAmountDisplay.textContent = new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2
                    }).format(total);
                }
                if (selectedCountDisplay) {
                    selectedCountDisplay.textContent = `${count} items selected`;
                }
                if (submitBtn) {
                    submitBtn.disabled = count === 0;
                }
            }

            function syncVisibleSelections() {
                document.querySelectorAll('.item-checkbox').forEach(cb => {
                    cb.checked = selectedLiabilities.has(cb.value);
                });

                document.querySelectorAll('.select-all-group').forEach(groupCheckbox => {
                    const group = groupCheckbox.dataset.group;
                    const groupCheckboxes = Array.from(document.querySelectorAll(`.item-checkbox[data-group="${group}"]`));
                    groupCheckbox.checked = groupCheckboxes.length > 0 && groupCheckboxes.every(cb => cb.checked);
                });

                updateTotal();
            }

            function rememberCheckbox(cb) {
                const amountInput = cb.closest('tr')?.querySelector('.allocation-amount');
                if (amountInput) amountInput.disabled = !cb.checked;
                if (cb.checked) {
                    selectedLiabilities.set(cb.value, {
                        amount: amountInput?.value || cb.dataset.amount,
                        group: cb.dataset.group
                    });
                } else {
                    selectedLiabilities.delete(cb.value);
                }
            }

            document.body.addEventListener('input', function(e) {
                if (e.target.matches('.allocation-amount')) {
                    const checkbox = e.target.closest('tr')?.querySelector('.item-checkbox');
                    if (checkbox?.checked) {
                        selectedLiabilities.set(checkbox.value, { amount: e.target.value, group: checkbox.dataset.group });
                        updateTotal();
                    }
                }
            });

            // Use event delegation on the document body to handle elements replaced by pagination
            document.body.addEventListener('change', function(e) {
                if (e.target.matches('.item-checkbox')) {
                    rememberCheckbox(e.target);
                    syncVisibleSelections();
                } else if (e.target.matches('.select-all-group')) {
                    const group = e.target.dataset.group;
                    const groupCheckboxes = document.querySelectorAll(`.item-checkbox[data-group="${group}"]`);
                    groupCheckboxes.forEach(cb => {
                        cb.checked = e.target.checked;
                        rememberCheckbox(cb);
                    });
                    syncVisibleSelections();
                }
            });

            document.body.addEventListener('submit', function(e) {
                if (e.target.id === 'batchPayForm') {
                    e.target.querySelectorAll('.selected-liability-hidden').forEach(el => el.remove());

                    selectedLiabilities.forEach((item, id) => {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = `allocations[${id}]`;
                        hidden.value = item.amount;
                        hidden.className = 'selected-liability-hidden';
                        e.target.appendChild(hidden);
                    });

                    if (!confirm(`Are you sure you want to record payment for the selected liabilities?`)) {
                        e.preventDefault();
                    }
                }
            });

            // Re-initialize select2 and flatpickr when AJAX replaces the table HTML
            document.addEventListener('tableUpdated', function() {
                if (typeof $ !== 'undefined') {
                    $('#liabilities-table select.select2').each(function() {
                        if (!$(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2({
                                width: '100%',
                                height: '100%',
                                allowClear: true
                            });
                        }
                    });
                }
                
                if (typeof flatpickr !== 'undefined') {
                    flatpickr("#liabilities-table .date", {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d/m/Y",
                        allowInput: true,
                        defaultDate: "",
                        onReady: function(selectedDates, dateStr, instance) {
                            instance.altInput.placeholder = "dd/mm/yyyy";
                        }
                    });
                }

                syncVisibleSelections();
            });

            syncVisibleSelections();
        });
    </script>

    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('payroll.liabilities.index') }}',
            tableSelector: '#liabilities-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
