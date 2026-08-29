@extends('layouts.auth')

@section('title', 'Logistics Suppliers')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Supplier </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative">
                        <div></div>
                        <div></div>

                        <!-- Column Chooser -->
                        {{-- <x-columns :columns="[
                            'name' => 'Name',
                            'region' => 'Region',
                            'business_type' => 'Business Type',
                            'contact' => 'Contact',
                        ]" name="supplier_columns" /> --}}

                        <!-- Export Dropdown -->
                        <x-common.export-dropdown name="supplier" />

                        <!-- Add New Button -->
                        <a href="{{ route('logistics.suppliers.create') }}"
                            class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            ADD NEW
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="my-4 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                        <x-common.filter-form action="{{ route('logistics.suppliers.index') }}">
                            <x-common.date-filter name="from_date" id="from_date" value="{{ request('from_date') }}"
                                label="From Date" />
                            <x-common.date-filter name="to_date" id="to_date" value="{{ request('to_date') }}"
                                label="To Date" />
                            <x-common.search-filter name="q" value="{{ request('q') }}" label="Search"
                                id="search" />
                            <x-filter-button />
                        </x-common.filter-form>
                    </div>

                    <!-- Table -->
                    <div id="custom_table">
                        {{-- <x-common.table :rows="$suppliers" :columns="[
                            'name' => 'Name',
                            'region' => 'Region',
                            'business_type' => 'Business Type',
                            'contact' => 'Contact',
                        ]" /> --}}

                        @include('pages.products.logistics.suppliers.partials.table')
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Delete confirmation modal component --}}
    <x-delete-confirmation />
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const cancelDeleteBtn = document.getElementById('cancelDelete');
            let deleteUrl = null;

            // Open Modal
            document.body.addEventListener('click', function(e) {
                const btn = e.target.closest('.delete-btn');
                if (btn) {
                    deleteUrl = btn.dataset.url;
                    deleteModal.classList.remove('hidden');
                }
            });

            // Close Modal
            cancelDeleteBtn.addEventListener('click', function() {
                deleteModal.classList.add('hidden');
                deleteUrl = null;
            });

            // Confirm Delete
            confirmDeleteBtn.addEventListener('click', function() {
                if (deleteUrl) {
                    fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting.');
                        });
                }
            });
        });
    </script>
@endpush
