<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">GD Number</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Booking Ref</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Type</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Importer</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">HS Code</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Customs Value</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Total Duty</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Exam</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Status</th>
                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-right">Action
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($gds as $gd)
                @php
                    $statusConfig = [
                        'Draft' => 'bg-gray-50 text-gray-500 border-gray-200',
                        'Filed' => 'bg-blue-50 text-blue-600 border-blue-100',
                        'Approved' => 'bg-green-50 text-green-700 border-green-100',
                        'Cleared' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        'On Hold' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'Rejected' => 'bg-red-50 text-red-700 border-red-100',
                    ];
                    $examConfig = [
                        'no_exam' => 'bg-green-50 text-green-700 border-green-100',
                        'doc_check' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'physical_exam' => 'bg-red-50 text-red-700 border-red-100',
                    ];
                    $examLabel = [
                        'no_exam' => 'No Exam',
                        'doc_check' => 'Doc Check',
                        'physical_exam' => 'Physical Exam',
                    ];
                    $sc = $statusConfig[$gd->status] ?? 'bg-gray-50 text-gray-500 border-gray-200';
                    $ec = $examConfig[$gd->exam_status] ?? 'bg-gray-50 text-gray-500 border-gray-200';
                    $el = $examLabel[$gd->exam_status] ?? ucfirst($gd->exam_status ?? 'No Exam');
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-4 text-sm font-bold text-gray-900">
                        <a href="{{ route('logistics.gd.show', $gd->id) }}"
                            class="hover:text-[#0078C8] hover:underline">{{ $gd->gd_number }}</a>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">
                        {{ $gd->booking?->booking_number ?? ($gd->bl_awb_number ?: '—') }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ ucfirst($gd->declaration_type) ?: '—' }}
                    </td>
                    <td class="px-5 py-4 text-sm font-medium text-gray-700">{{ $gd->importer ?: '—' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600 font-mono">{{ $gd->hs_code ?: '—' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-700">
                        {{ $gd->customs_value ? number_format($gd->customs_value, 0) . ' ' . $gd->currency : '—' }}
                    </td>
                    <td class="px-5 py-4 text-sm font-bold text-gray-800">
                        {{ $gd->total_duty ? 'PKR ' . number_format($gd->total_duty, 0) : '—' }}
                    </td>
                    <td class="px-5 py-4">
                        <span
                            class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $ec }}">
                            {{ $el }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span
                            class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $sc }}">
                            {{ $gd->status }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="relative inline-block" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-500 transition-all">
                                <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-100 z-20 overflow-hidden"
                                style="display:none;">
                                <a href="{{ route('logistics.gd.show', $gd->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-eye w-3.5 text-gray-400"></i> View
                                </a>
                                <a href="{{ route('logistics.gd.edit', $gd->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-pen w-3.5 text-gray-400"></i> Edit
                                </a>
                                <button
                                    class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-red-600 hover:bg-red-50 transition-colors delete-btn"
                                    data-url="{{ route('logistics.gd.destroy', $gd->id) }}">
                                    <i class="fa-solid fa-trash-can w-3.5"></i> Delete
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-5 py-14 text-center">
                        <i class="fa-solid fa-file-invoice text-gray-200 text-4xl mb-3 block"></i>
                        <p class="text-sm text-gray-400 font-medium">No GD records found</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your filters or file a new GD
                        </p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between gap-4">
    <p class="text-xs text-gray-500">
        Showing <span class="font-semibold text-gray-700">{{ $gds->firstItem() ?? 0 }}</span>–<span
            class="font-semibold text-gray-700">{{ $gds->lastItem() ?? 0 }}</span>
        of <span class="font-semibold text-gray-700">{{ $gds->total() }}</span> declarations
    </p>
    <div class="text-xs" id="pagination-wrapper">{{ $gds->links() }}</div>
</div>
