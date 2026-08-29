@extends('layouts.auth')

@section('title', 'GD Exam Queue')

@section('content')
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.gd') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">GD Exam Queue</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Goods Declarations under Doc Check or Physical Examination</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('logistics.gd.index') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-arrow-left text-xs"></i> All GDs
                    </a>
                </div>
            </div>

            {{-- Filters + Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                {{-- Filters --}}
                <div class="p-5 border-b border-gray-200 flex items-center gap-4 flex-wrap">
                    <form method="GET" action="{{ route('logistics.gd.exam') }}"
                        class="flex items-center gap-3 flex-wrap">
                        <select name="exam_status"
                            class="py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <option value="">All Exam Types</option>
                            <option value="doc_check" {{ request('exam_status') === 'doc_check' ? 'selected' : '' }}>Doc
                                Check</option>
                            <option value="physical_exam"
                                {{ request('exam_status') === 'physical_exam' ? 'selected' : '' }}>Physical Exam</option>
                        </select>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" name="q" value="{{ request('q') }}"
                                placeholder="GD number, importer, HS code..."
                                class="pl-8 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all w-64">
                        </div>
                        <button type="submit"
                            class="px-4 py-2 bg-[#0078C8] hover:bg-[#006bb0] text-white text-xs font-medium rounded-lg transition-colors">
                            Filter
                        </button>
                        @if (request()->hasAny(['exam_status', 'q']))
                            <a href="{{ route('logistics.gd.exam') }}"
                                class="px-4 py-2 bg-white border border-gray-200 text-gray-600 text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                Clear
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">GD Number</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Importer</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">HS Code</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Description</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Customs Value</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Total Duty</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">Exam Type</th>
                                <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider">GD Status</th>
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
                                        'doc_check' => 'bg-amber-50 text-amber-700 border-amber-100',
                                        'physical_exam' => 'bg-red-50 text-red-700 border-red-100',
                                    ];
                                    $examLabel = [
                                        'doc_check' => 'Doc Check',
                                        'physical_exam' => 'Physical Exam',
                                    ];
                                    $sc = $statusConfig[$gd->status] ?? 'bg-gray-50 text-gray-500 border-gray-200';
                                    $ec =
                                        $examConfig[$gd->exam_status] ?? 'bg-amber-50 text-amber-700 border-amber-100';
                                    $el = $examLabel[$gd->exam_status] ?? ucfirst($gd->exam_status);
                                @endphp
                                <tr class="hover:bg-amber-50/30 transition-colors">
                                    <td class="px-5 py-4 text-sm font-bold text-gray-900">
                                        <a href="{{ route('logistics.gd.show', $gd->id) }}"
                                            class="hover:text-[#0078C8] hover:underline">{{ $gd->gd_number }}</a>
                                    </td>
                                    <td class="px-5 py-4 text-sm font-medium text-gray-700">{{ $gd->importer ?: '—' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600 font-mono">{{ $gd->hs_code ?: '—' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600 max-w-40 truncate"
                                        title="{{ $gd->description }}">
                                        {{ $gd->description ?: '—' }}
                                    </td>
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
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-14 text-center">
                                        <i class="fa-solid fa-magnifying-glass text-gray-200 text-4xl mb-3 block"></i>
                                        <p class="text-sm text-gray-400 font-medium">No GDs under examination</p>
                                        <p class="text-xs text-gray-300 mt-1">GDs flagged for Doc Check or Physical Exam
                                            will appear here</p>
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
                        of <span class="font-semibold text-gray-700">{{ $gds->total() }}</span> declarations under exam
                    </p>
                    <div class="text-xs">{{ $gds->links() }}</div>
                </div>

            </div>
        </div>
    </div>
@endsection
