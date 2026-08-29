@extends('layouts.auth')

@section('title', 'Opening Balance Detail')

@section('content')
    @php
        $openingBalanceApproverRole = strtolower(trim((string) auth('web')->user()?->role?->name));
        $canApproveOpeningBalance = in_array($openingBalanceApproverRole, ['super admin', 'accountant manager'], true);
    @endphp
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $batch->reference }}</h1>
                <p class="text-sm text-gray-500">Opening balance conversion detail</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('account.conversion.opening-balances.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Back</a>
                @if ($batch->status === 'Draft')
                    <a href="{{ route('account.conversion.opening-balances.edit', $batch) }}" class="px-4 py-2 border border-[#0078C8] text-[#0078C8] rounded-lg text-sm">Edit</a>
                    <form method="POST" action="{{ route('account.conversion.opening-balances.validate', $batch) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Validate</button>
                    </form>
                @elseif ($batch->status === 'Validated' && $canApproveOpeningBalance)
                    <form method="POST" action="{{ route('account.conversion.opening-balances.approve', $batch) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm">Approve</button>
                    </form>
                @elseif ($batch->status === 'Validated')
                    <span class="px-4 py-2 border border-amber-300 bg-amber-50 text-amber-800 rounded-lg text-sm">
                        Awaiting Super Admin or Accountant Manager approval
                    </span>
                @elseif ($batch->status === 'Approved')
                    <form method="POST" action="{{ route('account.conversion.opening-balances.post', $batch) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-700 text-white rounded-lg text-sm">Post</button>
                    </form>
                @elseif ($batch->status === 'Posted')
                    @if (auth()->user()->hasPermission('account.opening-balance-conversion.edit'))
                        <details class="relative">
                            <summary class="cursor-pointer px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm">Continue / Revise Conversion Balances</summary>
                            <form method="POST" action="{{ route('account.conversion.opening-balances.revise', $batch) }}" class="absolute right-0 z-20 mt-2 w-96 space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-xl">
                                @csrf
                                <p class="text-xs text-gray-600">Current targets are copied so you can add remaining accounts or replace incorrect balances. The previous journal is reversed only after the complete new version is approved and posted.</p>
                                <input name="reason" required maxlength="1000" placeholder="Why are the target balances changing?" class="w-full border rounded px-2 py-2 text-sm">
                                <button type="submit" class="w-full px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm">Continue Conversion Balances</button>
                            </form>
                        </details>
                    @endif
                    <details class="relative">
                        <summary class="cursor-pointer px-4 py-2 bg-amber-600 text-white rounded-lg text-sm">Correct Posting Date</summary>
                        <form method="POST" action="{{ route('account.conversion.opening-balances.correct-posting-date', $batch) }}"
                            class="absolute right-0 z-20 mt-2 w-96 space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-xl"
                            onsubmit="return confirm('Correct this posting date? All lines will be copied automatically, the original will be reversed on its original date, and the replacement will post on the selected date.');">
                            @csrf
                            <div class="text-xs text-gray-600">Original posting date: {{ $batch->conversion_date->format('d/m/Y') }}</div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Correct posting date</label>
                                <input id="correct-posting-date-{{ $batch->id }}" name="target_date" type="text" required class="opening-balance-date w-full border rounded px-2 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Correction reason</label>
                                <input name="reason" required maxlength="1000" placeholder="Why was the original date incorrect?" class="w-full border rounded px-2 py-2 text-sm">
                            </div>
                            <p class="text-xs text-amber-800">Both the original-date period and corrected-date period must be open.</p>
                            <button type="submit" class="w-full px-4 py-2 bg-amber-700 text-white rounded-lg text-sm">Correct Date & Copy All Lines</button>
                        </form>
                    </details>
                    <details class="relative">
                        <summary class="cursor-pointer px-4 py-2 bg-red-700 text-white rounded-lg text-sm">Reverse Without Replacement</summary>
                        <form method="POST" action="{{ route('account.conversion.opening-balances.reverse', $batch) }}" class="absolute right-0 z-20 mt-2 w-96 space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-xl">
                            @csrf
                            <input type="text" name="reversal_date" value="{{ $batch->conversion_date->format('Y-m-d') }}" required class="opening-balance-date w-full border rounded px-2 py-2 text-sm">
                            <input name="reason" required maxlength="1000" placeholder="Correction reason" class="w-full border rounded px-2 py-2 text-sm">
                            <button type="submit" class="w-full px-4 py-2 bg-red-700 text-white rounded-lg text-sm">Post Reversal</button>
                        </form>
                    </details>
                @elseif ($batch->status === 'Reversed' && auth()->user()->hasPermission('account.opening-balance-conversion.create'))
                    <details class="relative">
                        <summary class="cursor-pointer px-4 py-2 bg-amber-600 text-white rounded-lg text-sm">Copy to Replacement Draft</summary>
                        <form method="POST" action="{{ route('account.conversion.opening-balances.copy-reversed-batch', $batch) }}"
                            class="absolute right-0 z-20 mt-2 w-96 space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-xl">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Replacement posting date</label>
                                <input name="target_date" type="text" value="{{ $batch->conversion_date->format('Y-m-d') }}" required class="opening-balance-date w-full border rounded px-2 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Reason</label>
                                <input name="reason" required maxlength="1000" placeholder="Why is a replacement required?" class="w-full border rounded px-2 py-2 text-sm">
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-amber-700 text-white rounded-lg text-sm">Copy All Lines to Draft</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>

        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-xs uppercase text-gray-500">Status</div>
                <div class="mt-1 text-lg font-semibold text-gray-900">{{ $batch->status }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-xs uppercase text-gray-500">Total Debit</div>
                <div class="mt-1 text-lg font-semibold text-gray-900">{{ numberFormat($batch->total_debit) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-xs uppercase text-gray-500">Total Credit</div>
                <div class="mt-1 text-lg font-semibold text-gray-900">{{ numberFormat($batch->total_credit) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-xs uppercase text-gray-500">Difference</div>
                <div class="mt-1 text-lg font-semibold {{ abs($difference) < 0.0001 ? 'text-green-700' : 'text-red-700' }}">
                    {{ numberFormat($difference) }}
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Batch Information</h2>
            <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="text-gray-500">Source System</div>
                    <div class="font-medium text-gray-900">{{ $batch->source_system }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Balance As At</div>
                    <div class="font-medium text-gray-900">{{ optional($batch->closing_date)->format('Y-m-d') ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Effective Reporting Date</div>
                    <div class="font-medium text-gray-900">{{ optional($batch->conversion_date)->format('Y-m-d') ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Start Using ERP From</div>
                    <div class="font-medium text-gray-900">{{ optional($batch->go_live_date)->format('Y-m-d') ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Fiscal Year</div>
                    <div class="font-medium text-gray-900">{{ $batch->fiscal_year ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Posted At</div>
                    <div class="font-medium text-gray-900">{{ optional($batch->posted_at)->format('Y-m-d H:i') ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Notes</div>
                    <div class="font-medium text-gray-900">{{ $batch->notes ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border {{ $reconciliation['balanced'] && empty($reconciliation['warnings']) ? 'border-green-200 bg-green-50' : 'border-amber-300 bg-amber-50' }} p-5 text-sm">
            <h2 class="text-lg font-semibold">Conversion Reconciliation</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-4">
                <div>Difference<br><strong>{{ numberFormat($reconciliation['difference']) }}</strong></div>
                <div>Imported P&amp;L result<br><strong>{{ numberFormat($reconciliation['pnl_net_result']) }}</strong></div>
                <div>Historical adjustment<br><strong>{{ numberFormat($reconciliation['historical_adjustment']) }}</strong></div>
                <div>Control status<br><strong>{{ $reconciliation['balanced'] ? 'Balanced' : 'Out of balance' }}</strong></div>
            </div>
            @foreach ($reconciliation['warnings'] as $warning)
                <div class="mt-3 text-amber-900">{{ $warning }}</div>
            @endforeach
            @include('pages.account.conversion.opening-balances.partials.control-reconciliation')
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Imported Lines</h2>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Account</th>
                        <th class="px-4 py-3">{{ $batch->source_system ?: 'Xero' }} Code</th>
                        <th class="px-4 py-3">{{ $batch->source_system ?: 'Xero' }} Name</th>
                        <th class="px-4 py-3 text-right">Debit</th>
                        <th class="px-4 py-3 text-right">Credit</th>
                        <th class="px-4 py-3">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($batch->lines as $line)
                        <tr>
                            <td class="px-4 py-3">
                                {{ $line->account?->account_number }} - {{ $line->account?->name }}
                            </td>
                            <td class="px-4 py-3">{{ $line->source_account_code ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $line->source_account_name ?: '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ numberFormat($line->debit) }}</td>
                            <td class="px-4 py-3 text-right">{{ numberFormat($line->credit) }}</td>
                            <td class="px-4 py-3">{{ $line->description ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No imported lines found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Ledger Transactions</h2>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Account</th>
                        <th class="px-4 py-3 text-right">Debit</th>
                        <th class="px-4 py-3 text-right">Credit</th>
                        <th class="px-4 py-3">Reversal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($batch->transactions as $transaction)
                        <tr>
                            <td class="px-4 py-3">{{ optional($transaction->date)->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ $transaction->unique_no }}</td>
                            <td class="px-4 py-3">{{ $transaction->account?->account_number }} - {{ $transaction->account?->name }}</td>
                            <td class="px-4 py-3 text-right">{{ numberFormat($transaction->debit) }}</td>
                            <td class="px-4 py-3 text-right">{{ numberFormat($transaction->credit) }}</td>
                            <td class="px-4 py-3">
                                @if ($transaction->reversal_of_transaction_id)
                                    Reverses #{{ $transaction->reversal_of_transaction_id }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No ledger transactions yet. Post the batch to create them.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr === 'undefined') return;
            document.querySelectorAll('.opening-balance-date').forEach(function (input) {
                if (input._flatpickr) return;
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true
                });
            });
        });
    </script>
@endpush
