@extends('layouts.auth')

@section('title', 'Add Statement Line - ' . $bankAccount->name)

@section('content')
<div class="mx-auto px-3 my-8 max-w-5xl">
    <div class="bg-white shadow-md rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Add Statement Line: {{ $bankAccount->name }}</h1>
                <a href="{{ route('account.bank-reconciliation.index', $bankAccount->id) }}" class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4] hover:underline">Cancel</a>
            </div>

            <div class="rounded p-6">
                <form action="{{ route('account.bank-reconciliation.store-line', $bankAccount->id) }}" method="POST">
                    @csrf
                    
                    <div class="grid grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" name="date" class="date w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payee</label>
                            <input type="text" name="payee" class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm" placeholder="Payee Name">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" name="description" class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm" placeholder="Description">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                            <input type="text" name="reference" class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm" placeholder="Reference">
                        </div>
                    </div>

                    <div class="mb-6 w-1/3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                        <div class="text-xs text-gray-500 mb-1">Positive for Money In, Negative for Money Out</div>
                        <input type="number" step="0.01" name="amount" class="w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm font-bold" placeholder="0.00" required>
                    </div>

                    <div class="border-t border-gray-200 pt-4 flex justify-end">
                        <button type="submit" class="bg-[#0078C8] text-white font-bold py-2 px-6 rounded shadow hover:bg-[#0a8de4]">Add Line</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
