@extends('layouts.auth')

@section('title', 'Logistics Entitys')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Importers / Exporters</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative">
                        <div></div>
                        <div></div>

                        <!-- Column Chooser -->
                        {{-- <x-columns :columns="[
                            'business_legal_name' => 'Business Name',
                            'entity_type' => 'Type',
                            'contact_full_name' => 'Contact',
                            'registered_city' => 'Location',
                        ]" name="entity_columns" /> --}}

                        <!-- Export Dropdown -->
                        <x-common.export-dropdown name="entity" />

                        <!-- Add New Button -->
                        <a href="{{ route('logistics.entities.create') }}"
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
                        <x-common.filter-form action="{{ route('logistics.entities.index') }}">
                            <div class="w-full md:w-48">
                                <label for="entity_type"
                                    class="block mb-[13px] text-xs font-medium text-gray-700 uppercase tracking-wider">Type</label>
                                <select name="entity_type" id="entity_type"
                                    class="select2 w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">All Types</option>
                                    <option value="Importer" {{ request('entity_type') == 'Importer' ? 'selected' : '' }}>
                                        Importer</option>
                                    <option value="Exporter" {{ request('entity_type') == 'Exporter' ? 'selected' : '' }}>
                                        Exporter</option>
                                    <option value="Both" {{ request('entity_type') == 'Both' ? 'selected' : '' }}>Both
                                    </option>
                                </select>
                            </div>
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
                        @include('pages.products.logistics.entities.partials.table')
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
