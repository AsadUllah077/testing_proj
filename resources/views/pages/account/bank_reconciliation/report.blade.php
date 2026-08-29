@extends('layouts.auth')

@section('title', 'Bank Reconciliation Summary - ' . $bankAccount->name)

@push('styles')
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background-color: #fff !important;
            }

            .print-border-none {
                border: none !important;
                box-shadow: none !important;
            }

            .print-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto px-3 my-8 max-w-4xl print-container">
        <!-- Action Buttons -->
        <div class="flex justify-end mb-4 no-print gap-3">
            <a href="{{ route('account.bank-reconciliation.index', $bankAccount->id) }}"
                class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-sm font-medium transition-colors">Back</a>
            <a href="{{ route('account.bank-reconciliation.report.export-pdf', $bankAccount->id) }}"
                class="px-4 py-2 bg-[#0078C8] text-white rounded hover:bg-[#006bb0] text-sm font-medium transition-colors">Export
                PDF</a>
        </div>

        <!-- Report Container -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg print-border-none">
            <!-- Header -->
            <div class="p-8 text-center border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Bank Reconciliation Summary</h1>
                <h2 class="text-lg text-gray-700 font-medium">{{ auth('web')->user()->company->name ?? 'Company Name' }}</h2>
                <h3 class="text-md text-gray-600 mt-1">{{ $bankAccount->name }}</h3>
                <p class="text-sm text-gray-500 mt-2">As at {{ \Carbon\Carbon::parse($cutoff)->format('d M Y') }}</p>
                @if($statement)
                    <p class="text-xs text-gray-500 mt-1">Statement {{ $statement->statement_number }} &middot; {{ ucfirst($statement->status) }} &middot; {{ $statement->currency }}</p>
                @endif
            </div>

            <!-- Summary Table -->
            <div class="p-8">
                <div class="max-w-2xl mx-auto">
                    <table class="w-full text-sm mb-8">
                        <tbody>
                            <tr class="bg-gray-50"><td colspan="2" class="py-2 px-3 font-bold">Balance per bank</td></tr>
                            <tr><td class="py-2 px-3">Declared closing balance</td><td class="py-2 px-3 text-right">{{ number_format($declaredClosingBalance, 2) }}</td></tr>
                            <tr><td class="py-2 px-3 text-gray-600">Plus: deposits in transit</td><td class="py-2 px-3 text-right">{{ number_format($depositsInTransit, 2) }}</td></tr>
                            <tr><td class="py-2 px-3 text-gray-600">Less: outstanding payments</td><td class="py-2 px-3 text-right">({{ number_format($outstandingCheques, 2) }})</td></tr>
                            <tr class="border-t font-bold"><td class="py-3 px-3">Adjusted bank balance</td><td class="py-3 px-3 text-right">{{ number_format($adjustedBankBalance, 2) }}</td></tr>
                            <tr class="bg-gray-50"><td colspan="2" class="py-2 px-3 pt-6 font-bold">Balance per books</td></tr>
                            <tr><td class="py-2 px-3">Ledger balance at cutoff</td><td class="py-2 px-3 text-right">{{ number_format($ledgerBalance, 2) }}</td></tr>
                            <tr><td class="py-2 px-3 text-gray-600">Plus: bank-only receipts</td><td class="py-2 px-3 text-right">{{ number_format($bankOnlyReceipts, 2) }}</td></tr>
                            <tr><td class="py-2 px-3 text-gray-600">Less: bank-only payments</td><td class="py-2 px-3 text-right">({{ number_format($bankOnlyPayments, 2) }})</td></tr>
                            <tr class="border-t font-bold"><td class="py-3 px-3">Adjusted book balance</td><td class="py-3 px-3 text-right">{{ number_format($adjustedBookBalance, 2) }}</td></tr>
                            <tr class="border-t-2">
                                <td class="py-3 px-3 font-bold">Reconciliation difference</td>
                                <td class="py-3 px-3 text-right font-bold {{ abs($variance) >= 0.005 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($variance, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    @if($statement)
                        <div class="grid grid-cols-3 gap-3 mb-8 text-xs">
                            <div class="border rounded p-3"><span class="block text-gray-500">Opening</span><strong>{{ number_format($openingBalance, 2) }}</strong></div>
                            <div class="border rounded p-3"><span class="block text-gray-500">Movements</span><strong>{{ number_format($statementMovements, 2) }}</strong></div>
                            <div class="border rounded p-3"><span class="block text-gray-500">Statement control difference</span><strong class="{{ abs($statementVariance) >= 0.005 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($statementVariance, 2) }}</strong></div>
                        </div>
                    @endif

                    <table class="hidden w-full text-sm">
                        <tbody>
                            <!-- Balance in Xero -->
                            <tr>
                                <td class="py-3 font-medium text-gray-800">Balance in Xero</td>
                                <td class="py-3 text-right font-medium text-gray-800">{{ number_format($ledgerBalance, 2) }}
                                </td>
                            </tr>

                            <!-- Outstanding Payments -->
                            @if ($outstandingPayments->count() > 0)
                                <tr>
                                    <td class="py-2 text-gray-600 pl-4">Plus Outstanding Payments</td>
                                    <td class="py-2 text-right text-gray-600">
                                        {{ number_format($outstandingPayments->sum('credit'), 2) }}</td>
                                </tr>
                            @endif

                            <!-- Outstanding Receipts -->
                            @if ($outstandingReceipts->count() > 0)
                                <tr>
                                    <td class="py-2 text-gray-600 pl-4">Less Outstanding Receipts</td>
                                    <td class="py-2 text-right text-gray-600">
                                        ({{ number_format($outstandingReceipts->sum('debit'), 2) }})</td>
                                </tr>
                            @endif

                            <!-- Unreconciled Statement Lines (Receipts) -->
                            @php $unreconciledReceipts = $unreconciledStatementLines->where('amount', '>', 0); @endphp
                            @if ($unreconciledReceipts->count() > 0)
                                <tr>
                                    <td class="py-2 text-gray-600 pl-4">Plus Unreconciled Statement Lines</td>
                                    <td class="py-2 text-right text-gray-600">
                                        {{ number_format($unreconciledReceipts->sum('amount'), 2) }}</td>
                                </tr>
                            @endif

                            <!-- Unreconciled Statement Lines (Payments) -->
                            @php $unreconciledPayments = $unreconciledStatementLines->where('amount', '<', 0); @endphp
                            @if ($unreconciledPayments->count() > 0)
                                <tr>
                                    <td class="py-2 text-gray-600 pl-4">Less Unreconciled Statement Lines</td>
                                    <td class="py-2 text-right text-gray-600">
                                        ({{ number_format(abs($unreconciledPayments->sum('amount')), 2) }})</td>
                                </tr>
                            @endif

                            <!-- Statement Balance -->
                            <tr class="border-t-2 border-gray-800">
                                <td class="py-3 font-bold text-gray-900">Statement Balance</td>
                                <td class="py-3 text-right font-bold text-gray-900">
                                    {{ number_format($adjustedBalance, 2) }}</td>
                            </tr>

                            <!-- Imported Statement Balance -->
                            <tr>
                                <td class="py-3 pt-6 font-medium text-gray-700">Imported Statement Balance</td>
                                <td class="py-3 pt-6 text-right font-medium text-gray-700">
                                    {{ number_format($statementBalance, 2) }}</td>
                            </tr>

                            <!-- Difference -->
                            <tr class="border-t border-gray-200">
                                <td class="py-3 font-bold text-gray-900">Difference</td>
                                <td
                                    class="py-3 text-right font-bold {{ $variance != 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($variance, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Detailed Lists -->
                <div class="mt-12 space-y-10">

                    <!-- Outstanding Payments Details -->
                    @if ($outstandingPayments->count() > 0)
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 mb-3 border-b border-gray-300 pb-2">Outstanding
                                Payments</h4>
                            <div class="overflow-x-auto overflow-y-auto max-h-[80vh]">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-gray-500 font-medium">
                                        <tr>
                                            <th class="py-2 w-32">Date</th>
                                            <th class="py-2">Description</th>
                                            <th class="py-2 w-48">Reference</th>
                                            <th class="py-2 text-right w-32">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($outstandingPayments as $item)
                                            <tr>
                                                <td class="py-2">{{ $item->date?->format('d M Y') }}</td>
                                                <td class="py-2">{{ $item->description }}</td>
                                                <td class="py-2 text-gray-500">{{ $item->reference_id }}</td>
                                                <td class="py-2 text-right">{{ number_format($item->credit, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-gray-300 font-medium text-gray-800">
                                            <td colspan="3" class="py-2 text-right">Total</td>
                                            <td class="py-2 text-right">
                                                {{ number_format($outstandingPayments->sum('credit'), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Outstanding Receipts Details -->
                    @if ($outstandingReceipts->count() > 0)
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 mb-3 border-b border-gray-300 pb-2">Outstanding
                                Receipts</h4>
                            <div class="overflow-x-auto overflow-y-auto max-h-[80vh]">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-gray-500 font-medium">
                                        <tr>
                                            <th class="py-2 w-32">Date</th>
                                            <th class="py-2">Description</th>
                                            <th class="py-2 w-48">Reference</th>
                                            <th class="py-2 text-right w-32">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($outstandingReceipts as $item)
                                            <tr>
                                                <td class="py-2">{{ $item->date?->format('d M Y') }}</td>
                                                <td class="py-2">{{ $item->description }}</td>
                                                <td class="py-2 text-gray-500">{{ $item->reference_id }}</td>
                                                <td class="py-2 text-right">{{ number_format($item->debit, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-gray-300 font-medium text-gray-800">
                                            <td colspan="3" class="py-2 text-right">Total</td>
                                            <td class="py-2 text-right">
                                                {{ number_format($outstandingReceipts->sum('debit'), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Unreconciled Statement Lines Details -->
                    @if ($unreconciledStatementLines->count() > 0)
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 mb-3 border-b border-gray-300 pb-2">Unreconciled
                                Statement Lines</h4>
                            <div class="overflow-x-auto overflow-y-auto max-h-[80vh]">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-gray-500 font-medium">
                                        <tr>
                                            <th class="py-2 w-32">Date</th>
                                            <th class="py-2">Description</th>
                                            <th class="py-2 w-48">Reference</th>
                                            <th class="py-2 text-right w-32">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($unreconciledStatementLines as $item)
                                            <tr>
                                                <td class="py-2">{{ $item->date?->format('d M Y') }}</td>
                                                <td class="py-2">
                                                    {{ $item->payee ? $item->payee . ' - ' : '' }}{{ $item->description }}
                                                </td>
                                                <td class="py-2 text-gray-500">{{ $item->reference }}</td>
                                                <td class="py-2 text-right">{{ number_format($item->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-gray-300 font-medium text-gray-800">
                                            <td colspan="3" class="py-2 text-right">Total</td>
                                            <td class="py-2 text-right">
                                                {{ number_format($unreconciledStatementLines->sum('amount'), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($outstandingReceipts->isEmpty() && $outstandingPayments->isEmpty() && $unreconciledStatementLines->isEmpty())
                        <div class="text-center text-gray-500 py-8 italic">No outstanding items. Everything is perfectly
                            reconciled!</div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
