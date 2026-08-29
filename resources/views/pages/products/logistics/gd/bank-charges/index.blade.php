@extends('layouts.auth')

@section('title', 'Bank Charges Register')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Header --}}
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-1">Bank Charges Register</h2>
                        <p class="text-xs text-gray-400">All LC-related and trade finance charges</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative mt-3 md:mt-0">
                        <div></div>
                        <div></div>
                        <x-common.export-dropdown name="bank-charges" />
                        <a href="{{ route('logistics.gd.bank-charges.create') }}"
                            class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Record Charge
                        </a>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 my-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <p class="text-2xl font-black text-[#0078C8]">PKR 4.2M</p>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Total This Month</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <p class="text-2xl font-black text-amber-500">PKR 630K</p>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">LC Opening Fees</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <p class="text-2xl font-black text-gray-800">18</p>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Transactions</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                        <p class="text-2xl font-black text-green-600">PKR 185K</p>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Avg. Per Charge</p>
                    </div>
                </div>

                {{-- Filters + Table --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                        <x-common.filter-form action="{{ route('logistics.gd.bank-charges.index') }}">
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">Charge Type</label>
                                <select name="charge_type"
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    <option value="">All Types</option>
                                    <option value="lc_opening"   {{ request('charge_type') == 'lc_opening'   ? 'selected' : '' }}>LC Opening Commission</option>
                                    <option value="lc_amendment" {{ request('charge_type') == 'lc_amendment' ? 'selected' : '' }}>LC Amendment Fee</option>
                                    <option value="swift"        {{ request('charge_type') == 'swift'        ? 'selected' : '' }}>SWIFT / Telex Charges</option>
                                    <option value="acceptance"   {{ request('charge_type') == 'acceptance'   ? 'selected' : '' }}>Acceptance Commission</option>
                                    <option value="discrepancy"  {{ request('charge_type') == 'discrepancy'  ? 'selected' : '' }}>Discrepancy Fee</option>
                                    <option value="guarantee"    {{ request('charge_type') == 'guarantee'    ? 'selected' : '' }}>Bank Guarantee Fee</option>
                                </select>
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-2">Bank</label>
                                <select name="bank"
                                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                    <option value="">All Banks</option>
                                    <option value="hbl" {{ request('bank') == 'hbl' ? 'selected' : '' }}>HBL</option>
                                    <option value="mcb" {{ request('bank') == 'mcb' ? 'selected' : '' }}>MCB Bank</option>
                                    <option value="ubl" {{ request('bank') == 'ubl' ? 'selected' : '' }}>UBL</option>
                                    <option value="sc"  {{ request('bank') == 'sc'  ? 'selected' : '' }}>Standard Chartered</option>
                                    <option value="mbl" {{ request('bank') == 'mbl' ? 'selected' : '' }}>Meezan Bank</option>
                                </select>
                            </div>
                            <x-common.date-filter name="from_date" id="from_date" value="{{ request('from_date') }}" label="From Date" />
                            <x-common.date-filter name="to_date"   id="to_date"   value="{{ request('to_date') }}"   label="To Date" />
                            <x-common.search-filter name="q" value="{{ request('q') }}" label="Search" id="search" />
                            <x-filter-button />
                        </x-common.filter-form>
                    </div>

                    {{-- Table --}}
                    <div id="custom_table">
                        @include('pages.products.logistics.gd.bank-charges.partials.table')
                    </div>
                </div>

            </div>
        </div>
    </div>
    <x-delete-confirmation />
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteModal    = document.getElementById('deleteModal');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const cancelDeleteBtn  = document.getElementById('cancelDelete');
        let deleteUrl = null;

        document.body.addEventListener('click', function (e) {
            const btn = e.target.closest('.delete-btn');
            if (btn) { deleteUrl = btn.dataset.url; deleteModal.classList.remove('hidden'); }
        });
        cancelDeleteBtn.addEventListener('click', function () {
            deleteModal.classList.add('hidden'); deleteUrl = null;
        });
        confirmDeleteBtn.addEventListener('click', function () {
            if (deleteUrl) {
                fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => { if (data.success) window.location.reload(); else alert('Error: ' + data.message); })
                .catch(() => alert('An error occurred while deleting.'));
            }
        });
    });
</script>
@endpush
