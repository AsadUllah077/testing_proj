@extends('layouts.auth')

@section('title', 'Receive Money - ' . $bankAccount->name)

@section('content')
    <div class="mx-auto px-3 my-8 max-w-5xl">
        <div class="bg-white shadow-md rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Receive Money: {{ $bankAccount->name }}</h1>
                    <a href="{{ route('account.bank-reconciliation.index', $bankAccount->id) }}"
                        class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4] hover:underline">Cancel</a>
                </div>

                <div class="rounded p-6">
                    <form action="{{ route('account.bank-reconciliation.receive-money.store', $bankAccount->id) }}"
                        method="POST">
                        @csrf

                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                                <input type="text" name="payee"
                                    class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm"
                                    placeholder="Payer Name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" name="date"
                                    class="date w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm"
                                    required value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Details</label>
                                <input type="text" name="description"
                                    class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm"
                                    placeholder="Description" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                                <input type="text" name="reference"
                                    class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm"
                                    placeholder="Reference">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account (Revenue/Asset)</label>
                            <select name="contra_account_id"
                                class="select2 w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm bg-white"
                                required>
                                <option value="">Select Account...</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}
                                        ({{ $acc->code ?? $acc->account_number }})
                                        - {{ $acc->type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6 w-1/3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <input type="number" step="0.01" name="amount"
                                class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm font-bold"
                                placeholder="0.00" required>
                        </div>

                        <div class="border-t border-gray-200 pt-4 flex justify-end">
                            <button type="submit"
                                class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
