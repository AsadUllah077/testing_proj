@extends('layouts.auth')

@section('title', 'Mass Mailer Contacts')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Contacts</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-2 relative">
                        <x-common.export-dropdown name="mass_mailer_contacts" />
                        <a href="{{ route('mass-mailer.contacts.create') }}"
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
                        <x-common.filter-form action="{{ route('mass-mailer.contacts.index') }}">
                            <div class="flex flex-col gap-1 min-w-[200px]">
                                <label class="text-xs font-medium text-gray-500 uppercase">Tag</label>
                                <select name="tag_id"
                                    class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8] outline-hidden">
                                    <option value="">All Tags</option>
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}"
                                            {{ request('tag_id') == $tag->id ? 'selected' : '' }}>{{ $tag->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <x-common.search-filter name="search" value="{{ request('search') }}" label="Search"
                                id="search" />
                            <x-filter-button />
                        </x-common.filter-form>
                    </div>

                    <!-- Table -->
                    <div id="custom_table">
                        @include('pages.mass_mailer.contacts.partials.table')
                    </div>
                </div>
            </div>
        </div>
    </div>
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
