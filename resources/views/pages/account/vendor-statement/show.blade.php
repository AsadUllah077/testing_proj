@extends('layouts.auth')
@section('title', 'Vendor Statement - ' . $vendor->name)

@section('content')
    @php
        $statementQuery = array_filter(
            [
                'from_date' => $filters['from_date'] ?? null,
                'to_date' => $filters['to_date'] ?? $asOf,
                'invoice_no' => $filters['invoice_no'] ?? null,
                'per_page' => $filters['per_page'] ?? null,
            ],
            fn($value) => $value !== null && $value !== '',
        );
    @endphp

    <div class="p-4">
        <div class="mb-4 rounded-lg border border-black/10 bg-white p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <a href="{{ route('account.vendor-statements.index') }}" class="text-sm text-[#0078C8] hover:underline">
                        Back to Statements
                    </a>
                    <h1 class="mt-1 text-xl font-bold text-gray-800">{{ $vendor->name }}</h1>
                    <p class="text-sm text-gray-500">
                        Statement from
                        {{ $filters['from_date'] ?? null ? dateFormat($filters['from_date']) : 'Beginning' }}
                        to {{ dateFormat($asOf) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('account.vendor-statements.pdf', [$vendor] + $statementQuery) }}"
                        class="flex items-center justify-center rounded border border-[#0078C8] px-4 py-2 text-center text-xs font-normal text-[#0078C8] hover:bg-[#0078C8] hover:text-white">
                        Export PDF
                    </a>
                    @if ($vendor->email)
                        <form method="POST" action="{{ route('account.vendor-statements.email', $vendor) }}">
                            @csrf
                            @foreach ($statementQuery as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <button type="submit"
                                class="rounded-[3px] bg-[#0078C8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0583d7]">
                                Email Statement
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">From Date</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                        class="date w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">To Date</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? $asOf }}"
                        class="date w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Bill #</label>
                    <input type="text" name="invoice_no" value="{{ $filters['invoice_no'] ?? '' }}"
                        placeholder="Bill or unique no"
                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-[#0078C8] focus:outline-none">
                </div>
                <div>
                    <x-default-page />
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="w-full rounded bg-[#0078C8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0583d7]">
                        Filter
                    </button>
                    <a href="{{ route('account.vendor-statements.show', $vendor) }}"
                        class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-6 grid grid-cols-3 gap-4">
            <div class="rounded-[5px] border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Total Billed</p>
                <p class="mt-1 text-2xl font-bold text-gray-800">{{ numberFormat($totalBilled) }}</p>
            </div>
            <div class="rounded-[5px] border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Total Paid</p>
                <p class="mt-1 text-2xl font-bold text-green-600">{{ numberFormat($totalPaid) }}</p>
            </div>
            <div class="rounded-[5px] border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Outstanding Balance</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ numberFormat($totalOutstanding) }}</p>
                @if ($overdueBills->count() > 0)
                    <p class="mt-1 text-xs text-red-500">{{ $overdueBills->count() }} bill(s) overdue</p>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-[5px] border border-gray-200 bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 p-4">
                <h2 class="text-sm font-bold text-gray-800">Transaction History</h2>
                <p class="text-xs text-gray-500">{{ $bills->total() }} filtered record(s)</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Bill #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Due Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Paid</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Balance</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bills as $bill)
                            @php
                                $dueDate = \Carbon\Carbon::parse($bill->date)->addDays(30);
                                $isOverdue = $bill->remaining_amount > 0 && $dueDate->lt(\Carbon\Carbon::parse($asOf));
                            @endphp
                            <tr class="border-b border-gray-100 hover:bg-gray-50 {{ $isOverdue ? 'bg-red-50/40' : '' }}">
                                <td class="px-4 py-3">
                                    <a href="{{ route('account.bill-invoice.show', $bill->id) }}"
                                        class="font-semibold text-[#0078C8] hover:underline">
                                        {{ $bill->invoice_no ?: $bill->unique_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ dateFormat($bill->date) }}</td>
                                <td class="px-4 py-3 {{ $isOverdue ? 'font-semibold text-red-600' : 'text-gray-600' }}">
                                    {{ $dueDate->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-800">{{ numberFormat($bill->total) }}</td>
                                <td class="px-4 py-3 text-right text-green-600">{{ numberFormat($bill->paid_amount) }}</td>
                                <td
                                    class="px-4 py-3 text-right font-semibold {{ $bill->remaining_amount > 0 ? 'text-red-600' : 'text-gray-500' }}">
                                    {{ numberFormat($bill->remaining_amount) }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($bill->remaining_amount <= 0)
                                        <span
                                            class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Paid</span>
                                    @elseif ($isOverdue)
                                        <span
                                            class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-600">Overdue</span>
                                    @else
                                        <span
                                            class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Unpaid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">No bills found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right text-sm font-bold text-gray-700">Filtered Totals
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-800">{{ numberFormat($totalBilled) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-green-600">{{ numberFormat($totalPaid) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-red-600">{{ numberFormat($totalOutstanding) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="border-t border-gray-100 p-4">
                <x-data-table-paginatior :paginator="$bills" />
            </div>
        </div>
    </div>
@endsection
