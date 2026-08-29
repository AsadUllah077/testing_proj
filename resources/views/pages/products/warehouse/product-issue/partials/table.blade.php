<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50">
                <th data-col="issue_date" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest">Date Issued</th>
                <th data-col="product_id" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest">Product ID</th>
                <th data-col="product_title" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest">Product Title</th>
                <th data-col="reference" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest">Reference</th>
                <th data-col="employee" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest">Employee</th>
                <th data-col="quantity" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest text-center">Qty</th>
                <th data-col="issued_by" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest">Issued By</th>
                <th data-col="status" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest">Status</th>
                <th data-col="returned_on" class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest">Returned On</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 border-b border-gray-100 uppercase tracking-widest text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($issues as $issue)
                <tr class="hover:bg-blue-50/30 transition-colors duration-150">
                    <td data-col="issue_date" class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $issue->issue_date->format('d M, Y') }}</div>
                        <div class="text-xs text-gray-400 tracking-tighter">{{ $issue->issue_date->format('h:i A') }}</div>
                    </td>
                    <td data-col="product_id" class="px-6 py-4">
                        <span class="text-sm font-bold text-[#0078C8]">{{ $issue->product->unique_id }}</span>
                    </td>
                    <td data-col="product_title" class="px-6 py-4">
                        <div class="text-sm text-gray-700 truncate max-w-[200px]">{{ $issue->product->title }}</div>
                    </td>
                    <td data-col="reference" class="px-6 py-4">
                        <div class="text-sm text-gray-700 truncate max-w-[160px]">{{ $issue->reference ?: '-' }}</div>
                    </td>
                    <td data-col="employee" class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-sm bg-gray-100 flex items-center justify-center text-gray-600 text-[10px] font-bold border border-gray-200">
                                {{ $issue->employee ? strtoupper(substr($issue->employee->full_name, 0, 2)) : '--' }}
                            </div>
                            <div class="text-sm font-medium text-gray-700">{{ $issue->employee?->full_name ?? 'Not assigned' }}</div>
                        </div>
                    </td>
                    <td data-col="quantity" class="px-6 py-4 text-center">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-bold">{{ $issue->quantity }}</span>
                    </td>
                    <td data-col="issued_by" class="px-6 py-4 text-sm text-gray-600">
                        {{ $issue->issuedBy->username ?? 'System' }}
                    </td>
                    <td data-col="status" class="px-6 py-4">
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $issue->status === 'Issued' ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-green-50 text-green-600 border border-green-100' }}">
                            {{ $issue->status }}
                        </span>
                    </td>
                    <td data-col="returned_on" class="px-6 py-4 text-sm text-gray-500 italic">
                        @if($issue->return_date)
                            {{ $issue->return_date->format('d M, Y') }}
                        @else
                            -
                        @endif
                    </td>
                    @php
                        $canReturnIssue = auth()->user()->hasPermission('warehouse.product-issue.return') && $issue->status === 'Issued';
                        $canEditIssue = auth()->user()->hasPermission('warehouse.product-issue.edit');
                        $canDeleteIssue = auth()->user()->hasPermission('warehouse.product-issue.delete');
                    @endphp
                    <td class="px-6 py-4 text-right flex justify-end items-center gap-3">
                        @if($canReturnIssue)
                            <button onclick="returnProduct({{ $issue->id }})" class="group relative text-gray-400 hover:text-green-600 transition-colors" title="Mark as Returned">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 8.959 8.959 0 01-4.5 7.723M12 21a8.967 8.967 0 01-6-2.277" />
                                </svg>
                            </button>
                        @endif
                        
                        @if($canEditIssue)
                        <a href="{{ route('warehouse.product-issue.edit', $issue->id) }}" class="text-gray-400 hover:text-[#0078C8] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </a>
                        @endif
                        @if($canDeleteIssue)
                        <form action="{{ route('warehouse.product-issue.destroy', $issue->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this record?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                        @endif
                        @if(!$canReturnIssue && !$canEditIssue && !$canDeleteIssue)
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-6 py-16 text-center text-gray-500 text-sm italic">
                        No product issue records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
