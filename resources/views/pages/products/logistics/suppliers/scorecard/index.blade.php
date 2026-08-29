@extends('layouts.auth')

@section('title', 'Supplier Scorecards')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div>
                        <h1 class="text-gray-900 text-xl font-normal">Supplier Performance Scorecards</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Track and analyze supplier quality, delivery, and compliance metrics</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('logistics.suppliers.v2.index') }}" class="px-3 py-2 bg-white text-gray-700 border border-gray-300 text-xs font-medium rounded hover:bg-gray-50 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-users"></i> Supplier Directory
                        </a>
                        <a href="{{ route('logistics.suppliers.scorecard.create') }}"
                            class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                            <i class="fa-solid fa-plus text-[10px]"></i> New Scorecard
                        </a>
                    </div>
                </div>

                {{-- Table Section --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Supplier</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Quality Score</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Delivery OTD</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">Doc Compliance</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">Communication</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">Price Comp.</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">Overall</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">Grade</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap text-center">YoY Change</th>
                                    <th class="px-5 py-4 text-[10px] font-semibold uppercase tracking-wider text-right whitespace-nowrap">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($scorecards as $row)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-5 py-4 font-bold text-sm text-gray-900 whitespace-nowrap">{{ $row->supplier->company_name }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden shrink-0">
                                                <div class="h-full rounded-full @if($row->quality_score >= 90) bg-green-500 @elseif($row->quality_score >= 75) bg-amber-500 @else bg-red-500 @endif" 
                                                     style="width: {{ $row->quality_score }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-gray-700">{{ $row->quality_score }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden shrink-0">
                                                <div class="h-full rounded-full @if($row->delivery_otd >= 90) bg-green-500 @elseif($row->delivery_otd >= 75) bg-amber-500 @else bg-red-500 @endif" 
                                                     style="width: {{ $row->delivery_otd }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-gray-700">{{ $row->delivery_otd }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden shrink-0">
                                                <div class="h-full rounded-full @if($row->doc_compliance >= 90) bg-green-500 @elseif($row->doc_compliance >= 75) bg-amber-500 @else bg-red-500 @endif" 
                                                     style="width: {{ $row->doc_compliance }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-gray-700">{{ $row->doc_compliance }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-0.5 text-amber-400 text-[10px]">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="fa-{{ $i <= $row->communication ? 'solid' : 'regular' }} fa-star"></i>
                                            @endfor
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-0.5 text-blue-400 text-[10px]">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="fa-{{ $i <= $row->price_competitiveness ? 'solid' : 'regular' }} fa-star"></i>
                                            @endfor
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="text-sm font-black @if($row->overall_score >= 90) text-green-600 @elseif($row->overall_score >= 75) text-amber-600 @else text-red-600 @endif">
                                            {{ $row->overall_score }}/100
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-block w-6 h-6 leading-6 rounded-lg text-[10px] font-black uppercase tracking-tighter border 
                                            @if($row->grade === 'A') bg-green-50 text-green-600 border-green-200
                                            @elseif($row->grade === 'B') bg-amber-50 text-amber-600 border-amber-200
                                            @else bg-red-50 text-red-600 border-red-200 @endif">
                                            {{ $row->grade }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="text-[10px] font-bold @if($row->yoy_status === 'up') text-green-600 @elseif($row->yoy_status === 'dn') text-red-600 @else text-gray-400 @endif">
                                            @if($row->yoy_status === 'up') <i class="fa-solid fa-arrow-up text-[8px] mr-0.5"></i> @elseif($row->yoy_status === 'dn') <i class="fa-solid fa-arrow-down text-[8px] mr-0.5"></i> @else — @endif
                                            {{ $row->yoy_change }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('logistics.suppliers.scorecard.edit', $row->id) }}" class="px-3 py-1 bg-white border border-gray-200 text-gray-600 text-[10px] font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                                @if($row->overall_score < 60) <span class="text-red-500 font-bold">Review</span> @else Details @endif
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
