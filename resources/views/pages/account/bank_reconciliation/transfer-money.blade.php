@extends('layouts.auth')

@section('title', 'Transfer Money - ' . $bankAccount->name)

@section('content')
    <div class="mx-auto px-3 my-8 max-w-5xl">
        <div class="bg-white shadow-md rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Transfer Money</h1>
                    <a href="{{ route('account.bank-reconciliation.index', $bankAccount->id) }}"
                        class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4] hover:underline">Cancel</a>
                </div>

                <div class="rounded p-6">
                    <form action="{{ route('account.bank-reconciliation.transfer-money.store', $bankAccount->id) }}"
                        method="POST">
                        @csrf

                        <div class="flex items-center gap-4 mb-6">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">From Account</label>
                                <div
                                    class="border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 bg-gray-100 text-gray-700 font-medium">
                                    {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                                </div>
                            </div>
                            <div class="pt-6">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">To Account</label>
                                <select name="target_account_id"
                                    class="select2 w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm bg-white"
                                    required>
                                    <option value="">Select Bank Account...</option>
                                    @foreach ($otherBanks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}
                                            ({{ $bank->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" name="date"
                                    class="date w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm"
                                    required value="{{ date('Y-m-d') }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                                <input type="text" name="reference"
                                    class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm"
                                    placeholder="Reference">
                            </div>
                        </div>

                        <div class="mb-6 w-1/3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <input type="number" step="0.01" name="amount"
                                class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm font-bold"
                                placeholder="0.00" required>
                        </div>

                        <div class="border-t border-gray-200 pt-4 flex justify-end">
                            <button type="submit"
                                class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">Transfer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
