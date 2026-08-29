@extends('layouts.auth')

@section('title', 'Journal Entry Detail')

@section('content')
    @php
        $totalDebit = $entries->sum(fn ($entry) => (float) $entry->debit);
        $totalCredit = $entries->sum(fn ($entry) => (float) $entry->credit);
        $isReversal = filled($journal_entry->reversal_of_unique_no);
    @endphp

    <div class="p-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Journal Entry</h1>
                <p class="text-sm text-gray-500">{{ $journal_entry->unique_no }}</p>
            </div>
            <a href="{{ route('account.journal-entries.index') }}"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Back</a>
        </div>

        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-xs uppercase text-gray-500">Posting Date</div>
                <div class="mt-1 font-semibold">{{ $journal_entry->date->format('d/m/Y') }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-xs uppercase text-gray-500">Status</div>
                <div class="mt-1 font-semibold capitalize">{{ $journal_entry->posting_status ?: 'posted' }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-xs uppercase text-gray-500">Total Debit</div>
                <div class="mt-1 font-semibold">{{ number_format($totalDebit, 2) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="text-xs uppercase text-gray-500">Total Credit</div>
                <div class="mt-1 font-semibold">{{ number_format($totalCredit, 2) }}</div>
            </div>
        </div>

        @if ($opening_balance_batch)
            <div class="border border-amber-300 bg-amber-50 text-amber-900 rounded-lg p-4">
                This journal belongs to opening balance batch
                <a class="font-semibold underline"
                    href="{{ route('account.conversion.opening-balances.show', $opening_balance_batch) }}">{{ $opening_balance_batch->reference }}</a>.
                Reverse it from the batch page so its approval, batch status, journal, and ledger remain synchronized.
            </div>
        @elseif ($reversal)
            <div class="border border-green-300 bg-green-50 text-green-900 rounded-lg p-4">
                This journal has already been reversed by
                <a class="font-semibold underline"
                    href="{{ route('account.journal-entries.show', $reversal->unique_no) }}">{{ $reversal->unique_no }}</a>.
                No additional reversal is required.
                @if (auth()->user()->hasPermission('account.journal-entries.create') && auth()->user()->hasPermission('account.journal-entries.reverse'))
                    <div class="mt-3">
                        <a href="{{ route('account.journal-entries.copy-replacement', $journal_entry->unique_no) }}"
                            class="inline-block px-4 py-2 bg-amber-600 text-white rounded-lg text-sm">Copy to Replacement</a>
                    </div>
                @endif
            </div>
        @elseif ($isReversal)
            <div class="border border-blue-300 bg-blue-50 text-blue-900 rounded-lg p-4">
                This is a controlled reversal of
                <a class="font-semibold underline"
                    href="{{ route('account.journal-entries.show', $journal_entry->reversal_of_unique_no) }}">{{ $journal_entry->reversal_of_unique_no }}</a>.
            </div>
        @elseif (auth()->user()->hasPermission('account.journal-entries.reverse'))
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <h2 class="text-lg font-semibold text-gray-900">Controlled Reversal</h2>
                <p class="mt-1 text-sm text-gray-600">
                    This creates a new journal with opposite debits and credits. The original remains unchanged for audit history.
                </p>
                @if (auth()->user()->hasPermission('account.journal-entries.create'))
                    <a href="{{ route('account.journal-entries.correct', $journal_entry->unique_no) }}"
                        class="inline-block mt-4 px-4 py-2 bg-amber-600 text-white rounded-lg text-sm">
                        Reverse & Copy for Correction
                    </a>
                    <p class="mt-2 text-xs text-gray-500">Recommended when the date, amount, or account is wrong. Every line is copied automatically for review.</p>
                @endif
                <form method="POST" action="{{ route('account.journal-entries.reverse', $journal_entry->unique_no) }}"
                    class="mt-4 grid md:grid-cols-[220px_1fr_auto] gap-3 items-end"
                    onsubmit="return confirm('Post this controlled reversal? The reversal will be a new posted journal.');">
                    @csrf
                    <div>
                        <label for="reversal-date" class="block text-sm font-medium text-gray-700 mb-1">Reversal Date</label>
                        <input id="reversal-date" name="reversal_date" type="text"
                            value="{{ old('reversal_date', $journal_entry->date->format('Y-m-d')) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label for="reversal-reason" class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                        <input id="reversal-reason" name="reason" value="{{ old('reason') }}" maxlength="1000"
                            placeholder="Explain why this posted journal is being reversed"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded-lg text-sm">Post Reversal</button>
                </form>
                <p class="mt-2 text-xs text-gray-500">
                    The date cannot precede {{ $journal_entry->date->format('d/m/Y') }} and must belong to an open accounting period.
                </p>
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Journal Lines</h2>
                <p class="text-sm text-gray-500">{{ $journal_entry->narration ?: $journal_entry->description ?: '-' }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Account</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Debit</th>
                            <th class="px-4 py-3 text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($entries as $entry)
                            <tr>
                                <td class="px-4 py-3">{{ $entry->account?->account_number }} - {{ $entry->account?->name }}</td>
                                <td class="px-4 py-3">{{ $entry->description ?: $entry->item ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $entry->debit, 2) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $entry->credit, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-semibold">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right">Totals</td>
                            <td class="px-4 py-3 text-right">{{ number_format($totalDebit, 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($totalCredit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('reversal-date');
            if (input && typeof flatpickr !== 'undefined') {
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    minDate: @json($journal_entry->date->format('Y-m-d')),
                    allowInput: true
                });
            }
        });
    </script>
@endpush
