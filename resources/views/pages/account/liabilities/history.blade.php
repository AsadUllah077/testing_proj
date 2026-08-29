@extends('layouts.auth')

@section('title', 'Liability Payment History')

@section('content')
    <x-acccount.loader />
    <div class="px-4 py-8 max-w-7xl mx-auto">
        <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-wrap justify-between items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-light text-gray-800">Liability Payment History</h2>
                <p class="text-sm text-gray-500">Review posted and reversed liability payments with their bank and account allocations.</p>
            </div>
            <a href="{{ route('account.liabilities.index') }}"
                class="px-4 py-2 text-xs border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                <i class="fa-solid fa-arrow-left mr-1"></i> Outstanding Liabilities
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white border border-black/10 rounded-lg p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Filtered Payments</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($summary['records']) }}</p>
            </div>
            <div class="bg-white border border-black/10 rounded-lg p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Posted Amount</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($summary['posted_amount'], 2) }}</p>
            </div>
            <div class="bg-white border border-black/10 rounded-lg p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reversed Amount</p>
                <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($summary['reversed_amount'], 2) }}</p>
            </div>
        </div>

        <div class="bg-white border border-black/10 rounded-lg p-5 mb-6">
            <form method="GET" action="{{ route('account.liabilities.history') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
                <div class="xl:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Internal reference, payment reference, payee, bank or liability account"
                        class="w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">From Date</label>
                    <input type="text" name="from_date" value="{{ request('from_date') }}"
                        class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">To Date</label>
                    <input type="text" name="to_date" value="{{ request('to_date') }}"
                        class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-[#0078C8] focus:border-[#0078C8]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Liability Account</label>
                    <select name="account_id" class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm">
                        <option value="">All liability accounts</option>
                        @foreach ($liabilityAccounts as $account)
                            <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>
                                {{ $account->name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Paid From</label>
                    <select name="bank_account_id" class="select2 w-full py-2 px-3 border border-gray-300 rounded text-sm">
                        <option value="">All bank accounts</option>
                        @foreach ($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected((string) request('bank_account_id') === (string) $account->id)>
                                {{ $account->name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full py-2 px-3 border border-gray-300 rounded text-sm">
                        <option value="">All statuses</option>
                        <option value="posted" @selected(request('status') === 'posted')>Posted</option>
                        <option value="reversed" @selected(request('status') === 'reversed')>Reversed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Rows Per Page</label>
                    <select name="per_page" class="w-full py-2 px-3 border border-gray-300 rounded text-sm">
                        @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="xl:col-span-4 flex justify-end gap-2">
                    <a href="{{ route('account.liabilities.history') }}"
                        class="px-4 py-2 text-xs border border-gray-300 text-gray-700 rounded hover:bg-gray-50">Reset</a>
                    <button type="submit"
                        class="px-5 py-2 text-xs text-white bg-[#0078C8] border border-[#0078C8] rounded hover:bg-blue-700">
                        <i class="fa-solid fa-filter mr-1"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-black/10 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-xs uppercase tracking-wide text-gray-600">
                            <th class="px-4 py-3 text-left">Payment Date</th>
                            <th class="px-4 py-3 text-left">References</th>
                            <th class="px-4 py-3 text-left">Payee / Authority</th>
                            <th class="px-4 py-3 text-left">Paid From</th>
                            <th class="px-4 py-3 text-left">Allocations</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Evidence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr class="align-top hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ optional($payment->payment_date)->format('d/m/Y') }}
                                    <span class="block text-xs text-gray-400 mt-1">Created {{ optional($payment->created_at)->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="font-medium text-gray-900">{{ $payment->reference }}</span>
                                    <span class="block text-xs text-gray-500 mt-1">{{ $payment->external_reference ?: 'No external reference' }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ $payment->payee ?: '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    {{ $payment->bankAccount?->name ?: 'Deleted account' }}
                                    @if ($payment->bankAccount?->account_number)
                                        <span class="block text-xs text-gray-500">{{ $payment->bankAccount->account_number }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    <details>
                                        <summary class="cursor-pointer text-[#0078C8] hover:underline">
                                            {{ $payment->allocations->count() }} account(s)
                                        </summary>
                                        <div class="mt-2 space-y-2 min-w-[230px]">
                                            @foreach ($payment->allocations as $allocation)
                                                <div class="flex justify-between gap-4 text-xs border-b border-gray-100 pb-1">
                                                    <span>
                                                        {{ $allocation->account?->name ?: 'Deleted account' }}
                                                        @if ($allocation->account?->account_number)
                                                            ({{ $allocation->account->account_number }})
                                                        @endif
                                                    </span>
                                                    <span class="font-semibold whitespace-nowrap">{{ number_format($allocation->amount, 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>
                                <td class="px-4 py-4 text-sm text-right font-bold text-gray-900 whitespace-nowrap">
                                    {{ number_format($payment->total_amount, 2) }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if ($payment->status === 'posted')
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Posted</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Reversed</span>
                                        @if ($payment->reversal_reason)
                                            <span class="block text-xs text-gray-500 mt-1" title="{{ $payment->reversal_reason }}">Reason available</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if ($payment->attachment)
                                        <a href="{{ route('account.liabilities.history.attachment', $payment->id) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-xs border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white">
                                            <i class="fa-solid fa-download mr-1"></i> Download
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">None</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-14 text-center">
                                    <i class="fa-solid fa-receipt text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-700 font-medium">No liability payments found</p>
                                    <p class="text-sm text-gray-500 mt-1">Change the filters or record a payment from Outstanding Liabilities.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($payments->hasPages() || $payments->total() > 0)
                <div class="px-4 py-3 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">
                        Showing {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} payments
                    </p>
                    <div>{{ $payments->links() }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection