{{-- before ui enhancement like xero have --}}
@extends('layouts.auth')

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>
@endpush

@section('title')
    Bank Reconciliation - {{ $bankAccount->name }}
@endsection

@section('styles')
    <style>
        :root {
            --xero-blue: #0078C8;
            --xero-green: #1e8e3e;
            --xero-bg: #f4f7f9;
        }

        .reconcile-container {
            background-color: var(--xero-bg);
            min-height: calc(100vh - 200px);
        }

        .xero-card {
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e4e8;
        }

        .btn-ok {
            background-color: var(--xero-blue);
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-ok:hover {
            background-color: #005a96;
        }

        .btn-ok:active {
            transform: scale(0.98);
        }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        /* Transitions */
        [x-cloak] {
            display: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="mx-auto px-3 my-8" x-data="reconciliationApp" @reconcile-refresh.window="_handleReconcileRefresh()">
        <div class="bg-white shadow-md rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Reconcile: {{ $bankAccount->name }}
                            ({{ $bankAccount->acc_no ?? $bankAccount->account_number }})</h1>
                        <p class="text-gray-500">Statement Balance: <strong>{{ currency()['symbol'] ?? 'RS' }}
                                {{ numberFormat($statementBalance) }}</strong> | Account Balance:
                            <strong>{{ currency()['symbol'] ?? 'RS' }} {{ numberFormat($ledgerBalance) }}</strong></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Manage Account Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.away="open = false"
                                class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-2 rounded shadow-sm hover:bg-gray-200 flex items-center font-medium text-sm transition-all">
                                Manage Account
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95" style="display: none;"
                                class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded shadow-xl z-50 py-1 overflow-hidden">
                                <div
                                    class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider bg-gray-50 border-b border-gray-100 mb-1">
                                    Common Actions</div>
                                <a href="{{ route('account.bank-reconciliation.spend-money', $bankAccount->id) }}"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium">Spend
                                    Money</a>
                                <a href="{{ route('account.bank-reconciliation.receive-money', $bankAccount->id) }}"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium">Receive
                                    Money</a>
                                <a href="{{ route('account.bank-reconciliation.transfer-money', $bankAccount->id) }}"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium">Transfer
                                    Money</a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="{{ route('account.bank-reconciliation.create-line', $bankAccount->id) }}"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium">Add
                                    Statement Line</a>
                                <a href="{{ route('account.bank-reconciliation.report', $bankAccount->id) }}"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium">Reconciliation
                                    Report</a>
                                <a :href="statementExportUrl()" @click="open = false"
                                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium">Export
                                    Bank Statement</a>
                                <form action="{{ route('account.bank-reconciliation.unreconcile-all', $bankAccount->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to revert ALL reconciliation for this account? This will disconnect all matched statement lines.')">
                                    @csrf
                                    <button type="submit"
                                        class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 font-medium">Revert
                                        All Reconciliation</button>
                                </form>
                            </div>
                        </div>

                        <!-- Xero Style Import Button -->
                        <div>
                            <button @click="showImport = !showImport"
                                class="bg-[#0078C8] text-white px-5 py-2 rounded-md shadow hover:bg-[#005a96] flex items-center font-bold text-sm transition-all active:transform active:scale-95">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12"></path>
                                </svg>
                                Import Statement
                            </button>

                            <!-- Import Overlay/Modal -->
                            <div x-show="showImport" style="display: none;"
                                class="fixed inset-0 bg-black/50 z-60 flex items-center justify-center p-4">
                                <div @click.away="showImport = false"
                                    class="bg-white rounded-lg shadow-2xl max-w-md w-full p-8 relative">
                                    <button @click="showImport = false"
                                        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                    <h3 class="text-xl font-bold text-gray-800 mb-4">Import Bank Statement</h3>
                                    <p class="text-sm text-gray-600 mb-6 leading-relaxed">Select a CSV file to import your
                                        statement lines. You can use our template to ensure the formatting is correct.</p>

                                    <form @submit.prevent="handleImport" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-6">
                                            <div
                                                class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors group relative cursor-pointer">
                                                <input type="file" name="file" id="csvFile"
                                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                    accept=".csv" required
                                                    @change="selectedFileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                                <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-blue-500 transition-colors"
                                                    :class="{ 'text-blue-500': selectedFileName }" fill="none"
                                                    stroke="currentColor" viewBox="0 0 48 48">
                                                    <path
                                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <div class="mt-2 text-sm font-medium">
                                                    <template x-if="!selectedFileName">
                                                        <p class="text-gray-600">Click to upload or drag and drop</p>
                                                    </template>
                                                    <template x-if="selectedFileName">
                                                        <p class="text-blue-600 flex items-center justify-center">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor"
                                                                viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            <span x-text="selectedFileName"></span>
                                                        </p>
                                                    </template>
                                                </div>
                                                <p class="text-xs text-gray-400" x-show="!selectedFileName">CSV files only
                                                </p>
                                                <p class="text-xs text-blue-400" x-show="selectedFileName">Click to change
                                                    file</p>
                                            </div>
                                        </div>

                                        <!-- Progress Bar UI -->
                                        <div x-show="importProgress.show" class="mb-6">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-xs font-semibold text-[#0078C8]"
                                                    x-text="importProgress.status"></span>
                                                <span class="text-xs font-semibold text-[#0078C8]"
                                                    x-text="importProgress.percentage + '%'"></span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-[#0078C8] h-2.5 rounded-full transition-all duration-300"
                                                    :style="'width: ' + importProgress.percentage + '%'"></div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <a href="{{ route('account.bank-reconciliation.template.download') }}"
                                                class="text-[#0078C8] hover:underline text-sm font-medium flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4 4m4 4V4">
                                                    </path>
                                                </svg>
                                                Download Template
                                            </a>
                                            <button type="submit" :disabled="importProgress.show"
                                                class="bg-[#0078C8] text-white px-6 py-2.5 rounded-md font-bold hover:bg-[#005a96] shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span x-show="!importProgress.show">Import Now</span>
                                                <span x-show="importProgress.show">Importing...</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Top Level Tabs (Xero Style) -->
                <div>
                    <!-- Edit Modal -->
                    <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-70 overflow-y-auto"
                        aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div
                            class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div x-show="showEditModal" x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50 transition-opacity"
                                aria-hidden="true" @click="showEditModal = false"></div>

                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                aria-hidden="true">&#8203;</span>

                            <div x-show="showEditModal" x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                                @click.stop>
                                <form :action="'{{ url('account/bank-reconciliation/line') }}/' + editingLine.id"
                                    method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" id="modal-title">
                                            Edit Entry</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Date</label>
                                                <input type="date" name="date" id="editDateInput"
                                                    x-model="editingLine.date" required
                                                    class="date mt-1 w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Amount</label>
                                                <input type="number" step="0.01" name="amount"
                                                    x-model="editingLine.amount" required
                                                    class="mt-1 w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm">
                                                <p class="mt-1 text-xs text-gray-500">Negative for spent, positive for
                                                    received.</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Payee</label>
                                                <input type="text" name="payee" x-model="editingLine.payee"
                                                    class="mt-1 w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                                <textarea name="description" x-model="editingLine.description" rows="2"
                                                    class="mt-1 w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm"></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Reference</label>
                                                <input type="text" name="reference" x-model="editingLine.reference"
                                                    class="mt-1 w-full border outline-none border-gray-200 focus:border-[#0078C8] focus:ring-[#0078C8] focus:ring-1 rounded px-3 py-2 text-sm">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit"
                                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#076fb5] text-base font-medium text-white hover:bg-[#005a96] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Save
                                            Changes</button>
                                        <button type="button" @click="showEditModal = false"
                                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 flex flex-col gap-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="relative flex-1 min-w-[200px] max-w-md">
                                <input type="text" x-model="search"
                                    @input.debounce.300ms="fetchLines(1); fetchTransactions(1); fetchAllLines(1)"
                                    placeholder="Search reference, description, payee..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchDateFrom" x-ref="searchDateFromInput" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-32 bg-white" placeholder="Date From">
                                <input type="text" x-model="searchDateTo" x-ref="searchDateToInput" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-32 bg-white" placeholder="Date To">
                                <input type="number" step="0.01" x-model="searchMinAmount" @input.debounce.300ms="fetchLines(1); fetchTransactions(1); fetchAllLines(1)" placeholder="Min Amount" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-28">
                                <span class="text-sm text-gray-400">-</span>
                                <input type="number" step="0.01" x-model="searchMaxAmount" @input.debounce.300ms="fetchLines(1); fetchTransactions(1); fetchAllLines(1)" placeholder="Max Amount" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-28">
                            </div>

                            <div class="flex items-center gap-2">
                                <label class="text-xs font-medium text-gray-500 whitespace-nowrap">Per Page</label>
                                <select x-model="perPage" @change="fetchLines(1); fetchTransactions(1); fetchAllLines(1)"
                                    class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-20 p-2 py-1.5">
                                    @foreach (config('default.per_page') as $value)
                                        <option value="{{ $value }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button x-show="search || searchDateFrom || searchDateTo || searchMinAmount || searchMaxAmount"
                                @click="search = ''; searchDateFrom = ''; searchDateTo = ''; searchMinAmount = ''; searchMaxAmount = ''; if($refs.searchDateFromInput && $refs.searchDateFromInput._flatpickr) $refs.searchDateFromInput._flatpickr.clear(); if($refs.searchDateToInput && $refs.searchDateToInput._flatpickr) $refs.searchDateToInput._flatpickr.clear(); fetchLines(1); fetchTransactions(1); fetchAllLines(1)"
                                class="text-gray-500 hover:text-gray-700 text-sm font-medium whitespace-nowrap">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 text-sm font-medium border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button @click="tab = 'reconcile'"
                                :class="{ 'border-blue-500 text-blue-600': tab === 'reconcile', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'reconcile' }"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                                Reconcile
                                <span x-show="linesPagination.total > 0" x-text="'(' + linesPagination.total + ')'"
                                    class="ml-2 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-[10px]"></span>
                            </button>
                            <button @click="tab = 'statements'"
                                :class="{ 'border-blue-500 text-blue-600': tab === 'statements', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'statements' }"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Bank Statements
                            </button>
                            <button @click="tab = 'transactions'"
                                :class="{ 'border-blue-500 text-blue-600': tab === 'transactions', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'transactions' }"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Account Transactions
                            </button>
                        </nav>
                    </div>

                    <!-- RECONCILE TAB CONTENT -->
                    <div x-show="tab === 'reconcile'" class="bg-white rounded shadow">
                        <div class="grid grid-cols-12 bg-gray-100 border-b p-4 font-semibold text-gray-700">
                            <div class="col-span-1">Date</div>
                            <div class="col-span-3">Details</div>
                            <div class="col-span-2 text-right">Amount</div>
                            <div class="col-span-6 pl-4">Match / Action</div>
                        </div>

                        <div x-show="linesLoading" class="p-12 text-center">
                            <svg class="animate-spin h-10 w-10 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <p class="mt-4 text-gray-500 font-medium">Fetching statement lines and finding
                                matches...</p>
                        </div>

                        <div x-show="!linesLoading">
                            <template x-for="line in statementLines" :key="line.id + '-' + linesLoadedCounter">
                                <div class="grid grid-cols-12 border-b border-gray-300 bg-white items-stretch"
                                    x-data="{
                                        {{-- mode: line.matches && line.matches.length > 0 ? 'match' : 'create', --}}
                                        mode: 'match',
                                            showFind: false, search: '', searchResults: [], isSearching: false,
                                            selectedMatchId: (line.matches && line.matches.length > 0) ? line.matches[0].id : null,
                                    }">

                                    <!-- LEFT COLUMN: Statement Line -->
                                    <div class="col-span-6 p-4 flex flex-col justify-center border-r border-gray-200">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <span class="text-xs text-gray-500 font-bold block mb-1"
                                                    x-text="formatDate(line.date)"></span>
                                                <span class="font-bold text-gray-800 block text-lg"
                                                    x-text="line.payee || 'No Payee'"></span>
                                                <span class="text-sm text-gray-600 block"
                                                    x-text="line.description"></span>
                                                <span class="text-xs text-gray-400 block mt-1" x-show="line.reference"
                                                    x-text="'Ref: ' + line.reference"></span>

                                                <template x-if="line.status === 'Partially Reconciled'">
                                                    <div class="mt-2">
                                                        <span
                                                            class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Partially
                                                            Reconciled</span>
                                                        <div class="mt-1 text-[10px] text-gray-500">
                                                            Matched: <span class="font-bold"
                                                                x-text="formatAmount(line.matched_amount)"></span>
                                                            Remaining: <span class="font-bold text-red-600"
                                                                x-text="formatAmount(Math.abs(line.remaining_amount !== null ? line.remaining_amount : line.amount))"></span>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Action Buttons for Line -->
                                                <div class="flex gap-4 mt-3 pt-2 border-t border-gray-100">
                                                    <button @click="editLine(line)"
                                                        class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center transition-colors">
                                                        <svg class="w-3.5 h-3.5 mr-1" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                            </path>
                                                        </svg>
                                                        Edit
                                                    </button>
                                                    <button @click="deleteLine(line.id)"
                                                        class="text-xs font-bold text-red-600 hover:text-red-800 flex items-center transition-colors">
                                                        <svg class="w-3.5 h-3.5 mr-1" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="text-right ml-4">
                                                <span class="block font-bold text-lg"
                                                    x-text="formatAmount(line.amount)"></span>
                                                <span class="text-xs text-gray-400 uppercase tracking-wider block mt-1"
                                                    x-text="line.amount > 0 ? 'Received' : 'Spent'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT COLUMN: User Action (Reconcile) -->
                                    <div class="col-span-6 bg-gray-50 p-2 relative">
                                        <!-- TABS HEADER -->
                                        <div class="flex space-x-1 border-b border-gray-300 px-2">
                                            <button @click="mode = 'match'; showFind = false"
                                                :class="{ 'bg-white border-t border-l border-r border-gray-300 border-b-white text-blue-600': mode === 'match', 'text-gray-500 hover:text-gray-700': mode !== 'match' }"
                                                class="px-4 py-2 text-sm font-medium rounded-t-md -mb-px relative z-10 transition-colors">
                                                Match
                                            </button>
                                            <button @click="mode = 'create'; showFind = false"
                                                :class="{ 'bg-white border-t border-l border-r border-gray-300 border-b-white text-blue-600': mode === 'create', 'text-gray-500 hover:text-gray-700': mode !== 'create' }"
                                                class="px-4 py-2 text-sm font-medium rounded-t-md -mb-px relative z-10 transition-colors">
                                                Create
                                            </button>
                                            <button @click="mode = 'transfer'; showFind = false"
                                                :class="{ 'bg-white border-t border-l border-r border-gray-300 border-b-white text-blue-600': mode === 'transfer', 'text-gray-500 hover:text-gray-700': mode !== 'transfer' }"
                                                class="px-4 py-2 text-sm font-medium rounded-t-md -mb-px relative z-10 transition-colors">Transfer</button>
                                            <button @click="mode = 'discuss'; showFind = false"
                                                :class="{ 'bg-white border-t border-l border-r border-gray-300 border-b-white text-blue-600': mode === 'discuss', 'text-gray-500 hover:text-gray-700': mode !== 'discuss' }"
                                                class="px-4 py-2 text-sm font-medium rounded-t-md -mb-px relative z-10 transition-colors">Discuss</button>
                                        </div>

                                        <!-- TAB CONTENT AREA -->
                                        <div
                                            class="bg-white border border-gray-300 border-t-0 p-4 rounded-b-md shadow-sm min-h-[140px] flex flex-col justify-between">

                                            <!-- MATCH TAB -->
                                            <div x-show="mode === 'match' && !showFind" class="flex-1 animate-fade-in">
                                                <!-- Current Matches (Already Reconciled) -->
                                                <template x-if="line.transactions && line.transactions.length > 0">
                                                    <div class="mb-4">
                                                        <p
                                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                                                            Currently Matched (Already Reconciled)</p>
                                                        <div class="space-y-2">
                                                            <template x-for="txn in line.transactions"
                                                                :key="'txn-' + txn.id">
                                                                <div
                                                                    class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex justify-between items-center shadow-sm">
                                                                    <div>
                                                                        <div class="font-bold text-sm text-blue-900"
                                                                            x-text="txn.description"></div>
                                                                        <div class="text-[10px] text-blue-500 font-medium uppercase"
                                                                            x-text="formatDate(txn.date)"></div>
                                                                    </div>
                                                                    <div class="text-right">
                                                                        <div class="font-black text-blue-900"
                                                                            x-text="formatAmount(parseFloat(txn.debit) > 0 ? txn.debit : txn.credit)">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Match Suggestions -->
                                                <div x-data="{
                                                    selectedType: (line.invoice_matches?.length > 0 ? 'invoice' : (line.bill_matches?.length > 0 ? 'bill' : (line.payroll_matches?.length > 0 ? 'payroll' : 'transaction'))),
                                                    selectedId: (line.invoice_matches?.length > 0 ? line.invoice_matches[0].id : (line.bill_matches?.length > 0 ? line.bill_matches[0].id : (line.payroll_matches?.length > 0 ? line.payroll_matches[0].id : (line.matches?.length > 0 ? line.matches[0].id : null))))
                                                }">
                                                    <template
                                                        x-if="(line.matches?.length > 0) || (line.invoice_matches?.length > 0) || (line.bill_matches?.length > 0) || (line.payroll_matches?.length > 0)">
                                                        <div>
                                                            <div class="flex items-center justify-between mb-2">
                                                                <p
                                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                                    Suggested Matches</p>
                                                                <span
                                                                    class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">Smart
                                                                    Match Active</span>
                                                            </div>

                                                            <div
                                                                class="space-y-2 mb-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                                                <!-- Invoice Matches -->
                                                                <template x-for="match in line.invoice_matches"
                                                                    :key="'inv-' + match.id">
                                                                    <label
                                                                        class="block rounded-lg border-2 p-3 cursor-pointer transition-all duration-200 hover:shadow-md"
                                                                        :class="{
                                                                            'bg-green-50 border-green-500 ring-2 ring-green-100': selectedId ===
                                                                                match.id &&
                                                                                selectedType === 'invoice',
                                                                            'bg-white border-gray-100 hover:border-blue-200': selectedId !==
                                                                                match.id ||
                                                                                selectedType !== 'invoice',
                                                                        }">
                                                                        <div class="flex items-center gap-3">
                                                                            <input type="radio"
                                                                                :name="'match-' + line.id"
                                                                                :value="match.id"
                                                                                @change="selectedId = match.id; selectedType = 'invoice'"
                                                                                :checked="selectedId === match.id &&
                                                                                    selectedType === 'invoice'"
                                                                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                                                                            <div class="flex-1 min-w-0">
                                                                                <div
                                                                                    class="flex items-center justify-between mb-1">
                                                                                    <a :href="'{{ url('account/sale/show') }}/' + match.id" target="_blank" @click.stop
                                                                                        class="font-bold text-sm text-[#0078C8] hover:underline truncate"
                                                                                        x-text="'Invoice: ' + match.unique_no"></a>
                                                                                    <span
                                                                                        class="text-[10px] font-black uppercase px-2 py-0.5 rounded shadow-sm"
                                                                                        :class="{
                                                                                            'bg-green-600 text-white': match
                                                                                                .confidence === 'high',
                                                                                            'bg-blue-500 text-white': match
                                                                                                .confidence === 'medium',
                                                                                            'bg-gray-400 text-white': match
                                                                                                .confidence === 'low',
                                                                                        }"
                                                                                        x-text="match.confidence"></span>
                                                                                </div>
                                                                                <div
                                                                                    class="flex items-center justify-between">
                                                                                    <div class="text-xs text-gray-500 font-medium"
                                                                                        x-text="match.customer?.name">
                                                                                    </div>
                                                                                    <div class="text-sm font-black text-gray-900"
                                                                                        x-text="formatAmount(match.remaining_amount)">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="text-[10px] text-gray-400 font-bold uppercase mt-1"
                                                                                    x-text="formatDate(match.date)"></div>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                </template>

                                                                <!-- Bill Matches -->
                                                                <template x-for="match in line.bill_matches"
                                                                    :key="'bill-' + match.id">
                                                                    <label
                                                                        class="block rounded-lg border-2 p-3 cursor-pointer transition-all duration-200 hover:shadow-md"
                                                                        :class="{
                                                                            'bg-purple-50 border-purple-500 ring-2 ring-purple-100': selectedId ===
                                                                                match.id &&
                                                                                selectedType === 'bill',
                                                                            'bg-white border-gray-100 hover:border-blue-200': selectedId !==
                                                                                match.id ||
                                                                                selectedType !== 'bill',
                                                                        }">
                                                                        <div class="flex items-center gap-3">
                                                                            <input type="radio"
                                                                                :name="'match-' + line.id"
                                                                                :value="match.id"
                                                                                @change="selectedId = match.id; selectedType = 'bill'"
                                                                                :checked="selectedId === match.id &&
                                                                                    selectedType === 'bill'"
                                                                                class="w-4 h-4 text-purple-600 focus:ring-purple-500">
                                                                            <div class="flex-1 min-w-0">
                                                                                <div
                                                                                    class="flex items-center justify-between mb-1">
                                                                                    <a :href="'{{ url('account/bill-invoice') }}/' + match.id" target="_blank" @click.stop
                                                                                        class="font-bold text-sm text-[#0078C8] hover:underline truncate"
                                                                                        x-text="'Bill: ' + match.unique_no"></a>
                                                                                    <span
                                                                                        class="text-[10px] font-black uppercase px-2 py-0.5 rounded shadow-sm"
                                                                                        :class="{
                                                                                            'bg-green-600 text-white': match
                                                                                                .confidence === 'high',
                                                                                            'bg-blue-500 text-white': match
                                                                                                .confidence === 'medium',
                                                                                            'bg-gray-400 text-white': match
                                                                                                .confidence === 'low',
                                                                                        }"
                                                                                        x-text="match.confidence"></span>
                                                                                </div>
                                                                                <div
                                                                                    class="flex items-center justify-between">
                                                                                    <div class="text-xs text-gray-500 font-medium"
                                                                                        x-text="match.vendor?.name"></div>
                                                                                    <div class="text-sm font-black text-gray-900"
                                                                                        x-text="formatAmount(match.remaining_amount)">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="text-[10px] text-gray-400 font-bold uppercase mt-1"
                                                                                    x-text="formatDate(match.date)"></div>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                </template>

                                                                <!-- Payroll Matches -->
                                                                <template x-for="match in line.payroll_matches"
                                                                    :key="'payroll-' + match.id">
                                                                    <label
                                                                        class="block rounded-lg border-2 p-3 cursor-pointer transition-all duration-200 hover:shadow-md"
                                                                        :class="{
                                                                            'bg-amber-50 border-amber-500 ring-2 ring-amber-100': selectedId ===
                                                                                match.id &&
                                                                                selectedType === 'payroll',
                                                                            'bg-white border-gray-100 hover:border-blue-200': selectedId !==
                                                                                match.id ||
                                                                                selectedType !== 'payroll',
                                                                        }">
                                                                        <div class="flex items-center gap-3">
                                                                            <input type="radio"
                                                                                :name="'match-' + line.id"
                                                                                :value="match.id"
                                                                                @change="selectedId = match.id; selectedType = 'payroll'"
                                                                                :checked="selectedId === match.id &&
                                                                                    selectedType === 'payroll'"
                                                                                class="w-4 h-4 text-amber-600 focus:ring-amber-500">
                                                                            <div class="flex-1 min-w-0">
                                                                                <div
                                                                                    class="flex items-center justify-between mb-1">
                                                                                    <a :href="'{{ url('payroll/pay-runs') }}/' + match.id" target="_blank" @click.stop
                                                                                        class="font-bold text-sm text-[#0078C8] hover:underline truncate"
                                                                                        x-text="'Payroll: ' + (match.unique_no || ('#' + match.id))"></a>
                                                                                    <span
                                                                                        class="text-[10px] font-black uppercase px-2 py-0.5 rounded shadow-sm"
                                                                                        :class="{
                                                                                            'bg-green-600 text-white': match
                                                                                                .confidence === 'high',
                                                                                            'bg-blue-500 text-white': match
                                                                                                .confidence === 'medium',
                                                                                            'bg-gray-400 text-white': match
                                                                                                .confidence === 'low',
                                                                                        }"
                                                                                        x-text="match.confidence"></span>
                                                                                </div>
                                                                                <div
                                                                                    class="flex items-center justify-between">
                                                                                    <div class="text-xs text-gray-500 font-medium"
                                                                                        x-text="(match.period_start ? formatDate(match.period_start) + ' - ' : '') + (match.period_end ? formatDate(match.period_end) : '')">
                                                                                    </div>
                                                                                    <div class="text-sm font-black text-gray-900"
                                                                                        x-text="formatAmount(match.net_pay_total || match.remaining_amount)">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="text-[10px] text-gray-400 font-bold uppercase mt-1"
                                                                                    x-text="'Payment: ' + formatDate(match.payment_date)"></div>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                </template>

                                                                <!-- Transaction Matches -->
                                                                <template x-for="match in line.matches"
                                                                    :key="'txn-' + match.id">
                                                                    <label
                                                                        class="block rounded-lg border-2 p-3 cursor-pointer transition-all duration-200 hover:shadow-md"
                                                                        :class="{
                                                                            'bg-blue-50 border-blue-500 ring-2 ring-blue-100': selectedId ===
                                                                                match.id &&
                                                                                selectedType === 'transaction',
                                                                            'bg-white border-gray-100 hover:border-blue-200': selectedId !==
                                                                                match.id ||
                                                                                selectedType !== 'transaction',
                                                                        }">
                                                                        <div class="flex items-center gap-3">
                                                                            <input type="radio"
                                                                                :name="'match-' + line.id"
                                                                                :value="match.id"
                                                                                @change="selectedId = match.id; selectedType = 'transaction'"
                                                                                :checked="selectedId === match.id &&
                                                                                    selectedType === 'transaction'"
                                                                                class="w-4 h-4 text-[#0078C8] focus:ring-[#0078C8]">
                                                                            <div class="flex-1 min-w-0">
                                                                                <div
                                                                                    class="flex items-center justify-between mb-1">
                                                                                    <span
                                                                                        class="font-bold text-sm text-gray-900 truncate"
                                                                                        x-text="match.description"></span>
                                                                                    <span
                                                                                        class="text-[10px] font-black uppercase px-2 py-0.5 rounded shadow-sm"
                                                                                        :class="{
                                                                                            'bg-green-600 text-white': match
                                                                                                .confidence === 'high',
                                                                                            'bg-blue-500 text-white': match
                                                                                                .confidence === 'medium',
                                                                                            'bg-gray-400 text-white': match
                                                                                                .confidence === 'low',
                                                                                        }"
                                                                                        x-text="match.confidence"></span>
                                                                                </div>
                                                                                <div
                                                                                    class="flex items-center justify-between">
                                                                                    <div class="text-xs text-gray-400 font-bold uppercase"
                                                                                        x-text="formatDate(match.date)">
                                                                                    </div>
                                                                                    <div class="text-sm font-black text-gray-900"
                                                                                        x-text="formatAmount(parseFloat(match.debit) > 0 ? match.debit : match.credit)">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                </template>
                                                            </div>

                                                            <div
                                                                class="flex justify-between items-center bg-gray-50 -mx-5 -mb-5 p-5 border-t border-gray-100">
                                                                <button @click="showFind = true"
                                                                    class="text-[#0078C8] hover:text-[#005a96] text-sm font-bold flex items-center transition">
                                                                    <svg class="w-4 h-4 mr-1" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
                                                                        </path>
                                                                    </svg>
                                                                    Find & Match
                                                                </button>

                                                                <form
                                                                    :action="selectedType === 'transaction' ?
                                                                        '{{ route('account.bank-reconciliation.match') }}' :
                                                                        '{{ route('account.bank-reconciliation.match-invoice') }}'"
                                                                    method="POST" @submit.prevent="window._submitReconcileForm($event)">
                                                                    @csrf
                                                                    <input type="hidden" name="line_id"
                                                                        :value="line.id">
                                                                    <template x-if="selectedType === 'transaction'">
                                                                        <input type="hidden" name="transaction_id"
                                                                            :value="selectedId">
                                                                    </template>
                                                                    <template x-if="selectedType !== 'transaction'">
                                                                        <div>
                                                                            <input type="hidden" name="invoice_id"
                                                                                :value="selectedId">
                                                                            <input type="hidden" name="type"
                                                                                :value="selectedType === 'invoice' ? 'Sale' :
                                                                                    (selectedType === 'bill' ? 'Bill' : 'Payroll')">
                                                                        </div>
                                                                    </template>

                                                                    <button type="submit" :disabled="!selectedId"
                                                                        class="btn-ok bg-[#0078C8] hover:bg-[#005a96] text-white font-black py-2 px-6 rounded shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed text-base tex-sm tracking-widest uppercase">OK</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <template
                                                        x-if="!line.matches?.length && !line.invoice_matches?.length && !line.bill_matches?.length && !line.payroll_matches?.length">
                                                        <div class="text-center py-12">
                                                            <div
                                                                class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                                                <svg class="w-8 h-8 text-gray-400" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                    </path>
                                                                </svg>
                                                            </div>
                                                            <p class="text-gray-500 font-medium mb-4">We couldn't find an
                                                                automatic match.</p>
                                                            <button @click="showFind = true"
                                                                class="bg-white border-2 border-[#0078C8] text-[#0078C8] hover:bg-blue-50 font-bold py-2 px-6 rounded-md transition shadow-sm">
                                                                Find & Match manually
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- FIND & MATCH INTERFACE -->
                                            <div x-show="showFind" class="flex-1 overflow-hidden" x-data="{
                                                search: '',
                                                searchDateFrom: '',
                                                searchDateTo: '',
                                                searchMinAmount: '',
                                                searchMaxAmount: '',
                                                selectedItems: [],
                                                totalMatched: 0,
                                                adjustment: { active: false, account_id: '', description: '', amount: 0 },
                                                get currentType() {
                                                    if (this.selectedItems.length === 0) return null;
                                                    return this.selectedItems[0].type;
                                                },
                                                get difference() {
                                                    let diff = Math.abs(line.remaining_amount !== null ? line.remaining_amount : line.amount) - this.totalMatched;
                                                    if (this.adjustment.active) {
                                                        diff = Math.abs(diff) - parseFloat(this.adjustment.amount || 0);
                                                    }
                                                    return diff;
                                                },
                                                fetchSearchResults() {
                                                    this.isSearching = true;
                                                    let url = '{{ route('account.bank-reconciliation.search-transactions', $bankAccount->id) }}?search=' + encodeURIComponent(this.search);
                                                    if(this.searchDateFrom) url += '&date_from=' + encodeURIComponent(this.searchDateFrom);
                                                    if(this.searchDateTo) url += '&date_to=' + encodeURIComponent(this.searchDateTo);
                                                    if(this.searchMinAmount) url += '&min_amount=' + encodeURIComponent(this.searchMinAmount);
                                                    if(this.searchMaxAmount) url += '&max_amount=' + encodeURIComponent(this.searchMaxAmount);
                                                    fetch(url)
                                                        .then(res => res.json())
                                                        .then(data => { this.searchResults = data.all; this.isSearching = false; });
                                                }
                                            }"
                                                x-init="$watch('selectedItems', () => {
                                                     totalMatched = selectedItems.reduce((sum, item) => sum + parseFloat(item.allocated_amount || item.amount || 0), 0);
                                                     if (adjustment.active) {
                                                         adjustment.amount = parseFloat(Math.abs(Math.abs(line.remaining_amount !== null ? line.remaining_amount : line.amount) - totalMatched).toFixed(2));
                                                     }
                                                 }, { deep: true });
                                                 setTimeout(() => {
                                                     if (typeof flatpickr !== 'undefined') {
                                                         const syncMatchDateFilters = () => {
                                                             searchDateFrom = $refs.matchSearchDateFromInput && $refs.matchSearchDateFromInput._flatpickr ? $refs.matchSearchDateFromInput._flatpickr.input.value : searchDateFrom;
                                                             searchDateTo = $refs.matchSearchDateToInput && $refs.matchSearchDateToInput._flatpickr ? $refs.matchSearchDateToInput._flatpickr.input.value : searchDateTo;
                                                         };
                                                         const matchDateConfig = {
                                                             dateFormat: 'Y-m-d',
                                                             altInput: true,
                                                             altInputClass: 'border border-gray-300 rounded-md px-2 py-1.5 text-xs outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
                                                             altFormat: 'd/m/Y',
                                                             allowInput: true,
                                                             onChange: (selectedDates, dateStr) => {
                                                                 syncMatchDateFilters();
                                                                 fetchSearchResults();
                                                             }
                                                         };
                                                         if ($refs.matchSearchDateFromInput) flatpickr($refs.matchSearchDateFromInput, matchDateConfig);
                                                         if ($refs.matchSearchDateToInput) flatpickr($refs.matchSearchDateToInput, matchDateConfig);
                                                     }
                                                 }, 100)">
                                                <div class="flex flex-col gap-2 mb-2 px-1">
                                                    <div class="flex justify-between items-center">
                                                        <div class="relative flex-1 mr-2">
                                                            <input type="text" x-model="search"
                                                                @input.debounce.500ms="fetchSearchResults()"
                                                                placeholder="Search by name, ref, or amount..."
                                                                class="border rounded-md pl-8 pr-10 py-1.5 text-sm w-full outline-none">
                                                            <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"
                                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                            </svg>
                                                            <div x-show="isSearching" class="absolute right-3 top-2.5">
                                                                <svg class="animate-spin h-4 w-4 text-blue-500"
                                                                    xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                    viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12"
                                                                        r="10" stroke="currentColor" stroke-width="4">
                                                                    </circle>
                                                                    <path class="opacity-75" fill="currentColor"
                                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                    </path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <button @click="showFind = false; search = ''; searchDateFrom = ''; searchDateTo = ''; searchMinAmount = ''; searchMaxAmount = ''; selectedItems = []; if ($refs.matchSearchDateFromInput && $refs.matchSearchDateFromInput._flatpickr) $refs.matchSearchDateFromInput._flatpickr.clear(); if ($refs.matchSearchDateToInput && $refs.matchSearchDateToInput._flatpickr) $refs.matchSearchDateToInput._flatpickr.clear()"
                                                            class="text-gray-500 hover:text-gray-700 text-sm font-medium">Cancel</button>
                                                    </div>
                                                    
                                                    <div class="flex gap-2 items-center">
                                                        <input type="text" x-model="searchDateFrom" x-ref="matchSearchDateFromInput" placeholder="Date From" class="border border-gray-300 rounded-md px-2 py-1.5 text-xs outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" title="Date From">
                                                        <input type="text" x-model="searchDateTo" x-ref="matchSearchDateToInput" placeholder="Date To" class="border border-gray-300 rounded-md px-2 py-1.5 text-xs outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" title="Date To">
                                                        <input type="number" step="0.01" x-model="searchMinAmount" @input.debounce.500ms="fetchSearchResults()" placeholder="Min Amount" class="border border-gray-300 rounded-md px-2 py-1.5 text-xs outline-none w-24 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                        <span class="text-xs text-gray-400">-</span>
                                                        <input type="number" step="0.01" x-model="searchMaxAmount" @input.debounce.500ms="fetchSearchResults()" placeholder="Max Amount" class="border border-gray-300 rounded-md px-2 py-1.5 text-xs outline-none w-24 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                    </div>
                                                </div>

                                                <div
                                                    class="overflow-y-auto max-h-[180px] border border-gray-200 rounded-md text-xs bg-white mb-2">
                                                    <table class="w-full text-left">
                                                        <thead class="bg-gray-50 sticky top-0 border-b">
                                                            <tr>
                                                                <th class="p-2 w-8"><input type="checkbox" disabled
                                                                        class="rounded"></th>
                                                                <th class="p-2 text-gray-500">Date</th>
                                                                <th class="p-2 text-gray-500">Source</th>
                                                                <th class="p-2 text-gray-500">Description</th>
                                                                <th class="p-2 text-right text-gray-500">Amount</th>
                                                                <th class="p-2 text-right text-gray-500 w-32">Allocation</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                            <template x-for="item in searchResults"
                                                                :key="item.type + '-' + item.id">
                                                                <tr class="hover:bg-blue-50 cursor-pointer"
                                                                    @click="
                                                                        const exists = selectedItems.find(i => i.id === item.id && i.type === item.type);
                                                                        if(exists) { 
                                                                            selectedItems = selectedItems.filter(i => !(i.id === item.id && i.type === item.type)) 
                                                                        } else { 
                                                                            if (totalMatched >= Math.abs(line.remaining_amount !== null ? line.remaining_amount : line.amount) - 0.01 && !adjustment.active) {
                                                                                alert('The statement line is fully matched. To select this item, please unselect another or split an existing match.');
                                                                                return;
                                                                            }
                                                                            // Prevent mixing types for now for simplicity
                                                                            if (selectedItems.length > 0 && selectedItems[0].type !== item.type) {
                                                                                if(!confirm('You cannot mix different types in one match. Clear current selection?')) return;
                                                                                item.allocated_amount = parseFloat(item.amount);
                                                                                selectedItems = [item];
                                                                            } else {
                                                                                item.allocated_amount = parseFloat(item.amount);
                                                                                selectedItems.push(item);
                                                                            }
                                                                        }
                                                                    ">
                                                                    <td class="p-2">
                                                                        <input type="checkbox"
                                                                            :checked="selectedItems.find(i => i.id === item.id &&
                                                                                i.type === item.type)"
                                                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 pointer-events-none">
                                                                    </td>
                                                                    <td class="p-2" x-text="formatDate(item.date)">
                                                                    </td>
                                                                    <td class="p-2">
                                                                            <span
                                                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase"
                                                                                :class="{
                                                                                'bg-blue-100 text-blue-800': item.type === 'transaction',
                                                                                'bg-green-100 text-green-800': item.type === 'invoice',
                                                                                'bg-purple-100 text-purple-800': item.type === 'bill',
                                                                                'bg-amber-100 text-amber-800': item.type === 'payroll'
                                                                            }"
                                                                            x-text="item.source || item.type"></span>
                                                                    </td>
                                                                    <td class="p-2">
                                                                        <div class="font-medium">
                                                                            <template x-if="item.type === 'invoice'">
                                                                                <a :href="'{{ url('account/sale/show') }}/' + item.id" target="_blank" @click.stop class="text-[#0078C8] hover:underline" x-text="item.description"></a>
                                                                            </template>
                                                                            <template x-if="item.type === 'bill'">
                                                                                <a :href="'{{ url('account/bill-invoice') }}/' + item.id" target="_blank" @click.stop class="text-[#0078C8] hover:underline" x-text="item.description"></a>
                                                                            </template>
                                                                            <template x-if="item.type === 'payroll'">
                                                                                <a :href="'{{ url('payroll/pay-runs') }}/' + item.id" target="_blank" @click.stop class="text-[#0078C8] hover:underline" x-text="item.description"></a>
                                                                            </template>
                                                                            <template x-if="!['invoice', 'bill', 'payroll'].includes(item.type)">
                                                                                <span x-text="item.description"></span>
                                                                            </template>
                                                                        </div>
                                                                        <div class="text-[10px] text-gray-400"
                                                                            x-text="item.reference ? 'Ref: ' + item.reference : ''">
                                                                        </div>
                                                                    </td>
                                                                    <td class="p-2 text-right font-bold"
                                                                        x-text="formatAmount(item.amount)"></td>
                                                                    <td class="p-2 text-right" @click.stop>
                                                                        <template x-if="selectedItems.find(i => i.id === item.id && i.type === item.type) && ['invoice', 'bill'].includes(item.type)">
                                                                            <div x-data="{ 
                                                                                isSplitting: false, 
                                                                                tempAmount: 0 
                                                                            }">
                                                                                <button @click.prevent="
                                                                                    isSplitting = true; 
                                                                                    let currentItem = selectedItems.find(i => i.id === item.id && i.type === item.type);
                                                                                    let currentAlloc = currentItem ? (currentItem.allocated_amount || item.amount) : item.amount;
                                                                                    let otherMatches = totalMatched - parseFloat(currentAlloc);
                                                                                    let suggestion = Math.abs(line.remaining_amount !== null ? line.remaining_amount : line.amount) - otherMatches;
                                                                                    if(suggestion < 0) suggestion = 0;
                                                                                    if(suggestion > item.amount) suggestion = item.amount;
                                                                                    tempAmount = parseFloat(suggestion.toFixed(2));
                                                                                " type="button" class="text-xs text-blue-600 hover:underline font-medium">Split</button>
                                                                                
                                                                                <!-- Xero-Style Split Modal -->
                                                                                <div x-show="isSplitting" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 bg-opacity-50" style="display: none;" x-transition>
                                                                                    <div class="bg-white rounded-lg shadow-2xl w-96 overflow-hidden transform transition-all" @click.outside="isSplitting = false">
                                                                                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                                                                                            <h3 class="text-base font-bold text-gray-900">Split Transaction</h3>
                                                                                            <button @click="isSplitting = false" type="button" class="text-gray-400 hover:text-gray-500">
                                                                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                                                            </button>
                                                                                        </div>
                                                                                        <div class="p-4 text-left">
                                                                                            <div class="flex justify-between text-sm mb-2">
                                                                                                <span class="text-gray-500">Total</span>
                                                                                                <span class="font-bold text-gray-900" x-text="formatAmount(item.amount)"></span>
                                                                                            </div>
                                                                                            <div class="flex justify-between text-sm mb-4">
                                                                                                <span class="text-gray-500">Remaining</span>
                                                                                                <span class="font-bold text-gray-900" x-text="formatAmount(item.amount - tempAmount)"></span>
                                                                                            </div>
                                                                                            <div class="mb-4">
                                                                                                <label class="block text-sm font-bold text-gray-700 mb-1">Part payment</label>
                                                                                                <input type="number" step="0.01" 
                                                                                                    x-model.number="tempAmount" 
                                                                                                    class="w-full text-right border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm p-2 border">
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex justify-end gap-2">
                                                                                            <button type="button" @click="isSplitting = false; selectedItems.find(i => i.id === item.id && i.type === item.type).allocated_amount = parseFloat(item.amount); totalMatched = selectedItems.reduce((sum, i) => sum + parseFloat(i.allocated_amount || i.amount || 0), 0)" class="px-4 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none transition">Cancel</button>
                                                                                            <button type="button" @click="selectedItems.find(i => i.id === item.id && i.type === item.type).allocated_amount = parseFloat(tempAmount); totalMatched = selectedItems.reduce((sum, i) => sum + parseFloat(i.allocated_amount || i.amount || 0), 0); isSplitting = false" class="px-4 py-2 text-sm font-bold text-white bg-[#0078C8] border border-transparent rounded-md shadow-sm hover:bg-[#005a96] focus:outline-none transition">Split</button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </template>
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- Adjustment Form Drawer -->
                                                <div x-show="adjustment.active" x-transition class="bg-gray-50 border border-gray-200 rounded-md p-3 mb-2 text-sm shadow-sm">
                                                    <div class="flex justify-between items-center mb-2">
                                                        <h4 class="font-bold text-gray-800">Reconciliation Adjustment</h4>
                                                        <button @click="adjustment.active = false; adjustment.amount = 0" type="button" class="text-gray-400 hover:text-gray-600">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        </button>
                                                    </div>
                                                    <div class="grid grid-cols-12 gap-2 items-end">
                                                        <div class="col-span-5">
                                                            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Account</label>
                                                            <select x-model="adjustment.account_id" class="select2 w-full border-gray-300 rounded text-xs py-1.5 focus:border-[#0078C8] focus:ring-[#0078C8]">
                                                                <option value="">Select account...</option>
                                                                @foreach (\App\Models\ChartOfAccount::forCompany(loggedCompanyId()['company_id'] ?? null)->get() as $acc)
                                                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-span-4">
                                                            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Description</label>
                                                            <input type="text" x-model="adjustment.description" placeholder="e.g. Bank Fee" class="w-full border-gray-300 rounded text-xs py-1.5 focus:border-[#0078C8] focus:ring-[#0078C8]">
                                                        </div>
                                                        <div class="col-span-3">
                                                            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Amount</label>
                                                            <input type="number" step="0.01" x-model.number="adjustment.amount" class="w-full border-gray-300 rounded text-xs py-1.5 text-right focus:border-[#0078C8] focus:ring-[#0078C8]">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex items-center justify-between bg-blue-50 p-3 rounded border border-blue-100">
                                                    <div class="text-xs">
                                                        <div class="text-gray-600">Total Matched: <span
                                                                class="font-bold text-gray-900"
                                                                x-text="formatAmount(totalMatched)"></span></div>
                                                        <div class="flex items-center gap-3">
                                                            <div class="text-gray-600">Difference: <span class="font-bold"
                                                                    :class="Math.abs(difference) < 0.01 ? 'text-green-600' : 'text-red-600'"
                                                                    x-text="formatAmount(Math.abs(difference))"></span>
                                                            </div>
                                                            <template x-if="Math.abs(Math.abs(line.amount) - totalMatched) > 0.01 && !adjustment.active">
                                                                <button type="button" @click="adjustment.active = true; adjustment.amount = parseFloat(Math.abs(Math.abs(line.amount) - totalMatched).toFixed(2));" class="text-[#0078C8] hover:text-[#005a96] hover:underline font-bold">+ Adjustments</button>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <form
                                                        :action="currentType === 'transaction' ?
                                                            '{{ route('account.bank-reconciliation.match') }}' :
                                                            '{{ route('account.bank-reconciliation.match-invoice') }}'"
                                                        method="POST" @submit.prevent="window._submitReconcileForm($event)">
                                                        @csrf
                                                        <input type="hidden" name="line_id" :value="line.id">

                                                        <template x-if="adjustment.active && adjustment.amount > 0">
                                                            <div>
                                                                <input type="hidden" name="adjustment_account_id" :value="adjustment.account_id">
                                                                <input type="hidden" name="adjustment_description" :value="adjustment.description">
                                                                <input type="hidden" name="adjustment_amount" :value="adjustment.amount">
                                                            </div>
                                                        </template>

                                                        <template x-if="currentType === 'transaction'">
                                                            <div>
                                                                <template x-for="item in selectedItems"
                                                                    :key="'match-' + item.id">
                                                                    <input type="hidden" name="transaction_ids[]"
                                                                        :value="item.id">
                                                                </template>
                                                            </div>
                                                        </template>

                                                        <template
                                                            x-if="['invoice', 'bill'].includes(currentType) && selectedItems.length > 0">
                                                            <div>
                                                                <input type="hidden" name="allocations" :value="JSON.stringify(selectedItems.map(i => ({ id: i.id, amount: parseFloat(i.allocated_amount || i.amount || 0) })))">
                                                                <input type="hidden" name="invoice_id"
                                                                    :value="selectedItems[0].id">
                                                                <input type="hidden" name="type"
                                                                    :value="currentType === 'invoice' ? 'Sale' : 'Bill'">
                                                            </div>
                                                        </template>

                                                        <template
                                                            x-if="currentType === 'payroll' && selectedItems.length > 0">
                                                            <div>
                                                                <input type="hidden" name="invoice_id"
                                                                    :value="selectedItems[0].id">
                                                                <input type="hidden" name="type" value="Payroll">
                                                            </div>
                                                        </template>

                                                        <button type="submit" :disabled="selectedItems.length === 0"
                                                            class="bg-[#0078C8] text-white font-bold py-2 px-6 rounded shadow hover:bg-[#005a96] transition disabled:opacity-50 disabled:cursor-not-allowed">Reconcile</button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- CREATE TAB -->
                                            <div x-show="mode === 'create'" class="flex-1"
                                                x-data="{ contraAccountId: line.suggested_account_id ? String(line.suggested_account_id) : '' }"
                                                x-init="$nextTick(() => {
                                                    if (line.suggested_account_id) {
                                                        contraAccountId = String(line.suggested_account_id);
                                                    }
                                                })">
                                                <form
                                                    action="{{ route('account.bank-reconciliation.create-transaction') }}"
                                                    method="POST" @submit.prevent="window._submitReconcileForm($event)">
                                                    @csrf
                                                    <input type="hidden" name="line_id" :value="line.id">
                                                    <div class="space-y-2">
                                                        <div>
                                                            <label
                                                                class="block text-xs font-semibold text-gray-600 mb-0.5">Who</label>
                                                            <input type="text" name="payee" :value="line.payee"
                                                                class="w-full border border-gray-300 rounded px-2 py-1 text-sm outline-none">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="block text-xs font-semibold text-gray-600 mb-0.5">What</label>
                                                            <div class="relative">
                                                                <select name="contra_account_id"
                                                                    x-model="contraAccountId"
                                                                    x-init="
                                                                        setTimeout(() => {
                                                                            $( $el ).select2({
                                                                                width: '100%'
                                                                            }).on('change', function(e) {
                                                                                contraAccountId = e.target.value;
                                                                            });
                                                                            if (contraAccountId) {
                                                                                $( $el ).val(contraAccountId).trigger('change.select2');
                                                                            }
                                                                        }, 100);
                                                                        $watch('contraAccountId', value => {
                                                                            if ($( $el ).val() !== value) {
                                                                                $( $el ).val(value).trigger('change.select2');
                                                                            }
                                                                        });
                                                                        return () => {
                                                                            if ($( $el ).data('select2')) {
                                                                                $( $el ).select2('destroy');
                                                                            }
                                                                        }
                                                                    "
                                                                    class="w-full border border-gray-300 rounded px-2 py-1 text-sm bg-white outline-none"
                                                                    :class="{ 'border-green-400 bg-green-50': line.suggested_account_id && contraAccountId == line.suggested_account_id }"
                                                                    required>
                                                                    <option value="">Select Account...</option>
                                                                    @foreach ($accounts as $acc)
                                                                        <option value="{{ $acc->id }}">
                                                                            {{ $acc->name }}
                                                                            ({{ $acc->code ?? $acc->account_number }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <span x-show="line.suggested_account_id && contraAccountId == line.suggested_account_id"
                                                                    x-transition
                                                                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-green-100 text-green-700 text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full tracking-wider">
                                                                    Auto-filled
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="block text-xs font-semibold text-gray-600 mb-0.5">Why</label>
                                                            <input type="text" name="description"
                                                                :value="line.description"
                                                                class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                                        </div>
                                                    </div>
                                                    <div class="flex justify-end mt-3 border-t pt-3">
                                                        <button type="submit"
                                                            class="bg-[#0078C8] text-white font-bold py-2 px-6 rounded shadow hover:bg-[#005a96] transition text-sm">OK</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- TRANSFER TAB -->
                                            <div x-show="mode === 'transfer'" class="flex-1">
                                                <form action="{{ route('account.bank-reconciliation.transfer') }}"
                                                    method="POST" @submit.prevent="window._submitReconcileForm($event)">
                                                    @csrf
                                                    <input type="hidden" name="line_id" :value="line.id">
                                                    <div class="space-y-2">
                                                        <div>
                                                            <label
                                                                class="block text-xs font-semibold text-gray-600 mb-0.5">Select
                                                                Bank Account</label>
                                                             <select name="target_account_id"
                                                                x-init="
                                                                    setTimeout(() => {
                                                                        $( $el ).select2({
                                                                            width: '100%'
                                                                        });
                                                                    }, 100);
                                                                    return () => {
                                                                        if ($( $el ).data('select2')) {
                                                                            $( $el ).select2('destroy');
                                                                        }
                                                                    }
                                                                "
                                                                class="w-full border border-gray-300 rounded px-2 py-1 text-sm bg-white outline-none"
                                                                required>
                                                                <option value="">Select Bank Account...</option>
                                                                @foreach ($otherBanks as $bank)
                                                                    <option value="{{ $bank->id }}">
                                                                        {{ $bank->name }}
                                                                        ({{ $bank->code ?? $bank->account_number }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="block text-xs font-semibold text-gray-600 mb-0.5">Reference</label>
                                                            <input type="text" name="reference"
                                                                :value="line.reference"
                                                                class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                                                        </div>
                                                    </div>
                                                    <div class="flex justify-end mt-3 border-t pt-3">
                                                        <button type="submit"
                                                            class="bg-[#0078C8] text-white font-bold py-2 px-6 rounded shadow hover:bg-[#005a96] transition text-sm">OK</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- DISCUSS TAB -->
                                            <div x-show="mode === 'discuss'" class="flex-1">
                                                <form action="{{ route('account.bank-reconciliation.update-note') }}"
                                                    method="POST" @submit.prevent="window._submitReconcileForm($event)">
                                                    @csrf
                                                    <input type="hidden" name="line_id" :value="line.id">
                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 mb-0.5">Add
                                                            a Note</label>
                                                        <textarea name="note" rows="3" class="w-full border border-gray-300 rounded px-2 py-1 text-sm outline-none"
                                                            placeholder="Add a comment..." x-text="line.note"></textarea>
                                                    </div>
                                                    <div class="flex justify-end mt-3 border-t pt-3">
                                                        <button type="submit"
                                                            class="bg-[#0078C8] text-white font-bold py-2 px-4 rounded shadow hover:bg-[#005a96] transition text-sm">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="statementLines.length === 0" class="p-8 text-center text-gray-500">
                                <p class="text-lg font-medium">Great job! No unreconciled lines.</p>
                                <p class="text-sm">You have reconciled everything for now.</p>
                            </div>

                            <!-- AJAX Pagination for Lines -->
                            <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 bg-gray-50 sm:px-6"
                                x-show="linesPagination.total > 0">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <button @click="fetchLines(linesPagination.current_page - 1)"
                                        :disabled="linesPagination.current_page === 1"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </button>
                                    <button @click="fetchLines(linesPagination.current_page + 1)"
                                        :disabled="linesPagination.current_page === linesPagination.last_page"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </button>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing <span class="font-medium" x-text="linesPagination.from || 0"></span>
                                            to <span class="font-medium" x-text="linesPagination.to || 0"></span>
                                            of <span class="font-medium" x-text="linesPagination.total"></span> lines
                                            <span class="text-gray-400" x-show="linesPagination.last_page > 1"
                                                x-text="'(Page ' + linesPagination.current_page + ' of ' + linesPagination.last_page + ')'"></span>
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                            aria-label="Pagination">
                                            <!-- First Page -->
                                            <button @click="fetchLines(1)" :disabled="linesPagination.current_page === 1"
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">First</span>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                                </svg>
                                            </button>

                                            <!-- Previous Page -->
                                            <button @click="fetchLines(linesPagination.current_page - 1)"
                                                :disabled="linesPagination.current_page === 1"
                                                class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Previous</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Page Numbers -->
                                            <template x-for="(page, index) in getPages(linesPagination)"
                                                :key="'line-page-' + index">
                                                <button @click="page !== '...' ? fetchLines(page) : null"
                                                    :class="{
                                                        'bg-blue-50 border-blue-500 text-blue-600 z-10': page ===
                                                            linesPagination.current_page,
                                                        'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': page !==
                                                            linesPagination.current_page && page !== '...',
                                                        'bg-white border-gray-300 text-gray-500 cursor-default': page === '...'
                                                    }"
                                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                                    x-text="page">
                                                </button>
                                            </template>

                                            <!-- Next Page -->
                                            <button @click="fetchLines(linesPagination.current_page + 1)"
                                                :disabled="linesPagination.current_page === linesPagination.last_page"
                                                class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Next</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Last Page -->
                                            <button @click="fetchLines(linesPagination.last_page)"
                                                :disabled="linesPagination.current_page === linesPagination.last_page"
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Last</span>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BANK STATEMENTS TAB CONTENT -->
                    <div x-show="tab === 'statements'" class="bg-white rounded shadow overflow-hidden">
                        <div x-show="allLinesLoading" class="p-8 text-center">
                            <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <p class="mt-2 text-gray-500">Loading statements...</p>
                        </div>

                        <div x-show="!allLinesLoading">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr
                                        class="bg-gray-100 border-b border-gray-300 text-xs text-gray-500 uppercase tracking-wider">
                                        <th class="p-4 font-semibold w-10"></th>
                                        <th class="p-4 font-semibold">Date</th>
                                        <th class="p-4 font-semibold">Payee</th>
                                        <th class="p-4 font-semibold">Description</th>
                                        <th class="p-4 font-semibold">Reference</th>
                                        <th class="p-4 font-semibold text-right">Spent</th>
                                        <th class="p-4 font-semibold text-right">Received</th>
                                        <th class="p-4 font-semibold text-right">Balance</th>
                                        <th class="p-4 font-semibold text-center">Status</th>
                                        <th class="p-4 font-semibold text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="line in allStatementLines" :key="line.id">
                                        <tr class="hover:bg-gray-50 text-sm text-gray-700"
                                            :class="{ 'bg-gray-50/50': line.status === 'Reconciled' }">
                                            <td class="p-4"></td>
                                            <td class="p-4" x-text="line.formatted_date || formatDate(line.date)"></td>
                                            <td class="p-4 font-medium" x-text="line.payee"></td>
                                            <td class="p-4 font-medium" x-text="line.description"></td>
                                            <td class="p-4 text-gray-500">
                                                <a :href="'{{ url('account/bank-reconciliation/transaction') }}/' + line.unique_no" target="_blank" class="text-[#0078C8] hover:underline" x-text="line.reference || line.unique_no"></a>
                                            </td>
                                            <td class="p-4 text-right"
                                                x-text="line.amount < 0 ? formatAmount(Math.abs(line.amount)) : ''"></td>
                                            <td class="p-4 text-right"
                                                x-text="line.amount > 0 ? formatAmount(line.amount) : ''"></td>
                                            <td class="p-4 text-right font-semibold" x-text="formatAmount(line.balance)">
                                            </td>
                                            <td class="p-4 text-center">
                                                <template x-if="line.status === 'Reconciled'">
                                                    <span
                                                        class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">Reconciled</span>
                                                </template>
                                                <template x-if="line.status === 'Unreconciled'">
                                                    <span
                                                        class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-xs font-bold">Unreconciled</span>
                                                </template>
                                                <template x-if="line.status === 'Partially Reconciled'">
                                                    <span
                                                        class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs font-bold">Partially
                                                        Reconciled</span>
                                                </template>
                                            </td>
                                            <td class="p-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button @click="editLine(line)"
                                                        class="text-blue-600 hover:text-blue-800" title="Edit Entry">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                    <template
                                                        x-if="line.status !== 'Reconciled' && line.status !== 'Partially Reconciled'">
                                                        <button @click="deleteLine(line.id)"
                                                            class="text-red-600 hover:text-red-800" title="Delete Entry">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                </path>
                                                            </svg>
                                                        </button>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            <!-- AJAX Pagination for Statements -->
                            <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 bg-gray-50 sm:px-6"
                                x-show="allLinesPagination.last_page > 1">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <button @click="fetchAllLines(allLinesPagination.current_page - 1)"
                                        :disabled="allLinesPagination.current_page === 1"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </button>
                                    <button @click="fetchAllLines(allLinesPagination.current_page + 1)"
                                        :disabled="allLinesPagination.current_page === allLinesPagination.last_page"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </button>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing <span class="font-medium"
                                                x-text="allLinesPagination.from || 0"></span>
                                            to <span class="font-medium" x-text="allLinesPagination.to || 0"></span>
                                            of <span class="font-medium" x-text="allLinesPagination.total"></span>
                                            statements
                                            <span class="text-gray-400" x-show="allLinesPagination.last_page > 1"
                                                x-text="'(Page ' + allLinesPagination.current_page + ' of ' + allLinesPagination.last_page + ')'"></span>
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                            aria-label="Pagination">
                                            <!-- First Page -->
                                            <button @click="fetchAllLines(1)"
                                                :disabled="allLinesPagination.current_page === 1"
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">First</span>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                                </svg>
                                            </button>

                                            <!-- Previous Page -->
                                            <button @click="fetchAllLines(allLinesPagination.current_page - 1)"
                                                :disabled="allLinesPagination.current_page === 1"
                                                class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Previous</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Page Numbers -->
                                            <template x-for="(page, index) in getPages(allLinesPagination)"
                                                :key="'stmt-page-' + index">
                                                <button @click="page !== '...' ? fetchAllLines(page) : null"
                                                    :class="{
                                                        'bg-blue-50 border-blue-500 text-blue-600 z-10': page ===
                                                            allLinesPagination.current_page,
                                                        'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': page !==
                                                            allLinesPagination.current_page && page !== '...',
                                                        'bg-white border-gray-300 text-gray-500 cursor-default': page ===
                                                            '...'
                                                    }"
                                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                                    x-text="page">
                                                </button>
                                            </template>

                                            <!-- Next Page -->
                                            <button @click="fetchAllLines(allLinesPagination.current_page + 1)"
                                                :disabled="allLinesPagination.current_page === allLinesPagination.last_page"
                                                class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Next</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Last Page -->
                                            <button @click="fetchAllLines(allLinesPagination.last_page)"
                                                :disabled="allLinesPagination.current_page === allLinesPagination.last_page"
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Last</span>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ACCOUNT TRANSACTIONS TAB CONTENT -->
                    <div x-show="tab === 'transactions'" class="bg-white rounded shadow overflow-hidden">
                        <div x-show="txnsLoading" class="p-8 text-center">
                            <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <p class="mt-2 text-gray-500">Loading transactions...</p>
                        </div>

                        <div x-show="!txnsLoading">
                            <div x-show="selectedTxns.length > 0"
                                class="bg-blue-50 p-3 border-b border-blue-100 flex items-center justify-between">
                                <span class="text-sm text-blue-700 font-medium">
                                    <span x-text="selectedTxns.length"></span> transactions selected
                                </span>
                                <button @click="bulkUnreconcile()"
                                    class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm font-medium hover:bg-blue-700 shadow-sm transition-colors">
                                    Unreconcile Selected
                                </button>
                            </div>
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr
                                        class="bg-gray-100 border-b border-gray-300 text-xs text-gray-500 uppercase tracking-wider">
                                        <th class="p-4 w-10">
                                            <input type="checkbox" @click="toggleSelectAll()"
                                                :checked="selectedTxns.length > 0 && selectedTxns.length === accountTransactions
                                                    .filter(t => t.bank_statement_line).length"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                        </th>
                                        <th class="p-4 font-semibold">Date</th>
                                        <th class="p-4 font-semibold">Description</th>
                                        <th class="p-4 font-semibold">Reference</th>
                                        <th class="p-4 font-semibold text-right">Debit</th>
                                        <th class="p-4 font-semibold text-right">Credit</th>
                                        <th class="p-4 font-semibold text-center">Status</th>
                                        <th class="p-4 font-semibold text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="txn in accountTransactions" :key="txn.id">
                                        <tr class="hover:bg-gray-50 text-sm text-gray-700"
                                            :class="{
                                                'bg-blue-50/30': txn.bank_statement_line && selectedTxns.includes(txn
                                                    .bank_statement_line.id)
                                            }">
                                            <td class="p-4">
                                                <template x-if="txn.bank_statement_line">
                                                    <input type="checkbox" :value="txn.bank_statement_line.id"
                                                        x-model="selectedTxns"
                                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                                </template>
                                            </td>
                                            <td class="p-4" x-text="txn.formatted_date"></td>
                                            <td class="p-4 font-medium" x-text="txn.description"></td>
                                            <td class="p-4 text-gray-500">
                                                <a :href="'{{ url('account/bank-reconciliation/transaction') }}/' + txn.unique_no" target="_blank" class="text-[#0078C8] hover:underline" x-text="txn.unique_no"></a>
                                            </td>
                                            <td class="p-4 text-right"
                                                x-text="txn.debit > 0 ? formatAmount(txn.debit) : '-'"></td>
                                            <td class="p-4 text-right"
                                                x-text="txn.credit > 0 ? formatAmount(txn.credit) : '-'"></td>
                                            <td class="p-4 text-center">
                                                <template x-if="txn.bank_statement_line">
                                                    <div class="flex flex-col items-center gap-1">
                                                        <span
                                                            class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">Reconciled</span>
                                                        <button @click="unreconcile(txn.bank_statement_line.id)"
                                                            class="text-[10px] text-blue-600 hover:underline font-medium">Unreconcile</button>
                                                    </div>
                                                </template>
                                                <template x-if="!txn.bank_statement_line">
                                                    <span
                                                        class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-xs font-bold">Unreconciled</span>
                                                </template>
                                            </td>
                                            <td class="p-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <template x-if="txn.bank_statement_line">
                                                        <div class="flex gap-2">
                                                            {{-- <button @click="editLine(txn.bank_statement_line)"
                                                                class="text-blue-600 hover:text-blue-800"
                                                                title="Edit Entry">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                                    </path>
                                                                </svg>
                                                            </button> --}}
                                                            {{-- <button @click="deleteLine(txn.bank_statement_line.id)" --}}
                                                            <button @click="deleteTxn(txn.unique_no)"
                                                                class="text-red-600 hover:text-red-800"
                                                                title="Delete Entry">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                    </path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            <!-- AJAX Pagination for Transactions -->
                            <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 bg-gray-50 sm:px-6"
                                x-show="txnsPagination.last_page > 1">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <button @click="fetchTransactions(txnsPagination.current_page - 1)"
                                        :disabled="txnsPagination.current_page === 1"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </button>
                                    <button @click="fetchTransactions(txnsPagination.current_page + 1)"
                                        :disabled="txnsPagination.current_page === txnsPagination.last_page"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </button>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing <span class="font-medium" x-text="txnsPagination.from || 0"></span>
                                            to <span class="font-medium" x-text="txnsPagination.to || 0"></span>
                                            of <span class="font-medium" x-text="txnsPagination.total"></span>
                                            transactions
                                            <span class="text-gray-400" x-show="txnsPagination.last_page > 1"
                                                x-text="'(Page ' + txnsPagination.current_page + ' of ' + txnsPagination.last_page + ')'"></span>
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                            aria-label="Pagination">
                                            <!-- First Page -->
                                            <button @click="fetchTransactions(1)"
                                                :disabled="txnsPagination.current_page === 1"
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">First</span>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                                </svg>
                                            </button>

                                            <!-- Previous Page -->
                                            <button @click="fetchTransactions(txnsPagination.current_page - 1)"
                                                :disabled="txnsPagination.current_page === 1"
                                                class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Previous</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Page Numbers -->
                                            <template x-for="(page, index) in getPages(txnsPagination)"
                                                :key="'txn-page-' + index">
                                                <button @click="page !== '...' ? fetchTransactions(page) : null"
                                                    :class="{
                                                        'bg-blue-50 border-blue-500 text-blue-600 z-10': page ===
                                                            txnsPagination.current_page,
                                                        'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': page !==
                                                            txnsPagination.current_page && page !== '...',
                                                        'bg-white border-gray-300 text-gray-500 cursor-default': page ===
                                                            '...'
                                                    }"
                                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                                    x-text="page">
                                                </button>
                                            </template>

                                            <!-- Next Page -->
                                            <button @click="fetchTransactions(txnsPagination.current_page + 1)"
                                                :disabled="txnsPagination.current_page === txnsPagination.last_page"
                                                class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Next</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Last Page -->
                                            <button @click="fetchTransactions(txnsPagination.last_page)"
                                                :disabled="txnsPagination.current_page === txnsPagination.last_page"
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                                <span class="sr-only">Last</span>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.js is assumed to be loaded in layouts.auth or we add it -->
    <!-- Alpine.js Application -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('reconciliationApp', () => ({
                tab: 'reconcile',
                _tabsStale: false,
                showImport: false,
                search: '',
                searchDateFrom: '',
                searchDateTo: '',
                searchMinAmount: '',
                searchMaxAmount: '',
                perPage: 20,
                selectedFileName: '',
                statementLines: [],
                linesLoadedCounter: 0,
                allStatementLines: [],
                accountTransactions: [],
                linesLoading: false,
                allLinesLoading: false,
                txnsLoading: false,
                linesPagination: {
                    current_page: 1,
                    last_page: 1,
                    total: 0
                },
                allLinesPagination: {
                    current_page: 1,
                    last_page: 1,
                    total: 0
                },
                txnsPagination: {
                    current_page: 1,
                    last_page: 1,
                    total: 0
                },
                selectedTxns: [],
                showEditModal: false,
                editingLine: {
                    id: '',
                    date: '',
                    amount: 0,
                    payee: '',
                    description: '',
                    reference: ''
                },
                importProgress: {
                    show: false,
                    percentage: 0,
                    status: 'Parsing...'
                },

                handleImport(e) {
                    const fileInput = document.getElementById('csvFile');
                    if (!fileInput || !fileInput.files[0]) return;

                    const file = fileInput.files[0];
                    this.importProgress.show = true;
                    this.importProgress.percentage = 0;
                    this.importProgress.status = 'Parsing CSV...';

                    Papa.parse(file, {
                        header: false,
                        skipEmptyLines: true,
                        complete: (results) => {
                            let rows = results.data;

                            // Check for header
                            if (rows.length > 0) {
                                const firstRow = rows[0].join(' ').toLowerCase();
                                if (firstRow.includes('date') || firstRow.includes(
                                        'amount')) {
                                    rows.shift();
                                }
                            }

                            if (rows.length === 0) {
                                this.importProgress.show = false;
                                alert('No data found in CSV.');
                                return;
                            }

                            this.processChunks(rows);
                        },
                        error: (err) => {
                            console.error('PapaParse error:', err);
                            this.importProgress.show = false;
                            alert('Error parsing CSV file.');
                        }
                    });
                },

                async processChunks(rows) {
                    const chunkSize = 1000;
                    const totalRows = rows.length;
                    let processedRows = 0;

                    for (let i = 0; i < rows.length; i += chunkSize) {
                        const chunk = rows.slice(i, i + chunkSize);
                        this.importProgress.status =
                            `Importing rows ${i + 1} to ${Math.min(i + chunkSize, totalRows)}...`;

                        try {
                            const response = await fetch(
                                `{{ route('account.bank-reconciliation.import-chunk', $bankAccount->id) }}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        rows: chunk
                                    })
                                });

                            if (!response.ok) {
                                const errorData = await response.json();
                                throw new Error(errorData.message || 'Server error');
                            }

                            processedRows += chunk.length;
                            this.importProgress.percentage = Math.round((processedRows /
                                totalRows) * 100);
                        } catch (err) {
                            console.error('Import error:', err);
                            alert('Import failed: ' + err.message);
                            this.importProgress.show = false;
                            return;
                        }
                    }

                    this.importProgress.status = 'Import completed!';
                    this.importProgress.percentage = 100;

                    setTimeout(() => {
                        this.importProgress.show = false;
                        location.reload();
                    }, 1000);
                },

                 init() {
                    const params = new URLSearchParams(window.location.search);
                    this.tab = params.get('tab') || 'reconcile';
                    this.perPage = parseInt(params.get('per_page')) || 20;
                    this.search = params.get('search') || '';
                    this.searchDateFrom = params.get('date_from') || params.get('date') || '';
                    this.searchDateTo = params.get('date_to') || '';
                    this.searchMinAmount = params.get('min_amount') || '';
                    this.searchMaxAmount = params.get('max_amount') || '';
                    const linesPage = parseInt(params.get('lines_page')) || 1;
                    const txnsPage = parseInt(params.get('txns_page')) || 1;
                    const stmtsPage = parseInt(params.get('stmts_page')) || 1;

                    this.fetchLines(linesPage);
                    this.fetchTransactions(txnsPage);
                    this.fetchAllLines(stmtsPage);

                    // Watch for tab changes to update URL
                    this.$watch('tab', (value) => {
                        this.updateUrl();
                        if (this._tabsStale) {
                            if (value === 'reconcile') {
                                this.fetchLines(this.linesPagination.current_page, true);
                            } else if (value === 'transactions') {
                                this.fetchTransactions(this.txnsPagination.current_page, true);
                            } else if (value === 'statements') {
                                this.fetchAllLines(this.allLinesPagination.current_page, true);
                            }
                            this._tabsStale = false;
                        }
                    });

                    // Initialize Select2 for dynamic elements
                    this.initSelect2();

                    // Initialize Flatpickr for Date Filters
                    setTimeout(() => {
                        if (typeof flatpickr !== 'undefined') {
                            const syncMainDateFilters = () => {
                                this.searchDateFrom = this.$refs.searchDateFromInput && this.$refs.searchDateFromInput._flatpickr ? this.$refs.searchDateFromInput._flatpickr.input.value : this.searchDateFrom;
                                this.searchDateTo = this.$refs.searchDateToInput && this.$refs.searchDateToInput._flatpickr ? this.$refs.searchDateToInput._flatpickr.input.value : this.searchDateTo;
                            };
                            const mainDateConfig = {
                                dateFormat: "Y-m-d",
                                altInput: true,
                                altInputClass: "border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-32 bg-white",
                                altFormat: "d/m/Y",
                                allowInput: true,
                                onChange: (selectedDates, dateStr) => {
                                    syncMainDateFilters();
                                    this.fetchLines(1);
                                    this.fetchTransactions(1);
                                    this.fetchAllLines(1);
                                }
                            };

                            if (this.$refs.searchDateFromInput) {
                                flatpickr(this.$refs.searchDateFromInput, Object.assign({}, mainDateConfig, {
                                    defaultDate: this.searchDateFrom
                                }));
                            }

                            if (this.$refs.searchDateToInput) {
                                flatpickr(this.$refs.searchDateToInput, Object.assign({}, mainDateConfig, {
                                    defaultDate: this.searchDateTo
                                }));
                            }
                        }
                    }, 100);
                },

                updateUrl() {
                    const url = new URL(window.location);
                    url.searchParams.set('tab', this.tab);
                    url.searchParams.set('per_page', this.perPage);
                    url.searchParams.set('lines_page', this.linesPagination.current_page);
                    url.searchParams.set('txns_page', this.txnsPagination.current_page);
                    url.searchParams.set('stmts_page', this.allLinesPagination.current_page);
                    
                    if (this.search) url.searchParams.set('search', this.search);
                    else url.searchParams.delete('search');

                    url.searchParams.delete('date');

                    if (this.searchDateFrom) url.searchParams.set('date_from', this.searchDateFrom);
                    else url.searchParams.delete('date_from');

                    if (this.searchDateTo) url.searchParams.set('date_to', this.searchDateTo);
                    else url.searchParams.delete('date_to');

                    if (this.searchMinAmount) url.searchParams.set('min_amount', this.searchMinAmount);
                    else url.searchParams.delete('min_amount');

                    if (this.searchMaxAmount) url.searchParams.set('max_amount', this.searchMaxAmount);
                    else url.searchParams.delete('max_amount');

                    window.history.replaceState({}, '', url);
                },

                statementExportUrl() {
                    const url = new URL(`{{ route('account.bank-reconciliation.statement.export-excel', $bankAccount->id) }}`, window.location.origin);

                    if (this.search) url.searchParams.set('search', this.search);
                    if (this.searchDateFrom) url.searchParams.set('date_from', this.searchDateFrom);
                    if (this.searchDateTo) url.searchParams.set('date_to', this.searchDateTo);
                    if (this.searchMinAmount) url.searchParams.set('min_amount', this.searchMinAmount);
                    if (this.searchMaxAmount) url.searchParams.set('max_amount', this.searchMaxAmount);

                    return url.toString();
                },

                unreconcile(lineId) {
                    if (!confirm('Are you sure you want to unreconcile this transaction?')) return;

                    fetch(`{{ route('account.bank-reconciliation.unreconcile') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                line_id: lineId
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.fetchLines();
                            this.fetchTransactions();
                            this.fetchAllLines();
                        })
                        .catch(err => {
                            location.reload();
                        });
                },

                deleteLine(lineId) {
                    if (!confirm(
                            'Are you sure you want to delete this entry? This will also remove any associated ledger transactions.'
                        )) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `{{ url('account/bank-reconciliation/line') }}/${lineId}`;

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'DELETE';
                    form.appendChild(methodField);

                    document.body.appendChild(form);
                    form.submit();
                },

                deleteTxn(txnId) {
                    if (!confirm(
                            'Are you sure you want to delete this entry? This will also remove any associated ledger transactions.'
                        )) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `{{ url('account/bank-reconciliation/txn') }}/${txnId}`;

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'DELETE';
                    form.appendChild(methodField);

                    document.body.appendChild(form);
                    form.submit();
                },

                editLine(line) {
                    if (!line) return;

                    let rawDate = line.date;
                    let formattedDate = '';
                    if (rawDate) {
                        if (typeof rawDate === 'string') {
                            // Handle ISO 8601 format with timezone
                            // The server sends: "2026-01-02T19:00:00.000Z" which is midnight UTC+5 = 19:00 UTC previous day
                            // We need to parse it and add the timezone offset to get the correct local date
                            const datePart = rawDate.split('T')[0];
                            const timePart = rawDate.split('T')[1];

                            if (timePart && timePart.endsWith('Z')) {
                                // It's UTC format, need to convert to local
                                // Extract hours from the time part
                                const hours = parseInt(timePart.split(':')[0], 10);
                                // If hours is 19, that means the datePart is actually the previous day in local time
                                // So we need to add one day to get the correct local date
                                const dateObj = new Date(datePart);
                                dateObj.setDate(dateObj.getDate() + 1);
                                formattedDate = dateObj.toISOString().split('T')[0];
                            } else {
                                formattedDate = datePart;
                            }
                        } else if (rawDate instanceof Date) {
                            const dateObj = new Date(rawDate);
                            dateObj.setDate(dateObj.getDate() + 1);
                            formattedDate = dateObj.toISOString().split('T')[0];
                        }
                    }

                    this.editingLine = {
                        id: line.id,
                        date: formattedDate,
                        amount: line.amount,
                        payee: line.payee,
                        description: line.description,
                        reference: line.reference
                    };
                    this.showEditModal = true;

                    // Initialize flatpickr for the date field in edit modal
                    setTimeout(() => {
                        const dateInput = document.querySelector('#editDateInput');
                        if (dateInput && typeof flatpickr !== 'undefined') {
                            if (dateInput._flatpickr) {
                                dateInput._flatpickr.setDate(formattedDate);
                            } else {
                                flatpickr(dateInput, {
                                    dateFormat: "Y-m-d",
                                    altInput: true,
                                    altFormat: "d/m/Y",
                                    allowInput: true,
                                    defaultDate: formattedDate
                                });
                            }
                        }
                    }, 100);
                },

                bulkUnreconcile() {
                    if (this.selectedTxns.length === 0) return;
                    if (!confirm(
                            `Are you sure you want to unreconcile ${this.selectedTxns.length} transactions?`
                        )) return;

                    fetch(`{{ route('account.bank-reconciliation.bulk-unreconcile') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                line_ids: this.selectedTxns
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.selectedTxns = [];
                            this.fetchLines();
                            this.fetchTransactions();
                            this.fetchAllLines();
                        });
                },

                toggleSelectAll() {
                    if (this.selectedTxns.length === this.accountTransactions.filter(t => t
                            .bank_statement_line).length) {
                        this.selectedTxns = [];
                    } else {
                        this.selectedTxns = this.accountTransactions
                            .filter(t => t.bank_statement_line)
                            .map(t => t.bank_statement_line.id);
                    }
                },

                fetchLines(page = 1, silent = false) {
                    if (!silent) this.linesLoading = true;
                    const searchParams = new URLSearchParams();
                    searchParams.append('page', page);
                    searchParams.append('per_page', this.perPage);
                    if (this.search) searchParams.append('search', this.search);
                    if (this.searchDateFrom) searchParams.append('date_from', this.searchDateFrom);
                    if (this.searchDateTo) searchParams.append('date_to', this.searchDateTo);
                    if (this.searchMinAmount) searchParams.append('min_amount', this.searchMinAmount);
                    if (this.searchMaxAmount) searchParams.append('max_amount', this.searchMaxAmount);
                    fetch(
                            `{{ route('account.bank-reconciliation.get-statement-lines', $bankAccount->id) }}?${searchParams.toString()}`
                        )
                        .then(res => res.json())
                        .then(data => {
                            // If we requested a page that is now empty, and we aren't on page 1, go back
                            if (data.data.length === 0 && data.current_page > 1) {
                                this.fetchLines(data.current_page - 1);
                                return;
                            }

                            this.statementLines = data.data;
                            this.linesLoadedCounter++;
                            this.linesPagination = {
                                current_page: data.current_page,
                                last_page: data.last_page,
                                total: data.total,
                                from: data.from,
                                to: data.to
                            };
                            this.updateUrl();
                            this.linesLoading = false;
                            // Initialize Select2 for newly rendered selects
                            this.initSelect2();
                        })
                        .catch(err => {
                            console.error('Error fetching lines:', err);
                            this.linesLoading = false;
                        });
                },

                fetchTransactions(page = 1, silent = false) {
                    if (!silent) this.txnsLoading = true;
                    const searchParams = new URLSearchParams();
                    searchParams.append('page', page);
                    searchParams.append('per_page', this.perPage);
                    if (this.search) searchParams.append('search', this.search);
                    if (this.searchDateFrom) searchParams.append('date_from', this.searchDateFrom);
                    if (this.searchDateTo) searchParams.append('date_to', this.searchDateTo);
                    if (this.searchMinAmount) searchParams.append('min_amount', this.searchMinAmount);
                    if (this.searchMaxAmount) searchParams.append('max_amount', this.searchMaxAmount);
                    fetch(
                            `{{ route('account.bank-reconciliation.get-account-transactions', $bankAccount->id) }}?${searchParams.toString()}`
                        )
                        .then(res => res.json())
                        .then(data => {
                            this.accountTransactions = data.data;
                            this.txnsPagination = {
                                current_page: data.current_page,
                                last_page: data.last_page,
                                total: data.total,
                                from: data.from,
                                to: data.to
                            };
                            this.updateUrl();
                            this.txnsLoading = false;
                        })
                        .catch(err => {
                            console.error('Error fetching transactions:', err);
                            this.txnsLoading = false;
                        });
                },

                fetchAllLines(page = 1, silent = false) {
                    if (!silent) this.allLinesLoading = true;
                    const searchParams = new URLSearchParams();
                    searchParams.append('page', page);
                    searchParams.append('per_page', this.perPage);
                    if (this.search) searchParams.append('search', this.search);
                    if (this.searchDateFrom) searchParams.append('date_from', this.searchDateFrom);
                    if (this.searchDateTo) searchParams.append('date_to', this.searchDateTo);
                    if (this.searchMinAmount) searchParams.append('min_amount', this.searchMinAmount);
                    if (this.searchMaxAmount) searchParams.append('max_amount', this.searchMaxAmount);
                    fetch(
                            `{{ route('account.bank-reconciliation.get-all-statement-lines', $bankAccount->id) }}?${searchParams.toString()}`
                        )
                        .then(res => res.json())
                        .then(data => {
                            this.allStatementLines = data.data;
                            this.allLinesPagination = {
                                current_page: data.current_page,
                                last_page: data.last_page,
                                total: data.total,
                                from: data.from,
                                to: data.to
                            };
                            this.updateUrl();
                            this.allLinesLoading = false;
                        })
                        .catch(err => {
                            console.error('Error fetching all statement lines:', err);
                            this.allLinesLoading = false;
                        });
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                },

                formatAmount(amount) {
                    if (amount === undefined || amount === null) return '0.00';
                    return parseFloat(amount).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                txnId(txn) {
                    return txn.id;
                },

                getPages(pagination) {
                    let total = pagination.last_page;
                    let current = pagination.current_page;
                    let delta = 2;
                    let left = current - delta;
                    let right = current + delta + 1;
                    let range = [];
                    let rangeWithDots = [];
                    let l;

                    for (let i = 1; i <= total; i++) {
                        if (i == 1 || i == total || (i >= left && i < right)) {
                            range.push(i);
                        }
                    }

                    for (let i of range) {
                        if (l) {
                            if (i - l === 2) {
                                rangeWithDots.push(l + 1);
                            } else if (i - l !== 1) {
                                rangeWithDots.push('...');
                            }
                        }
                        rangeWithDots.push(i);
                        l = i;
                    }

                    return rangeWithDots;
                },

                _handleReconcileRefresh() {
                    // Only refresh the active tab silently (no loader, preserves scroll)
                    if (this.tab === 'reconcile') {
                        this.fetchLines(this.linesPagination.current_page, true);
                    } else if (this.tab === 'transactions') {
                        this.fetchTransactions(this.txnsPagination.current_page, true);
                    } else if (this.tab === 'statements') {
                        this.fetchAllLines(this.allLinesPagination.current_page, true);
                    }
                    // Mark other tabs as stale so they refresh when switched to
                    this._tabsStale = true;
                },

                initSelect2() {
                    // Initialize Select2 for dynamically created selects in Alpine templates
                    setTimeout(() => {
                        const selects = document.querySelectorAll('.select2');
                        selects.forEach(select => {
                            if (!$(select).hasClass('select2-hidden-accessible')) {
                                $(select).select2({
                                    width: '100%'
                                });
                            }
                        });
                    }, 100);
                }
            }));
        });
    </script>

    <script>
        // Initialize Select2 when statement lines are loaded
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        setTimeout(() => {
                            const selects = document.querySelectorAll('.select2');
                            selects.forEach(select => {
                                if (!$(select).hasClass(
                                        'select2-hidden-accessible')) {
                                    $(select).select2({
                                        width: '100%'
                                    });
                                }
                            });
                        }, 100);
                    }
                });
            });

            const container = document.querySelector('.grid\-cols-12');
            if (container) {
                observer.observe(container, {
                    childList: true,
                    subtree: true
                });
            }
        });
    </script>

    <script>
        // Global AJAX form handler for reconciliation — avoids page reload, preserves scroll position
        window._submitReconcileForm = async function(event) {
            const form = event.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Processing...'; }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData
                });

                if (!response.ok) {
                    const err = await response.text();
                    throw new Error(err);
                }

                // Read JSON response
                const result = await response.json();

                // Dispatch event to refresh Alpine component data (no page reload = scroll stays)
                window.dispatchEvent(new CustomEvent('reconcile-refresh'));

                // Show success notification
                if (typeof Notification !== 'undefined' && Notification.success) {
                    Notification.success(result.message || 'Reconciled successfully.');
                }
            } catch (err) {
                console.error('Reconcile form error:', err);
                alert('An error occurred. Please try again.');
            } finally {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalText; }
            }
        };
    </script>
@endsection
