@extends('layouts.auth')

@section('title', 'Tax Period Details')

@section('content')
    <div class="mb-8">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-6">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">{{ $taxPeriod->name }}</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $taxPeriod->start_date->format('M d, Y') }} - {{ $taxPeriod->end_date->format('M d, Y') }}
                        <span class="uppercase">{{ str_replace('_', ' ', $taxPeriod->approval_status) }}</span>
                        · <span class="uppercase">{{ $taxPeriod->status }}</span>
                    </p>
                </div>
                <div class="flex gap-3 mt-4 md:mt-0">
                    <div class="relative">
                        <button onclick="toggleDropdown('exportDropdown')"
                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-xs font-normal rounded hover:bg-[#0078C8] hover:text-white transition-all duration-200 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            EXPORT
                        </button>
                        <div id="exportDropdown"
                            class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                            <button type="button"
                                class="export-btn block w-full text-left px-4 py-2 hover:bg-[#0078C8] hover:text-white"
                                onclick="return false;" data-export-type="tax-period" data-export-format="pdf"
                                data-tax-period-id="{{ $taxPeriod->id }}">
                                <i class="far fa-file-pdf mr-2"></i> PDF
                            </button>
                            <button type="button"
                                class="export-btn block w-full text-left px-4 py-2 hover:bg-[#0078C8] hover:text-white"
                                onclick="return false;" data-export-type="tax-period" data-export-format="xlsx"
                                data-tax-period-id="{{ $taxPeriod->id }}">
                                <i class="fas fa-file-excel mr-2"></i> Excel
                            </button>
                        </div>
                    </div>
                    <a href="{{ route('account.tax-periods.index') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-normal rounded hover:bg-gray-50 transition-all duration-200">
                        BACK TO LIST
                    </a>
                    @if ($taxPeriod->status === 'open' && $taxPeriod->approval_status === 'draft')
                        <form action="{{ route('account.tax-periods.submit', $taxPeriod->id) }}" method="POST">@csrf<button class="px-4 py-2 border border-amber-500 text-amber-700 text-xs rounded">SUBMIT FOR REVIEW</button></form>
                    @elseif ($taxPeriod->status === 'open' && $taxPeriod->approval_status === 'submitted')
                        <form action="{{ route('account.tax-periods.review', $taxPeriod->id) }}" method="POST">@csrf<button class="px-4 py-2 border border-indigo-600 text-indigo-700 text-xs rounded">MARK REVIEWED</button></form>
                    @elseif ($taxPeriod->status === 'open' && $taxPeriod->approval_status === 'reviewed')
                        <form action="{{ route('account.tax-periods.approve', $taxPeriod->id) }}" method="POST">@csrf<button class="px-4 py-2 border border-green-600 text-green-700 text-xs rounded">APPROVE</button></form>
                    @elseif ($taxPeriod->status === 'open' && $taxPeriod->approval_status === 'approved')
                        <form action="{{ route('account.tax-periods.settle', $taxPeriod->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4 mr-1">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                GENERATE SETTLEMENT
                            </button>
                        </form>
                    @elseif($taxPeriod->status === 'settled')
                        <a href="{{ route('account.tax-periods.payment.create', $taxPeriod->id) }}"
                            class="px-4 py-2 bg-green-600 text-white text-xs font-normal rounded hover:bg-green-700 flex items-center shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 8.25H9m6 3H9m3 6l-3-3h1.5a3 3 0 100-6M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            RECORD {{ $taxPeriod->net_amount > 0 ? 'PAYMENT' : 'REFUND' }}
                        </a>
                        @if ((float) $taxPeriod->paid_amount <= 0)
                            <form action="{{ route('account.tax-periods.reverse', $taxPeriod->id) }}" method="POST" class="flex gap-2">@csrf<input name="reason" required maxlength="1000" placeholder="Reversal reason" class="border rounded px-2 text-xs"><button class="px-3 py-2 border border-red-500 text-red-600 text-xs rounded">REVERSE</button></form>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Tax Summary Section -->
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <div class="flex items-center justify-between mb-4"><h3 class="text-sm font-semibold text-gray-800">Filing Evidence</h3><span class="text-xs px-2 py-1 rounded bg-gray-100">{{ ucfirst(str_replace('_', ' ', $taxPeriod->filing_status)) }}</span></div>
                <form action="{{ route('account.tax-periods.filing.update', $taxPeriod->id) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-3">@csrf @method('PUT')
                    <input name="tax_authority" value="{{ $taxPeriod->tax_authority }}" placeholder="Tax authority" class="border rounded px-3 py-2 text-sm"><input name="jurisdiction" value="{{ $taxPeriod->jurisdiction }}" placeholder="Jurisdiction" class="border rounded px-3 py-2 text-sm"><input name="registration_number" value="{{ $taxPeriod->registration_number }}" placeholder="Registration / NTN / VAT no." class="border rounded px-3 py-2 text-sm"><input name="filing_type" value="{{ $taxPeriod->filing_type }}" placeholder="Return / filing type" class="border rounded px-3 py-2 text-sm"><input name="filing_reference" value="{{ $taxPeriod->filing_reference }}" placeholder="Filing acknowledgement" class="border rounded px-3 py-2 text-sm"><input type="date" name="filed_at" value="{{ optional($taxPeriod->filed_at)->format('Y-m-d') }}" class="date border rounded px-3 py-2 text-sm"><select name="filing_status" class="border rounded px-3 py-2 text-sm">@foreach(['not_filed','prepared','filed','accepted','rejected'] as $filingStatus)<option value="{{ $filingStatus }}" {{ $taxPeriod->filing_status === $filingStatus ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$filingStatus)) }}</option>@endforeach</select><input type="file" name="filing_attachment" accept=".pdf,.jpg,.jpeg,.png" class="text-sm"><button class="px-4 py-2 bg-[#0078C8] text-white text-xs rounded">SAVE FILING EVIDENCE</button></form>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Settlement Reconciliation</h3>
                    @forelse ($settlements as $settlement)
                        <div class="border-b py-2 text-sm"><div class="flex justify-between"><span>{{ $settlement->reference }}</span><span class="font-medium">{{ ucfirst($settlement->status) }}</span></div><div class="text-xs text-gray-500 mt-1">Output {{ numberFormat($settlement->output_tax) }} · Input {{ numberFormat($settlement->input_tax) }} · Net {{ numberFormat($settlement->net_amount) }}</div></div>
                    @empty <p class="text-sm text-gray-500">No settlement has been posted.</p> @endforelse
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Payment / Refund Allocations</h3>
                    @forelse ($payments as $payment)
                        <div class="border-b py-2 text-sm"><div class="flex justify-between"><span>{{ $payment->reference }}</span><span class="font-medium">{{ ucfirst($payment->direction) }} {{ numberFormat($payment->amount) }}</span></div><div class="text-xs text-gray-500 mt-1">{{ $payment->payment_date->format('d M Y') }} · {{ $payment->external_reference ?: 'No external reference' }}</div></div>
                    @empty <p class="text-sm text-gray-500">No payments or refunds recorded.</p> @endforelse
                </div>
            </div>
            @if ($taxPeriod->status !== 'open')
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Tax Period Summary</h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Sales Tax
                                    </p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ numberFormat($taxPeriod->total_payable) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Purchase Tax
                                    </p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ numberFormat($taxPeriod->total_receivable) }}</p>
                                </div>
                                @if ($taxPeriod->carried_forward_amount > 0)
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Carried
                                            Forward</p>
                                        <p class="text-lg font-bold text-green-600">
                                            {{ numberFormat($taxPeriod->carried_forward_amount) }}</p>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Net Tax
                                        {{ $taxPeriod->net_amount > 0 ? 'Payable' : 'Refundable' }}</p>
                                    <p
                                        class="text-lg font-bold {{ $taxPeriod->net_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ numberFormat(abs($taxPeriod->net_amount)) }}
                                    </p>
                                    @php $paidAmount = $taxPeriod->paid_amount ?? 0; @endphp
                                    @if ($paidAmount > 0)
                                        <p class="text-xs text-gray-500 mt-1">Paid:
                                            {{ numberFormat($paidAmount) }}</p>
                                        <p
                                            class="text-xs {{ abs($taxPeriod->net_amount) - $paidAmount > 0 ? 'text-orange-600' : 'text-green-600' }} mt-1">
                                            Remaining:
                                            {{ numberFormat(abs($taxPeriod->net_amount) - $paidAmount) }}
                                        </p>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</p>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    @if ($taxPeriod->status === 'settled') @if ($taxPeriod->paid_amount > 0 && $taxPeriod->paid_amount < abs($taxPeriod->net_amount))
                                            bg-orange-100 text-orange-800
                                        @else
                                            bg-yellow-100 text-yellow-800 @endif
@elseif($taxPeriod->status === 'paid')
bg-green-100 text-green-800 @endif">
                                        @if (
                                            $taxPeriod->status === 'settled' &&
                                                $taxPeriod->paid_amount > 0 &&
                                                $taxPeriod->paid_amount < abs($taxPeriod->net_amount))
                                            Settled (Partial)
                                        @else
                                            {{ ucfirst($taxPeriod->status) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="hidden md:block">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-12 h-12 text-blue-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V12zm0 3h.008v.008H6.75V12z" />
                            </svg>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Payable -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center">
                    <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-6 h-6 text-red-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 6.75v.75m0 6.75v.75m15-15h.75m-15 15h.75m15-15v.75m0 6.75v.75m0 6.75v.75M6.75 22.5a.75.75 0 010-1.5h10.5a.75.75 0 010 1.5H6.75zM6.75 1.5a.75.75 0 000 1.5h10.5a.75.75 0 000-1.5H6.75z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Payable (Sales)</p>
                        <h3 class="text-xl font-semibold text-gray-900">{{ numberFormat($balances['payable']) }}</h3>
                    </div>
                </div>

                <!-- Receivable -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center">
                    <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-6 h-6 text-green-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v12m-3-2.818l.879.659c1.546 1.16 3.743 1.16 5.289 0m-5.289-8.402l.879.659c1.546 1.16 3.743 1.16 5.289 0m-5.289 4.352c1.724-1.367 3.312-1.367 5.289 0m-5.289-4.352V18.75m5.289-12.75V15.75" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Refund (Purchases)
                        </p>
                        <h3 class="text-xl font-semibold text-gray-900">{{ numberFormat($balances['receivable']) }}</h3>
                    </div>
                </div>

                <!-- Net Amount -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center">
                    <div
                        class="w-12 h-12 {{ $balances['net'] > 0 ? 'bg-orange-50 text-orange-600' : 'bg-blue-50 text-blue-600' }} rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v17.25m0 0L8.25 16.5m3.75 3.75L15.75 16.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Net
                            {{ $balances['net'] > 0 ? 'Payable' : 'Receivable' }}</p>
                        <h3 class="text-xl font-bold {{ $balances['net'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ numberFormat(abs($balances['net'])) }}
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Transactions Section -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-gray-800">Accounting Transactions</h3>
                    @if ($taxPeriod->unique_no)
                        <span
                            class="text-xs px-2 py-1 bg-blue-50 text-blue-700 rounded border border-blue-100 font-medium">
                            REF: {{ $taxPeriod->unique_no }}
                        </span>
                    @endif
                </div>

                <div class="p-0">
                    @if ($taxPeriod->unique_no)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th
                                            class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                            Date</th>
                                        <th
                                            class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                            Account</th>
                                        <th
                                            class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                                            Description</th>
                                        <th
                                            class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                                            Debit</th>
                                        <th
                                            class="px-6 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider text-right">
                                            Credit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($entries as $entry)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-xs text-gray-600 whitespace-nowrap">
                                                {{ $entry->date->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-800">{{ $entry->account->name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $entry->description }}</td>
                                            <td
                                                class="px-6 py-4 text-sm text-right text-green-600 font-medium whitespace-nowrap">
                                                {{ $entry->debit > 0 ? numberFormat($entry->debit) : '' }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-sm text-right text-red-600 font-medium whitespace-nowrap">
                                                {{ $entry->credit > 0 ? numberFormat($entry->credit) : '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-6 py-16 text-center">
                            <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 mb-1">No transactions found</h4>
                            <p class="text-xs text-gray-500 mb-6">This period has not been settled yet.</p>
                            @if ($taxPeriod->status === 'open')
                                <form action="{{ route('account.tax-periods.settle', $taxPeriod->id) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] transition-all">
                                        GENERATE SETTLEMENT JOURNAL
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

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

        // Handle tax period export buttons
        document.addEventListener('DOMContentLoaded', function() {
            const exportButtons = document.querySelectorAll('.export-btn');

            exportButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const exportType = this.getAttribute('data-export-type');
                    const exportFormat = this.getAttribute('data-export-format');
                    const taxPeriodId = this.getAttribute('data-tax-period-id');

                    // Close dropdown
                    document.getElementById('exportDropdown').classList.add('hidden');

                    // Show notification
                    showNotification();
                    resetNotificationProgress();

                    // Make AJAX request to start export
                    fetch('{{ route('export') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                format: exportFormat,
                                type: exportType,
                                tax_period_id: taxPeriodId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                currentJobId = data.job_id;
                                localStorage.setItem('pendingExportJobId', currentJobId);
                                checkExportProgress();
                            } else {
                                showNotificationError(data.message);
                            }
                        })
                        .catch(error => {
                            showNotificationError('Failed to start export: ' + error.message);
                        });
                });
            });
        });
    </script>
@endsection
