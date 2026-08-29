<table class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">
                Date</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">
                File</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">
                Type</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">
                Extracted Vendor/Entity</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">
                Total</th>
            <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider text-xs">
                Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200" id="draftsTableBody">
        @forelse($drafts as $draft)
            <tr id="draft-row-{{ $draft->id }}" class="hover:bg-gray-50">
                <td class="px-4 py-3 whitespace-nowrap">
                    {{ $draft->created_at?->format('d M, Y') }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ Storage::url($draft->file_path) }}" target="_blank" class="truncate w-32"
                            title="{{ $draft->file_name }}">
                            {{ $draft->file_name }}
                        </a>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <span
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ ucfirst(str_replace('_', ' ', $draft->document_type)) }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if ($draft->document_type === 'bank_statement')
                        <span class="text-gray-500 italic">{{ count($draft->extracted_data['transactions'] ?? []) }}
                            transactions</span>
                    @else
                        {{ $draft->extracted_data['vendor_name'] ?? 'N/A' }}
                    @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    @if ($draft->document_type !== 'bank_statement')
                        <span class="font-medium">{{ number_format($draft->extracted_data['total'] ?? 0, 2) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="reviewDraft({{ $draft->id }})"
                        class="text-[#0078C8] hover:text-[#006bb0] mr-3">Review</button>
                    <button onclick="deleteDraft({{ $draft->id }})"
                        class="text-red-600 hover:text-red-900">Delete</button>
                </td>
            </tr>
        @empty
            <tr id="emptyStateRow">
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z">
                        </path>
                    </svg>
                    No documents in queue. Upload some files to get started.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($drafts instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="px-4 py-3 border-t border-gray-200" id="pagination-wrapper">
        {{ $drafts->links() }}
    </div>
@endif

<script>
    if (typeof DRAFTS !== 'undefined') {
        Object.assign(DRAFTS, @json($drafts->keyBy('id')));
    }
    // Update count in header
    const countEl = document.getElementById('draftCount');
    if (countEl) {
        countEl.textContent = '{{ $drafts->total() }} Records';
    }
</script>
