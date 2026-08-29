{{-- resources/views/pages/products/warehouse/scrap/show.blade.php --}}
@extends('layouts.auth')

@section('title', 'Scrap Material - ' . $scrap->scrap_id)

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('warehouse.scrap.index') }}" class="text-gray-600 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </a>
                            <h2 class="text-stone-950 text-xl font-normal">Scrap Material Details</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                            @if (!$scrap->isDisposed())
                                <a href="{{ route('warehouse.scrap.edit', $scrap->id) }}"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-xs font-normal rounded hover:bg-[#0078C8] hover:text-white flex items-center gap-2 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    EDIT
                                </a>
                            @endif
                            @if ($scrap->canDelete())
                                <button type="button"
                                    onclick="deleteScrap({{ $scrap->id }}, '{{ route('warehouse.scrap.destroy', $scrap->id) }}')"
                                    class="px-4 py-2 border border-red-400 text-red-600 text-xs font-normal rounded hover:bg-red-600 hover:text-white flex items-center gap-2 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    DELETE
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="mt-4 space-y-4">

                        <!-- Basic Info Card -->
                        <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                                <h3 class="text-base font-medium text-gray-900">Basic Information</h3>
                                @php
                                    $statusBadge = match ($scrap->status) {
                                        'in_storage' => 'bg-blue-100 text-blue-800',
                                        'pending_disposal' => 'bg-yellow-100 text-yellow-800',
                                        'pending_recycling' => 'bg-orange-100 text-orange-800',
                                        'transferred' => 'bg-purple-100 text-purple-800',
                                        'disposed' => 'bg-gray-100 text-gray-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                    $statusText = match ($scrap->status) {
                                        'in_storage' => 'In Storage',
                                        'pending_disposal' => 'Pending Disposal',
                                        'pending_recycling' => 'Pending Recycling',
                                        'transferred' => 'Transferred',
                                        'disposed' => 'Disposed',
                                        default => $scrap->status,
                                    };
                                @endphp
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusBadge }}">
                                    {{ $statusText }}
                                </span>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">
                                            Identification</h4>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Scrap ID:</span>
                                                <span class="text-xs font-medium font-mono">{{ $scrap->scrap_id }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Item Description:</span>
                                                <span class="text-xs font-medium">{{ $scrap->item_description }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Material Category:</span>
                                                <span
                                                    class="text-xs font-medium">{{ $scrap->scrapCategory?->name ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Quantity
                                            & Type</h4>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Weight:</span>
                                                <span class="text-xs font-medium">{{ number_format($scrap->weight, 2) }}
                                                    kg</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Quantity Type:</span>
                                                <span
                                                    class="text-xs font-medium capitalize">{{ $scrap->quantity_type }}</span>
                                            </div>

                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Quantity:</span>
                                                <span class="text-xs font-medium">{{ $scrap->quantity ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Location
                                            & Source</h4>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Source:</span>
                                                <span
                                                    class="text-xs font-medium capitalize">{{ str_replace('_', ' ', $scrap->source) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Current Location:</span>
                                                <span
                                                    class="text-xs font-medium">{{ $scrap->warehouseLocation->name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-xs text-gray-600">Scrap Type:</span>
                                                <span
                                                    class="text-xs font-medium">{{ $scrap->scrapType?->name ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scrap Reason & Notes -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Scrap Reason -->
                            <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h3 class="text-base font-medium text-gray-900">Scrap Reason</h3>
                                </div>
                                <div class="p-6">
                                    <p class="text-sm text-gray-700 bg-gray-50 rounded-lg px-4 py-3">
                                        {{ $scrap->scrap_reason }}</p>
                                </div>
                            </div>

                            <!-- Additional Notes -->
                            <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h3 class="text-base font-medium text-gray-900">Additional Notes</h3>
                                </div>
                                <div class="p-6">
                                    @if ($scrap->notes)
                                        <p class="text-sm text-gray-700 bg-gray-50 rounded-lg px-4 py-3">
                                            {{ $scrap->notes }}</p>
                                    @else
                                        <p class="text-sm text-gray-400 italic">No additional notes.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Audit Info -->
                        <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-base font-medium text-gray-900">Audit Information</h3>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-xs text-gray-500 block mb-1">Created By</span>
                                        <span
                                            class="font-medium text-gray-800">{{ $scrap->createdBy?->username ?? 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500 block mb-1">Created At</span>
                                        <span
                                            class="font-medium text-gray-800">{{ $scrap->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500 block mb-1">Last Updated</span>
                                        <span
                                            class="font-medium text-gray-800">{{ $scrap->updated_at->format('d M Y, H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div id="deleteConfirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Scrap Material</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to delete this scrap material? This
                                    action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmDeleteBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent  px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="hideDeleteModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300  px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toast-content');
            const colors = {
                success: 'text-green-600',
                error: 'text-red-600',
                info: 'text-blue-600'
            };
            const icons = {
                success: '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/>',
                error: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>',
            };
            content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    <svg class="w-5 h-5 ${colors[type]||colors.success}" fill="currentColor" viewBox="0 0 20 20">${icons[type]||icons.success}</svg>
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('toast').classList.add('hidden')">✕</button>
            </div>`;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3500);
        }

        let deleteUrl = null;

        function deleteScrap(id, url) {
            deleteUrl = url;
            document.getElementById('deleteConfirmationModal').classList.remove('hidden');
        }
        window.deleteScrap = deleteScrap;

        function hideDeleteModal() {
            document.getElementById('deleteConfirmationModal').classList.add('hidden');
            deleteUrl = null;
        }
        window.hideDeleteModal = hideDeleteModal;

        document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
            if (!deleteUrl) return;
            fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => window.location.href = '{{ route('warehouse.scrap.index') }}', 1500);
                    }
                    hideDeleteModal();
                })
                .catch(() => {
                    showToast('Network error. Please try again.', 'error');
                    hideDeleteModal();
                });
        });

        @if (session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif
        @if (session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif
    </script>
@endpush
