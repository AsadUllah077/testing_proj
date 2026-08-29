{{-- resources/views/pages/products/warehouse/gate-pass/partials/table.blade.php --}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="w-10 px-3 py-3 text-left">
                    <span class="sr-only">Expand details</span>
                </th>
                <th class="px-4 py-3 text-left w-10">
                    <input id="checkAll" type="checkbox"
                        class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded cursor-pointer">
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gate Pass ID
                </th>
                <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Info</th>
                <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source
                    Location</th>
                <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categories</th>
                <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prepared By
                </th>
                <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            @forelse($gatePasses as $gatePass)
                @php
                    $statusClass = match ($gatePass->status) {
                        'QC_AND_SECURITY_APPROVED' => 'bg-green-100 text-green-700',
                        'SECURITY_APPROVAL_PENDING' => 'bg-yellow-100 text-yellow-700',
                        'QC_APPROVAL_PENDING' => 'bg-blue-100 text-blue-700',
                        'REJECTED' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-700',
                    };
                    $statusText = match ($gatePass->status) {
                        'QC_AND_SECURITY_APPROVED' => 'Approved',
                        'SECURITY_APPROVAL_PENDING' => 'Security Pending',
                        'QC_APPROVAL_PENDING' => 'QC Pending',
                        'REJECTED' => 'Rejected',
                        default => $gatePass->status,
                    };
                    $categories = $gatePass->items
                        ->map(fn ($item) => trim((string) $item->category_name))
                        ->filter()
                        ->unique(fn ($category) => mb_strtolower($category))
                        ->sort()
                        ->values();
                @endphp
                <tr class="hover:bg-gray-50 transition-colors" data-gate-pass-id="{{ $gatePass->id }}">
                    <td class="px-3 py-3 align-middle">
                        <button type="button"
                            class="gate-pass-expand inline-flex h-7 w-7 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-blue-50 hover:text-[#0078C8] focus:outline-none focus:ring-2 focus:ring-[#0078C8]/30"
                            data-target="gate-pass-details-{{ $gatePass->id }}"
                            aria-expanded="false"
                            aria-controls="gate-pass-details-{{ $gatePass->id }}"
                            title="Show gate pass details">
                            <svg class="gate-pass-expand-icon h-4 w-4 transition-transform duration-200" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="sr-only">Show details for {{ $gatePass->gate_pass_id }}</span>
                        </button>
                    </td>
                    <td class="px-4 py-3">
                        @if (!$gatePass->isFullyApproved())
                            <input type="checkbox"
                                class="rowCheck h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded cursor-pointer"
                                value="{{ $gatePass->id }}">
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <a href="{{ route('warehouse.gate-pass.show', $gatePass->id) }}"
                            class="text-sm font-mono font-medium text-[#0078C8] hover:underline">
                            {{ $gatePass->gate_pass_id }}
                        </a>
                    </td>
                    <td class="hidden md:table-cell px-4 py-3 text-sm text-gray-700">
                        @if ($gatePass->customer_info)
                            <span class="block max-w-xs truncate" title="{{ $gatePass->customer_info }}">
                                {{ $gatePass->customer_info }}
                            </span>
                        @else
                            <span class="text-xs text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $gatePass->warehouseLocation->name ?? 'N/A' }}
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $gatePass->items_count ?? $gatePass->items->count() }}
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 text-sm text-gray-700">
                        @if ($categories->isNotEmpty())
                            <div class="flex max-w-sm flex-wrap gap-1.5">
                                @foreach ($categories as $category)
                                    <span class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                        {{ $category }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-xs text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $gatePass->preparedBy?->username ?? 'N/A' }}
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $gatePass->created_at->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                            {{ $statusText }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="relative inline-block">
                            <button
                                class="dropdown-btn p-1.5 rounded hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div
                                class="dropdown-menu hidden absolute right-0 mt-1 w-44 bg-white border border-gray-200 rounded shadow-lg z-20">
                                <div class="py-1">
                                    <a href="{{ route('warehouse.gate-pass.show', $gatePass->id) }}"
                                        class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View
                                    </a>

                                    {{-- @if (!$gatePass->isFullyApproved() && $gatePass->status !== 'REJECTED') --}}
                                        @if(auth()->user()->hasPermission('warehouse.gate-pass.edit'))
                                        <a href="{{ route('warehouse.gate-pass.edit', $gatePass->id) }}"
                                            class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                            <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </a>
                                        @endif
                                    {{-- @endif --}}

                                    @if ($gatePass->canApproveQC())
                                        <button onclick="showQcModal({{ $gatePass->id }})"
                                            class="w-full flex items-center gap-2 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 text-left">
                                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            QC Approval
                                        </button>
                                    @endif

                                    @if ($gatePass->canApproveSecurity())
                                        <button onclick="showSecurityModal({{ $gatePass->id }})"
                                            class="w-full flex items-center gap-2 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 text-left">
                                            <svg class="w-3.5 h-3.5 text-purple-500" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Security Approval
                                        </button>
                                    @endif

                                    @if (!$gatePass->isFullyApproved() && $gatePass->status !== 'REJECTED')
                                        @if(auth()->user()->hasPermission('warehouse.gate-pass.delete'))
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <button onclick="confirmDelete(this)"
                                            data-url="{{ route('warehouse.gate-pass.destroy', $gatePass->id) }}"
                                            data-row="tr[data-gate-pass-id='{{ $gatePass->id }}']"
                                            class="w-full flex items-center gap-2 px-4 py-2 text-xs text-red-600 hover:bg-red-50 text-left">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @php
                    $activeItems = $gatePass->items->whereNull('returned_at');
                    $returnedItems = $gatePass->items->whereNotNull('returned_at');
                    $totalQuantity = $gatePass->items->sum(fn ($item) => (int) ($item->quantity ?? 1));
                @endphp
                <tr id="gate-pass-details-{{ $gatePass->id }}"
                    class="gate-pass-details-row hidden bg-slate-50/80"
                    data-parent-gate-pass-id="{{ $gatePass->id }}">
                    <td colspan="11" class="border-y border-blue-100 px-4 py-5 sm:px-8">
                        <div class="space-y-5">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Gate Pass Details</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $gatePass->gate_pass_id }}</p>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $gatePass->items->count() }} {{ Str::plural('item', $gatePass->items->count()) }}
                                    <span class="mx-1 text-gray-300">&bull;</span>
                                    Total quantity: <span class="font-semibold text-gray-700">{{ $totalQuantity }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                                        <tbody class="divide-y divide-gray-100">
                                            <tr>
                                                <th class="w-40 bg-gray-50 px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Customer Info</th>
                                                <td class="px-4 py-2.5 text-gray-800 whitespace-pre-line">{{ $gatePass->customer_info ?: 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-gray-50 px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Invoice No</th>
                                                <td class="px-4 py-2.5 text-gray-800">{{ $gatePass->invoice_no ?: 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-gray-50 px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Warranty</th>
                                                <td class="px-4 py-2.5 text-gray-800">{{ $gatePass->warranty_days ? $gatePass->warranty_days . ' ' . Str::plural('day', $gatePass->warranty_days) : 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                                        <tbody class="divide-y divide-gray-100">
                                            <tr>
                                                <th class="w-40 bg-gray-50 px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Source Location</th>
                                                <td class="px-4 py-2.5 text-gray-800">{{ $gatePass->warehouseLocation->name ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-gray-50 px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Departure Date</th>
                                                <td class="px-4 py-2.5 text-gray-800">{{ $gatePass->departure_date?->format('d M Y, H:i') ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-gray-50 px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Prepared By</th>
                                                <td class="px-4 py-2.5 text-gray-800">{{ $gatePass->preparedBy?->username ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">#</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Unique ID</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Product</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Category</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Quantity</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @forelse ($activeItems->concat($returnedItems) as $item)
                                            <tr class="{{ $item->returned_at ? 'bg-orange-50/70' : 'hover:bg-gray-50' }}">
                                                <td class="px-4 py-2.5 text-xs text-gray-400">{{ $loop->iteration }}</td>
                                                <td class="px-4 py-2.5 text-sm font-mono {{ $item->returned_at ? 'text-gray-400 line-through' : 'text-gray-800' }}">{{ $item->unique_id ?: 'N/A' }}</td>
                                                <td class="px-4 py-2.5 text-sm {{ $item->returned_at ? 'text-gray-400 line-through' : 'text-gray-800' }}">{{ $item->product_name ?: 'N/A' }}</td>
                                                <td class="px-4 py-2.5 text-sm {{ $item->returned_at ? 'text-gray-400' : 'text-gray-700' }}">{{ $item->category_name ?: 'Uncategorized' }}</td>
                                                <td class="px-4 py-2.5 text-sm text-gray-700">{{ $item->quantity ?? 1 }}</td>
                                                <td class="px-4 py-2.5">
                                                    @if ($item->returned_at)
                                                        <span class="inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">Returned</span>
                                                        <p class="mt-1 text-[10px] text-gray-400">{{ $item->returned_at->format('d M Y, H:i') }}</p>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Active</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-400">No item details found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm font-medium text-gray-500">No gate passes found</p>
                            <a href="{{ route('warehouse.gate-pass.create') }}"
                                class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#0066a0] transition-colors inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Add New Gate Pass
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$gatePasses" />

<!-- QC Modal -->
<div id="qcModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">QC Approval</h3>
                <button onclick="hideQcModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="qcApprovalForm" class="px-6 py-5">
                @csrf
                <label class="block text-xs font-medium text-gray-600 mb-1.5">QC Remarks</label>
                <textarea name="qc_remarks" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none resize-none placeholder:text-gray-400"
                    placeholder="Enter QC remarks..."></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="hideQcModal()"
                        class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700">Approve
                        QC</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Security Modal -->
<div id="securityModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Security Approval</h3>
                <button onclick="hideSecurityModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="securityApprovalForm" class="px-6 py-5">
                @csrf
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Security In charge Name <span
                        class="text-red-500">*</span></label>
                <input type="text" name="security_incharge_name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none placeholder:text-gray-400"
                    placeholder="Enter security in charge name">
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="hideSecurityModal()"
                        class="px-4 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white text-xs font-medium rounded hover:bg-purple-700">Approve
                        Security</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        let currentGatePassId = null;

        window.showQcModal = function(id) {
            currentGatePassId = id;
            document.getElementById('qcModal').classList.remove('hidden');
        };
        window.hideQcModal = function() {
            currentGatePassId = null;
            document.getElementById('qcModal').classList.add('hidden');
        };
        window.showSecurityModal = function(id) {
            currentGatePassId = id;
            document.getElementById('securityModal').classList.remove('hidden');
        };
        window.hideSecurityModal = function() {
            currentGatePassId = null;
            document.getElementById('securityModal').classList.add('hidden');
        };

        function submitApproval(formId, endpoint, hideModal) {
            const form = document.getElementById(formId);
            const submitBtn = form.querySelector('button[type="submit"]');
            const original = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Approving...';

            fetch(endpoint, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message);
                        hideModal();
                        setTimeout(() => data.redirect ? (window.location.href = data.redirect) : window.location
                            .reload(), 1500);
                    } else {
                        Notification.error(data.message);
                        submitBtn.disabled = false;
                        submitBtn.textContent = original;
                    }
                })
                .catch(() => {
                    Notification.error('Network error. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = original;
                });
        }

        document.getElementById('qcApprovalForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentGatePassId) return;
            submitApproval('qcApprovalForm', `/warehouse/gate-pass/${currentGatePassId}/qc-approval`, hideQcModal);
        });

        document.getElementById('securityApprovalForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentGatePassId) return;
            submitApproval('securityApprovalForm', `/warehouse/gate-pass/${currentGatePassId}/security-approval`,
                hideSecurityModal);
        });

        document.getElementById('qcModal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget || e.target.classList.contains('absolute')) hideQcModal();
        });
        document.getElementById('securityModal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget || e.target.classList.contains('absolute')) hideSecurityModal();
        });
    </script>
@endpush
