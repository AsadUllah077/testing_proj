@extends('layouts.auth')

@section('title', 'Product Details - ' . $product->unique_id)

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header with Product Info - Using brand color -->
                <div class="bg-gradient-to-r from-[#0078C8] to-[#005a9c] rounded-lg shadow-lg mb-6">
                    <div class="px-6 py-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <h1 class="text-white text-2xl font-bold">{{ $product->title }}</h1>
                                    <span class="px-3 py-1 bg-white/20 text-white text-xs rounded-full backdrop-blur-sm border border-white/30">
                                        {{ $product->unique_id }}
                                    </span>
                                </div>
                                <p class="text-blue-100 text-sm">Complete product details and lifecycle information</p>
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

                @include('pages.products.warehouse.products.partials.complete_product_record', ['product' => $product])

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content - Left Side (2 columns) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Product Summary Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                            @if($product->manual_status == 1)
                            <div class="bg-white rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Product Count</p>
                                        <p class="text-lg font-bold text-gray-900 mt-1">{{ number_format($product->count ?? 0) }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Bulk Quantity</p>
                                    </div>
                                    <div class="p-2 bg-blue-50 rounded-lg">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($product->manual_status != 1)
                            <div class="bg-white rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Disposition</p>
                                        <p class="text-lg font-bold text-gray-900 mt-1">{{ $product->disposition->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Current Disposition</p>
                                    </div>
                                    <div class="p-2 bg-purple-50 rounded-lg">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-5-5A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="bg-white rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Location</p>
                                        <p class="text-lg font-bold text-gray-900 mt-1">{{ $product->warehouseLocation->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Current Location</p>
                                    </div>
                                    <div class="p-2 bg-[#0078C8]/10 rounded-lg">
                                        <svg class="w-6 h-6 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            @if($product->manual_status != 1)
                            <div class="bg-white rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Workflow Stage</p>
                                        <p class="text-lg font-bold text-gray-900 mt-1">{{ $product->workflowStage->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Current Stage</p>
                                    </div>
                                    <div class="p-2 bg-amber-50 rounded-lg">
                                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            @endif
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @if($product->manual_status != 1)
                            <!-- Visual Inspection Summary -->
                            <div class="bg-gradient-to-br from-white to-blue-50/30 rounded-xl border border-gray-200 p-5 hover:shadow-md transition-all hover:border-[#0078C8]/30 cursor-pointer"
                                 onclick="openVisualModal()">
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
                            <div class="bg-gradient-to-br from-white to-purple-50/30 rounded-xl border border-gray-200 p-5  hover:shadow-md transition-all hover:border-[#0078C8]/30 cursor-pointer"
                                 onclick="openFunctionalModal()">
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
                            @endif

                            <!-- General Task Summary -->
                            <div class="bg-gradient-to-br from-white to-green-50/30 rounded-xl border border-gray-200 p-5 hover:shadow-md transition-all hover:border-green-400/30 cursor-pointer"
                                 onclick="openGeneralTaskModal()">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 bg-green-100 rounded-lg">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-base font-semibold text-gray-900">General Task</h3>
                                    </div>
                                    @if($generalTask)
                                        @php $gtGrade = $generalTask->additional_data['grade'] ?? null; @endphp
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $gtGrade ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-600' }}">
                                            Grade: {{ $gtGrade ?? 'Not Set' }}
                                        </span>
                                    @endif
                                </div>
                                @if($generalTask)
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Checklist Items</p>
                                            <p class="font-medium text-gray-900">{{ count($generalTask->additional_data['checklist_items'] ?? []) }} points</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Completed</p>
                                            <p class="font-medium text-gray-900">{{ $generalTask->completed_at ? $generalTask->completed_at->format('M d, Y') : 'N/A' }}</p>
                                        </div>
                                        @if($generalTask->technician_notes)
                                            <div class="col-span-2 mt-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                                                <p class="text-xs text-gray-500 mb-1">Notes</p>
                                                <p class="text-sm text-gray-700 line-clamp-2">{{ $generalTask->technician_notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 italic">No general task records found</p>
                                @endif
                            </div>
                        </div>

                        <!-- Cosmetic Grade & Functional Grade -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <!-- Cosmetic Grade -->
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
                            <!-- Functional Grade -->
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

                        <!-- Audit Trail Timeline -->
                        <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Activity Timeline
                                        </h3>
                                        <p class="text-sm text-gray-600 mt-1">Recent product lifecycle events</p>
                                    </div>
                                    <a href="{{ route('warehouse.products.history', $product->id) }}"
                                       class="px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#005a9c] transition-colors flex items-center gap-2 w-full sm:w-auto text-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        View Full History
                                    </a>
                                </div>
                            </div>

                            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                                @forelse($product->histories->take(5) as $history)
                                    <div class="hover:bg-blue-50/20 transition-colors">
                                        <div class="px-6 py-4">
                                            <div class="flex items-start gap-4">
                                                <div class="flex-shrink-0">
                                                    <div class="w-10 h-10 rounded-full {{ getActionColorClass($history->action) }} flex items-center justify-center ">
                                                        {!! getActionIcon($history->action) !!}
                                                    </div>
                                                </div>

                                                <div class="flex-1 min-w-0">
                                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 mb-2">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <h4 class="text-sm font-semibold text-gray-900">
                                                                {{ formatActionTitle($history->action, $history->field) }}
                                                            </h4>
                                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ getActionBadgeColor($history->action) }}">
                                                                {{ formatActionType($history->action) }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                                            <span>{{ $history->created_at->format('M d, Y') }}</span>
                                                            <span>•</span>
                                                            <span>{{ $history->created_at->format('g:i A') }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-2 text-xs">
                                                        <div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#0078C8] to-[#005a9c] flex items-center justify-center text-white text-xs font-bold">
                                                            {{ $history->changedBy ? strtoupper(substr($history->changedBy->username, 0, 1)) : 'S' }}
                                                        </div>
                                                        <span class="text-gray-700 font-medium">{{ $history->changedBy->username ?? 'System' }}</span>
                                                        <span class="text-gray-400">({{ $history->created_at->diffForHumans() }})</span>
                                                    </div>

                                                    @if($history->notes)
                                                        <div class="mt-2 p-2 bg-blue-50/50 rounded-lg border border-blue-100">
                                                            <p class="text-xs text-gray-700">{{ Str::limit($history->notes, 100) }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-6 py-12 text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-semibold text-gray-900 mb-1">No History Found</h3>
                                        <p class="text-xs text-gray-500">Activity will appear here as changes are made</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar - Right Side (1 column) -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Product Gallery Card -->
                        <div x-data="productGallery('{{ $product->title }}')" x-cloak class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Product Media
                                </h3>
                                <template x-if="images.length > 0">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" x-text="(currentIndex + 1) + ' / ' + images.length"></span>
                                </template>
                            </div>

                            <div class="relative bg-gray-50 group min-h-[300px] flex items-center justify-center">
                                <!-- Loading State -->
                                <template x-if="loading">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-8 h-8 border-2 border-[#0078C8] border-t-transparent rounded-full animate-spin"></div>
                                        <p class="text-xs text-gray-400 font-medium tracking-wide">Syncing from Catalog...</p>
                                    </div>
                                </template>

                                <!-- Error/Empty State -->
                                <template x-if="!loading && images.length === 0">
                                    <div class="text-center p-8">
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <p class="text-xs text-gray-400 font-medium">No official images available</p>
                                    </div>
                                </template>

                                <!-- Main Image Gallery -->
                                <template x-if="!loading && images.length > 0">
                                    <div class="w-full h-full relative aspect-square overflow-hidden bg-white">
                                        <!-- Main Image -->
                                        <img :src="images[currentIndex]"
                                             class="w-full h-full object-contain p-4 transition-all duration-500 ease-out transform scale-100 group-hover:scale-105"
                                             :alt="'Gallery image ' + (currentIndex + 1)">

                                        <!-- Navigation Overlays -->
                                        <template x-if="images.length > 1">
                                            <div class="absolute inset-0 flex items-center justify-between px-2 opacity-0 group-hover:opacity-100 transition-all duration-300 pointer-events-none">
                                                <button @click.stop="prev()"
                                                        class="w-10 h-10 bg-white/90 backdrop-blur shadow-xl rounded-full flex items-center justify-center text-gray-800 hover:bg-[#0078C8] hover:text-white transition-all transform -translate-x-4 group-hover:translate-x-0 pointer-events-auto border border-gray-100">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                                                </button>
                                                <button @click.stop="next()"
                                                        class="w-10 h-10 bg-white/90 backdrop-blur shadow-xl rounded-full flex items-center justify-center text-gray-800 hover:bg-[#0078C8] hover:text-white transition-all transform translate-x-4 group-hover:translate-x-0 pointer-events-auto border border-gray-100">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                                </button>
                                            </div>
                                        </template>

                                        <!-- Pagination Dots -->
                                        <template x-if="images.length > 1">
                                            <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-1.5 pointer-events-none">
                                                <template x-for="(img, index) in images" :key="index">
                                                    <button @click.stop="currentIndex = index"
                                                            class="w-1.5 h-1.5 rounded-full transition-all duration-300 pointer-events-auto"
                                                            :class="currentIndex === index ? 'bg-[#0078C8] scale-125' : 'bg-gray-300 hover:bg-gray-400'"></button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Product Information Card -->
                        <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Product Information
                                </h3>
                            </div>
                            <div class="p-5 space-y-3">
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Product ID</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $product->unique_id }}</span>
                                </div>
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">DPN/SN Number</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $product->dpn_sn_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Category</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-[#0078C8]/10 text-[#0078C8]">
                                        {{ $product->category->name ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Assigned To</span>
                                    <div class="flex items-center gap-2">
                                        @if($product->assignedTo)
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-[#0078C8] to-[#005a9c] flex items-center justify-center text-white text-xs font-bold">
                                                {{ strtoupper(substr($product->assignedTo->full_name, 0, 1)) }}
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900">{{ $product->assignedTo->full_name }}</span>
                                        @else
                                            <span class="text-sm text-gray-400 italic">Unassigned</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Created By</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $product->createdBy->username ?? 'System' }}</span>
                                </div>
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Created At</span>
                                    <span class="text-sm text-gray-600">{{ $product->created_at ? $product->created_at->format('M d, Y') : 'N/A' }}</span>
                                </div>
                                @if($product->manual_status == 1)
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Quantity</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $product->count ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Notes</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $product->problem ?? 'N/A' }}</span>
                                </div>
                                @endif



                                <div class="flex justify-between items-start py-2">
                                    <span class="text-sm text-gray-500">Last Updated</span>
                                    <span class="text-sm text-gray-600">{{ $product->updated_at ? $product->updated_at->format('M d, Y') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Technical Specifications Card -->
                        @if($product->specifications && count($product->specifications) > 0)
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Technical Specifications
                                </h3>
                            </div>
                            <div class="p-5 space-y-2">
                                @foreach($product->specifications as $spec)
                                    @php
                                        $specKey   = is_array($spec) ? ($spec['key']   ?? '') : $spec;
                                        $specValue = is_array($spec) ? ($spec['value'] ?? '') : '';
                                    @endphp
                                    @if($specKey)
                                    <div class="flex justify-between items-start py-2 border-b border-gray-100 last:border-b-0">
                                        <span class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $specKey) }}</span>
                                        <span class="text-sm font-semibold text-gray-900 text-right ml-4">{{ $specValue ?: '—' }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Added Parts Card -->
                        @if($product->parts && count($product->parts) > 0)
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
                            <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 01-1-1V4a1 1 0 011-1h3a1 1 0 001-1v-1z"></path>
                                    </svg>
                                    Added Parts
                                </h3>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-1 gap-3">
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

                        <!-- Barcode Card --

                        @if($product->barcode)
                        <div class="bg-white rounded-xl border border-gray-200  overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                    Product Barcode
                                </h3>
                            </div>
                            <div class="p-5">
                                <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
                                    <div class="barcode-container mb-3">
                                        {!! $product->barcode->barcode_html ?? '' !!}
                                    </div>
                                    <div class="flex justify-center">
                                        <span class="px-3 py-1.5 text-xs font-medium rounded-full bg-gray-800 text-white">
                                            {{ $product->barcode->barcode_number ?? $product->unique_id }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Quick Actions Card -->
                        <div class="bg-gradient-to-br from-[#0078C8] to-[#005a9c] rounded-xl  overflow-hidden">
                            <div class="p-5">
                                <h3 class="text-base font-bold text-white flex items-center gap-2 mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Quick Actions
                                </h3>
                                <div class="space-y-2">
                                    @if(!$product->sold_status)
                                    <a href="{{ route('warehouse.products.edit', $product->id) }}"
                                       class="block w-full px-4 py-2.5 bg-white text-[#0078C8] rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors text-center">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Edit Product
                                    </a>
                                    @endif
                                    <a href="{{ route('warehouse.products.history', $product->id) }}"
                                       class="block w-full px-4 py-2.5 bg-white/10 text-white rounded-lg text-sm font-medium hover:bg-white/20 transition-colors backdrop-blur-sm text-center border border-white/20">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Full History
                                    </a>
                                    <button onclick="window.print()"
                                            class="block w-full px-4 py-2.5 bg-white/10 text-white rounded-lg text-sm font-medium hover:bg-white/20 transition-colors backdrop-blur-sm text-center border border-white/20">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                        </svg>
                                        Print Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($product->manual_status != 1)
    <!-- Visual Inspection Modal -->
    <div id="visualInspectionModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" onclick="closeVisualModal(event)">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-[#0078C8]/5 to-white rounded-t-2xl flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-[#0078C8]/10 rounded-lg">
                        <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Visual Inspection</h3>
                        <p class="text-xs text-gray-500">All inspection points</p>
                    </div>
                </div>
                <button onclick="closeVisualModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                @if($visualInspection)
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Last Inspection</p>
                            <p class="text-sm font-medium text-gray-800">{{ $visualInspection->inspected_at ? $visualInspection->inspected_at->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        @if($visualInspection->grade)
                            <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-[#0078C8]/10 text-[#0078C8] border border-[#0078C8]/20">
                                Grade: {{ $visualInspection->grade }}
                            </span>
                        @endif
                    </div>

                    @if(!empty($visualInspection->checklist_items))
                        <div class="mb-5">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Inspection Points ({{ count($visualInspection->checklist_items) }})</p>
                            <div class="space-y-2">
                                @foreach($visualInspection->checklist_items as $item)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <div class="w-5 h-5 rounded-full bg-[#0078C8]/15 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <span class="text-sm text-gray-700 capitalize">{{ str_replace('_', ' ', $item) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic mb-5">No checklist items recorded</p>
                    @endif

                    @if($visualInspection->inspection_notes)
                        <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wider mb-2">Notes</p>
                            <p class="text-sm text-gray-700">{{ $visualInspection->inspection_notes }}</p>
                        </div>
                    @endif
                @else
                    <div class="text-center py-10">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">No visual inspection records found</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Functional Testing Modal -->
    <div id="functionalTestingModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" onclick="closeFunctionalModal(event)">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-white rounded-t-2xl flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Functional Testing</h3>
                        <p class="text-xs text-gray-500">All test results</p>
                    </div>
                </div>
                <button onclick="closeFunctionalModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                @if($functionalTesting)
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Last Tested</p>
                            <p class="text-sm font-medium text-gray-800">{{ $functionalTesting->tested_at ? $functionalTesting->tested_at->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        @if($functionalTesting->grade)
                            <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-purple-100 text-purple-700 border border-purple-200">
                                Grade: {{ $functionalTesting->grade }}
                            </span>
                        @endif
                    </div>

                    @if(!empty($functionalTesting->custom_tests))
                        <div class="mb-5">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Tests ({{ count($functionalTesting->custom_tests) }})</p>
                            <div class="space-y-2">
                                @foreach($functionalTesting->custom_tests as $testName => $testResult)
                                    @php
                                        $resultStr = is_array($testResult) ? implode(', ', array_filter($testResult, 'is_string')) : (string) $testResult;
                                        $isPass = strtolower($resultStr) === 'pass';
                                        $isFail = strtolower($resultStr) === 'fail';
                                        $label = is_string($testName) ? str_replace('_', ' ', $testName) : str_replace('_', ' ', $resultStr);
                                    @endphp
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <div class="w-5 h-5 rounded-full {{ $isPass ? 'bg-green-100' : ($isFail ? 'bg-red-100' : 'bg-gray-200') }} flex items-center justify-center flex-shrink-0">
                                                @if($isPass)
                                                    <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                @elseif($isFail)
                                                    <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                @else
                                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                                @endif
                                            </div>
                                            <span class="text-sm text-gray-700 capitalize">{{ $label }}</span>
                                        </div>
                                        @if(!is_array($testResult))
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $isPass ? 'bg-green-100 text-green-700' : ($isFail ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                                {{ ucfirst($resultStr) }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic mb-5">No test results recorded</p>
                    @endif

                    @if($functionalTesting->overall_results_summary)
                        <div class="p-4 bg-purple-50 rounded-xl border border-purple-100">
                            <p class="text-xs font-semibold text-purple-700 uppercase tracking-wider mb-2">Summary</p>
                            <p class="text-sm text-gray-700">{{ $functionalTesting->overall_results_summary }}</p>
                        </div>
                    @endif
                @else
                    <div class="text-center py-10">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">No functional testing records found</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- General Task Modal -->
    <div id="generalTaskModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" onclick="closeGeneralTaskModal(event)">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-green-50 to-white rounded-t-2xl flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">General Task</h3>
                        <p class="text-xs text-gray-500">Task checklist & results</p>
                    </div>
                </div>
                <button onclick="closeGeneralTaskModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                @if($generalTask)
                    @php
                        $gtGrade    = $generalTask->additional_data['grade'] ?? null;
                        $gtChecklist = $generalTask->additional_data['checklist_items'] ?? [];
                    @endphp
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Completed</p>
                            <p class="text-sm font-medium text-gray-800">{{ $generalTask->completed_at ? $generalTask->completed_at->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        @if($gtGrade)
                            <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-green-100 text-green-700 border border-green-200">
                                Grade: {{ $gtGrade }}
                            </span>
                        @endif
                    </div>

                    @if(!empty($gtChecklist))
                        <div class="mb-5">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Checklist Points ({{ count($gtChecklist) }})</p>
                            <div class="space-y-2">
                                @foreach($gtChecklist as $key => $value)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <div class="w-5 h-5 rounded-full {{ $value === 'pass' ? 'bg-green-100' : 'bg-gray-200' }} flex items-center justify-center flex-shrink-0">
                                            @if($value === 'pass')
                                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <span class="text-sm text-gray-700 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic mb-5">No checklist items recorded</p>
                    @endif

                    @if($generalTask->technician_notes)
                        <div class="p-4 bg-green-50 rounded-xl border border-green-100 mb-4">
                            <p class="text-xs font-semibold text-green-700 uppercase tracking-wider mb-2">Technician Notes</p>
                            <p class="text-sm text-gray-700">{{ $generalTask->technician_notes }}</p>
                        </div>
                    @endif

                    @if($generalTask->remarks)
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Remarks</p>
                            <p class="text-sm text-gray-700">{{ $generalTask->remarks }}</p>
                        </div>
                    @endif
                @else
                    <div class="text-center py-10">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">No general task records found</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
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
            'visual' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
            'functional' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>',
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
            'visual' => 'bg-[#0078C8]/10 text-[#0078C8] border border-[#0078C8]/30',
            'functional' => 'bg-purple-100 text-purple-800 border border-purple-200',
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
        if (str_contains($action, 'functional_testing_created')) {
            return 'Functional Testing Added';
        }

        $formatted = str_replace('_', ' ', $action);
        return ucwords($formatted);
    }

    // Helper function to format action type
    function formatActionType($action) {
        if (str_contains(strtolower($action), 'visual')) return 'Visual';
        if (str_contains(strtolower($action), 'functional')) return 'Functional';
        if (str_contains(strtolower($action), 'created')) return 'Creation';
        if (str_contains(strtolower($action), 'updated')) return 'Update';
        if (str_contains(strtolower($action), 'assigned')) return 'Assignment';

        return 'Event';
    }
@endphp

@push('scripts')
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
                    // 2. Clean query: remove parentheticals (Used Good), special chars, and redundant spaces
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
                    // 3. Fallback: Take first 5 words (usually Brand + Model + Basic Specs)
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
                        console.log('Fetched images for query:', q, this.images);
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
        // Smooth scroll for timeline
        const timeline = document.querySelector('.max-h-96');
        if (timeline) {
            timeline.style.scrollBehavior = 'smooth';
        }
    });

    function openVisualModal() {
        const modal = document.getElementById('visualInspectionModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeVisualModal(event) {
        if (event && event.target !== document.getElementById('visualInspectionModal')) return;
        const modal = document.getElementById('visualInspectionModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function openFunctionalModal() {
        const modal = document.getElementById('functionalTestingModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeFunctionalModal(event) {
        if (event && event.target !== document.getElementById('functionalTestingModal')) return;
        const modal = document.getElementById('functionalTestingModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function openGeneralTaskModal() {
        const modal = document.getElementById('generalTaskModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeGeneralTaskModal(event) {
        if (event && event.target !== document.getElementById('generalTaskModal')) return;
        const modal = document.getElementById('generalTaskModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('visualInspectionModal').classList.add('hidden');
            document.getElementById('visualInspectionModal').classList.remove('flex');
            document.getElementById('functionalTestingModal').classList.add('hidden');
            document.getElementById('functionalTestingModal').classList.remove('flex');
            document.getElementById('generalTaskModal').classList.add('hidden');
            document.getElementById('generalTaskModal').classList.remove('flex');
            document.body.style.overflow = '';
        }
    });
</script>
@endpush
