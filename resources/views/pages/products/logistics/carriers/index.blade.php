@extends('layouts.auth')

@section('title', 'Logistics Carriers')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Logistics Carriers</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative">
                        <div></div>
                        <div></div>
                        <!-- Column Chooser -->
                        <!-- Export Dropdown -->
                        <x-common.export-dropdown name="carrier" />

                        <!-- Add New Button -->
                        <a href="{{ route('logistics.carriers.create') }}"
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
                        <x-common.filter-form action="{{ route('logistics.carriers.index') }}">
                            <div class="w-full md:w-48">
                                <label for="logistics_mode"
                                    class="block mb-[13px] text-xs font-medium text-gray-700 uppercase tracking-wider">Mode</label>
                                <select name="logistics_mode" id="logistics_mode"
                                    class="select2 w-full outline-none border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">All Modes</option>
                                    <option value="inland_trucking"
                                        {{ request('logistics_mode') == 'inland_trucking' ? 'selected' : '' }}>
                                        Inland (Trucking)</option>
                                    <option value="air_freight"
                                        {{ request('logistics_mode') == 'air_freight' ? 'selected' : '' }}>
                                        Air Freight</option>
                                    <option value="ocean_freight"
                                        {{ request('logistics_mode') == 'ocean_freight' ? 'selected' : '' }}>
                                        Ocean Freight</option>
                                    <option value="rail_freight"
                                        {{ request('logistics_mode') == 'rail_freight' ? 'selected' : '' }}>
                                        Rail Freight</option>
                                    <option value="multimodal"
                                        {{ request('logistics_mode') == 'multimodal' ? 'selected' : '' }}>
                                        Multimodal</option>
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
                        @include('pages.products.logistics.carriers.partials.table')
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
