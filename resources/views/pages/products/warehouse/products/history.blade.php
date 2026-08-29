@extends('layouts.auth')

@section('title', 'Product History - ' . $product->unique_id)

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header with Stats - Using brand color -->
                <div class="bg-gradient-to-r from-[#0078C8] to-[#005a9c] rounded-lg shadow-lg mb-6">
                    <div class="px-6 py-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h1 class="text-white text-2xl font-bold">Product History</h1>
                                    <span class="px-3 py-1 bg-white/20 text-white text-xs rounded-full backdrop-blur-sm border border-white/30">
                                        {{ $histories->total() }} Events
                                    </span>
                                </div>
                                <p class="text-blue-100 text-sm mt-2">Complete audit trail for <span class="font-semibold">{{ $product->unique_id }}</span></p>
                            </div>
                            <div class="flex flex-col-reverse sm:flex-row gap-3 w-full sm:w-auto">
                                @if(!$product->sold_status)
                                <a href="{{ route('warehouse.products.edit', $product->id) }}"
                                    class="px-4 py-2 bg-white text-[#0078C8] rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors flex items-center gap-2 border border-white/50 w-full sm:w-auto text-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit Product
                                </a>
                                @endif
                                <a href="{{ route('warehouse.products.index') }}"
                                    class="px-4 py-2 bg-white/10 text-white rounded-lg text-sm font-medium hover:bg-white/20 transition-colors backdrop-blur-sm flex items-center gap-2 border border-white/20 w-full sm:w-auto text-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                    </svg>
                                    Back to Products
                                </a>
                            </div>
                        </div>
                </div>
            </div>

            <!-- Product Image Gallery Row -->
            <div x-data="productGallery('{{ $product->title }}')" x-cloak class="mb-6">
                <template x-if="!loading && images.length > 0">
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm p-4">
                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Slider Section -->
                            <div class="w-full md:w-1/3 relative group aspect-square bg-gray-50 rounded-lg overflow-hidden border border-gray-100 flex items-center justify-center">
                                <img :src="images[currentIndex]"
                                     class="w-full h-full object-contain p-4 transition-all duration-500 ease-out transform scale-100 group-hover:scale-105"
                                     :alt="'Gallery image ' + (currentIndex + 1)">

                                <!-- Navigation -->
                                <template x-if="images.length > 1">
                                    <div class="absolute inset-0 flex items-center justify-between px-2 opacity-0 group-hover:opacity-100 transition-all duration-300 pointer-events-none">
                                        <button @click.stop="prev()"
                                                class="w-8 h-8 bg-white/95 backdrop-blur shadow rounded-full flex items-center justify-center text-gray-800 hover:bg-[#0078C8] hover:text-white transition-all pointer-events-auto border border-gray-100">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                                        </button>
                                        <button @click.stop="next()"
                                                class="w-8 h-8 bg-white/95 backdrop-blur shadow rounded-full flex items-center justify-center text-gray-800 hover:bg-[#0078C8] hover:text-white transition-all pointer-events-auto border border-gray-100">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                        </button>
                                    </div>
                                </template>

                                <!-- Pagination Dots -->
                                <template x-if="images.length > 1">
                                    <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 pointer-events-none">
                                        <template x-for="(img, index) in images" :key="index">
                                            <button @click.stop="currentIndex = index"
                                                    class="w-1.5 h-1.5 rounded-full transition-all duration-300 pointer-events-auto"
                                                    :class="currentIndex === index ? 'bg-[#0078C8] scale-125' : 'bg-gray-300'"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <!-- Product Identification Context -->
                            <div class="flex-1 py-2">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="p-2 bg-blue-50 rounded-lg">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                        </svg>
                                    </div>
                                    <h2 class="text-xl font-bold text-gray-900 tracking-tight" x-text="'Official Catalog Reference'"></h2>
                                </div>
                                <div class="space-y-4">
                                    <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Catalog Title</p>
                                        <p class="text-sm font-semibold text-gray-700 leading-relaxed">{{ $product->title }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Stock ID</p>
                                            <p class="text-sm font-bold text-[#0078C8]">{{ $product->unique_id }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Reference Count</p>
                                            <p class="text-sm font-bold text-gray-700" x-text="images.length + ' Images Linked'"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

                @include('pages.products.warehouse.products.partials.complete_product_record', ['product' => $product])

                <!-- Product Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Product ID</p>
                                <p class="text-lg font-bold text-gray-900 mt-1">{{ $product->unique_id }}</p>
                                <p class="text-sm text-gray-600 mt-1 line-clamp-1">{{ $product->title }}</p>
                            </div>
                            <div class="p-2 bg-[#0078C8]/10 rounded-lg">
                                <svg class="w-6 h-6 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-5-5A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</p>
                                <div class="mt-2">
                                    <span class="px-3 py-1.5 text-sm font-medium rounded-full
                                        {{ $product->status === 'Active' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                        {{ $product->status }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Current Status</p>
                            </div>
                            <div class="p-2 {{ $product->status === 'Active' ? 'bg-green-50' : 'bg-red-50' }} rounded-lg">
                                <svg class="w-6 h-6 {{ $product->status === 'Active' ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-5m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#0078C8] to-[#005a9c] flex items-center justify-center text-white text-xs font-bold ">
                                        {{ $product->assignedTo ? strtoupper(substr($product->assignedTo->full_name, 0, 2)) : 'NA' }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $product->assignedTo->full_name ?? 'Unassigned' }}</p>
                                        <p class="text-xs text-gray-500">Current Assignee</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-2 bg-[#0078C8]/10 rounded-lg">
                                <svg class="w-6 h-6 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Location</p>
                                <p class="text-lg font-bold text-gray-900 mt-1">{{ $product->warehouseLocation->name ?? 'N/A' }}</p>
                                @if($product->manual_status == 1)
                                <p class="text-xs text-gray-500 mt-1">Qty: <span class="font-semibold text-gray-700">{{ $product->count ?? 'N/A' }}</span></p>
                                <p class="text-xs text-gray-500 mt-1">Notes: <span class="font-semibold text-gray-700">{{ $product->problem ?? 'N/A' }}</span></p>
                                @else
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-500">{{ $product->disposition->name ?? 'No Disposition' }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-xs text-gray-500">{{ $product->workflowStage->name ?? 'No Stage' }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="p-2 bg-[#0078C8]/10 rounded-lg">
                                <svg class="w-6 h-6 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inspection Summary Cards -->
                @php
                    $visualInspection = $product->visualInspections->first();
                    $functionalTesting = $product->functionalTestings->first();
                    $cosmeticGradeNames = [
                        'C0'=>'Not Categorized','C1'=>'Damaged','C2'=>'Used Poor',
                        'C3'=>'Used Fair','C4'=>'Used Good','C5'=>'Used Very Good',
                        'C6'=>'Used Excellent','C7'=>'Certified Pre-Owned',
                        'C8'=>'Unused','C9'=>'New Open Box',
                    ];
                    $functionalGradeNames = [
                        'F1'=>'Collectible / Specialty','F2'=>'Verified Specialty',
                        'F3'=>'Key Functions Working','F4'=>'Hardware Functional',
                        'F5'=>'Refurbished','F6'=>'Like New',
                    ];
                @endphp

                @if($product->manual_status != 1)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <!-- Visual Inspection Summary -->
                    <div class="bg-gradient-to-br from-white to-blue-50/30 rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="p-2 bg-[#0078C8]/10 rounded-lg">
                                    <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900">Visual Inspection</h3>
                            </div>
                            @if($visualInspection)
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $visualInspection->grade ? 'bg-[#0078C8]/10 text-[#0078C8] border border-[#0078C8]/20' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $visualInspection->grade ? $visualInspection->grade . ' – ' . ($cosmeticGradeNames[$visualInspection->grade] ?? $visualInspection->grade) : 'Not Set' }}
                                </span>
                            @endif
                        </div>
                        @if($visualInspection)
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Checklist Items</p>
                                    <p class="font-medium text-gray-900">{{ count($visualInspection->checklist_items ?? []) }} points</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Last Inspection</p>
                                    <p class="font-medium text-gray-900">{{ $visualInspection->inspected_at ? $visualInspection->inspected_at->format('M d, Y') : 'Never' }}</p>
                                </div>
                                @if($visualInspection->inspection_notes)
                                    <div class="col-span-2 mt-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Notes</p>
                                        <p class="text-sm text-gray-700 line-clamp-2">{{ $visualInspection->inspection_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-gray-500 italic">No visual inspection records found</p>
                        @endif
                    </div>

                    <!-- Functional Testing Summary -->
                    <div class="bg-gradient-to-br from-white to-purple-50/30 rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="p-2 bg-purple-100 rounded-lg">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900">Functional Testing</h3>
                            </div>
                            @if($functionalTesting)
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $functionalTesting->grade ? 'bg-purple-100 text-purple-700 border border-purple-200' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $functionalTesting->grade ? $functionalTesting->grade . ' – ' . ($functionalGradeNames[$functionalTesting->grade] ?? $functionalTesting->grade) : 'Not Set' }}
                                </span>
                            @endif
                        </div>
                        @if($functionalTesting)
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Custom Tests</p>
                                    <p class="font-medium text-gray-900">{{ count($functionalTesting->custom_tests ?? []) }} tests</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Last Tested</p>
                                    <p class="font-medium text-gray-900">{{ $functionalTesting->tested_at ? $functionalTesting->tested_at->format('M d, Y') : 'Never' }}</p>
                                </div>
                                @if($functionalTesting->overall_results_summary)
                                    <div class="col-span-2 mt-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Summary</p>
                                        <p class="text-sm text-gray-700 line-clamp-2">{{ $functionalTesting->overall_results_summary }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-gray-500 italic">No functional testing records found</p>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Cosmetic Grade & Functional Grade -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Cosmetic Grade</h3>
                        @php $cg = $visualInspection->grade ?? null; @endphp
                        @if($cg)
                            <div class="flex items-center gap-3">
                                <span class="text-3xl font-bold text-[#0078C8]">{{ $cg }}</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $cosmeticGradeNames[$cg] ?? $cg }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Cosmetic condition rating</p>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 italic">No cosmetic grade assigned</p>
                        @endif
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Functional Grade</h3>
                        @php $fg = $functionalTesting->grade ?? null; @endphp
                        @if($fg)
                            <div class="flex items-center gap-3">
                                <span class="text-3xl font-bold text-purple-600">{{ $fg }}</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $functionalGradeNames[$fg] ?? $fg }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Functional condition rating</p>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 italic">No functional grade assigned</p>
                        @endif
                    </div>
                </div>

                <!-- Technical Specifications -->
                @if($product->specifications && count($product->specifications) > 0)
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Technical Specifications
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($product->specifications as $spec)
                                @php
                                    $specKey   = is_array($spec) ? ($spec['key']   ?? '') : $spec;
                                    $specValue = is_array($spec) ? ($spec['value'] ?? '') : '';
                                @endphp
                                @if($specKey)
                                <div class="flex justify-between items-start p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <span class="text-xs text-gray-500 font-medium capitalize">{{ str_replace('_', ' ', $specKey) }}</span>
                                    <span class="text-xs font-bold text-gray-900 ml-3 text-right">{{ $specValue ?: '—' }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Added Parts -->
                @if($product->parts && count($product->parts) > 0)
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 01-1-1V4a1 1 0 011-1h3a1 1 0 001-1v-1z"></path>
                            </svg>
                            Added Parts
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($product->parts as $part)
                            <div class="flex flex-col p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-sm font-bold text-gray-900">{{ $part->name }}</span>
                                    <span class="px-2 py-0.5 text-xs font-semibold bg-[#0078C8]/10 text-[#0078C8] rounded-full">Qty: {{ $part->pivot->quantity }}</span>
                                </div>
                                @if($part->sku)
                                    <span class="text-xs text-gray-500 mb-1">SKU: {{ $part->sku }}</span>
                                @endif
                                @if($part->pivot->notes)
                                    <p class="text-xs text-gray-600 mt-2 bg-white p-2 rounded border border-gray-100 italic">"{{ $part->pivot->notes }}"</p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- History Timeline -->
                <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">
                    <!-- Header with Filters -->
                    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Activity Timeline
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">Complete history of all changes made to this product</p>
                            </div>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'desc' ? 'asc' : 'desc', 'page' => 1]) }}"
                               class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg border border-gray-200 hover:border-[#0078C8]/40 hover:bg-blue-50/30 transition-colors cursor-pointer w-full sm:w-auto justify-center sm:justify-start">
                                @if($sort === 'desc')
                                    <svg class="w-4 h-4 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                                    </svg>
                                    <span class="text-sm text-gray-700 font-medium">Latest First</span>
                                @else
                                    <svg class="w-4 h-4 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <span class="text-sm text-gray-700 font-medium">Oldest First</span>
                                @endif
                            </a>
                        </div>
                    </div>

                    <!-- Timeline Content -->
                    <div class="px-6 py-4">
                        @forelse($histories as $history)
                            @if($product->manual_status == 1 && (str_contains($history->action, 'visual') || str_contains($history->action, 'functional')))
                                @continue
                            @endif
                            <div class="relative flex gap-4 {{ $loop->last ? 'pb-2' : 'pb-7' }} hover:bg-gray-50/40 rounded-lg px-2 -mx-2 transition-colors group">

                                <!-- Icon column + connecting line -->
                                <div class="flex flex-col items-center flex-shrink-0 pt-0.5">
                                    <div class="w-9 h-9 rounded-full {{ getActionColorClass($history->action) }} flex items-center justify-center shadow-sm z-10 relative ring-2 ring-white">
                                        {!! getActionIcon($history->action) !!}
                                    </div>
                                    @if(!$loop->last)
                                        <div class="w-0.5 flex-1 bg-gradient-to-b from-gray-300 to-gray-100 mt-2 min-h-[1.5rem]"></div>
                                    @endif
                                </div>

                                    <!-- Main Content -->
                                    <div class="flex-1 min-w-0 pt-0.5">

                                        <!-- Row 1: Title + Badge + Timestamp -->
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 mb-2">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-sm font-semibold text-gray-900">
                                                    {{ formatActionTitle($history->action, $history->field) }}
                                                </span>
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ getActionBadgeColor($history->action) }}">
                                                    {{ formatActionType($history->action) }}
                                                </span>
                                            </div>
                                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                                {{ $history->created_at->format('d M Y, g:i A') }}
                                                &middot; {{ $history->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        <!-- Row 2: Who + Field + Event# -->
                                        <div class="flex items-center flex-wrap gap-x-2 gap-y-1 mb-3 text-xs text-gray-500">
                                            <div class="flex items-center gap-1.5">
                                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#0078C8] to-[#005a9c] flex items-center justify-center text-white font-bold" style="font-size:10px">
                                                    {{ $history->changedBy ? strtoupper(substr($history->changedBy->username, 0, 2)) : 'SY' }}
                                                </div>
                                                <span class="font-medium text-gray-700">{{ $history->changedBy->username ?? 'System' }}</span>
                                            </div>
                                            @if($history->field)
                                                <span class="text-gray-300">&middot;</span>
                                                <span class="px-1.5 py-0.5 bg-gray-100 rounded font-mono text-gray-600" style="font-size:11px">
                                                    {{ formatFieldName($history->field) }}
                                                </span>
                                            @endif
                                            <span class="text-gray-300">&middot;</span>
                                            <span>Event #{{ $history->id }}</span>
                                        </div>

                                        <!-- Notes -->
                                        @if($history->notes)
                                            <div class="mb-3 pl-3 border-l-4 border-[#0078C8] bg-blue-50/60 rounded-r-lg py-2 pr-3">
                                                <p class="text-sm text-gray-700">{{ $history->notes }}</p>
                                            </div>
                                        @endif

                                        <!-- Change Description (human-readable) -->
                                        @if(!str_contains($history->action, 'visual_checklist') &&
                                            !str_contains($history->action, 'functional_test') &&
                                            !str_contains($history->action, 'grade_updated') &&
                                            ($history->old_value || $history->new_value))
                                            @php
                                                $oldDecoded = $history->old_value ? json_decode($history->old_value, true) : null;
                                                $newDecoded = $history->new_value ? json_decode($history->new_value, true) : null;
                                                $isOldJson  = is_array($oldDecoded);
                                                $isNewJson  = is_array($newDecoded);
                                                $sentence   = buildChangeText(
                                                    $history->action,
                                                    $history->field,
                                                    $history->old_value,
                                                    $history->new_value,
                                                    $oldDecoded,
                                                    $newDecoded,
                                                    $isOldJson,
                                                    $isNewJson,
                                                    $dispositionMap ?? [],
                                                    $workflowStageMap ?? [],
                                                    $locationMap ?? []
                                                );
                                            @endphp

                                            @if($sentence)
                                                <div class="mt-2 text-sm text-gray-600 leading-relaxed">
                                                    {!! $sentence !!}
                                                </div>
                                            @elseif($isNewJson || $isOldJson)
                                                {{-- Complex JSON: render as readable pill tags --}}
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @foreach(($isNewJson ? $newDecoded : $oldDecoded) as $key => $val)
                                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-50 border border-gray-200 rounded-lg text-xs">
                                                            <span class="text-gray-400 font-medium">{{ formatFieldLabel($key) }}:</span>
                                                            <span class="text-gray-800 font-semibold">{{ is_array($val) ? implode(', ', array_map('strval', $val)) : $val }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif

                                        <!-- Grade Change -->
                                        @if(str_contains($history->action, 'grade_updated'))
                                            @php
                                                $isVisualGrade = str_contains($history->action, 'visual');
                                            @endphp
                                            <div class="mt-2 flex items-center gap-3 text-sm text-gray-600">
                                                @if($history->old_value)
                                                    Grade changed from
                                                    <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-500 line-through">{{ $history->old_value }}</span>
                                                @else
                                                    Grade set to
                                                @endif
                                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                                </svg>
                                                @if($history->new_value)
                                                    <span class="px-2.5 py-0.5 rounded-md text-sm font-bold {{ $isVisualGrade ? 'bg-[#0078C8]/10 text-[#0078C8]' : 'bg-purple-100 text-purple-700' }}">{{ $history->new_value }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Checklist Items -->
                                        @if(str_contains($history->action, 'visual_checklist') && $history->new_value && is_array(json_decode($history->new_value, true)))
                                            @php $checklistItems = json_decode($history->new_value, true); @endphp
                                            <div class="mt-2 text-sm text-gray-600 mb-2">
                                                {{ count($checklistItems) }} checklist {{ Str::plural('point', count($checklistItems)) }}
                                                {{ str_contains($history->action, 'removed') ? 'removed' : 'added' }}:
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($checklistItems as $item)
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-[#0078C8]/5 border border-[#0078C8]/20 rounded-lg text-xs text-gray-700">
                                                        <svg class="w-3 h-3 {{ str_contains($history->action, 'removed') ? 'text-red-400' : 'text-emerald-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                                            @if(str_contains($history->action, 'removed'))
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            @else
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            @endif
                                                        </svg>
                                                        {{ $item }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <!-- Functional Test Details -->
                                        @if(str_contains($history->action, 'functional_test') && $history->new_value && is_array(json_decode($history->new_value, true)))
                                            @php $testData = json_decode($history->new_value, true); @endphp
                                            @php $passed = ($testData['result'] ?? '') === 'pass'; @endphp
                                            <div class="mt-2 text-sm text-gray-600">
                                                Test <span class="font-medium text-gray-800">{{ $testData['name'] ?? 'Unknown' }}</span>
                                                @if(isset($testData['result']))
                                                    marked as
                                                    <span class="px-1.5 py-0.5 rounded-md text-xs font-semibold {{ $passed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                                        {{ strtoupper($testData['result']) }}
                                                    </span>
                                                @endif
                                                @if(isset($testData['notes']))
                                                    <span class="block mt-1 text-xs text-gray-500">{{ $testData['notes'] }}</span>
                                                @endif
                                            </div>
                                        @endif

                                    </div>
                            </div>
                        @empty
                            <div class="px-6 py-16 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900 mb-1">No History Found</h3>
                                <p class="text-sm text-gray-500 mb-5">Changes to this product will appear here as they happen.</p>
                                @if(!$product->sold_status)
                                <a href="{{ route('warehouse.products.edit', $product->id) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#005a9c] transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Product Now
                                </a>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if($histories->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                                <p class="text-sm text-gray-600">
                                    Showing <span class="font-medium">{{ $histories->firstItem() }}</span>
                                    to <span class="font-medium">{{ $histories->lastItem() }}</span>
                                    of <span class="font-medium">{{ $histories->total() }}</span> events
                                </p>
                                <div class="flex gap-2">
                                    {{ $histories->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productGallery', (query) => ({
                images: [],
                currentIndex: 0,
                loading: true,

                init() {
                    this.fetchImages();
                },

                async fetchImages() {
                    if (!query) {
                        this.loading = false;
                        return;
                    }

                    // 1. Try with raw title first (but trimmed)
                    let success = await this.doFetch(query.trim());

                    if (!success) {
                        // 2. Clean query: remove parentheticals, special chars, etc.
                        let cleanQuery = query.replace(/\(.*?\)/g, '')
                                              .replace(/[\[\]]/g, '')
                                              .replace(/[^\w\s-]/g, ' ')
                                              .replace(/\s+/g, ' ')
                                              .trim();

                        if (cleanQuery !== query.trim()) {
                            success = await this.doFetch(cleanQuery);
                        }
                    }

                    if (!success) {
                        // 3. Fallback: Take first 5 words
                        let words = query.trim().split(/\s+/);
                        if (words.length > 5) {
                            let shortQuery = words.slice(0, 5).join(' ');
                            await this.doFetch(shortQuery);
                        }
                    }

                    this.loading = false;
                },

                async doFetch(q) {
                    try {
                        const response = await fetch(`{{ route('warehouse.products.catalog-search') }}?q=${encodeURIComponent(q)}`);
                        const data = await response.json();
                        if (data.products && data.products.length > 0) {
                            this.images = data.products[0].images || [];
                            return this.images.length > 0;
                        }
                    } catch (err) {
                        console.error('Gallery Fetch Error:', err);
                    }
                    return false;
                },

                next() {
                    this.currentIndex = (this.currentIndex + 1) % this.images.length;
                },

                prev() {
                    this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
                }
            }))
        });

        document.addEventListener('DOMContentLoaded', function() {
            const timeline = document.querySelector('.max-h-96');
            if (timeline) {
                timeline.style.scrollBehavior = 'smooth';
            }
        });
    </script>
@endsection

@php
    // Helper function to get action color class
    function getActionColorClass($action) {
        $colors = [
            'created' => 'bg-gradient-to-br from-blue-500 to-blue-600',
            'updated' => 'bg-gradient-to-br from-yellow-500 to-yellow-600',
            'deleted' => 'bg-gradient-to-br from-red-500 to-red-600',
            'assigned' => 'bg-gradient-to-br from-purple-500 to-purple-600',
            'location' => 'bg-gradient-to-br from-indigo-500 to-indigo-600',
            'part_added' => 'bg-gradient-to-br from-green-500 to-green-600',
            'part_extracted' => 'bg-gradient-to-br from-orange-500 to-orange-600',
            'status' => 'bg-gradient-to-br from-teal-500 to-teal-600',
            'bulk' => 'bg-gradient-to-br from-pink-500 to-pink-600',
            'visual' => 'bg-gradient-to-br from-[#0078C8] to-[#005a9c]',
            'functional' => 'bg-gradient-to-br from-purple-500 to-purple-700',
            'grade' => 'bg-gradient-to-br from-amber-500 to-amber-600',
            'checklist' => 'bg-gradient-to-br from-emerald-500 to-emerald-600',
        ];

        foreach ($colors as $key => $color) {
            if (str_contains(strtolower($action), $key)) {
                return $color;
            }
        }

        return 'bg-gradient-to-br from-gray-500 to-gray-600';
    }

    // Helper function to get action icon
    function getActionIcon($action) {
        $icons = [
            'created' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>',
            'updated' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>',
            'deleted' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
            'assigned' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
            'location' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>',
            'part_added' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-5 5h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>',
            'part_extracted' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4M4 12l4 4m-4-4l4-4"></path></svg>',
            'status' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-5m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'bulk' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>',
            'visual' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
            'functional' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>',
            'grade' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>',
        ];

        foreach ($icons as $key => $icon) {
            if (str_contains(strtolower($action), $key)) {
                return $icon;
            }
        }

        return '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    }

    // Helper function to get badge color
    function getActionBadgeColor($action) {
        $colors = [
            'created' => 'bg-blue-100 text-blue-800 border border-blue-200',
            'updated' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            'deleted' => 'bg-red-100 text-red-800 border border-red-200',
            'assigned' => 'bg-purple-100 text-purple-800 border border-purple-200',
            'location' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
            'part_added' => 'bg-green-100 text-green-800 border border-green-200',
            'part_extracted' => 'bg-orange-100 text-orange-800 border border-orange-200',
            'status' => 'bg-teal-100 text-teal-800 border border-teal-200',
            'bulk' => 'bg-pink-100 text-pink-800 border border-pink-200',
            'visual' => 'bg-[#0078C8]/10 text-[#0078C8] border border-[#0078C8]/30',
            'functional' => 'bg-purple-100 text-purple-800 border border-purple-200',
            'grade' => 'bg-amber-100 text-amber-800 border border-amber-200',
            'checklist' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        ];

        foreach ($colors as $key => $color) {
            if (str_contains(strtolower($action), $key)) {
                return $color;
            }
        }

        return 'bg-gray-100 text-gray-800 border border-gray-200';
    }

    // Helper function to format action title
    function formatActionTitle($action, $field = null) {
        if (str_contains($action, 'visual_inspection_created')) {
            return 'Visual Inspection Added';
        }
        if (str_contains($action, 'visual_grade_updated')) {
            return 'Visual Grade Updated';
        }
        if (str_contains($action, 'visual_notes_updated')) {
            return 'Visual Notes Updated';
        }
        if (str_contains($action, 'visual_checklist_items_added')) {
            return 'Inspection Points Added';
        }
        if (str_contains($action, 'visual_checklist_items_removed')) {
            return 'Inspection Points Removed';
        }
        if (str_contains($action, 'visual_checklist_status_updated')) {
            return 'Checklist Status Updated';
        }
        if (str_contains($action, 'functional_testing_created')) {
            return 'Functional Testing Added';
        }
        if (str_contains($action, 'functional_grade_updated')) {
            return 'Functional Grade Updated';
        }
        if (str_contains($action, 'functional_summary_updated')) {
            return 'Test Summary Updated';
        }
        if (str_contains($action, 'functional_test_added')) {
            return 'New Test Added';
        }
        if (str_contains($action, 'functional_test_removed')) {
            return 'Test Removed';
        }
        if (str_contains($action, 'functional_test_updated')) {
            return 'Test Result Updated';
        }

        $formatted = str_replace('_', ' ', $action);
        $formatted = ucwords($formatted);

        $replacements = [
            'Created' => 'Product Created',
            'Updated' => 'Product Updated',
            'Deleted' => 'Product Deleted',
            'Assigned To Technician' => 'Technician Assignment',
            'Location Changed' => 'Location Updated',
            'Part Added' => 'Part Added to Product',
            'Part Extracted' => 'Part Removed from Product',
            'Status Changed' => 'Status Updated',
            'Bulk Update' => 'Bulk Update Applied',
        ];

        foreach ($replacements as $search => $replace) {
            if (str_contains($formatted, $search)) {
                return $replace;
            }
        }

        return $formatted;
    }

    // Helper function to format action type
    function formatActionType($action) {
        if (str_contains(strtolower($action), 'visual')) return 'Visual';
        if (str_contains(strtolower($action), 'functional')) return 'Functional';
        if (str_contains(strtolower($action), 'created')) return 'Creation';
        if (str_contains(strtolower($action), 'updated')) return 'Update';
        if (str_contains(strtolower($action), 'deleted')) return 'Deletion';
        if (str_contains(strtolower($action), 'assigned')) return 'Assignment';
        if (str_contains(strtolower($action), 'location')) return 'Location';
        if (str_contains(strtolower($action), 'part_added')) return 'Addition';
        if (str_contains(strtolower($action), 'part_extracted')) return 'Removal';
        if (str_contains(strtolower($action), 'status')) return 'Status';
        if (str_contains(strtolower($action), 'bulk')) return 'Bulk';
        if (str_contains(strtolower($action), 'grade')) return 'Grade';
        if (str_contains(strtolower($action), 'checklist')) return 'Checklist';

        return 'Event';
    }

    // Helper function to format field name
    function formatFieldName($field) {
        $formatted = str_replace('_', ' ', $field);
        $formatted = ucwords($formatted);

        $replacements = [
            'Id' => 'ID',
            'Assigned To' => 'Assigned Technician',
            'Work Flow Stage Id' => 'Workflow Stage',
            'Category Id' => 'Category',
            'Disposition Id' => 'Disposition',
            'Product Details' => 'Product Details',
            'Visual' => 'Visual Inspection',
            'Functional' => 'Functional Testing',
            'Grade' => 'Grade',
            'Checklist' => 'Checklist',
        ];

        foreach ($replacements as $search => $replace) {
            $formatted = str_replace($search, $replace, $formatted);
        }

        return $formatted;
    }

    // Helper function to format JSON values
    function formatJsonValue($value, $field = null) {
        if (!is_array($value)) return $value;

        $formatted = [];
        foreach ($value as $key => $val) {
            if ($key === 'name') {
                $formatted[] = $val;
            } elseif ($key === 'result') {
                $formatted[] = 'Result: ' . strtoupper($val);
            } elseif ($key === 'notes' && $val) {
                $formatted[] = 'Notes: ' . $val;
            } elseif (is_array($val)) {
                $formatted[] = ucfirst($key) . ': ' . json_encode($val);
            } else {
                $formatted[] = ucfirst($key) . ': ' . $val;
            }
        }

        return implode(' | ', $formatted);
    }

    // Helper function to convert snake_case keys to readable labels
    function formatFieldLabel($key) {
        $labels = [
            'assignment_id'          => 'Assignment',
            'technician'             => 'Technician',
            'task_type'              => 'Task Type',
            'remarks'                => 'Remarks',
            'workflow_stage'         => 'Workflow Stage',
            'work_flow_stage'        => 'Workflow Stage',
            'disposition'            => 'Disposition',
            'status'                 => 'Status',
            'grade'                  => 'Grade',
            'location'               => 'Location',
            'warehouse_location'     => 'Warehouse Location',
            'name'                   => 'Name',
            'result'                 => 'Result',
            'notes'                  => 'Notes',
            'summary'                => 'Summary',
            'overall_results_summary'=> 'Overall Summary',
            'inspected_by'           => 'Inspected By',
            'inspected_at'           => 'Inspected At',
            'tested_by'              => 'Tested By',
            'tested_at'              => 'Tested At',
            'checklist_items'        => 'Checklist Items',
            'custom_tests'           => 'Custom Tests',
            'inspection_notes'       => 'Inspection Notes',
            'category'               => 'Category',
            'assigned_to'            => 'Assigned To',
            'title'                  => 'Title',
            'description'            => 'Description',
        ];

        return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    // Build a human-readable sentence describing what changed
    function buildChangeText($action, $field, $old, $new, $oldDec, $newDec, $isOldJson, $isNewJson, $dispositionMap = [], $workflowStageMap = [], $locationMap = []) {
        $esc    = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $chip   = fn($val, $cls = 'bg-blue-50 text-blue-800 border border-blue-200')
                  => "<span class='px-1.5 py-0.5 rounded-md text-xs font-semibold {$cls}'>" . $esc($val) . "</span>";
        $oldChip = fn($val) => "<span class='px-1.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-500 line-through'>" . $esc($val) . "</span>";
        $newChip = fn($val) => "<span class='px-1.5 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200'>" . $esc($val) . "</span>";

        $resolveDisposition = fn($id) => $dispositionMap[$id]   ?? $id;
        $resolveStage       = fn($id) => $workflowStageMap[$id] ?? $id;
        $resolveLocation    = fn($id) => $locationMap[$id]      ?? $id;

        // --- Bulk updates ---
        if (str_contains($action, 'bulk')) {
            if (str_contains($field ?? $action, 'specification')) {
                $specificationChips = [];
                if (is_array($newDec)) {
                    foreach (array_slice($newDec, 0, 5) as $specification) {
                        if (is_array($specification) && isset($specification['key'], $specification['value'])) {
                            $specificationChips[] = $chip($specification['key'] . ': ' . $specification['value']);
                        }
                    }
                }
                $moreCount = is_array($newDec) ? max(count($newDec) - 5, 0) : 0;
                $moreText = $moreCount > 0 ? " and {$moreCount} more" : '';

                return 'Bulk specifications updated'.($specificationChips ? ' — '.implode(' ', $specificationChips).$moreText : '');
            }
            if (str_contains($field ?? $action, 'disposition')) {
                return "Bulk disposition set to " . $newChip($resolveDisposition($new));
            }
            if (str_contains($field ?? $action, 'work_flow') || str_contains($field ?? $action, 'workflow')) {
                return "Bulk workflow stage set to " . $newChip($resolveStage($new));
            }
            if (str_contains($field ?? $action, 'location')) {
                return "Bulk location set to " . $newChip($resolveLocation($new));
            }
            if ($new && !$isNewJson) {
                $label = $field ? formatFieldLabel($field) : 'Value';
                return "$label updated to " . $newChip($new);
            }
            return null;
        }

        // --- Technician assignment ---
        if ((str_contains($action, 'assigned') || str_contains($action, 'assign')) && $isNewJson) {
            $tech     = $newDec['technician'] ?? null;
            $taskType = $newDec['task_type']  ?? null;
            if ($tech && $taskType) {
                return "<span class='font-medium text-gray-700'>" . $esc($taskType) . "</span> task assigned to technician " . $chip($tech, 'bg-purple-50 text-purple-800 border border-purple-200');
            }
            if ($tech) {
                return "Assigned to technician " . $chip($tech, 'bg-purple-50 text-purple-800 border border-purple-200');
            }
        }

        // --- Product created ---
        if (str_contains($action, 'created') && !str_contains($action, 'visual') && !str_contains($action, 'functional') && $isNewJson) {
            $parts = [];
            if (isset($newDec['title']))         $parts[] = $chip($newDec['title'], 'bg-gray-100 text-gray-700 border border-gray-200');
            if (isset($newDec['unique_id']))      $parts[] = "ID " . $chip($newDec['unique_id'], 'bg-[#0078C8]/10 text-[#0078C8] border border-[#0078C8]/20');
            if (isset($newDec['status']))         $parts[] = "status " . $newChip($newDec['status']);
            if (isset($newDec['category_name'])) $parts[] = "category " . $chip($newDec['category_name'], 'bg-gray-100 text-gray-700 border border-gray-200');
            return "Product created — " . implode(' &middot; ', $parts);
        }

        // --- Barcode generated ---
        if (str_contains($action, 'barcode') && $isNewJson) {
            $parts = [];
            if (isset($newDec['barcode_unique_id'])) $parts[] = "barcode " . $chip($newDec['barcode_unique_id'], 'bg-[#0078C8]/10 text-[#0078C8] border border-[#0078C8]/20');
            if (isset($newDec['barcode_type']))       $parts[] = "type " . $chip($newDec['barcode_type']);
            if (isset($newDec['sequence_number']))    $parts[] = "sequence #<span class='font-semibold'>" . $esc($newDec['sequence_number']) . "</span>";
            if (!empty($parts)) return implode(', ', $parts) . " generated and attached to product";
            return null;
        }

        // --- Simple (non-JSON) value changes ---
        if (!$isOldJson && !$isNewJson && ($old !== null || $new !== null)) {
            // Disposition
            if (str_contains($field ?? '', 'disposition') || str_contains($action, 'disposition')) {
                $oldName = $old ? $resolveDisposition($old) : null;
                $newName = $new ? $resolveDisposition($new) : null;
                if ($oldName && $newName) return "Disposition changed from " . $oldChip($oldName) . " to " . $newChip($newName);
                if ($newName)             return "Disposition set to " . $newChip($newName);
                if ($oldName)             return "Disposition removed (was " . $oldChip($oldName) . ")";
            }
            // Workflow stage
            if (str_contains($field ?? '', 'work_flow') || str_contains($field ?? '', 'workflow') || str_contains($action, 'workflow')) {
                $oldName = $old ? $resolveStage($old) : null;
                $newName = $new ? $resolveStage($new) : null;
                if ($oldName && $newName) return "Workflow stage changed from " . $oldChip($oldName) . " to " . $newChip($newName);
                if ($newName)             return "Workflow stage set to " . $newChip($newName);
                if ($oldName)             return "Workflow stage removed (was " . $oldChip($oldName) . ")";
            }
            // Location
            if (str_contains($field ?? '', 'location') || str_contains($action, 'location')) {
                $oldName = $old ? $resolveLocation($old) : null;
                $newName = $new ? $resolveLocation($new) : null;
                if ($oldName && $newName) return "Location changed from " . $oldChip($oldName) . " to " . $newChip($newName);
                if ($newName)             return "Moved to location " . $newChip($newName);
                if ($oldName)             return "Location removed (was " . $oldChip($oldName) . ")";
            }
            // Generic
            $label = $field ? "<span class='font-medium text-gray-700'>" . formatFieldLabel($field) . "</span> " : "";
            if ($old && $new) return $label . "changed from " . $oldChip($old) . " to " . $newChip($new);
            if ($new)         return $label . "set to " . $newChip($new);
            if ($old)         return $label . "removed (was " . $oldChip($old) . ")";
        }

        return null; // Fall back to pill-tag display in the template
    }
@endphp

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects for timeline items
        const timelineItems = document.querySelectorAll('.hover\\:bg-blue-50\\/20');
        timelineItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.classList.add('');
            });
            item.addEventListener('mouseleave', function() {
                this.classList.remove('');
            });
        });
    });

    @if (session('success'))
        console.log('Success: {{ session('success') }}');
    @endif

    @if (session('error'))
        console.error('Error: {{ session('error') }}');
    @endif
</script>
@endpush
