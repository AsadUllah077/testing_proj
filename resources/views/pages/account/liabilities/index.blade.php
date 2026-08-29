@extends('layouts.auth')

@section('title', 'Liability Payments')

@section('content')
    <x-acccount.loader />
    <div class="px-4 py-8 max-w-7xl mx-auto">

        <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-light text-gray-800">Accounting Liabilities</h2>
                <p class="text-sm text-gray-500">View and settle outstanding tax and other liability account balances.</p>
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <a href="{{ route('account.liabilities.history') }}"
                    class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8] whitespace-nowrap">
                    <i class="fa-solid fa-clock-rotate-left mr-1"></i> Payment History
                </a>
                <form method="GET" class="flex items-center gap-4" id="filter-form">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Filter Account</label>
                        <select name="account_id"
                            class="block w-64 border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            <option value="">All Liability Accounts</option>
                            @foreach ($liabilityAccounts as $acc)
                                <option value="{{ $acc->id }}"
                                    {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }} ({{ $acc->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2 items-end mt-5">
                        <button type="button" id="search-btn"
                            class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                            <span id="search-text">Apply</span>
                            <span id="search-loader" class="hidden ml-2">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Container -->
        <div id="liabilities-table">
            @include('pages.account.liabilities.partials.table')
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to re-bind event listeners after AJAX load
            function bindEvents() {
                const checkboxes = document.querySelectorAll('.item-checkbox');
                const selectAllGroups = document.querySelectorAll('.select-all-group');
                const totalAmountDisplay = document.getElementById('totalAmount');
                const selectedCountDisplay = document.getElementById('selectedCount');
                const submitBtn = document.getElementById('submitBtn');

                if (!totalAmountDisplay) return;

                function updateTotal() {
                    let total = 0;
                    let count = 0;
                    checkboxes.forEach(cb => {
                        const row = cb.closest('tr');
                        const amountInput = row?.querySelector('.allocation-amount');
                        const accountInput = row?.querySelector('.allocation-account');
                        if (amountInput) amountInput.disabled = !cb.checked;
                        if (accountInput) accountInput.disabled = !cb.checked;
                        if (cb.checked) {
                            total += parseFloat(amountInput?.value || cb.dataset.amount);
                            count++;
                        }
                    });
                    totalAmountDisplay.textContent = new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2
                    }).format(total);
                    selectedCountDisplay.textContent = `${count} items selected`;
                    submitBtn.disabled = count === 0;
                }

                checkboxes.forEach(cb => {
                    cb.addEventListener('change', updateTotal);
                    cb.closest('tr')?.querySelector('.allocation-amount')?.addEventListener('input', updateTotal);
                });

                selectAllGroups.forEach(sag => {
                    sag.addEventListener('change', function() {
                        const group = this.dataset.group;
                        const groupCheckboxes = document.querySelectorAll(
                            `.item-checkbox[data-group="${group}"]`);
                        groupCheckboxes.forEach(cb => {
                            cb.checked = this.checked;
                        });
                        updateTotal();
                    });
                });

                const form = document.getElementById('batchPayForm');
                if (form) {
                    form.onsubmit = function() {
                        const count = document.querySelectorAll('.item-checkbox:checked').length;
                        return confirm(
                            `Are you sure you want to record payment for the ${count} selected liability items?`
                            );
                    };
                }
            }

            bindEvents();

            // Re-bind on AJAX success from ReportFilter if used, or manually handle search
            document.getElementById('search-btn').addEventListener('click', function() {
                const form = document.getElementById('filter-form');
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                const loader = document.getElementById('search-loader');
                const text = document.getElementById('search-text');

                loader.classList.remove('hidden');
                text.classList.add('hidden');

                fetch(`{{ route('account.liabilities.index') }}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('liabilities-table').innerHTML = data.html;
                        bindEvents();
                        loader.classList.add('hidden');
                        text.classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loader.classList.add('hidden');
                        text.classList.remove('hidden');
                    });
            });
        });
    </script>
@endpush
