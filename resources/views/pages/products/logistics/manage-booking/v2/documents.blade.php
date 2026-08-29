@extends('layouts.auth')

@section('title', 'Trade Documents')

@section('content')
    <div class="mt-4">
        <!-- Module Tabs -->
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.manage-booking') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">Trade Documents</h1>
                    <p class="text-xs text-gray-400 mt-0.5">
                        @if (request('q') && $booking)
                            Showing documents for <span
                                class="font-semibold text-gray-600">{{ $documentScope ?? ($booking->booking_id ?? $booking->booking_number) }}</span>
                        @else
                            All trade documents across all bookings
                        @endif
                    </p>
                </div>
                <x-common.back-button url="{{ route('logistics.manage-booking.v2.index') }}" />
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div
                    class="mb-4 px-4 py-3 bg-lime-50 border border-lime-200 rounded-lg text-lime-700 text-xs font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif

            {{-- Search --}}
            <form method="GET" action="{{ route('logistics.manage-booking.v2.documents') }}"
                class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5 mb-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Filter by Booking ID</p>
                <div class="flex items-center gap-3 max-w-lg">
                    <div class="relative flex-1">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}"
                            placeholder="e.g. BK-0001 — leave blank to see all"
                            class="w-full pl-9 pr-4 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                    </div>
                    <button type="submit"
                        class="px-5 py-2.5 bg-[#0078C8] hover:bg-[#006bb0] text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                        Search
                    </button>
                    @if (request('q'))
                        <a href="{{ route('logistics.manage-booking.v2.documents') }}"
                            class="px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-600 text-sm rounded-lg border border-gray-200 transition-colors">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            {{-- Booking not found --}}
            @if (request('q') && !$booking)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-16 text-center">
                    <i class="fa-solid fa-circle-xmark text-red-300 text-4xl mb-3"></i>
                    <p class="text-sm font-medium text-gray-600">
                        No booking found for <span class="font-bold text-gray-900">{{ request('q') }}</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Check the Booking ID and try again</p>
                </div>
            @else
                {{-- Documents Table --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

                    {{-- Table Header --}}
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">
                                @if ($booking)
                                    Documents —
                                    <span class="text-[#0078C8]">{{ $documentScope ?? $booking->booking_number }}</span>
                                @else
                                    All Trade Documents
                                @endif
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                @if ($booking)
                                    {{ $booking->shipper ?? '' }}
                                    @if ($booking->origin_port ?? null)
                                        · {{ $booking->origin_port }} → {{ $booking->dest_port }}
                                    @endif
                                @else
                                    {{ $documents->count() }} document{{ $documents->count() !== 1 ? 's' : '' }} total
                                @endif
                            </p>
                        </div>

                        {{-- Upload button only visible when a specific booking is selected --}}
                        @if ($booking)
                            <button onclick="document.getElementById('uploadDocModal').classList.remove('hidden')"
                                class="flex items-center gap-2 px-4 py-2 bg-[#0078C8] hover:bg-[#006bb0] text-white text-xs font-medium rounded-lg transition-colors">
                                <i class="fa-solid fa-upload text-[10px]"></i> Upload Document
                            </button>
                        @endif
                    </div>

                    {{-- Empty state --}}
                    @if ($documents->isEmpty())
                        <div class="px-6 py-16 text-center border-t border-gray-100">
                            <i class="fa-solid fa-folder-open text-gray-200 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-400">No trade documents found</p>
                            @if ($booking)
                                <p class="text-xs text-gray-300 mt-1">Click <strong>Upload Document</strong> to add one</p>
                            @endif
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/60 text-gray-400 border-b border-gray-100">
                                        {{-- Show Booking column only in "all documents" mode --}}
                                        @if (!$booking)
                                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Booking
                                                ID
                                            </th>
                                        @endif
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Document
                                            Type</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Description
                                        </th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Required
                                            For</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">File</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Status</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Date</th>
                                        <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-right">
                                            Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @php
                                        $statusPill = [
                                            'Received' => 'bg-green-50 text-green-600 border-green-100',
                                            'Pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                            'Missing' => 'bg-red-50 text-red-600 border-red-100',
                                            'Active' => 'bg-blue-50 text-blue-600 border-blue-100',
                                            'Filed' => 'bg-teal-50 text-teal-600 border-teal-100',
                                            'In Progress' => 'bg-purple-50 text-purple-600 border-purple-100',
                                            'Not Required' => 'bg-gray-100 text-gray-400 border-gray-200',
                                        ];
                                    @endphp

                                    @foreach ($documents as $doc)
                                        @php $pill = $statusPill[$doc->status] ?? 'bg-gray-50 text-gray-500 border-gray-200'; @endphp
                                        <tr class="hover:bg-gray-50/50 transition-colors">

                                            {{-- Booking column — all-docs mode only --}}
                                            @if (!$booking)
                                                <td class="px-5 py-3">
                                                    <a href="{{ route('logistics.manage-booking.v2.documents', ['q' => $doc->booking?->booking_number ?? '']) }}"
                                                        class="text-xs font-semibold font-mono text-[#0078C8] hover:underline">
                                                        {{ $doc->child_booking_number ? $doc->child_booking_number : $doc->booking?->booking_number }}

                                                    </a>
                                                </td>
                                            @endif

                                            <td class="px-5 py-3 text-sm font-bold text-gray-900">{{ $doc->document_type }}
                                            </td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ $doc->description ?: '—' }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ $doc->required_for ?: '—' }}
                                            </td>
                                            <td class="px-5 py-3 text-sm">
                                                @if ($doc->file_path)
                                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank"
                                                        class="flex items-center gap-1.5 text-[#0078C8] hover:underline">
                                                        <i class="fa-solid fa-file text-[10px]"></i>
                                                        {{ $doc->original_filename }}
                                                    </a>
                                                @else
                                                    <span class="text-gray-300 text-xs">No file</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3">
                                                <span
                                                    class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $pill }}">
                                                    {{ $doc->status }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 text-xs text-gray-400">
                                                {{ $doc->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button
                                                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 transition-colors"
                                                        onclick="openEditDocModal({{ json_encode($doc) }})">
                                                        <i class="fa-solid fa-pen text-xs"></i>
                                                    </button>
                                                    <button
                                                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-red-100 text-red-400 hover:bg-red-50 transition-colors"
                                                        onclick="deleteDoc({{ $doc->id }}, '{{ route('logistics.manage-booking.v2.documents.destroy', $doc->id) }}')">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="px-5 py-3 border-t border-gray-100">
                        <p class="text-xs text-gray-400">
                            {{ $documents->count() }} trade document{{ $documents->count() !== 1 ? 's' : '' }}
                            @if ($booking)
                                for {{ $booking->booking_id }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Edit Document Modal --}}
                <div id="editDocModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
                    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Update Document</h3>
                                <p class="text-xs text-gray-400 mt-0.5" id="editDocBookingNum"></p>
                            </div>
                            <button onclick="document.getElementById('editDocModal').classList.add('hidden')"
                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        <form id="editDocForm" method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
                            @csrf
                            <div id="editDocMethod"></div>

                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1.5">
                                    Document Type <span class="text-red-500">*</span>
                                </label>
                                <select name="document_type" id="edit_document_type" required
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                    <option value="">Select type...</option>
                                    <option>Bill of Lading (BOL)</option>
                                    <option>Airway Bill (AWB)</option>
                                    <option>Commercial Invoice</option>
                                    <option>Packing List</option>
                                    <option>Certificate of Origin (CoO)</option>
                                    <option>Letter of Credit (LC)</option>
                                    <option>GD / Goods Declaration</option>
                                    <option>Insurance Certificate</option>
                                    <option>Fumigation Certificate</option>
                                    <option>Phytosanitary Certificate</option>
                                    <option>Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1.5">Description</label>
                                <input type="text" name="description" id="edit_description"
                                    placeholder="Brief description..."
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1.5">Required For</label>
                                <input type="text" name="required_for" id="edit_required_for"
                                    placeholder="e.g. Customs clearance, CIF incoterms..."
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1.5">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select name="status" id="edit_status" required
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                    <option value="Pending">Pending</option>
                                    <option value="Received">Received</option>
                                    <option value="Missing">Missing</option>
                                    <option value="Filed">Filed</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Not Required">Not Required</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1.5">File (Keep empty to maintain
                                    current file)</label>
                                <label
                                    class="flex items-center gap-2.5 w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 border-dashed cursor-pointer hover:border-[#0078C8] hover:bg-blue-50/30 transition group">
                                    <i
                                        class="fa-solid fa-paperclip text-gray-300 group-hover:text-[#0078C8] text-sm transition"></i>
                                    <span id="editDocFileName"
                                        class="text-sm text-gray-400 group-hover:text-[#0078C8] transition truncate">
                                        Click to upload new file
                                    </span>
                                    <input type="file" name="file"
                                        onchange="document.getElementById('editDocFileName').textContent = this.files[0]?.name ?? 'Click to upload new file'"
                                        class="hidden">
                                </label>
                                <p id="currentFileName" class="text-[10px] text-[#0078C8] mt-1 truncate"></p>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button"
                                    onclick="document.getElementById('editDocModal').classList.add('hidden')"
                                    class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                                    <i class="fa-solid fa-save text-[10px]"></i> Update Document
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Upload Modal — only rendered when a booking is in context --}}
                @if ($booking)
                    <div id="uploadDocModal"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
                        <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-md mx-4">
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Upload Document</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $booking->booking_id }}</p>
                                </div>
                                <button onclick="document.getElementById('uploadDocModal').classList.add('hidden')"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>

                            <form method="POST"
                                action="{{ route('logistics.manage-booking.v2.documents.store', $booking->id) }}"
                                enctype="multipart/form-data" class="px-6 py-5 space-y-4">
                                @csrf

                                <div>
                                    <label class="text-xs font-medium text-gray-600 block mb-1.5">
                                        Document Type <span class="text-red-500">*</span>
                                    </label>
                                    <select name="document_type" required
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                        <option value="">Select type...</option>
                                        <option>Bill of Lading (BOL)</option>
                                        <option>Airway Bill (AWB)</option>
                                        <option>Commercial Invoice</option>
                                        <option>Packing List</option>
                                        <option>Certificate of Origin (CoO)</option>
                                        <option>Letter of Credit (LC)</option>
                                        <option>GD / Goods Declaration</option>
                                        <option>Insurance Certificate</option>
                                        <option>Fumigation Certificate</option>
                                        <option>Phytosanitary Certificate</option>
                                        <option>Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Description</label>
                                    <input type="text" name="description" placeholder="Brief description..."
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                </div>

                                <div>
                                    <label class="text-xs font-medium text-gray-600 block mb-1.5">Required For</label>
                                    <input type="text" name="required_for"
                                        placeholder="e.g. Customs clearance, CIF incoterms..."
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                </div>

                                <div>
                                    <label class="text-xs font-medium text-gray-600 block mb-1.5">
                                        Status <span class="text-red-500">*</span>
                                    </label>
                                    <select name="status" required
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                        <option value="Pending">Pending</option>
                                        <option value="Received">Received</option>
                                        <option value="Missing">Missing</option>
                                        <option value="Filed">Filed</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Not Required">Not Required</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-medium text-gray-600 block mb-1.5">File (optional)</label>
                                    <label
                                        class="flex items-center gap-2.5 w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 border-dashed cursor-pointer hover:border-[#0078C8] hover:bg-blue-50/30 transition group">
                                        <i
                                            class="fa-solid fa-paperclip text-gray-300 group-hover:text-[#0078C8] text-sm transition"></i>
                                        <span id="docFileName"
                                            class="text-sm text-gray-400 group-hover:text-[#0078C8] transition truncate">
                                            Click to upload file
                                        </span>
                                        <input type="file" name="file"
                                            onchange="document.getElementById('docFileName').textContent = this.files[0]?.name ?? 'Click to upload file'"
                                            class="hidden">
                                    </label>
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <button type="button"
                                        onclick="document.getElementById('uploadDocModal').classList.add('hidden')"
                                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                                        <i class="fa-solid fa-upload text-[10px]"></i> Upload
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

            @endif {{-- end booking-not-found else --}}

        </div>
    </div>

    <script>
        function openEditDocModal(doc) {
            const modal = document.getElementById('editDocModal');
            const form = document.getElementById('editDocForm');

            // Set form action dynamically
            form.action = `/logistics/manage-booking/v2/documents/${doc.id}/update`;

            // Populate fields
            document.getElementById('edit_document_type').value = doc.document_type;
            document.getElementById('edit_description').value = doc.description || '';
            document.getElementById('edit_required_for').value = doc.required_for || '';
            document.getElementById('edit_status').value = doc.status;

            // Show current file info if exists
            const currentFile = document.getElementById('currentFileName');
            if (doc.original_filename) {
                currentFile.textContent = `Current file: ${doc.original_filename}`;
            } else {
                currentFile.textContent = '';
            }

            // Reset file input label
            document.getElementById('editDocFileName').textContent = 'Click to upload new file';
            document.getElementById('editDocBookingNum').textContent = doc.booking?.booking_id ?? doc.booking
                ?.booking_number ?? '';

            modal.classList.remove('hidden');
        }

        function deleteDoc(id, url) {
            if (!confirm('Delete this document?')) return;
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(r => r.json()).then(d => {
                if (d.success) location.reload();
            });
        }
    </script>
@endsection
