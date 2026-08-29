@extends('layouts.auth')

@section('title', 'Update Product')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                <!-- Header -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-black">Update Product</h2>
                        <a href="{{ route('warehouse.products.index') }}"
                            class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                            Back
                        </a>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <form id="formSubmit"
                          action_url="{{ route('warehouse.products.update', $product->id) }}"
                          back_url="{{ route('warehouse.products.index') }}"
                          method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-6">

                            <hr class="border-gray-200 mb-6">

                            <!-- Product ID - Full Width -->
                            <div class="mb-6">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">PRODUCT ID</label>
                                <div class="text-sm font-medium text-gray-900 bg-gray-50 px-4 py-2.5 rounded-md border border-gray-200" id="productIdValue">{{ $product->unique_id ?? 'product-'.$product->id }}</div>
                                <input type="hidden" name="product_id" id="productIdInput" value="{{ $product->id }}">
                                <input type="hidden" name="unique_id" value="{{ $product->unique_id ?? '' }}">
                            </div>

                            <!-- Two Column Layout for Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Category Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
                                    <select name="category_id" class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ ($product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                    <input type="text" id="title" name="title"
                                           value="{{ old('title', $product->title) }}"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                           placeholder="Enter product title">
                                </div>
                            </div>

                            <!-- Two Column Layout for Location and Status -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Location -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                                    <input type="text" name="location"
                                           value="{{ old('location', $product->location) }}"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                           placeholder="Enter warehouse location">
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select name="status" class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                        <option value="Active" {{ ($product->status ?? 'Active') == 'Active' ? 'selected' : '' }}>Active</option>
                                        <option value="Inactive" {{ ($product->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Two Column Layout for Disposition and Work Flow -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Disposition -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Disposition</label>
                                    <select name="disposition_id" class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                        <option value="">Select Disposition</option>
                                        @foreach($dispositions as $disposition)
                                            <option value="{{ $disposition->id }}" {{ ($product->disposition_id ?? '') == $disposition->id ? 'selected' : '' }}>
                                                {{ $disposition->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Work Flow Stage -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Work Flow Stage</label>
                                    <select name="work_flow_stage_id" class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                        <option value="">Select Work Flow Stage</option>
                                        @foreach($workflowStages as $stage)
                                            <option value="{{ $stage->id }}" {{ ($product->work_flow_stage_id ?? '') == $stage->id ? 'selected' : '' }}>
                                                {{ $stage->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Product Details - Full Width -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Product Details</label>
                                <textarea id="productDetails" name="product_details" rows="3"
                                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                          placeholder="Add detailed product description...">{{ old('product_details', $product->product_details) }}</textarea>
                            </div>

                            <!-- Specification Section -->
                            <div class="mb-6 bg-gray-50 p-5 rounded-lg border border-gray-200">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Specifications</label>

                                <div id="specificationsList" class="space-y-2 mb-4"></div>

                                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Key</label>
                                        <input type="text" id="specKeyInput"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                               placeholder="e.g., Color, Size, Weight">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Value</label>
                                        <input type="text" id="specValueInput"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                               placeholder="e.g., Red, Large, 2kg">
                                    </div>
                                    <div class="flex gap-2 md:col-span-1">
                                        <button type="button" onclick="addSpecification()"
                                                class="flex-1 px-4 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#0063A6] transition-colors shadow-sm">
                                            ADD
                                        </button>
                                        <button type="button" onclick="deleteSpecification()"
                                                class="px-4 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition-colors shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="specifications" id="specificationsInput" value="{{ old('specifications', json_encode($product->specifications ?? [])) }}">
                            </div>

                            <!-- Visual Inspection Section -->
                            <div class="border border-gray-200 rounded-lg p-5 mb-6 bg-white shadow-sm">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-base font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Visual Inspection
                                    </h3>
                                    <button type="button" onclick="toggleSection('visualInspectionContent')"
                                            class="text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div id="visualInspectionContent" class="space-y-5">
                                    <div id="visualDynamicChecklist" class="space-y-2 mb-4 bg-gray-50 p-4 rounded-lg"></div>

                                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Add New Inspection Point</h4>
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <input type="text" id="visualNewChecklistItem"
                                                   placeholder="Enter new inspection point..."
                                                   class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                                            <button type="button" onclick="addVisualChecklistItem()"
                                                    class="px-6 py-2.5 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
                                                ADD POINT
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">Press Enter or click ADD POINT to create a new checkbox</p>
                                    </div>

                                    <div>
                                        <label for="visualInspectionNotes" class="block text-sm font-medium text-gray-700 mb-2">Inspection Notes</label>
                                        <textarea id="visualInspectionNotes" name="visual_inspection_notes" rows="2"
                                                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                                  placeholder="Enter any additional inspection notes...">{{ old('visual_inspection_notes', $product->visualInspections->first()->inspection_notes ?? '') }}</textarea>
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Assign Grade</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            @php $visualGrade = $product->visualInspections->first()->grade ?? ''; @endphp
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all visual-grade-option {{ $visualGrade == 'A' ? 'bg-[#0078C8] text-white border-[#0078C8] shadow-md' : 'bg-white' }}"
                                                 data-grade="A" onclick="selectVisualGrade('A', this)">
                                                <div class="text-xl font-semibold {{ $visualGrade == 'A' ? 'text-white' : 'text-gray-900' }}">A</div>
                                                <div class="text-xs {{ $visualGrade == 'A' ? 'text-white' : 'text-gray-500' }} mt-1">Like New</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all visual-grade-option {{ $visualGrade == 'B' ? 'bg-[#0078C8] text-white border-[#0078C8] shadow-md' : 'bg-white' }}"
                                                 data-grade="B" onclick="selectVisualGrade('B', this)">
                                                <div class="text-xl font-semibold {{ $visualGrade == 'B' ? 'text-white' : 'text-gray-900' }}">B</div>
                                                <div class="text-xs {{ $visualGrade == 'B' ? 'text-white' : 'text-gray-500' }} mt-1">Minor Wear</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all visual-grade-option {{ $visualGrade == 'C' ? 'bg-[#0078C8] text-white border-[#0078C8] shadow-md' : 'bg-white' }}"
                                                 data-grade="C" onclick="selectVisualGrade('C', this)">
                                                <div class="text-xl font-semibold {{ $visualGrade == 'C' ? 'text-white' : 'text-gray-900' }}">C</div>
                                                <div class="text-xs {{ $visualGrade == 'C' ? 'text-white' : 'text-gray-500' }} mt-1">Major Use</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all visual-grade-option {{ $visualGrade == 'F' ? 'bg-[#0078C8] text-white border-[#0078C8] shadow-md' : 'bg-white' }}"
                                                 data-grade="F" onclick="selectVisualGrade('F', this)">
                                                <div class="text-xl font-semibold {{ $visualGrade == 'F' ? 'text-white' : 'text-gray-900' }}">F</div>
                                                <div class="text-xs {{ $visualGrade == 'F' ? 'text-white' : 'text-gray-500' }} mt-1">Faulty</div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="visual_grade" id="visualGrade" value="{{ $visualGrade }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Functional Testing Section -->
                            <div class="border border-gray-200 rounded-lg p-5 mb-6 bg-white shadow-sm">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-base font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Functional Testing
                                    </h3>
                                    <button type="button" onclick="toggleSection('functionalTestingContent')"
                                            class="text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div id="functionalTestingContent" class="space-y-5">
                                    <div id="functionalDynamicTestFields" class="space-y-4 mb-4"></div>

                                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Add New Test</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Test Name</label>
                                                <input type="text" id="functionalNewTestName" placeholder="Enter test name..."
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                                                <input type="text" id="functionalNewTestNotes" placeholder="Enter test notes..."
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" onclick="addFunctionalTestField()"
                                                    class="w-full px-6 py-2.5 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
                                                    ADD TEST
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">Press Enter in either input field to add test</p>
                                    </div>

                                    <div>
                                        <label for="functionalTestResults" class="block text-sm font-medium text-gray-700 mb-2">Overall Test Results Summary</label>
                                        <textarea id="functionalTestResults" name="functional_test_results" rows="2"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                            placeholder="Enter overall test summary...">{{ old('functional_test_results', $product->functionalTestings->first()->overall_results_summary ?? '') }}</textarea>
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Assign Grade</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            @php $functionalGrade = $product->functionalTestings->first()->grade ?? ''; @endphp
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all functional-grade-option {{ $functionalGrade == 'A' ? 'bg-[#0078C8] text-white border-[#0078C8] shadow-md' : 'bg-white' }}"
                                                 data-grade="A" onclick="selectFunctionalGrade('A', this)">
                                                <div class="text-xl font-semibold {{ $functionalGrade == 'A' ? 'text-white' : 'text-gray-900' }}">A</div>
                                                <div class="text-xs {{ $functionalGrade == 'A' ? 'text-white' : 'text-gray-500' }} mt-1">Like New</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all functional-grade-option {{ $functionalGrade == 'B' ? 'bg-[#0078C8] text-white border-[#0078C8] shadow-md' : 'bg-white' }}"
                                                 data-grade="B" onclick="selectFunctionalGrade('B', this)">
                                                <div class="text-xl font-semibold {{ $functionalGrade == 'B' ? 'text-white' : 'text-gray-900' }}">B</div>
                                                <div class="text-xs {{ $functionalGrade == 'B' ? 'text-white' : 'text-gray-500' }} mt-1">Minor Wear</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all functional-grade-option {{ $functionalGrade == 'C' ? 'bg-[#0078C8] text-white border-[#0078C8] shadow-md' : 'bg-white' }}"
                                                 data-grade="C" onclick="selectFunctionalGrade('C', this)">
                                                <div class="text-xl font-semibold {{ $functionalGrade == 'C' ? 'text-white' : 'text-gray-900' }}">C</div>
                                                <div class="text-xs {{ $functionalGrade == 'C' ? 'text-white' : 'text-gray-500' }} mt-1">Major Use</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all functional-grade-option {{ $functionalGrade == 'F' ? 'bg-[#0078C8] text-white border-[#0078C8] shadow-md' : 'bg-white' }}"
                                                 data-grade="F" onclick="selectFunctionalGrade('F', this)">
                                                <div class="text-xl font-semibold {{ $functionalGrade == 'F' ? 'text-white' : 'text-gray-900' }}">F</div>
                                                <div class="text-xs {{ $functionalGrade == 'F' ? 'text-white' : 'text-gray-500' }} mt-1">Faulty</div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="functional_grade" id="functionalGrade" value="{{ $functionalGrade }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Assigned Personnel -->
                            <div class="border border-gray-200 rounded-lg p-5 mb-6 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Assigned Personnel</h4>
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-sm font-medium shadow-sm">
                                                {{ $product->assignedTo ? strtoupper(substr($product->assignedTo->full_name, 0, 2)) : 'NA' }}
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900" id="assignedPersonnel">
                                                    {{ $product->assignedTo->full_name ?? 'Unassigned' }}
                                                </span>
                                                <p class="text-xs text-gray-500">Current assignee</p>
                                            </div>
                                            <input type="hidden" name="assigned_to" id="assignedToInput" value="{{ $product->assigned_to ?? '' }}">
                                        </div>
                                    </div>
                                    <button type="button" onclick="openChangeAssigneeModal()"
                                            class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-xs font-medium hover:bg-[#0063A6] transition-colors shadow-sm">
                                        CHANGE ASSIGNEE
                                    </button>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row justify-between items-center mt-8 pt-6 border-t border-gray-200 gap-4">
                                <div class="flex flex-wrap gap-3">
                                    <button type="button" onclick="handleAction('SCRAP', {{ $product->id }})"
                                            class="px-5 py-2.5 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors shadow-sm flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        SCRAP
                                    </button>
                                    <button type="button" onclick="handleAction('HOLD', {{ $product->id }})"
                                            class="px-5 py-2.5 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600 transition-colors shadow-sm flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        HOLD
                                    </button>
                                    <button type="button" onclick="handleAction('PASS', {{ $product->id }})"
                                            class="px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors shadow-sm flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        PASS
                                    </button>
                                </div>

                                <div class="flex gap-3">
                                    <a href="{{ route('warehouse.products.index') }}"
                                       class="px-5 py-2.5 border border-[#0078C8] text-[#0078C8] rounded-lg text-sm font-medium hover:bg-[#e6f4ff] transition-colors">
                                        CANCEL
                                    </a>
                                    <button type="submit" id="submitBtn"
                                            class="px-8 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#0063A6] transition-colors shadow-md flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        UPDATE PRODUCT
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Assignee Modal -->
    <div id="changeAssigneeModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 backdrop-blur-sm">
        <div class="relative top-20 mx-auto p-6 border w-[90%] md:w-[450px] shadow-xl rounded-xl bg-white">
            <div class="mt-1">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-semibold text-gray-900">Change Assignee</h3>
                    <button onclick="closeChangeAssigneeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-5">
                    <label for="assigneeSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Personnel</label>
                    <select id="assigneeSelect" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                        <option value="">Select personnel...</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}"
                                    data-initials="{{ strtoupper(substr($technician->full_name, 0, 2)) }}"
                                    {{ ($product->assigned_to ?? '') == $technician->id ? 'selected' : '' }}>
                                {{ $technician->full_name }} ({{ $technician->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                    <button onclick="closeChangeAssigneeModal()" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        CANCEL
                    </button>
                    <button onclick="updateAssignee()" class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#0063A6] transition-colors shadow-sm">
                        CHANGE ASSIGNEE
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[300px] max-w-[380px] bg-white border border-gray-200 shadow-lg rounded-lg p-4">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>
@endsection

@push('scripts')
<script>
    const productData = @json($product);
    let specifications = productData.specifications || [];

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        toast.classList.remove('hidden');
        content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    ${type === 'success'
                        ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                        : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;
        setTimeout(hideToast, 3000);
    }

    function hideToast() {
        document.getElementById('toast').classList.add('hidden');
    }

    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) section.classList.toggle('hidden');
    }

    function addSpecification() {
        const keyInput = document.getElementById('specKeyInput');
        const valueInput = document.getElementById('specValueInput');
        const key = keyInput.value.trim();
        const value = valueInput.value.trim();
        if (!key || !value) { showToast('Please enter both key and value', 'error'); return; }
        specifications.push({ key, value });
        renderSpecifications();
        updateSpecificationsInput();
        keyInput.value = '';
        valueInput.value = '';
        showToast('Specification added successfully', 'success');
    }

    function deleteSpecification() {
        document.getElementById('specKeyInput').value = '';
        document.getElementById('specValueInput').value = '';
        showToast('Inputs cleared', 'success');
    }

    function renderSpecifications() {
        const container = document.getElementById('specificationsList');
        if (!container) return;
        container.innerHTML = '';
        specifications.forEach((spec, index) => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200';
            div.innerHTML = `
                <div class="flex-1">
                    <span class="font-medium text-sm text-gray-700">${spec.key}:</span>
                    <span class="text-sm text-gray-600 ml-2">${spec.value}</span>
                </div>
                <button type="button" onclick="removeSpecification(${index})" class="text-red-500 hover:text-red-700 ml-2 p-1 rounded-full hover:bg-red-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
        });
    }

    function removeSpecification(index) {
        specifications.splice(index, 1);
        renderSpecifications();
        updateSpecificationsInput();
        showToast('Specification removed', 'success');
    }

    function updateSpecificationsInput() {
        const input = document.getElementById('specificationsInput');
        if (input) input.value = JSON.stringify(specifications);
    }

    let visualChecklistCounter = 0;

    function loadVisualChecklist() {
        const visualInspection = productData.visual_inspections?.[0];
        if (visualInspection?.checklist_items && Array.isArray(visualInspection.checklist_items)) {
            visualInspection.checklist_items.forEach(item => addExistingVisualChecklistItem(item, true));
        }
    }

    function addExistingVisualChecklistItem(itemText, isChecked = true) {
        const container = document.getElementById('visualDynamicChecklist');
        if (!container) return;
        const itemId = 'visual-checklist-item-' + visualChecklistCounter++;
        const displayText = typeof itemText === 'object' ? (itemText.value || itemText.name || JSON.stringify(itemText)) : itemText;
        const itemValue = typeof itemText === 'object' ? (itemText.value || itemText.name || itemText) : itemText;
        const checklistItem = document.createElement('div');
        checklistItem.className = 'flex items-center justify-between group bg-white p-2 rounded-lg border border-gray-200 hover:border-[#0078C8] transition-colors';
        checklistItem.innerHTML = `
            <div class="flex items-center flex-1">
                <input type="checkbox" id="${itemId}" name="visual_checklist[]" value="${itemValue}"
                       class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300 cursor-pointer"
                       ${isChecked ? 'checked' : ''}>
                <label for="${itemId}" class="ml-3 text-sm text-gray-700 cursor-pointer font-medium">${displayText}</label>
            </div>
            <button type="button" onclick="removeVisualChecklistItem(this)"
                    class="ml-2 text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-all p-1 rounded-full hover:bg-red-50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        container.appendChild(checklistItem);
    }

    function addVisualChecklistItem() {
        const input = document.getElementById('visualNewChecklistItem');
        if (!input) return;
        const itemText = input.value.trim();
        if (!itemText) { showToast('Please enter an inspection point', 'error'); return; }
        addExistingVisualChecklistItem(itemText, true);
        input.value = '';
        input.focus();
        showToast('Visual checklist item added successfully', 'success');
    }

    function removeVisualChecklistItem(button) {
        const checklistItem = button.closest('.flex.items-center.justify-between');
        if (checklistItem) { checklistItem.remove(); showToast('Visual checklist item removed', 'success'); }
    }

    function selectVisualGrade(grade, element) {
        document.querySelectorAll('.visual-grade-option').forEach(option => {
            option.classList.remove('bg-[#0078C8]', 'text-white', 'border-[#0078C8]', 'shadow-md');
            const titleDiv = option.querySelector('div:first-child');
            const descDiv = option.querySelector('div:last-child');
            if (titleDiv) { titleDiv.classList.remove('text-white'); titleDiv.classList.add('text-gray-900'); }
            if (descDiv) { descDiv.classList.remove('text-white'); descDiv.classList.add('text-gray-500'); }
        });
        element.classList.add('bg-[#0078C8]', 'text-[#0078C8]', 'border-[#0078C8]', 'shadow-md');
        const titleDiv = element.querySelector('div:first-child');
        const descDiv = element.querySelector('div:last-child');
        if (titleDiv) { titleDiv.classList.remove('text-gray-900'); titleDiv.classList.add('text-[#0078C8]'); }
        if (descDiv) { descDiv.classList.remove('text-gray-500'); descDiv.classList.add('text-[#0078C8]'); }
        const visualGradeInput = document.getElementById('visualGrade');
        if (visualGradeInput) visualGradeInput.value = grade;
    }

    let functionalTestFieldCounter = 0;

    function loadFunctionalTests() {
        const functionalTesting = productData.functional_testings?.[0];
        if (functionalTesting?.custom_tests) {
            Object.values(functionalTesting.custom_tests).forEach(test => {
                addExistingFunctionalTestField(test.name, test.notes, test.result);
            });
        }
    }

    function addExistingFunctionalTestField(testName, testNotes, testResult = '') {
        const container = document.getElementById('functionalDynamicTestFields');
        if (!container) return;
        const sanitizedTestName = testName.toLowerCase().replace(/[^a-z0-9]/g, '_');
        functionalTestFieldCounter++;
        const testField = document.createElement('div');
        testField.className = 'p-4 border border-gray-200 rounded-lg bg-white group hover:border-[#0078C8] transition-colors';
        testField.innerHTML = `
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="text-sm font-semibold text-gray-800">${testName}</h4>
                    <p class="text-xs text-gray-500 mt-1">${testNotes || 'No notes'}</p>
                </div>
                <button type="button" onclick="removeFunctionalTestField(this)"
                        class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Test Result</label>
                <div class="flex items-center space-x-6">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="functional_test_result_${sanitizedTestName}" value="pass"
                               ${testResult === 'pass' ? 'checked' : ''}
                               class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 cursor-pointer">
                        <span class="ml-2 text-sm text-gray-700">Pass</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="functional_test_result_${sanitizedTestName}" value="fail"
                               ${testResult === 'fail' ? 'checked' : ''}
                               class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 cursor-pointer">
                        <span class="ml-2 text-sm text-gray-700">Fail</span>
                    </label>
                </div>
            </div>
            <input type="hidden" name="functional_custom_tests[${sanitizedTestName}][name]" value="${testName}">
            <input type="hidden" name="functional_custom_tests[${sanitizedTestName}][notes]" value="${testNotes || ''}">
            <input type="hidden" name="functional_custom_tests[${sanitizedTestName}][result]" value="${testResult}" id="functional_test_result_${sanitizedTestName}_hidden">
        `;
        container.appendChild(testField);
        const radioButtons = testField.querySelectorAll(`input[name="functional_test_result_${sanitizedTestName}"]`);
        const hiddenInput = document.getElementById(`functional_test_result_${sanitizedTestName}_hidden`);
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() { if (hiddenInput) hiddenInput.value = this.value; });
        });
    }

    function addFunctionalTestField() {
        const testNameInput = document.getElementById('functionalNewTestName');
        const testNotesInput = document.getElementById('functionalNewTestNotes');
        if (!testNameInput || !testNotesInput) return;
        const testName = testNameInput.value.trim();
        const testNotes = testNotesInput.value.trim();
        if (!testName) { showToast('Please enter a test name', 'error'); testNameInput.focus(); return; }
        addExistingFunctionalTestField(testName, testNotes);
        testNameInput.value = '';
        testNotesInput.value = '';
        testNameInput.focus();
        showToast('Functional test added successfully', 'success');
    }

    function removeFunctionalTestField(button) {
        const testField = button.closest('.p-4.border.border-gray-200.rounded-lg');
        if (testField) { testField.remove(); showToast('Functional test removed', 'success'); }
    }

    function selectFunctionalGrade(grade, element) {
        document.querySelectorAll('.functional-grade-option').forEach(option => {
            option.classList.remove('bg-[#0078C8]', 'text-white', 'border-[#0078C8]', 'shadow-md');
            const titleDiv = option.querySelector('div:first-child');
            const descDiv = option.querySelector('div:last-child');
            if (titleDiv) { titleDiv.classList.remove('text-white'); titleDiv.classList.add('text-gray-900'); }
            if (descDiv) { descDiv.classList.remove('text-white'); descDiv.classList.add('text-gray-500'); }
        });
        element.classList.add('bg-[#0078C8]', 'text-[#0078C8]', 'border-[#0078C8]', 'shadow-md');
        const titleDiv = element.querySelector('div:first-child');
        const descDiv = element.querySelector('div:last-child');
        if (titleDiv) { titleDiv.classList.remove('text-gray-900'); titleDiv.classList.add('text-[#0078C8]'); }
        if (descDiv) { descDiv.classList.remove('text-gray-500'); descDiv.classList.add('text-[#0078C8]'); }
        const functionalGradeInput = document.getElementById('functionalGrade');
        if (functionalGradeInput) functionalGradeInput.value = grade;
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderSpecifications();
        updateSpecificationsInput();
        loadVisualChecklist();
        loadFunctionalTests();

        const visualInput = document.getElementById('visualNewChecklistItem');
        if (visualInput) {
            visualInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); addVisualChecklistItem(); } });
        }

        const testNameInput = document.getElementById('functionalNewTestName');
        const testNotesInput = document.getElementById('functionalNewTestNotes');
        if (testNameInput) {
            testNameInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); addFunctionalTestField(); } });
        }
        if (testNotesInput) {
            testNotesInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); addFunctionalTestField(); } });
        }
    });

    function openChangeAssigneeModal() {
        const modal = document.getElementById('changeAssigneeModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeChangeAssigneeModal() {
        const modal = document.getElementById('changeAssigneeModal');
        if (modal) modal.classList.add('hidden');
    }

    function updateAssignee() {
        const select = document.getElementById('assigneeSelect');
        if (!select) return;
        const selectedValue = select.value;
        if (!selectedValue) { showToast('Please select a personnel', 'error'); return; }
        const selectedOption = select.options[select.selectedIndex];
        const selectedText = selectedOption.text.split(' (')[0];
        const initials = selectedOption.dataset.initials;
        const assignedPersonnel = document.getElementById('assignedPersonnel');
        const assignedToInput = document.getElementById('assignedToInput');
        if (assignedPersonnel) assignedPersonnel.textContent = selectedText;
        if (assignedToInput) assignedToInput.value = selectedValue;
        const avatar = document.querySelector('.w-10.h-10.rounded-full');
        if (avatar && initials) avatar.textContent = initials;
        showToast('Assignee updated successfully', 'success');
        closeChangeAssigneeModal();
    }

    function handleAction(action, productId) {
        const token = document.querySelector('input[name="_token"]');
        if (!token) { showToast('CSRF token not found', 'error'); return; }
        fetch(`/warehouse/products/${productId}/action`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token.value },
            body: JSON.stringify({ action: action })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(`${action} action performed successfully`, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message || `Failed to perform ${action} action`, 'error');
            }
        })
        .catch(() => showToast(`Error performing ${action} action`, 'error'));
    }

    @if(session('success'))
        showToast('{{ session('success') }}', 'success');
    @endif

    @if(session('error'))
        showToast('{{ session('error') }}', 'error');
    @endif
</script>
@endpush
